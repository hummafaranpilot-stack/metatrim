# PowerShell script to update pricing card CSS and JavaScript across multiple files

$files = @(
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\short\go\index.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\long4\go\index.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\best\go\go.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v1\long2\index.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\long2\index.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v1\long3\page5.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\long3\page5.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v1\index_disabled.html",
    "d:\TrustedNutraProducts\meta trim\New Funnel\v2\index_disabled.html"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Processing: $file"

        $content = Get-Content $file -Raw -Encoding UTF8

        # CSS Updates - Desktop
        $content = $content -replace 'padding: 1\.2rem 1rem;(\s*color: white;)', 'padding: 0.6rem 1rem;$1'
        $content = $content -replace '(\.mt-product-image img \{[^}]*max-width: )280px;', '$1252px;'
        $content = $content -replace '(\.mt-savings-badge \{[^}]*font-size: )1\.1rem;', '$10.85rem;$2line-height: 1.3;'
        $content = $content -replace '(\.mt-buy-now-btn \{[^}]*margin-top: )5px;', '$12px;'
        $content = $content -replace '(\.mt-shipping-text \{[^}]*margin-top: )8px;', '$13px;'
        $content = $content -replace '(\.mt-total-line \{[^}]*margin-top: )6px;', '$13px;'
        $content = $content -replace '(\.mt-payment-icons \{[^}]*margin: )1rem 0;', '$10.4rem 0;'
        $content = $content -replace '(\.mt-subscribe-savings \{[^}]*margin: )8px 0;', '$14px 0;'
        $content = $content -replace '(\.mt-subscribe-savings \{[^}]*padding: )10px 12px;', '$16px 10px;'
        $content = $content -replace '(\.mt-subscribe-savings \.line1 \{[^}]*font-size: )0\.95rem;', '$10.9rem;'
        $content = $content -replace '(\.mt-subscribe-savings \.line1 \{[^}]*margin-bottom: )4px;', '$12px;'
        $content = $content -replace '(\.mt-subscribe-savings \.line2 \{[^}]*font-size: )0\.8rem;', '$10.75rem;'

        # Add mt-cancel-notice CSS if not present
        if ($content -notmatch '\.mt-cancel-notice \{') {
            $cancelNoticeCSS = @"

.mt-cancel-notice {
  display: none;
  text-align: center;
  margin: 4px 0;
  padding: 6px 10px;
  background: #28a745;
  color: white;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
}

.mt-cancel-notice.show {
  display: block;
}

"@
            $content = $content -replace '(\.mt-subscribe-savings \.line2 \.price-highlight \{[^\}]*\}\s*)', "`$1$cancelNoticeCSS"
        }

        # Add 767px media query if not in main CSS section
        if ($content -notmatch '@media \(max-width: 767px\)[^@]*\.mt-product-content') {
            $mediaQuery767 = @"

@media (max-width: 767px) {
  .mt-product-content {
    display: grid;
    grid-template-columns: 42% 58%;
    grid-template-rows: auto 1fr;
    padding: 0;
    background: #fff;
    position: relative;
  }

  .mt-product-image {
    grid-column: 1;
    grid-row: 2;
    min-width: 130px;
    max-width: 200px;
    padding: 5px 5px 8px 8px;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    border: none;
    background: transparent;
  }

  .mt-product-image img {
    max-height: 180px;
    max-width: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
  }

  .mt-product-details {
    grid-column: 2;
    grid-row: 2;
    padding: 0 8px 8px 5px;
    border-left: none;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .mt-subscription-toggle {
    grid-column: 1 / -1;
    grid-row: 1;
    gap: 3px;
    margin-top: 8px;
    margin-bottom: 8px;
    padding: 6px 8px;
  }

  .mt-subscription-option {
    padding: 5px 8px;
  }

  .mt-option-label {
    font-size: 0.75rem;
  }

  .mt-radio-dot {
    width: 14px;
    height: 14px;
  }

  .mt-cancel-notice {
    font-size: 0.7rem;
    padding: 5px 8px;
  }

  .mt-product-card.mt-featured .mt-product-details {
    border-left: none;
  }
}
"@
            $content = $content -replace '(@media \(max-width: 480px\)[^\}]*\}\s*\}\s*)(</style>)', "`$1$mediaQuery767`$2"
        }

        # JavaScript Updates
        $content = $content -replace "card\.querySelector\('\.mt-savings-badge'\)\.textContent = 'YOU SAVE \$' \+ save \+ '!';", @"
// Update savings badge text based on type
      if (type === 'subscribe') {
        card.querySelector('.mt-savings-badge').innerHTML = 'YOU SAVE 10% EXTRA ON THIS ORDER<br>(\$' + save + ' Saved)!';
      } else {
        card.querySelector('.mt-savings-badge').textContent = 'YOU SAVE \$' + save + '!';
      }
"@

        $content = $content -replace "// Show/hide savings text and recurring price\s+const recurringPrice = card\.querySelector\('\.mt-recurring-price'\);\s+if \(type === 'subscribe'\) \{\s+if \(savingsText\) savingsText\.classList\.add\('show'\);\s+if \(recurringPrice\) recurringPrice\.classList\.add\('show'\);\s+\} else \{\s+if \(savingsText\) savingsText\.classList\.remove\('show'\);\s+if \(recurringPrice\) recurringPrice\.classList\.remove\('show'\);\s+\}", @"
// Show/hide savings text, recurring price, and cancel notice
      const recurringPrice = card.querySelector('.mt-recurring-price');
      const cancelNotice = card.querySelector('.mt-cancel-notice');
      if (type === 'subscribe') {
        if (savingsText) savingsText.classList.add('show');
        if (recurringPrice) recurringPrice.classList.add('show');
        if (cancelNotice) cancelNotice.classList.add('show');
      } else {
        if (savingsText) savingsText.classList.remove('show');
        if (recurringPrice) recurringPrice.classList.remove('show');
        if (cancelNotice) cancelNotice.classList.remove('show');
      }
"@

        # HTML Updates - Remove option prices
        $content = $content -replace '\s*<div class="mt-option-price">\$\d+ <span>/Bottle</span></div>', ''

        # HTML Updates - Update subscription savings messages
        $content = $content -replace "(<span class=""line2"">)Every recurring order saves you 30% <span class=""price-highlight"">\(\`$55/bottle\)</span>(</span>\s*</div>)", "`$1After 60 Days, 1 Bottle for Just `$55+Shipping`$2`r`n        <div class=""mt-cancel-notice"">Cancel subscription anytime!</div>"
        $content = $content -replace "(<span class=""line2"">)Every recurring order saves you 30% <span class=""price-highlight"">\(\`$34/bottle\)</span>(</span>\s*</div>)", "`$1After 210 Days, 1 Bottle for Just `$34`$2`r`n        <div class=""mt-cancel-notice"">Cancel subscription anytime!</div>"
        $content = $content -replace "(<span class=""line2"">)Every recurring order saves you 30% <span class=""price-highlight"">\(\`$49/bottle\)</span>(</span>\s*</div>)", "`$1After 120 Days, 1 Bottle for Just `$49`$2`r`n        <div class=""mt-cancel-notice"">Cancel subscription anytime!</div>"

        # Save the file
        Set-Content -Path $file -Value $content -Encoding UTF8 -NoNewline

        Write-Host "Completed: $file" -ForegroundColor Green
    } else {
        Write-Host "File not found: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nAll files processed!" -ForegroundColor Cyan
