<?php
/**
 * Premium Referral Bot - PHP Version
 * Configuration File Template
 * 
 * این فایل توسط نصب‌کننده خودکار ایجاد می‌شود
 * هرگز این فایل را در گیت commit نکنید!
 */

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// تنظیمات تلگرام
define('BOT_TOKEN', 'your_bot_token_here');
define('ADMIN_ID', 'your_telegram_user_id');
define('ADMIN_KEY', 'your_admin_panel_password');

// کانال اجباری (اختیاری)
define('CHANNEL_USERNAME', ''); // مثال: @yourchannel

// Webhook Secret (اختیاری - برای امنیت بیشتر)
define('WEBHOOK_SECRET', ''); // یک رشته تصادفی

// تنظیمات سایت
define('SITE_URL', 'https://yourdomain.com'); // URL کامل سایت شما
define('WEBHOOK_URL', SITE_URL . '/webhook.php');

// تنظیمات امنیتی
define('SESSION_LIFETIME', 86400 * 30); // 30 روز
define('CSRF_TOKEN_LENGTH', 32);

// تنظیمات عمومی
define('TIMEZONE', 'Asia/Tehran');
// نسخه برنامه از فایل VERSION و BUILD خوانده می‌شود تا پس از هر آپدیت به‌روز باشد
if (!function_exists('app_version')) {
	function app_version() {
		$base = @file_get_contents(__DIR__ . '/VERSION');
	$base = $base ? trim($base) : '2.1.0';
		$build = @file_get_contents(__DIR__ . '/BUILD');
		$build = $build ? (int)trim($build) : 0;
		return $build > 0 ? ($base . '+build.' . $build) : $base;
	}
}
define('APP_VERSION', app_version());

// محیط اجرا (development یا production)
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);

// تنظیمات کش
define('CACHE_ENABLED', true);
define('CACHE_TTL', 300); // 5 دقیقه

// حالت نگهداری
define('MAINTENANCE_MODE', false);

date_default_timezone_set(TIMEZONE);

// --- GitHub Update (اختیاری) ---
// برای آپدیت مستقیم از GitHub به‌صورت ZIP (بدون git)، می‌توانید ریپو را اینجا تعریف کنید
// اگر این مقادیر را ست کنید، در آدرس /update.php فقط کافیست source=zip و ref را بدهید
// مثال: /update.php?source=zip&ref=staging
// اگر ریپو private است، می‌توانید GITHUB_TOKEN (با دسترسی read repo) را هم تنظیم کنید
// توجه: این فایل را در گیت commit نکنید.
// define('GITHUB_OWNER', 'your-github-user');
// define('GITHUB_REPO', 'your-repo-name');
// define('GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

// --- Multi-language adapters (اختیاری) ---
// The project includes minimal adapter examples in /adapters for Node.js and Python.
// These are optional webhook receivers you can run on cPanel or other hosts and
// forward events to this PHP backend or reimplement business logic.
// Node.js adapter: adapters/nodejs/server.js
// Python adapter: adapters/python/app.py
