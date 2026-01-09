# Story Reactions Feature - API Documentation

## Overview

Users can now react to stories with emojis. Each user can only have one reaction per story, but they can change their reaction at any time.

## Supported Reactions

| Reaction Type | Emoji | Description      |
| ------------- | ----- | ---------------- |
| `like`        | 👍    | Like/Thumbs up   |
| `love`        | ❤️    | Love/Heart       |
| `haha`        | 😂    | Laughing         |
| `wow`         | 😮    | Surprised/Wow    |
| `sad`         | 😢    | Sad              |
| `angry`       | 😠    | Angry            |
| `fire`        | 🔥    | Fire/Hot         |
| `clap`        | 👏    | Clapping         |
| `heart_eyes`  | 😍    | Heart eyes/Adore |
| `thinking`    | 🤔    | Thinking         |

## API Endpoints

### 1. Get Supported Reactions

Get the list of all supported reaction types with their emojis.

**Endpoint:** `GET /api/v1/stories/reactions/supported`

**Response:**

```json
{
    "status": 1,
    "message": "Supported reactions retrieved successfully",
    "data": {
        "like": "👍",
        "love": "❤️",
        "haha": "😂",
        "wow": "😮",
        "sad": "😢",
        "angry": "😠",
        "fire": "🔥",
        "clap": "👏",
        "heart_eyes": "😍",
        "thinking": "🤔"
    }
}
```

### 2. React to a Story

Add or update your reaction to a story. If you already reacted, this will update your reaction.

**Endpoint:** `POST /api/v1/stories/{story_id}/react`

**Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "reaction_type": "love"
}
```

**Validation:**

-   `reaction_type` is required
-   Must be one of the supported reaction types

**Response (Success):**

```json
{
    "status": 1,
    "message": "Reaction added successfully",
    "data": {
        "reaction": {
            "type": "love",
            "emoji": "❤️",
            "created_at": "2026-01-09T10:30:00.000000Z"
        },
        "reactions_summary": {
            "like": 5,
            "love": 12,
            "fire": 3
        },
        "total_reactions": 20
    }
}
```

**Auto-behaviors:**

-   Story is automatically marked as viewed when you react
-   If you already reacted, your old reaction is replaced with the new one

**Error Responses:**

Story expired (404):

```json
{
    "status": 0,
    "message": "Cannot react to expired story"
}
```

No permission (403):

```json
{
    "status": 0,
    "message": "You do not have permission to react to this story"
}
```

Invalid reaction type (422):

```json
{
    "status": 0,
    "message": "Validation failed",
    "errors": {
        "reaction_type": ["The selected reaction type is invalid."]
    }
}
```

### 3. Remove Reaction

Remove your reaction from a story.

**Endpoint:** `DELETE /api/v1/stories/{story_id}/react`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (Success):**

```json
{
    "status": 1,
    "message": "Reaction removed successfully",
    "data": {
        "reactions_summary": {
            "like": 5,
            "love": 11,
            "fire": 3
        },
        "total_reactions": 19
    }
}
```

**Error Response:**

No reaction found (404):

```json
{
    "status": 0,
    "message": "No reaction found to remove"
}
```

### 4. Get Story Reactions (Story Owner Only)

View all reactions on your story, with detailed information about who reacted.

**Endpoint:** `GET /api/v1/stories/{story_id}/reactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Query Parameters:**

-   `reaction_type` (optional) - Filter by specific reaction type (e.g., `like`, `love`)

**Examples:**

-   Get all reactions: `/api/v1/stories/123/reactions`
-   Get only love reactions: `/api/v1/stories/123/reactions?reaction_type=love`

**Response:**

```json
{
    "status": 1,
    "message": "Story reactions retrieved successfully",
    "data": {
        "total_reactions": 20,
        "reactions_summary": {
            "like": 5,
            "love": 12,
            "fire": 3
        },
        "reactions": [
            {
                "id": 1,
                "reaction_type": "love",
                "emoji": "❤️",
                "user": {
                    "id": 123,
                    "name": "John Doe",
                    "username": "johndoe",
                    "profile_image": "https://example.com/profile.jpg"
                },
                "created_at": "2026-01-09T10:30:00.000000Z"
            },
            {
                "id": 2,
                "reaction_type": "like",
                "emoji": "👍",
                "user": {
                    "id": 456,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_image": "https://example.com/profile2.jpg"
                },
                "created_at": "2026-01-09T10:25:00.000000Z"
            }
        ]
    }
}
```

**Error Response:**

Not story owner (403):

```json
{
    "status": 0,
    "message": "You can only view reactions of your own stories"
}
```

## Database Schema

### story_reactions Table

