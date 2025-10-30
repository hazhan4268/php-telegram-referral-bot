<?php
/**
 * Reset Telegram Webhook: delete (drop pending) then set again
 * Security: requires admin session
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

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: admin login required']);
    exit;
}

$token = BOT_TOKEN;
$url   = WEBHOOK_URL;
$secret = defined('WEBHOOK_SECRET') ? WEBHOOK_SECRET : '';

function tg($method, $data) {
    $api = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $ch = curl_init($api);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($res, true)];
}

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
