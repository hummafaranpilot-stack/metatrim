# Digistore Compliance - Commented Out Code for Reversal

This document tracks code changes made for Digistore compliance that have been commented out for potential reversal.

---

## File: index.html

### Change #1: "Today Only" Claim Removed

**Reason:** Digistore requires confirmation that the offer is truly only available today and will change/expire.

**Location:** Around line ~925

**Hidden Element:**
- `<h1 class="text-danger">Today Only:</h1>`

**How to Revert:** Search for `DIGISTORE REVERSAL: "Today Only"` comment in index.html, uncomment the original `<h1>` tag.

---

### Change #2: "Lowest Guaranteed Price" FAQ Removed

**Reason:** Digistore requires confirmation that the time-limited price claim ("until tonight at midnight only") is true and applicable.

**Location:** Around line ~2112

**Hidden Elements:**
- FAQ question: "Is this the lowest guaranteed price?"
- Answer: "Yes, this is the lowest guaranteed price, but it is guaranteed until tonight at midnight only..."

**How to Revert:** Search for `DIGISTORE REVERSAL: "Lowest guaranteed price"` comment in index.html, uncomment the FAQ section.

---

### Change #3: Ingredients Section Hidden

**Reason:** Digistore requested ingredients be removed as they don't match the product label.

**Location:** Around line ~1042-1133

**Hidden Elements:**
- Entire "Key Ingredients" section showing:
  - Calcium BHB
  - Irish Sea Moss
  - Magnesium BHB & Sodium BHB
  - Supporting Ingredients (Green Tea Extract, Apple Cider Vinegar, etc.)
- Associated images and descriptions

**How to Revert:** Search for `DIGISTORE REVERSAL: Ingredients section` comment in index.html, uncomment the entire ingredients section.

---
