# Admin Push Notification System - Complete Guide

## ✅ Issues Fixed

### 1. **Missing 'specific' Target Type**

-   **Problem**: Validation included 'specific' but switch case only handled 'users'
-   **Fix**: Added case for both 'specific' and 'users' targeting

### 2. **N+1 Query Problem**

-   **Problem**: `$user->fcmTokens()` loaded tokens separately for each user
-   **Fix**: Added `with('fcmTokens')` eager loading to all queries

### 3. **Memory Issues with Large User Sets**

-   **Problem**: Loading all users at once could cause memory overflow
-   **Fix**: Added queue job option for async processing

### 4. **Missing Null Coalescing**

-   **Problem**: Could crash if `user_ids`, `social_circle_ids`, or `country_ids` not provided
-   **Fix**: Added `?? []` fallback to all array inputs

### 5. **No Empty User Check**

-   **Problem**: Would attempt to send notifications even if no users found
-   **Fix**: Added early return with 404 response if users empty

### 6. **No Token Cleanup**

-   **Problem**: Inactive tokens accumulate over time
-   **Fix**: Created scheduled command to clean up tokens inactive for 90+ days

---

## 🚀 How to Use

### Send Notifications (Immediate)

```json
POST /admin/notifications/push/send

{
  "title": "New Feature Available",
  "body": "Check out our latest update!",
  "target_type": "all",
  "data": {
    "screen": "home",
    "action": "open_feature"
  }
}
```

### Send Notifications (Queued - Recommended for Large Sets)

```json
POST /admin/notifications/push/send

{
  "title": "System Maintenance",
  "body": "App will be down for 1 hour",
  "target_type": "all",
  "use_queue": true,
  "data": {
    "priority": "high"
  }
}
```

### Target Specific Users

```json
{
    "title": "Welcome!",
    "body": "Thanks for joining",
    "target_type": "users",
    "user_ids": [1, 2, 3, 45],
    "use_queue": false
}
```

### Target Social Circles

```json
{
    "title": "Circle Event",
    "body": "Your circle has a new event",
    "target_type": "social_circles",
    "social_circle_ids": [11, 15],
    "use_queue": true
}
```

### Target Countries

```json
{
    "title": "Regional Update",
    "body": "New features for your region",
    "target_type": "countries",
    "country_ids": [1, 5, 10],
    "use_queue": true
}
```

---

## 🎯 Target Types

| Type                 | Description                      | Required Fields     |
| -------------------- | -------------------------------- | ------------------- |
| `all`                | All users with active FCM tokens | None                |
| `specific` / `users` | Specific user IDs                | `user_ids`          |
| `social_circles`     | Users in specific circles        | `social_circle_ids` |
| `countries`          | Users in specific countries      | `country_ids`       |

---

## ⚙️ Configuration Required

### 1. Firebase Setup (.env)

```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS=path/to/service-account.json
FIREBASE_API_KEY=your-legacy-api-key
FIREBASE_VAPID_KEY=your-vapid-key-for-web-push
```

### 2. Queue Worker (Production)

For queued notifications, you MUST run a queue worker:

```bash
# Start queue worker
php artisan queue:work --queue=default --tries=3

# Or use supervisor for auto-restart
```

### 3. Scheduler (Token Cleanup)

Add to your crontab for automatic token cleanup:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧹 Token Cleanup

### Automatic (Weekly)

Runs every Sunday at 2:00 AM - deactivates tokens inactive for 90+ days

### Manual Cleanup

```bash
# Clean tokens inactive for 90 days (default)
php artisan fcm:cleanup-inactive

# Custom inactivity period (e.g., 30 days)
php artisan fcm:cleanup-inactive --days=30
```

---

## 📊 Response Format

### Success (Immediate Send)

```json
{
    "success": true,
    "message": "Push notifications sent successfully",
    "sent": 245,
    "failed": 5,
    "mode": "immediate"
}
```

### Success (Queued)

```json
{
    "success": true,
    "message": "Push notifications queued successfully",
    "queued": 10000,
    "mode": "queued"
}
```

### No Users Found

```json
{
    "success": false,
    "message": "No users found matching the specified criteria with active FCM tokens",
    "sent": 0,
    "failed": 0
}
```

---

## 🔍 Monitoring

### Check Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log | grep "Push notification"

