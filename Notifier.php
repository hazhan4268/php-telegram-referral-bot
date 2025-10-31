<?php
class ErrorNotifier {
    public static function sendTelegram($title, $message, $context, array $meta = []) {
        if (!defined('BOT_TOKEN') || !defined('ADMIN_ID') || !BOT_TOKEN || !ADMIN_ID) return false;
        $maxLen = 3500;
        $host = $meta['host'] ?? (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI');
        $uri  = $meta['uri'] ?? (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $time = $meta['time'] ?? date('Y-m-d H:i:s');
        $ver  = $meta['version'] ?? (defined('APP_VERSION') ? APP_VERSION : 'unknown');
        $lines = [];
        $lines[] = "🚨 خطای سیستم";
        $lines[] = "عنوان: {$title}";
        $lines[] = "زمان: {$time}";
        $lines[] = "نسخه: {$ver}";
        $lines[] = "مسیر: {$host}{$uri}";
        $lines[] = "پیام: {$message}";
        if ($context) { $lines[] = "جزئیات: " . (is_string($context) ? $context : json_encode($context)); }
        $text = implode("\n", $lines);
        if (strlen($text) > $maxLen) $text = substr($text, 0, $maxLen) . "\n…";
        $payload = json_encode(['chat_id' => ADMIN_ID, 'text' => $text, 'disable_web_page_preview' => true]);
        $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            @curl_exec($ch);
            @curl_close($ch);
            return true;
        } else {
            $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 5]]);
            @file_get_contents($url, false, $ctx);
            return true;
        }
    }
}
