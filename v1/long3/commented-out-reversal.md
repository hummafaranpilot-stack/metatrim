# Digistore Compliance - Commented Out Code for Reversal

This document tracks code changes made for Digistore compliance that have been commented out for potential reversal.

---

## File: index.html

### Change #1: Hidden Camera Images Removed

**Reason:** Digistore requires proof of consent for individuals shown in hidden camera footage. Without consent documentation, images must be removed.

**Location:** Lines ~297-372

**Hidden Elements:**
- camera1.jpg, camera2.jpg (lines ~297-298)
- camera3.jpg, camera4.jpg (lines ~312-313)
- camera5.jpg, camera6.jpg (lines ~329-330)
- camera7.jpg, camera8.jpg, camera9.jpg (lines ~350-352)
- camera11.jpg (line ~372)

**How to Revert:** Search for `DIGISTORE REVERSAL: Hidden camera images` comments in index.html, uncomment the original `<img>` tags.

---