# Check specific user notification
tail -f storage/logs/laravel.log | grep "user_id: 123"
```

### Check Queue Status

```bash
# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all
```

### Database Logs

All notifications are logged to `push_notification_logs` table:

```sql
SELECT * FROM push_notification_logs
WHERE created_at > NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;
```

---

## ⚡ Performance Recommendations

### When to Use Queue (`use_queue: true`)

-   ✅ Sending to > 100 users
-   ✅ Sending to "all" users
-   ✅ Non-urgent notifications
-   ✅ Scheduled campaigns

### When to Send Immediately (`use_queue: false`)

-   ✅ Critical alerts (< 50 users)
-   ✅ Real-time notifications
-   ✅ Admin testing
-   ✅ Single user notifications

---

## 🛡️ Security

### Admin Authorization

Only authorized admins can access notification endpoints. Configure in:

```php
// app/Providers/TelescopeServiceProvider.php
Gate::define('viewTelescope', function ($user) {
    return in_array($user->email, [
        'admin@example.com',
        // Add admin emails here
    ]);
});
```

### Rate Limiting

Consider adding rate limiting to prevent abuse:

```php
// routes/api.php
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/admin/notifications/push/send', [NotificationController::class, 'sendPushNotification']);
});
```

---

## 🧪 Testing

### Test Single User

```bash
# Create test FCM token for user
php artisan tinker

$user = User::find(1);
$user->fcmTokens()->create([
    'fcm_token' => 'test-token-here',
    'device_id' => 'test-device',
    'platform' => 'android',
    'is_active' => true
]);
```

### Test Notification Send

```bash
curl -X POST http://your-app.com/api/admin/notifications/push/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "title": "Test",
    "body": "Testing notifications",
    "target_type": "users",
    "user_ids": [1]
  }'
```

---

## 📝 Data Payload

All `data` values MUST be strings (FCM requirement). Service auto-converts.

### Good ✅

```json
{
    "data": {
        "user_id": "123",
        "type": "message",
        "count": "5"
    }
}
```

### Bad ❌

```json
{
    "data": {
        "user_id": 123, // Number - will be auto-converted
        "type": "message",
        "count": 5 // Number - will be auto-converted
    }
}
```

---

## 🔧 Troubleshooting

### Notifications Not Received

1. **Check FCM tokens are active**:

```sql
SELECT * FROM user_fcm_tokens WHERE user_id = X AND is_active = 1;
```

2. **Verify Firebase credentials**:

```bash
php artisan tinker
config('firebase.credentials'); // Should return valid path
```

3. **Check logs**:

```bash
tail -f storage/logs/laravel.log | grep "FCM"
```

4. **Test Firebase connection**:

```php
$service = app(\App\Services\FirebaseService::class);
$result = $service->sendNotification('test-token', 'Test', 'Body', []);
```

### Queue Not Processing

```bash
# Check queue status
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush

# Restart queue worker
supervisorctl restart laravel-worker:*
```

### High Failure Rate

-   Invalid/expired tokens will be logged
-   Check `push_notification_logs` for error details
-   Run `php artisan fcm:cleanup-inactive` to remove dead tokens

---

## 📦 Database Schema

### user_fcm_tokens

```sql
- id (bigint)
- user_id (bigint)
- fcm_token (text)
- device_id (varchar)
- platform (enum: android, ios, web)
- app_version (varchar)
- is_active (tinyint)
- last_used_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
```

### push_notification_logs

```sql
- id (bigint)
- user_id (bigint)
- fcm_token (text)
- title (varchar)
- body (text)
- data (json)
- status (enum: success, failed)
- response (text)
- created_at (timestamp)
```

---

## ✨ Best Practices

1. **Always use queue for bulk notifications** (> 100 users)
2. **Clean up inactive tokens regularly** (weekly recommended)
3. **Monitor push_notification_logs** for failure patterns
4. **Test with small user sets first** before broadcasting to all
5. **Keep data payload minimal** (< 4KB recommended)
6. **Use meaningful titles and bodies** (< 255 chars for title)
7. **Include action data** for deep linking (screen, action, id)
8. **Rate limit admin endpoints** to prevent abuse
9. **Log all notification attempts** for debugging
10. **Handle token refresh** in mobile apps

---

## 🎉 System is Production Ready!

All issues have been fixed and optimizations added. The notification system now supports:

✅ Multiple targeting options (all, users, circles, countries)
✅ Queue-based async processing for scalability
✅ Automatic token cleanup
✅ Comprehensive error handling
✅ Performance optimizations (eager loading, chunking)
✅ Multi-platform support (Android, iOS, Web)
✅ Complete logging and monitoring

**Next Steps**:

1. Configure Firebase credentials in `.env`
2. Start queue worker: `php artisan queue:work`
3. Enable scheduler: Add cron job for `php artisan schedule:run`
4. Test with small user set
5. Monitor logs during first bulk send
6. Scale as needed!
