# Story API Backend Requirements

## Overview

This document outlines the backend API requirements for the Story feature, which supports both text-only stories and media (image/video) stories.

---

## 1. Story Types

The API must support three story types:

- `text` - Text-only stories with customizable background and font settings
- `image` - Image stories with optional caption
- `video` - Video stories with optional caption

---

## 2. Conditional Validation Rules

### For Text Stories (`type === "text"`)

**Required Fields:**
| Field | Type | Validation |
|-------|------|------------|
| `type` | string | Must be `"text"` |
| `content` | string | Required, max 280 characters |
| `background_color` | string | Required, hex color format (#RRGGBB) |
| `font_settings` | object | Required (see structure below) |
| `privacy` | string | Required, enum: `all_connections`, `close_friends`, `only_me` |
| `allow_replies` | boolean/string | Required, accept: `0`, `1`, `true`, `false` |

**Optional Fields:**

- `file` - Should be `null` or omitted
- `caption` - Not applicable for text stories

**Font Settings Structure:**

```json
{
  "font_settings": {
    "size": 20, // integer, min: 12, max: 48
    "family": "System", // enum: ["System", "sans-serif", "serif", "monospace"]
    "weight": "bold" // enum: ["normal", "bold", "600", "700"]
  }
}
```

### For Image/Video Stories (`type === "image"` or `type === "video"`)

**Required Fields:**
| Field | Type | Validation |
|-------|------|------------|
| `type` | string | Must be `"image"` or `"video"` |
| `file` | file | Required, validate MIME types (image/_, video/_) |
| `privacy` | string | Required, enum: `all_connections`, `close_friends`, `only_me` |
| `allow_replies` | boolean/string | Required, accept: `0`, `1`, `true`, `false` |

**Optional Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `content` | string | Caption text, max 200 characters |
| `background_color` | string | Should be `null` for media stories |
| `font_settings` | object | Should be `null` for media stories |

---

## 3. Validation Rules Summary

```php
// Pseudocode validation logic

if ($request->type === 'text') {
    return [
        'type' => 'required|in:text',
        'content' => 'required|string|max:280',
        'background_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'font_settings' => 'required|array',
        'font_settings.size' => 'required|integer|min:12|max:48',
        'font_settings.family' => 'required|in:System,sans-serif,serif,monospace',
        'font_settings.weight' => 'required|in:normal,bold,600,700',
        'privacy' => 'required|in:all_connections,close_friends,only_me',
        'allow_replies' => 'required|boolean',
        'file' => 'nullable', // File is NOT required for text stories
    ];
}

if (in_array($request->type, ['image', 'video'])) {
    return [
        'type' => 'required|in:image,video',
        'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200', // 50MB
        'content' => 'nullable|string|max:200', // Optional caption
        'privacy' => 'required|in:all_connections,close_friends,only_me',
        'allow_replies' => 'required|boolean',
        'background_color' => 'nullable',
        'font_settings' => 'nullable',
    ];
}
```

---

## 4. Request Examples

### Text Story Request

```json
POST /api/stories
Content-Type: multipart/form-data

{
  "type": "text",
  "content": "God is good All The time",
  "privacy": "all_connections",
  "allow_replies": "1",
  "background_color": "#FF5733",
  "font_settings[size]": "20",
  "font_settings[family]": "System",
  "font_settings[weight]": "bold"
}
```

### Image Story Request

```json
POST /api/stories
Content-Type: multipart/form-data

{
  "type": "image",
  "file": [binary file data],
  "content": "Beautiful sunset today!",
  "privacy": "all_connections",
  "allow_replies": "1"
}
```

### Video Story Request

```json
POST /api/stories
Content-Type: multipart/form-data

{
  "type": "video",
  "file": [binary file data],
  "content": "Check out this amazing moment",
  "privacy": "close_friends",
  "allow_replies": "0"
}
```

---

## 5. Response Format

### Success Response (Text Story)

```json
{
  "status": 1,
  "message": "Story created successfully",
  "data": {
    "id": 446,
    "user_id": 123,
    "type": "text",
    "content": "God is good All The time",
    "file_url": null,
    "caption": null,
    "background_color": "#FF5733",
    "font_settings": {
      "size": 20,
      "family": "System",
      "weight": "bold"
    },
    "privacy": "all_connections",
    "allow_replies": true,
    "views_count": 0,
    "created_at": "2026-01-03T10:30:00Z",
    "expires_at": "2026-01-04T10:30:00Z"
  }
}
```

### Success Response (Image/Video Story)

```json
{
  "status": 1,
  "message": "Story created successfully",
  "data": {
    "id": 447,
    "user_id": 123,
    "type": "image",
    "content": "story_1735900200_user123.jpg",
    "file_url": "https://cdn.example.com/stories/1735900200_user123.jpg",
    "caption": "Beautiful sunset today!",
    "background_color": null,
    "font_settings": null,
    "privacy": "all_connections",
    "allow_replies": true,
    "views_count": 0,
    "created_at": "2026-01-03T10:30:00Z",
    "expires_at": "2026-01-04T10:30:00Z"
  }
}
```

### Error Response

```json
{
  "status": 0,
  "message": "Validation failed",
  "errors": {
    "content": ["The content field is required when type is text."],
    "font_settings.family": ["The selected font settings.family is invalid."]
  }
}
```

---

## 6. Database Schema Recommendations

### Stories Table

```sql
CREATE TABLE stories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('text', 'image', 'video') NOT NULL,
    content TEXT,                           -- Text content or file path
    file_url VARCHAR(500) DEFAULT NULL,      -- Full URL for image/video
    caption VARCHAR(200) DEFAULT NULL,       -- Caption for image/video
    background_color VARCHAR(7) DEFAULT NULL,-- Hex color for text stories
    font_settings JSON DEFAULT NULL,         -- Font settings for text stories
    privacy ENUM('all_connections', 'close_friends', 'only_me') DEFAULT 'all_connections',
    allow_replies BOOLEAN DEFAULT TRUE,
    views_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,               -- Auto-expire after 24 hours
    deleted_at TIMESTAMP NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 7. Additional Requirements

### File Upload

- **Max file size**: 50MB
- **Allowed image formats**: JPG, JPEG, PNG, GIF
- **Allowed video formats**: MP4, MOV, AVI
- **Video duration limit**: 30 seconds (optional, can be enforced)
- **Storage**: Use CDN for optimal performance

### Story Expiration

- Stories should automatically expire after 24 hours
- Implement a cron job or scheduled task to delete expired stories
- Set `expires_at = created_at + 24 hours` on creation

### Privacy Implementation

- `all_connections`: Visible to all user's connections
- `close_friends`: Visible only to users marked as close friends
- `only_me`: Visible only to the story creator

### Font Settings Notes

- Frontend sends font settings as nested object in FormData format:
  ```
  font_settings[size]=20
  font_settings[family]=System
  font_settings[weight]=bold
  ```
- Backend should parse this into a proper object/JSON structure

---

## 8. Current Issues to Fix

### Issue 1: File Field Required for Text Stories

**Current Error:**

```json
{
  "message": "The file field is required.",
  "errors": {
    "file": ["The file field is required."]
  }
}
```

**Fix Required:**
Make the `file` field conditional - only required when `type` is `"image"` or `"video"`.

### Issue 2: Invalid Font Family

**Current Error:**

```json
{
  "errors": {
    "font_settings.family": ["The selected font settings.family is invalid."]
  }
}
```

**Fix Required:**
Update the validation to accept these font families:

- `System`
- `sans-serif`
- `serif`
- `monospace`

Or provide the list of valid font families so the frontend can be updated accordingly.

---

## 9. Testing Checklist

- [ ] Text story with all required fields succeeds
- [ ] Text story without `file` field succeeds
- [ ] Image story with file upload succeeds
- [ ] Image story without file fails with proper error
- [ ] Video story with file upload succeeds
- [ ] Invalid `privacy` value returns validation error
- [ ] `allow_replies` accepts: `0`, `1`, `true`, `false`
- [ ] Font settings with all valid families are accepted
- [ ] Stories expire after 24 hours
- [ ] Response format is consistent across all story types
- [ ] File size limit (50MB) is enforced
- [ ] Video duration limit (30s) is enforced (if implemented)

---

## 10. API Endpoint

```
POST /api/stories
```

**Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Rate Limiting:** Consider implementing rate limiting (e.g., max 10 stories per user per day)

---

## Questions for Backend Team

1. What are the officially supported font families for text stories?
2. Should we enforce a maximum video duration (e.g., 30 seconds)?
3. Is there a daily limit on the number of stories a user can create?
4. Should we implement story drafts functionality?
5. Should deleted stories be soft-deleted or permanently removed?

---

**Document Version:** 1.0
**Last Updated:** January 3, 2026
**Contact:** Frontend Team
