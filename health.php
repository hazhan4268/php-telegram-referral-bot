<?php
header('Content-Type: application/json; charset=utf-8');

// Helper: safely parse constants from config.php without executing it
function parse_config_constants($file)
{
    $out = [];
    if (!is_file($file)) return $out;
    $code = @file_get_contents($file);
    if ($code === false) return $out;
    $map = [
        'DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_CHARSET',
        'SITE_URL','WEBHOOK_URL','WEBHOOK_SECRET','DEBUG_MODE','TIMEZONE'
    ];
    foreach ($map as $key) {
        // match: define('KEY', 'value'); or define('KEY', true/false);
        if (preg_match("/define\(\s*'" . preg_quote($key, '/') . "'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_]+))\s*\)\s*;/", $code, $m)) {
            $val = null;
            if (isset($m[1]) && $m[1] !== '') $val = $m[1];
            elseif (isset($m[2]) && $m[2] !== '') $val = $m[2];
            else $val = $m[3];
            // normalize booleans
            if (is_string($val)) {
                $lv = strtolower($val);
                if ($lv === 'true') $val = true;
                elseif ($lv === 'false') $val = false;
            }
            $out[$key] = $val;
        }
    }
    return $out;
}

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
    ],
    'config' => [
        'exists' => file_exists(__DIR__ . '/config.php'),
        'parsed' => false,
    ],
    'db' => [
        'ok' => false,
        'error' => null,
    ],
    'write' => [
        'deploy_log' => null,
    ],
    'db_schema' => [
        'checked' => false,
        'missing_tables' => [],
        'problems' => [],
    ],
    'php_ini' => [
        'error_log' => ini_get('error_log'),
        'display_errors' => ini_get('display_errors'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
    ],
];

// Try to parse config without executing
$cfg = [];
if ($result['config']['exists']) {
    $cfg = parse_config_constants(__DIR__ . '/config.php');
    if (!empty($cfg)) $result['config']['parsed'] = true;
}

// Test DB using parsed config (avoid executing config.php to bypass parse errors)
if (!empty($cfg['DB_HOST']) && !empty($cfg['DB_NAME']) && isset($cfg['DB_USER'])) {
    $host = $cfg['DB_HOST'];
    $dbn  = $cfg['DB_NAME'];
    $usr  = $cfg['DB_USER'];
    $pwd  = $cfg['DB_PASS'] ?? '';
    $chs  = $cfg['DB_CHARSET'] ?? 'utf8mb4';
    if (!extension_loaded('pdo_mysql')) {
        $result['db']['ok'] = false;
        $result['db']['error'] = 'pdo_mysql extension is not loaded';
    } else {
        try {
            $dsn = "mysql:host={$host};dbname={$dbn};charset={$chs}";
            $pdo = new PDO($dsn, $usr, $pwd, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->query('SELECT 1');
            $result['db']['ok'] = true;
            // Schema validation (granular)
            $expected = [
                'users' => ['id','first_name','username','joined_at','premium_claimed','note','last_join_check','join_status'],
                'referrals' => ['referred_id','referrer_id','created_at','credited','credited_at'],
                'scores' => ['user_id','score','updated_at'],
                'settings' => ['key','value'],
                'channels' => ['id','username','invite_link','required','active','created_at','updated_at'],
                'throttle' => ['user_id','action','at'],
                'last_msgs' => ['user_id','last_text','last_type','last_sent_at','last_msg_id'],
                'spins' => ['user_id','last_day','last_at','total_spins','total_points'],
                'member_cache' => ['channel','user_id','status','cached_at'],
                'claims' => ['id','user_id','score_at_claim','status','created_at','updated_at','admin_note','responded_at','points_deducted'],
                'bans' => ['user_id','reason','banned_at'],
                'score_logs' => ['id','user_id','delta','reason','by_admin','created_at'],
                'post_msgs' => ['user_id','slot','msg_id','last_sent_at'],
                'contact_state' => ['user_id','awaiting','started_at'],
                'admin_sessions' => ['session_id','admin_id','csrf_token','created_at'],
                'sponsors' => ['id','title','channel_username','link','image_url','description','priority','active','created_at','updated_at'],
                'sponsor_views' => ['id','sponsor_id','user_id','viewed_at'],
                'sponsor_clicks' => ['id','sponsor_id','user_id','clicked_at'],
                'admin_errors' => ['id','type','message','context','created_at'],
                'logs' => ['id','time','type','message','meta'],
                'admin_logs' => ['id','action','actor','meta','created_at'],
            ];
            try {
                $result['db_schema']['checked'] = true;
                // Check existence
                foreach ($expected as $table => $cols) {
                    // SHOW/DESCRIBE statements don't support bound params; use quoted identifiers safely
                    $like = $pdo->quote($table);
                    $stmt = $pdo->query("SHOW TABLES LIKE {$like}");
                    $exists = $stmt && $stmt->fetch(PDO::FETCH_NUM);
                    if (!$exists) {
                        $result['db_schema']['missing_tables'][] = $table;
                        continue;
                    }
                    // Check columns
                    $descStmt = $pdo->query('DESCRIBE `'.$table.'`');
                    $desc = $descStmt ? $descStmt->fetchAll() : [];
                    $have = array_map(fn($r) => $r['Field'] ?? '', $desc);
                    $missingCols = array_values(array_diff($cols, $have));
                    if (!empty($missingCols)) {
                        $result['db_schema']['problems'][] = [
                            'table' => $table,
                            'missing_columns' => $missingCols,
                        ];
                    }
                }
            } catch (Throwable $schemaEx) {
                $result['db_schema']['checked'] = false;
                $result['db_schema']['error'] = $schemaEx->getMessage();
            }
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            // mask secrets
            $secrets = array_filter([$usr, $pwd]);
            if (!empty($secrets)) $msg = str_replace($secrets, '***', $msg);
            $result['db']['ok'] = false;
            $result['db']['error'] = $msg;
        }
    }
} else {
    $result['db']['ok'] = false;
    $result['db']['error'] = 'config.php not parsed or DB constants missing';
}

// Test write to deploy.log
$logFile = __DIR__ . '/deploy.log';
$test = @file_put_contents($logFile, '[' . date('H:i:s') . "] health check\n", FILE_APPEND);
$result['write']['deploy_log'] = $test !== false;

// Hints
$hints = [];
if (!$result['php']['ok']) $hints[] = 'PHP 8.0+ مورد نیاز است';
if (!$result['extensions']['pdo_mysql']) $hints[] = 'افزونه pdo_mysql باید فعال باشد';
if (!$result['config']['exists']) $hints[] = 'فایل config.php ایجاد نشده است (install.php را اجرا کنید)';
if ($result['config']['exists'] && !$result['config']['parsed']) $hints[] = 'config.php قابل خواندن نیست (خطای نحوی؟ دسترسی فایل؟)';
if (!$result['db']['ok']) $hints[] = 'اتصال به دیتابیس برقرار نشد؛ اطلاعات دیتابیس را بررسی کنید';
if ($result['db']['ok'] && $result['db_schema']['checked']) {
    if (!empty($result['db_schema']['missing_tables'])) {
        $hints[] = 'برخی جداول وجود ندارند: ' . implode(', ', $result['db_schema']['missing_tables']) . ' — از دکمه راه‌اندازی دیتابیس در پنل ادمین استفاده کنید.';
    }
    if (!empty($result['db_schema']['problems'])) {
        $hints[] = 'ساختار برخی جداول ناقص است (ستون‌های مفقود). schema.sql را اجرا کنید.';
    }
}
$result['hints'] = $hints;

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
