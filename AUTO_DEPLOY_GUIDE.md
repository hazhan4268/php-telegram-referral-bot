# 🔄 راهنمای Auto-Deploy از GitHub به cPanel

این راهنما نحوه تنظیم deployment اتوماتیک را برای دریافت آپدیت‌های جدید از GitHub توضیح می‌دهد.

## 🎯 نتیجه نهایی:
هر بار که در GitHub چیزی commit کنید، **خودکار** به سرور cPanel شما آپلود می‌شود!

---

## 📋 پیش‌نیازها:
- ✅ پروژه در GitHub آپلود شده باشد
- ✅ دسترسی SSH به cPanel (یا Terminal در cPanel)
- ✅ Git در سرور نصب باشد
- ✅ دسترسی به تنظیمات GitHub Repository

---

## 🚀 مرحله 1: راه‌اندازی اولیه Git در cPanel

### روش 1: استفاده از Terminal cPanel

1. **وارد cPanel شوید** → **Terminal**
2. دستورات زیر را اجرا کنید:

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

### روش 2: استفاده از اسکریپت خودکار

فایل `setup_git_deploy.sh` را آپلود و اجرا کنید:

```bash
cd ~/public_html
chmod +x setup_git_deploy.sh
./setup_git_deploy.sh
```

---

## 🔧 مرحله 2: نصب Deploy Script

1. **فایل `deploy.php` را در root سایت** (public_html) قرار دهید

2. **فایل را ویرایش کنید**:
```php
define('GITHUB_SECRET', 'your-super-secret-key-here'); // یک رمز قوی بسازید
define('BRANCH', 'main'); // یا 'master'
```

3. **مجوزات فایل**:
```bash
chmod 755 deploy.php
```

4. **تست کنید**:
```
https://yourdomain.com/deploy.php
```
باید خطای "Missing signature" نمایش دهد (این خوب است!)

---

## 🎣 مرحله 3: تنظیم GitHub Webhook

1. **وارد Repository شوید** در GitHub

2. **Settings** → **Webhooks** → **Add webhook**

3. **تنظیمات Webhook**:

```
Payload URL: https://yourdomain.com/deploy.php
Content type: application/json
Secret: your-super-secret-key-here (همان SECRET در deploy.php)
```

4. **انتخاب Events**:
   - ☑️ **Just the push event**

5. **Active** را تیک بزنید ✅

6. **Add webhook** کلیک کنید

---

## ✅ مرحله 4: تست Deployment

1. **یک تغییر کوچک در GitHub ایجاد کنید**:
   - README.md را ویرایش کنید
   - Commit کنید

2. **بررسی Webhook**:
   - **Settings** → **Webhooks** → روی webhook کلیک کنید
   - **Recent Deliveries** را ببینید
   - باید Response `200 OK` باشد

3. **بررسی فایل لاگ**:
```bash
tail -f ~/public_html/deploy.log
```

4. **بررسی فایل‌های سایت**:
   - تغییرات باید اعمال شده باشند!

---

## 🛡️ امنیت و بهترین روش‌ها

### 1. محافظت از فایل deploy.php

در `.htaccess` اضافه کنید:

```apache
# محدود کردن دسترسی به deploy.php
<Files "deploy.php">
    Order Deny,Allow
    Deny from all
    # IP GitHub را مجاز کنید (اختیاری)
    Allow from 140.82.112.0/20
    Allow from 143.55.64.0/20
    Allow from 192.30.252.0/22
</Files>
```

### 2. محافظت از config.php

مطمئن شوید `config.php` در `.gitignore` است:

```
config.php
deploy.log
*.log
```

### 3. استفاده از Environment Variables

برای حساس‌تر بودن، از متغیرهای محیطی استفاده کنید:

```php
// در deploy.php
define('GITHUB_SECRET', getenv('GITHUB_WEBHOOK_SECRET'));
```

---

## 📊 نظارت و لاگ‌ها

### مشاهده لاگ‌های Deployment:

```bash
# آخرین 50 خط
tail -50 ~/public_html/deploy.log

# نظارت Real-time
tail -f ~/public_html/deploy.log

# فیلتر خطاها
grep "ERROR" ~/public_html/deploy.log
```

### پاک کردن لاگ‌های قدیمی:

```bash
# نگه‌داری آخرین 100 خط
tail -100 deploy.log > deploy.log.tmp && mv deploy.log.tmp deploy.log
```

---

## 🔄 استفاده روزمره

### بعد از راه‌اندازی، فقط کافی است:

1. **در GitHub کد بنویسید**
2. **Commit کنید**
3. **Push کنید**

```bash
git add .
git commit -m "Feature: Added new feature"
git push origin main
```

4. **✨ خودکار روی سرور آپدیت می‌شود!**

---

## 🐛 عیب‌یابی

### مشکل: Webhook با خطای 500 مواجه می‌شود

**راه حل:**
```bash
# بررسی مجوزات
ls -la deploy.php

# بررسی لاگ PHP
tail ~/public_html/error_log

# اجرای دستی
cd ~/public_html
git pull origin main
```

### مشکل: تغییرات اعمال نمی‌شوند

**راه حل:**
```bash
# بررسی وضعیت Git
git status

# بررسی remote
git remote -v

# Force pull
git fetch origin main
git reset --hard origin/main
```

### مشکل: خطای Permission Denied

**راه حل:**
```bash
# تنظیم مجوزات
chmod -R 755 ~/public_html
chmod 644 ~/public_html/config.php
```

---

## 📝 نکات مهم

### ⚠️ توجه:
1. **فایل `config.php` را در Git قرار ندهید** (حاوی اطلاعات حساس)
2. **قبل از هر deploy، backup بگیرید**
3. **در production، از branch جداگانه استفاده کنید**
4. **مجوزات فایل‌ها را بررسی کنید**

### 💡 نکات بهینه‌سازی:
1. استفاده از **Deploy Keys** در GitHub (بهتر از username/password)
2. تنظیم **Slack/Email notifications** برای موفقیت/خطا
3. اضافه کردن **Rollback functionality**
4. استفاده از **Git Tags** برای versioning

---

## 🎯 مثال کامل Workflow

### Development → Production:

```bash
# 1. در کامپیوتر خود کار کنید
git checkout -b feature/new-feature
# ... کدنویسی ...
git commit -m "Add new feature"
git push origin feature/new-feature

# 2. Pull Request در GitHub
# ... Review و Approve ...

# 3. Merge به main
git checkout main
git merge feature/new-feature
git push origin main

# 4. ✨ خودکار روی سرور deploy می‌شود!
```

---

## 📚 منابع بیشتر

- [GitHub Webhooks Documentation](https://docs.github.com/en/webhooks)
- [Git in cPanel](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git/)
- [PHP Deployment Best Practices](https://github.com/deployphp/deployer)

---

## ✅ چک‌لیست نهایی

- [ ] Git در سرور نصب شده
- [ ] Repository از GitHub clone شده
- [ ] deploy.php در root قرار داده شده
- [ ] SECRET در deploy.php تنظیم شده
- [ ] Webhook در GitHub ساخته شده
- [ ] Webhook با موفقیت تست شده
- [ ] deploy.log در حال ثبت رویدادها
- [ ] config.php در .gitignore است
- [ ] Backup منظم گرفته می‌شود

**🎊 حالا سیستم Auto-Deploy شما آماده است! هر commit خودکار deploy می‌شود! 🚀**