# 🎁 ربات ارجاع پرمیوم - نسخه PHP

ربات تلگرام ارجاع و پاداش پرمیوم برای cPanel با نصب خودکار (نسخه 2.1.0)

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

## 🚀 نصب سریع

### پیش‌نیازها
- PHP 8.0+ و MySQL 5.7+/MariaDB 10+
- دسترسی cPanel
- Bot Token از [@BotFather](https://t.me/BotFather)
- SSL/HTTPS (ضروری)

### مراحل نصب

1. **آپلود فایل‌ها** به `public_html`
2. **ساخت دیتابیس MySQL** در cPanel
3. **اجرا:** `https://yourdomain.com/install.php`
4. **فرم نصب** را با اطلاعات دیتابیس، Bot Token، User ID و رمز پنل پر کنید
5. **تنظیم Webhook** با دکمه خودکار یا:
   ```bash
   # از tools.php برای مدیریت webhook
   https://yourdomain.com/tools.php?a=reset_webhook&token=WEBHOOK_SECRET
   ```
6. **Cron Job** در cPanel: `*/5 * * * * php /path/to/cron.php`
7. **ورود به پنل:** `https://yourdomain.com/admin/`

📖 **برای جزئیات بیشتر:** [DOCS.md](DOCS.md)

## 🎮 استفاده

### دستورات کاربران
`/start` `/invite` `/score` `/top` `/spin` `/claim` `/contact` `/help`

### دستورات ادمین
`/stats` `/ban <uid>` `/give <uid> <points>` `/r <uid> <text>`

### پنل ادمین
📊 آمار • 👥 کاربران • 🎁 جوایز • 🔗 کانال‌ها • 📣 پیام همگانی • ⚙️ تنظیمات • 📋 لاگ‌ها

## 📁 فایل‌های کلیدی

- `install.php` - نصب‌کننده خودکار
- `webhook.php` - هندلر پیام‌های تلگرام
- `tools.php` - ابزارهای مدیریتی (ping, webhook info, reset, debug, health)
- `update.php` - آپدیت خودکار از GitHub (از پنل ادمین)
- `deploy.php` - GitHub webhook auto-deploy
- `cron.php` - وظایف دوره‌ای
- `admin/index.php` - پنل مدیریت
- `includes/` - Database و BotHelper
- `DOCS.md` - مستندات کامل نصب و پیکربندی

## ⚙️ تنظیمات اصلی

تنظیمات در `config.php` (ساخته می‌شود توسط installer):
- اتصال دیتابیس (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- Bot (BOT_TOKEN, ADMIN_ID, ADMIN_KEY)
- Webhook (WEBHOOK_SECRET, SITE_URL)

از پنل ادمین قابل تغییر: threshold، banner، maintenance، کانال‌ها

## 🔒 امنیت

✅ **Session-based authentication** برای پنل ادمین  
✅ **CSRF protection** برای تمام فرم‌ها  
✅ **Webhook secret token** برای تأیید درخواست‌های تلگرام  
✅ **Rate limiting** با سیستم throttle  
✅ **محافظت از فایل config.php** با .htaccess  
✅ **SQL injection prevention** با PDO prepared statements  
✅ **XSS protection** با htmlspecialchars  

## 🐛 عیب‌یابی سریع

**ربات پاسخ نمی‌دهد:**
- سلامت سیستم: `https://yourdomain.com/tools.php?a=health`
- بررسی Webhook: `https://yourdomain.com/tools.php?a=get_webhook_info&token=WEBHOOK_SECRET`
- تست سرور: `https://yourdomain.com/tools.php?a=ping`
- دیباگ Webhook: `https://yourdomain.com/tools.php?a=reset_webhook&token=WEBHOOK_SECRET&target=debug`
- چک کردن لاگ‌های PHP در cPanel → Error Logs و `webhook_debug.log`
- فعال کردن DEBUG_MODE در config.php

**خطای دیتابیس:** بررسی اطلاعات در config.php و دسترسی‌های کاربر

**Cron کار نمی‌کنه:** مسیر PHP و مجوزهای فایل را چک کنید

**پنل ادمین 500 می‌دهد:**
- از تنظیم بودن `ADMIN_KEY` و `ADMIN_ID` در `config.php` مطمئن شوید.
- اگر پس از ورود خطا می‌بینید، رمز واردشده باید دقیقا مساوی `ADMIN_KEY` باشد.
- سلامت را از این آدرس ببینید: `https://yourdomain.com/tools.php?a=health`
- خطاهای سرور را در cPanel → Error Logs بررسی کنید.
- اگر دیتابیس در دسترس نباشد، صفحه راهنما با کد 500 نشان داده می‌شود؛ اطلاعات اتصال DB را بازبینی کنید.

## 🔄 به‌روزرسانی

### روش 1: آپدیت خودکار از پنل (پیشنهادی) ⭐
1. وارد پنل ادمین شوید
2. تب **تنظیمات** → **ابزارها** → دکمه **آپدیت کن**
3. تأیید کنید و صبر کنید (config.php حفظ می‌شه)

### روش 2: GitHub Auto-Deploy
تنظیم Webhook در GitHub به `deploy.php` - هر push خودکار deploy می‌شود

### روش 3: اسکریپت دستی
```bash
cd ~/public_html
./update.sh
```

**نسخه‌دهی خودکار:** VERSION در گیت، BUILD روی سرور → `APP_VERSION = 2.1.0+build.N`

📖 **راهنمای کامل:** [DOCS.md](DOCS.md)
 
 📘 **آموزش ساخت شاخه برای Pull:** [GIT_BRANCH_TUTORIAL.md](GIT_BRANCH_TUTORIAL.md)

## 🏷️ سیاست نسخه‌دهی خودکار

- فایل `VERSION` نسخه اصلی (Semantic) پروژه را نگه می‌دارد، مثل `2.0.0` و در گیت commit می‌شود.
- فایل `BUILD` فقط روی سرور ساخته/به‌روزرسانی می‌شود و در `.gitignore` قرار دارد تا commit نشود.
- مقدار `APP_VERSION` در زمان اجرا از ترکیب این دو ساخته می‌شود:
  - اگر `BUILD` موجود باشد: `2.0.0+build.N`
  - در غیر این صورت: همان مقدار `VERSION`
- بعد از هر آپدیت موفق:
  - اگر Auto-Deploy با `deploy.php` باشد، `BUILD` به طور خودکار +1 می‌شود.
  - اگر با `update.sh` آپدیت کنید، `BUILD` نیز +1 می‌شود.

بنابراین، شما همیشه بعد از هر آپدیت، نسخه به‌روز را بدون ویرایش دستی فایل‌های کد خواهید داشت.

## 📝 لاگ تغییرات

### نسخه 2.1.0 (فعلی)
- 🔄 **آپدیت خودکار با یک کلیک**: فایل `update.php` و دکمه آپدیت در پنل ادمین
- 🏷️ نسخه‌دهی خودکار: VERSION/BUILD با افزایش Build پس از هر آپدیت
- 🧭 بهبود UI پنل: ثبات اسکرول، تثبیت ابعاد چارت، رفع باگ‌های رابط کاربری
- 🛠️ **ادغام ابزارها**: کاهش 10 فایل با متمرکزسازی در `tools.php` و `DOCS.md`
  - tools.php: ping, webhook info/reset/debug, health
  - DOCS.md: ادغام INSTALL.md, AUTO_DEPLOY_GUIDE.md, UPDATE_SYSTEM.md

### نسخه‌های قبل
- **2.0.x**: بازطراحی کامل UI پنل ادمین، بهبود امنیت و پایداری، زیرساخت deploy
- **1.0.0**: نسخه اولیه با نصب‌کننده، سیستم دعوت، گردونه شانس، پنل ادمین

##  License
MIT License - استفاده آزاد

---

⚠️ **نکته امنیتی:** بعد از نصب، `install.php` را حذف کنید: `rm install.php`

**موفق باشید! 🚀**
