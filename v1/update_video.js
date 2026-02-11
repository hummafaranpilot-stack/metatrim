const fs = require('fs');
const path = require('path');

// Read source CSS from v2/short/go/index.html
const sourceFile = path.resolve(__dirname, '..', 'v2', 'short', 'go', 'index.html');
const sourceContent = fs.readFileSync(sourceFile, 'utf8');

const cssStartMarker = '/* NEW PRICING CARDS CSS */';
const cssStartIdx = sourceContent.indexOf(cssStartMarker);
const cssEndSearch = sourceContent.indexOf('</style>', cssStartIdx);
let cssEndIdx = cssEndSearch;
for (let i = cssEndSearch - 1; i > cssStartIdx; i--) {
  if (sourceContent[i] === '}') {
    cssEndIdx = i + 1;
    break;
  }
}
const newCSS = sourceContent.substring(cssStartIdx, cssEndIdx);
const fontImport = '@import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap");';

// Read grid template (1-deep since video/ is 1 level deep from root)
const gridHTML = fs.readFileSync(path.resolve(__dirname, 'temp_grid_1deep.html'), 'utf8');

// Read target file
const targetFile = path.resolve(__dirname, 'video', 'index.html');
let content = fs.readFileSync(targetFile, 'utf8');
const lines = content.split('\n');

console.log(`Total lines: ${lines.length}`);

// Strategy: Find the old card sections and CSS blocks by markers
// Card sections start with: <div class="desktop-width row justify-content-center fade-container fe-atc-zindex">
// Card CSS blocks start with: <style> followed by .atc p, .atc-tab

// Find all occurrences of card HTML sections
const cardMarker = 'desktop-width row justify-content-center fade-container fe-atc-zindex';
const cardPositions = [];
let searchPos = 0;
while (true) {
  const idx = content.indexOf(cardMarker, searchPos);
  if (idx === -1) break;

  // Find the start of this div (go back to find '<div')
  let divStart = content.lastIndexOf('<div', idx);

  // Find the end of this div by tracking nesting
  let depth = 0;
  let pos = divStart;
  let divEnd = -1;

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
        divEnd = closeDiv + 6;
        break;
      }
      pos = closeDiv + 6;
    }
  }

  if (divEnd !== -1) {
    cardPositions.push({ start: divStart, end: divEnd });
    console.log(`Found card section at char ${divStart}-${divEnd}`);
  }

  searchPos = idx + cardMarker.length;
}

// Find all old ATC CSS style blocks (containing ".atc p, .atc-tab")
const atcCssMarker = '.atc p, .atc-tab';
const cssPositions = [];
searchPos = 0;
while (true) {
  const idx = content.indexOf(atcCssMarker, searchPos);
  if (idx === -1) break;

  // Find the <style> tag before this
  let styleStart = content.lastIndexOf('<style', idx);
  // Find the </style> after this
  let styleEnd = content.indexOf('</style>', idx);
  if (styleEnd !== -1) styleEnd += 8; // include </style>

  if (styleStart !== -1 && styleEnd !== -1) {
    cssPositions.push({ start: styleStart, end: styleEnd });
    console.log(`Found ATC CSS block at char ${styleStart}-${styleEnd}`);
  }

  searchPos = idx + atcCssMarker.length;
}

// Also check for the MBG banner row right after each card section
// We'll keep those - they're separate divs

// Now replace from end to start
const allReplacements = [];

// For each card section, replace with new grid
for (const pos of cardPositions) {
  allReplacements.push({ ...pos, type: 'card', replacement: gridHTML });
}

// For CSS blocks, first one gets the full new CSS, second one gets empty (or we can put it again)
for (let i = 0; i < cssPositions.length; i++) {
  const newStyleBlock = `<style>\n${fontImport}\n${newCSS}\n</style>`;
  allReplacements.push({ ...cssPositions[i], type: 'css', replacement: newStyleBlock });
}

// Sort all replacements by start position descending
allReplacements.sort((a, b) => b.start - a.start);

console.log(`\nPerforming ${allReplacements.length} replacements:`);
for (const r of allReplacements) {
  console.log(`  ${r.type}: ${r.start}-${r.end}`);
  content = content.substring(0, r.start) + r.replacement + content.substring(r.end);
}

// Write result
fs.writeFileSync(targetFile, content, 'utf8');

// Verify
const verifyContent = fs.readFileSync(targetFile, 'utf8');
const badgeCount = (verifyContent.match(/mt-badge-images/g) || []).length;
const gridCount = (verifyContent.match(/mt-product-grid/g) || []).length;
const priceCheck = verifyContent.includes('$79');
const oldAtcCount = (verifyContent.match(/class="atc"/g) || []).length;
const buyGoodsCount = (verifyContent.match(/buygoods\.com/g) || []).length;

console.log(`\nVerification:`);
console.log(`  Grids (mt-product-grid): ${gridCount}`);
console.log(`  Badge divs: ${badgeCount}`);
console.log(`  Has $79 price: ${priceCheck}`);
console.log(`  Old atc cards remaining: ${oldAtcCount}`);
console.log(`  BuyGoods links: ${buyGoodsCount}`);
console.log('Done!');
