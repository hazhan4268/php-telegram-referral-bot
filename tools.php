<?php
/**
 * Consolidated tools endpoint
 * Actions via ?a=
 * - ping (text)
 * - get_webhook_info (JSON) [auth]
 * - reset_webhook (JSON) [auth] supports: no_secret=1, target=debug|webhook, path=relative, url=explicit
 * - webhook_debug (POST passthrough, logs to webhook_debug.log, always 200 ok)
 */
session_start();

$action = $_GET['a'] ?? '';

// Load error handler early (optional)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

// Safe parse small set of constants from config.php without executing it
function tools_parse_config_constants($file) {
    $out = [];
    if (!is_file($file)) return $out;
    $code = @file_get_contents($file);
    if ($code === false) return $out;
    $keys = ['BOT_TOKEN','WEBHOOK_SECRET','SITE_URL','DEBUG_MODE'];
    foreach ($keys as $key) {
        if (preg_match("/define\(\s*'" . preg_quote($key, '/') . "'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_:\\/.+-]+))\s*\)\s*;/", $code, $m)) {
            $val = $m[1] !== '' ? $m[1] : ($m[2] !== '' ? $m[2] : $m[3]);
            $out[$key] = $val;
        }
    }
    return $out;
}

$cfg = tools_parse_config_constants(__DIR__ . '/config.php');

function tools_auth_required($cfg) {
    $authorized = false;
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $authorized = true;
    }
    $tokenParam = $_GET['token'] ?? '';
    $cfgSecret  = $cfg['WEBHOOK_SECRET'] ?? '';
    if (!$authorized && $cfgSecret !== '' && $tokenParam !== '' && hash_equals($cfgSecret, $tokenParam)) {
        $authorized = true;
    }
    if (!$authorized) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Forbidden: admin login or valid token required']);
        return false;
    }
    return true;
}