```sql
CREATE TABLE story_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    story_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_story (story_id, user_id),
    INDEX idx_story_reaction (story_id, reaction_type),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Key Points:**

-   Unique constraint ensures one reaction per user per story
-   When user reacts again, the old reaction is updated (not duplicated)
-   Cascade delete removes reactions when story or user is deleted

## Mobile Implementation Guide

### React Native Example

```javascript
import React, { useState } from "react";

const StoryReactionButton = ({ storyId, currentReaction }) => {
    const [selectedReaction, setSelectedReaction] = useState(currentReaction);
    const [showPicker, setShowPicker] = useState(false);

    const reactions = {
        like: "👍",
        love: "❤️",
        haha: "😂",
        wow: "😮",
        sad: "😢",
        angry: "😠",
        fire: "🔥",
        clap: "👏",
        heart_eyes: "😍",
        thinking: "🤔",
    };

    const handleReact = async (reactionType) => {
        try {
            const response = await fetch(
                `${API_URL}/api/v1/stories/${storyId}/react`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ reaction_type: reactionType }),
                }
            );

            const data = await response.json();
            if (data.status === 1) {
                setSelectedReaction(reactionType);
                setShowPicker(false);
                // Update UI with reactions_summary
                console.log("Total reactions:", data.data.total_reactions);
            }
        } catch (error) {
            console.error("Failed to react:", error);
        }
    };

    const handleRemoveReaction = async () => {
        try {
            const response = await fetch(
                `${API_URL}/api/v1/stories/${storyId}/react`,
                {
                    method: "DELETE",
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                }
            );

            const data = await response.json();
            if (data.status === 1) {
                setSelectedReaction(null);
            }
        } catch (error) {
            console.error("Failed to remove reaction:", error);
        }
    };

    return (
        <View>
            {/* Current reaction or react button */}
            {selectedReaction ? (
                <TouchableOpacity onPress={handleRemoveReaction}>
                    <Text>{reactions[selectedReaction]}</Text>
                </TouchableOpacity>
            ) : (
                <TouchableOpacity onPress={() => setShowPicker(true)}>
                    <Text>❤️ React</Text>
                </TouchableOpacity>
            )}

            {/* Reaction picker */}
            {showPicker && (
                <View style={styles.reactionPicker}>
                    {Object.entries(reactions).map(([type, emoji]) => (
                        <TouchableOpacity
                            key={type}
                            onPress={() => handleReact(type)}
                        >
                            <Text style={styles.emoji}>{emoji}</Text>
                        </TouchableOpacity>
                    ))}
                </View>
            )}
        </View>
    );
};
```

### Flutter Example

```dart
class StoryReactionService {
  final String baseUrl;
  final String token;

  StoryReactionService(this.baseUrl, this.token);

  Future<Map<String, dynamic>> reactToStory(int storyId, String reactionType) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/v1/stories/$storyId/react'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'reaction_type': reactionType}),
    );

    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> removeReaction(int storyId) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/api/v1/stories/$storyId/react'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    return jsonDecode(response.body);
  }

  Future<List<dynamic>> getStoryReactions(int storyId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/stories/$storyId/reactions'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    final data = jsonDecode(response.body);
    return data['data']['reactions'];
  }
}
```

## UI/UX Recommendations

### Reaction Display

1. **Story Feed**: Show small reaction count badge on story ring
2. **Story View**: Show reaction button at bottom of story
3. **Long Press**: Open reaction picker with all emoji options
4. **Owner View**: Show detailed reactions with user list

### Reaction Picker

-   Display all 10 emoji reactions in a horizontal scrollable list
-   Highlight currently selected reaction (if any)
-   Animate emoji on selection
-   Show reaction count for each emoji type (optional)

### Real-time Updates

Consider using Pusher/WebSockets to broadcast reactions in real-time:

```javascript
// Subscribe to story reactions channel
pusher.subscribe(`story.${storyId}.reactions`);
pusher.bind("reaction.added", (data) => {
    // Update reaction count in UI
    updateReactionCount(data.reactions_summary);
});
```

## Testing Checklist

-   [ ] React to a story successfully
-   [ ] Change reaction (update existing)
-   [ ] Remove reaction
-   [ ] React to expired story (should fail)
-   [ ] React to story without permission (should fail)
-   [ ] View reactions as story owner
-   [ ] View reactions as non-owner (should fail)
-   [ ] Filter reactions by type
-   [ ] Get supported reactions list
-   [ ] Story auto-marked as viewed when reacting
-   [ ] Reaction count updates correctly
-   [ ] Database unique constraint works (no duplicate reactions)

## Migration Command

Run this command to create the reactions table:

```bash
php artisan migrate
```

The migration file: `2026_01_09_000000_create_story_reactions_table.php`

---

**Version:** 1.0
**Last Updated:** January 9, 2026
**Feature Status:** ✅ Ready for Testing
