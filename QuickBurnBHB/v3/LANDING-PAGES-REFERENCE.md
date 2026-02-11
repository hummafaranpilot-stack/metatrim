# Meta Trim BHB - Landing Pages & Card Layout Reference

> Internal reference for all public-facing landing pages, the 3-card pricing layout code, CSS, JavaScript, image locations, and Digistore integration details.

---

## 1. All Public Landing Pages

### Long TSLs (Text Sales Letters)

| Page | Location | Entry Point | Type | Notes |
|------|----------|-------------|------|-------|
| Long TSL #1 (Best) | `v2/best/go/go.html` | `best/go/go.html` | Single page | Top performer, modern mt-product-grid cards |
| Long TSL #2 | `v2/long4/go/index.html` | `long4/go/index.html` | Single page | Modern mt-product-grid cards |
| Long TSL #3 | `v2/long2/index.html` | `long2/index.html` | Single page | Modern mt-product-grid cards |
| Long TSL #4 | `v2/long3/index.html` | `long3/index.html` | 5-page multi-step funnel | Modern mt-product-grid cards on page5 |

**Long TSL #4 Multi-Page Flow:**
- Page 1: `v2/long3/index.html`
- Page 2: `v2/long3/page2.html`
- Page 3: `v2/long3/page3.html`
- Page 4: `v2/long3/page4.html`
- Page 5 (Checkout): `v2/long3/page5.html` (contains product cards)

### Short TSL

| Page | Location | Entry Point | Type | Notes |
|------|----------|-------------|------|-------|
| Short TSL | `v2/short/go/index.html` | `short/go/index.html` | Single page | Modern mt-product-grid cards |

### Video Sales Letter (VSL)

| Page | Location | Entry Point | Type | Notes |
|------|----------|-------------|------|-------|
| Video TSL | `v2/video/index.html` | `video/index.html` | Single page | **Classic ATC cards** (different layout) |

### Upsell Pages

| Page | Location | Notes |
|------|----------|-------|
| Upsell 1 | `v2/upsell1.html` | First upsell after checkout |
| Upsell 2 | `v2/upsell2.html` | Second upsell |
| Upsell 3 | `v2/upsell3.html` | Third upsell |
| Upsell 4 | `v2/upsell4.html` | Fourth upsell |
| Thank You | `v2/Thankyou.html` | Post-purchase confirmation |

### Other Pages

| Page | Location | Notes |
|------|----------|-------|
| Affiliate Tools | `v2/aff/aff_details/aff/afftools.html` | Digistore Affiliates Only |
| Privacy Policy | `v2/Privacy policy.html` | |
| Terms & Conditions | `v2/Terms & Conditions.html` | |
| Return Policy | `v2/Return Policy.html` | |
| Contact | `v2/contact.html` | |

---

## 2. Card Layout Styles Overview

There are **2 card layout styles** used across the site:

| Style | Used In | Class Prefix |
|-------|---------|-------------|
| **Modern (mt-product-grid)** | best/go, short/go, long2, long3/page5, long4/go | `.mt-product-*` |
| **Classic (ATC)** | video | `.atc-*` |

---

## 3. Modern Card Layout (mt-product-grid)

Used in: `best/go/go.html`, `short/go/index.html`, `long2/index.html`, `long3/page5.html`, `long4/go/index.html`

### HTML Structure (Single Card Template)

