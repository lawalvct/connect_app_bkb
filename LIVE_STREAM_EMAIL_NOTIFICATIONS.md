# Live Stream Email Notification System

## Overview

When an admin creates a new live stream, the system automatically sends email notifications to all active users in the platform. This feature uses Laravel queues and chunking to efficiently handle 3000+ users.

---

## Implementation Details

### Files Created/Modified

#### 1. **Mailable Class** - `app/Mail/NewLiveStreamNotification.php`

-   Implements `ShouldQueue` for queued email delivery
-   Contains stream details: title, description, banner, pricing, schedule
-   Dynamic subject line based on stream status (Live vs Upcoming)
-   Template: `resources/views/emails/new-live-stream.blade.php`

#### 2. **Queue Job** - `app/Jobs/SendLiveStreamNotifications.php`

-   Processes email sending in chunks of 100 users
-   Filters: Active users, verified emails, email notifications enabled
-   Error handling: Logs failures, retries up to 3 times
-   Timeout: 5 minutes per job execution
-   Small delay (100ms) between chunks to prevent mail server overload

#### 3. **Controller** - `app/Http/Controllers/Admin/StreamManagementController.php`

-   Updated `store()` method to dispatch notification job after stream creation
-   Logs stream creation and notification queue status

#### 4. **Email Template** - `resources/views/emails/new-live-stream.blade.php`

-   Beautiful, responsive HTML email design
-   Shows stream banner, title, description, host name
-   Live badge with pulsing animation for live streams
-   Pricing information (free/paid with free minutes)
-   Call-to-action button ("Watch Now" or "Set Reminder")
-   Links to notification preferences

---

## How It Works

### Flow Diagram

```
Admin Creates Stream
        ↓
Stream Saved to Database
        ↓
Job Dispatched to Queue (SendLiveStreamNotifications)
        ↓
Query Active Users (is_active=true, email_verified, notification_email=true)
        ↓
Process in Chunks of 100 Users
        ↓
Send Email to Each User
        ↓
Log Success/Failure
        ↓
100ms Delay → Next Chunk
```

### User Filtering Criteria

The system only sends emails to users who meet ALL of these criteria:

-   `is_active = true` - Active account
-   `deleted_flag = 'N'` - Not soft-deleted
-   `is_banned = false` - Not banned
-   `email IS NOT NULL` - Has email address
-   `email_verified_at IS NOT NULL` - Email verified
-   `notification_email = true` - Email notifications enabled

---

## Queue Configuration

### Current Setup

-   **Queue Driver**: Database (configured in `.env`)
-   **Table**: `jobs` (default Laravel queue table)
-   **Retries**: 3 attempts
-   **Timeout**: 300 seconds (5 minutes)

### Starting the Queue Worker

#### Development (Manual)

```powershell
php artisan queue:work --tries=3 --timeout=300
```

#### Production (Supervisor - Linux)

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --tries=3 --timeout=300
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

#### Production (Windows Service)

Use NSSM (Non-Sucking Service Manager):

```powershell
nssm install LaravelQueue "C:\php\php.exe" "C:\laragon\www\connect_app_bkb\artisan queue:work --tries=3 --timeout=300"
nssm start LaravelQueue
```

---

## Performance Optimization

### Chunk Size

Currently set to **100 users per chunk**. Adjust in `SendLiveStreamNotifications.php`:

```php
->chunk(100, function ($users) use (&$emailsSent, &$emailsFailed) {
```

**Recommendations**:

-   **< 1,000 users**: Chunk size 100-200
-   **1,000-5,000 users**: Chunk size 100
-   **5,000-10,000 users**: Chunk size 50
-   **> 10,000 users**: Chunk size 25-50

### Delay Between Chunks

Currently **100ms (0.1 seconds)**. Adjust in `SendLiveStreamNotifications.php`:

```php
usleep(100000); // 100ms delay
```

**Recommendations**:

-   **Shared hosting**: 200-500ms
-   **VPS/Dedicated**: 100-200ms
-   **High-performance mail server**: 50-100ms

### Queue Priority

For multiple queue jobs, prioritize stream notifications:

```powershell
php artisan queue:work --queue=high,default,low --tries=3
```

Then dispatch with priority:

```php
SendLiveStreamNotifications::dispatch($stream)->onQueue('high');
```

---

## Monitoring & Logging

### Check Queue Status

```powershell
# View pending jobs
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Log Files

-   **Job execution**: `storage/logs/laravel.log`
-   **Email sent**: Look for "Stream notification sent" entries
-   **Failures**: Look for "Failed to send stream notification" entries

### Example Log Entries

```
[INFO] Starting live stream email notifications
  stream_id: 123
  stream_title: "Amazing Live Concert"
  status: live

[DEBUG] Stream notification sent
  user_id: 456
  email: user@example.com
  stream_id: 123

[INFO] Completed live stream email notifications
  stream_id: 123
  emails_sent: 3245
  emails_failed: 5
```

---

## Testing

### 1. Manual Test (Single Stream)

```php
// Create test stream via admin panel
// Check jobs table
SELECT * FROM jobs WHERE queue = 'default' ORDER BY id DESC LIMIT 1;

