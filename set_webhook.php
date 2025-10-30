<?php
/**
 * Set Webhook Helper
 * تنظیم خودکار webhook تلگرام
 */

session_start();

header('Content-Type: application/json');

// اگر session منقضی شده، از config.php استفاده کن
if (!isset($_SESSION['bot_token']) || !isset($_SESSION['webhook_url']) || !isset($_SESSION['webhook_secret'])) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        $_SESSION['bot_token'] = BOT_TOKEN;
        $_SESSION['webhook_url'] = WEBHOOK_URL;
        $_SESSION['webhook_secret'] = WEBHOOK_SECRET;
    } else {
        echo json_encode(['success' => false, 'error' => 'Session expired and config not found']);
        exit;
    }
}

$botToken = $_SESSION['bot_token'];
$webhookUrl = $_SESSION['webhook_url'];
$webhookSecret = $_SESSION['webhook_secret'];

$ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'url' => $webhookUrl,
    'secret_token' => $webhookSecret
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result && $result['ok']) {
    echo json_encode(['success' => true]);
} else {
    $error = $result['description'] ?? 'Unknown error';
    echo json_encode(['success' => false, 'error' => $error]);
}