```html
<div class="mt-product-grid">

  <!-- CARD: 2 BOTTLES -->
  <div class="mt-product-card" data-card="2bottle">
    <div class="mt-product-header">
      <h3>2 BOTTLES</h3>
      <p>60 DAY SUPPLY</p>
    </div>
    <div class="mt-product-content">
      <div class="mt-product-image">
        <img src="../../lib/img/prod1.png" alt="MetaTrim 2 Bottles">
      </div>
      <div class="mt-product-details">

        <!-- Subscription Toggle -->
        <div class="mt-subscription-toggle">
          <div class="mt-subscription-option" data-type="subscribe" data-price="71" data-total="142" data-save="218" data-url="#subscribe-2bottle">
            <div class="mt-option-left">
              <div class="mt-radio-dot"></div>
              <span class="mt-option-label">Subscribe & save up to 30%</span>
            </div>
            <div class="mt-option-price">$71 <span>/Bottle</span></div>
          </div>
          <div class="mt-subscription-option active" data-type="onetime" data-price="79" data-total="158" data-save="200" data-url="https://www.digistore24.com/product/PRODUCT_ID?account_id=11943&product_codename=met2&redirect=...">
            <div class="mt-option-left">
              <div class="mt-radio-dot"></div>
              <span class="mt-option-label">One-time purchase</span>
            </div>
            <div class="mt-option-price">$79 <span>/Bottle</span></div>
          </div>
        </div>

        <p class="mt-recurring-price">Recurring: $55/bottle</p>
        <div class="mt-product-price">
          <h4 class="mt-display-price">$79</h4>
          <div class="mt-price-info"><p>/Bottle</p></div>
        </div>
        <div class="mt-savings-badge">YOU SAVE $200!</div>

        <div class="mt-subscribe-savings">
          <span class="line1">You saved 10% on this order!</span>
          <span class="line2">Every recurring order saves you 30% <span class="price-highlight">($55/bottle)</span></span>
        </div>

        <a href="Digistore_URL" class="mt-buy-now-btn">
          <img src="../../lib/img/buy-now.webp" alt="Buy Now">
        </a>
        <p class="mt-shipping-text">+ $19.99 Shipping</p>
        <p class="mt-total-line">TOTAL: <span class="mt-strikethrough">$260</span> <span class="mt-final-price">$158</span></p>
        <div class="mt-payment-icons"><img src="../../lib/img/cards.webp" alt="Payment Methods"></div>
      </div>
    </div>
  </div>

  <!-- CARD: 6+1 BOTTLES (FEATURED / BEST VALUE) -->
  <div class="mt-product-card mt-featured" data-card="6bottle">
    <div class="mt-product-header">
      <h3>6+1 BOTTLES</h3>
      <p>210 DAY SUPPLY</p>
    </div>
    <div class="mt-product-content">
      <div class="mt-product-image">
        <span class="mt-best-badge">BEST VALUE</span>
        <img src="../../lib/img/6-bottles.png" alt="MetaTrim 6+1 Bottles">
      </div>
      <div class="mt-product-details">
        <!-- Same toggle structure as above with different data attributes -->
        <!-- data-price="39", data-total="234", data-save="696", product_codename=met6 -->
        <!-- Uses buy-now2.webp for buy button -->
        <!-- FREE Shipping -->
      </div>
    </div>
  </div>

  <!-- CARD: 4 BOTTLES (MOST POPULAR) -->
  <div class="mt-product-card" data-card="4bottle">
    <div class="mt-product-header">
      <h3>4 BOTTLES</h3>
      <p>120 DAY SUPPLY</p>
    </div>
    <div class="mt-product-content">
      <div class="mt-product-image">
        <span class="mt-best-badge">MOST POPULAR</span>
        <img src="../../lib/img/3-bottle-buy-btn.png" alt="MetaTrim 4 Bottles">
      </div>
      <div class="mt-product-details">
        <!-- data-price="59", data-total="236", data-save="424", product_codename=met4 -->
        <!-- FREE Shipping -->
      </div>
    </div>
  </div>

</div>
```

### Product Card Data Attributes

| Card | data-card | Product Code | One-Time Price | Subscribe Price | Total | Save | Shipping |
|------|-----------|-------------|----------------|-----------------|-------|------|----------|
| 2 Bottles | `2bottle` | `met2` | $79/bottle | $71/bottle | $158 | $200 | $19.99 |
| 6+1 Bottles | `6bottle` | `met6` | $39/bottle | $35/bottle | $234 | $696 | FREE |
| 4 Bottles | `4bottle` | `met4` | $59/bottle | $53/bottle | $236 | $424 | FREE |

### Complete CSS

