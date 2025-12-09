# Live Stream Email Notifications - Quick Start Guide

## 🚀 Setup (One-Time)

### 1. Ensure Queue Table Exists

```powershell
php artisan migrate:status | Select-String "jobs"
```

✓ Should show: `0001_01_01_000002_create_jobs_table ... Ran`

### 2. Configure Mail Settings

Edit `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Or your mail server
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@connectinc.app
MAIL_FROM_NAME="Connect Inc"
```

### 3. Start Queue Worker

```powershell
# Development (manual - run in separate terminal)
php artisan queue:work --tries=3 --timeout=300

# Production (Windows Service with NSSM)
nssm install LaravelQueue "C:\php\php.exe" "C:\laragon\www\connect_app_bkb\artisan queue:work --tries=3 --timeout=300"
nssm start LaravelQueue
```

---

## 📧 How It Works

### When Admin Creates Stream:

1. Admin fills out stream creation form
2. Stream is saved to database
3. Email notification job is queued
4. Queue worker processes job
5. Emails sent in chunks of 100 users
6. Only active, verified users with email notifications enabled receive emails

### Current Stats:

-   **Eligible Users**: 3,587 users
-   **Chunk Size**: 100 users per batch
-   **Delay Between Chunks**: 100ms
-   **Estimated Send Time**: ~36 seconds (for 3,587 users)

---

## ✅ Testing

### Run Test Script

```powershell
php test_stream_email_notifications.php
```

### Create Test Stream

1. Login to admin panel
2. Navigate to Streams → Create Stream
3. Fill in details:
    - Title: "Test Live Stream"
    - Type: Immediate (for live) or Scheduled
    - Add banner image (optional)
    - Set pricing/free minutes
4. Click "Create Stream"
5. Check queue: `SELECT * FROM jobs ORDER BY id DESC LIMIT 1;`

### Monitor Logs

```powershell
# PowerShell
Get-Content storage\logs\laravel.log -Tail 50 -Wait

# Or use a log viewer
```

Look for:

-   `Starting live stream email notifications`
-   `Stream notification sent` (per user)
-   `Completed live stream email notifications`

---

## 🔍 Monitoring

### Check Queue Status

```powershell
# View jobs table
php artisan tinker
>>> DB::table('jobs')->count()
>>> DB::table('jobs')->get()

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush
```

### Check Email Delivery

-   **Development**: Check Mailtrap inbox
-   **Production**: Check user emails or mail server logs

---

## 🛠️ Management Commands

### Queue Worker

```powershell
# Start worker
php artisan queue:work --tries=3 --timeout=300

# Restart worker (reload code changes)
php artisan queue:restart

# Process single job (testing)
php artisan queue:work --once

# Monitor queue
php artisan queue:monitor
```

### Cleanup

```powershell
# Cleanup old failed jobs (default: 7 days)
php artisan stream:cleanup-failed-notifications

# Cleanup with custom days
php artisan stream:cleanup-failed-notifications --days=30
```

### Cache & Config

```powershell
# Clear all caches
php artisan optimize:clear

# Or individually
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

---

## 🎨 Email Template

### Preview

Located at: `resources/views/emails/new-live-stream.blade.php`

### Features:

-   ✅ Responsive design (mobile-friendly)
-   ✅ Stream banner image
-   ✅ Live badge with pulsing animation
-   ✅ Pricing information (free/paid)
-   ✅ "Watch Now" or "Set Reminder" button
-   ✅ Link to notification preferences
-   ✅ Gradient backgrounds

### Customize:

Edit the blade file to change colors, layout, or content.

---

## 📊 Performance

### Current Configuration:

-   **Users to notify**: 3,587
-   **Chunk size**: 100 users
-   **Chunks needed**: 36 chunks
-   **Delay per chunk**: 100ms
-   **Total time**: ~3.6 seconds (chunk delays only)
-   **Email send time**: Depends on mail server (typically 30-60 seconds total)

### Optimize for More Users:

If you have 10,000+ users:

1. Reduce chunk size to 50
2. Increase delay to 150ms
3. Run multiple queue workers:
    ```powershell
    php artisan queue:work --queue=high --tries=3 &
    php artisan queue:work --queue=default --tries=3 &
    ```

---

## 🐛 Troubleshooting

### Emails Not Sending?

1. Check queue worker is running: `ps | Select-String queue`
2. Check jobs table: `SELECT * FROM jobs;`
3. Check failed jobs: `php artisan queue:failed`
4. Check mail config in `.env`
5. Check logs: `storage/logs/laravel.log`

### Queue Not Processing?

```powershell
# Restart queue
php artisan queue:restart

# Clear and restart
php artisan cache:clear
php artisan queue:work --tries=3 --timeout=300
```

### Some Users Not Receiving?

Check user requirements:

```sql
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN notification_email = 1 THEN 1 ELSE 0 END) as notifications_on
FROM users;
```

### Memory Issues?

```powershell
# Increase PHP memory limit
php -d memory_limit=512M artisan queue:work --tries=3 --timeout=300
```

---

## 📝 Files Reference

### Backend

-   `app/Jobs/SendLiveStreamNotifications.php` - Queue job
-   `app/Mail/NewLiveStreamNotification.php` - Email mailable
-   `app/Http/Controllers/Admin/StreamManagementController.php` - Dispatch job
-   `app/Console/Commands/CleanupFailedStreamNotifications.php` - Cleanup command

### Frontend

-   `resources/views/emails/new-live-stream.blade.php` - Email template

### Testing

-   `test_stream_email_notifications.php` - Test script

### Documentation

-   `LIVE_STREAM_EMAIL_NOTIFICATIONS.md` - Full documentation
-   `LIVE_STREAM_EMAIL_QUICK_START.md` - This file

---

## 🎯 Quick Commands Cheat Sheet

```powershell
# Start queue worker
php artisan queue:work --tries=3 --timeout=300

# Test system
php test_stream_email_notifications.php

# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Cleanup old failed jobs
php artisan stream:cleanup-failed-notifications

# Clear caches
php artisan optimize:clear

# Restart queue worker
php artisan queue:restart

# Monitor logs
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

---

## ✨ Success Indicators

You'll know it's working when:

1. ✅ Queue worker shows job processing
2. ✅ Logs show "Starting live stream email notifications"
3. ✅ Logs show "Stream notification sent" entries
4. ✅ Logs show "Completed live stream email notifications"
5. ✅ Jobs table is empty (all processed)
6. ✅ Users receive emails in their inbox

---

**Need Help?**

-   Check full documentation: `LIVE_STREAM_EMAIL_NOTIFICATIONS.md`
-   Review logs: `storage/logs/laravel.log`
-   Run test script: `php test_stream_email_notifications.php`

**Version**: 1.0
**Last Updated**: December 9, 2025
