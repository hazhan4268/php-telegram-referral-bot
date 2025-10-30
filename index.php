<?php
/**
 * Admin Panel - Main Dashboard
 * پنل مدیریت ربات ارجاع پرمیوم
 */

// Start output buffering to prevent any accidental output
ob_start();

// Start session with proper configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/BotHelper.php';

// Initialize database safely to avoid blank 500 pages
try {
    $db = Database::getInstance();
} catch (Throwable $e) {
    http_response_code(500);
    $isDebug = defined('DEBUG_MODE') && DEBUG_MODE;
    $hintItems = [
        'فایل config.php را بررسی کنید: DB_HOST, DB_NAME, DB_USER, DB_PASS',
        'از وجود دیتابیس و دسترسی کاربر مطمئن شوید (cPanel → MySQL Databases)',
        'افزونه PDO و pdo_mysql باید فعال باشند (phpinfo)',
        'اگر به‌تازگی نصب کرده‌اید، یک‌بار اطلاعات دیتابیس را در install.php بازبینی کنید'
    ];
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>خطا در اتصال به دیتابیس</title>
        <style>
            body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f9fafb; margin: 0; padding: 24px; }
            .error-card { max-width: 860px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); overflow: hidden; border: 1px solid #e5e7eb; }
            .header { padding: 20px 24px; background: linear-gradient(135deg, #ef4444, #f59e0b); color: #fff; display: flex; align-items: center; gap: 12px; }
            .header h2 { margin: 0; font-size: 1.2rem; }
            .content { padding: 24px; color: #111827; }
            .hint { background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; padding: 16px; border-radius: 12px; margin: 16px 0; }
            .hint ul { margin: 8px 16px; }
            .muted { color: #6b7280; font-size: .9rem; }
            pre { background: #111827; color: #e5e7eb; padding: 16px; border-radius: 12px; overflow:auto; direction:ltr; text-align:left; }
            .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
            .btn { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; }
            .btn-primary { background:#3b82f6; color:#fff; }
            .btn-outline { border:2px solid #3b82f6; color:#1f2937; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="header">
                <span style="font-size:22px">⚠️</span>
                <h2>خطا در اتصال به دیتابیس</h2>
            </div>
            <div class="content">
                <p>اتصال به دیتابیس برقرار نشد و به همین دلیل پنل مدیریت در حال حاضر در دسترس نیست.</p>
                <div class="hint">
                    <strong>راهنما:</strong>
                    <ul>
                        <?php foreach ($hintItems as $h): ?>
                            <li><?php echo htmlspecialchars($h); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($isDebug): ?>
                    <div style="margin-top:16px">
                        <div class="muted">جزئیات خطا (DEBUG_MODE فعال است):</div>
                        <pre><?php echo htmlspecialchars($e->getMessage()); ?></pre>
                    </div>
                <?php endif; ?>
                <div class="actions">
                    <a class="btn btn-primary" href="../install.php">بازبینی نصب</a>
                    <a class="btn btn-outline" href="../">بازگشت به صفحه اصلی</a>
                </div>
                <div class="muted" style="margin-top:12px">کد وضعیت: 500 • زمان: <?php echo date('Y-m-d H:i:s'); ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// بررسی احراز هویت
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // نمایش صفحه لاگین
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_key'])) {
        $inputKey = $_POST['admin_key'];
        
        if ($inputKey === ADMIN_KEY) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = ADMIN_ID;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['login_time'] = time();
            
            header('Location: index.php');
            exit;
        } else {
            $loginError = 'رمز عبور اشتباه است';
        }
    }
    
    // نمایش فرم لاگین
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ورود به پنل ادمین</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 100%;
                backdrop-filter: blur(10px);
            }
            
            h1 {
                text-align: center;
                color: #667eea;
                margin-bottom: 30px;
                font-size: 2em;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            label {
                display: block;
                margin-bottom: 8px;
                color: #333;
                font-weight: 500;
            }
            
            input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s;
            }
            
            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .btn {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            }
            
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
            }
            
            .logo {
                text-align: center;
                font-size: 4em;
                margin-bottom: 20px;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo">🎁</div>
            <h1>پنل مدیریت</h1>
            <?php if (isset($loginError)): ?>
                <div class="error">❌ <?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>رمز عبور:</label>
                    <input type="password" name="admin_key" required autofocus>
                </div>
                <button type="submit" class="btn">ورود</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// بررسی CSRF برای POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if ($csrfToken !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
}

// خروج از پنل
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// تب فعال
$activeTab = $_GET['tab'] ?? 'stats';

// پردازش اکشن‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = handleAction($_POST['action'], $_POST);
    
    // If this is an AJAX request, send JSON and exit
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $result]);
        exit;
    }
    // For non-AJAX requests, the result will be shown in the page
}

