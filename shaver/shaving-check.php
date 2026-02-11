<?php
/**
 * Shaving Check Script
 *
 * This PHP file outputs JavaScript that:
 * 1. Contains active shaving sessions from database
 * 2. Checks if current page's aff_id should be shaved
 * 3. Removes URL parameters BEFORE BuyGoods tracking runs
 * 4. Tracks the visit to database
 * 5. Calls window.runBuyGoods() to allow BuyGoods tracking to proceed
 */

// Set content type to JavaScript
header('Content-Type: application/javascript; charset=utf-8');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');

// Prevent caching so we always get fresh session data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Database configuration - NEW HOSTING
define('DB_HOST', 'localhost');
define('DB_NAME', 'u373133718_shavingdb');
define('DB_USER', 'u373133718_shavingdbuser');
define('DB_PASS', 'Ali547$$$');

// Get active sessions from database
$sessions = [];
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SELECT id, aff_id, sub_id, replace_mode, replace_aff_id, replace_sub_id FROM shaving_sessions WHERE active = 1");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If database error, output empty sessions array
    $sessions = [];
}

// Convert sessions to JavaScript format
$sessionsJson = json_encode(array_map(function($s) {
    return [
        'id' => $s['id'],
        'affId' => $s['aff_id'],
        'subId' => $s['sub_id'] ?? '',
        'replaceMode' => (bool)$s['replace_mode'],
        'replaceAffId' => $s['replace_aff_id'] ?? '',
        'replaceSubId' => $s['replace_sub_id'] ?? ''
    ];
}, $sessions));

// Get the API URL base (same directory as this script)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$apiUrl = $protocol . '://' . $host . $path . '/api.php';
?>
/**
 * Shaving Check - Runs BEFORE BuyGoods tracking
 * Generated: <?php echo date('Y-m-d H:i:s'); ?>
 * Active Sessions: <?php echo count($sessions); ?>
 */