```css
/* =============================================
   MT-PRODUCT-GRID - Modern Pricing Cards
   ============================================= */

.mt-product-grid {
  display: flex;
  flex-wrap: nowrap;
  gap: 1rem;
  justify-content: center;
  padding: 2rem 1rem;
  background-color: transparent;
  font-family: "Montserrat", sans-serif;
  max-width: 1100px;
  margin: 0 auto;
}

.mt-product-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  max-width: 340px;
  width: calc(33.333% - 0.75rem);
  min-width: 280px;
  flex: 1 1 auto;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  font-family: "Montserrat", sans-serif;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.mt-product-card:hover {
  transform: scale(1.02);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.mt-product-card.mt-featured {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  border: 2px solid #ffc107;
}

/* --- Header --- */
.mt-product-header {
  background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
  text-align: center;
  padding: 1.2rem 1rem;
  color: white;
}

.mt-product-card.mt-featured .mt-product-header {
  background: linear-gradient(135deg, #0d47a1 0%, #1976d2 50%, #42a5f5 100%);
}

.mt-product-header h3 {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin: 0;
}

.mt-product-header p {
  font-size: 1.1rem;
  margin-top: 0.3rem;
  font-weight: 500;
}

/* --- Content --- */
.mt-product-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
}

.mt-product-image {
  width: 100%;
  position: relative;
  text-align: center;
  padding: 1rem;
  border: 2px solid #bbdefb;
  border-radius: 12px;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  margin-bottom: 0.5rem;
}

.mt-product-image img {
  width: 100%;
  max-width: 280px;
  height: auto;
  object-fit: contain;
}

.mt-best-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: linear-gradient(135deg, #1a237e, #303f9f);
  color: #fff;
  font-weight: bold;
  font-size: 0.85rem;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  text-transform: uppercase;
  box-shadow: 0 4px 15px rgba(26, 35, 126, 0.4);
}

/* --- Details --- */
.mt-product-details {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 1.5rem;
  width: 100%;
}

.mt-product-price {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.2rem;
}

.mt-product-price h4,
.mt-display-price {
  font-size: 4.5rem;
  color: #1565c0;
  font-weight: 800;
  margin: 0;
  line-height: 1;
}

.mt-price-info p {
  font-size: 1.3rem;
  color: #1565c0;
  font-weight: 600;
  margin: 0;
}

/* --- Subscription Toggle --- */
.mt-subscription-toggle {
  width: 100%;
  background: #1a1a2e;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 10px;
}

.mt-subscription-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.mt-subscription-option:hover {
  background: rgba(255, 255, 255, 0.1);
}

.mt-subscription-option.active {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.mt-option-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mt-radio-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #666;
  position: relative;
  flex-shrink: 0;
}

.mt-subscription-option.active .mt-radio-dot {
  border-color: #fff;
  background: #fff;
}

.mt-radio-dot::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #1a1a2e;
  opacity: 0;
}

.mt-subscription-option.active .mt-radio-dot::after {
  opacity: 1;
}

.mt-option-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
}

.mt-option-price {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
}

.mt-option-price span {
  font-size: 0.8rem;
  font-weight: 500;
  color: #ccc;
}

.mt-recurring-price {
  text-align: center;
  font-size: 0.85rem;
  color: #1565c0;
  font-weight: 600;
  margin-top: 5px;
  display: none;
}

.mt-recurring-price.show {
  display: block;
}

/* --- Savings & Buy --- */
.mt-savings-badge {
  background: none;
  color: #000;
  padding: 0;
  font-size: 1.1rem;
  font-weight: 700;
  margin: 8px 0 0 0;
  display: block;
}

.mt-buy-now-btn {
  display: block;
  margin-top: 5px;
  width: 100%;
  max-width: 220px;
}

.mt-buy-now-btn img {
  width: 100%;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.mt-buy-now-btn:hover img {
  transform: scale(1.05);
}

.mt-shipping-text {
  color: #000;
  font-size: 1rem;
  margin-top: 8px;
  font-weight: 500;
}

.mt-total-line {
  font-size: 1.1rem;
  margin-top: 6px;
  font-weight: 500;
}

.mt-strikethrough {
  text-decoration: line-through;
  color: #999;
  margin-right: 0.5rem;
}

.mt-final-price {
  font-weight: bold;
  color: #000;
}

.mt-payment-icons {
  margin: 1rem 0;
}

.mt-payment-icons img {
  width: 100%;
  max-width: 200px;
}

/* --- Subscribe Savings Box --- */
.mt-subscribe-savings {
  display: none;
  text-align: center;
  margin: 8px 0;
  background: rgba(26, 26, 46, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(26, 26, 46, 0.15);
  border-radius: 8px;
  padding: 10px 12px;
}

.mt-subscribe-savings.show {
  display: block;
  animation: borderFlicker 2.5s ease-in-out infinite;
}

@keyframes borderFlicker {
  0%, 100% { border-color: transparent; }
  50% { border-color: #000; }
}

.mt-subscribe-savings .line1 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1565c0;
  display: block;
  margin-bottom: 4px;
}

.mt-subscribe-savings .line2 {
  font-size: 0.8rem;
  font-weight: 500;
  color: #333;
  display: block;
}

.mt-subscribe-savings .line2 .price-highlight {
  font-weight: 700;
  color: #1565c0;
}

/* --- Subscription Guarantee --- */
.mt-subscription-guarantee {
  text-align: center;
  padding: 25px 30px;
  margin-top: 25px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
}

.mt-subscription-guarantee .guarantee-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #1a237e;
  margin: 0 0 10px 0;
  text-transform: uppercase;
}

.mt-subscription-guarantee .guarantee-title span {
  color: #1565c0;
}

.mt-subscription-guarantee .guarantee-text {
  font-size: 1rem;
  font-weight: 500;
  color: #333;
  margin: 0;
  line-height: 1.5;
}

/* =============================================
   RESPONSIVE - Tablet (max-width: 992px)
   ============================================= */
@media (max-width: 992px) {
  .mt-product-grid {
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }
  .mt-product-card {
    max-width: 400px;
    width: 100%;
  }
  .mt-product-card.mt-featured {
    order: -1;
  }
  .mt-product-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.8rem;
  }
  .mt-product-image {
    width: 100%;
    max-width: 200px;
    padding: 0.5rem;
  }
  .mt-product-details {
    width: 100%;
    padding: 0 0.5rem;
  }
  .mt-product-price h4,
  .mt-display-price {
    font-size: 3rem;
  }
  .mt-product-header h3 {
    font-size: 1.5rem;
  }
  .mt-product-header p {
    font-size: 0.85rem;
  }
}

/* =============================================
   RESPONSIVE - Mobile (max-width: 767px)
   Side-by-side layout
   ============================================= */
@media (max-width: 767px) {
  .mt-product-grid {
    padding: 0 5px;
    gap: 0.8rem;
  }
  .mt-product-card {
    border: 2px solid #1565c0;
    border-radius: 8px;
    max-width: 100%;
    margin: 0;
  }
  .mt-product-card.mt-featured {
    border-color: #FFD700;
  }
  .mt-product-header {
    padding: 8px 12px;
    border-radius: 6px 6px 0 0;
  }
  .mt-product-header h3 {
    font-size: 1.1rem;
    margin: 0;
  }
  .mt-product-header p {
    font-size: 0.7rem;
    margin: 0;
  }
  .mt-product-content {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 8px;
    background: #fff;
    position: relative;
  }
  .mt-product-image {
    width: 42%;
    min-width: 130px;
    max-width: 180px;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    border: none;
    background: transparent;
  }
  .mt-product-image img {
    max-height: 150px;
    max-width: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
  }
  .mt-product-details {
    width: 55%;
    padding: 0 5px 0 0;
    border-left: none;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .mt-best-badge {
    position: absolute;
    top: 5px;
    left: 10px;
    right: auto;
    font-size: 0.5rem;
    padding: 3px 8px;
    border-radius: 10px;
  }
  .mt-subscription-toggle {
    gap: 5px;
    margin-bottom: 5px;
  }
  .mt-option-label {
    font-size: 0.65rem;
  }
  .mt-option-price {
    font-size: 0.75rem;
  }
  .mt-product-price h4,
  .mt-display-price {
    font-size: 2.2rem;
    margin: 5px 0;
  }
  .mt-savings-badge {
    font-size: 0.85rem;
    margin: 5px 0;
  }
  .mt-buy-now-btn {
    max-width: 150px;
    margin: 5px auto 0;
  }
  .mt-shipping-text {
    font-size: 0.75rem;
    margin-top: 5px;
  }
  .mt-total-line {
    font-size: 0.8rem;
    margin-top: 5px;
  }
  .mt-payment-icons {
    margin: 8px 0;
  }
  .mt-payment-icons img {
    max-width: 150px;
  }
  .mt-subscribe-savings {
    padding: 6px 8px;
    margin: 5px 0;
  }
  .mt-subscribe-savings .line1 {
    font-size: 0.75rem;
  }
  .mt-subscribe-savings .line2 {
    font-size: 0.65rem;
  }
  .mt-recurring-price {
    font-size: 0.7rem;
  }
}

/* =============================================
   RESPONSIVE - Small Phones (max-width: 480px)
   ============================================= */
@media (max-width: 480px) {
  .mt-product-price h4,
  .mt-display-price {
    font-size: 1.8rem;
  }
  .mt-product-image {
    width: 42%;
    min-width: 110px;
    max-width: 150px;
  }
  .mt-product-image img {
    max-height: 130px;
  }
  .mt-option-label {
    font-size: 0.6rem;
  }
  .mt-option-price {
    font-size: 0.7rem;
  }
  .mt-buy-now-btn {
    max-width: 130px;
  }
}
```

