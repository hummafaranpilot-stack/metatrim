// Main JavaScript functionality for AquaSculpt website

document.addEventListener('DOMContentLoaded', function() {
  initializeFAQ();
  initializeLazyLoading();
  // initializeProductCards();
  initializeScrollEffects();
  initializeHeaderScroll();
  initializeMobileMenu();
});

// FAQ Accordion functionality
function initializeFAQ() {
  var faqItems = document.querySelectorAll('.faq-item');
  console.log('FAQ items found:', faqItems.length); // Debug

  for (var i = 0; i < faqItems.length; i++) {
    (function(item, index) {
      var question = item.querySelector('.faq-question');
      var answer = item.querySelector('.faq-answer');
      var toggle = item.querySelector('.faq-toggle');

      console.log('FAQ item ' + (index + 1) + ':', { question: !!question, answer: !!answer, toggle: !!toggle }); // Debug

      if (question && answer && toggle) {
        question.addEventListener('click', function() {
          console.log('FAQ question clicked'); // Debug
          var isActive = answer.classList.contains('active');

          // Close all other FAQ items
          for (var j = 0; j < faqItems.length; j++) {
            var otherItem = faqItems[j];
            var otherAnswer = otherItem.querySelector('.faq-answer');
            var otherToggle = otherItem.querySelector('.faq-toggle');
            var otherQuestion = otherItem.querySelector('.faq-question');

            if (otherAnswer && otherToggle && otherQuestion && otherItem !== item) {
              otherAnswer.classList.remove('active');
              otherToggle.textContent = '+';
              otherQuestion.classList.remove('active');
            }
          }

          // Toggle current item
          if (!isActive) {
            answer.classList.add('active');
            toggle.textContent = '−';
            question.classList.add('active');
          } else {
            answer.classList.remove('active');
            toggle.textContent = '+';
            question.classList.remove('active');
          }
        });
      }
    })(faqItems[i], i);
  }
}
.section__wave {
  display: block;
  width: 100%;
  height: 100px;
  margin-bottom: -5px;
}

/* SECTION STYLING */
.features-section {
  background: #ffffff; /* Change section color easily */
  padding: 80px 0;
  display: flex;
  justify-content: center;
}

/* GRID CONTAINER */
.features-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
  width: 90%;
  max-width: 1200px;
}

/* BOX STYLE */
.feature-box {
  background: #f8f9ff; /* Box background */
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  text-align: center;
  padding: 40px 25px;
  transition: all 0.3s ease;
}

.feature-box:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

/* ICON */
.feature-icon {
  width: 70px;
  height: auto;
  margin-bottom: 20px;
}

/* TITLE */
.feature-box h3 {
  color: #3A7BD5; /* Title color (adjust freely) */
  font-size: 1.3rem;
  margin-bottom: 10px;
}

/* TEXT */
.feature-box p {
  color: #666;
  font-size: 0.95rem;
  line-height: 1.5;
}

// Lazy loading for images
function initializeLazyLoading() {
  var images = document.querySelectorAll('img[data-src]');

  // Check if IntersectionObserver is supported
  if ('IntersectionObserver' in window) {
    var imageObserver = new IntersectionObserver(function(entries) {
      for (var i = 0; i < entries.length; i++) {
        var entry = entries[i];
        if (entry.isIntersecting) {
          var img = entry.target;
          img.src = img.getAttribute('data-src');
          img.classList.remove('lazy');
          imageObserver.unobserve(img);
        }
      }
    });

    for (var j = 0; j < images.length; j++) {
      imageObserver.observe(images[j]);
    }
  } else {
    // Fallback for older browsers - load all images immediately
    for (var k = 0; k < images.length; k++) {
      var img = images[k];
      img.src = img.getAttribute('data-src');
      img.classList.remove('lazy');
    }
  }
}

// Product card interactions
// function initializeProductCards() {
//     var productCards = document.querySelectorAll('.product-card');
//     var addToCartBtns = document.querySelectorAll('.add-to-cart-btn');

//     // Add hover effects to product cards
//     for (var i = 0; i < productCards.length; i++) {
//         (function(card) {
//             card.addEventListener('mouseenter', function() {
//                 this.style.transform = 'translateY(-5px)';
//                 this.style.boxShadow = '0px 8px 25px rgba(0, 0, 0, 0.15)';
//             });

//             card.addEventListener('mouseleave', function() {
//                 this.style.transform = 'translateY(0)';
//                 this.style.boxShadow = '0px 2px 4px -2px rgba(0, 0, 0, 0.1), 0px 4px 6px -1px rgba(0, 0, 0, 0.1)';
//             });
//         })(productCards[i]);
//     }