(function() {
    'use strict';

    // Active shaving sessions from database
    var sessions = <?php echo $sessionsJson; ?>;
    var API_URL = '<?php echo $apiUrl; ?>';

    console.log('[Shaving] Loaded', sessions.length, 'active sessions');

    // Get URL parameters
    function getUrlParams() {
        var params = {};
        var search = window.location.search.substring(1);
        if (!search) return params;

        var pairs = search.split('&');
        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].split('=');
            var key = decodeURIComponent(pair[0]);
            var value = pair[1] ? decodeURIComponent(pair[1]) : '';
            params[key] = value;
        }
        return params;
    }

    // Find matching session for this aff_id
    function findSession(affId, subId) {
        for (var i = 0; i < sessions.length; i++) {
            var s = sessions[i];
            if (s.affId === affId) {
                // If session has subId filter, must match
                if (s.subId && s.subId !== subId) {
                    continue;
                }
                return s;
            }
        }
        return null;
    }

    // Remove or replace URL parameters
    function modifyUrl(session) {
        var url = new URL(window.location.href);

        if (session.replaceMode) {
            // Replace with our aff_id
            url.searchParams.set('aff_id', session.replaceAffId);
            if (session.replaceSubId) {
                url.searchParams.set('subid', session.replaceSubId);
            } else {
                url.searchParams.delete('subid');
            }
            console.log('[Shaving] Replacing aff_id with:', session.replaceAffId);
        } else {
            // Remove affiliate parameters
            url.searchParams.delete('aff_id');
            url.searchParams.delete('subid');
            console.log('[Shaving] Removing affiliate parameters');
        }

        // Update URL without reloading page
        window.history.replaceState({}, '', url.toString());
    }

    // Track visit to database (async, doesn't block)
    function trackVisit(session, affId, subId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var result = JSON.parse(xhr.responseText);
                        console.log('[Shaving] Visit tracked - Total:', result.totalVisits);
                    } catch (e) {
                        console.error('[Shaving] Error parsing response:', e);
                    }
                }
            }
        };
        xhr.send(JSON.stringify({
            action: 'track_visit',
            session_id: session.id,
            aff_id: affId,
            sub_id: subId,
            page: window.location.href,
            referrer: document.referrer || 'direct'
        }));
    }

    // Log ALL affiliate traffic to analytics (async, doesn't block)
    function logTraffic(affId, subId, wasShaved, shavingSessionId, source) {
        if (!affId) return; // Only log if there's an affiliate ID

        // Use source parameter (utm_source) if provided, otherwise use referrer
        var trafficSource = source || document.referrer || 'direct';
        console.log('[Analytics] Sending traffic log for aff_id:', affId, 'source:', trafficSource, 'to:', API_URL);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    console.log('[Analytics] Traffic logged successfully for aff_id:', affId);
                    // NEW: Capture traffic_id and process queued events
                    try {
                        var result = JSON.parse(xhr.responseText);
                        if (result.success && result.traffic_id && window.__behaviorTracking) {
                            window.__behaviorTracking.trafficId = result.traffic_id;
                            console.log('[Behavior Tracking] Traffic ID captured:', result.traffic_id);

                            // Process queued events
                            if (window.__behaviorTracking.eventQueue.length > 0) {
                                console.log('[Behavior Tracking] Processing', window.__behaviorTracking.eventQueue.length, 'queued events');
                                window.__behaviorTracking.eventQueue.forEach(function(event) {
                                    logBehaviorEvent(event.eventType, event.eventData);
                                });
                                window.__behaviorTracking.eventQueue = [];
                            }
                        }
                    } catch (e) {
                        console.error('[Behavior Tracking] Error parsing response:', e);
                    }
                } else {
                    console.error('[Analytics] Failed to log traffic. Status:', xhr.status, 'Response:', xhr.responseText);
                }
            }
        };
        xhr.onerror = function() {
            console.error('[Analytics] Network error logging traffic');
        };
        xhr.send(JSON.stringify({
            action: 'log_traffic',
            aff_id: affId,
            sub_id: subId,
            page_url: window.location.href,
            referrer: trafficSource,
            user_agent: navigator.userAgent,
            was_shaved: wasShaved,
            shaving_session_id: shavingSessionId,
            // NEW: Behavior tracking fields
            session_uuid: window.__behaviorTracking ? window.__behaviorTracking.sessionUUID : null,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight
        }));
    }

    // ============================================================
    // BEHAVIOR TRACKING SYSTEM
    // ============================================================

    // Generate or retrieve session UUID
    function getSessionUUID() {
        var uuid = null;
        try {
            uuid = sessionStorage.getItem('_behavior_session_id');
        } catch (e) {
            console.warn('[Behavior Tracking] SessionStorage not available:', e);
        }
        if (!uuid) {
            uuid = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            try {
                sessionStorage.setItem('_behavior_session_id', uuid);
            } catch (e) {
                console.warn('[Behavior Tracking] Could not save session UUID:', e);
            }
        }
        return uuid;
    }

    // Initialize tracking data globally
    window.__behaviorTracking = {
        sessionUUID: getSessionUUID(),
        trafficId: null,  // Will be set after initial log_traffic call
        landedAt: Date.now(),
        maxScrollDepth: 0,
        clickCount: 0,
        hasReachedCheckout: false,
        eventQueue: [],  // Queue events until trafficId available
        isTabVisible: true,
        lastScrollTime: 0,
        firstClickTime: null,
        checkoutTime: null,
        checkoutUrl: null,
        pageLoadTime: window.performance ? (window.performance.timing.loadEventEnd - window.performance.timing.navigationStart) : null
    };

    // Log behavior event to API
    function logBehaviorEvent(eventType, eventData) {
        if (!window.__behaviorTracking.trafficId) {
            // Queue events until we have traffic_id
            window.__behaviorTracking.eventQueue.push({
                eventType: eventType,
                eventData: eventData,
                timestamp: Date.now()
            });
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'log_behavior_event',
            traffic_id: window.__behaviorTracking.trafficId,
            session_uuid: window.__behaviorTracking.sessionUUID,
            event_type: eventType,
            event_data: eventData,
            timestamp: new Date().toISOString()
        }));
    }

    // Batch update session metrics
    function updateSessionMetrics() {
        if (!window.__behaviorTracking.trafficId) return;

        var sessionDuration = Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'update_session_metrics',
            traffic_id: window.__behaviorTracking.trafficId,
            session_uuid: window.__behaviorTracking.sessionUUID,
            session_duration: sessionDuration,
            max_scroll_depth: window.__behaviorTracking.maxScrollDepth,
            total_clicks: window.__behaviorTracking.clickCount,
            reached_checkout: window.__behaviorTracking.hasReachedCheckout ? 1 : 0,
            checkout_url: window.__behaviorTracking.checkoutUrl || null,
            time_to_first_click: window.__behaviorTracking.firstClickTime ?
                Math.floor((window.__behaviorTracking.firstClickTime - window.__behaviorTracking.landedAt) / 1000) : null,
            time_to_checkout: window.__behaviorTracking.checkoutTime ?
                Math.floor((window.__behaviorTracking.checkoutTime - window.__behaviorTracking.landedAt) / 1000) : null,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            page_load_time: window.__behaviorTracking.pageLoadTime,
            bounce: window.__behaviorTracking.clickCount === 0 ? 1 : 0
        }));
    }

    // Setup scroll tracking with debouncing
    function setupScrollTracking() {
        var scrollTimeout;
        var lastDepth = 0;

        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            window.__behaviorTracking.lastScrollTime = Date.now();

            scrollTimeout = setTimeout(function() {
                var scrollY = window.scrollY || window.pageYOffset;
                var docHeight = document.documentElement.scrollHeight;
                var viewportHeight = window.innerHeight;
                var scrollDepth = Math.min(100, Math.floor(((scrollY + viewportHeight) / docHeight) * 100));

                if (scrollDepth > window.__behaviorTracking.maxScrollDepth) {
                    window.__behaviorTracking.maxScrollDepth = scrollDepth;

                    // Log milestone scroll depths
                    if (scrollDepth >= 25 && lastDepth < 25) {
                        logBehaviorEvent('scroll', {scrollDepth: 25, milestone: true, viewportHeight: viewportHeight, documentHeight: docHeight});
                    } else if (scrollDepth >= 50 && lastDepth < 50) {
                        logBehaviorEvent('scroll', {scrollDepth: 50, milestone: true, viewportHeight: viewportHeight, documentHeight: docHeight});
                    } else if (scrollDepth >= 75 && lastDepth < 75) {
                        logBehaviorEvent('scroll', {scrollDepth: 75, milestone: true, viewportHeight: viewportHeight, documentHeight: docHeight});
                    } else if (scrollDepth >= 90 && lastDepth < 90) {
                        logBehaviorEvent('scroll', {scrollDepth: 90, milestone: true, viewportHeight: viewportHeight, documentHeight: docHeight});
                    }

                    lastDepth = scrollDepth;
                }
            }, 300);  // 300ms debounce
        });
    }

    // Extract package information from button context
    function extractPackageInfo(button) {
        var info = {};

        // Look for price in button or parent
        var buttonText = button.textContent || button.innerText || '';
        var priceMatch = buttonText.match(/\$(\d+)/);
        if (priceMatch) {
            info.price = '$' + priceMatch[1];
        }

        // Look for bottle count
        var bottleMatch = buttonText.match(/(\d+)\s*bottle/i);
        if (bottleMatch) {
            info.bottles = bottleMatch[1];
        }

        return Object.keys(info).length > 0 ? info : null;
    }

    // Setup click tracking with detailed capture
    function setupDetailedClickTracking() {
        document.addEventListener('click', function(e) {
            var target = e.target;

            // Find closest button/link
            while (target && target !== document.body) {
                if (target.tagName === 'A' || target.tagName === 'BUTTON' ||
                    (target.classList && target.classList.contains('mt-buy-now-btn'))) {

                    window.__behaviorTracking.clickCount++;
                    if (!window.__behaviorTracking.firstClickTime) {
                        window.__behaviorTracking.firstClickTime = Date.now();
                    }

                    // Extract button details
                    var buttonText = target.textContent ? target.textContent.trim() : '';
                    var buttonHref = target.href || '';
                    var buttonId = target.id || '';
                    var buttonClass = target.className || '';

                    // Try to extract package/price info
                    var packageInfo = extractPackageInfo(target);

                    logBehaviorEvent('click', {
                        buttonText: buttonText.substring(0, 100), // Limit length
                        buttonId: buttonId,
                        buttonClass: buttonClass,
                        targetUrl: buttonHref.substring(0, 200), // Limit length
                        clickX: e.clientX,
                        clickY: e.clientY,
                        scrollDepthAtClick: window.__behaviorTracking.maxScrollDepth,
                        timeFromLanding: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000),
                        packageInfo: packageInfo
                    });

                    break;
                }
                target = target.parentElement;
            }
        });
    }

    // Setup hover tracking on buy buttons
    function setupHoverTracking() {
        var hoverStartTime = null;
        var hoveredButton = null;

        document.addEventListener('mouseover', function(e) {
            var target = e.target;
            while (target && target !== document.body) {
                if (target.classList && target.classList.contains('mt-buy-now-btn')) {
                    hoverStartTime = Date.now();
                    hoveredButton = target;
                    break;
                }
                target = target.parentElement;
            }
        });

        document.addEventListener('mouseout', function(e) {
            if (hoveredButton && hoverStartTime) {
                var duration = Date.now() - hoverStartTime;
                if (duration > 500) {  // Only log hovers > 500ms
                    var buttonText = hoveredButton.textContent ? hoveredButton.textContent.trim() : '';
                    logBehaviorEvent('hover', {
                        element: '.mt-buy-now-btn',
                        buttonText: buttonText.substring(0, 100),
                        duration: duration
                    });
                }
                hoverStartTime = null;
                hoveredButton = null;
            }
        });
    }

    // Setup checkout detection
    function setupCheckoutDetection() {
        // Method 1: Detect BuyGoods checkout URL navigation
        var originalPushState = history.pushState;
        if (originalPushState) {
            history.pushState = function() {
                if (arguments[2]) {
                    checkIfCheckoutReached(arguments[2]);
                }
                return originalPushState.apply(history, arguments);
            };
        }

        window.addEventListener('popstate', function() {
            checkIfCheckoutReached(window.location.href);
        });

        // Method 2: Intercept link clicks to checkout
        document.addEventListener('click', function(e) {
            var target = e.target;
            while (target && target !== document.body) {
                if (target.href && target.href.includes('buygoods.com')) {
                    if (!window.__behaviorTracking.hasReachedCheckout) {
                        window.__behaviorTracking.hasReachedCheckout = true;
                        window.__behaviorTracking.checkoutUrl = target.href;
                        window.__behaviorTracking.checkoutTime = Date.now();

                        logBehaviorEvent('checkout_reached', {
                            checkoutUrl: target.href.substring(0, 200),
                            timeToCheckout: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000),
                            scrollDepthAtCheckout: window.__behaviorTracking.maxScrollDepth,
                            clicksBeforeCheckout: window.__behaviorTracking.clickCount
                        });
                    }
                    break;
                }
                target = target.parentElement;
            }
        });

        // Method 3: Detect if current page is checkout (for direct links)
        if (window.location.href.includes('buygoods.com/checkout')) {
            window.__behaviorTracking.hasReachedCheckout = true;
            window.__behaviorTracking.checkoutUrl = window.location.href;
            logBehaviorEvent('checkout_reached', {
                checkoutUrl: window.location.href.substring(0, 200),
                directCheckout: true
            });
        }
    }

    function checkIfCheckoutReached(url) {
        if (url && url.includes('buygoods.com/checkout') && !window.__behaviorTracking.hasReachedCheckout) {
            window.__behaviorTracking.hasReachedCheckout = true;
            window.__behaviorTracking.checkoutUrl = url;
            window.__behaviorTracking.checkoutTime = Date.now();

            logBehaviorEvent('checkout_reached', {
                checkoutUrl: url.substring(0, 200),
                timeToCheckout: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000)
            });
        }
    }

    // Setup tab visibility tracking
    function setupTabVisibilityTracking() {
        var visibleStart = Date.now();

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                var visibleDuration = Date.now() - visibleStart;
                window.__behaviorTracking.isTabVisible = false;

                logBehaviorEvent('tab_hidden', {
                    hidden: true,
                    visibleDuration: visibleDuration
                });
            } else {
                window.__behaviorTracking.isTabVisible = true;
                visibleStart = Date.now();

                logBehaviorEvent('tab_visible', {
                    hidden: false
                });
            }
        });
    }

    // Send final update before page unload
    function setupBeforeUnload() {
        window.addEventListener('beforeunload', function() {
            updateSessionMetrics();
            // Use sendBeacon for reliable delivery
            if (navigator.sendBeacon && window.__behaviorTracking.trafficId) {
                var data = JSON.stringify({
                    action: 'update_session_metrics',
                    traffic_id: window.__behaviorTracking.trafficId,
                    session_duration: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000),
                    max_scroll_depth: window.__behaviorTracking.maxScrollDepth,
                    total_clicks: window.__behaviorTracking.clickCount,
                    reached_checkout: window.__behaviorTracking.hasReachedCheckout ? 1 : 0
                });
                navigator.sendBeacon(API_URL, data);
            }
        });
    }

    // Periodic metric updates (every 30 seconds)
    setInterval(updateSessionMetrics, 30000);

    // Initialize all behavior tracking after DOM is ready
    function initBehaviorTracking() {
        setupScrollTracking();
        setupDetailedClickTracking();
        setupHoverTracking();
        setupCheckoutDetection();
        setupTabVisibilityTracking();
        setupBeforeUnload();

        console.log('[Behavior Tracking] Initialized with session UUID:', window.__behaviorTracking.sessionUUID);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBehaviorTracking);
    } else {
        initBehaviorTracking();
    }

    // Main logic
    var params = getUrlParams();
    var affId = params.aff_id || params.affid || '';
    var subId = params.subid || params.sub_id || '';
    // Check for UTM or custom source parameter
    var utmSource = params.utm_source || params.source || params.ref || '';

    if (affId) {
        var session = findSession(affId, subId);

        if (session) {
            console.log('[Shaving] MATCH FOUND - aff_id:', affId, 'will be', session.replaceMode ? 'REPLACED' : 'REMOVED');

            // Log traffic BEFORE modifying URL (was_shaved = true)
            logTraffic(affId, subId, true, session.id, utmSource);

            // Modify URL BEFORE BuyGoods sees it
            modifyUrl(session);

            // Track the visit (async)
            trackVisit(session, affId, subId);

            // Store session info for click tracking later
            window.__shavingSession = session;
            window.__shavingOriginalAffId = affId;
            window.__shavingOriginalSubId = subId;
        } else {
            console.log('[Shaving] No match for aff_id:', affId);
            // Log traffic even when NOT shaved (was_shaved = false)
            logTraffic(affId, subId, false, null, utmSource);
        }
    } else {
        console.log('[Shaving] No aff_id in URL');
    }

    // Signal that shaving is complete
    console.log('[Shaving] Complete - injecting BuyGoods script');

    // Mark as loaded for other scripts
    window.__shavingLoaded = true;

    // ============================================================
    // INJECT BUYGOODS TRACKING SCRIPT
    // Using document.write guarantees BuyGoods runs AFTER URL is clean
    // ============================================================

    // Cookie reader function (required by BuyGoods)
    function ReadCookie(name) {
        name += '=';
        var parts = document.cookie.split(/;\s*/);
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            if (part.indexOf(name) == 0) return part.substring(name.length);
        }
        return '';
    }
    window.ReadCookie = ReadCookie; // Make globally available

    // Build BuyGoods tracking URL with CLEAN window.location.href
    var bgSrc = "https://tracking.buygoods.com/track/?a=11943&firstcookie=0"
        + "&tracking_redirect=&referrer=" + encodeURIComponent(document.referrer)
        + "&sessid2=" + ReadCookie('sessid2')
        + "&product=met2v2,met3v2,met6v2,met2v2s,met6v2s,met3v2s"
        + "&vid1=&vid2=&vid3="
        + "&caller_url=" + encodeURIComponent(window.location.href);

    // Inject BuyGoods tracking script
    // Use document.write() during initial parsing (first load) for guaranteed synchronous execution
    // Use createElement for already-loaded pages (SPA navigation, etc.)
    if (document.readyState === 'loading') {
        // FIRST LOAD: document.write() is synchronous and reliable during parsing
        document.write('<scr' + 'ipt type="text/javascript" src="' + bgSrc + '"></scr' + 'ipt>');
        console.log('[BuyGoods] Tracking script injected via document.write (first load)');
    } else {
        // PAGE ALREADY LOADED: use createElement
        var bgScript = document.createElement('script');
        bgScript.type = 'text/javascript';
        bgScript.src = bgSrc;
        document.head.appendChild(bgScript);
        console.log('[BuyGoods] Tracking script injected via createElement (reload)');
    }

    console.log('[BuyGoods] Clean URL:', window.location.href);

    // Conversion iframe (after DOM ready)
    function injectConversionIframe() {
        setTimeout(function() {
            if (!document.body) return;
            var i = document.createElement("iframe");
            i.async = true;
            i.style.display = "none";
            // Re-read sessid2 cookie here - it may have been set by BuyGoods tracking above
            i.setAttribute("src", "https://buygoods.com/affiliates/go/conversion/iframe/bg?a=11943&t=6bea6c7c7a71a36b83e176af6f6189de&s=" + ReadCookie('sessid2'));
            document.body.appendChild(i);
        }, 2000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectConversionIframe);
    } else {
        injectConversionIframe();
    }

    // ============================================================
    // CLICK TRACKING - Runs after page loads
    // ============================================================

    // Track click to database
    function trackClick(session, affId, subId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    console.log('[Shaving] Click tracked - Total:', result.totalClicks);
                } catch (e) {
                    console.error('[Shaving] Error parsing click response:', e);
                }
            }
        };
        xhr.send(JSON.stringify({
            action: 'track_click',
            session_id: session.id,
            aff_id: affId,
            sub_id: subId,
            page: window.location.href
        }));
    }

    // Setup click handlers after DOM is ready
    function setupClickHandlers() {
        // Check if we have an active shaving session
        if (!window.__shavingSession) {
            console.log('[Shaving] No active shaving session - click tracking disabled');
            return;
        }

        var session = window.__shavingSession;
        var affId = window.__shavingOriginalAffId;
        var subId = window.__shavingOriginalSubId;

        // Find all Meta Trim buy buttons
        var buttons = document.querySelectorAll('.mt-buy-now-btn, a[href*="buygoods.com"]');
        console.log('[Shaving] Found', buttons.length, 'buy buttons for click tracking');

        buttons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                console.log('[Shaving] Buy button clicked - tracking...');
                trackClick(session, affId, subId);
            });
        });
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupClickHandlers);
    } else {
        // DOM already loaded
        setupClickHandlers();
    }
})();