function tools_tg($token, $method, $data = []) {
    $api = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $payload = !empty($data) ? json_encode($data) : null;
    if (function_exists('curl_init')) {
        $ch = curl_init($api);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $opts = ['http' => ['method' => $payload !== null ? 'POST' : 'GET', 'timeout' => 10]];
        if ($payload !== null) {
            $opts['http']['header'] = "Content-Type: application/json\r\n";
            $opts['http']['content'] = $payload;
        }
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

switch ($action) {
    case 'ping':
        header('Content-Type: text/plain; charset=utf-8');
        echo "pong\n";
        break;

    case 'get_webhook_info':
        header('Content-Type: application/json; charset=utf-8');
        if (empty($cfg) || empty($cfg['BOT_TOKEN'])) {
            echo json_encode(['ok' => false, 'error' => 'config.php missing or BOT_TOKEN not set']);
            break;
        }
        if (!tools_auth_required($cfg)) break;
        list($code, $json) = tools_tg($cfg['BOT_TOKEN'], 'getWebhookInfo');
        echo json_encode(['http' => $code, 'response' => $json]);
        break;

    case 'reset_webhook':
        header('Content-Type: application/json; charset=utf-8');
        if (empty($cfg) || empty($cfg['BOT_TOKEN'])) {
            echo json_encode(['success' => false, 'error' => 'config.php missing or BOT_TOKEN not set']);
            break;
        }
        if (!tools_auth_required($cfg)) break;
        $token = $cfg['BOT_TOKEN'];
        $site  = !empty($cfg['SITE_URL']) ? rtrim($cfg['SITE_URL'], '/') : '';
        $url   = $site ? ($site . '/webhook.php') : '';
        $secret = $cfg['WEBHOOK_SECRET'] ?? '';

        // overrides
        $target = $_GET['target'] ?? '';
        if ($target === 'debug' && $site) {
            $url = $site . '/tools.php?a=webhook_debug';
        }
        if (!empty($_GET['path']) && $site) {
            $base = basename($_GET['path']);
            $url = $site . '/' . $base;
        }
        if (!empty($_GET['url'])) {
            $url = $_GET['url'];
        }
        $noSecret = isset($_GET['no_secret']) && in_array(strtolower((string)$_GET['no_secret']), ['1','true','yes'], true);

        try {
            $steps = [];
            list($code1, $res1) = tools_tg($token, 'deleteWebhook', ['drop_pending_updates' => true]);
            $steps[] = ['deleteWebhook', $code1, $res1];
            if ($code1 !== 200 || !$res1 || !$res1['ok']) {
                if (function_exists('error_notify_admin')) {
                    error_notify_admin('reset_webhook_delete_failed', json_encode($res1));
                }
                echo json_encode(['success' => false, 'step' => 'deleteWebhook', 'response' => $res1]);
                break;
            }
            $payload = ['url' => $url];
            if (!$noSecret && !empty($secret)) { $payload['secret_token'] = $secret; }
            list($code2, $res2) = tools_tg($token, 'setWebhook', $payload);
            $steps[] = ['setWebhook', $code2, $res2];
            if ($code2 !== 200 || !$res2 || !$res2['ok']) {
                if (function_exists('error_notify_admin')) {
                    error_notify_admin('reset_webhook_set_failed', json_encode($res2));
                }
                echo json_encode(['success' => false, 'step' => 'setWebhook', 'response' => $res2]);
                break;
            }
            echo json_encode(['success' => true, 'url' => $url, 'used_secret' => (!$noSecret && !empty($secret)), 'steps' => $steps]);
        } catch (Throwable $e) {
            if (function_exists('error_notify_admin')) {
                error_notify_admin('reset_webhook_exception', $e->getMessage());
            }
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'health':
        header('Content-Type: application/json; charset=utf-8');
        // System health diagnostics
        $result = [
            'time' => date('Y-m-d H:i:s'),
            'php' => [
                'version' => PHP_VERSION,
                'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'ziparchive' => class_exists('ZipArchive'),
            ],
            'config' => [
                'exists' => file_exists(__DIR__ . '/config.php'),
                'parsed' => !empty($cfg),
            ],
            'db' => ['ok' => false, 'error' => null],
            'git' => [
                'exec_available' => function_exists('exec'),
                'repo' => is_dir(__DIR__ . '/.git'),
            ],
            'write' => ['deploy_log' => null],
            'db_schema' => ['checked' => false, 'missing_tables' => [], 'problems' => []],
            'php_ini' => [
                'error_log' => ini_get('error_log'),
                'display_errors' => ini_get('display_errors'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];

        // Test DB using parsed config
        if (!empty($cfg)) {
            $cfgFull = $cfg;
            // Re-parse for DB keys if needed
            $keys = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_CHARSET'];
            $hasDB = false;
            foreach ($keys as $k) {
                if (!isset($cfgFull[$k])) {
                    $cfgAll = tools_parse_config_constants(__DIR__ . '/config.php');
                    $cfgFull = array_merge($cfgFull, $cfgAll);
                    break;
                }
            }
            if (!empty($cfgFull['DB_HOST']) && !empty($cfgFull['DB_NAME'])) {
                try {
                    $dsn = "mysql:host={$cfgFull['DB_HOST']};dbname={$cfgFull['DB_NAME']};charset=" . ($cfgFull['DB_CHARSET'] ?? 'utf8mb4');
                    $pdo = new PDO($dsn, $cfgFull['DB_USER'], $cfgFull['DB_PASS'] ?? '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    $pdo->query('SELECT 1');
                    $result['db']['ok'] = true;

                    // Schema validation
                    $expected = ['users','referrals','scores','settings','channels','throttle','last_msgs','spins','member_cache','claims','bans','score_logs','post_msgs','contact_state','admin_sessions','sponsors','sponsor_views','sponsor_clicks','admin_errors','logs','admin_logs'];
                    $result['db_schema']['checked'] = true;
                    foreach ($expected as $table) {
                        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
                        if (!$stmt || !$stmt->fetch()) {
                            $result['db_schema']['missing_tables'][] = $table;
                        }
                    }
                } catch (Throwable $e) {
                    $result['db']['error'] = $e->getMessage();
                }
            } else {
                $result['db']['error'] = 'DB constants missing in config';
            }
        }

        // Write test
        $logFile = __DIR__ . '/deploy.log';
        $test = @file_put_contents($logFile, '[' . date('H:i:s') . "] health\n", FILE_APPEND);
        $result['write']['deploy_log'] = $test !== false;

        // Hints
        $hints = [];
        if (!$result['php']['ok']) $hints[] = 'PHP 8.0+ required';
        if (!$result['extensions']['pdo_mysql']) $hints[] = 'pdo_mysql must be enabled';
        if (!$result['extensions']['ziparchive']) $hints[] = 'ZipArchive extension is recommended for ZIP updates';
        if (!$result['config']['exists']) $hints[] = 'config.php not found (run install.php)';
        if (!$result['db']['ok']) $hints[] = 'DB connection failed: check credentials';
        if (!$result['git']['exec_available']) $hints[] = 'exec() disabled; git-based update will not work';
        if (!$result['git']['repo']) $hints[] = 'Not a git repository; prefer ZIP updates or initialize git';
        if (!empty($result['db_schema']['missing_tables'])) {
            $hints[] = 'Missing tables: ' . implode(', ', $result['db_schema']['missing_tables']) . ' — initialize schema from admin panel';
        }
        $result['hints'] = $hints;

        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    case 'webhook_debug':
        // Always 200; log details
        $logFile = __DIR__ . '/webhook_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $input = file_get_contents('php://input');
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $logEntry = "=== {$timestamp} ===\n";
        $logEntry .= "Method: " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n";
        $logEntry .= "Headers: " . json_encode($headers) . "\n";
        $logEntry .= "Input: " . $input . "\n";
        $logEntry .= "Input Length: " . strlen($input) . "\n\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Secret check (non-blocking)
        if (!empty($cfg['WEBHOOK_SECRET'])) {
            $received = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($received !== $cfg['WEBHOOK_SECRET']) {
                @file_put_contents($logFile, "ERROR: invalid secret\n\n", FILE_APPEND | LOCK_EX);
                echo 'ok';
                break;
            }
        }
        $update = json_decode($input, true);
        if (!$update) {
            @file_put_contents($logFile, "ERROR: invalid json\n\n", FILE_APPEND | LOCK_EX);
            echo 'ok';
            break;
        }

        // If classes present, try minimal reply
        if (file_exists(__DIR__ . '/includes/Database.php') && file_exists(__DIR__ . '/includes/BotHelper.php')) {
            require_once __DIR__ . '/includes/Database.php';
            require_once __DIR__ . '/includes/BotHelper.php';
            try {
                $db = Database::getInstance();
                if (isset($update['message'])) {
                    $chatId = $update['message']['chat']['id'];
                    $text = $update['message']['text'] ?? '';
                    if ($text === '/test') {
                        BotHelper::sendMessage($chatId, "✅ Debug ok: " . date('Y-m-d H:i:s'));
                    }
                }
            } catch (Throwable $e) {
                @file_put_contents($logFile, "ERROR: exception: " . $e->getMessage() . "\n\n", FILE_APPEND | LOCK_EX);
            }
        } else {
            @file_put_contents($logFile, "ERROR: missing includes\n\n", FILE_APPEND | LOCK_EX);
        }
        echo 'ok';
        break;

    default:
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'actions' => ['ping','get_webhook_info','reset_webhook','webhook_debug','health'],
            'usage' => [
                '/tools.php?a=ping',
                '/tools.php?a=health',
                '/tools.php?a=get_webhook_info&token=WEBHOOK_SECRET',
                '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET',
                '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET&no_secret=1',
                '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET&target=debug'
            ]
        ]);
}