//     // Add to cart functionality
//     for (var j = 0; j < addToCartBtns.length; j++) {
//         (function(btn) {
//             btn.addEventListener('click', function(e) {
//                 e.preventDefault();

//                 // Get product information
//                 var productCard = this.closest('.product-card');
//                 var productTitle = productCard.querySelector('h3').textContent;
//                 var productPrice = productCard.querySelector('.total-price').textContent;

//                 // Show success message
//                 showNotification(productTitle + ' added to cart! ' + productPrice, 'success');

//                 // Add loading state
//                 var originalText = this.innerHTML;
//                 this.innerHTML = '<span>Adding...</span>';
//                 this.disabled = true;

//                 setTimeout(function() {
//                     btn.innerHTML = originalText;
//                     btn.disabled = false;
//                 }, 2000);
//             });
//         })(addToCartBtns[j]);
//     }
// }

// Scroll effects and animations
function initializeScrollEffects() {
  // Check if IntersectionObserver is supported
  if ('IntersectionObserver' in window) {
    // Intersection Observer for fade-in animations
    var observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    var observer = new IntersectionObserver(function(entries) {
      for (var i = 0; i < entries.length; i++) {
        var entry = entries[i];
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      }
    }, observerOptions);

    // Observe elements for animation
    var animatedElements = document.querySelectorAll(
      '.testimonial-card, .ingredient-card, .product-card, .benefit-item'
    );

    for (var j = 0; j < animatedElements.length; j++) {
      var el = animatedElements[j];
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      observer.observe(el);
    }
  }

  // Parallax effect for hero section
  window.addEventListener('scroll', function() {
    var scrolled = window.pageYOffset;
    var heroImage = document.querySelector('.hero-bg');

    if (heroImage) {
      var rate = scrolled * -0.5;
      heroImage.style.transform = 'translateY(' + rate + 'px)';
    }
  });
}

// Notification system (helper function for product cards)
// function showNotification(message, type) {
//     // Create notification element
//     var notification = document.createElement('div');
//     notification.className = 'notification notification-' + type;
//     notification.textContent = message;

//     // Style the notification
//     notification.style.position = 'fixed';
//     notification.style.top = '20px';
//     notification.style.right = '20px';
//     notification.style.padding = '15px 20px';
//     notification.style.borderRadius = '5px';
//     notification.style.color = 'white';
//     notification.style.fontWeight = 'bold';
//     notification.style.zIndex = '9999';
//     notification.style.transform = 'translateX(100%)';
//     notification.style.transition = 'transform 0.3s ease';

//     // Set background color based on type
//     if (type === 'success') {
//         notification.style.backgroundColor = '#4CAF50';
//     } else if (type === 'error') {
//         notification.style.backgroundColor = '#f44336';
//     } else {
//         notification.style.backgroundColor = '#2196F3';
//     }

//     // Add to page
//     document.body.appendChild(notification);

//     // Animate in
//     setTimeout(function() {
//         notification.style.transform = 'translateX(0)';
//     }, 100);

//     // Remove after 3 seconds
//     setTimeout(function() {
//         notification.style.transform = 'translateX(100%)';
//         setTimeout(function() {
//             if (notification.parentNode) {
//                 notification.parentNode.removeChild(notification);
//             }
//         }, 300);
//     }, 3000);
// }

// Header scroll effect
function initializeHeaderScroll() {
  var navContainer = document.querySelector('.header .nav-container');
  var scrollThreshold = 100;

  if (navContainer) {
    window.addEventListener('scroll', function() {
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

      if (scrollTop > scrollThreshold) {
        navContainer.style.height = '65px';
        navContainer.style.transition = 'height 0.3s ease';
      } else {
        navContainer.style.height = '79px';
        navContainer.style.transition = 'height 0.3s ease';
      }
    });
  }
}

