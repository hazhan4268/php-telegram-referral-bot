<?php
require_once __DIR__ . '/Common.php';
class ToolsHealth {
    public static function handle() {
        header('Content-Type: application/json; charset=utf-8');
        $cfg = ToolsCommon::parseConfig();
        $result = [
            'time' => date('Y-m-d H:i:s'),
            'php' => [ 'version' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.0.0', '>=') ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'ziparchive' => class_exists('ZipArchive'),
            ],
            'config' => [ 'exists' => file_exists(__DIR__ . '/../../config.php'), 'parsed' => !empty($cfg) ],
            'db' => ['ok'=>false,'error'=>null],
            'git' => ['exec_available'=>function_exists('exec'), 'repo'=>is_dir(__DIR__ . '/../../.git')],
            'write' => ['deploy_log'=>null],
            'db_schema' => ['checked'=>false,'missing_tables'=>[],'problems'=>[]],
            'php_ini' => [
                'error_log' => ini_get('error_log'),
                'display_errors' => ini_get('display_errors'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];
        // DB test (best-effort)
        if (!empty($cfg)) {
            $keys = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_CHARSET'];
            $cfgAll = $cfg;
            foreach ($keys as $k) { if (!isset($cfgAll[$k])) { $code = @file_get_contents(__DIR__ . '/../../config.php'); if ($code!==false) { foreach ($keys as $kk) { if (preg_match("/define\(\s*'".preg_quote($kk,'/')."'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_:\\/.+-]+))\s*\)\s*;/", $code, $m)) { $val=$m[1]!==''?$m[1]:( $m[2]!==''?$m[2]:$m[3]); $cfgAll[$kk]=$val; } } } break; } }
            if (!empty($cfgAll['DB_HOST']) && !empty($cfgAll['DB_NAME'])) {
                try {
                    $dsn = "mysql:host={$cfgAll['DB_HOST']};dbname={$cfgAll['DB_NAME']};charset=" . ($cfgAll['DB_CHARSET'] ?? 'utf8mb4');
                    $pdo = new PDO($dsn, $cfgAll['DB_USER'], $cfgAll['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
                    $pdo->query('SELECT 1');
                    $result['db']['ok'] = true;
                    $expected = ['users','referrals','scores','settings','channels','throttle','last_msgs','spins','member_cache','claims','bans','score_logs','post_msgs','contact_state','admin_sessions','sponsors','sponsor_views','sponsor_clicks','admin_errors','logs','admin_logs'];
                    $result['db_schema']['checked'] = true;
                    foreach ($expected as $table) { $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table)); if (!$stmt || !$stmt->fetch()) { $result['db_schema']['missing_tables'][] = $table; } }
                } catch (Throwable $e) { $result['db']['error'] = $e->getMessage(); }
            } else { $result['db']['error'] = 'DB constants missing in config'; }
        }
        // Write test
        $logFile = __DIR__ . '/../../deploy.log';
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
        if (!empty($result['db_schema']['missing_tables'])) { $hints[] = 'Missing tables: ' . implode(', ', $result['db_schema']['missing_tables']) . ' — initialize schema from admin panel'; }
        $result['hints'] = $hints;
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