### Subscription Toggle JavaScript

```javascript
document.addEventListener('DOMContentLoaded', function() {
  const subscriptionOptions = document.querySelectorAll('.mt-subscription-option');

  subscriptionOptions.forEach(option => {
    option.addEventListener('click', function() {
      const card = this.closest('.mt-product-card');
      const toggle = this.closest('.mt-subscription-toggle');
      const savingsText = card.querySelector('.mt-subscribe-savings');
      const buyButton = card.querySelector('.mt-buy-now-btn');

      // Remove active from all options in this toggle
      toggle.querySelectorAll('.mt-subscription-option').forEach(opt =>
        opt.classList.remove('active')
      );

      // Add active to clicked option
      this.classList.add('active');

      // Update price display from data attributes
      const price = this.dataset.price;
      const total = this.dataset.total;
      const save  = this.dataset.save;
      const type  = this.dataset.type;
      const url   = this.dataset.url;

      card.querySelector('.mt-display-price').textContent = '$' + price;
      card.querySelector('.mt-final-price').textContent = '$' + total;
      card.querySelector('.mt-savings-badge').textContent = 'YOU SAVE $' + save + '!';

      // Update Buy button URL
      if (buyButton && url) {
        buyButton.href = url;
      }

      // Show/hide subscribe-specific elements
      const recurringPrice = card.querySelector('.mt-recurring-price');
      if (type === 'subscribe') {
        if (savingsText) savingsText.classList.add('show');
        if (recurringPrice) recurringPrice.classList.add('show');
      } else {
        if (savingsText) savingsText.classList.remove('show');
        if (recurringPrice) recurringPrice.classList.remove('show');
      }
    });
  });
});
```

