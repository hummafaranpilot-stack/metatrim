"""
Update Pricing Cards Across All Pages
This script copies the updated pricing cards CSS and HTML from short/go/index.html
to all other pages, replacing all occurrences.
"""

import re
import shutil
from pathlib import Path

# Paths
base_dir = Path(r"d:\TrustedNutraProducts\meta trim\New Funnel\v2")
source_file = base_dir / "short" / "go" / "index.html"

target_files = [
    base_dir / "best" / "go" / "go.html",
    base_dir / "long2" / "index.html",
    base_dir / "long3" / "index.html",
    base_dir / "long4" / "go" / "index.html",
    base_dir / "vsl" / "index.html",
]

print("=" * 60)
print("PRICING CARDS UPDATE SCRIPT")
print("=" * 60)

# Read source file
print(f"\n📖 Reading source file: {source_file.name}")
with open(source_file, 'r', encoding='utf-8') as f:
    source_content = f.read()

# Extract CSS section (from /* NEW PRICING CARDS CSS */ to end of media queries)
css_pattern = r'/\* NEW PRICING CARDS CSS \*/.*?(?=</style>)'
css_match = re.search(css_pattern, source_content, re.DOTALL)
if not css_match:
    print("❌ ERROR: Could not find CSS section in source file!")
    exit(1)

new_css = css_match.group(0)
print(f"✅ Extracted CSS section ({len(new_css)} characters)")

# Extract HTML section
html_pattern = r'<!-- NEW PRICING CARDS -->.*?<!-- END NEW PRICING CARDS -->'
html_match = re.search(html_pattern, source_content, re.DOTALL)
if not html_match:
    print("❌ ERROR: Could not find HTML section in source file!")
    exit(1)

new_html = html_match.group(0)
print(f"✅ Extracted HTML section ({len(new_html)} characters)")

# Update each target file
total_updates = 0
for target_file in target_files:
    if not target_file.exists():
        print(f"\n⚠️  SKIPPED: {target_file} (file not found)")
        continue

    print(f"\n🔄 Processing: {target_file}")

    # Create backup
    backup_file = target_file.with_suffix('.html.backup')
    shutil.copy2(target_file, backup_file)
    print(f"   💾 Backup created: {backup_file.name}")

    # Read target file
    with open(target_file, 'r', encoding='utf-8') as f:
        target_content = f.read()

    # Replace CSS section (should be only one)
    css_replacements = len(re.findall(css_pattern, target_content, re.DOTALL))
    if css_replacements > 0:
        target_content = re.sub(css_pattern, new_css, target_content, flags=re.DOTALL)
        print(f"   ✅ Replaced CSS section ({css_replacements} occurrence)")
    else:
        print(f"   ⚠️  No CSS section found to replace")

    # Replace HTML sections (may be multiple)
    html_replacements = len(re.findall(html_pattern, target_content, re.DOTALL))
    if html_replacements > 0:
        target_content = re.sub(html_pattern, new_html, target_content, flags=re.DOTALL)
        print(f"   ✅ Replaced HTML sections ({html_replacements} occurrences)")
        total_updates += html_replacements
    else:
        print(f"   ⚠️  No HTML sections found to replace")

    # Write updated content
    with open(target_file, 'w', encoding='utf-8') as f:
        f.write(target_content)

    print(f"   💾 File updated successfully!")

print("\n" + "=" * 60)
print(f"✅ COMPLETE! Updated {total_updates} pricing card sections")
print(f"📁 Backups saved with .backup extension")
print("=" * 60)

# Open files in browser
print("\n🌐 Opening updated files in browser...")
import subprocess
for target_file in target_files:
    if target_file.exists():
        subprocess.Popen(['start', '', str(target_file)], shell=True)
        print(f"   Opened: {target_file.name}")

print("\n✅ Done! Check your browser.")
