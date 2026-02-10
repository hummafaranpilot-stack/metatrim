# Digistore Compliance - Commented Out Code for Reversal

This document tracks code changes made for Digistore compliance that have been commented out for potential reversal.

---

## File: index.html

### Change #1: Bonus Products Retail Prices Hidden

**Reason:** Digistore does not allow assigning a value to products offered as bonuses for the main product.

**Location:** CSS for `.bonuses-section .retail-price` (around line ~753)

**Hidden Elements:**
- "Retail Price - $79" (Free Extra Bottle bonus)
- "Retail Price - $49" (Keto Recipe Guide eBook bonus)
- "Retail Price - $39" (30-Day Diet Plan eBook bonus)

**How to Revert:** Search for `DIGISTORE REVERSAL` comment in the CSS section for `.bonuses-section .retail-price`. Uncomment the original CSS block and remove the `display: none` version.

---
