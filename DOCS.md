# 📚 مستندات کامل ربات ارجاع پرمیوم

> راهنمای جامع نصب، پیکربندی، و نگهداری

---

## فهرست

1. [نصب سریع](#نصب-سریع)
2. [راه‌اندازی Auto-Deploy](#راه‌اندازی-auto-deploy)
3. [سیستم آپدیت](#سیستم-آپدیت)
4. [عیب‌یابی](#عیب‌یابی)
5. [پیکربندی پیشرفته](#پیکربندی-پیشرفته)

---

## 📦 نصب سریع

### مراحل نصب در 5 دقیقه

#### 1️⃣ آپلود فایل‌ها
تمام فایل‌ها را در `public_html` آپلود کنید.

#### 2️⃣ ساخت دیتابیس
در cPanel → MySQL Databases:
- دیتابیس جدید بسازید
- کاربر جدید با رمز قوی بسازید
- کاربر را به دیتابیس متصل کنید

#### 3️⃣ نصب
به `https://yourdomain.com/install.php` بروید و فرم را پر کنید.

#### 4️⃣ تنظیم Webhook
از دکمه "تنظیم خودکار Webhook" در صفحه نصب استفاده کنید یا:
```bash
# مدیریت webhook از tools.php
https://yourdomain.com/tools.php?a=reset_webhook&token=WEBHOOK_SECRET
```

#### 5️⃣ Cron Job
در cPanel → Cron Jobs اضافه کنید:
```bash
*/5 * * * * php /home/username/public_html/cron.php
```

#### ✅ تمام!
ربات آماده است. وارد شوید:
- ربات: شروع چت با ربات در تلگرام
- پنل: `https://yourdomain.com/admin/`

#### ⚠️ امنیت
**بعد از نصب موفق:**
```bash
rm install.php
```

---

## 🚀 راه‌اندازی Auto-Deploy

### نتیجه نهایی
هر بار که در GitHub چیزی commit کنید، **خودکار** به سرور cPanel شما آپلود می‌شود!

### پیش‌نیازها
- ✅ پروژه در GitHub آپلود شده باشد
- ✅ دسترسی SSH به cPanel (یا Terminal در cPanel)
- ✅ Git در سرور نصب باشد
- ✅ دسترسی به تنظیمات GitHub Repository

### مرحله 1: راه‌اندازی اولیه Git در cPanel

#### روش 1: استفاده از Terminal cPanel

```bash
# رفتن به مسیر public_html
cd ~/public_html

# Initialize کردن Git
git init

# اضافه کردن remote
git remote add origin https://github.com/YOUR_USERNAME/php-telegram-referral-bot.git

# دانلود کدها
git fetch origin main
git checkout -b main origin/main

# تنظیم مجوزات
chmod -R 755 .
chmod 644 config.php
```

#### روش 2: استفاده از اسکریپت خودکار

```bash
cd ~/public_html
chmod +x setup_git_deploy.sh
./setup_git_deploy.sh
```

### مرحله 2: نصب Deploy Script

1. **فایل `deploy.php` را در root سایت** قرار دهید

2. **فایل را ویرایش کنید**:
```php
define('GITHUB_SECRET', 'your-super-secret-key-here'); // یک رمز قوی بسازید
define('BRANCH', 'main'); // یا 'master'
```

3. **تست کنید**:
```bash
https://yourdomain.com/deploy.php
```
باید خطای "Missing signature" نمایش دهد (این خوب است!)

### مرحله 3: تنظیم GitHub Webhook

1. **وارد Repository شوید** در GitHub
2. **Settings** → **Webhooks** → **Add webhook**
3. **تنظیمات Webhook**:

```
Payload URL: https://yourdomain.com/deploy.php
Content type: application/json
Secret: your-super-secret-key-here (همان SECRET در deploy.php)
```

4. **انتخاب Events**: ☑️ **Just the push event**
5. **Active** را تیک بزنید ✅
6. **Add webhook** کلیک کنید

### مرحله 4: تست Deployment

1. **یک تغییر کوچک در GitHub ایجاد کنید**
2. **بررسی Webhook**: Settings → Webhooks → Recent Deliveries (باید 200 OK باشد)
3. **بررسی فایل لاگ**:
```bash
tail -f ~/public_html/deploy.log
```

### امنیت

#### محافظت از فایل deploy.php

در `.htaccess` اضافه کنید:

```apache
# محدود کردن دسترسی به deploy.php
<Files "deploy.php">
    Order Deny,Allow
    Deny from all
    # IP GitHub را مجاز کنید
    Allow from 140.82.112.0/20
    Allow from 143.55.64.0/20
    Allow from 192.30.252.0/22
</Files>
```

#### محافظت از config.php

مطمئن شوید `config.php` در `.gitignore` است:

```gitignore
config.php
deploy.log
*.log
BUILD
```

---

## 🔄 سیستم آپدیت

### 3 روش مختلف برای دریافت آپدیت‌ها

#### 1️⃣ Auto-Deploy (توصیه می‌شود) 🚀
- **خودکار**: هر commit در GitHub → خودکار به سرور
- **سریع**: 5-10 ثانیه
- **استفاده**: فقط `git push origin main`

#### 2️⃣ Manual Update با اسکریپت 🔧
```bash
cd ~/public_html
chmod +x update.sh
./update.sh
```

**فرآیند اسکریپت:**
1. 💾 Backup از config.php
2. 📦 ذخیره تغییرات محلی
3. 📥 دریافت آپدیت‌ها
4. ⬇️ اعمال تغییرات
5. 🔧 بازگرداندن config.php
6. 🔒 تنظیم مجوزات
7. 🏷️ افزایش BUILD
8. ✅ گزارش نهایی

#### 3️⃣ Manual Git Commands 💻
```bash
# آپدیت ساده
git pull origin main

# آپدیت با حفظ تغییرات محلی
git stash
git pull origin main
git stash pop

# آپدیت Force
git fetch origin main
git reset --hard origin/main
```

### Backup قبل از آپدیت

```bash
# Backup از config.php
cp config.php config.php.backup.$(date +%Y%m%d)

# Backup از دیتابیس
mysqldump -u USER -p DATABASE > backup_$(date +%Y%m%d).sql

# Backup از کل پروژه
tar -czf project_backup_$(date +%Y%m%d).tar.gz .
```

### بازگشت به نسخه قبلی

```bash
# مشاهده تاریخچه
git log --oneline

# بازگشت به commit خاص
git reset --hard COMMIT_HASH

# بازگشت به آخرین commit
git reset --hard HEAD~1
```

---

## 🐛 عیب‌یابی

### ربات پاسخ نمی‌دهد

**چک‌لیست:**
- ✅ سلامت سیستم: `https://yourdomain.com/tools.php?a=health`
- ✅ وضعیت Webhook: `https://yourdomain.com/tools.php?a=get_webhook_info&token=SECRET`
- ✅ تست سرور: `https://yourdomain.com/tools.php?a=ping`
- ✅ لاگ‌های PHP: cPanel → Error Logs
- ✅ فعال کردن DEBUG_MODE در config.php

**دیباگ Webhook:**
```bash
# ست کردن به حالت debug
https://yourdomain.com/tools.php?a=reset_webhook&token=SECRET&target=debug

# مشاهده لاگ
cat webhook_debug.log
```

### خطای دیتابیس

```bash
# بررسی اطلاعات config.php
# تست اتصال
https://yourdomain.com/tools.php?a=health

# بررسی schema
# از پنل ادمین: تنظیمات → راه‌اندازی دیتابیس
```

### Webhook با خطای 500

**راه‌حل‌ها:**
```bash
# بررسی مجوزات
ls -la webhook.php

# بررسی لاگ PHP
tail ~/public_html/error_log

# تست بدون secret
https://yourdomain.com/tools.php?a=reset_webhook&token=SECRET&no_secret=1

# بررسی ModSecurity
# cPanel → Security → ModSecurity (موقتاً غیرفعال کنید)
```

### Git Pull/Fetch خطا می‌دهد

```bash
# بررسی وضعیت
git status

# بررسی remote
git remote -v

# Force pull
git fetch origin main
git reset --hard origin/main
git clean -fd
```

### Permission Denied

```bash
# تنظیم مجوزات
chmod -R 755 ~/public_html
chmod 644 ~/public_html/config.php
chmod +x ~/public_html/update.sh
```

---

## ⚙️ پیکربندی پیشرفته

### Auto-Update Cron Job

```bash
# هر شب ساعت 3
0 3 * * * cd ~/public_html && ./update.sh >> update_cron.log 2>&1
```

### Notification بعد از Deploy

در `deploy.php` اضافه کنید:
```php
// ارسال Telegram
if (function_exists('error_notify_admin')) {
    error_notify_admin('deploy_success', 'Project updated successfully!');
}
```

### Environment Variables

```php
// در deploy.php
define('GITHUB_SECRET', getenv('GITHUB_WEBHOOK_SECRET'));
```

### Monitoring لاگ‌ها

```bash
# آخرین 50 خط
tail -50 deploy.log

# Real-time
tail -f deploy.log

# فیلتر خطاها
grep "ERROR" deploy.log
```

### Staging Environment

```bash
# Clone برای staging
git clone -b develop https://github.com/user/repo.git ~/staging

# Webhook جداگانه
https://yourdomain.com/staging/deploy.php
```

---

## 📊 مقایسه روش‌های آپدیت

| ویژگی | Auto-Deploy | اسکریپت | Git دستی |
|-------|-------------|---------|----------|
| سرعت | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| آسانی | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ |
| کنترل | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| امنیت | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Backup خودکار | ❌ | ✅ | ❌ |
| نیاز به SSH | ❌ | ✅ | ✅ |

---

## ✅ چک‌لیست نهایی

### راه‌اندازی اولیه
- [ ] Git در سرور نصب شده
- [ ] Repository clone شده
- [ ] Remote به GitHub اضافه شده
- [ ] config.php در .gitignore
- [ ] مجوزات فایل‌ها تنظیم شده

### Auto-Deploy
- [ ] deploy.php آپلود شده
- [ ] SECRET تنظیم شده
- [ ] Webhook در GitHub ساخته شده
- [ ] Webhook تست شده
- [ ] deploy.log کار می‌کند

### امنیت
- [ ] config.php محافظت شده
- [ ] .htaccess فعال است
- [ ] Backup منظم انجام می‌شود
- [ ] SSL/HTTPS فعال است

---

## 🎯 بهترین روش‌ها

**برای Production:**
1. استفاده از **Auto-Deploy** برای سرعت
2. **Backup منظم** از دیتابیس و فایل‌ها
3. **Test** در محیط Staging قبل از Production
4. **Monitoring** لاگ‌ها برای خطاها
5. **Rollback Plan** برای مشکلات احتمالی
6. **استفاده از Git Tags** برای versioning

**موفق باشید! 🚀**
