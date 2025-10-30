# 🎁 ربات ارجاع پرمیوم - نسخه PHP

ربات تلگرام ارجاع و پاداش پرمیوم برای cPanel با نصب خودکار

## ✨ امکانات

### 🎯 امکانات کاربران
- **لینک اختصاصی دعوت**: هر کاربر لینک منحصر به فرد دارد
- **🎡 گردونه شانس**: روزانه یک بار (1-5 امتیاز)
- **امتیازدهی خودکار**: پس از تأیید عضویت مهمان
- **درخواست جایزه**: هر N دعوت = ۱ پرمیوم
- **📞 تماس با ادمین**: ارسال پیام به ادمین از طریق ربات
- **🔒 جوین اجباری**: بررسی عضویت در کانال‌ها
- **🏆 جدول برترین‌ها**: مشاهده ۱۰ کاربر برتر

### 🛠 پنل ادمین
- **📊 آمار**: نمودارها و KPI کاربران
- **📣 پیام‌رسانی**: ارسال همگانی
- **🔗 مدیریت کانال‌ها**: افزودن/حذف/غیرفعال‌سازی
- **⚙️ تنظیمات**: threshold، banner، maintenance
- **👥 کاربران**: جستجو، مشاهده جزئیات
- **🎁 جوایز**: بررسی و پردازش claim‌ها
- **📋 لاگ‌ها**: مشاهده خطاها و رویدادها

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها
- ✅ Hosting با PHP 7.4+ و MySQL
- ✅ دسترسی به cPanel
- ✅ Telegram Bot Token از [@BotFather](https://t.me/BotFather)
- ✅ SSL Certificate (توصیه می‌شود)

### مراحل نصب سریع

#### 1️⃣ آپلود فایل‌ها
تمام فایل‌های پروژه را در پوشه `public_html` یا یک زیرپوشه آپلود کنید:

```
public_html/
├── install.php
├── webhook.php
├── set_webhook.php
├── cron.php
├── schema.sql
├── .htaccess
├── config.sample.php
├── admin/
│   └── index.php
└── includes/
    ├── Database.php
    └── BotHelper.php
```

#### 2️⃣ ساخت دیتابیس MySQL

در cPanel → MySQL Databases:

1. یک دیتابیس جدید بسازید (مثال: `mybot_db`)
2. یک کاربر جدید بسازید (مثال: `mybot_user`)
3. رمز قوی تعیین کنید
4. کاربر را به دیتابیس اضافه کنید با تمام دسترسی‌ها

#### 3️⃣ اجرای نصب‌کننده

در مرورگر به آدرس زیر بروید:

```
https://yourdomain.com/install.php
```

فرم را با اطلاعات زیر پر کنید:

**تنظیمات دیتابیس:**
- هاست دیتابیس: `localhost`
- نام دیتابیس: نامی که در مرحله 2 ساختید
- نام کاربری: کاربری که ساختید
- رمز عبور: رمز دیتابیس

**تنظیمات ربات:**
- توکن ربات: از @BotFather دریافت کنید
- شناسه ادمین: User ID خود را از @userinfobot بگیرید
- کانال اجباری: (اختیاری) مثال: `@yourchannel`

**تنظیمات پنل:**
- رمز عبور پنل: یک رمز قوی انتخاب کنید
- آدرس سایت: `https://yourdomain.com`

#### 4️⃣ تنظیم Webhook

بعد از نصب، از یکی از روش‌های زیر استفاده کنید:

**روش 1: از صفحه نصب (آسان‌تر)**

روی دکمه "تنظیم خودکار Webhook" کلیک کنید.

**روش 2: دستی با cURL**

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
-H "Content-Type: application/json" \
-d '{
  "url": "https://yourdomain.com/webhook.php",
  "secret_token": "YOUR_SECRET_FROM_CONFIG"
}'
```

**بررسی Webhook:**

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

#### 5️⃣ تنظیم Cron Job

در cPanel → Cron Jobs یک job جدید بسازید:

**تنظیمات:**
- زمان اجرا: `*/5 * * * *` (هر 5 دقیقه)
- دستور:

```bash
php /home/username/public_html/cron.php
```

یا با مسیر کامل PHP:

```bash
/usr/local/bin/php /home/username/public_html/cron.php
```

**نکته:** `username` را با نام کاربری cPanel خود جایگزین کنید.

#### 6️⃣ ورود به پنل ادمین

```
https://yourdomain.com/admin/
```

با رمزی که در مرحله نصب تعیین کردید وارد شوید.

## 🎮 استفاده

### دستورات کاربران
- `/start` - شروع ربات و دریافت لینک دعوت
- `/menu` - منوی اصلی
- `/invite` - مشاهده لینک اختصاصی
- `/score` - مشاهده امتیاز فعلی
- `/top` - جدول برترین‌ها
- `/spin` - چرخاندن گردونه (روزانه 1 بار)
- `/claim` - درخواست جایزه پرمیوم
- `/contact` - تماس با ادمین
- `/help` - راهنما
- `/about` - درباره ربات

### دستورات ادمین (در PM با ربات)
- `/stats` - آمار سریع
- `/ban <uid>` - مسدود کردن
- `/unban <uid>` - رفع مسدودی
- `/give <uid> <points>` - اعطای امتیاز
- `/take <uid> <points>` - کسر امتیاز
- `/r <uid> <text>` - پاسخ به تماس کاربر

### پنل ادمین
تب‌های موجود:
- **📊 آمار**: نمایش آمار کلی و کاربران اخیر
- **👥 کاربران**: جستجو و مدیریت کاربران
- **🎁 درخواست جوایز**: تأیید یا رد درخواست‌ها
- **🔗 کانال‌ها**: مدیریت کانال‌های اجباری
- **📣 ارسال همگانی**: ارسال پیام به همه کاربران
- **⚙️ تنظیمات**: تغییر threshold، banner، maintenance
- **📋 لاگ‌ها**: مشاهده خطاها

## 📁 ساختار فایل‌ها

```
php-referral-bot/
├── install.php              # نصب‌کننده خودکار
├── webhook.php              # دریافت پیام‌های تلگرام
├── set_webhook.php          # کمکی برای تنظیم webhook
├── cron.php                 # وظایف دوره‌ای
├── schema.sql               # Schema دیتابیس MySQL
├── .htaccess                # تنظیمات Apache
├── config.php               # تنظیمات (ساخته می‌شود توسط installer)
├── config.sample.php        # نمونه فایل config
├── README.md                # این فایل
├── admin/
│   └── index.php            # پنل ادمین کامل
└── includes/
    ├── Database.php         # کلاس اتصال به دیتابیس
    └── BotHelper.php        # توابع کمکی ربات
