# Stream Interactions API Documentation

## Overview

The Stream Interactions API provides endpoints for users to like, dislike, and share streams. It includes comprehensive tracking of user interactions and provides real-time statistics.

## Features

-   ✅ **Like/Dislike System**: Users can like or dislike streams with toggle functionality
-   ✅ **Share Tracking**: Track stream shares across multiple platforms (Facebook, Twitter, WhatsApp, etc.)
-   ✅ **Real-time Counts**: Automatic count updates in the database
-   ✅ **User Interaction History**: Track what each user has done with each stream
-   ✅ **Flexible Share Metadata**: Store custom data with shares
-   ✅ **Mutual Exclusivity**: Users can't like and dislike the same stream simultaneously

## Database Schema

### Stream Interactions Table

```sql
CREATE TABLE stream_interactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    stream_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    interaction_type ENUM('like', 'dislike', 'share'),
    share_platform VARCHAR(255) NULL,
    share_metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (stream_id) REFERENCES streams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_stream_user_interaction (stream_id, user_id, interaction_type)
);
```

### Streams Table Updates

```sql
ALTER TABLE streams ADD COLUMN likes_count INT DEFAULT 0;
ALTER TABLE streams ADD COLUMN dislikes_count INT DEFAULT 0;
ALTER TABLE streams ADD COLUMN shares_count INT DEFAULT 0;
```

## API Endpoints

### Authentication

All endpoints require authentication using Laravel Sanctum tokens:

```
Authorization: Bearer {your-token}
```

### 1. Like a Stream

**Endpoint:** `POST /api/v1/streams/{id}/like`

**Description:** Toggle like for a stream. If user already liked, it removes the like. If user disliked, it changes to like.

**Response:**

```json
{
    "success": true,
    "message": "Stream like toggled successfully",
    "data": {
        "action": "added|removed|changed",
        "type": "like",
        "from": "dislike", // Only present when changed
        "interaction_stats": {
            "likes_count": 1,
            "dislikes_count": 0,
            "shares_count": 3
        },
        "user_interaction": "like"
    }
}
```

### 2. Dislike a Stream

**Endpoint:** `POST /api/v1/streams/{id}/dislike`

**Description:** Toggle dislike for a stream. Similar behavior to like endpoint.

**Response:**

```json
{
    "success": true,
    "message": "Stream dislike toggled successfully",
    "data": {
        "action": "added|removed|changed",
        "type": "dislike",
        "from": "like", // Only present when changed
        "interaction_stats": {
            "likes_count": 0,
            "dislikes_count": 1,
            "shares_count": 3
        },
        "user_interaction": "dislike"
    }
}
```

### 3. Share a Stream

**Endpoint:** `POST /api/v1/streams/{id}/share`

**Description:** Record a stream share. Allows multiple shares per user.

**Request Body:**

```json
{
    "platform": "facebook|twitter|whatsapp|instagram|telegram|email|copy_link",
    "metadata": {
        "message": "Check out this amazing stream!",
        "recipients": ["friend1", "friend2"],
        "url": "https://example.com/streams/123",
        "hashtags": ["livestream", "connect"]
    }
}
```

**Response:**

```json
{
    "success": true,
    "message": "Stream shared successfully",
    "data": {
        "share_id": 123,
        "platform": "facebook",
        "shared_at": "2025-09-05T10:30:00.000000Z",
        "interaction_stats": {
            "likes_count": 1,
            "dislikes_count": 0,
            "shares_count": 4
        },
        "share_url": "http://localhost/streams/161"
    }
}
```

### 4. Get Interaction Stats

**Endpoint:** `GET /api/v1/streams/{id}/interactions`

**Description:** Get comprehensive interaction statistics for a stream.

**Response:**

```json
{
    "success": true,
    "message": "Stream interaction stats retrieved successfully",
    "data": {
        "stream_id": 161,
        "interaction_stats": {
            "likes_count": 1,
            "dislikes_count": 0,
            "shares_count": 3
        },
        "user_interaction": "like",
        "has_liked": true,
        "has_disliked": false
    }
}
```

### 5. Get Stream Shares

**Endpoint:** `GET /api/v1/streams/{id}/shares`

**Description:** Get detailed list of stream shares.

**Query Parameters:**

-   `limit` (optional): Number of shares to return (default: 20)

**Response:**

```json
{
    "success": true,
    "message": "Stream shares retrieved successfully",
    "data": {
        "stream_id": 161,
        "shares_count": 3,
        "shares": [
            {
                "id": 10,
                "platform": "whatsapp",
                "shared_at": "2025-09-05T10:18:21.000000Z",
                "user": {
                    "id": 3968,
                    "name": "Test Admin",
                    "username": "testadmin"
                },
                "metadata": {
                    "message": "Join me on this live stream",
                    "recipients": ["friend1", "friend2"],
                    "shared_at": "2025-09-05T10:18:21.000000Z",
                    "stream_title": "Test Stream for Interactions",
                    "streamer_name": "Test Admin"
                }
            }
        ]
    }
}
```

### 6. Remove Interaction

**Endpoint:** `DELETE /api/v1/streams/{id}/interactions`

**Description:** Remove a specific interaction (like or dislike).

**Request Body:**

```json
{
    "interaction_type": "like|dislike"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Interaction removed successfully",
    "data": {
        "removed_interaction": "like",
        "interaction_stats": {
            "likes_count": 0,
            "dislikes_count": 0,
            "shares_count": 3
        },
        "user_interaction": null
    }
}
```

### 7. Stream Details (Enhanced)

