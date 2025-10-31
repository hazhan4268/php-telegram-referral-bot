<?php
// webhook_simple.php
// دریافت و ثبت داده‌های وب‌هوک بدون نیاز به سکرت

// دریافت داده‌های POST
$input = file_get_contents('php://input');

// ثبت داده‌ها در یک فایل لاگ
file_put_contents(__DIR__ . '/webhook_simple.log', date('Y-m-d H:i:s') . "\n" . $input . "\n\n", FILE_APPEND);

// پاسخ موفقیت
http_response_code(200);
echo 'OK';
