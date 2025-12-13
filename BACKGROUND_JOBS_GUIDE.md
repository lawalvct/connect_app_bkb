# Background Jobs & Scheduled Tasks - Complete Guide (aaPanel)

## 🚀 QUICK START (aaPanel Users)

### Step 1: Add Cron Job for Scheduler

1. aaPanel → **Cron** → **Add Cron**
2. Name: `Laravel Scheduler`
3. Type: `Shell Script`
4. Cycle: `Every 1 minute` (N Minutes: 1)
5. Script: `cd /www/wwwroot/admin.connectinc.app && php artisan schedule:run >> /dev/null 2>&1`

### Step 2: Update Supervisor Config

1. aaPanel → **App Store** → **Installed** → **Supervisor** → **Settings**
2. Edit `laravel-worker` or add new daemon
3. Use the config provided in "Supervisor Config" section below

### Step 3: Deploy Changes

```bash
cd /www/wwwroot/admin.connectinc.app
git pull
php artisan optimize:clear
```

### Step 4: Restart Workers

Via aaPanel GUI: **App Store** → **Supervisor** → **Settings** → Find `laravel-worker` → **Restart**

---

## ⚠️ CRITICAL ISSUES FOUND & FIXED

### Issue 1: Schedules in Wrong Place (FIXED ✅)

Your Laravel 12 project had schedules split across 3 different files:

-   `app/Console/Kernel.php` - **NOT USED IN LARAVEL 12!** ❌
-   `routes/console.php` - Partial schedules
-   `bootstrap/app.php` - Only had swipes reset

**All schedules are now consolidated in `bootstrap/app.php`** ✅

### Issue 2: Missing Cron Job on Server

You need to add a cron job that runs the Laravel scheduler every minute.

---

## 🔧 SERVER CONFIGURATION (aaPanel)

### 1. Supervisor Config (Queue Worker) - UPDATED

Your current config is mostly correct, but needs a few improvements:

#### Using aaPanel GUI:

1. Go to **"App Store"** → **"Installed"**
2. Find **"Supervisor"** and click **"Settings"**
3. Click **"Add Daemon"** or edit existing `laravel-worker`
4. Configure as below

#### Config File Location:

**File: `/etc/supervisor/conf.d/laravel-worker.conf`** (or via aaPanel GUI)

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/admin.connectinc.app/artisan queue:work database --queue=default,notifications --sleep=3 --tries=3 --max-time=3600
directory=/www/wwwroot/admin.connectinc.app
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=/www/wwwroot/admin.connectinc.app/storage/logs/worker.log
stopwaitsecs=3600
```

**Changes Made:**

-   Added `database` after `queue:work` to be explicit about queue driver
-   Added `--max-time=3600` to restart workers hourly (prevents memory leaks)
-   Added `stopasgroup=true` and `killasgroup=true` for clean process termination
-   Removed redundant `environment` section (not needed)

### 2. Cron Job for Scheduler - REQUIRED! ⚠️

**This is CRITICAL and may be missing on your server!**

Add this cron job to run the Laravel scheduler every minute:

#### Using aaPanel GUI:

1. Login to aaPanel
2. Go to **"Cron"** in the left sidebar
3. Click **"Add Cron"**
4. Configure:
    - **Name:** Laravel Scheduler
    - **Type:** Shell Script
    - **Execution Cycle:** Every 1 minute (N Minutes: 1)
    - **Script:**
        ```bash
        cd /www/wwwroot/admin.connectinc.app && php artisan schedule:run >> /dev/null 2>&1
        ```
5. Click **"Add"**

#### Using SSH/Terminal:

```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /www/wwwroot/admin.connectinc.app && php artisan schedule:run >> /dev/null 2>&1
```

**Verify it was added:**

```bash
crontab -l | grep artisan
```

---

## 📋 SCHEDULED TASKS (Now All Active)

| Command                               | Schedule             | Description                         |
| ------------------------------------- | -------------------- | ----------------------------------- |
| `posts:publish-scheduled`             | Every minute         | Publish scheduled posts             |
| `stories:cleanup`                     | Hourly (XX:00)       | Delete expired stories              |
| `swipes:reset-daily`                  | Daily 00:01 UTC      | Reset daily swipe counts            |
| `subscriptions:expire --notify`       | Daily 06:00 UTC      | Expire subscriptions & notify users |
| `ads:send-reminders`                  | Daily 09:00 UTC      | Send ad expiry reminders            |
| `fcm:cleanup-inactive`                | Weekly Sun 02:00 UTC | Clean up inactive FCM tokens        |
| `stream:cleanup-failed-notifications` | Weekly Sun 03:00 UTC | Clean failed notification jobs      |

---

## 📬 QUEUED JOBS (Processed by Supervisor)

These jobs are dispatched to the queue and processed by your supervisor workers:

| Job                                     | Queue         | Triggered By                    | Description                        |
| --------------------------------------- | ------------- | ------------------------------- | ---------------------------------- |
| `SendLiveStreamNotifications`           | default       | Admin creates stream            | Email notifications to 3587+ users |
| `SendAdExpiryRemindersJob`              | default       | `ads:send-reminders` command    | Ad expiry emails                   |
| `SendPushNotificationJob`               | default       | Various actions                 | Push notifications                 |
| `ExportUsersJob`                        | default       | Admin exports users             | Large user data export             |
| `ProcessAdMetricsJob`                   | default       | Ad system                       | Process ad metrics                 |
| `SendAdminNotificationEmail`            | default       | Admin actions                   | Admin email notifications          |
| `SendConnectionRequestNotificationJob`  | notifications | User sends connection request   | Push + Email to receiver           |
| `SendConnectionAcceptedNotificationJob` | notifications | User accepts connection request | Push + Email to requester          |

---

## 🔍 VERIFICATION COMMANDS

### On Your Server (SSH)

```bash
# Check if scheduler cron is running
crontab -l | grep artisan