// Run queue worker
php artisan queue:work --once

// Check logs
tail -f storage/logs/laravel.log
```

### 2. Check Email Delivery

-   Use Mailtrap.io (development)
-   Check user inboxes (production)
-   Monitor mail server logs

### 3. Load Test

```php
// Create multiple streams rapidly
// Monitor queue table size
// Monitor memory usage
// Check email delivery rate
```

---

## Email Template Customization

### Modify Template

Edit `resources/views/emails/new-live-stream.blade.php`

### Available Variables

-   `$streamTitle` - Stream title
-   `$streamDescription` - Stream description
-   `$streamerName` - Host name
-   `$streamUrl` - Link to stream page
-   `$bannerUrl` - Stream banner image URL
-   `$isLive` - Boolean (true if live now)
-   `$scheduledAt` - Carbon date for scheduled streams
-   `$isFree` - Boolean (true if free)
-   `$price` - Stream price
-   `$currency` - Currency code (USD, NGN, etc.)
-   `$freeMinutes` - Free viewing minutes

### Styling

-   Inline CSS (email-safe)
-   Responsive design (mobile-friendly)
-   Gradient backgrounds
-   Animated live badge (pulsing effect)

---

## Configuration Options

### Disable Notifications (Per User)

Users can disable email notifications in their settings:

```sql
UPDATE users SET notification_email = 0 WHERE id = ?;
```

### Disable Feature Globally

Comment out the dispatch line in `StreamManagementController.php`:

```php
// SendLiveStreamNotifications::dispatch($stream);
```

### Change Recipient Criteria

Modify the query in `SendLiveStreamNotifications.php`:

```php
User::where('is_active', true)
    ->where('deleted_flag', 'N')
    ->where('is_banned', false)
    ->whereNotNull('email')
    ->whereNotNull('email_verified_at')
    ->where('notification_email', true)
    // Add custom criteria here
    ->chunk(100, function ($users) { ... });
```

---

## Troubleshooting

### Issue: Emails Not Sending

**Check**:

1. Queue worker is running: `ps aux | grep queue:work`
2. Jobs in queue: `SELECT COUNT(*) FROM jobs;`
3. Failed jobs: `php artisan queue:failed`
4. Mail configuration in `.env`

**Solution**:

```powershell
php artisan queue:restart
php artisan queue:work --tries=3 --timeout=300
```

### Issue: Some Users Not Receiving Emails

**Check**:

1. User's `notification_email` setting
2. User's `email_verified_at` is not null
3. User's `is_active` status
4. Mail server limits (SPF, DKIM, rate limits)

### Issue: Queue Processing Too Slow

**Solutions**:

1. Increase chunk size (if memory allows)
2. Reduce delay between chunks
3. Run multiple queue workers
4. Use Redis instead of database queue

### Issue: Memory Exhaustion

**Solutions**:

1. Reduce chunk size
2. Increase PHP memory limit in `.env`:
    ```
    MEMORY_LIMIT=512M
    ```
3. Add memory limit to queue worker:
    ```powershell
    php -d memory_limit=512M artisan queue:work
    ```

---

## Best Practices

### 1. Queue Worker Management

-   Always run queue worker in production
-   Use supervisor (Linux) or NSSM (Windows)
-   Monitor worker health
-   Restart workers daily to prevent memory leaks

### 2. Email Sending

-   Use dedicated mail service (SendGrid, Mailgun, AWS SES)
-   Configure SPF and DKIM records
-   Monitor bounce rates
-   Respect user preferences

### 3. Performance

-   Keep chunk sizes reasonable (50-200)
-   Add delays to prevent server overload
-   Monitor database query performance
-   Use Redis for high-traffic sites

### 4. Logging

-   Log all email attempts
-   Track success/failure rates
-   Alert on high failure rates
-   Archive old logs regularly

---

## Future Enhancements

### Planned Features

-   [ ] Email open tracking
-   [ ] Click tracking for "Watch Now" button
-   [ ] A/B testing for email templates
-   [ ] User preferences for stream categories
-   [ ] Digest mode (daily summary instead of immediate)
-   [ ] Push notifications (in addition to email)
-   [ ] SMS notifications for premium users

### Suggested Improvements

-   [ ] Add unsubscribe link
-   [ ] Personalize content based on user interests
-   [ ] Include related streams
-   [ ] Add social sharing buttons
-   [ ] Preview option before sending
-   [ ] Schedule notifications for optimal delivery time

---

## API Endpoints (Future)

### Get Notification Status

```http
GET /api/v1/admin/streams/{id}/notification-status
```

Response:

```json
{
    "stream_id": 123,
    "notifications_sent": 3245,
    "notifications_failed": 5,
    "notifications_pending": 0,
    "job_status": "completed",
    "sent_at": "2025-12-09T10:30:00Z"
}
```

---

## Support

For issues or questions:

1. Check logs: `storage/logs/laravel.log`
2. Review queue status: `php artisan queue:monitor`
3. Check documentation: This file
4. Contact development team

---

**Version**: 1.0
**Last Updated**: December 9, 2025
**Status**: Production Ready ✅
