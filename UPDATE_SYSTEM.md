# 🔄 سیستم آپدیت خودکار پروژه

## 📌 خلاصه

این پروژه از **3 روش مختلف** برای دریافت آپدیت‌ها پشتیبانی می‌کند:

### 1️⃣ **Auto-Deploy (توصیه می‌شود) 🚀**
- **خودکار**: هر commit در GitHub → خودکار به سرور
- **سریع**: 5-10 ثانیه
- **نیاز**: GitHub Webhook + deploy.php

### 2️⃣ **Manual Update با اسکریپت 🔧**
- **دستی**: وقتی بخواهید
- **امن**: با Backup خودکار
- **نیاز**: دسترسی SSH/Terminal

### 3️⃣ **Manual Git Commands 💻**
- **کامل**: کنترل کامل
- **پیشرفته**: برای کاربران حرفه‌ای
- **نیاز**: دانش Git

---

## 🚀 روش 1: Auto-Deploy (Webhook)

### مزایا:
- ✅ کاملاً خودکار
- ✅ بدون نیاز به SSH
- ✅ Deployment سریع
- ✅ لاگ‌گیری کامل

### راه‌اندازی سریع:

```bash
# 1. در سرور
cd ~/public_html
git init
git remote add origin https://github.com/USERNAME/REPO.git
git pull origin main

# 2. آپلود deploy.php
# 3. تنظیم SECRET در deploy.php
# 4. ساخت Webhook در GitHub
```

📖 **راهنمای کامل**: [AUTO_DEPLOY_GUIDE.md](AUTO_DEPLOY_GUIDE.md)

### استفاده:
```bash
# فقط در GitHub کار کنید:
git push origin main
# ✨ خودکار به سرور می‌رود!
```

---

## 🔧 روش 2: Manual Update (اسکریپت)

### مزایا:
- ✅ کنترل دستی
- ✅ Backup خودکار
- ✅ نمایش تغییرات
- ✅ بازگشت آسان

### استفاده:

```bash
# در Terminal cPanel:
cd ~/public_html
chmod +x update.sh
./update.sh
```

### فرآیند اسکریپت:
1. 💾 Backup از config.php
2. 📦 ذخیره تغییرات محلی
3. 📥 دریافت آپدیت‌ها
4. ⬇️ اعمال تغییرات
5. 🔧 بازگرداندن config.php
6. 🔒 تنظیم مجوزات
7. ✅ گزارش نهایی

---

## 💻 روش 3: Manual Git Commands

### برای کاربران پیشرفته:

```bash
# آپدیت ساده
cd ~/public_html
git pull origin main

# آپدیت با بررسی
git fetch origin main
git log HEAD..origin/main    # مشاهده تغییرات
git pull origin main

# آپدیت با حفظ تغییرات محلی
git stash
git pull origin main
git stash pop

# آپدیت Force (حذف تغییرات محلی)
git fetch origin main
git reset --hard origin/main
git clean -fd
```

---

## 🛡️ امنیت و Backup

### قبل از هر آپدیت:

```bash
# 1. Backup از config.php
cp config.php config.php.backup.$(date +%Y%m%d)

# 2. Backup از دیتابیس
mysqldump -u USER -p DATABASE > backup_$(date +%Y%m%d).sql

# 3. Backup از کل پروژه
tar -czf project_backup_$(date +%Y%m%d).tar.gz .
```

### بازگشت به نسخه قبلی:

```bash
# مشاهده تاریخچه
git log --oneline

# بازگشت به commit خاص
git reset --hard COMMIT_HASH

# بازگشت به آخرین commit
git reset --hard HEAD~1
```

---

## 📊 مقایسه روش‌ها

| ویژگی | Auto-Deploy | اسکریپت | دستورات Git |
|-------|-------------|---------|-------------|
| سرعت | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| آسانی | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ |
| کنترل | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| امنیت | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Backup خودکار | ❌ | ✅ | ❌ |
| نیاز به SSH | ❌ | ✅ | ✅ |

---

## 🔍 بررسی وضعیت

### چک کردن نسخه فعلی:

```bash
# آخرین commit
git log -1 --oneline

# تفاوت با GitHub
git fetch origin main
git log HEAD..origin/main --oneline

# وضعیت کلی
git status
```

### مشاهده لاگ‌های Deploy:

```bash
# آخرین 50 خط
tail -50 deploy.log

# Real-time
tail -f deploy.log

# فیلتر خطاها
grep "ERROR" deploy.log
```

---

## ⚙️ تنظیمات پیشرفته

### Ignore کردن فایل‌های محلی:

در `.gitignore`:
```
config.php
deploy.log
*.backup
*.log
/uploads/
/cache/
```

### Auto-Update Cron Job:

```bash
# هر شب ساعت 3
0 3 * * * cd ~/public_html && ./update.sh >> update_cron.log 2>&1
```

### Notification بعد از Update:

در `deploy.php` اضافه کنید:
```php
// ارسال ایمیل
mail('admin@domain.com', 'Deployment Success', 'Project updated!');

// یا Telegram
file_get_contents("https://api.telegram.org/bot{TOKEN}/sendMessage?chat_id={CHAT_ID}&text=Deployed!");
```

---

## 🐛 عیب‌یابی

### مشکل: Permission Denied

```bash
chmod +x update.sh
chmod +x setup_git_deploy.sh
```

### مشکل: Merge Conflicts

```bash
# حذف تغییرات محلی
git reset --hard origin/main

# یا حل دستی
git status  # مشاهده conflicts
# ویرایش فایل‌ها
git add .
git commit -m "Resolved conflicts"
```

### مشکل: Config.php بازنویسی می‌شود

```bash
# در .gitignore
echo "config.php" >> .gitignore
git rm --cached config.php
git commit -m "Remove config.php from git"
```

---

## 📚 فایل‌های مرتبط

- 📄 `deploy.php` - اسکریپت Auto-Deploy
- 📄 `update.sh` - اسکریپت Manual Update
- 📄 `setup_git_deploy.sh` - راه‌اندازی اولیه
- 📄 `AUTO_DEPLOY_GUIDE.md` - راهنمای کامل Auto-Deploy
- 📄 `.gitignore` - فایل‌های ignore شده

---

## ✅ چک‌لیست

### راه‌اندازی اولیه:
- [ ] Git در سرور نصب شده
- [ ] Repository clone شده
- [ ] Remote به GitHub اضافه شده
- [ ] config.php در .gitignore
- [ ] مجوزات فایل‌ها تنظیم شده

### Auto-Deploy:
- [ ] deploy.php آپلود شده
- [ ] SECRET تنظیم شده
- [ ] Webhook در GitHub ساخته شده
- [ ] Webhook تست شده
- [ ] deploy.log کار می‌کند

### Manual Update:
- [ ] update.sh قابل اجرا
- [ ] Backup خودکار کار می‌کند
- [ ] config.php حفظ می‌شود

---

## 🎯 توصیه نهایی

**برای Production:**
1. استفاده از **Auto-Deploy** برای سرعت
2. **Backup منظم** از دیتابیس و فایل‌ها
3. **Test** در محیط Staging قبل از Production
4. **Monitoring** لاگ‌ها برای خطاها
5. **Rollback Plan** برای مشکلات احتمالی

**موفق باشید! 🚀**