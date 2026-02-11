const fs = require('fs');
const path = require('path');

// Arguments: target_file grid_template_file
const targetFile = process.argv[2];
const gridTemplateFile = process.argv[3];

if (!targetFile || !gridTemplateFile) {
  console.error('Usage: node update_file.js <target_file> <grid_template_file>');
  process.exit(1);
}

// Read source CSS from v2/short/go/index.html
const sourceFile = path.resolve(__dirname, '..', 'v2', 'short', 'go', 'index.html');
const sourceContent = fs.readFileSync(sourceFile, 'utf8');

// Extract CSS from /* NEW PRICING CARDS CSS */ to the closing }
const cssStartMarker = '/* NEW PRICING CARDS CSS */';
const cssStartIdx = sourceContent.indexOf(cssStartMarker);
if (cssStartIdx === -1) {
  console.error('Could not find CSS start marker in source');
  process.exit(1);
}

// Find the end of the CSS block - look for </style> after the CSS
const cssEndSearch = sourceContent.indexOf('</style>', cssStartIdx);
if (cssEndSearch === -1) {
  console.error('Could not find </style> after CSS');
  process.exit(1);
}

// Work backward from </style> to find last } (the actual CSS end)
let cssEndIdx = cssEndSearch;
for (let i = cssEndSearch - 1; i > cssStartIdx; i--) {
  if (sourceContent[i] === '}') {
    cssEndIdx = i + 1;
    break;
  }
}

const newCSS = sourceContent.substring(cssStartIdx, cssEndIdx);
const fontImport = '@import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap");';

// Read the grid template
const gridHTML = fs.readFileSync(gridTemplateFile, 'utf8');

// Read target file
let content = fs.readFileSync(targetFile, 'utf8');

// === STEP 1: Replace CSS ===
// Find the first <style> block that contains old pricing card CSS
// Look for patterns like .mt-product-grid, .mt-product-card, or old card CSS
const styleRegex = /<style[^>]*>/gi;
let styleMatch;
let cssReplaced = false;

while ((styleMatch = styleRegex.exec(content)) !== null) {
  const styleStart = styleMatch.index;
  const styleTagEnd = styleStart + styleMatch[0].length;
  const styleCloseIdx = content.indexOf('</style>', styleTagEnd);
  if (styleCloseIdx === -1) continue;

  const styleContent = content.substring(styleTagEnd, styleCloseIdx);

  // Check if this style block has pricing card CSS
  if (styleContent.includes('.mt-product-grid') ||
      styleContent.includes('.mt-product-card') ||
      styleContent.includes('mt-subscription') ||
      styleContent.includes('mt-buy-now') ||
      styleContent.includes('.mt-featured')) {

    // Check if there's an @import before this style block's content
    const hasImport = styleContent.includes('@import');

    // Replace the content of this style block
    // Keep everything before the old pricing CSS and after it
    const oldCssStart = styleContent.indexOf('.mt-product-grid');
    let otherCssBefore = '';

    if (oldCssStart > 0) {
      otherCssBefore = styleContent.substring(0, oldCssStart);
    }

    // Build replacement - keep @import if not already present
    let newStyleContent = '\n' + fontImport + '\n';
    if (otherCssBefore.trim()) {
      // Remove any existing @import from the "before" section to avoid duplicates
      otherCssBefore = otherCssBefore.replace(/@import\s+url\([^)]+\)\s*;?\s*/g, '');
      if (otherCssBefore.trim()) {
        newStyleContent += otherCssBefore;
      }
    }
    newStyleContent += newCSS + '\n';

    content = content.substring(0, styleTagEnd) + newStyleContent + content.substring(styleCloseIdx);
    cssReplaced = true;
    console.log('CSS replaced in style block starting at position', styleStart);
    break;
  }
}

if (!cssReplaced) {
  console.log('WARNING: No existing pricing CSS found to replace. Injecting CSS before first </style>.');
  const firstStyleClose = content.indexOf('</style>');
  if (firstStyleClose !== -1) {
    const cssToInject = '\n' + fontImport + '\n' + newCSS + '\n';
    content = content.substring(0, firstStyleClose) + cssToInject + content.substring(firstStyleClose);
    console.log('CSS injected before </style> at position', firstStyleClose);
  }
}

// === STEP 2: Replace all product grids ===
// Find all mt-product-grid divs and their boundaries
const gridMarkers = [
  '<div class="mt-product-grid">',
  '<div id="buynow" class="mt-product-grid">'
];

// Collect all grid positions first
const gridPositions = [];

for (const marker of gridMarkers) {
  let searchStart = 0;
  while (true) {
    const gridStart = content.indexOf(marker, searchStart);
    if (gridStart === -1) break;

    // Find the end of this grid by tracking div nesting
    let depth = 0;
    let pos = gridStart;
    let gridEnd = -1;

    while (pos < content.length) {
      const openDiv = content.indexOf('<div', pos);
      const closeDiv = content.indexOf('</div>', pos);

      if (closeDiv === -1) break;

      if (openDiv !== -1 && openDiv < closeDiv) {
        depth++;
        pos = openDiv + 4;
      } else {
        depth--;
        if (depth === 0) {
          gridEnd = closeDiv + 6; // length of '</div>'
          break;
        }
        pos = closeDiv + 6;
      }
    }

    if (gridEnd !== -1) {
      // Also check for subscription-guarantee div after the grid
      const afterGrid = content.substring(gridEnd, gridEnd + 500);
      let extraEnd = gridEnd;

      const guaranteeMatch = afterGrid.match(/\s*<div class="mt-subscription-guarantee">/);
      if (guaranteeMatch) {
        const guaranteeStart = gridEnd + guaranteeMatch.index;
        // Find end of guarantee div
        let gDepth = 0;
        let gPos = guaranteeStart;
        while (gPos < content.length) {
          const gOpen = content.indexOf('<div', gPos);
          const gClose = content.indexOf('</div>', gPos);
          if (gClose === -1) break;
          if (gOpen !== -1 && gOpen < gClose) {
            gDepth++;
            gPos = gOpen + 4;
          } else {
            gDepth--;
            if (gDepth === 0) {
              extraEnd = gClose + 6;
              break;
            }
            gPos = gClose + 6;
          }
        }
      }

      gridPositions.push({ start: gridStart, end: extraEnd, marker: marker });
    }

    searchStart = gridStart + marker.length;
  }
}

// Sort by position descending so we replace from end to start
gridPositions.sort((a, b) => b.start - a.start);

console.log(`Found ${gridPositions.length} grids to replace`);

for (const pos of gridPositions) {
  content = content.substring(0, pos.start) + gridHTML + content.substring(pos.end);
  console.log(`Replaced grid at position ${pos.start}-${pos.end}`);
}

// Write the result
fs.writeFileSync(targetFile, content, 'utf8');

// Verify
const verifyContent = fs.readFileSync(targetFile, 'utf8');
const badgeCount = (verifyContent.match(/mt-badge-images/g) || []).length;
const toggleCount = (verifyContent.match(/mt-subscription-toggle/g) || []).length;
const priceCheck = verifyContent.includes('$79');
const gridCount = (verifyContent.match(/mt-product-grid/g) || []).length;

console.log(`\nVerification:`);
console.log(`  Grids (mt-product-grid): ${gridCount}`);
console.log(`  Badge divs: ${badgeCount}`);
console.log(`  Toggle divs: ${toggleCount}`);
console.log(`  Has $79 price: ${priceCheck}`);
console.log('Done!');
