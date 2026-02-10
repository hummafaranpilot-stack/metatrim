# Digistore Compliance - Commented Out Code for Reversal

This document tracks code changes made for Digistore compliance that have been commented out for potential reversal.

---

## File: go.html

### Change #1: Benefit Claims Toned Down (4 icons section)

**Reason:** Digistore requested claims be toned down as they indicate clear results.

**Location:** Lines ~1186-1321 (benefit icons section)

| Original Claim | Toned Down Version |
|----------------|-------------------|
| "Trigger intense fat burning" | "May support fat metabolism" |
| "Force your body to release fat" | "May support fat release" |
| "Preventing fat absorption" | "May help manage fat absorption" |
| "Making you feel fuller" | "May support satiety" |
| "Burning off white fat cells" | "May support metabolic processes" |

**How to Revert:** Search for `DIGISTORE REVERSAL` comments in go.html, uncomment the original `<h5>` tags and remove the replacement versions.

---

### Change #2: Bonus Products Retail Prices Hidden

**Reason:** Digistore does not allow assigning a value to products offered as bonuses for the main product.

**Location:** CSS for `.bonuses-section .retail-price` (around line ~2916)

**Hidden Elements:**
- "Retail Price - $79" (Free Extra Bottle bonus)
- "Retail Price - $49" (Prostate Health Guide eBook bonus)
- "Retail Price - $39" (30-Day Wellness Plan bonus)

**How to Revert:** Search for `DIGISTORE REVERSAL` comment in the CSS section for `.bonuses-section .retail-price`. Uncomment the original CSS block and remove the `display: none` version.

---

### Change #3: Ingredients Section Hidden

**Reason:** Digistore requested ingredients be removed as they don't match the product label.

**Location:** Around line ~3911

**Hidden Elements:**
- Entire ingredients section showing:
  - Irish Sea Moss
  - Bladderwrack
  - Burdock
  - BioPerine®
- Associated images and descriptions
- "Our team has saved you the trouble" section with product image

**How to Revert:** Search for `DIGISTORE REVERSAL: Ingredients section` comment in go.html, uncomment the entire ingredients block.

---
