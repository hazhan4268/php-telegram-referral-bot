<?php
/**
 * Manual Update Endpoint
 * Call this file in the browser or via curl to pull latest changes from GitHub
 * Security: requires either an admin session or a valid token (?token=WEBHOOK_SECRET)
 */

header('Content-Type: application/json; charset=utf-8');

$startTime = microtime(true);

// Optional: show more detailed errors when DEBUG_MODE is true
$DEBUG = false;

// Try to load config for WEBHOOK_SECRET and settings
$configLoaded = false;
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $configLoaded = true;
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $DEBUG = true;
    }
}

// Global Error Handler (after config if available)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

// Basic auth check: admin session only
session_start();
$authorized = false;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $authorized = true;
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden: Admin login required',
        'hint' => 'Please login to admin panel first at /admin/'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check exec availability
if (!function_exists('exec')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'exec() is disabled on this server',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$REPO_PATH = __DIR__;
$BRANCH = isset($_GET['branch']) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_GET['branch']) : 'main';
$logFile = __DIR__ . '/deploy.log';

function logMessage($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] [update.php] {$msg}\n", FILE_APPEND);
}

function run($cmd, &$output, &$code) {
    $output = [];
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);
    return $code === 0;
}

// Verify git repo exists
if (!is_dir($REPO_PATH . '/.git')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Not a git repository at ' . $REPO_PATH,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Backup config.php (if exists) to preserve local settings
$backupTaken = false;
if (file_exists($REPO_PATH . '/config.php')) {
    if (@copy($REPO_PATH . '/config.php', $REPO_PATH . '/config.php.backup')) {
        $backupTaken = true;
        logMessage('Backup created: config.php.backup');
    } else {
        logMessage('WARNING: Failed to create config.php backup');
    }
}

$steps = [];

// Detect current branch (for info)
$steps[] = 'cd ' . $REPO_PATH;
$steps[] = 'git rev-parse --abbrev-ref HEAD';

// Fetch and reset to origin/BRANCH
$steps[] = 'git fetch origin ' . $BRANCH;
$steps[] = 'git reset --hard origin/' . $BRANCH;
$steps[] = 'git clean -fd';

// Permissions (best effort)
$steps[] = 'chmod -R 755 .';
$steps[] = 'chmod 644 config.php 2>/dev/null || true';
$steps[] = 'chmod 644 .htaccess 2>/dev/null || true';

$allOutput = [];

// Execute steps
foreach ($steps as $cmd) {
    $full = 'cd ' . escapeshellarg($REPO_PATH) . ' && ' . $cmd;
    logMessage('Executing: ' . $cmd);
    $out = [];
    $code = 0;
    exec($full . ' 2>&1', $out, $code);
    $allOutput[] = ['$ ' . $cmd, implode("\n", $out)];
    if ($code !== 0) {
        logMessage('ERROR: command failed (' . $code . ')');
        if (function_exists('error_notify_admin')) {
            error_notify_admin('update_failed', 'Update failed at: ' . $cmd, implode("\n", $out));
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Update failed at: ' . $cmd,
            'code' => $code,
            'output' => $out,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Restore config.php if backup was taken (should be unchanged, but safe)
if ($backupTaken && file_exists($REPO_PATH . '/config.php.backup')) {
    if (@copy($REPO_PATH . '/config.php.backup', $REPO_PATH . '/config.php')) {
        @unlink($REPO_PATH . '/config.php.backup');
        logMessage('config.php restored from backup');
    } else {
        logMessage('WARNING: Failed to restore config.php from backup');
    }
}

// Increment BUILD like deploy.php
$buildFile = $REPO_PATH . '/BUILD';
$prevBuild = 0;
if (is_file($buildFile)) {
    $content = @file_get_contents($buildFile);
    if ($content !== false) { $prevBuild = (int)trim($content); }
}
$newBuild = $prevBuild + 1;
if (@file_put_contents($buildFile, (string)$newBuild) !== false) {
    logMessage('Build incremented: ' . $prevBuild . ' -> ' . $newBuild);
}

$elapsed = round((microtime(true) - $startTime) * 1000);

echo json_encode([
    'success' => true,
    'message' => 'Update completed successfully',
    'branch' => $BRANCH,
    'build' => $newBuild,
    'elapsed_ms' => $elapsed,
    'timestamp' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
