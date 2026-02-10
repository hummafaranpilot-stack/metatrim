#!/bin/bash

# List of files to update (remaining 8 files)
files=(
  "d:/TrustedNutraProducts/meta trim/New Funnel/v2/long4/go/index.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v2/best/go/go.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v1/long2/index.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v2/long2/index.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v1/long3/page5.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v2/long3/page5.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v1/index_disabled.html"
  "d:/TrustedNutraProducts/meta trim/New Funnel/v2/index_disabled.html"
)

for file in "${files[@]}"; do
  if [ -f "$file" ]; then
    echo "Processing: $file"

    # Create backup
    cp "$file" "$file.bak"

    echo "File backed up and ready for manual updates"
  else
    echo "File not found: $file"
  fi
done

echo ""
echo "All files backed up. Please apply the same CSS/JS/HTML changes manually using the Edit tool."
echo "Pattern established from the first 3 files can be replicated."