---

## 4. Classic ATC Card Layout (Video Page Only)

Used in: `video/index.html`

### HTML Structure (Single Card Template)

```html
<!-- Hidden forms for submission -->
<table style="display:none">
  <tr>
    <th><form class="fade-box" action="/go/order-fe.php" method="get" id="form_cta1"><input type="hidden" name="pid" value="XITOX1-19B"></form></th>
    <th><form class="fade-box" action="/go/order-fe.php" method="get" id="form_cta2"><input type="hidden" name="pid" value="XITOX6-19B"></form></th>
    <th><form class="fade-box" action="/go/order-fe.php" method="get" id="form_cta3"><input type="hidden" name="pid" value="XITOX3-19B"></form></th>
  </tr>
</table>

<div class="desktop-width row justify-content-center fade-container fe-atc-zindex">

  <!-- CARD 1: 2-PACK (SAMPLER) -->
  <div class="col-11 col-md-4 text-center py-0 px-md-1 px-lg-2">
    <div onclick="javascript:submitform('form_cta1');" class="atc-transform" style="cursor:pointer;">
      <div class="text-right text-md-left">
        <p class="text-blue atc-tab">SAMPLER PACKAGE <br> 2 PACK</p>
      </div>
      <div class="atc">
        <div class="atc-section position-relative" id="atc-left">
          <h4 class="text-white d-none d-md-block">2-MONTH SUPPLY</h4>
          <div class="atc-savings"><p>OFF <br>50%</p></div>
          <picture>
            <source srcset="img/atc-guarantee.png" type="image/png">
            <img loading="lazy" class="atc-guarantee img-fluid" src="img/atc-guarantee.png" alt="">
          </picture>
          <img loading="lazy" class="atc-img img-fluid" src="../lib/img/prod1.png" alt="">
        </div>
        <div class="atc-section" id="atc-right">
          <h6 class="text-white d-md-none mb-0" style="font-weight: 600;">2-MONTH SUPPLY</h6>
          <h1 class="atc-price mr-1 mb-0 mb-md-2"><span class="atc-dollar">$</span>69</h1>
          <h5 class="mb-0 mb-md-2"> /EACH</h5>
          <p class="atc-whitetext d-none d-md-block">FAST & $19.99 SHIPPING</p>
          <p class="atc-yellowtext d-md-none">YOU SAVE $40</p>
          <p class="atc-whitetext mb-1 mb-md-3">60-DAY MONEY-BACK GUARANTEE</p>
          <h4 class="text-center" style="color: white;">Total: $138</h4>
          <a href="Digistore_URL" class="atc-button" style="display:flex; align-items:center; justify-content:center; text-decoration:none; cursor:pointer;">
            <h2 style="margin:0; color:#fff;">Order Today</h2>
            <img loading="lazy" class="img-fluid" src="img/atc-icon.svg" alt="Cart Icon" style="margin-left:10px; width:24px; height:auto;">
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- CARD 2: 6-PACK (BEST SELLER) - add class "center" to .atc -->
  <!-- CARD 3: 3-PACK (MOST POPULAR) -->

</div>
```