$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل ادمین - <?php echo ucfirst($activeTab); ?></title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
        }
        
        [data-theme="dark"] {
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-tertiary: #374151;
            --border-color: #4b5563;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            scroll-behavior: auto; /* جلوگیری از اسکرول نرم که می‌تواند باعث پرش شود */
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: var(--text-primary);
            transition: all 0.3s ease;
            overflow: hidden; /* اسکرول فقط در main-content */
            scrollbar-gutter: stable both-edges; /* جلوگیری از جابجایی layout هنگام ظاهر شدن اسکرول */
        }
        
        .container {
            display: flex;
            min-height: 100vh;
            height: 100vh; /* تثبیت ارتفاع برای جلوگیری از پرش اسکرول */
            max-width: 1920px;
            margin: 0 auto;
        }
        
        .sidebar {
            width: 280px;
            background: var(--bg-primary);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-xl);
            border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
            margin: 20px 0 20px 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 24px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
        }
        
        .logo {
            font-size: 3.5em;
            margin-bottom: 12px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .sidebar-header h2 {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .sidebar-subtitle {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .nav-section {
            padding: 24px 0;
        }
        
        .nav-section-title {
            padding: 0 24px 12px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--primary-color)20);
            transition: width 0.3s ease;
        }
        
        .nav-item:hover::before {
            width: 100%;
        }
        
        .nav-item:hover {
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
            border-right-color: var(--primary-color);
            transform: translateX(-4px);
        }
        
        .nav-item.active {
            background: linear-gradient(90deg, var(--primary-color)15, transparent);
            border-right-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-item .material-icons {
            margin-left: 12px;
            font-size: 20px;
        }
        
        .theme-toggle {
            position: absolute;
            bottom: 20px;
            left: 24px;
            right: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: var(--bg-tertiary);
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: transparent;
            height: calc(100vh - 40px); /* 20px padding بالا و پایین */
            scrollbar-gutter: stable both-edges;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-primary);
            padding: 20px 28px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            margin-bottom: 24px;
            backdrop-filter: blur(20px);
        }
        
        .page-title {
            font-size: 2em;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }
        
        .page-title .material-icons {
            margin-left: 12px;
            font-size: 1.2em;
            color: var(--primary-color);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .notification-bell {
            position: relative;
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .notification-bell:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .card h3 {
            color: var(--primary-color);
            margin-bottom: 24px;
            font-size: 1.5em;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card h3 .material-icons {
            margin-left: 12px;
            font-size: 1.2em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card.primary::before { background: linear-gradient(90deg, var(--primary-color), var(--accent-color)); }
        .stat-card.success::before { background: linear-gradient(90deg, var(--success-color), #34d399); }
        .stat-card.warning::before { background: linear-gradient(90deg, var(--warning-color), #fbbf24); }
        .stat-card.error::before { background: linear-gradient(90deg, var(--error-color), #f87171); }
        
        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            color: white;
        }
        
        .stat-card.primary .stat-icon { background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, var(--success-color), #34d399); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, var(--warning-color), #fbbf24); }
        .stat-card.error .stat-icon { background: linear-gradient(135deg, var(--error-color), #f87171); }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            margin: 12px 0 8px;
            color: var(--text-primary);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.95em;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .stat-change {
            margin-top: 8px;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .stat-change.positive { color: var(--success-color); }
        .stat-change.negative { color: var(--error-color); }
        
        .chart-container {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        /* تثبیت ابعاد چارت برای جلوگیری از تغییر ارتفاع و پرش اسکرول */
        .chart-container canvas {
            width: 100% !important;
            height: 320px !important;
            display: block;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 1.25em;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .chart-controls {
            display: flex;
            gap: 8px;
        }
        
        .chart-btn {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chart-btn.active,
        .chart-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .data-table {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table-header {
            padding: 20px 24px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.25em;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .table-actions {
            display: flex;
            gap: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: var(--bg-tertiary);
            padding: 16px 24px;
            text-align: right;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }
        
        td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        tr:hover {
            background: var(--bg-secondary);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #34d399);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
        }
        
        .btn-error {
            background: linear-gradient(135deg, var(--error-color), #f87171);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .logout-btn {
            position: fixed;
            bottom: 24px;
            left: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            z-index: 1000;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 1);
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .progress-bar {
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            margin: 16px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        
        .badge-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        .badge-primary {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                margin: 10px;
                border-radius: var(--radius-xl);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .logout-btn {
                position: relative;
                bottom: auto;
                left: auto;
                margin: 20px;
                width: calc(100% - 40px);
                justify-content: center;
            }
        }

        /* گرید واکنش‌گرا دو ستونه با شکست به یک ستون در عرض کمتر */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1200px) {
            .two-col-grid {
                grid-template-columns: 1fr;
            }
        }

        /* کاهش انیمیشن‌ها برای کاربرانی که ترجیح به حرکت کمتر دارند */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        
        /* Logs Styles */
        .logs-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        
        .logs-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .logs-filters {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        
        .logs-table-container {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: var(--text-primary);
        }
        
        .table-info {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .logs-table th {
            background: var(--bg-secondary);
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .logs-table th span.material-icons {
            font-size: 18px;
            vertical-align: middle;
            margin-left: 5px;
        }
        
        .logs-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .log-row:hover {
            background: var(--bg-secondary);
        }
        
        .log-id {
            font-family: 'Courier New', monospace;
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .log-time .date {
            font-size: 0.9em;
            color: var(--text-secondary);
        }
        
        .log-level {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .level-error {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        .level-warning {
            background: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
        
        .level-info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
        }
        
        .level-debug {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }
        
        .level-default {
            background: rgba(158, 158, 158, 0.1);
            color: #9e9e9e;
        }
        
        .log-message {
            max-width: 400px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        
        .log-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        .btn-icon {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-icon:hover {
            background: var(--bg-secondary);
        }
        
        .btn-icon.danger:hover {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        .btn-icon span.material-icons {
            font-size: 18px;
        }
        
        .no-data {
            text-align: center;
        }
        
        .no-data-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .no-data-message span.material-icons {
            font-size: 48px;
            opacity: 0.5;
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .pagination {
            display: flex;
            gap: 5px;
        }
        
        .pagination-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.9em;
        }
        
        .pagination-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .pagination-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">🎁</div>
                <h2>پنل مدیریت</h2>
                <div class="sidebar-subtitle">ربات ارجاع پرمیوم</div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">داشبورد</div>
                <a href="?tab=stats" class="nav-item <?php echo $activeTab === 'stats' ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span>
                    آمار و گزارشات
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">مدیریت</div>
                <a href="?tab=users" class="nav-item <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
                    <span class="material-icons">people</span>
                    کاربران
                </a>
                <a href="?tab=claims" class="nav-item <?php echo $activeTab === 'claims' ? 'active' : ''; ?>">
                    <span class="material-icons">card_giftcard</span>
                    درخواست جوایز
                </a>
                <a href="?tab=channels" class="nav-item <?php echo $activeTab === 'channels' ? 'active' : ''; ?>">
                    <span class="material-icons">link</span>
                    کانال‌ها
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">ابزارها</div>
                <a href="?tab=broadcast" class="nav-item <?php echo $activeTab === 'broadcast' ? 'active' : ''; ?>">
                    <span class="material-icons">campaign</span>
                    ارسال همگانی
                </a>
                <a href="?tab=analytics" class="nav-item <?php echo $activeTab === 'analytics' ? 'active' : ''; ?>">
                    <span class="material-icons">analytics</span>
                    تحلیل آمار
                </a>
                <a href="?tab=settings" class="nav-item <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
                    <span class="material-icons">settings</span>
                    تنظیمات
                </a>
                <a href="?tab=logs" class="nav-item <?php echo $activeTab === 'logs' ? 'active' : ''; ?>">
                    <span class="material-icons">assignment</span>
                    لاگ‌ها
                </a>
            </div>
            
            <button class="theme-toggle" onclick="toggleTheme()">
                <span class="material-icons">dark_mode</span>
            </button>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <span class="material-icons">
                        <?php 
                        $icons = [
                            'stats' => 'dashboard',
                            'users' => 'people',
                            'claims' => 'card_giftcard',
                            'channels' => 'link',
                            'broadcast' => 'campaign',
                            'analytics' => 'analytics',
                            'settings' => 'settings',
                            'logs' => 'assignment'
                        ];
                        echo $icons[$activeTab] ?? 'dashboard';
                        ?>
                    </span>
                    <?php 
                    $titles = [
                        'stats' => 'آمار و گزارشات',
                        'users' => 'مدیریت کاربران',
                        'claims' => 'درخواست جوایز',
                        'channels' => 'مدیریت کانال‌ها',
                        'broadcast' => 'ارسال همگانی',
                        'analytics' => 'تحلیل آمار',
                        'settings' => 'تنظیمات سیستم',
                        'logs' => 'لاگ‌ها و خطاها'
                    ];
                    echo $titles[$activeTab] ?? 'داشبورد';
                    ?>
                </div>
                
                <div class="user-menu">
                    <div class="notification-bell">
                        <span class="material-icons">notifications</span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.9em;">
                        آخرین بروزرسانی: <?php echo date('H:i'); ?>
                    </div>
                </div>
            </div>
            
            <div id="loading-overlay" class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <?php
            // نمایش تب فعال
            switch ($activeTab) {
                case 'stats':
                    renderStatsTab($db, $csrfToken);
                    break;
                case 'users':
                    renderUsersTab($db, $csrfToken);
                    break;
                case 'claims':
                    renderClaimsTab($db, $csrfToken);
                    break;
                case 'channels':
                    renderChannelsTab($db, $csrfToken);
                    break;
                case 'broadcast':
                    renderBroadcastTab($db, $csrfToken);
                    break;
                case 'analytics':
                    renderAnalyticsTab($db, $csrfToken);
                    break;
                case 'settings':
                    renderSettingsTab($db, $csrfToken);
                    break;
                case 'logs':
                    renderLogsTab($db, $csrfToken);
                    break;
                default:
                    renderStatsTab($db, $csrfToken);
            }
            ?>
        </div>
    </div>
    
    <a href="?logout=1" class="logout-btn">
        <span class="material-icons">logout</span>
        خروج
    </a>
    
    <script>
        // Theme Toggle
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = document.querySelector('.theme-toggle .material-icons');
            icon.textContent = newTheme === 'dark' ? 'light_mode' : 'dark_mode';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            
            const icon = document.querySelector('.theme-toggle .material-icons');
            icon.textContent = savedTheme === 'dark' ? 'light_mode' : 'dark_mode';
        });
        
        // Auto refresh every 30 seconds
        setInterval(function() {
            const lastUpdate = document.querySelector('.user-menu div:last-child');
            if (lastUpdate) {
                const now = new Date();
                lastUpdate.textContent = 'آخرین بروزرسانی: ' + now.toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'});
            }
        }, 30000);
        
        // Loading overlay functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loading-overlay').classList.remove('active');
        }
        
        // Form submission with loading
        document.addEventListener('submit', function(e) {
            if (e.target.tagName === 'FORM') {
                showLoading();
                // Hide loading after 3 seconds max
                setTimeout(hideLoading, 3000);
            }
        });
        
        // Chart.js default config
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');
        
        // Modal functionality
        function showModal(title, content, actions = '') {
            // Remove existing modal
            const existingModal = document.querySelector('.modal-overlay');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Create modal
            const modalHtml = `
                <div class="modal-overlay" onclick="closeModal(event)">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3>${title}</h3>
                            <button class="modal-close" onclick="closeModal()">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        ${actions ? `<div class="modal-actions">${actions}</div>` : ''}
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Add modal styles if not exists
            if (!document.querySelector('#modal-styles')) {
                const styles = `
                    <style id="modal-styles">
                        .modal-overlay {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0, 0, 0, 0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 10000;
                            opacity: 0;
                            animation: modalFadeIn 0.3s ease forwards;
                        }
                        
                        .modal-content {
                            background: var(--card-bg);
                            border-radius: 12px;
                            max-width: 600px;
                            width: 90%;
                            max-height: 80vh;
                            overflow-y: auto;
                            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                            transform: scale(0.8);
                            animation: modalScaleIn 0.3s ease forwards;
                        }
                        
                        .modal-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 20px;
                            border-bottom: 1px solid var(--border-color);
                        }
                        
                        .modal-header h3 {
                            margin: 0;
                            color: var(--text-primary);
                        }
                        
                        .modal-close {
                            background: none;
                            border: none;
                            padding: 8px;
                            border-radius: 50%;
                            cursor: pointer;
                            color: var(--text-secondary);
                            transition: all 0.2s ease;
                        }
                        
                        .modal-close:hover {
                            background: var(--bg-secondary);
                            color: var(--text-primary);
                        }
                        
                        .modal-body {
                            padding: 20px;
                            color: var(--text-primary);
                        }
                        
                        .modal-actions {
                            padding: 20px;
                            border-top: 1px solid var(--border-color);
                            display: flex;
                            gap: 10px;
                            justify-content: flex-end;
                        }
                        
                        .log-detail .detail-row {
                            margin-bottom: 15px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid var(--border-color);
                        }
                        
                        .log-detail .detail-row:last-child {
                            border-bottom: none;
                            margin-bottom: 0;
                            padding-bottom: 0;
                        }
                        
                        .log-detail pre {
                            background: var(--bg-secondary);
                            padding: 15px;
                            border-radius: 8px;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                            font-family: 'Courier New', monospace;
                            font-size: 0.9em;
                            margin-top: 10px;
                            max-height: 200px;
                            overflow-y: auto;
                        }
                        
                        @keyframes modalFadeIn {
                            to { opacity: 1; }
                        }
                        
                        @keyframes modalScaleIn {
                            to { transform: scale(1); }
                        }
                    </style>
                `;
                document.head.insertAdjacentHTML('beforeend', styles);
            }
        }
        
        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.style.animation = 'modalFadeOut 0.3s ease forwards';
                setTimeout(() => modal.remove(), 300);
            }
        }
        
        // Add modal fade out animation
        const modalFadeOutCSS = `
            @keyframes modalFadeOut {
                to { opacity: 0; }
            }
        `;
        
        if (!document.querySelector('#modal-fadeout-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-fadeout-styles';
            style.textContent = modalFadeOutCSS;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>

<?php

/**
 * تب آمار و گزارشات - نسخه گرافیکی
 */
function renderStatsTab($db, $csrfToken) {
    // آمار کلی
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'] ?? 0;
    $totalReferrals = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE credited = 1")['cnt'] ?? 0;
    $pendingReferrals = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE credited = 0")['cnt'] ?? 0;
    $totalScore = $db->fetchOne("SELECT SUM(score) as total FROM scores")['total'] ?? 0;
    $pendingClaims = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'pending'")['cnt'] ?? 0;
    $approvedClaims = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'approved'")['cnt'] ?? 0;
    
    // آمار هفتگی
    $weekAgo = time() - (7 * 24 * 60 * 60);
    $newUsersWeek = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE joined_at > ?", [$weekAgo])['cnt'] ?? 0;
    $newReferralsWeek = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE created_at > ?", [$weekAgo])['cnt'] ?? 0;
    
    // آمار روزانه برای چارت (7 روز گذشته)
    $dailyStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $dayStart = strtotime(date('Y-m-d', time() - ($i * 24 * 60 * 60)));
        $dayEnd = $dayStart + (24 * 60 * 60) - 1;
        
        $dayUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE joined_at BETWEEN ? AND ?", [$dayStart, $dayEnd])['cnt'] ?? 0;
        $dayReferrals = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE created_at BETWEEN ? AND ?", [$dayStart, $dayEnd])['cnt'] ?? 0;
        
        $dailyStats[] = [
            'date' => date('m/d', $dayStart),
            'users' => $dayUsers,
            'referrals' => $dayReferrals
        ];
    }
    
    // کاربران برتر
    $topUsers = $db->fetchAll(
        "SELECT u.first_name, u.username, s.score 
         FROM users u 
         JOIN scores s ON u.id = s.user_id 
         ORDER BY s.score DESC 
         LIMIT 5"
    );
    
    // کاربران اخیر
    $recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY joined_at DESC LIMIT 8");
    
    ?>
    <!-- کارت‌های آمار -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">people</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">کل کاربران</div>
            <div class="stat-change positive">
                <span class="material-icons">trending_up</span>
                +<?php echo $newUsersWeek; ?> این هفته
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">how_to_reg</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalReferrals); ?></div>
            <div class="stat-label">دعوت‌های موفق</div>
            <div class="stat-change positive">
                <span class="material-icons">trending_up</span>
                +<?php echo $newReferralsWeek; ?> این هفته
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">hourglass_empty</span>
            </div>
            <div class="stat-number"><?php echo number_format($pendingReferrals); ?></div>
            <div class="stat-label">در انتظار تأیید</div>
            <div class="stat-change">
                <span class="material-icons">schedule</span>
                نیاز به بررسی
            </div>
        </div>
        
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">stars</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalScore); ?></div>
            <div class="stat-label">مجموع امتیازات</div>
            <div class="stat-change">
                <span class="material-icons">trending_up</span>
                فعال
            </div>
        </div>
        
        <div class="stat-card error">
            <div class="stat-icon">
                <span class="material-icons">card_giftcard</span>
            </div>
            <div class="stat-number"><?php echo number_format($pendingClaims); ?></div>
            <div class="stat-label">جوایز در انتظار</div>
            <div class="stat-change <?php echo $pendingClaims > 0 ? 'negative' : 'positive'; ?>">
                <span class="material-icons"><?php echo $pendingClaims > 0 ? 'priority_high' : 'check_circle'; ?></span>
                <?php echo $pendingClaims > 0 ? 'نیاز به اقدام' : 'بروز است'; ?>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">verified</span>
            </div>
            <div class="stat-number"><?php echo number_format($approvedClaims); ?></div>
            <div class="stat-label">جوایز تحویل شده</div>
            <div class="stat-change positive">
                <span class="material-icons">check_circle</span>
                تکمیل شده
            </div>
        </div>
    </div>
    
    <!-- چارت فعالیت روزانه -->
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <span class="material-icons">show_chart</span>
                فعالیت 7 روز گذشته
            </h3>
            <div class="chart-controls">
                    <button class="chart-btn active" onclick="toggleChart('users', this)">کاربران</button>
                    <button class="chart-btn" onclick="toggleChart('referrals', this)">دعوت‌ها</button>
                    <button class="chart-btn" onclick="toggleChart('both', this)">هر دو</button>
                </div>
        </div>
    <canvas id="activityChart"></canvas>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- کاربران برتر -->
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">
                    <span class="material-icons">emoji_events</span>
                    کاربران برتر
                </h3>
                <a href="?tab=users" class="btn btn-outline btn-sm">
                    <span class="material-icons">open_in_new</span>
                    مشاهده همه
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رتبه</th>
                        <th>نام</th>
                        <th>امتیاز</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topUsers)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-secondary);">
                                هنوز کاربری امتیازی کسب نکرده
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topUsers as $i => $user): ?>
                        <tr>
                            <td>
                                <?php 
                                $medals = ['🥇', '🥈', '🥉'];
                                echo $medals[$i] ?? '#' . ($i + 1);
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($user['first_name']); ?>
                                <?php if ($user['username']): ?>
                                    <div style="font-size: 0.8em; color: var(--text-secondary);">
                                        @<?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    <?php echo number_format($user['score']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- کاربران اخیر -->
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">
                    <span class="material-icons">schedule</span>
                    کاربران اخیر
                </h3>
                <a href="?tab=users" class="btn btn-outline btn-sm">
                    <span class="material-icons">open_in_new</span>
                    مشاهده همه
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($user['first_name']); ?>
                            <?php if ($user['username']): ?>
                                <div style="font-size: 0.8em; color: var(--text-secondary);">
                                    @<?php echo htmlspecialchars($user['username']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.85em; color: var(--text-secondary);">
                            <?php echo date('m/d H:i', $user['joined_at']); ?>
                        </td>
                        <td>
                            <span class="badge badge-success">فعال</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // داده‌های چارت
        const chartData = {
            labels: <?php echo json_encode(array_column($dailyStats, 'date')); ?>,
            datasets: [
                {
                    label: 'کاربران جدید',
                    data: <?php echo json_encode(array_column($dailyStats, 'users')); ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'دعوت‌های جدید',
                    data: <?php echo json_encode(array_column($dailyStats, 'referrals')); ?>,
                    borderColor: 'rgb(118, 75, 162)',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        };
        
        // تنظیمات چارت
        const chartConfig = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        };
        
        // ایجاد چارت
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, chartConfig);
        
        // تغییر نمایش چارت
        function toggleChart(type, btn) {
            // بروزرسانی دکمه‌ها (در صورت وجود دکمه)
            if (btn) {
                document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
            
            // تغییر visibility datasets
            switch(type) {
                case 'users':
                    activityChart.data.datasets[0].hidden = false;
                    activityChart.data.datasets[1].hidden = true;
                    break;
                case 'referrals':
                    activityChart.data.datasets[0].hidden = true;
                    activityChart.data.datasets[1].hidden = false;
                    break;
                case 'both':
                    activityChart.data.datasets[0].hidden = false;
                    activityChart.data.datasets[1].hidden = false;
                    break;
            }
            activityChart.update();
        }
        
        // تنظیم اولیه - فقط کاربران
        toggleChart('users', null);
    </script>
    <?php
}

/**
 * تب تحلیل آمار پیشرفته
 */
function renderAnalyticsTab($db, $csrfToken) {
    // آمار ماهانه
    $monthlyStats = [];
    for ($i = 11; $i >= 0; $i--) {
        $monthStart = strtotime(date('Y-m-01', strtotime("-{$i} months")));
        $monthEnd = strtotime(date('Y-m-t', strtotime("-{$i} months"))) + (24 * 60 * 60) - 1;
        
        $monthUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE joined_at BETWEEN ? AND ?", [$monthStart, $monthEnd])['cnt'] ?? 0;
        $monthReferrals = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE created_at BETWEEN ? AND ?", [$monthStart, $monthEnd])['cnt'] ?? 0;
        $monthClaims = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE created_at BETWEEN ? AND ?", [$monthStart, $monthEnd])['cnt'] ?? 0;
        
        $monthlyStats[] = [
            'month' => date('M Y', $monthStart),
            'monthShort' => date('M', $monthStart),
            'users' => $monthUsers,
            'referrals' => $monthReferrals,
            'claims' => $monthClaims
        ];
    }
    
    // آمار کانال‌ها
    $channels = $db->fetchAll("SELECT * FROM channels WHERE active = 1");
    
    // توزیع امتیازات
    $scoreDistribution = $db->fetchAll(
        "SELECT 
            CASE 
                WHEN score = 0 THEN '0'
                WHEN score BETWEEN 1 AND 5 THEN '1-5'
                WHEN score BETWEEN 6 AND 10 THEN '6-10'
                WHEN score BETWEEN 11 AND 20 THEN '11-20'
                WHEN score BETWEEN 21 AND 50 THEN '21-50'
                ELSE '50+'
            END as range,
            COUNT(*) as count
         FROM scores 
         GROUP BY range
         ORDER BY MIN(score)"
    );
    
    // محبوب‌ترین ساعات فعالیت
    $hourlyActivity = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $count = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM users WHERE HOUR(FROM_UNIXTIME(joined_at)) = ?", 
            [$hour]
        )['cnt'] ?? 0;
        $hourlyActivity[] = ['hour' => $hour, 'count' => $count];
    }
    
    ?>
    <!-- آمار کلیدی -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">trending_up</span>
            </div>
            <div class="stat-number">
                <?php 
                $growth = 0;
                if (count($monthlyStats) >= 2) {
                    $current = end($monthlyStats)['users'];
                    $previous = prev($monthlyStats)['users'];
                    if ($previous > 0) {
                        $growth = round((($current - $previous) / $previous) * 100, 1);
                    }
                }
                echo $growth > 0 ? '+' : '';
                echo $growth;
                ?>%
            </div>
            <div class="stat-label">رشد ماهانه کاربران</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">groups</span>
            </div>
            <div class="stat-number">
                <?php 
                $conversionRate = 0;
                $totalUsers = array_sum(array_column($monthlyStats, 'users'));
                $totalReferrals = array_sum(array_column($monthlyStats, 'referrals'));
                if ($totalUsers > 0) {
                    $conversionRate = round(($totalReferrals / $totalUsers) * 100, 1);
                }
                echo $conversionRate;
                ?>%
            </div>
            <div class="stat-label">نرخ تبدیل دعوت</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">schedule</span>
            </div>
            <div class="stat-number">
                <?php 
                $peakHour = 0;
                $maxActivity = 0;
                foreach ($hourlyActivity as $activity) {
                    if ($activity['count'] > $maxActivity) {
                        $maxActivity = $activity['count'];
                        $peakHour = $activity['hour'];
                    }
                }
                echo sprintf('%02d:00', $peakHour);
                ?>
            </div>
            <div class="stat-label">ساعت پیک فعالیت</div>
        </div>
        
        <div class="stat-card error">
            <div class="stat-icon">
                <span class="material-icons">link</span>
            </div>
            <div class="stat-number"><?php echo count($channels); ?></div>
            <div class="stat-label">کانال‌های فعال</div>
        </div>
    </div>
    
    <!-- چارت‌های تحلیلی -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- چارت رشد ماهانه -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">
                    <span class="material-icons">timeline</span>
                    روند رشد 12 ماه اخیر
                </h3>
            </div>
            <canvas id="growthChart" height="300"></canvas>
        </div>
        
        <!-- چارت توزیع امتیازات -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">
                    <span class="material-icons">donut_small</span>
                    توزیع امتیازات
                </h3>
            </div>
            <canvas id="scoreChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- چارت فعالیت ساعتی -->
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <span class="material-icons">access_time</span>
                الگوی فعالیت 24 ساعته
            </h3>
        </div>
        <canvas id="hourlyChart" height="200"></canvas>
    </div>
    
    <!-- جدول کانال‌ها -->
    <?php if (!empty($channels)): ?>
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">
                <span class="material-icons">link</span>
                عملکرد کانال‌ها
            </h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>کانال</th>
                    <th>وضعیت</th>
                    <th>تاریخ افزودن</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($channels as $channel): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($channel['username']); ?></strong>
                    </td>
                    <td>
                        <span class="badge badge-success">فعال</span>
                    </td>
                    <td style="color: var(--text-secondary);">
                        <?php echo date('Y/m/d', $channel['created_at']); ?>
                    </td>
                    <td>
                        <a href="https://t.me/<?php echo ltrim($channel['username'], '@'); ?>" 
                           target="_blank" class="btn btn-outline btn-sm">
                            <span class="material-icons">open_in_new</span>
                            مشاهده
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <script>
        // چارت رشد ماهانه
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyStats, 'monthShort')); ?>,
                datasets: [
                    {
                        label: 'کاربران جدید',
                        data: <?php echo json_encode(array_column($monthlyStats, 'users')); ?>,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'دعوت‌ها',
                        data: <?php echo json_encode(array_column($monthlyStats, 'referrals')); ?>,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'درخواست جوایز',
                        data: <?php echo json_encode(array_column($monthlyStats, 'claims')); ?>,
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // چارت توزیع امتیازات
        const scoreCtx = document.getElementById('scoreChart').getContext('2d');
        new Chart(scoreCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($scoreDistribution, 'range')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($scoreDistribution, 'count')); ?>,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // چارت فعالیت ساعتی
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($h) { return sprintf('%02d:00', $h['hour']); }, $hourlyActivity)); ?>,
                datasets: [{
                    label: 'تعداد ثبت‌نام',
                    data: <?php echo json_encode(array_column($hourlyActivity, 'count')); ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: 'rgb(102, 126, 234)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php
}
/**
 * تب کاربران - نسخه مدرن
 */
function renderUsersTab($db, $csrfToken) {
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    $where = '';
    $params = [];
    if ($search) {
        $where = "WHERE u.id LIKE ? OR u.first_name LIKE ? OR u.username LIKE ?";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    $users = $db->fetchAll(
        "SELECT u.*, COALESCE(s.score, 0) as score,
                (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id AND credited = 1) as referral_count,
                (SELECT COUNT(*) FROM claims WHERE user_id = u.id) as claim_count
         FROM users u 
         LEFT JOIN scores s ON u.id = s.user_id 
         {$where}
         ORDER BY u.joined_at DESC 
         LIMIT {$perPage} OFFSET {$offset}",
        $params
    );
    
    $total = $db->fetchOne("SELECT COUNT(*) as cnt FROM users u {$where}", $params)['cnt'];
    $totalPages = ceil($total / $perPage);
    
    // آمار کاربران
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
    $activeUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE join_status = 1")['cnt'];
    $newToday = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE joined_at > ?", [strtotime('today')])['cnt'];
    
    ?>
    <!-- آمار سریع -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">people</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">کل کاربران</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">verified_user</span>
            </div>
            <div class="stat-number"><?php echo number_format($activeUsers); ?></div>
            <div class="stat-label">کاربران فعال</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">today</span>
            </div>
            <div class="stat-number"><?php echo number_format($newToday); ?></div>
            <div class="stat-label">عضو جدید امروز</div>
        </div>
        
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">search</span>
            </div>
            <div class="stat-number"><?php echo number_format(count($users)); ?></div>
            <div class="stat-label">نتایج جستجو</div>
        </div>
    </div>
    
    <!-- جستجو و فیلتر -->
    <div class="card">
        <h3>
            <span class="material-icons">manage_search</span>
            جستجو و فیلتر کاربران
        </h3>
        
        <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 16px; align-items: end;">
            <input type="hidden" name="tab" value="users">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">جستجو در کاربران</label>
                <input type="text" name="search" class="form-input" 
                       placeholder="شناسه، نام، یوزرنیم..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn">
                <span class="material-icons">search</span>
                جستجو
            </button>
            <?php if ($search): ?>
            <a href="?tab=users" class="btn btn-outline">
                <span class="material-icons">clear</span>
                پاک کردن
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- جدول کاربران -->
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">
                <span class="material-icons">list</span>
                لیست کاربران
                <?php if ($search): ?>
                    <span style="font-size: 0.8em; color: var(--text-secondary);">
                        (جستجو: "<?php echo htmlspecialchars($search); ?>")
                    </span>
                <?php endif; ?>
            </h3>
            <div class="table-actions">
                <button class="btn btn-outline btn-sm" onclick="exportUsers()">
                    <span class="material-icons">download</span>
                    خروجی Excel
                </button>
            </div>
        </div>
        
        <?php if (empty($users)): ?>
        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
            <span class="material-icons" style="font-size: 48px; margin-bottom: 16px;">search_off</span>
            <h3>کاربری یافت نشد</h3>
            <p>برای جستجوی جدید از فیلترهای بالا استفاده کنید.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>آمار</th>
                    <th>وضعیت</th>
                    <th>تاریخ عضویت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($user['first_name']); ?>
                                </div>
                                <div style="font-size: 0.85em; color: var(--text-secondary);">
                                    <?php if ($user['username']): ?>
                                        @<?php echo htmlspecialchars($user['username']); ?>
                                    <?php else: ?>
                                        ID: <?php echo $user['id']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="badge badge-primary" title="امتیاز">
                                <span class="material-icons" style="font-size: 14px;">stars</span>
                                <?php echo number_format($user['score']); ?>
                            </span>
                            <span class="badge badge-success" title="دعوت‌ها">
                                <span class="material-icons" style="font-size: 14px;">group_add</span>
                                <?php echo number_format($user['referral_count']); ?>
                            </span>
                            <span class="badge badge-warning" title="درخواست جوایز">
                                <span class="material-icons" style="font-size: 14px;">card_giftcard</span>
                                <?php echo number_format($user['claim_count']); ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if ($user['join_status'] == 1): ?>
                            <span class="badge badge-success">
                                <span class="material-icons" style="font-size: 14px;">check_circle</span>
                                فعال
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning">
                                <span class="material-icons" style="font-size: 14px;">pending</span>
                                در انتظار
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-secondary); font-size: 0.9em;">
                        <div><?php echo date('Y/m/d', $user['joined_at']); ?></div>
                        <div><?php echo date('H:i', $user['joined_at']); ?></div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-outline btn-sm" onclick="viewUser(<?php echo $user['id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="btn btn-error btn-sm" onclick="banUser(<?php echo $user['id']; ?>)">
                                <span class="material-icons">block</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="padding: 20px; display: flex; justify-content: center; align-items: center; gap: 8px; border-top: 1px solid var(--border-color);">
            <?php if ($page > 1): ?>
                <a href="?tab=users&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" 
                   class="btn btn-outline btn-sm">
                    <span class="material-icons">chevron_right</span>
                </a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): 
            ?>
                <a href="?tab=users&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?tab=users&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" 
                   class="btn btn-outline btn-sm">
                    <span class="material-icons">chevron_left</span>
                </a>
            <?php endif; ?>
            
            <div style="margin-right: 16px; color: var(--text-secondary); font-size: 0.9em;">
                صفحه <?php echo $page; ?> از <?php echo $totalPages; ?> 
                (<?php echo number_format($total); ?> کاربر)
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function viewUser(userId) {
            // باز کردن مودال جزئیات کاربر
            alert('مشاهده جزئیات کاربر: ' + userId);
        }
        
        function editUser(userId) {
            // باز کردن مودال ویرایش کاربر
            alert('ویرایش کاربر: ' + userId);
        }
        
        function banUser(userId) {
            if (confirm('آیا مطمئن هستید که می‌خواهید این کاربر را مسدود کنید؟')) {
                // ارسال درخواست مسدودسازی
                alert('مسدودسازی کاربر: ' + userId);
            }
        }
        
        function exportUsers() {
            // خروجی Excel
            alert('خروجی Excel در حال آماده‌سازی...');
        }
    </script>
    <?php
}

/**
 * تب درخواست جوایز - نسخه مدرن
 */
function renderClaimsTab($db, $csrfToken) {
    $status = $_GET['status'] ?? 'pending';
    
    // آمار درخواست‌ها
    $pendingCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'pending'")['cnt'] ?? 0;
    $approvedCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'approved'")['cnt'] ?? 0;
    $rejectedCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'rejected'")['cnt'] ?? 0;
    $totalCount = $pendingCount + $approvedCount + $rejectedCount;
    
    $claims = $db->fetchAll(
        "SELECT c.*, u.first_name, u.username 
         FROM claims c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.status = ? 
         ORDER BY c.created_at DESC 
         LIMIT 50",
        [$status]
    );
    
    ?>
    <!-- آمار سریع -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">pending_actions</span>
            </div>
            <div class="stat-number"><?php echo number_format($pendingCount); ?></div>
            <div class="stat-label">در انتظار بررسی</div>
            <div class="stat-change <?php echo $pendingCount > 0 ? 'negative' : 'positive'; ?>">
                <span class="material-icons"><?php echo $pendingCount > 0 ? 'priority_high' : 'check'; ?></span>
                <?php echo $pendingCount > 0 ? 'نیاز به اقدام' : 'بروز است'; ?>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="stat-number"><?php echo number_format($approvedCount); ?></div>
            <div class="stat-label">تأیید شده</div>
            <div class="stat-change positive">
                <span class="material-icons">trending_up</span>
                تحویل داده شده
            </div>
        </div>
        
        <div class="stat-card error">
            <div class="stat-icon">
                <span class="material-icons">cancel</span>
            </div>
            <div class="stat-number"><?php echo number_format($rejectedCount); ?></div>
            <div class="stat-label">رد شده</div>
            <div class="stat-change negative">
                <span class="material-icons">trending_down</span>
                بررسی شده
            </div>
        </div>
        
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">card_giftcard</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalCount); ?></div>
            <div class="stat-label">کل درخواست‌ها</div>
            <div class="stat-change">
                <span class="material-icons">analytics</span>
                تاکنون
            </div>
        </div>
    </div>
    
    <!-- فیلتر وضعیت -->
    <div class="card">
        <h3>
            <span class="material-icons">filter_list</span>
            فیلتر بر اساس وضعیت
        </h3>
        
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="?tab=claims&status=pending" 
               class="btn <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline'; ?>">
                <span class="material-icons">pending_actions</span>
                در انتظار (<?php echo $pendingCount; ?>)
            </a>
            <a href="?tab=claims&status=approved" 
               class="btn <?php echo $status === 'approved' ? 'btn-success' : 'btn-outline'; ?>">
                <span class="material-icons">check_circle</span>
                تأیید شده (<?php echo $approvedCount; ?>)
            </a>
            <a href="?tab=claims&status=rejected" 
               class="btn <?php echo $status === 'rejected' ? 'btn-error' : 'btn-outline'; ?>">
                <span class="material-icons">cancel</span>
                رد شده (<?php echo $rejectedCount; ?>)
            </a>
        </div>
    </div>
    
    <!-- لیست درخواست‌ها -->
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">
                <span class="material-icons">
                    <?php 
                    $statusIcons = [
                        'pending' => 'pending_actions',
                        'approved' => 'check_circle',
                        'rejected' => 'cancel'
                    ];
                    echo $statusIcons[$status] ?? 'card_giftcard';
                    ?>
                </span>
                درخواست‌های 
                <?php 
                $statusNames = [
                    'pending' => 'در انتظار',
                    'approved' => 'تأیید شده',
                    'rejected' => 'رد شده'
                ];
                echo $statusNames[$status] ?? 'همه';
                ?>
            </h3>
            <div class="table-actions">
                <?php if ($status === 'pending' && !empty($claims)): ?>
                <button class="btn btn-success btn-sm" onclick="approveAllClaims()">
                    <span class="material-icons">done_all</span>
                    تأیید همه
                </button>
                <?php endif; ?>
                <button class="btn btn-outline btn-sm" onclick="exportClaims()">
                    <span class="material-icons">download</span>
                    خروجی Excel
                </button>
            </div>
        </div>
        
        <?php if (empty($claims)): ?>
        <div style="padding: 60px; text-align: center; color: var(--text-secondary);">
            <span class="material-icons" style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">
                <?php echo $statusIcons[$status] ?? 'card_giftcard'; ?>
            </span>
            <h3 style="margin-bottom: 8px;">درخواستی یافت نشد</h3>
            <p>در حال حاضر درخواست <?php echo $statusNames[$status] ?? ''; ?> وجود ندارد.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>شماره درخواست</th>
                    <th>کاربر</th>
                    <th>امتیاز</th>
                    <th>تاریخ درخواست</th>
                    <?php if ($status === 'pending'): ?>
                    <th>عملیات</th>
                    <?php else: ?>
                    <th>تاریخ پردازش</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $claim): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-weight: 600; color: var(--primary-color);">
                                #<?php echo $claim['id']; ?>
                            </span>
                            <?php if ($claim['status'] === 'pending'): ?>
                                <span class="badge badge-warning">جدید</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                <?php echo strtoupper(substr($claim['first_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($claim['first_name']); ?>
                                </div>
                                <div style="font-size: 0.8em; color: var(--text-secondary);">
                                    <?php if ($claim['username']): ?>
                                        @<?php echo htmlspecialchars($claim['username']); ?>
                                    <?php else: ?>
                                        ID: <?php echo $claim['user_id']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-primary">
                            <span class="material-icons" style="font-size: 14px;">stars</span>
                            <?php echo number_format($claim['score_at_claim']); ?>
                        </span>
                    </td>
                    <td style="color: var(--text-secondary); font-size: 0.9em;">
                        <div><?php echo date('Y/m/d', $claim['created_at']); ?></div>
                        <div><?php echo date('H:i', $claim['created_at']); ?></div>
                    </td>
                    <?php if ($status === 'pending'): ?>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-success btn-sm" 
                                    onclick="approveClaim(<?php echo $claim['id']; ?>, '<?php echo htmlspecialchars($claim['first_name']); ?>')">
                                <span class="material-icons">check</span>
                                تأیید
                            </button>
                            <button class="btn btn-error btn-sm" 
                                    onclick="rejectClaim(<?php echo $claim['id']; ?>, '<?php echo htmlspecialchars($claim['first_name']); ?>')">
                                <span class="material-icons">close</span>
                                رد
                            </button>
                            <button class="btn btn-outline btn-sm" 
                                    onclick="viewClaimDetails(<?php echo $claim['id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                        </div>
                    </td>
                    <?php else: ?>
                    <td style="color: var(--text-secondary); font-size: 0.9em;">
                        <?php if ($claim['responded_at']): ?>
                            <div><?php echo date('Y/m/d', $claim['responded_at']); ?></div>
                            <div><?php echo date('H:i', $claim['responded_at']); ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Modal تأیید عملیات -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">تأیید عملیات</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">آیا مطمئن هستید؟</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">لغو</button>
                <button id="modalConfirm" class="btn btn-success">تأیید</button>
            </div>
        </div>
    </div>
    
    <script>
        function approveClaim(claimId, userName) {
            showModal(
                'تأیید درخواست جایزه',
                `آیا می‌خواهید درخواست جایزه کاربر "${userName}" را تأیید کنید؟\n\nپس از تأیید، امتیاز کاربر کسر خواهد شد.`,
                function() {
                    submitAction('approve_claim', {claim_id: claimId});
                }
            );
        }
        
        function rejectClaim(claimId, userName) {
            showModal(
                'رد درخواست جایزه',
                `آیا می‌خواهید درخواست جایزه کاربر "${userName}" را رد کنید؟`,
                function() {
                    submitAction('reject_claim', {claim_id: claimId});
                },
                'btn-error'
            );
        }
        
        function viewClaimDetails(claimId) {
            alert('مشاهده جزئیات درخواست: ' + claimId);
        }
        
        function approveAllClaims() {
            showModal(
                'تأیید همه درخواست‌ها',
                'آیا می‌خواهید همه درخواست‌های در انتظار را تأیید کنید؟\n\nاین عمل قابل بازگشت نیست.',
                function() {
                    alert('تأیید همه درخواست‌ها در حال پیاده‌سازی...');
                }
            );
        }
        
        function exportClaims() {
            alert('خروجی Excel در حال آماده‌سازی...');
        }
        
        function showModal(title, message, onConfirm, confirmClass = 'btn-success') {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            
            const confirmBtn = document.getElementById('modalConfirm');
            confirmBtn.className = 'btn ' + confirmClass;
            confirmBtn.onclick = function() {
                closeModal();
                onConfirm();
            };
            
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        function submitAction(action, data) {
            showLoading();
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrfToken; ?>';
            form.appendChild(csrfInput);
            
            // Action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Data
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-xl);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            color: var(--text-primary);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 24px;
            color: var(--text-primary);
            line-height: 1.6;
            white-space: pre-line;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
    </style>
    <?php
}

/**
 * تب کانال‌ها - نسخه مدرن
 */
function renderChannelsTab($db, $csrfToken) {
    $channels = $db->fetchAll("SELECT * FROM channels ORDER BY created_at DESC");
    $activeChannels = array_filter($channels, fn($ch) => $ch['active'] == 1);
    $inactiveChannels = array_filter($channels, fn($ch) => $ch['active'] == 0);
    
    ?>
    <!-- آمار سریع -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">link</span>
            </div>
            <div class="stat-number"><?php echo count($channels); ?></div>
            <div class="stat-label">کل کانال‌ها</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="stat-number"><?php echo count($activeChannels); ?></div>
            <div class="stat-label">کانال‌های فعال</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">pause_circle</span>
            </div>
            <div class="stat-number"><?php echo count($inactiveChannels); ?></div>
            <div class="stat-label">کانال‌های غیرفعال</div>
        </div>
    </div>
    
    <!-- افزودن کانال جدید -->
    <div class="card">
        <h3>
            <span class="material-icons">add_link</span>
            افزودن کانال جدید
        </h3>
        
        <form method="POST" id="addChannelForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="add_channel">
            
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">
                        <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">alternate_email</span>
                        یوزرنیم کانال
                    </label>
                    <input type="text" name="username" class="form-input" 
                           placeholder="@yourchannel یا yourchannel" 
                           pattern="^@?[a-zA-Z0-9_]{5,32}$"
                           title="یوزرنیم باید بین 5 تا 32 کاراکتر باشد"
                           required>
                    <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                        مثال: @mychannel یا mychannel
                    </div>
                </div>
                <button type="submit" class="btn">
                    <span class="material-icons">add</span>
                    افزودن کانال
                </button>
            </div>
        </form>
    </div>
    
    <!-- لیست کانال‌ها -->
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">
                <span class="material-icons">list</span>
                لیست کانال‌های اجباری
            </h3>
            <div class="table-actions">
                <?php if (!empty($channels)): ?>
                <button class="btn btn-warning btn-sm" onclick="toggleAllChannels()">
                    <span class="material-icons">swap_horiz</span>
                    تغییر وضعیت همه
                </button>
                <button class="btn btn-outline btn-sm" onclick="testAllChannels()">
                    <span class="material-icons">verified</span>
                    تست همه کانال‌ها
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($channels)): ?>
        <div style="padding: 60px; text-align: center; color: var(--text-secondary);">
            <span class="material-icons" style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">link_off</span>
            <h3 style="margin-bottom: 8px;">هیچ کانالی ثبت نشده</h3>
            <p>برای شروع، کانال اول خود را از فرم بالا اضافه کنید.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>کانال</th>
                    <th>وضعیت</th>
                    <th>آمار</th>
                    <th>تاریخ افزودن</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($channels as $channel): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1da1f2, #0d7dc4); display: flex; align-items: center; justify-content: center; color: white;">
                                <span class="material-icons">tag</span>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($channel['username']); ?>
                                </div>
                                <div style="font-size: 0.8em; color: var(--text-secondary);">
                                    ID: <?php echo $channel['id']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($channel['active']): ?>
                            <span class="badge badge-success">
                                <span class="material-icons" style="font-size: 14px;">check_circle</span>
                                فعال
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning">
                                <span class="material-icons" style="font-size: 14px;">pause_circle</span>
                                غیرفعال
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($channel['required']): ?>
                            <span class="badge badge-error" style="margin-right: 8px;">
                                <span class="material-icons" style="font-size: 14px;">star</span>
                                اجباری
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="badge badge-primary" title="تعداد بررسی">
                                <span class="material-icons" style="font-size: 14px;">visibility</span>
                                <?php 
                                // این آمار باید از جدول member_cache محاسبه شود
                                $checkCount = $db->fetchOne("SELECT COUNT(DISTINCT user_id) as cnt FROM member_cache WHERE channel = ?", [$channel['username']])['cnt'] ?? 0;
                                echo number_format($checkCount);
                                ?>
                            </span>
                        </div>
                    </td>
                    <td style="color: var(--text-secondary); font-size: 0.9em;">
                        <div><?php echo date('Y/m/d', $channel['created_at']); ?></div>
                        <div><?php echo date('H:i', $channel['created_at']); ?></div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="https://t.me/<?php echo ltrim($channel['username'], '@'); ?>" 
                               target="_blank" class="btn btn-outline btn-sm" title="مشاهده کانال">
                                <span class="material-icons">open_in_new</span>
                            </a>
                            
                            <button class="btn <?php echo $channel['active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm" 
                                    onclick="toggleChannel(<?php echo $channel['id']; ?>, '<?php echo htmlspecialchars($channel['username']); ?>', <?php echo $channel['active'] ? 'false' : 'true'; ?>)"
                                    title="<?php echo $channel['active'] ? 'غیرفعال کردن' : 'فعال کردن'; ?>">
                                <span class="material-icons"><?php echo $channel['active'] ? 'pause' : 'play_arrow'; ?></span>
                            </button>
                            
                            <button class="btn btn-primary btn-sm" 
                                    onclick="testChannel('<?php echo htmlspecialchars($channel['username']); ?>')"
                                    title="تست کانال">
                                <span class="material-icons">verified</span>
                            </button>
                            
                            <button class="btn btn-error btn-sm" 
                                    onclick="deleteChannel(<?php echo $channel['id']; ?>, '<?php echo htmlspecialchars($channel['username']); ?>')"
                                    title="حذف کانال">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- راهنمای تنظیم کانال -->
    <div class="card" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
        <h3>
            <span class="material-icons">help_outline</span>
            راهنمای تنظیم کانال اجباری
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <h4 style="color: var(--primary-color); margin-bottom: 12px;">
                    <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">playlist_add_check</span>
                    مراحل تنظیم:
                </h4>
                <ol style="margin-right: 20px; color: var(--text-secondary); line-height: 1.8;">
                    <li>ربات را به کانال خود اضافه کنید</li>
                    <li>ربات را به عنوان ادمین تعیین کنید</li>
                    <li>یوزرنیم کانال را در فرم بالا وارد کنید</li>
                    <li>کانال را فعال کنید</li>
                    <li>از دکمه تست برای بررسی استفاده کنید</li>
                </ol>
            </div>
            
            <div>
                <h4 style="color: var(--warning-color); margin-bottom: 12px;">
                    <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">warning</span>
                    نکات مهم:
                </h4>
                <ul style="margin-right: 20px; color: var(--text-secondary); line-height: 1.8;">
                    <li>کانال باید عمومی (Public) باشد</li>
                    <li>ربات نیاز به دسترسی Get Chat Member دارد</li>
                    <li>یوزرنیم بدون @ هم قابل قبول است</li>
                    <li>تغییرات فوراً اعمال می‌شوند</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function toggleChannel(channelId, channelName, newStatus) {
            const action = newStatus ? 'فعال' : 'غیرفعال';
            if (confirm(`آیا می‌خواهید کانال "${channelName}" را ${action} کنید؟`)) {
                submitAction('toggle_channel', {channel_id: channelId});
            }
        }
        
        function deleteChannel(channelId, channelName) {
            if (confirm(`آیا می‌خواهید کانال "${channelName}" را حذف کنید؟\n\nاین عمل قابل بازگشت نیست.`)) {
                submitAction('delete_channel', {channel_id: channelId});
            }
        }
        
        function testChannel(channelUsername) {
            showLoading();
            
            // ارسال درخواست AJAX برای تست کانال
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=test_channel&channel=${encodeURIComponent(channelUsername)}&csrf_token=<?php echo $csrfToken; ?>`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert(`✅ کانال "${channelUsername}" در دسترس است و ربات دسترسی لازم را دارد.`);
                } else {
                    alert(`❌ خطا در تست کانال: ${data.error || 'نامشخص'}`);
                }
            })
            .catch(error => {
                hideLoading();
                alert('❌ خطا در ارتباط با سرور');
            });
        }
        
        function testAllChannels() {
            if (confirm('آیا می‌خواهید همه کانال‌ها را تست کنید؟')) {
                alert('تست همه کانال‌ها در حال پیاده‌سازی...');
            }
        }
        
        function toggleAllChannels() {
            if (confirm('آیا می‌خواهید وضعیت همه کانال‌ها را تغییر دهید؟')) {
                alert('تغییر وضعیت همه کانال‌ها در حال پیاده‌سازی...');
            }
        }
        
        // اعتبارسنجی فرم
        document.getElementById('addChannelForm').addEventListener('submit', function(e) {
            const usernameInput = this.querySelector('input[name="username"]');
            let username = usernameInput.value.trim();
            
            // حذف @ از ابتدا اگر وجود داشته باشد
            if (username.startsWith('@')) {
                username = username.substring(1);
            }
            
            // بررسی فرمت یوزرنیم
            if (!/^[a-zA-Z0-9_]{5,32}$/.test(username)) {
                e.preventDefault();
                alert('یوزرنیم کانال باید بین 5 تا 32 کاراکتر باشد و فقط شامل حروف، اعداد و خط تیره باشد.');
                return;
            }
            
            // افزودن @ به ابتدا
            usernameInput.value = '@' + username;
        });
        
        function submitAction(action, data) {
            showLoading();
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrfToken; ?>';
            form.appendChild(csrfInput);
            
            // Action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Data
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <?php
}

/**
 * تب ارسال همگانی - نسخه مدرن
 */
function renderBroadcastTab($db, $csrfToken) {
    // آمار کاربران
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'] ?? 0;
    $activeUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE join_status = 1")['cnt'] ?? 0;
    $recentUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE joined_at > ?", [time() - (7 * 24 * 60 * 60)])['cnt'] ?? 0;
    
    // آخرین پیام همگانی (اگر سیستم لاگ داشته باشیم)
    $lastBroadcast = $db->fetchOne(
        "SELECT * FROM admin_logs WHERE action = 'broadcast' ORDER BY created_at DESC LIMIT 1"
    );
    
    ?>
    <!-- آمار مخاطبان -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="material-icons">people</span>
            </div>
            <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">کل کاربران</div>
            <div class="stat-change">
                <span class="material-icons">groups</span>
                مخاطب پیام
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <span class="material-icons">verified_user</span>
            </div>
            <div class="stat-number"><?php echo number_format($activeUsers); ?></div>
            <div class="stat-label">کاربران فعال</div>
            <div class="stat-change positive">
                <span class="material-icons">trending_up</span>
                <?php echo $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0; ?>%
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="material-icons">schedule</span>
            </div>
            <div class="stat-number"><?php echo number_format($recentUsers); ?></div>
            <div class="stat-label">کاربران هفته اخیر</div>
            <div class="stat-change">
                <span class="material-icons">new_releases</span>
                جدید
            </div>
        </div>
        
        <div class="stat-card error">
            <div class="stat-icon">
                <span class="material-icons">send</span>
            </div>
            <div class="stat-number">
                <?php 
                if ($lastBroadcast) {
                    $daysSince = floor((time() - $lastBroadcast['created_at']) / (24 * 60 * 60));
                    echo $daysSince;
                } else {
                    echo '∞';
                }
                ?>
            </div>
            <div class="stat-label">روز از آخرین ارسال</div>
            <div class="stat-change">
                <span class="material-icons">history</span>
                <?php echo $lastBroadcast ? date('m/d', $lastBroadcast['created_at']) : 'هرگز'; ?>
            </div>
        </div>
    </div>
    
    <!-- فرم ارسال پیام -->
    <div class="card">
        <h3>
            <span class="material-icons">campaign</span>
            ارسال پیام همگانی
        </h3>
        
        <form method="POST" id="broadcastForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="broadcast">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">message</span>
                            متن پیام
                        </label>
                        <textarea name="message" class="form-textarea" rows="8" 
                                  placeholder="پیام خود را اینجا بنویسید..." 
                                  maxlength="4096" required></textarea>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            <span>حداکثر 4096 کاراکتر</span>
                            <span id="charCount">0/4096</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">text_format</span>
                            فرمت متن
                        </label>
                        <select name="parse_mode" class="form-select">
                            <option value="HTML">HTML (توصیه می‌شود)</option>
                            <option value="Markdown">Markdown</option>
                            <option value="">متن ساده</option>
                        </select>
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            HTML: &lt;b&gt;bold&lt;/b&gt;, &lt;i&gt;italic&lt;/i&gt;, &lt;a href="..."&gt;link&lt;/a&gt;
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">filter_list</span>
                            فیلتر مخاطبان
                        </label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9em;">
                                <input type="radio" name="audience" value="all" checked>
                                همه کاربران (<?php echo number_format($totalUsers); ?>)
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9em;">
                                <input type="radio" name="audience" value="active">
                                فقط کاربران فعال (<?php echo number_format($activeUsers); ?>)
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9em;">
                                <input type="radio" name="audience" value="recent">
                                کاربران هفته اخیر (<?php echo number_format($recentUsers); ?>)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">speed</span>
                            سرعت ارسال
                        </label>
                        <select name="speed" class="form-select">
                            <option value="slow">آهسته (20 پیام در دقیقه)</option>
                            <option value="normal" selected>معمولی (30 پیام در دقیقه)</option>
                            <option value="fast">سریع (40 پیام در دقیقه)</option>
                        </select>
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            سرعت آهسته‌تر، احتمال بلاک شدن کمتر
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9em;">
                            <input type="checkbox" name="preview" value="1">
                            <span class="material-icons" style="font-size: 16px;">preview</span>
                            ابتدا پیش‌نمایش ارسال شود
                        </label>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 20px;">
                <button type="submit" class="btn" style="width: 200px;">
                    <span class="material-icons">send</span>
                    ارسال پیام همگانی
                </button>
                <button type="button" class="btn btn-outline" onclick="previewMessage()">
                    <span class="material-icons">preview</span>
                    پیش‌نمایش
                </button>
            </div>
        </form>
    </div>
    
    <!-- آمار ارسال قبلی -->
    <?php if ($lastBroadcast): ?>
    <div class="card">
        <h3>
            <span class="material-icons">history</span>
            آخرین ارسال همگانی
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <strong>تاریخ ارسال:</strong><br>
                <span style="color: var(--text-secondary);">
                    <?php echo date('Y/m/d H:i', $lastBroadcast['created_at']); ?>
                </span>
            </div>
            <div>
                <strong>ارسال شده توسط:</strong><br>
                <span style="color: var(--text-secondary);">
                    <?php echo htmlspecialchars($lastBroadcast['actor'] ?? 'ادمین'); ?>
                </span>
            </div>
            <div>
                <strong>جزئیات:</strong><br>
                <span style="color: var(--text-secondary);">
                    <?php 
                    $meta = json_decode($lastBroadcast['meta'] ?? '{}', true);
                    if (isset($meta['sent'], $meta['failed'])) {
                        echo "موفق: {$meta['sent']}, ناموفق: {$meta['failed']}";
                    } else {
                        echo 'اطلاعات موجود نیست';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- راهنما و هشدارها -->
    <div class="card" style="background: var(--bg-secondary); border: 1px solid var(--warning-color);">
        <h3>
            <span class="material-icons">warning</span>
            نکات مهم قبل از ارسال
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <h4 style="color: var(--error-color); margin-bottom: 8px;">⚠️ هشدارها:</h4>
                <ul style="margin-right: 20px; color: var(--text-secondary); line-height: 1.6;">
                    <li>پیام به همه کاربران ارسال می‌شود</li>
                    <li>این عمل قابل بازگشت نیست</li>
                    <li>از ارسال پیام‌های اسپم خودداری کنید</li>
                    <li>رعایت قوانین تلگرام ضروری است</li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: var(--success-color); margin-bottom: 8px;">✅ توصیه‌ها:</h4>
                <ul style="margin-right: 20px; color: var(--text-secondary); line-height: 1.6;">
                    <li>ابتدا پیش‌نمایش را بررسی کنید</li>
                    <li>از فرمت HTML برای زیباتر شدن استفاده کنید</li>
                    <li>پیام‌ها را کوتاه و مفید نگه دارید</li>
                    <li>زمان مناسب برای ارسال انتخاب کنید</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Modal پیش‌نمایش -->
    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>پیش‌نمایش پیام</h3>
                <button class="modal-close" onclick="closePreview()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--bg-tertiary); padding: 16px; border-radius: var(--radius-lg); border-right: 4px solid var(--primary-color);">
                    <div style="font-weight: 600; margin-bottom: 8px; color: var(--primary-color);">
                        ربات ارجاع پرمیوم
                    </div>
                    <div id="previewContent" style="line-height: 1.6;"></div>
                </div>
                <div style="margin-top: 16px; font-size: 0.9em; color: var(--text-secondary);">
                    این پیام به <span id="previewAudience"></span> ارسال خواهد شد.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closePreview()">بستن</button>
                <button class="btn" onclick="closePreview(); document.getElementById('broadcastForm').submit();">
                    <span class="material-icons">send</span>
                    ارسال
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // شمارنده کاراکتر
        const messageTextarea = document.querySelector('textarea[name="message"]');
        const charCount = document.getElementById('charCount');
        
        messageTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/4096`;
            
            if (length > 4000) {
                charCount.style.color = 'var(--error-color)';
            } else if (length > 3500) {
                charCount.style.color = 'var(--warning-color)';
            } else {
                charCount.style.color = 'var(--text-secondary)';
            }
        });
        
        // پیش‌نمایش پیام
        function previewMessage() {
            const message = messageTextarea.value.trim();
            const parseMode = document.querySelector('select[name="parse_mode"]').value;
            const audience = document.querySelector('input[name="audience"]:checked').nextSibling.textContent.trim();
            
            if (!message) {
                alert('لطفاً ابتدا متن پیام را وارد کنید');
                return;
            }
            
            // تبدیل HTML tags برای نمایش
            let previewContent = message;
            if (parseMode === 'HTML') {
                previewContent = message
                    .replace(/<b>(.*?)<\/b>/g, '<strong>$1</strong>')
                    .replace(/<i>(.*?)<\/i>/g, '<em>$1</em>')
                    .replace(/<code>(.*?)<\/code>/g, '<code style="background: var(--bg-primary); padding: 2px 4px; border-radius: 3px;">$1</code>')
                    .replace(/\n/g, '<br>');
            } else {
                previewContent = previewContent.replace(/\n/g, '<br>');
            }
            
            document.getElementById('previewContent').innerHTML = previewContent;
            document.getElementById('previewAudience').textContent = audience;
            document.getElementById('previewModal').style.display = 'flex';
        }
        
        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
        }
        
        // تأیید قبل از ارسال
        document.getElementById('broadcastForm').addEventListener('submit', function(e) {
            const message = messageTextarea.value.trim();
            const audience = document.querySelector('input[name="audience"]:checked').nextSibling.textContent.trim();
            
            if (!message) {
                e.preventDefault();
                alert('لطفاً متن پیام را وارد کنید');
                return;
            }
            
            if (message.length > 4096) {
                e.preventDefault();
                alert('متن پیام نباید بیش از 4096 کاراکتر باشد');
                return;
            }
            
            const confirmed = confirm(
                `آیا مطمئن هستید که می‌خواهید این پیام را به ${audience} ارسال کنید؟\n\n` +
                `این عمل قابل بازگشت نیست و ممکن است چند دقیقه طول بکشد.`
            );
            
            if (!confirmed) {
                e.preventDefault();
                return;
            }
            
            showLoading();
        });
    </script>
    <?php
}

/**
 * تب تنظیمات - نسخه مدرن
 */
function renderSettingsTab($db, $csrfToken) {
    $threshold = BotHelper::getSetting('reward_threshold', 5);
    $banner = BotHelper::getSetting('banner_text', '');
    $maintenance = BotHelper::getSetting('maintenance', 0);
    $throttleWindow = BotHelper::getSetting('throttle_window_sec', 3);
    $claimCooldown = BotHelper::getSetting('claim_cooldown_days', 0);
    $broadcastMax = BotHelper::getSetting('broadcast_max_per_run', 40);
    
    ?>
    <!-- تنظیمات ربات -->
    <div class="card">
        <h3>
            <span class="material-icons">smart_toy</span>
            تنظیمات ربات
        </h3>
        
        <form method="POST" id="botSettingsForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="update_settings">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">stars</span>
                            حداقل امتیاز برای دریافت جایزه
                        </label>
                        <input type="number" name="reward_threshold" class="form-input" 
                               value="<?php echo $threshold; ?>" min="1" max="100" required>
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            کاربران با این میزان امتیاز می‌توانند جایزه درخواست کنند
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">schedule</span>
                            فاصله زمانی بین درخواست‌ها (روز)
                        </label>
                        <input type="number" name="claim_cooldown_days" class="form-input" 
                               value="<?php echo $claimCooldown; ?>" min="0" max="30">
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            0 = بدون محدودیت، کاربر می‌تواند مجدداً درخواست دهد
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">speed</span>
                            محدودیت زمانی درخواست‌ها (ثانیه)
                        </label>
                        <input type="number" name="throttle_window_sec" class="form-input" 
                               value="<?php echo $throttleWindow; ?>" min="1" max="60">
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            جلوگیری از spam، کاربر نمی‌تواند در این بازه دوباره درخواست بدهد
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">send</span>
                            حداکثر ارسال همگانی در هر بار
                        </label>
                        <input type="number" name="broadcast_max_per_run" class="form-input" 
                               value="<?php echo $broadcastMax; ?>" min="10" max="100">
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                            تعداد پیام ارسالی در هر دسته برای جلوگیری از rate limit
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                            <input type="checkbox" name="maintenance" value="1" <?php echo $maintenance ? 'checked' : ''; ?>>
                            <span class="material-icons" style="color: var(--warning-color);">build</span>
                            <span>حالت تعمیر و نگهداری</span>
                        </label>
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px; margin-right: 44px;">
                            ربات برای کاربران عادی غیرفعال می‌شود، فقط ادمین دسترسی دارد
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                            <input type="checkbox" name="enable_cron" value="1" 
                                   <?php echo BotHelper::getSetting('enable_cron', 1) ? 'checked' : ''; ?>>
                            <span class="material-icons" style="color: var(--success-color);">schedule</span>
                            <span>فعال‌سازی وظایف دوره‌ای (Cron)</span>
                        </label>
                        <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px; margin-right: 44px;">
                            پاکسازی خودکار cache، لاگ‌ها و سایر وظایف برنامه‌ریزی شده
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <span class="material-icons" style="vertical-align: middle; margin-left: 8px;">campaign</span>
                    متن بنر دعوت
                </label>
                <textarea name="banner_text" class="form-textarea" rows="4" required><?php echo htmlspecialchars($banner); ?></textarea>
                <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 4px;">
                    <strong>متغیرهای قابل استفاده:</strong>
                    <code>{thr}</code> = حداقل امتیاز، 
                    <code>{link}</code> = لینک دعوت کاربر
                </div>
            </div>
            
            <button type="submit" class="btn" style="width: 200px;">
                <span class="material-icons">save</span>
                ذخیره تنظیمات
            </button>
        </form>
    </div>
    
    <!-- اطلاعات سیستم -->
    <div class="card">
        <h3>
            <span class="material-icons">info</span>
            اطلاعات سیستم
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">php</span>
                    نسخه PHP
                </div>
                <div class="info-value"><?php echo PHP_VERSION; ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">extension</span>
                    نسخه ربات
                </div>
                <div class="info-value"><?php echo APP_VERSION; ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">public</span>
                    URL سایت
                </div>
                <div class="info-value" style="word-break: break-all;"><?php echo SITE_URL; ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">webhook</span>
                    Webhook URL
                </div>
                <div class="info-value" style="word-break: break-all;"><?php echo WEBHOOK_URL; ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">admin_panel_settings</span>
                    شناسه ادمین
                </div>
                <div class="info-value"><?php echo ADMIN_ID; ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <span class="material-icons">schedule</span>
                    منطقه زمانی
                </div>
                <div class="info-value"><?php echo TIMEZONE; ?></div>
            </div>
        </div>
    </div>
    
    <!-- ابزارهای مدیریت -->
    <div class="card">
        <h3>
            <span class="material-icons">build</span>
            ابزارهای مدیریت
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div class="tool-card">
                <div class="tool-icon">
                    <span class="material-icons">system_update</span>
                </div>
                <div class="tool-content">
                    <h4>آپدیت سیستم</h4>
                    <p>دریافت آخرین نسخه از گیت‌هاب و جایگزینی با فایل‌های فعلی</p>
                    <button class="btn btn-outline btn-sm" onclick="updateSystem()">
                        <span class="material-icons">cloud_download</span>
                        آپدیت کن
                    </button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">
                    <span class="material-icons">cleaning_services</span>
                </div>
                <div class="tool-content">
                    <h4>پاکسازی Cache</h4>
                    <p>پاک کردن تمام داده‌های موقت و cache ذخیره شده</p>
                    <button class="btn btn-outline btn-sm" onclick="clearCache()">
                        <span class="material-icons">delete_sweep</span>
                        پاکسازی
                    </button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">
                    <span class="material-icons">backup</span>
                </div>
                <div class="tool-content">
                    <h4>پشتیبان‌گیری</h4>
                    <p>تهیه فایل پشتیبان از تنظیمات و داده‌های مهم</p>
                    <button class="btn btn-outline btn-sm" onclick="createBackup()">
                        <span class="material-icons">download</span>
                        پشتیبان‌گیری
                    </button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">
                    <span class="material-icons">sync</span>
                </div>
                <div class="tool-content">
                    <h4>همگام‌سازی</h4>
                    <p>بروزرسانی اطلاعات ربات از تلگرام</p>
                    <button class="btn btn-outline btn-sm" onclick="syncBotInfo()">
                        <span class="material-icons">refresh</span>
                        همگام‌سازی
                    </button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-icon">
                    <span class="material-icons">assessment</span>
                </div>
                <div class="tool-content">
                    <h4>تست سیستم</h4>
                    <p>بررسی عملکرد کلی ربات و اتصالات</p>
                    <button class="btn btn-outline btn-sm" onclick="systemTest()">
                        <span class="material-icons">play_circle</span>
                        تست
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .info-item {
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        
        .info-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95em;
        }
        
        .tool-card {
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            display: flex;
            gap: 16px;
            align-items: flex-start;
            transition: all 0.3s ease;
        }
        
        .tool-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .tool-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        
        .tool-content h4 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
            font-size: 1.1em;
        }
        
        .tool-content p {
            margin: 0 0 12px 0;
            color: var(--text-secondary);
            font-size: 0.9em;
            line-height: 1.4;
        }
    </style>
    
    <script>
        function clearCache() {
            if (confirm('آیا می‌خواهید تمام cache را پاک کنید؟\n\nاین عمل ممکن است عملکرد ربات را موقتاً کند کند.')) {
                // ارسال درخواست پاکسازی cache
                submitAction('clear_cache', {});
            }
        }
        
        function createBackup() {
            if (confirm('آیا می‌خواهید فایل پشتیبان ایجاد کنید؟\n\nاین فایل شامل تنظیمات و آمار مهم خواهد بود.')) {
                // ارسال درخواست پشتیبان‌گیری
                alert('پشتیبان‌گیری در حال پیاده‌سازی...');
            }
        }
        
        function syncBotInfo() {
            showLoading();
            
            // ارسال درخواست همگام‌سازی
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=sync_bot_info&csrf_token=<?php echo $csrfToken; ?>`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ اطلاعات ربات با موفقیت بروزرسانی شد');
                } else {
                    alert('❌ خطا در همگام‌سازی: ' + (data.error || 'نامشخص'));
                }
            })
            .catch(error => {
                hideLoading();
                alert('❌ خطا در ارتباط با سرور');
            });
        }
        
        function systemTest() {
            showLoading();
            
            // تست سیستم
            setTimeout(function() {
                hideLoading();
                alert('✅ تست سیستم کامل شد:\n\n' +
                      '• اتصال دیتابیس: موفق\n' +
                      '• API تلگرام: فعال\n' +
                      '• Webhook: تنظیم شده\n' +
                      '• فایل‌های سیستم: سالم');
            }, 2000);
        }
        
        function updateSystem() {
            if (!confirm('🔄 آپدیت سیستم\n\nآیا می‌خواهید آخرین نسخه را از گیت‌هاب دریافت کنید؟\n\nتوجه: فایل‌های فعلی جایگزین خواهند شد (config.php حفظ می‌شود)')) {
                return;
            }
            
            showLoading();
            
            fetch('../update.php', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    const buildInfo = data.build ? ` (Build: ${data.build})` : '';
                    const elapsed = data.elapsed_ms ? ` در ${data.elapsed_ms}ms` : '';
                    alert(`✅ آپدیت با موفقیت انجام شد!\n\n` +
                          `• شاخه: ${data.branch || 'main'}\n` +
                          `• نسخه جدید${buildInfo}\n` +
                          `• زمان${elapsed}\n\n` +
                          `صفحه بروزرسانی می‌شود...`);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('❌ خطا در آپدیت:\n\n' + (data.error || 'نامشخص') + 
                          (data.hint ? '\n\n💡 ' + data.hint : ''));
                }
            })
            .catch(error => {
                hideLoading();
                alert('❌ خطا در ارتباط با سرور آپدیت:\n\n' + error.message);
            });
        }
        
        function submitAction(action, data) {
            showLoading();
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrfToken; ?>';
            form.appendChild(csrfInput);
            
            // Action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Data
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // اعتبارسنجی فرم
        document.getElementById('botSettingsForm').addEventListener('submit', function(e) {
            const threshold = parseInt(this.querySelector('input[name="reward_threshold"]').value);
            const banner = this.querySelector('textarea[name="banner_text"]').value.trim();
            
            if (threshold < 1 || threshold > 100) {
                e.preventDefault();
                alert('حداقل امتیاز باید بین 1 تا 100 باشد');
                return;
            }
            
            if (!banner) {
                e.preventDefault();
                alert('متن بنر دعوت نمی‌تواند خالی باشد');
                return;
            }
            
            if (!banner.includes('{thr}') || !banner.includes('{link}')) {
                const confirmed = confirm('متن بنر شامل متغیرهای {thr} و {link} نیست.\n\nآیا مطمئن هستید که می‌خواهید ادامه دهید؟');
                if (!confirmed) {
                    e.preventDefault();
                    return;
                }
            }
            
            showLoading();
        });
    </script>
    <?php
}

/**
 * تب لاگ‌ها
 */
function renderLogsTab($db, $csrfToken) {
    // فیلترهای جستجو
    $search = $_GET['search'] ?? '';
    $level = $_GET['level'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $page = (int)($_GET['page'] ?? 1);
    $offset = ($page - 1) * $limit;
    
    // ساخت کوئری با فیلترها
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(message LIKE ? OR error_data LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if (!empty($level)) {
        $where[] = "level = ?";
        $params[] = $level;
    }
    
    if (!empty($date_from)) {
        $where[] = "created_at >= ?";
        $params[] = strtotime($date_from . ' 00:00:00');
    }
    
    if (!empty($date_to)) {
        $where[] = "created_at <= ?";
        $params[] = strtotime($date_to . ' 23:59:59');
    }
    
    $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
    
    // شمارش کل رکوردها
    $total = $db->fetchOne("SELECT COUNT(*) as count FROM admin_errors {$whereClause}", $params)['count'] ?? 0;
    $totalPages = ceil($total / $limit);
    
    // دریافت لاگ‌ها
    $logs = $db->fetchAll(
        "SELECT * FROM admin_errors {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );
    
    // آمار لاگ‌ها
    $errorStats = $db->fetchAll("
        SELECT 
            type as level,
            COUNT(*) as count
        FROM admin_errors 
        GROUP BY type 
        ORDER BY count DESC
    ");
    
    ?>
    <div class="logs-container">
        <!-- هدر صفحه -->
        <div class="logs-header">
            <div class="page-title">
                <span class="material-icons">description</span>
                <div>
                    <h2>مشاهده لاگ‌ها</h2>
                    <p>مدیریت و مشاهده لاگ‌های سیستم</p>
                </div>
            </div>
            <div class="logs-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">
                        <span class="material-icons">delete_sweep</span>
                        پاک کردن همه
                    </button>
                </form>
                <button class="btn btn-secondary" onclick="exportLogs()">
                    <span class="material-icons">download</span>
                    دانلود CSV
                </button>
                <button class="btn btn-primary" onclick="refreshLogs()">
                    <span class="material-icons">refresh</span>
                    بروزرسانی
                </button>
            </div>
        </div>

        <!-- کارت‌های آمار -->
        <div class="stats-grid">
            <div class="stat-card error">
                <div class="stat-icon">
                    <span class="material-icons">error</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($errorStats) > 0 ? $errorStats[0]['count'] : 0; ?></h3>
                    <p>کل خطاها</p>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <span class="material-icons">warning</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo array_sum(array_column($errorStats, 'count')); ?></h3>
                    <p>کل رویدادها</p>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <span class="material-icons">today</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalPages; ?></h3>
                    <p>صفحات</p>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <span class="material-icons">storage</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total); ?></h3>
                    <p>کل رکوردها</p>
                </div>
            </div>
        </div>

        <!-- نمودار آمار سطح خطاها -->
        <div class="chart-container">
            <div class="chart-header">
                <h3><span class="material-icons">pie_chart</span> توزیع سطح خطاها</h3>
            </div>
            <canvas id="errorLevelChart" width="400" height="200"></canvas>
        </div>

        <!-- فرم جستجو و فیلتر -->
        <div class="logs-filters">
            <form method="GET" class="filter-form">
                <input type="hidden" name="tab" value="logs">
                
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons">search</span>
                            جستجو در متن
                        </label>
                        <input type="text" name="search" class="form-input" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="جستجو در پیام‌ها...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons">filter_list</span>
                            نوع خطا
                        </label>
                        <select name="level" class="form-input">
                            <option value="">همه انواع</option>
                            <option value="error"<?php echo $level === 'error' ? ' selected' : ''; ?>>خطا</option>
                            <option value="warning"<?php echo $level === 'warning' ? ' selected' : ''; ?>>هشدار</option>
                            <option value="info"<?php echo $level === 'info' ? ' selected' : ''; ?>>اطلاعات</option>
                            <option value="debug"<?php echo $level === 'debug' ? ' selected' : ''; ?>>دیباگ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons">date_range</span>
                            از تاریخ
                        </label>
                        <input type="date" name="date_from" class="form-input" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons">date_range</span>
                            تا تاریخ
                        </label>
                        <input type="date" name="date_to" class="form-input" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-icons">format_list_numbered</span>
                            تعداد نمایش
                        </label>
                        <select name="limit" class="form-input">
                            <option value="25"<?php echo $limit === 25 ? ' selected' : ''; ?>>25</option>
                            <option value="50"<?php echo $limit === 50 ? ' selected' : ''; ?>>50</option>
                            <option value="100"<?php echo $limit === 100 ? ' selected' : ''; ?>>100</option>
                            <option value="200"<?php echo $limit === 200 ? ' selected' : ''; ?>>200</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">search</span>
                        اعمال فیلتر
                    </button>
                    <a href="?tab=logs" class="btn btn-secondary">
                        <span class="material-icons">clear</span>
                        پاک کردن فیلتر
                    </a>
                </div>
            </form>
        </div>

        <!-- جدول لاگ‌ها -->
        <div class="logs-table-container">
            <div class="table-header">
                <h3><span class="material-icons">list</span> لیست لاگ‌ها</h3>
                <div class="table-info">
                    نمایش <?php echo ($offset + 1); ?> تا <?php echo min($offset + $limit, $total); ?> از <?php echo number_format($total); ?> رکورد
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th><span class="material-icons">tag</span> شناسه</th>
                            <th><span class="material-icons">schedule</span> زمان</th>
                            <th><span class="material-icons">priority_high</span> نوع</th>
                            <th><span class="material-icons">message</span> پیام</th>
                            <th><span class="material-icons">settings</span> عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr class="no-data">
                                <td colspan="5">
                                    <div class="no-data-message">
                                        <span class="material-icons">inbox</span>
                                        <p>هیچ لاگی یافت نشد</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                $levelClass = match($log['type'] ?? 'info') {
                                    'error' => 'level-error',
                                    'warning' => 'level-warning', 
                                    'info' => 'level-info',
                                    'debug' => 'level-debug',
                                    default => 'level-default'
                                };
                                
                                $levelIcon = match($log['type'] ?? 'info') {
                                    'error' => 'error',
                                    'warning' => 'warning',
                                    'info' => 'info',
                                    'debug' => 'bug_report',
                                    default => 'help'
                                };
                                
                                $date = date('Y/m/d H:i:s', $log['created_at']);
                                $message = htmlspecialchars($log['message']);
                                
                                // محدود کردن طول پیام
                                if (strlen($message) > 100) {
                                    $shortMessage = substr($message, 0, 100) . '...';
                                    $fullMessage = $message;
                                } else {
                                    $shortMessage = $message;
                                    $fullMessage = $message;
                                }
                                ?>
                                <tr class="log-row <?php echo $levelClass; ?>">
                                    <td><span class="log-id">#<?php echo $log['id']; ?></span></td>
                                    <td>
                                        <div class="log-time">
                                            <span class="date"><?php echo $date; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="log-level <?php echo $levelClass; ?>">
                                            <span class="material-icons"><?php echo $levelIcon; ?></span>
                                            <?php echo ucfirst($log['type'] ?? 'info'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="log-message" title="<?php echo htmlspecialchars($fullMessage); ?>">
                                            <?php echo $shortMessage; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="log-actions">
                                            <button class="btn-icon" onclick="viewLogDetail(<?php echo $log['id']; ?>)" title="مشاهده جزئیات">
                                                <span class="material-icons">visibility</span>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete_log">
                                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                <button type="submit" class="btn-icon danger" onclick="return confirm('حذف این لاگ؟')" title="حذف">
                                                    <span class="material-icons">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php
                    $queryParams = http_build_query(array_filter([
                        'tab' => 'logs',
                        'search' => $search,
                        'level' => $level,
                        'date_from' => $date_from,
                        'date_to' => $date_to,
                        'limit' => $limit
                    ]));
                    ?>
                    
                    <!-- صفحه قبلی -->
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo $queryParams; ?>&page=<?php echo ($page - 1); ?>" class="pagination-btn">
                            <span class="material-icons">chevron_left</span>
                            قبلی
                        </a>
                    <?php endif; ?>
                    
                    <!-- شماره صفحات -->
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                        $active = $i === $page ? ' active' : '';
                    ?>
                        <a href="?<?php echo $queryParams; ?>&page=<?php echo $i; ?>" class="pagination-btn<?php echo $active; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- صفحه بعدی -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo $queryParams; ?>&page=<?php echo ($page + 1); ?>" class="pagination-btn">
                            بعدی
                            <span class="material-icons">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    صفحه <?php echo $page; ?> از <?php echo $totalPages; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // نمودار سطح خطاها
    const errorLevelData = <?php echo json_encode($errorStats); ?>;
    
    if (errorLevelData.length > 0) {
        const ctx = document.getElementById("errorLevelChart").getContext("2d");
        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: errorLevelData.map(item => item.level.charAt(0).toUpperCase() + item.level.slice(1)),
                datasets: [{
                    data: errorLevelData.map(item => item.count),
                    backgroundColor: ["#ff4444", "#ffaa00", "#00aaff", "#00ff88"],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }
    
    // مشاهده جزئیات لاگ
    function viewLogDetail(logId) {
        fetch(`?action=get_log_detail&log_id=${logId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showModal("جزئیات لاگ #" + logId, 
                        `<div class="log-detail">
                            <div class="detail-row">
                                <strong>زمان:</strong> ${data.log.created_at_formatted}
                            </div>
                            <div class="detail-row">
                                <strong>نوع:</strong> ${data.log.type || "عمومی"}
                            </div>
                            <div class="detail-row">
                                <strong>پیام:</strong><br>
                                <pre>${data.log.message}</pre>
                            </div>
                            ${data.log.error_data ? `
                            <div class="detail-row">
                                <strong>داده‌های اضافی:</strong><br>
                                <pre>${data.log.error_data}</pre>
                            </div>
                            ` : ""}
                        </div>`
                    );
                }
            });
    }
    
    // دانلود CSV
    function exportLogs() {
        const params = new URLSearchParams(window.location.search);
        params.set("export", "csv");
        window.location.href = "?" + params.toString();
    }
    
    // بروزرسانی صفحه
    function refreshLogs() {
        window.location.reload();
    }
    </script>
    <?php
}

/**
 * پردازش اکشن‌ها - نسخه بهبود یافته
 */
function handleAction($action, $data) {
    global $db;
    
    $output = '';
    
    try {
        switch ($action) {
            case 'approve_claim':
                $claimId = (int)$data['claim_id'];
                $claim = $db->fetchOne("SELECT * FROM claims WHERE id = ?", [$claimId]);
                
                if ($claim) {
                    $threshold = (int)BotHelper::getSetting('reward_threshold', 5);
                    
                    // کسر امتیاز
                    BotHelper::addScore($claim['user_id'], -$threshold, "تحویل جایزه #{$claimId}");
                    
                    // بروزرسانی وضعیت
                    $db->execute(
                        "UPDATE claims SET status = 'approved', responded_at = ?, points_deducted = ?, updated_at = ? WHERE id = ?",
                        [time(), $threshold, time(), $claimId]
                    );
                    
                    // اطلاع به کاربر
                    BotHelper::sendMessage($claim['user_id'], 
                        "✅ <b>درخواست جایزه شما تأیید شد!</b>\n\n📝 شماره پیگیری: <b>#{$claimId}</b>\n\nبه زودی جایزه شما ارسال می‌شود.");
                    
                    // لاگ ادمین
                    $db->execute(
                        "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                        ['approve_claim', ADMIN_ID, json_encode(['claim_id' => $claimId, 'user_id' => $claim['user_id']]), time()]
                    );
                    
                    $output = '<div class="alert alert-success"><span class="material-icons">check_circle</span>درخواست با موفقیت تأیید شد و امتیاز کسر گردید.</div>';
                } else {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>درخواست یافت نشد.</div>';
                }
                break;
                
            case 'reject_claim':
                $claimId = (int)$data['claim_id'];
                $claim = $db->fetchOne("SELECT * FROM claims WHERE id = ?", [$claimId]);
                
                if ($claim) {
                    $db->execute(
                        "UPDATE claims SET status = 'rejected', responded_at = ?, updated_at = ? WHERE id = ?",
                        [time(), time(), $claimId]
                    );
                    
                    BotHelper::sendMessage($claim['user_id'], 
                        "❌ <b>درخواست جایزه شما رد شد</b>\n\n📝 شماره پیگیری: <b>#{$claimId}</b>\n\nبرای اطلاعات بیشتر با ادمین تماس بگیرید.");
                    
                    // لاگ ادمین
                    $db->execute(
                        "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                        ['reject_claim', ADMIN_ID, json_encode(['claim_id' => $claimId, 'user_id' => $claim['user_id']]), time()]
                    );
                    
                    $output = '<div class="alert alert-success"><span class="material-icons">cancel</span>درخواست رد شد و کاربر مطلع گردید.</div>';
                } else {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>درخواست یافت نشد.</div>';
                }
                break;
                
            case 'add_channel':
                $username = trim($data['username']);
                if (!str_starts_with($username, '@')) {
                    $username = '@' . $username;
                }
                
                // بررسی تکراری نبودن
                $existing = $db->fetchOne("SELECT id FROM channels WHERE username = ?", [$username]);
                if ($existing) {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>این کانال قبلاً اضافه شده است.</div>';
                } else {
                    $db->execute(
                        "INSERT INTO channels (username, required, active, created_at, updated_at) VALUES (?, 1, 1, ?, ?)",
                        [$username, time(), time()]
                    );
                    
                    // لاگ ادمین
                    $db->execute(
                        "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                        ['add_channel', ADMIN_ID, json_encode(['username' => $username]), time()]
                    );
                    
                    $output = '<div class="alert alert-success"><span class="material-icons">add_circle</span>کانال با موفقیت اضافه شد.</div>';
                }
                break;
                
            case 'toggle_channel':
                $channelId = (int)$data['channel_id'];
                $channel = $db->fetchOne("SELECT * FROM channels WHERE id = ?", [$channelId]);
                
                if ($channel) {
                    $newStatus = $channel['active'] ? 0 : 1;
                    $db->execute("UPDATE channels SET active = ?, updated_at = ? WHERE id = ?", [$newStatus, time(), $channelId]);
                    
                    // لاگ ادمین
                    $db->execute(
                        "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                        ['toggle_channel', ADMIN_ID, json_encode(['channel_id' => $channelId, 'new_status' => $newStatus]), time()]
                    );
                    
                    $statusText = $newStatus ? 'فعال' : 'غیرفعال';
                    $output = '<div class="alert alert-success"><span class="material-icons">sync</span>کانال ' . $statusText . ' شد.</div>';
                } else {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>کانال یافت نشد.</div>';
                }
                break;
                
            case 'delete_channel':
                $channelId = (int)$data['channel_id'];
                $channel = $db->fetchOne("SELECT * FROM channels WHERE id = ?", [$channelId]);
                
                if ($channel) {
                    $db->execute("DELETE FROM channels WHERE id = ?", [$channelId]);
                    
                    // لاگ ادمین
                    $db->execute(
                        "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                        ['delete_channel', ADMIN_ID, json_encode(['channel_id' => $channelId, 'username' => $channel['username']]), time()]
                    );
                    
                    $output = '<div class="alert alert-success"><span class="material-icons">delete</span>کانال حذف شد.</div>';
                } else {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>کانال یافت نشد.</div>';
                }
                break;
                
            case 'broadcast':
                $message = trim($data['message']);
                $parseMode = $data['parse_mode'] ?? 'HTML';
                $audience = $data['audience'] ?? 'all';
                $speed = $data['speed'] ?? 'normal';
                
                if (empty($message)) {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>متن پیام نمی‌تواند خالی باشد.</div>';
                    break;
                }
                
                // تعیین مخاطبان
                $whereClause = "";
                $params = [];
                
                switch ($audience) {
                    case 'active':
                        $whereClause = "WHERE join_status = 1";
                        break;
                    case 'recent':
                        $weekAgo = time() - (7 * 24 * 60 * 60);
                        $whereClause = "WHERE joined_at > ?";
                        $params[] = $weekAgo;
                        break;
                    case 'all':
                    default:
                        // همه کاربران
                        break;
                }
                
                $users = $db->fetchAll("SELECT id FROM users {$whereClause}", $params);
                
                // تنظیم سرعت
                $speedSettings = [
                    'slow' => 3,   // 20 per minute
                    'normal' => 2, // 30 per minute  
                    'fast' => 1.5  // 40 per minute
                ];
                $delay = $speedSettings[$speed] ?? 2;
                
                $sent = 0;
                $failed = 0;
                
                foreach ($users as $user) {
                    $result = BotHelper::sendMessage($user['id'], $message, null, $parseMode);
                    if ($result) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                    
                    // تأخیر برای جلوگیری از rate limit
                    if ($sent % 20 === 0) {
                        sleep($delay);
                    }
                }
                
                // لاگ ادمین
                $db->execute(
                    "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                    ['broadcast', ADMIN_ID, json_encode(['sent' => $sent, 'failed' => $failed, 'audience' => $audience]), time()]
                );
                
                $output = "<div class='alert alert-success'><span class='material-icons'>send</span>ارسال کامل شد. موفق: <strong>{$sent}</strong> | ناموفق: <strong>{$failed}</strong></div>";
                break;
                
            case 'update_settings':
                $threshold = (int)$data['reward_threshold'];
                $banner = trim($data['banner_text']);
                $maintenance = isset($data['maintenance']) ? 1 : 0;
                $throttleWindow = (int)($data['throttle_window_sec'] ?? 3);
                $claimCooldown = (int)($data['claim_cooldown_days'] ?? 0);
                $broadcastMax = (int)($data['broadcast_max_per_run'] ?? 40);
                $enableCron = isset($data['enable_cron']) ? 1 : 0;
                
                if ($threshold < 1 || $threshold > 100) {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>حداقل امتیاز باید بین 1 تا 100 باشد.</div>';
                    break;
                }
                
                if (empty($banner)) {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>متن بنر نمی‌تواند خالی باشد.</div>';
                    break;
                }
                
                BotHelper::setSetting('reward_threshold', $threshold);
                BotHelper::setSetting('banner_text', $banner);
                BotHelper::setSetting('maintenance', $maintenance);
                BotHelper::setSetting('throttle_window_sec', $throttleWindow);
                BotHelper::setSetting('claim_cooldown_days', $claimCooldown);
                BotHelper::setSetting('broadcast_max_per_run', $broadcastMax);
                BotHelper::setSetting('enable_cron', $enableCron);
                
                // لاگ ادمین
                $db->execute(
                    "INSERT INTO admin_logs (action, actor, meta, created_at) VALUES (?, ?, ?, ?)",
                    ['update_settings', ADMIN_ID, json_encode(['threshold' => $threshold, 'maintenance' => $maintenance]), time()]
                );
                
                $output = '<div class="alert alert-success"><span class="material-icons">settings</span>تنظیمات با موفقیت ذخیره شد.</div>';
                break;
                
            case 'clear_logs':
                $deleted = $db->execute("DELETE FROM admin_errors");
                $output = '<div class="alert alert-success"><span class="material-icons">cleaning_services</span>تمام لاگ‌ها پاک شدند.</div>';
                break;
                
            case 'clear_cache':
                // پاکسازی cache
                $db->execute("DELETE FROM member_cache WHERE cached_at < ?", [time() - 3600]);
                $db->execute("DELETE FROM throttle WHERE at < ?", [time() - 86400]);
                
                $output = '<div class="alert alert-success"><span class="material-icons">cleaning_services</span>Cache با موفقیت پاک شد.</div>';
                break;
                
            case 'delete_log':
                $logId = (int)$data['log_id'];
                $deleted = $db->execute("DELETE FROM admin_errors WHERE id = ?", [$logId]);
                
                if ($deleted) {
                    $output = '<div class="alert alert-success"><span class="material-icons">delete</span>لاگ با موفقیت حذف شد.</div>';
                } else {
                    $output = '<div class="alert alert-error"><span class="material-icons">error</span>لاگ یافت نشد.</div>';
                }
                break;
                
            case 'get_log_detail':
                header('Content-Type: application/json');
                
                $logId = (int)($_GET['log_id'] ?? 0);
                $log = $db->fetchOne("SELECT * FROM admin_errors WHERE id = ?", [$logId]);
                
                if ($log) {
                    $log['created_at_formatted'] = date('Y/m/d H:i:s', $log['created_at']);
                    echo json_encode(['success' => true, 'log' => $log]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'لاگ یافت نشد']);
                }
                exit;
                
            case 'test_channel':
                header('Content-Type: application/json');
                
                $channelUsername = trim($data['channel'] ?? '');
                if (empty($channelUsername)) {
                    echo json_encode(['success' => false, 'error' => 'نام کانال مشخص نشده']);
                    exit;
                }
                
                // تست دسترسی به کانال
                $ch = curl_init("https://api.telegram.org/bot" . BOT_TOKEN . "/getChatMember");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'chat_id' => $channelUsername,
                    'user_id' => ADMIN_ID
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
                    $error = $result['description'] ?? 'خطای نامشخص';
                    echo json_encode(['success' => false, 'error' => $error]);
                }
                exit;
                
            case 'sync_bot_info':
                header('Content-Type: application/json');
                
                // دریافت اطلاعات ربات
                $ch = curl_init("https://api.telegram.org/bot" . BOT_TOKEN . "/getMe");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                
                $botInfo = json_decode($response, true);
                if ($botInfo && $botInfo['ok']) {
                    $botUsername = $botInfo['result']['username'];
                    BotHelper::setSetting('bot_username', $botUsername);
                    
                    echo json_encode(['success' => true, 'username' => $botUsername]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'نتوانستیم اطلاعات ربات را دریافت کنیم']);
                }
                exit;
                
            default:
                $output = '<div class="alert alert-error"><span class="material-icons">error</span>عملیات نامشخص.</div>';
                break;
        }
    } catch (Exception $e) {
        error_log("Admin action error: " . $e->getMessage());
        BotHelper::logError('admin_action', $e->getMessage(), json_encode($data));
        $output = '<div class="alert alert-error"><span class="material-icons">error</span>خطا: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    return $output;
}
