# Profile Upload Likes Feature

## Overview

This feature allows users to like/unlike individual photos and videos uploaded by other users to their profiles. Each upload tracks its total like count and maintains a history of which users liked it.

## Database Changes

### 1. Migration: Add `like_count` to `user_profile_uploads`

**File:** `database/migrations/2025_05_23_172238_create_user_profile_uploads_table.php`

Added column:

-   `like_count` (unsigned bigint, default: 0) - Tracks total likes for each upload

### 2. Migration: Create `user_profile_upload_likes` table

**File:** `database/migrations/2025_12_14_120000_create_user_profile_upload_likes_table.php`

Tracks which users liked which uploads:

-   `id` - Primary key
-   `user_id` - User who liked (foreign key to users)
-   `upload_id` - Upload that was liked (foreign key to user_profile_uploads)
-   `created_at`, `updated_at` - Timestamps
-   **Unique constraint:** Prevents duplicate likes from same user
-   **Indexes:** On `user_id` and `upload_id` for performance

## Model Updates

### UserProfileUpload Model

**File:** `app/Models/UserProfileUpload.php`

**New Properties:**

-   Added `like_count` to `$fillable` array

**New Methods:**

-   `likes()` - Returns all users who liked this upload (many-to-many relationship)
-   `isLikedBy($userId)` - Checks if a specific user has liked this upload
-   `getLikeCountAttribute()` - Accessor to ensure like_count is always an integer

## API Endpoints

### Base URL

All endpoints require authentication with Bearer token.

### 1. Toggle Like/Unlike

**POST** `/api/v1/profile/uploads/{uploadId}/like`

Toggle like status - like if not liked, unlike if already liked.

**Response:**

```json
{
    "success": true,
    "message": "Upload liked successfully",
    "data": {
        "liked": true,
        "like_count": 42
    }
}
```

### 2. Get Users Who Liked an Upload

**GET** `/api/v1/profile/uploads/{uploadId}/likes`

Get paginated list of users who liked a specific upload.

**Query Parameters:**

-   `per_page` (optional, default: 20) - Items per page
-   `page` (optional, default: 1) - Current page

**Response:**

```json
{
    "success": true,
    "message": "Likes retrieved successfully",
    "data": {
        "upload_id": 123,
        "total_likes": 42,
        "likes": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "profile_image": "https://..."
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 42,
            "last_page": 3,
            "has_more": true
        }
    }
}
```

### 3. Check Like Status

**GET** `/api/v1/profile/uploads/{uploadId}/like-status`

Check if current user has liked a specific upload.

**Response:**

```json
{
    "success": true,
    "message": "Like status retrieved successfully",
    "data": {
        "upload_id": 123,
        "is_liked": true,
        "like_count": 42
    }
}
```

### 4. Get My Liked Uploads

**GET** `/api/v1/profile/uploads/my-likes`

Get all uploads that the authenticated user has liked.

**Query Parameters:**

-   `per_page` (optional, default: 20) - Items per page
-   `page` (optional, default: 1) - Current page

**Response:**

```json
{
    "success": true,
    "message": "Liked uploads retrieved successfully",
    "data": {
        "uploads": [
            {
                "id": 123,
                "file_url": "https://...",
                "file_type": "image",
                "like_count": 42,
                "user": {
                    "id": 1,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_image": "https://..."
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 15,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

## Notifications

### Push & In-App Notifications

**Job:** `app/Jobs/SendProfileUploadLikeNotificationJob.php`

When a user likes a photo/video, the upload owner receives:

1. **In-App Notification**

    - Title: "New Like! 📷" (or 🎥 for videos)
    - Message: "[Liker Name] liked your photo/video"
    - Type: `profile_upload_like`
    - Priority: 5 (medium)

2. **Push Notification** (via Firebase)
    - Sent to all active FCM tokens
    - Same title and message as in-app
    - Data payload includes: liker details, upload info, total likes

**Features:**

-   Background job processing (queue: `notifications`)
-   Doesn't notify if user likes own upload
-   Retry logic: 3 attempts with backoff [10, 30, 60] seconds
-   Comprehensive logging for debugging
-   CamelCase keys for FCM v1 API compatibility

**Notification Data Payload:**

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
    "actionUrl": "/profile/789/uploads/456"
}
```

## Implementation Details

### Controller

**File:** `app/Http/Controllers/API/V1/ProfileUploadLikeController.php`

**Features:**

-   Transaction safety with DB::beginTransaction() and DB::commit()
-   Automatic like count increment/decrement
-   Comprehensive error logging
-   Prevents duplicate likes with unique constraint
-   Efficient pagination for large datasets
-   Dispatches notification job on like (not unlike)
-   Skips notification if user likes own upload

