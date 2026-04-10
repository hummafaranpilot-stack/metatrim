<?php
/**
 * Cloaker API
 * Handles CRUD for domain configs and log retrieval
 *
 * Actions:
 *   get_stats      - overview counters
 *   get_domains    - list all domain configs
 *   save_domain    - create or update a domain config (POST JSON)
 *   toggle_status  - flip is_active for a domain (POST JSON {id})
 *   delete_domain  - delete config + logs for a domain (POST JSON {id})
 *   get_logs       - paginated log rows (GET ?domain=&page=)
 *   clear_logs     - wipe logs + reset counters for a domain (POST JSON {domain})
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Read body for POST requests
$body   = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
}

$action = $body['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────────────────
function jsonSuccess($data = []): void {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
function jsonError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
// ─────────────────────────────────────────────────────────────────

try {
    switch ($action) {

        // ── Stats overview ────────────────────────────────────────────────
        case 'get_stats': {
            $cfg = $pdo->query("
                SELECT
                    COUNT(*)        AS total_domains,
                    SUM(is_active)  AS active_domains,
                    SUM(hits_total) AS total_hits,
                    SUM(hits_white) AS total_white,
                    SUM(hits_black) AS total_black
                FROM cloaker_configs
            ")->fetch(PDO::FETCH_ASSOC);

            $today = $pdo->query("
                SELECT
                    COUNT(*)      AS hits_today,
                    SUM(is_bot)   AS bots_today,
                    SUM(is_vpn)   AS vpn_today,
                    SUM(routed_to = 'white') AS white_today,
                    SUM(routed_to = 'black') AS black_today
                FROM cloaker_logs
                WHERE DATE(created_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);

            jsonSuccess(array_merge($cfg ?? [], $today ?? []));
        }

        // ── List all domains ──────────────────────────────────────────────
        case 'get_domains': {
            $rows = $pdo->query("
                SELECT * FROM cloaker_configs ORDER BY created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            jsonSuccess($rows);
        }

        // ── Save domain (insert or update) ────────────────────────────────
        case 'save_domain': {
            $id     = (int)($body['id'] ?? 0);
            $domain = trim($body['domain'] ?? '');
            $white  = trim($body['white_page_url'] ?? '');
            $black  = trim($body['black_page_url'] ?? '');

            if (!$domain)                           jsonError('Domain is required.');
            if (!$white)                            jsonError('White page URL is required.');
            if (!$black)                            jsonError('Black page URL is required.');
            if (!filter_var($white, FILTER_VALIDATE_URL)) jsonError('White page URL is not a valid URL.');
            if (!filter_var($black, FILTER_VALIDATE_URL)) jsonError('Black page URL is not a valid URL.');

            $fields = [
                'domain'            => $domain,
                'white_page_url'    => $white,
                'black_page_url'    => $black,
                'filter_bots'       => (int)($body['filter_bots']       ?? 1),
                'filter_vpn'        => (int)($body['filter_vpn']        ?? 1),
                'filter_datacenter' => (int)($body['filter_datacenter'] ?? 1),
                'ipqs_threshold'    => max(0, min(100, (int)($body['ipqs_threshold'] ?? 60))),
                'blocked_countries' => trim($body['blocked_countries'] ?? ''),
                'allowed_referrers' => trim($body['allowed_referrers'] ?? ''),
                'ip_blacklist'      => trim($body['ip_blacklist']      ?? ''),
            ];

            if ($id > 0) {
                // Check this ID exists
                $exists = $pdo->prepare("SELECT id FROM cloaker_configs WHERE id = ?");
                $exists->execute([$id]);
                if (!$exists->fetchColumn()) jsonError('Domain config not found.');

                $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($fields)));
                $stmt = $pdo->prepare("UPDATE cloaker_configs SET $sets WHERE id = ?");
                $stmt->execute([...array_values($fields), $id]);
            } else {
                $cols         = implode(', ', array_map(fn($k) => "`$k`", array_keys($fields)));
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $stmt = $pdo->prepare("INSERT INTO cloaker_configs ($cols) VALUES ($placeholders)");
                $stmt->execute(array_values($fields));
                $id = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT * FROM cloaker_configs WHERE id = ?");
            $stmt->execute([$id]);
            jsonSuccess($stmt->fetch(PDO::FETCH_ASSOC));
        }

        // ── Toggle active / paused ────────────────────────────────────────
        case 'toggle_status': {
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonError('Missing domain ID.');

            $pdo->prepare("UPDATE cloaker_configs SET is_active = NOT is_active WHERE id = ?")->execute([$id]);

            $stmt = $pdo->prepare("SELECT * FROM cloaker_configs WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) jsonError('Domain config not found.');
            jsonSuccess($row);
        }

        // ── Delete domain + its logs ──────────────────────────────────────
        case 'delete_domain': {
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonError('Missing domain ID.');

            $stmt = $pdo->prepare("SELECT domain FROM cloaker_configs WHERE id = ?");
            $stmt->execute([$id]);
            $domain = $stmt->fetchColumn();
            if (!$domain) jsonError('Domain config not found.');

            $pdo->prepare("DELETE FROM cloaker_logs WHERE domain = ?")->execute([$domain]);
            $pdo->prepare("DELETE FROM cloaker_configs WHERE id = ?")->execute([$id]);
            jsonSuccess(['deleted_id' => $id, 'domain' => $domain]);
        }

        // ── Paginated logs ────────────────────────────────────────────────
        case 'get_logs': {
            $domain = trim($_GET['domain'] ?? '');
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $limit  = 50;
            $offset = ($page - 1) * $limit;

            if ($domain) {
                $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM cloaker_logs WHERE domain = ?");
                $cntStmt->execute([$domain]);
                $total = (int)$cntStmt->fetchColumn();

                $stmt = $pdo->prepare(
                    "SELECT * FROM cloaker_logs WHERE domain = ?
                     ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
                );
                $stmt->execute([$domain]);
            } else {
                $total = (int)$pdo->query("SELECT COUNT(*) FROM cloaker_logs")->fetchColumn();
                $stmt  = $pdo->query(
                    "SELECT * FROM cloaker_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
                );
            }

            jsonSuccess([
                'logs'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'page'  => $page,
                'pages' => (int)ceil($total / $limit),
            ]);
        }

        // ── Clear logs + reset counters ───────────────────────────────────
        case 'clear_logs': {
            $domain = trim($body['domain'] ?? '');
            if (!$domain) jsonError('Domain is required.');

            $pdo->prepare("DELETE FROM cloaker_logs WHERE domain = ?")->execute([$domain]);
            $pdo->prepare(
                "UPDATE cloaker_configs SET hits_total=0, hits_white=0, hits_black=0 WHERE domain = ?"
            )->execute([$domain]);

            jsonSuccess(['cleared' => $domain]);
        }

        default:
            jsonError('Unknown action: ' . htmlspecialchars($action));
    }

} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
