# VSL (vsl/index.html) - Reversible Changes

**How to reverse:** For each item, delete the `<!-- COMMENTED OUT:...` opening and `-->` closing tags.
The original code is preserved inside the HTML comments.

---

## 1. "As Seen On" Media Logos Bar (1st instance, ~Line 7284)
- **What:** Section with `<picture>` element showing `as-seen-on-bar.webp` (desktop) and `as-seen-on-bar-mobile.webp` (mobile) — USA Today, NBC, Good Housekeeping, Martha Stewart Living logos
- **Comment marker:** `COMMENTED OUT: As Seen On media logos bar (1st instance)`
- **Location:** After the money-back guarantee section
- **Action to reverse:** Remove `<!-- COMMENTED OUT: As Seen On media logos bar (1st instance)` and `-->` tags

## 2. "As Seen On" Media Logos Bar (2nd instance, ~Line 8653)
- **What:** Same `<picture>` element banner, second placement further down the page
- **Comment marker:** `COMMENTED OUT: As Seen On media logos bar (2nd instance)`
- **Location:** Before the FAQ section
- **Action to reverse:** Remove `<!-- COMMENTED OUT: As Seen On media logos bar (2nd instance)` and `-->` tags

## 3. "eliminate" → "reduce" (~Line 7321)
- **What:** "burn fat deposits and eliminate their storage"
- **Comment marker:** `COMMENTED OUT: eliminate`
- **Replaced with:** "burn fat deposits and reduce their storage"
- **Action to reverse:** Remove inline comment and restore "eliminate"

## 4. "root cause" → "underlying factor" (~Line 7324)
- **What:** "addressing the root cause of obesity"
- **Comment marker:** `COMMENTED OUT: root cause`
- **Replaced with:** "addressing the underlying factor of obesity"
- **Action to reverse:** Remove inline comment and restore "root cause"

## 5. "diabetes" → "healthy blood sugar balance" (~Line 7526)
- **What:** "improving control of diabetes"
- **Comment marker:** `COMMENTED OUT: diabetes`
- **Replaced with:** "supporting healthy blood sugar balance"
- **Action to reverse:** Remove inline comment and restore original text

## 6. "100% Natural" hidden (2 instances, ~Lines 7403, 7426)
- **What:** `<li class="mb-2">100% Natural</li>` in the product features lists
- **Comment marker:** `COMMENTED OUT: 100% Natural`
- **Method:** Hidden via `style="display:none;"` — original text preserved in the element
- **Action to reverse:** Remove the inline comment and the `style="display:none;"` attribute

## 7. FAQ "results timeline" updated (~Line 8727)
- **What:** "most people notice their change *within the first week*" + "Your belly will start to flatten and you'll likely notice that clothes will feel looser."
- **Comment marker:** `COMMENTED OUT: results timeline`
- **Replaced with:** "within the first 4 weeks" + "Your belly may start to flatten and you'll likely notice that clothes begin to feel looser."
- **Action to reverse:** Remove comment and restore original two lines

---

*Last updated: March 17, 2026*
