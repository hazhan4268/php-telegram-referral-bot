<?php
/**
 * Manual Webhook Setup
 * تنظیم دستی webhook بدون وابستگی به session
 */

// تنظیمات - این مقادیر را تغییر دهید
$botToken = 'YOUR_BOT_TOKEN_HERE';
$webhookUrl = 'https://yourdomain.com/webhook.php';
$secretToken = 'secure_random_token_32_chars_here'; // یک رشته امنیتی 32 کاراکتری

echo "<h2>🔧 تنظیم Webhook</h2>";

// تنظیم webhook
$ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'url' => $webhookUrl,
    'secret_token' => $secretToken
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result && $result['ok']) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>موفق:</strong> Webhook با موفقیت تنظیم شد!<br>";
    echo "📍 <strong>URL:</strong> {$webhookUrl}<br>";
    echo "🔐 <strong>Secret Token:</strong> {$secretToken}";
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>خطا:</strong> " . ($result['description'] ?? 'نامشخص') . "<br>";
    echo "🔍 <strong>پاسخ کامل:</strong> " . htmlspecialchars($response);
    echo "</div>";
}

// بررسی وضعیت webhook
echo "<h3>📊 وضعیت فعلی Webhook:</h3>";
$ch = curl_init("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$webhookInfo = json_decode($response, true);
if ($webhookInfo && $webhookInfo['ok']) {
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "URL: " . ($webhookInfo['result']['url'] ?? 'تنظیم نشده') . "\n";
    echo "آخرین خطا: " . ($webhookInfo['result']['last_error_message'] ?? 'هیچ') . "\n";
    echo "تعداد به‌روزرسانی معلق: " . ($webhookInfo['result']['pending_update_count'] ?? 0) . "\n";
    echo "</pre>";
}
?>