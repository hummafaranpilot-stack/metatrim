# Meta Trim - Checkout Links Reference

All checkout links used across the v3 funnel (ClickBank).

---

## short/go/index.html (Main Checkout)

| Package | CB Item | Link |
|---------|---------|------|
| 1 Bottle | cbitems=2 | `https://tnproduct.pay.clickbank.net/?cbitems=2&template=checkout3&cbfid=62416&exitoffer=exit_met_2` |
| 3 Bottles | cbitems=7 | `https://tnproduct.pay.clickbank.net/?cbitems=7&template=checkout3&cbfid=62416&exitoffer=exit_7` |
| 6 Bottles | cbitems=4 | `https://tnproduct.pay.clickbank.net/?cbitems=4&template=checkout3&cbfid=62416&exitoffer=exit_met_3` |

---

## upsell1.html

| Action | CB Item | Link |
|--------|---------|------|
| Yes - 6 Bottles | cbitems=15 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=15` |
| Yes - 3 Bottles | cbitems=9 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=9` |
| Yes - 1 Bottle | cbitems=10 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=10` |
| No Thanks (decline) | cbitems=9 | `https://tnproduct.pay.clickbank.net/?cbur=d&cbitems=9` |

---

## upsell2.html

| Action | CB Item | Link |
|--------|---------|------|
| Yes - 6 Bottles | cbitems=13 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=13` |
| Yes - 3 Bottles | cbitems=11 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=11` |
| Yes - 1 Bottle | cbitems=12 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=12` |
| No Thanks (decline) | cbitems=13 | `https://tnproduct.pay.clickbank.net/?cbur=d&cbitems=13` |

---

## upsell3.html

| Action | CB Item | Link |
|--------|---------|------|
| Yes | cbitems=14 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=14` |
| Yes + Rebill Accepted | cbitems=14 + rebill | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=14&cbrblaccpt=true` |
| Yes - Unchecked | cbitems=16 | `https://tnproduct.pay.clickbank.net/?cbur=a&cbitems=16` |
| No Thanks (decline) | cbitems=14 | `https://tnproduct.pay.clickbank.net/?cbur=d&cbitems=14` |

---

## Parameter Reference

| Parameter | Description |
|-----------|-------------|
| `cbitems` | ClickBank product/item ID |
| `cbur=a` | Accept upsell |
| `cbur=d` | Decline upsell |
| `template=checkout3` | Checkout page template |
| `cbfid=62416` | ClickBank form ID |
| `exitoffer` | Exit intent offer ID |
| `cbrblaccpt=true` | Rebill acceptance flag |