### Classic ATC Card Pricing

| Card | Label | Product Code | Price | Total | Save | Shipping |
|------|-------|-------------|-------|-------|------|----------|
| 2-Pack | SAMPLER PACKAGE | `met1` | $69/each | $138 | $40 | $19.99 |
| 6-Pack | BEST SELLER | `met6` | $39/each | $234 | $396 | FREE |
| 3-Pack | MOST POPULAR | `met3` | $59/each | $177 | $150 | FREE |

### Classic ATC CSS (Key Styles)

```css
.atc-tab {
  display: inline-block;
  background: #fbc630;
  padding: 11px 23px;
  border-radius: 15px 15px 0 0;
  font-weight: bold;
  margin: 0;
  line-height: 1;
}

.atc {
  background: url('img/atc-bg.jpg');
  background-size: cover;
  background-position: center;
  padding: 25px 15px;
  position: relative;
  border: 4px solid transparent;
  display: flex;
  flex-direction: column;
}

.atc.center {
  border: 4px solid #fbc630;
  border-radius: 0px 4px 4px 4px;
}

.atc-img { max-height: 250px; }

.atc-savings {
  background: #f3bc00;
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  padding: 10px;
  border-radius: 50%;
  width: 100px;
  height: 100px;
  top: 25%;
  left: 95%;
  transform: translate(-95%, -25%) rotate(15deg);
}

.atc-price {
  display: inline-block;
  font-size: 77px;
  color: #edba14;
}

.atc-button {
  background: #f3bc00;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px 0;
  margin: 0 8px;
}

.atc-transform {
  transition: .7s ease-in-out;
}

.atc-transform:hover {
  transform: translateY(-10px) scale(1.05);
  transition: .7s ease-in-out;
}

#atc-left { width: 45%; }
#atc-right {
  width: 55%;
  border-left: 2px solid;
  border-image: linear-gradient(to top, transparent, #ffffff3d, transparent) 1 100%;
}
```

### Classic ATC JavaScript

```javascript
function submitform(formid) {
  window.onbeforeunload = function() {};
  document.getElementById(formid).submit();
}
```

---

## 5. Image Locations Reference

### Shared Product Images (`v2/lib/img/`)

| Image | File | Used For |
|-------|------|----------|
| 1 Bottle | `lib/img/prod1.png` | 2-bottle card product image |
| 3 Bottles | `lib/img/3-bottle-buy-btn.png` | 3/4-bottle card product image |
| 6 Bottles | `lib/img/6-bottles.png` | 6-bottle card product image |
| Buy Now Button | `lib/img/buy-now.webp` | Standard buy button (2 & 4 bottle) |
| Buy Now Button Alt | `lib/img/buy-now2.webp` | Featured buy button (6+1 bottle) |
| Payment Cards | `lib/img/cards.webp` | Visa/MC/Amex icons |
| Credit Cards Alt | `lib/img/credit-cards.png` | Alternative payment icons |
| Logo | `lib/img/logo.png` | Site header & favicon |
| Product Label | `lib/img/label1.jpg` | Label image |
| 2nd Bottles | `lib/img/2nd.png` | Alternate product display |
| Guarantee Badge | `lib/img/guarantee.png` | Money-back guarantee |
| Bonus 1 | `lib/img/bonus-1.png` | Bonus ebook image |
| Bonus 2 | `lib/img/bonus-2.png` | Bonus ebook image |

