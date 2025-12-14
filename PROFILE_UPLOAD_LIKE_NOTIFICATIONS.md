# Profile Upload Like Notifications - Implementation Summary

## ✅ What Was Added

### 1. Notification Job

**File:** `app/Jobs/SendProfileUploadLikeNotificationJob.php`

A background job that sends notifications when someone likes a profile photo/video.

**Features:**

-   ✅ In-app notification (UserNotification model)
-   ✅ Push notification via Firebase (FCM v1 API)
-   ✅ Doesn't notify if user likes own upload
-   ✅ Queue: `notifications`
-   ✅ Retry: 3 attempts with backoff [10, 30, 60] seconds
-   ✅ Comprehensive logging for debugging

**Notification Content:**

-   **Photos:** "New Like! 📷" - "[Name] liked your photo"
-   **Videos:** "New Like! 🎥" - "[Name] liked your video"

**Data Payload (camelCase for FCM v1):**

```json
{
    "type": "profile_upload_like",
    "likerId": "123",
    "likerName": "John Doe",
    "likerUsername": "johndoe",
    "likerProfile": "https://...",
    "uploadId": "456",
    "uploadType": "image",
    "uploadUrl": "https://...",
    "totalLikes": "42",
    "actionUrl": "/profile/789/uploads/456",
    "clickAction": "FLUTTER_NOTIFICATION_CLICK"
}
```

### 2. Controller Update

**File:** `app/Http/Controllers/API/V1/ProfileUploadLikeController.php`

**Changes:**

-   Added `use App\Jobs\SendProfileUploadLikeNotificationJob;`
-   Added notification dispatch in `toggleLike()` method
-   Dispatches job ONLY when liking (not unliking)
-   Skips dispatch if user likes own upload
-   Wrapped in try-catch to prevent blocking main response

**Code Added (lines ~75-95):**

```php
// Dispatch notification job (don't send if user liked own upload)
if ($user->id !== $upload->user_id) {
    try {
        SendProfileUploadLikeNotificationJob::dispatch(
            $user->id,
            $upload->user_id,
            $uploadId
        )->onQueue('notifications');

        Log::channel('daily')->info('Profile upload like notification job dispatched', [
            'liker_id' => $user->id,
            'upload_owner_id' => $upload->user_id,
            'upload_id' => $uploadId
        ]);
    } catch (\Exception $e) {
        Log::channel('daily')->error('Failed to dispatch profile upload like notification', [
            'error' => $e->getMessage(),
            'liker_id' => $user->id,
            'upload_id' => $uploadId
        ]);
    }
}
```

### 3. Documentation Updates

**PROFILE_UPLOAD_LIKES_GUIDE.md:**

-   Added comprehensive notifications section
-   Documented notification data payload
-   Added features and behaviors

**BACKGROUND_JOBS_GUIDE.md:**

-   Added `SendProfileUploadLikeNotificationJob` to jobs table

## 🚀 How It Works

### Flow:

1. User A likes User B's profile photo/video
2. API endpoint: `POST /api/v1/profile/uploads/{uploadId}/like`
3. Controller increments `like_count` in database
4. Controller dispatches `SendProfileUploadLikeNotificationJob` to `notifications` queue
5. Queue worker picks up the job
6. Job creates in-app notification in `user_notifications` table
7. Job sends push notification to all active FCM tokens of User B
8. User B sees notification on their device

### Smart Behavior:

-   ✅ Only sends notification on LIKE (not unlike)
-   ✅ Skips notification if user likes own upload
-   ✅ Non-blocking: Uses background job queue
-   ✅ Continues even if notification fails
-   ✅ Main API response not affected by notification status

## 📱 Mobile App Integration

The mobile app should listen for notifications with type `profile_upload_like`:

```dart
// Example Flutter/React Native handler
void handleNotification(Map<String, dynamic> data) {
  if (data['type'] == 'profile_upload_like') {
    String likerId = data['likerId'];
    String likerName = data['likerName'];
    String uploadId = data['uploadId'];
    String totalLikes = data['totalLikes'];

    // Navigate to profile upload view
    navigateToUpload(uploadId);
  }
}
```

## 🧪 Testing

### Test Like Notification:

1. User A likes User B's profile photo

    ```bash
    POST /api/v1/profile/uploads/123/like
    Headers: Authorization: Bearer {user_a_token}
    ```

2. Check logs:

    ```bash
    tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "SendProfileUploadLikeNotificationJob"
    ```

3. Verify User B receives:
    - In-app notification in database:
        ```sql
        SELECT * FROM user_notifications
        WHERE user_id = {user_b_id}
        AND type = 'profile_upload_like'
        ORDER BY created_at DESC LIMIT 1;
        ```
    - Push notification on their mobile device

### Test Own Upload (Should NOT notify):

1. User A likes their own photo

    ```bash
    POST /api/v1/profile/uploads/456/like
    Headers: Authorization: Bearer {user_a_token}
    ```

2. Check logs - should see: "User liked own upload - skipping notification"

3. No notification should be created

## 🔧 Queue Configuration

Ensure supervisor is configured with notifications queue:

```ini
[program:laravel-worker]
command=php artisan queue:work database --queue=default,notifications --sleep=3 --tries=3 --max-time=3600
```

Restart workers after deployment:

```bash
supervisorctl restart laravel-worker:*
```

## 📊 Monitoring

### Check Job Status:

```sql
-- Pending jobs
SELECT * FROM jobs WHERE queue = 'notifications' ORDER BY created_at DESC LIMIT 10;

-- Failed jobs
SELECT * FROM failed_jobs WHERE queue = 'notifications' ORDER BY failed_at DESC LIMIT 10;
```

### Check Notifications Sent:

```sql
-- Recent profile upload like notifications
SELECT
    un.id,
    un.user_id,
    u.name AS receiver_name,
    un.message,
    un.read_at,
    un.created_at
FROM user_notifications un
JOIN users u ON un.user_id = u.id
WHERE un.type = 'profile_upload_like'
ORDER BY un.created_at DESC
LIMIT 20;
```

## ⚡ Performance Considerations

-   **Non-blocking:** Notification job runs in background, doesn't slow down like action
-   **Batching:** FCM handles multiple tokens efficiently
-   **Retry Logic:** Failed notifications retry 3 times with exponential backoff
-   **Queue Priority:** Uses dedicated `notifications` queue for better isolation
-   **Database:** Indexed foreign keys ensure fast lookups

## 🔐 Security

-   ✅ Only authenticated users can like uploads
-   ✅ Only active uploads (deleted_flag = 'N') can be liked
-   ✅ Duplicate likes prevented by unique constraint
-   ✅ User can't spam notifications by liking own content
-   ✅ FCM tokens validated before sending
-   ✅ All errors logged without exposing sensitive data

## 📝 Files Changed

1. ✅ `app/Jobs/SendProfileUploadLikeNotificationJob.php` - Created
2. ✅ `app/Http/Controllers/API/V1/ProfileUploadLikeController.php` - Updated
3. ✅ `PROFILE_UPLOAD_LIKES_GUIDE.md` - Updated
4. ✅ `BACKGROUND_JOBS_GUIDE.md` - Updated

## ✨ Ready to Deploy!

The notification system is fully integrated and ready for production use. No additional migrations needed - just deploy and restart queue workers!