function initializeMobileMenu() {
  var hamburger = document.getElementById('hamburger-btn');
  var mobileMenu = document.getElementById('mobile-menu');

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function() {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      if (mobileMenu.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    });

    var links = mobileMenu.querySelectorAll('a, .order-btn');
    links.forEach(function(link) {
      link.addEventListener('click', function() {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }
}

/* global urlParam */
/*
function callURLWithParams(url) {
  var urlWithParam = url + "&";

  if (window.location.search) {
    urlWithParam += window.location.search.slice(1).replace(/sub=/g, "subid=");
  }

  window.location = urlWithParam;

  return false;
}*/
function paramIsNotUtm(param) {
  return param.slice(0, 4) !== 'utm_';
}
function getCookieValue(name) {
  var result = document.cookie.match("(^|[^;]+)\\s*" + name + "\\s*=\\s*([^;]+)");
  return result ? result.pop() : "";
}
function addOrderLinks () {

  window.variables = "";
  if (history && history.replaceState && location.search) {
    var params = location.search.slice(1).split('&');
    var newParams = params.filter(paramIsNotUtm);
    if (newParams.length <= params.length) {
      var search = newParams.length ? '' + newParams.join('&') : '';
      window.variables = search + location.hash;
    }
  }
  if (wsFlags.noredirect !== null) {


    window.sixBottleLinkMobile = window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw6+"/?";
    window.sixBottleLink= window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw6+"/?";
    window.threeBottleLinkMobile = window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw3+"/?";
    window.threeBottleLink =window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw3+"/?";
    window.oneBottleLinkMobile=window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw1+"/?";
    window.oneBottleLink=window.mwdomain+window.mwid+"/"+wsFlags.noredirect+"/"+window.mw1+"/?";
  }

  var redirectPath = '';
  if (window.processor === 'buygoods') {
    redirectPath = 'options-bg/1';
  }

  var i;
  var bottles6 = document.querySelectorAll(".order-link-6-bottle");

  for(i=0; i<bottles6.length; i++) {
    console.log(bottles6.length);
    bottles6[i].onclick = function() {
      console.log("clicked6");
      var orderLink;
      orderLink = window.sixBottleLink;

      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        "ecommerce": null
      }, {
        "event": "dl_add_to_cart",
        "event_id": window.tm.generate_event_id(),
        "funnel": window.productName,
        "processor": window.processor,
        "ecommerce": {
          "currency": "USD",
          "value": window.sixBottlePrice,
          "items": window.tm.sixBottle
        }
      });

      if(window.redirectM || window.redirectD ) {
        window.location = orderLink+'&redirect='+btoa('https://'+window.location.hostname+'/' + redirectPath + '/6/') +"&"+window.variables+"&sessid2="+getCookieValue('sessid2');
      }else {
        window.location = orderLink+"&"+window.variables;
      }

    }
  }

  var bottles3 = document.querySelectorAll(".order-link-3-bottle");
  for(i=0; i<bottles3.length; i++) {
    bottles3[i].onclick = function() {
      var orderLink;
      orderLink = window.threeBottleLink;

      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        "ecommerce": null
      }, {
        "event": "dl_add_to_cart",
        "event_id": window.tm.generate_event_id(),
        "funnel": window.productName,
        "processor": window.processor,
        "ecommerce": {
          "currency": "USD",
          "value": window.threeBottlePrice,
          "items": window.tm.threeBottle // see products array below
        }
      });

      console.log("clicked3");
      if(window.redirectM || window.redirectD ) {
        window.location = orderLink+'&redirect='+btoa('https://'+window.location.hostname +'/' + redirectPath + '/3/') +"&"+window.variables+"&sessid2="+getCookieValue('sessid2');
      }else {
        window.location = orderLink+"&"+window.variables;
      }

    }
  }
  var bottles1 = document.querySelectorAll(".order-link-1-bottle");
  for(i=0; i<bottles1.length; i++) {
    bottles1[i].onclick = function() {
      console.log("clicked1");
      var orderLink;
      orderLink = window.oneBottleLink;

      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        "ecommerce": null
      }, {
        "event": "dl_add_to_cart",
        "event_id": window.tm.generate_event_id(),
        "funnel": window.productName,
        "processor": window.processor,
        "ecommerce": {
          "currency": "USD",
          "value": window.oneBottlePrice,
          "items": window.tm.oneBottle // see products array below
        }
      });

      if(window.redirectM || window.redirectD ) {
        window.location = orderLink+'&redirect='+btoa('https://'+window.location.hostname+'/' + redirectPath + '/1/') +"&"+window.variables+"&sessid2="+getCookieValue('sessid2');
      }else {
        window.location = orderLink+"&"+window.variables;
      }

    }
  }
}
function anchorLinkSmoothScrolling() {
  document.querySelectorAll('a[href^="#"]').forEach(function ( anchor) {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();

      document.querySelector(this.getAttribute('href')).scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
}
(function () {
  window.wsFlags = {
    showFda: urlParam('fda') === '1',
    noredirect: urlParam('aid')
  };
  if(wsFlags.showFda ) {
    document.querySelector(".fda-logo").classList.add("show");
    document.querySelector(".grid").classList.add("fda");
  }
  trackingCode();
  addOrderLinks();
  anchorLinkSmoothScrolling();

})()

