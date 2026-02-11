const fs = require('fs');
const path = require('path');

const files = [
  'best/go/go.html',
  'long4/go/index.html',
  'long3/page5.html',
  'short/go/index.html',
  'long2/index.html',
  'video/index.html'
];

const v3Dir = __dirname;

for (const file of files) {
  const filePath = path.join(v3Dir, file);
  let content = fs.readFileSync(filePath, 'utf8');
  const original = content;

  // HTML replacements
  content = content.replace(/data-card="4bottle"/g, 'data-card="3bottle"');
  content = content.replace(/<h3>4 BOTTLES<\/h3>/g, '<h3>3 BOTTLES</h3>');
  content = content.replace(/FREE SHIPPING &bull; 120 DAY SUPPLY/g, 'FREE SHIPPING &bull; 90 DAY SUPPLY');
  content = content.replace(/3-bottle-buy-btn\.png/g, '3bottlesupgrade.png');

  // Update totals: $706 strikethrough and $256 final for the 4-bottle card
  content = content.replace(/\$706/g, '$642');
  content = content.replace(/\$256/g, '$192');

  // CSS replacements for ::before pseudo-elements
  content = content.replace(/\[data-card="4bottle"\]/g, '[data-card="3bottle"]');
  content = content.replace(/FREE SHIPPING • 120 DAY SUPPLY/g, 'FREE SHIPPING • 90 DAY SUPPLY');

  if (content !== original) {
    fs.writeFileSync(filePath, content, 'utf8');
    const changes = [];
    const c3bottle = (content.match(/3bottle/g) || []).length;
    const c4bottle = (content.match(/4bottle/g) || []).length;
    const img = (content.match(/3bottlesupgrade\.png/g) || []).length;
    console.log(`${file}: updated | 3bottle refs: ${c3bottle} | 4bottle remaining: ${c4bottle} | new image: ${img}`);
  } else {
    console.log(`${file}: NO CHANGES`);
  }
}

console.log('\nDone!');
