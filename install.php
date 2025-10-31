<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 نصب ربات ارجاع پرمیوم</title>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background Particles */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -150px;
            left: -150px;
            animation: float 20s infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            bottom: -200px;
            right: -200px;
            animation: float 25s infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .container {
            background: #ffffff;
            padding: 0;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
            position: relative;
            z-index: 10;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 40px 100px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo-icon span {
            font-size: 48px;
            color: white;
        }
        
        h1 {
            text-align: center;
            color: white;
            margin-bottom: 8px;
            font-size: 2.2em;
            font-weight: 800;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1em;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }
        
        .content {
            padding: 40px;
            margin-top: -80px;
            position: relative;
            z-index: 3;
        }
        
        /* Progress Steps */
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 15%;
            right: 15%;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .progress-step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 700;
            color: #999;
            transition: all 0.3s;
        }
        
        .progress-step.active .progress-step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            transform: scale(1.1);
        }
        
        .progress-step.completed .progress-step-circle {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        .progress-step-label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }
        
        .progress-step.active .progress-step-label {
            color: #667eea;
            font-weight: 700;
        }
        
        .step {
            background: linear-gradient(to bottom, #f8f9fb 0%, #ffffff 100%);
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 2px solid #f0f1f5;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .step::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }
        
        .step:hover {
            border-color: #667eea;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .step-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .step-icon span {
            color: white;
            font-size: 24px;
        }
        
        .step h3 {
            color: #1f2937;
            margin: 0;
            font-size: 1.3em;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        label .material-icons {
            font-size: 18px;
            margin-left: 6px;
            color: #667eea;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .material-icons {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 20px;
            pointer-events: none;
            transition: color 0.3s;
        }
        
        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            background: white;
        }
        
        input:hover {
            border-color: #d1d5db;
        }
        
        input:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: #fafbff;
        }
        
        input:focus + .material-icons {
            color: #667eea;
        }
        
        .help-text {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
            display: flex;
            align-items: center;
            line-height: 1.5;
        }
        
        .help-text .material-icons {
            font-size: 16px;
            margin-left: 4px;
            color: #9ca3af;
        }
        
        .btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn .material-icons {
            margin-left: 8px;
            font-size: 20px;
        }
        
        .alert {
            padding: 18px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: flex-start;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 2px solid;
        }
        
        .alert .material-icons {
            margin-left: 12px;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #6ee7b7;
            color: #065f46;
        }
        
        .alert-success .material-icons {
            color: #10b981;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #f87171;
            color: #991b1b;
        }
        
        .alert-error .material-icons {
            color: #ef4444;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #93c5fd;
            color: #1e40af;
        }
        
        .alert-info .material-icons {
            color: #3b82f6;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            background: linear-gradient(to bottom, #f8f9fb 0%, #ffffff 100%);
            border-radius: 16px;
            margin-top: 24px;
        }
        
        .loading.active {
            display: block;
            animation: slideIn 0.4s;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            position: relative;
        }
        
        .spinner::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid #e5e7eb;
            border-radius: 50%;
        }
        
        .spinner::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: #667eea;
            border-right-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #6b7280;
            font-weight: 600;
            font-size: 15px;
        }
        
        .success-animation {
            text-align: center;
            padding: 40px 0;
        }
        
        .success-checkmark {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
            animation: scaleIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .success-checkmark .material-icons {
            font-size: 60px;
            color: white;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(10deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        .next-steps {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            padding: 28px;
            border-radius: 16px;
            margin-top: 24px;
            border: 2px solid #a78bfa;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
        }
        
        .next-steps-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .next-steps-header .material-icons {
            font-size: 32px;
            color: #8b5cf6;
            margin-left: 12px;
        }
        
        .next-steps h4 {
            color: #6d28d9;
            margin: 0;
            font-size: 1.4em;
            font-weight: 700;
        }
        
        .next-steps ol {
            margin-right: 24px;
            margin-bottom: 16px;
        }
        
        .next-steps li {
            margin-bottom: 12px;
            color: #4c1d95;
            font-weight: 500;
            line-height: 1.6;
        }
        
        .next-steps strong {
            color: #6d28d9;
        }
        
        .code {
            background: #1f2937;
            padding: 16px 20px;
            border-radius: 12px;
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 13px;
            margin: 12px 0;
            direction: ltr;
            text-align: left;
            color: #e5e7eb;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.3);
            overflow-x: auto;
            position: relative;
        }
        
        .code::before {
            content: 'BASH';
            position: absolute;
            top: 8px;
            left: 12px;
            font-size: 10px;
            color: #9ca3af;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 30px 20px 80px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.8em;
            }
            
            .step {
                padding: 20px;
            }
            
            .logo-icon {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>
<body>
<?php
// Load global error handler early (won't send to Telegram until config exists)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}
?>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <span class="material-icons">card_giftcard</span>
                </div>
            </div>
            <h1>نصب ربات ارجاع پرمیوم</h1>
            <p class="subtitle">🚀 نسخه پیشرفته PHP با پنل مدیریت گرافیکی</p>
        </div>
        
        <div class="content">
        
        <?php
        session_start();
        
        // بررسی نصب قبلی
        if (file_exists(__DIR__ . '/config.php')) {
            echo '<div class="alert alert-info">';
            echo '<span class="material-icons">info</span>';
            echo '<div class="alert-content">';
            echo '<strong>توجه:</strong> ربات قبلاً نصب شده است. برای نصب مجدد، فایل config.php را حذف کنید.';
            echo '</div>';
            echo '</div>';
            echo '<a href="admin/" class="btn"><span>ورود به پنل ادمین</span><span class="material-icons">arrow_back</span></a>';
            echo '</div></div>'; // close content and container
            exit;
        }
        
        $errors = [];
        $success = false;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // دریافت داده‌ها
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            $botToken = trim($_POST['bot_token'] ?? '');
            $adminId = trim($_POST['admin_id'] ?? '');
            $adminKey = $_POST['admin_key'] ?? '';
            $channelUsername = trim($_POST['channel_username'] ?? '');
            $siteUrl = trim($_POST['site_url'] ?? '');
            
            // اعتبارسنجی
            if (empty($dbName)) $errors[] = 'نام دیتابیس الزامی است';
            if (empty($dbUser)) $errors[] = 'نام کاربری دیتابیس الزامی است';
            if (empty($botToken)) $errors[] = 'توکن ربات الزامی است';
            if (empty($adminId)) $errors[] = 'شناسه ادمین الزامی است';
            if (empty($adminKey)) $errors[] = 'رمز عبور پنل الزامی است';
            if (empty($siteUrl)) $errors[] = 'آدرس سایت الزامی است';
            
            if (empty($errors)) {
                try {
                    // تست اتصال به دیتابیس
                    $dsn = "mysql:host={$dbHost};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // ساخت دیتابیس
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbName}`");
                    
                    // اجرای schema
                    $schema = file_get_contents(__DIR__ . '/schema.sql');
                    $queries = array_filter(array_map('trim', explode(';', $schema)));
                    
                    foreach ($queries as $query) {
                        if (!empty($query)) {
                            $pdo->exec($query);
                        }
                    }
                    
                    // ساخت فایل config
                    $webhookSecret = bin2hex(random_bytes(16));
                    $configContent = "<?php\n";
                    $configContent .= "// Generated by installer on " . date('Y-m-d H:i:s') . "\n\n";
                    $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
                    $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
                    $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
                    $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
                    $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
                    $configContent .= "define('BOT_TOKEN', " . var_export($botToken, true) . ");\n";
                    $configContent .= "define('ADMIN_ID', " . var_export($adminId, true) . ");\n";
                    $configContent .= "define('ADMIN_KEY', " . var_export($adminKey, true) . ");\n";
                    $configContent .= "define('CHANNEL_USERNAME', " . var_export($channelUsername, true) . ");\n";
                    $configContent .= "define('WEBHOOK_SECRET', " . var_export($webhookSecret, true) . ");\n\n";
                    $configContent .= "define('SITE_URL', " . var_export(rtrim($siteUrl, '/'), true) . ");\n";
                    $configContent .= "define('WEBHOOK_URL', SITE_URL . '/webhook.php');\n\n";
                    $configContent .= "define('SESSION_LIFETIME', 86400 * 30);\n";
                    $configContent .= "define('CSRF_TOKEN_LENGTH', 32);\n";
                    $configContent .= "define('TIMEZONE', 'Asia/Tehran');\n";
                    // نسخه برنامه به‌صورت پویا از فایل‌ها خوانده می‌شود
                    $configContent .= "if (!function_exists('app_version')) { function app_version() { $base = @file_get_contents(__DIR__ . '/VERSION'); $base = $base ? trim($base) : '4.0.1'; $build = @file_get_contents(__DIR__ . '/BUILD'); $build = $build ? (int)trim($build) : 0; return $build > 0 ? ($base . '+build.' . $build) : $base; } }\n";
                    $configContent .= "define('APP_VERSION', app_version());\n";
                    $configContent .= "define('ENVIRONMENT', 'production');\n";
                    $configContent .= "define('DEBUG_MODE', false);\n";
                    $configContent .= "define('CACHE_ENABLED', true);\n";
                    $configContent .= "define('CACHE_TTL', 300);\n";
                    $configContent .= "define('MAINTENANCE_MODE', false);\n\n";
                    $configContent .= "date_default_timezone_set(TIMEZONE);\n";
                    
                    file_put_contents(__DIR__ . '/config.php', $configContent);
                    chmod(__DIR__ . '/config.php', 0644);
                    
                    // دریافت نام کاربری ربات و ذخیره در دیتابیس
                    $ch = curl_init("https://api.telegram.org/bot{$botToken}/getMe");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    $botInfo = json_decode($response, true);
                    if ($botInfo && $botInfo['ok']) {
                        $botUsername = $botInfo['result']['username'];
                        $pdo->exec("UPDATE settings SET value = '{$botUsername}' WHERE `key` = 'bot_username'");
                    }
                    
                    $success = true;
                    $_SESSION['webhook_url'] = $siteUrl . '/webhook.php';
                    $_SESSION['webhook_secret'] = $webhookSecret;
                    $_SESSION['bot_token'] = $botToken;
                    
                } catch (Exception $e) {
                    $errors[] = 'خطا در نصب: ' . $e->getMessage();
                }
            }
        }
        
        if ($success) {
            ?>
            <div class="success-animation">
                <div class="success-checkmark">
                    <span class="material-icons">check</span>
                </div>
            </div>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <div class="alert-content">
                    <strong>تبریک!</strong> ربات با موفقیت نصب شد و آماده استفاده است.
                </div>
            </div>
            
            <div class="next-steps">
                <div class="next-steps-header">
                    <span class="material-icons">rocket_launch</span>
                    <h4>مراحل بعدی</h4>
                </div>
                <ol>
                    <li><strong>تنظیم Webhook تلگرام:</strong></li>
                </ol>
                <div class="code">
curl -X POST "https://api.telegram.org/bot<?php echo $_SESSION['bot_token']; ?>/setWebhook" \
-H "Content-Type: application/json" \
-d '{
  "url": "<?php echo $_SESSION['webhook_url']; ?>",
  "secret_token": "<?php echo $_SESSION['webhook_secret']; ?>"
}'
                </div>
                
                <ol start="2">
                    <li><strong>یا از دکمه زیر استفاده کنید:</strong></li>
                </ol>
                <button class="btn" onclick="setWebhook()">
                    <span>تنظیم خودکار Webhook</span>
                    <span class="material-icons">settings</span>
                </button>
                
                <ol start="3">
                    <li><strong>ورود به پنل ادمین:</strong></li>
                </ol>
                <a href="admin/" class="btn" style="margin-top: 10px;">
                    <span>ورود به پنل مدیریت</span>
                    <span class="material-icons">dashboard</span>
                </a>
                
                <ol start="4">
                    <li><strong>تنظیم Cron Job:</strong> برای اجرای وظایف دوره‌ای، در cPanel یک cron job بسازید:</li>
                </ol>
                <div class="code">
*/5 * * * * php <?php echo __DIR__; ?>/cron.php
                </div>
            </div>
            
            <script>
            async function setWebhook() {
                const btn = event.target;
                btn.disabled = true;
                btn.textContent = 'در حال تنظیم...';
                
                try {
                    const response = await fetch('tools.php?a=reset_webhook&token=' + encodeURIComponent('<?php echo htmlspecialchars($WEBHOOK_SECRET ?? '', ENT_QUOTES); ?>'));
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('✅ Webhook با موفقیت تنظیم شد!');
                        btn.textContent = '✓ انجام شد';
                        btn.style.background = '#28a745';
                    } else {
                        alert('❌ خطا: ' + (data.error || 'نامشخص'));
                        btn.disabled = false;
                        btn.textContent = 'تنظیم خودکار Webhook';
                    }
                } catch (e) {
                    alert('❌ خطا در اتصال: ' + e.message);
                    btn.disabled = false;
                    btn.textContent = 'تنظیم خودکار Webhook';
                }
            }
            </script>
            <?php
        } else {
            if (!empty($errors)) {
                echo '<div class="alert alert-error">';
                echo '<span class="material-icons">error</span>';
                echo '<div class="alert-content">';
                foreach ($errors as $error) {
                    echo '<div>• ' . htmlspecialchars($error) . '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
            ?>
            
            <form method="POST" id="installForm">
                <div class="step">
                    <div class="step-header">
                        <div class="step-icon">
                            <span class="material-icons">storage</span>
                        </div>
                        <h3>تنظیمات دیتابیس</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">dns</span>
                            هاست دیتابیس
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="db_host" value="localhost" required>
                            <span class="material-icons">computer</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            معمولاً localhost است
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">label</span>
                            نام دیتابیس
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="db_name" required>
                            <span class="material-icons">database</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            نام دیتابیس MySQL از cPanel
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">person</span>
                            نام کاربری دیتابیس
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="db_user" required>
                            <span class="material-icons">account_circle</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">lock</span>
                            رمز عبور دیتابیس
                        </label>
                        <div class="input-wrapper">
                            <input type="password" name="db_pass">
                            <span class="material-icons">vpn_key</span>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-header">
                        <div class="step-icon">
                            <span class="material-icons">smart_toy</span>
                        </div>
                        <h3>تنظیمات ربات تلگرام</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">vpn_key</span>
                            توکن ربات
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="bot_token" required placeholder="123456:ABC-DEF1234ghIkl...">
                            <span class="material-icons">token</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            از @BotFather دریافت کنید
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">badge</span>
                            شناسه عددی ادمین
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="admin_id" required placeholder="123456789">
                            <span class="material-icons">fingerprint</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            User ID تلگرام خود را از @userinfobot دریافت کنید
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">group</span>
                            کانال اجباری (اختیاری)
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="channel_username" placeholder="@yourchannel">
                            <span class="material-icons">campaign</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            برای جوین اجباری - مثال: @yourchannel
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-header">
                        <div class="step-icon">
                            <span class="material-icons">admin_panel_settings</span>
                        </div>
                        <h3>تنظیمات پنل ادمین</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">password</span>
                            رمز عبور پنل مدیریت
                        </label>
                        <div class="input-wrapper">
                            <input type="password" name="admin_key" required>
                            <span class="material-icons">security</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            رمز قوی انتخاب کنید (حداقل 8 کاراکتر)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <span class="material-icons">language</span>
                            آدرس کامل سایت
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="site_url" value="<?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>" required>
                            <span class="material-icons">public</span>
                        </div>
                        <div class="help-text">
                            <span class="material-icons">info</span>
                            مثال: https://yourdomain.com
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <span>شروع نصب</span>
                    <span class="material-icons">play_arrow</span>
                </button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p class="loading-text">در حال نصب، لطفاً صبر کنید...</p>
            </div>
            
            <script>
            document.getElementById('installForm').addEventListener('submit', function(e) {
                // Show loading animation
                document.getElementById('loading').classList.add('active');
                document.querySelector('.btn[type="submit"]').disabled = true;
                
                // Scroll to loading
                document.getElementById('loading').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            
            // Add input validation animations
            document.querySelectorAll('input[required]').forEach(input => {
                input.addEventListener('invalid', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#ef4444';
                    this.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 500);
                });
                
                input.addEventListener('input', function() {
                    if (this.validity.valid) {
                        this.style.borderColor = '#10b981';
                    }
                });
            });
            </script>
            
            <style>
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
            </style>
            <?php
        }
        ?>
        </div><!-- close content -->
    </div><!-- close container -->
</body>
</html>
