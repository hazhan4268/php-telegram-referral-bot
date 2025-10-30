<?php
/**
 * Get Telegram getWebhookInfo (secured)
 * Auth: admin session OR ?token=WEBHOOK_SECRET from config
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Load error handler early (optional)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

// Safe parse small set of constants from config.php without executing it
function parse_config_constants($file) {
    $out = [];
    if (!is_file($file)) return $out;
    $code = @file_get_contents($file);
    if ($code === false) return $out;
    $keys = ['BOT_TOKEN','WEBHOOK_SECRET'];
    foreach ($keys as $key) {
        if (preg_match("/define\(\s*'" . preg_quote($key, '/') . "'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_:\\/.+-]+))\s*\)\s*;/", $code, $m)) {
            $val = $m[1] !== '' ? $m[1] : ($m[2] !== '' ? $m[2] : $m[3]);
            $out[$key] = $val;
        }
    }
    return $out;
}

$cfg = parse_config_constants(__DIR__ . '/config.php');
if (empty($cfg) || empty($cfg['BOT_TOKEN'])) {
    echo json_encode(['ok' => false, 'error' => 'config.php missing or BOT_TOKEN not set']);
    exit;
}

// Auth: admin session or token=WEBHOOK_SECRET
$authorized = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $authorized = true;
}
$secretQuery = $_GET['token'] ?? '';
$cfgSecret   = $cfg['WEBHOOK_SECRET'] ?? '';
if (!$authorized && $cfgSecret !== '' && $secretQuery !== '' && hash_equals($cfgSecret, $secretQuery)) {
    $authorized = true;
}
if (!$authorized) {
    echo json_encode(['ok' => false, 'error' => 'Forbidden: login as admin or provide ?token=WEBHOOK_SECRET']);
    exit;
}

$token = $cfg['BOT_TOKEN'];
$api   = 'https://api.telegram.org/bot' . $token . '/getWebhookInfo';

try {
    if (function_exists('curl_init')) {
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($res, true);
        echo json_encode(['http' => $code, 'response' => $json]);
    } else {
        $res = @file_get_contents($api);
        $json = json_decode($res, true);
        echo json_encode(['http' => 200, 'response' => $json]);
    }
} catch (Throwable $e) {
    if (function_exists('error_notify_admin')) {
        error_notify_admin('get_webhook_info_exception', $e->getMessage());
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
