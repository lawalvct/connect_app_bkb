# Block User Posts Feature

## Overview

This feature allows authenticated users to block other users' posts so they won't see posts from blocked users in their feed.

## Endpoints

### 1. Block a User's Posts

**POST** `/api/v1/posts/block-user`

Blocks a user so their posts won't appear in your feed.

**Headers:**

```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "user_id": 123,
    "reason": "Not interested in their content"
}
```

**Response (Success):**

```json
{
    "status": 1,
    "message": "User blocked successfully. You will no longer see their posts.",
    "data": {
        "blocked_user_id": 123,
        "blocked_user_name": "John Doe"
    }
}
```

**Response (Already Blocked):**

```json
{
    "status": 0,
    "message": "User is already blocked"
}
```

---

### 2. Unblock a User's Posts

**POST** `/api/v1/posts/unblock-user`

Unblocks a previously blocked user so you can see their posts again.

**Headers:**

```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "user_id": 123
}
```

**Response (Success):**

```json
{
    "status": 1,
    "message": "User unblocked successfully. You will now see their posts."
}
```

**Response (Not Blocked):**

```json
{
    "status": 0,
    "message": "User is not blocked"
}
```

---

### 3. Get Blocked Users List

**GET** `/api/v1/posts/blocked-users`

Retrieves the list of all users you have blocked.

**Headers:**

```
Authorization: Bearer {your_token}
```

**Response:**

```json
{
    "status": 1,
    "message": "Blocked users retrieved successfully",
    "data": [
        {
            "id": 1,
            "blocked_user": {
                "id": 123,
                "name": "John Doe",
                "username": "johndoe",
                "profile": "profile.jpg",
                "profile_url": "https://example.com/profile.jpg"
            },
            "reason": "Not interested in their content",
            "blocked_at": "2025-01-15 10:30:00"
        },
        {
            "id": 2,
            "blocked_user": {
                "id": 456,
                "name": "Jane Smith",
                "username": "janesmith",
                "profile": "profile2.jpg",
                "profile_url": "https://example.com/profile2.jpg"
            },
            "reason": "Spam",
            "blocked_at": "2025-01-20 14:45:00"
        }
    ]
}
```

---

## How It Works

### Feed Filtering

When you call the feed endpoints:

-   **GET** `/api/v1/posts/feed`
-   **GET** `/api/v1/posts/feed-with-ads`

The system automatically filters out posts from users you have blocked. You won't see:

-   Posts created by blocked users
-   Posts in your feed from those users

### Database Structure

The `block_users` table stores blocking relationships:

-   `user_id` - The user who is blocking
-   `block_user_id` - The user being blocked
-   `reason` - Optional reason for blocking
-   `deleted_flag` - 'N' for active blocks, 'Y' for unblocked

### Soft Deletes

When you unblock a user, the system:

-   Sets `deleted_flag` to 'Y' (soft delete)
-   Keeps the record for audit purposes
-   Immediately allows posts from that user to appear in your feed again

---

## Testing Examples

### Using Postman

#### 1. Block a user

```
POST http://localhost/api/v1/posts/block-user
Headers:
  Authorization: Bearer your_token_here
  Content-Type: application/json
Body:
{
  "user_id": 123,
  "reason": "Don't want to see their posts"
}
```

#### 2. Get blocked users

```
GET http://localhost/api/v1/posts/blocked-users
Headers:
  Authorization: Bearer your_token_here
```

#### 3. Unblock a user

```
POST http://localhost/api/v1/posts/unblock-user
Headers:
  Authorization: Bearer your_token_here
  Content-Type: application/json
Body:
{
  "user_id": 123
}
```

#### 4. Verify in feed

```
GET http://localhost/api/v1/posts/feed
Headers:
  Authorization: Bearer your_token_here
```

The feed should now exclude posts from blocked users.

---

## Error Codes

| Status Code | Message                      | Meaning                                       |
| ----------- | ---------------------------- | --------------------------------------------- |
| 200         | Success                      | Operation completed successfully              |
| 404         | User not found               | The user ID to block doesn't exist            |
| 404         | User is not blocked          | Trying to unblock a user who isn't blocked    |
| 409         | User is already blocked      | Trying to block a user who is already blocked |
| 422         | User ID is required          | Missing user_id in request                    |
| 422         | You cannot block yourself    | Trying to block your own account              |
| 500         | Failed to block/unblock user | Server error                                  |

---

## Notes

-   You cannot block yourself
-   Blocking is one-directional (if you block user A, user A can still see your posts unless they also block you)
-   Blocked users won't be notified that you blocked them
-   The block applies to all post types (text, image, video, etc.)
-   Blocking a user also prevents their posts from appearing in discovery feeds
