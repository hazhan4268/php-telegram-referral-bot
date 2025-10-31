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

// Update mode: git (default) or zip (download from GitHub without git)
$MODE = isset($_GET['source']) ? strtolower(trim($_GET['source'])) : 'git';
if (!in_array($MODE, ['git', 'zip'], true)) { $MODE = 'git'; }

// If using git mode, ensure exec() is available
if ($MODE === 'git' && !function_exists('exec')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'exec() is disabled on this server',
        'hint' => 'Use source=zip to update via GitHub zip without git',
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

// Helper: logging

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

if ($MODE === 'git') {
    // Verify git repo exists
    if (!is_dir($REPO_PATH . '/.git')) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Not a git repository at ' . $REPO_PATH,
            'hint' => 'Use source=zip to update without an on-server git repo',
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
} else {
    // ZIP mode: download zipball from GitHub and extract over current folder
    // Determine repo owner/name and ref (tag/commit/branch)
    $owner = defined('GITHUB_OWNER') ? GITHUB_OWNER : null;
    $repo  = defined('GITHUB_REPO') ? GITHUB_REPO : null;
    $token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : null; // optional for private repos
    $repoParam = isset($_GET['repo']) ? trim($_GET['repo']) : '';
    if (!$owner || !$repo) {
        if (strpos($repoParam, '/') !== false) {
            [$owner, $repo] = explode('/', $repoParam, 2);
        }
    }
    $ref = isset($_GET['ref']) ? trim($_GET['ref']) : $BRANCH; // allow tag/commit/branch

    if (!$owner || !$repo) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing repository info',
            'hint' => 'Define GITHUB_OWNER/GITHUB_REPO in config.php or pass ?repo=owner/repo',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'ZipArchive extension is not available on the server',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $zipUrl = "https://api.github.com/repos/{$owner}/{$repo}/zipball/" . rawurlencode($ref);
    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upd_' . uniqid();
    $tmpZip = $tmpDir . '.zip';
    @mkdir($tmpDir, 0775, true);

    // Download zip
    $headers = [
        'User-Agent: php-referral-bot-updater',
        'Accept: application/vnd.github+json'
    ];
    if ($token && is_string($token) && $token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $downloadOk = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($data !== false && $http >= 200 && $http < 300) {
            $downloadOk = file_put_contents($tmpZip, $data) !== false;
        } else {
            logMessage('ZIP download failed: HTTP ' . $http . ' ' . $err);
        }
    }
    if (!$downloadOk) {
        // fallback to file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
            ]
        ]);
        $data = @file_get_contents($zipUrl, false, $context);
        if ($data !== false) {
            $downloadOk = file_put_contents($tmpZip, $data) !== false;
        }
    }

    if (!$downloadOk || !is_file($tmpZip)) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to download zip from GitHub',
            'url' => $zipUrl,
        ], JSON_UNESCAPED_UNICODE);
        @unlink($tmpZip);
        @rmdir($tmpDir);
        exit;
    }

    // Extract zip
    $zip = new ZipArchive();
    if ($zip->open($tmpZip) !== true) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to open zip archive',
        ], JSON_UNESCAPED_UNICODE);
        @unlink($tmpZip);
        @rmdir($tmpDir);
        exit;
    }
    if (!$zip->extractTo($tmpDir)) {
        $zip->close();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to extract zip archive',
        ], JSON_UNESCAPED_UNICODE);
        @unlink($tmpZip);
        @rmdir($tmpDir);
        exit;
    }
    $zip->close();

    // Find extracted root directory (GitHub zipball has a single top-level folder)
    $entries = array_values(array_filter(scandir($tmpDir), function($f) use ($tmpDir) {
        return $f !== '.' && $f !== '..' && is_dir($tmpDir . DIRECTORY_SEPARATOR . $f);
    }));
    if (empty($entries)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Zip archive seems empty or invalid layout',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $root = $tmpDir . DIRECTORY_SEPARATOR . $entries[0];

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

    // Recursively copy files from extracted root to project, skipping sensitive/local files
    $skipNames = ['.git', '.github'];
    $copyErr = null;
    $copyFn = function($src, $dst) use (&$copyFn, $skipNames, &$copyErr) {
        if ($copyErr) return; // short-circuit on first error
        if (is_dir($src)) {
            $base = basename($src);
            if (in_array($base, $skipNames, true)) return;
            if (!is_dir($dst) && !@mkdir($dst, 0775, true)) { $copyErr = 'Failed to create dir: ' . $dst; return; }
            $items = scandir($src);
            foreach ($items as $it) {
                if ($it === '.' || $it === '..') continue;
                // Skip overwriting config.php
                if ($it === 'config.php') continue;
                $copyFn($src . DIRECTORY_SEPARATOR . $it, $dst . DIRECTORY_SEPARATOR . $it);
                if ($copyErr) return;
            }
        } else {
            if (!@copy($src, $dst)) { $copyErr = 'Failed to copy file: ' . $src; return; }
        }
    };
    $copyFn($root, $REPO_PATH);
    if ($copyErr) {
        if ($backupTaken && file_exists($REPO_PATH . '/config.php.backup')) {
            @copy($REPO_PATH . '/config.php.backup', $REPO_PATH . '/config.php');
            @unlink($REPO_PATH . '/config.php.backup');
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $copyErr,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Restore config.php if backup was taken
    if ($backupTaken && file_exists($REPO_PATH . '/config.php.backup')) {
        if (@copy($REPO_PATH . '/config.php.backup', $REPO_PATH . '/config.php')) {
            @unlink($REPO_PATH . '/config.php.backup');
            logMessage('config.php restored from backup');
        }
    }

    // Best-effort permissions
    @chmod($REPO_PATH, 0755);
    // Attempt to set perms on some known files (ignore failures)
    @chmod($REPO_PATH . '/config.php', 0644);
    @chmod($REPO_PATH . '/.htaccess', 0644);

    // Cleanup temp
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) { $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath()); }
    @rmdir($tmpDir);
    @unlink($tmpZip);
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

$resp = [
    'success' => true,
    'message' => 'Update completed successfully',
    'mode' => $MODE,
    'build' => $newBuild,
    'elapsed_ms' => $elapsed,
    'timestamp' => date('Y-m-d H:i:s'),
];
if ($MODE === 'git') { $resp['branch'] = $BRANCH; }
if ($MODE === 'zip') {
    $resp['ref'] = isset($ref) ? $ref : null;
}
echo json_encode($resp, JSON_UNESCAPED_UNICODE);