**Error Handling:**

-   Returns 404 if upload not found or deleted
-   Returns 500 with error details on exceptions
-   Rolls back transactions on errors

### Routes

**File:** `routes/api/v1.php`

All routes are:

-   Protected with `auth:sanctum` middleware
-   Grouped under `profile/uploads` prefix
-   Properly documented in route file

## Database Migration Steps

1. **Run migrations:**

    ```bash
    php artisan migrate
    ```

2. **If migrations already ran, add column manually:**

    ```sql
    ALTER TABLE user_profile_uploads
    ADD COLUMN like_count BIGINT UNSIGNED DEFAULT 0
    AFTER metadata;
    ```

3. **Create pivot table:**
    ```sql
    CREATE TABLE user_profile_upload_likes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        upload_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (upload_id) REFERENCES user_profile_uploads(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_upload (user_id, upload_id),
        INDEX idx_user_id (user_id),
        INDEX idx_upload_id (upload_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ```

## Testing Guide

### Test Scenarios

1. **Like an upload:**

    ```bash
    POST /api/v1/profile/uploads/123/like
    Headers: Authorization: Bearer {token}
    ```

2. **Unlike an upload (same endpoint):**

    ```bash
    POST /api/v1/profile/uploads/123/like
    Headers: Authorization: Bearer {token}
    ```

3. **Get likes for an upload:**

    ```bash
    GET /api/v1/profile/uploads/123/likes?per_page=10&page=1
    Headers: Authorization: Bearer {token}
    ```

4. **Check if I liked an upload:**

    ```bash
    GET /api/v1/profile/uploads/123/like-status
    Headers: Authorization: Bearer {token}
    ```

5. **Get my liked uploads:**
    ```bash
    GET /api/v1/profile/uploads/my-likes?per_page=20
    Headers: Authorization: Bearer {token}
    ```

### Expected Behaviors

1. ✅ User can like an upload once
2. ✅ Liking again will unlike it (toggle behavior)
3. ✅ Like count increments/decrements automatically
4. ✅ Cannot like deleted uploads (deleted_flag = 'Y')
5. ✅ Database prevents duplicate likes via unique constraint
6. ✅ Cascade delete: Deleting user or upload removes associated likes
7. ✅ Pagination works for large datasets
8. ✅ All operations are logged for debugging

## Frontend Integration Tips

### Display Like Count

```javascript
// Show like count on upload card
<div class="upload-card">
    <img src={upload.file_url} />
    <div class="like-section">
        <button onClick={() => toggleLike(upload.id)}>
            {upload.is_liked ? "❤️" : "🤍"}
        </button>
        <span>{upload.like_count} likes</span>
    </div>
</div>
```

### Toggle Like Function

```javascript
async function toggleLike(uploadId) {
    try {
        const response = await fetch(
            `/api/v1/profile/uploads/${uploadId}/like`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
            }
        );
        const data = await response.json();

        // Update UI with new like count
        updateUploadLikeCount(uploadId, data.data.like_count, data.data.liked);
    } catch (error) {
        console.error("Error toggling like:", error);
    }
}
```

### Show Who Liked

```javascript
async function showLikes(uploadId) {
    try {
        const response = await fetch(
            `/api/v1/profile/uploads/${uploadId}/likes?per_page=20`,
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            }
        );
        const data = await response.json();

        // Display modal with users who liked
        displayLikesModal(data.data.likes);
    } catch (error) {
        console.error("Error fetching likes:", error);
    }
}
```

## Performance Considerations

1. **Indexes:** Created on `user_id` and `upload_id` in pivot table for fast lookups
2. **Caching:** Consider caching like counts for popular uploads
3. **Pagination:** Default 20 items per page prevents large data transfers
4. **Eager Loading:** Use `->with(['user'])` to prevent N+1 queries when showing uploads
5. **Database Transactions:** Ensures data consistency even under high load

## Security Features

1. ✅ Authentication required for all endpoints
2. ✅ Users can only see active uploads (deleted_flag = 'N')
3. ✅ Unique constraint prevents duplicate likes
4. ✅ Foreign key constraints ensure referential integrity
5. ✅ All inputs validated and sanitized
6. ✅ Comprehensive error logging without exposing sensitive data

## Future Enhancements

Potential features to add:

-   [ ] Push notifications when someone likes your upload
-   [ ] Activity feed showing recent likes
-   [ ] Most liked uploads endpoint
-   [ ] Like analytics (likes over time)
-   [ ] Unlike notification to upload owner
-   [ ] Batch like/unlike operations
-   [ ] Like restrictions based on user relationships
