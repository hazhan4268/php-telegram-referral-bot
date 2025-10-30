<?php
/**
 * GitHub Auto Deploy Script
 * این فایل را در root سایت قرار دهید و از GitHub Webhook استفاده کنید
 */

// تنظیمات امنیتی
define('GITHUB_SECRET', 'YOUR_WEBHOOK_SECRET_HERE'); // از GitHub webhook تنظیم کنید
define('BRANCH', 'main'); // یا master
define('REPO_PATH', __DIR__); // مسیر repository
define('LOG_FILE', __DIR__ . '/deploy.log');

// دریافت payload از GitHub
$rawPayload = file_get_contents('php://input');
$headers = getallheaders();

// بررسی امضای GitHub
if (isset($headers['X-Hub-Signature-256'])) {
    $signature = 'sha256=' . hash_hmac('sha256', $rawPayload, GITHUB_SECRET);
    
    if (!hash_equals($signature, $headers['X-Hub-Signature-256'])) {
        http_response_code(403);
        die('Invalid signature');
    }
} else {
    http_response_code(401);
    die('Missing signature');
}

// پردازش payload
$payload = json_decode($rawPayload, true);

// بررسی event type
$event = $headers['X-GitHub-Event'] ?? '';

if ($event !== 'push') {
    logMessage('Event ignored: ' . $event);
    die('Event ignored');
}

// بررسی branch
if (isset($payload['ref']) && $payload['ref'] !== 'refs/heads/' . BRANCH) {
    logMessage('Branch ignored: ' . $payload['ref']);
    die('Branch ignored');
}

// اجرای deployment
logMessage('=== Starting Deployment ===');
logMessage('Triggered by: ' . ($payload['pusher']['name'] ?? 'Unknown'));
logMessage('Commit: ' . ($payload['head_commit']['message'] ?? 'N/A'));

// دستورات Git
$commands = [
    'cd ' . REPO_PATH,
    'git fetch origin ' . BRANCH,
    'git reset --hard origin/' . BRANCH,
    'git clean -fd',
    'chmod -R 755 .',
    'chmod 644 config.php 2>/dev/null || true', // حفظ امنیت config
];

$output = [];
$returnCode = 0;

foreach ($commands as $command) {
    logMessage('Executing: ' . $command);
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        logMessage('ERROR: Command failed with code ' . $returnCode);
        logMessage('Output: ' . implode("\n", $output));
        http_response_code(500);
        die('Deployment failed');
    }
    
    logMessage('Output: ' . implode("\n", $output));
    $output = []; // پاکسازی برای دستور بعدی
}

logMessage('=== Deployment Completed Successfully ===');
logMessage('');

// پاسخ موفقیت‌آمیز
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment completed',
    'timestamp' => date('Y-m-d H:i:s')
]);

/**
 * ثبت لاگ
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}
