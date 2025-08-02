# Quick Test Guide for Mobile Stream Viewer

## 🧪 Testing Your New Mobile Stream Viewer

### 1. Access the New Mobile Page

```
http://your-domain.com/stream/{streamId}/watch
```

Replace `{streamId}` with an actual stream ID from your database.

### 2. Test Chat Functionality

#### Get Chat Messages (Public)

```bash
curl -X GET "http://your-domain.com/api/v1/streams/1/chats?limit=10" \
  -H "Accept: application/json"
```

#### Send Chat Message (Authenticated)

```bash
curl -X POST "http://your-domain.com/api/v1/streams/1/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"message": "Hello from mobile viewer!"}'
```

#### Load Older Messages

```bash
curl -X GET "http://your-domain.com/api/v1/streams/1/chats?before_id=50&limit=10" \
  -H "Accept: application/json"
```

#### Load Newer Messages

```bash
curl -X GET "http://your-domain.com/api/v1/streams/1/chats?after_id=50&limit=10" \
  -H "Accept: application/json"
```

### 3. Mobile Features to Test

#### Visual Elements

-   ✅ **Brand colors** (`#A20030` red theme throughout)
-   ✅ **Full-screen video player** with overlay controls
-   ✅ **Floating chat panel** at bottom-right
-   ✅ **Transparent chat background** with blur effect
-   ✅ **Live indicator** with pulsing animation
-   ✅ **Viewer count badge** with smart formatting

#### Interactive Features

-   ✅ **Chat expand/collapse** by tapping chat icon
-   ✅ **Smooth animations** when toggling chat
-   ✅ **Unread message badges** on chat button
-   ✅ **Auto-scroll** to newest messages
-   ✅ **Touch-friendly controls** with proper sizing
-   ✅ **Fullscreen toggle** functionality

#### Mobile Responsiveness

-   ✅ **Portrait orientation** optimization
-   ✅ **Landscape orientation** support
-   ✅ **Various screen sizes** (phones, tablets)
-   ✅ **WebView compatibility** for mobile apps
-   ✅ **No horizontal scrolling**

### 4. Expected Behavior

#### On Page Load

1. **Video area** shows connecting state if stream is live
2. **Chat is collapsed** by default (only toggle button visible)
3. **Viewer count** displays in top-right overlay
4. **Stream info** shows in top-left overlay

#### During Live Stream

1. **Video plays automatically** when broadcaster is streaming
2. **Chat updates** every 3 seconds with new messages
3. **Viewer count updates** in real-time
4. **Expand chat** to see and send messages

#### Chat Interaction

1. **Tap chat icon** to expand/collapse chat panel
2. **Type messages** in bottom input field
3. **See avatars** and usernames for all messages
4. **Admin badges** show for stream creator messages
5. **Unread count** appears when chat is collapsed

### 5. Color Theme Verification

Check these elements have your brand colors:

#### Primary Red (`#A20030`)

-   Live indicator badge
-   Send message button
-   Unread message badges
-   Admin/moderator badges
-   Primary action buttons

#### Primary Light (`#A200302B`)

-   Chat panel borders
-   Subtle backgrounds
-   Hover states

#### Background (`#FAFAFA`)

-   Main page background
-   Chat message backgrounds
-   Panel backgrounds

### 6. API Response Format

Expected chat API response:

```json
{
    "success": true,
    "message": "Messages retrieved successfully",
    "data": [
        {
            "id": 123,
            "stream_id": 1,
            "user_id": 45,
            "username": "JohnDoe",
            "message": "Great stream!",
            "user_profile_url": "/uploads/avatars/user45.jpg",
            "is_admin": false,
            "created_at": "2025-08-02T15:30:00.000Z",
            "user": {
                "id": 45,
                "name": "John Doe",
                "profile_picture": "/uploads/avatars/user45.jpg"
            }
        }
    ],
    "meta": {
        "count": 10,
        "has_more": true,
        "oldest_id": 114,
        "newest_id": 123
    }
}
```

### 7. Troubleshooting

#### If Video Not Loading

-   Check Agora SDK configuration
-   Verify stream is actually live
-   Check browser console for errors

#### If Chat Not Working

-   Verify authentication for sending messages
-   Check API routes are properly configured
-   Test with browser dev tools network tab

#### If Colors Look Wrong

-   Check CSS variables are loading properly
-   Verify your brand colors in the blade template
-   Test on different devices/browsers

The mobile viewer is now ready for testing! 🚀
