# Recorded Videos API Documentation

## Overview

The API now supports recorded videos alongside live streams. All stream endpoints have been updated to include recorded video information.

## API Endpoints

### 1. Get Latest Streams (Live & Recorded)

**Endpoint:** `GET /api/v1/streams/latest`

**Parameters:**

- `content_type` (optional): Filter by type
    - `live` - Only live streams
    - `recorded` - Only recorded videos
    - Empty/null - Both live and recorded
- `limit` (optional): Number of items per page (default: 15)
- `page` (optional): Page number

**Example Requests:**

```bash
# Get all (live streams + available recorded videos)
GET /api/v1/streams/latest

# Get only live streams
GET /api/v1/streams/latest?content_type=live

# Get only recorded videos
GET /api/v1/streams/latest?content_type=recorded

# Paginated
GET /api/v1/streams/latest?content_type=recorded&limit=10&page=2
```

**Response:**

```json
{
    "success": true,
    "data": {
        "streams": [
            {
                "id": 1,
                "title": "Stream Title",
                "description": "Description",
                "banner_image_url": "https://...",
                "status": "ended",
                "is_live": false,
                "is_paid": true,
                "price": "100.00",
                "currency": "NGN",
                "max_viewers": null,
                "current_viewers": 0,
                "likes_count": 15,
                "dislikes_count": 2,
                "shares_count": 8,
                "duration": 3600,
                "scheduled_at": null,
                "started_at": null,
                "ended_at": "2026-01-18T10:30:00.000000Z",
                "created_at": "2026-01-18T09:00:00.000000Z",
                "updated_at": "2026-01-18T10:30:00.000000Z",

                // Recorded video specific fields
                "is_recorded": true,
                "video_url": "https://example.com/streams/videos/1234567_abc.mp4",
                "video_file": "streams/videos/1234567_abc.mp4",
                "video_duration": 3600,
                "formatted_duration": "01:00:00",
                "is_downloadable": true,
                "available_from": "2026-01-18T00:00:00.000000Z",
                "available_until": "2026-01-20T23:59:59.000000Z",
                "availability_status": "available",
                "is_available": true,

                "streamer": {
                    "id": 3,
                    "username": "admin",
                    "name": "Admin User",
                    "profile_picture": "https://..."
                }
            }
        ],
        "total": 50,
        "current_page": 1,
        "last_page": 5,
        "per_page": 15
    }
}
```

### 2. Get Recorded Videos Only

**Endpoint:** `GET /api/v1/streams/recorded`

**Parameters:**

- `status` (optional): Filter by availability status
    - `available` - Currently available to watch
    - `scheduled` - Not yet available (future date)
    - `expired` - No longer available (past date)
    - Empty/null - All recorded videos
- `limit` (optional): Number of items per page (default: 15)
- `page` (optional): Page number

**Example Requests:**

```bash
# Get all recorded videos
GET /api/v1/streams/recorded

# Get only currently available videos
GET /api/v1/streams/recorded?status=available

# Get scheduled videos
GET /api/v1/streams/recorded?status=scheduled
```

**Response:** Same format as `/latest` endpoint

### 3. Get Stream Details

**Endpoint:** `GET /api/v1/streams/{id}`

**Response:** Includes all recorded video fields if applicable

### 4. Get Upcoming Streams

**Endpoint:** `GET /api/v1/streams/upcoming`

**Note:** Only returns upcoming **live streams** (not recorded videos)

## Response Field Descriptions

### Recorded Video Fields

| Field                 | Type           | Description                                            |
| --------------------- | -------------- | ------------------------------------------------------ |
| `is_recorded`         | boolean        | `true` if recorded video, `false` if live stream       |
| `video_url`           | string\|null   | Full URL to access the video file                      |
| `video_file`          | string\|null   | Relative path to video file                            |
| `video_duration`      | int\|null      | Video duration in seconds                              |
| `formatted_duration`  | string\|null   | Human-readable duration (HH:MM:SS)                     |
| `is_downloadable`     | boolean        | Whether users can download this video                  |
| `available_from`      | datetime\|null | When video becomes available                           |
| `available_until`     | datetime\|null | When video expires                                     |
| `availability_status` | string\|null   | Current status: `scheduled`, `available`, or `expired` |
| `is_available`        | boolean        | Whether video is currently available to watch          |