# Check supervisor status
supervisorctl status

# Check if queue workers are running
supervisorctl status laravel-worker:*

# View queue worker logs
tail -f /www/wwwroot/admin.connectinc.app/storage/logs/worker.log

# Check pending jobs in queue
cd /www/wwwroot/admin.connectinc.app
php artisan tinker --execute="echo DB::table('jobs')->count();"

# Check failed jobs
php artisan queue:failed

# Manually run scheduler (for testing)
php artisan schedule:run

# List all scheduled tasks
php artisan schedule:list
```

### Restart Supervisor After Config Changes

#### Using aaPanel GUI:

1. Go to **"App Store"** → **"Installed"**
2. Find **"Supervisor"** and click **"Settings"**
3. Find your `laravel-worker` process
4. Click **"Restart"** button

#### Using SSH/Terminal:

```bash
# Re-read config
supervisorctl reread

# Update processes
supervisorctl update

# Restart workers
supervisorctl restart laravel-worker:*

# Check status
supervisorctl status
```

**Or restart all supervisor processes:**

```bash
# Via aaPanel's supervisor manager
supervisorctl restart all
```

---

## 📧 EMAIL SENDING VERIFICATION

### Check if Emails are Being Sent

1. **Check queue jobs table:**

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

2. **Check failed jobs:**

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

3. **Check worker logs:**

```bash
tail -100 /www/wwwroot/admin.connectinc.app/storage/logs/worker.log
```

4. **Check Laravel logs:**

```bash
tail -100 /www/wwwroot/admin.connectinc.app/storage/logs/laravel.log | grep -i "email\|mail\|notification"
```

---

## 🚨 TROUBLESHOOTING

### Scheduled Tasks Not Running?

1. **Check cron is installed:**

```bash
crontab -l
```

2. **Add scheduler cron if missing:**

```bash
* * * * * cd /www/wwwroot/admin.connectinc.app && php artisan schedule:run >> /dev/null 2>&1
```

3. **Check scheduler output:**

```bash
cd /www/wwwroot/admin.connectinc.app
php artisan schedule:run --verbose
```

### Queue Jobs Not Processing?

1. **Check supervisor is running:**

```bash
supervisorctl status
```

2. **Restart workers:**

```bash
supervisorctl restart laravel-worker:*
```

3. **Check for PHP errors:**

```bash
tail -f /www/wwwroot/admin.connectinc.app/storage/logs/worker.log
```

4. **Clear caches and restart:**

```bash
cd /www/wwwroot/admin.connectinc.app
php artisan optimize:clear
supervisorctl restart laravel-worker:*
```

### Emails Not Sending?

1. **Check mail configuration in `.env`:**

```
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@connectinc.app
MAIL_FROM_NAME="Connect Inc"
```

2. **Test email sending:**

```bash
php artisan tinker
>>> Mail::raw('Test email', function($m) { $m->to('your-email@example.com')->subject('Test'); });
```

---

## ✅ VERIFICATION CHECKLIST

Run these checks on your live server:

-   [ ] `supervisorctl status` shows workers running
-   [ ] `crontab -l` shows Laravel scheduler entry
-   [ ] `php artisan schedule:list` shows all 7 tasks
-   [ ] `php artisan queue:work --once` processes a test job
-   [ ] Queue jobs table is being processed (not building up)
-   [ ] Failed jobs table is empty or has few entries
-   [ ] Worker log shows job processing activity
-   [ ] Laravel log shows no critical errors

---

## 📝 SUMMARY

### What Was Wrong:

1. ❌ Schedules in `Kernel.php` were NOT running (Laravel 12 doesn't use it)
2. ❌ Only 3 of 7 scheduled tasks were actually registered
3. ⚠️ Possible missing cron job for scheduler

### What Was Fixed:

1. ✅ All 7 schedules consolidated in `bootstrap/app.php`
2. ✅ All schedules now show in `php artisan schedule:list`
3. ✅ Improved supervisor configuration provided

### Action Required on Server:

1. ⚠️ Verify cron job exists for scheduler
2. ⚠️ Update supervisor config and restart workers
3. ⚠️ Clear caches: `php artisan optimize:clear`

---

**Last Updated:** December 13, 2025
**Laravel Version:** 12.x