**Endpoint:** `GET /api/v1/streams/{id}`

**Description:** Get stream details including interaction data.

**Response:**

```json
{
    "success": true,
    "message": "Stream details retrieved successfully",
    "data": {
        "stream": {
            "id": 161,
            "title": "Test Stream for Interactions",
            "description": "Testing likes, dislikes, and shares",
            "status": "live",
            "likes_count": 1,
            "dislikes_count": 0,
            "shares_count": 3,
            "user_interaction": "like",
            "has_liked": true,
            "has_disliked": false,
            "streamer": {
                "id": 3968,
                "name": "Test Admin",
                "username": "testadmin"
            }
        }
    }
}
```

## Usage Examples

### cURL Examples

#### Like a Stream

```bash
curl -X POST "http://localhost:8000/api/v1/streams/161/like" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json"
```

#### Share on Social Media

```bash
curl -X POST "http://localhost:8000/api/v1/streams/161/share" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "twitter",
    "metadata": {
      "message": "Live streaming now! Join me!",
      "hashtags": ["livestream", "connect", "live"],
      "url": "https://example.com/streams/161"
    }
  }'
```

#### Get Stats

```bash
curl -X GET "http://localhost:8000/api/v1/streams/161/interactions" \
  -H "Authorization: Bearer your-token"
```

### JavaScript/Fetch Examples

#### Like a Stream

```javascript
fetch("/api/v1/streams/161/like", {
    method: "POST",
    headers: {
        Authorization: "Bearer your-token",
        "Content-Type": "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => {
        console.log("Like status:", data);
        // Update UI with new like count
        updateLikeButton(data.data.interaction_stats.likes_count);
    });
```

#### Share Stream

```javascript
const shareStream = async (platform, message) => {
    const response = await fetch("/api/v1/streams/161/share", {
        method: "POST",
        headers: {
            Authorization: "Bearer your-token",
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            platform: platform,
            metadata: {
                message: message,
                url: window.location.href,
            },
        }),
    });

    const result = await response.json();
    console.log("Share result:", result);
};
```

## Error Handling

### Common Error Responses

#### Stream Not Found

```json
{
    "success": false,
    "message": "Stream not found",
    "data": null
}
```

#### Validation Errors

```json
{
    "success": false,
    "message": "Validation Error",
    "data": {
        "platform": ["The selected platform is invalid."]
    }
}
```

#### Authentication Required

```json
{
    "message": "Unauthenticated."
}
```

## Business Logic

### Like/Dislike Rules

1. **Mutual Exclusivity**: Users cannot like and dislike the same stream
2. **Toggle Behavior**: Clicking like when already liked removes the like
3. **Switch Behavior**: Clicking dislike when liked switches to dislike
4. **Count Updates**: All counts are updated automatically in real-time

### Share Rules

1. **Multiple Shares**: Users can share the same stream multiple times
2. **Platform Tracking**: Each share records the platform used
3. **Metadata Storage**: Flexible metadata storage for custom share data
4. **Anonymous Shares**: Shares are always attributed to authenticated users

### Database Consistency

-   All interaction counts are maintained automatically
-   Soft deletes are handled properly
-   Foreign key constraints ensure data integrity
-   Indexes optimize query performance

## Performance Considerations

### Optimizations

-   Database indexes on frequently queried columns
-   Efficient count updates using direct SQL
-   Batch operations where possible
-   Proper relationship loading to avoid N+1 queries

### Caching Recommendations

-   Cache interaction counts for popular streams
-   Cache user interaction status
-   Use Redis for real-time updates in high-traffic scenarios

## Security

### Authentication

-   All endpoints require valid authentication tokens
-   Token validation through Laravel Sanctum
-   User authorization checked for each request

### Data Validation

-   Input validation on all endpoints
-   Platform whitelist for shares
-   Metadata size limits
-   Rate limiting on interaction endpoints

### Privacy

-   User interaction data is only visible to the user themselves
-   Share metadata may contain sensitive information - handle carefully
-   Respect user privacy settings in share functionality

## Testing

Run the test scripts to verify functionality:

```bash
# Test the core functionality
php test_stream_interactions.php

# Generate API examples
php test_stream_api_endpoints.php
```

## Migration Commands

To set up the database tables:

```bash
php artisan migrate
```

This will create:

-   `stream_interactions` table
-   Add interaction count columns to `streams` table
-   Set up proper indexes and constraints

## Models and Relationships

### StreamInteraction Model

-   Handles all interaction logic
-   Provides scopes for filtering by type
-   Manages count updates automatically

### Stream Model (Enhanced)

-   Added interaction relationships
-   Helper methods for user interaction status
-   Automatic count accessors

## Rate Limiting

Consider implementing rate limiting for interaction endpoints:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/streams/{id}/like', [StreamController::class, 'likeStream']);
    Route::post('/streams/{id}/dislike', [StreamController::class, 'dislikeStream']);
    Route::post('/streams/{id}/share', [StreamController::class, 'shareStream']);
});
```

## Future Enhancements

### Potential Features

-   **Reaction Types**: Expand beyond like/dislike (love, laugh, angry, etc.)
-   **Share Analytics**: Track click-through rates on shared links
-   **Interaction History**: Show user's interaction timeline
-   **Bulk Operations**: Allow batch interaction operations
-   **Real-time Updates**: WebSocket integration for live interaction updates
-   **Share Templates**: Predefined share messages for different platforms
-   **Interaction Rewards**: Gamification elements for active users

This completes the comprehensive stream interactions system with like, dislike, and share functionality!
