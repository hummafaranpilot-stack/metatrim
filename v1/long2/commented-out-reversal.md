# Digistore Compliance - Commented Out Code for Reversal

This document tracks code changes made for Digistore compliance that have been commented out for potential reversal.

---

## File: index.html

### Change #1: John Rowley Name and Media Appearances Hidden

**Reason:** Digistore requires confirmation that we are allowed to use John Rowley's name and media appearances (Fox News, CBS, ABC, Huffington Post).

**Location:** Around line ~745 and ~1405

**Hidden Elements:**
- "My name is John Rowley. Perhaps you've seen me recently on Fox News, CBS, ABC, in The Huffington Post, and other major media outlets."
- Signature image and "John Rowley" name

**Replacement:**
- "My name is John. I've dedicated years to researching weight loss and metabolic health."
- Signature shows just "John"

**How to Revert:** Search for `DIGISTORE REVERSAL: John Rowley` comments in index.html, uncomment the original text and remove the replacement versions.

---

### Change #2: Benefit Claims Toned Down

**Reason:** Digistore requested claims be toned down as they indicate direct and guaranteed results.

**Location:** Around line ~1180 (benefit checklist section)

| Original Claim | Toned Down Version |
|----------------|-------------------|
| "Hormonally-induced appetite control (which reduces the need for willpower)!" | "May support appetite management" |
| "Clear anti-aging signs, such as thicker hair, smoother skin, and increased libido!" | "May support healthy skin, hair, and overall vitality" |
| "Food cravings VANISHING due to the increased ketones your body produces naturally!" | "May help manage food cravings" |
| "Blood sugar levels supporting healthy balance within the normal range!" | "May support healthy blood sugar levels already within normal range" |
| "Your 'youth hormones' SOARING again!" | "May support healthy hormone levels" |
| "PCOS symptoms fading (it can even goes away completely!)" | "May support hormonal balance" |
| "Acne and other skin blemishes vanishing!" | "May support clearer skin" |
| "Vital heart functions and cholesterol levels improving to the point of shocking your doctor!" | "May support cardiovascular health" |
| "Blood pressure lowering..." | "May support healthy blood pressure levels already within normal range" |
| "Joint and muscle discomfort finally decreasing..." | "May support joint comfort and restful sleep" |
| "Endurance soaring back to the point of CRAVING to move and get outside!" | "May support energy and endurance" |

**How to Revert:** Search for `DIGISTORE REVERSAL: Original benefit claims` comment in index.html, uncomment the original `<li>` tags and remove the replacement versions.

---

### Change #3: GUARANTEED Claim Removed

**Reason:** Digistore does not allow claims that guarantee results.

**Location:** Around line ~1327

**Original:** "The Meta Trim BHB approach may appear too simple to work. All I ask is that you just try it. Trust the process. You will see in no time how incredible you will look and feel... GUARANTEED."

**Replacement:** "The Meta Trim BHB approach may appear too simple to work. All I ask is that you just try it. Trust the process."

**How to Revert:** Search for `DIGISTORE REVERSAL: GUARANTEED claim` comment in index.html, uncomment the original paragraph and remove the replacement version.

---