### Video Page Local Images (`v2/video/img/`)

| Image | File | Used For |
|-------|------|----------|
| ATC Background | `video/img/atc-bg.jpg` | Classic card background |
| Guarantee Badge | `video/img/atc-guarantee.png` | 60-day guarantee circle |
| Shipping Ribbon | `video/img/atc-shippingribbon.png` | Free shipping mobile badge |
| Cart Icon | `video/img/atc-icon.svg` | Order button cart icon |
| MBG Banner | `video/img/MBG-Desktop.png` | Money-back guarantee banner |

### Campaign Images for Affiliates (`v2/long3/Images Data/`)

50+ images organized into categories:
- **Banner Ads**: `1/`, `2/`, `3/` subfolders with GIF banners (300x250, 250x250, etc.)
- **Lifestyle**: `shop1.jpg`, `shop2.jpg`, `drink.jpg`, `cook.jpg`, `eat2.jpg`, `run2.jpg`, `bed.jpg`, `kissing.jpg`
- **Testimonials**: `beforeafter1.jpg`, `beforeafter2.jpg`, `result1.jpg`, `selfie.jpg`, `melissa.jpg`, `samantha.jpg`, `mariah2.jpg`, `grace2.jpg`
- **Video Thumbnails**: `video1.jpg`, `video3.jpg`, `camera1-7.jpg`

**ZIP download**: `v2/lib/docs/campaign-images.zip`

### Downloadable Documents (`v2/lib/docs/`)

| File | Description |
|------|-------------|
| `google-ads-tips.pdf` | Google Ads Traffic Guide |
| `email-swipes.pdf` | Email Swipes Collection |
| `campaign-images.zip` | All Campaign Images |

---

## 6. Digistore Integration

### Account Info

- **Account ID**: `11943`
- **Platform**: Digistore (Digistore.com)

### Checkout URL Pattern

```
https://www.digistore24.com/product/PRODUCT_ID?account_id=11943&product_codename=[CODE]&redirect=[BASE64_URL]
```

### Product Codes

| Code | Product | Bottles |
|------|---------|---------|
| `met1` | Meta Trim 1-Pack | 1-2 bottles (classic layout) |
| `met2` | Meta Trim 2-Pack | 2 bottles (modern layout) |
| `met3` | Meta Trim 3-Pack | 3 bottles (classic layout) |
| `met4` | Meta Trim 4-Pack | 4 bottles (modern layout) |
| `met6` | Meta Trim 6-Pack | 6+1 bottles |

### Redirect URLs (Base64 Decoded)

- **To Upsell 1**: `https://metatrim.trustednutraproduct.com/v2/upsell1.html`
- **To Thank You**: `https://www.digistore24.com/thankyou/PRODUCT_ID`

---

## 7. Key Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Dark Navy | `#1a237e` | Card headers, badges |
| Medium Blue | `#303f9f` | Header gradient end |
| Bright Blue | `#1565c0` | Prices, featured text |
| Light Blue BG | `#e3f2fd` | Product image background |
| Blue Border | `#bbdefb` | Image container border |
| Gold/Yellow | `#fbc630` | Classic ATC tab, buttons |
| Gold Price | `#edba14` | Classic ATC price text |
| Dark Toggle BG | `#1a1a2e` | Subscription toggle |
| Featured Gold Border | `#ffc107` | Featured card border |
| Featured Gradient Start | `#fff8e1` | Featured card background |
| Featured Gradient End | `#ffecb3` | Featured card background |
| Red Button | `#ad231b` | Affiliate page CTA buttons |

---

## 8. Font Stack

```css
font-family: "Montserrat", sans-serif;
```

All card layouts use Montserrat. Loaded via Google Fonts:
```html
<link href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">
```

---

*Last updated: January 2026*
