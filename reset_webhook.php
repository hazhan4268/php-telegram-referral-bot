<?php
/**
 * Reset Telegram Webhook: delete (drop pending) then set again
 * Security: requires admin session OR token=WEBHOOK_SECRET
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'config.php missing']);
    exit;
}
require_once __DIR__ . '/config.php';

// Global Error Handler (optional)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

// Auth: admin session or token
$authorized = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $authorized = true;
}
$tokenParam = $_GET['token'] ?? '';
if (!$authorized && defined('WEBHOOK_SECRET') && WEBHOOK_SECRET && hash_equals(WEBHOOK_SECRET, $tokenParam)) {
    $authorized = true;
}
if (!$authorized) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: admin login or valid token required']);
    exit;
}

$token = defined('BOT_TOKEN') ? BOT_TOKEN : '';
$url   = defined('WEBHOOK_URL') ? WEBHOOK_URL : (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/webhook.php' : '');
$secret = defined('WEBHOOK_SECRET') ? WEBHOOK_SECRET : '';

if (empty($token) || empty($url)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'BOT_TOKEN or WEBHOOK_URL is not set']);
    exit;
}

function tg($method, $data) {
    $api = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $payload = json_encode($data);
    if (function_exists('curl_init')) {
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($api, false, $context);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('/^HTTP\/\\d+\.\\d+\\s+(\\d+)/', $h, $m)) { $code = (int)$m[1]; break; }
            }
        }
    }
    return [$code, json_decode($res, true)];
}

try {
    $steps = [];

    // 1) deleteWebhook with drop_pending_updates=true
    list($code1, $res1) = tg('deleteWebhook', ['drop_pending_updates' => true]);
    $steps[] = ['deleteWebhook', $code1, $res1];
    if ($code1 !== 200 || !$res1 || !$res1['ok']) {
        if (function_exists('error_notify_admin')) {
            error_notify_admin('reset_webhook_delete_failed', json_encode($res1));
        }
        echo json_encode(['success' => false, 'step' => 'deleteWebhook', 'response' => $res1]);
        exit;
    }

    // 2) setWebhook again
    $payload = ['url' => $url];
    if (!empty($secret)) { $payload['secret_token'] = $secret; }
    list($code2, $res2) = tg('setWebhook', $payload);
    $steps[] = ['setWebhook', $code2, $res2];
    if ($code2 !== 200 || !$res2 || !$res2['ok']) {
        if (function_exists('error_notify_admin')) {
            error_notify_admin('reset_webhook_set_failed', json_encode($res2));
        }
        echo json_encode(['success' => false, 'step' => 'setWebhook', 'response' => $res2]);
        exit;
    }

    echo json_encode(['success' => true, 'steps' => $steps]);
} catch (Throwable $e) {
    if (function_exists('error_notify_admin')) {
        error_notify_admin('reset_webhook_exception', $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