### Availability Status

- **`scheduled`**: Video uploaded but not yet available (current time < `available_from`)
- **`available`**: Currently accessible to users
- **`expired`**: No longer available (current time > `available_until`)

## Usage Examples

### Frontend Implementation

```javascript
// Fetch all streams (live + recorded)
async function fetchAllStreams() {
    const response = await fetch("/api/v1/streams/latest");
    const data = await response.json();

    data.data.streams.forEach((stream) => {
        if (stream.is_recorded) {
            // Handle recorded video
            console.log("Recorded video:", stream.title);
            console.log("Duration:", stream.formatted_duration);
            console.log("Available:", stream.is_available);
            console.log("Downloadable:", stream.is_downloadable);
        } else {
            // Handle live stream
            console.log("Live stream:", stream.title);
            console.log("Status:", stream.status);
        }
    });
}

// Fetch only recorded videos that are available
async function fetchAvailableVideos() {
    const response = await fetch("/api/v1/streams/recorded?status=available");
    const data = await response.json();
    return data.data.streams;
}

// Check if a video is available before playing
function canPlayVideo(stream) {
    return stream.is_recorded && stream.is_available;
}
```

### Display Logic

```javascript
function displayStream(stream) {
    if (stream.is_recorded) {
        // Recorded video UI
        return {
            type: "recorded",
            title: stream.title,
            duration: stream.formatted_duration,
            videoUrl: stream.video_url,
            downloadable: stream.is_downloadable,
            status: stream.availability_status,
            badge:
                stream.availability_status === "available"
                    ? "Watch Now"
                    : stream.availability_status === "scheduled"
                      ? `Available ${formatDate(stream.available_from)}`
                      : "Expired",
        };
    } else {
        // Live stream UI
        return {
            type: "live",
            title: stream.title,
            status: stream.is_live ? "LIVE NOW" : stream.status,
            viewers: stream.current_viewers,
        };
    }
}
```

## Backward Compatibility

✅ **100% Compatible**

- All existing API endpoints continue to work
- New fields are optional and null-safe
- Live streams return `is_recorded: false`
- No breaking changes to existing responses

## Testing

Test the endpoints:

```bash
# Test latest endpoint
curl -X GET "http://your-domain/api/v1/streams/latest"

# Test with content type filter
curl -X GET "http://your-domain/api/v1/streams/latest?content_type=recorded"

# Test recorded videos endpoint
curl -X GET "http://your-domain/api/v1/streams/recorded?status=available"

# Test specific stream details
curl -X GET "http://your-domain/api/v1/streams/1"
```

## Common Use Cases

### 1. Video Library Page

```javascript
// Fetch all available recorded videos
fetch("/api/v1/streams/recorded?status=available&limit=20");
```

### 2. Mixed Feed (Live + VOD)

```javascript
// Show both live streams and recorded videos
fetch("/api/v1/streams/latest?limit=30");
```

### 3. Upcoming Content

```javascript
// Show scheduled content
fetch("/api/v1/streams/recorded?status=scheduled");
```

### 4. Live Streams Only

```javascript
// Traditional live streaming view
fetch("/api/v1/streams/latest?content_type=live");
```

## Notes

- Recorded videos with `available_from` in the future will not appear in regular queries
- Expired videos (past `available_until`) are excluded from available lists
- Video URLs are fully qualified and ready to use
- Download permission is enforced by the `is_downloadable` flag
- All dates are in UTC timezone

## Support

For issues or questions, check the main documentation in `RECORDED_VIDEO_FEATURE.md`