```

## 🗄️ Schema دیتابیس

### جداول اصلی
- `users` - اطلاعات کاربران
- `referrals` - دعوت‌ها
- `scores` - امتیازات
- `claims` - درخواست‌های جایزه
- `spins` - گردونه شانس
- `bans` - کاربران مسدود شده
- `score_logs` - تاریخچه امتیازات
- `settings` - تنظیمات سیستم
- `channels` - کانال‌های اجباری
- `throttle` - محدودیت نرخ
- `last_msgs` - پیام‌های ارسالی اخیر
- `post_msgs` - پیام‌های broadcast
- `contact_state` - وضعیت تماس با ادمین
- `admin_sessions` - session های پایدار ادمین
- `admin_errors` - لاگ خطاها
- `logs` - لاگ‌های سیستم
- `admin_logs` - لاگ اقدامات ادمین
- `sponsors` - اسپانسرها
- `sponsor_views` - بازدید اسپانسرها
- `sponsor_clicks` - کلیک‌های اسپانسرها

## ⚙️ تنظیمات

### متغیرهای محیطی (config.php)

پس از نصب، این فایل خودکار ساخته می‌شود:

| متغیر | توضیح |
|-------|-------|
| `DB_HOST` | آدرس دیتابیس (معمولاً localhost) |
| `DB_NAME` | نام دیتابیس |
| `DB_USER` | نام کاربری دیتابیس |
| `DB_PASS` | رمز عبور دیتابیس |
| `BOT_TOKEN` | توکن ربات تلگرام |
| `ADMIN_ID` | User ID ادمین (عدد) |
| `ADMIN_KEY` | رمز عبور پنل ادمین |
| `CHANNEL_USERNAME` | کانال پیش‌فرض اجباری (اختیاری) |
| `WEBHOOK_SECRET` | Secret token برای امنیت webhook |
| `SITE_URL` | آدرس کامل سایت |

### تنظیمات دیتابیس (جدول settings)

از پنل ادمین قابل تغییر:

- `reward_threshold` - تعداد دعوت برای یک جایزه (پیش‌فرض: 5)
- `banner_text` - متن بنر دعوت (با {thr} و {link})
- `maintenance` - حالت تعمیرات (0/1)
- `bot_username` - نام کاربری ربات (خودکار)

## 🔒 امنیت

✅ **Session-based authentication** برای پنل ادمین  
✅ **CSRF protection** برای تمام فرم‌ها  
✅ **Webhook secret token** برای تأیید درخواست‌های تلگرام  
✅ **Rate limiting** با سیستم throttle  
✅ **محافظت از فایل config.php** با .htaccess  
✅ **SQL injection prevention** با PDO prepared statements  
✅ **XSS protection** با htmlspecialchars  

## 🐛 عیب‌یابی

### ربات پاسخ نمی‌دهد

1. بررسی webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

2. بررسی لاگ‌های PHP:
   - cPanel → Error Logs
   - یا فایل `/home/username/public_html/error_log`

3. فعال کردن debug mode:
   - در `config.php` تغییر دهید: `define('DEBUG_MODE', true);`
   - لاگ‌ها در `debug.log` ذخیره می‌شود

### خطای دیتابیس

1. بررسی اطلاعات در `config.php`
2. اطمینان از دسترسی کاربر به دیتابیس
3. اجرای مجدد `schema.sql`

### خطای Webhook

1. SSL فعال باشد (HTTPS)
2. URL صحیح باشد
3. Secret token همخوانی داشته باشد

### Cron اجرا نمی‌شود

1. مسیر PHP را بررسی کنید:
```bash
which php
```

2. در cPanel logs بررسی کنید
3. مجوزهای فایل را بررسی کنید:
```bash
chmod 755 cron.php
```

## 📊 بهینه‌سازی

### برای ترافیک بالا

1. **فعال کردن OPcache** در PHP
2. **استفاده از Redis/Memcached** برای کش
3. **افزایش TTL کش** در `BotHelper.php`
4. **استفاده از CDN** برای فایل‌های استاتیک

### پایگاه داده

1. **ایندکس‌ها** از قبل در schema تعریف شده‌اند
2. **پاکسازی خودکار** با cron فعال است
3. **Backup منظم** توصیه می‌شود

## 🔄 به‌روزرسانی

1. Backup از دیتابیس و `config.php` بگیرید
2. فایل‌های جدید را جایگزین کنید (به جز config.php)
3. schema جدید را اجرا کنید (اگر تغییر کرده)
4. کش را پاک کنید

## 📝 لاگ تغییرات

### نسخه 1.0.0 (اولیه)
- ✅ نصب‌کننده خودکار با UI زیبا
- ✅ سیستم کامل دعوت و امتیازدهی
- ✅ گردونه شانس روزانه
- ✅ پنل ادمین تک‌صفحه‌ای با تب‌ها
- ✅ جوین اجباری چند کانال
- ✅ تماس با ادمین
- ✅ ارسال همگانی
- ✅ سیستم جوایز کامل
- ✅ Cron jobs برای پاکسازی
- ✅ لاگ خطاها و رویدادها

## 🤝 پشتیبانی

- 📧 ایمیل: support@example.com
- 💬 تلگرام: @yourusername
- 🌐 وب‌سایت: https://example.com

## 📜 License

MIT License - استفاده آزاد برای پروژه‌های شخصی و تجاری

---

**نکته مهم:** بعد از نصب، حتماً فایل `install.php` را حذف یا تغییر نام دهید تا امنیت سایت حفظ شود!

```bash
rm install.php
# یا
mv install.php install.php.bak
```

**موفق باشید! 🚀**
