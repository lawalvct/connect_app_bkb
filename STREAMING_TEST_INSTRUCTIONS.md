# Live Streaming Test Instructions

## Getting Started

1. **Access the Test Page**
   ```
   http://your-domain.com/test-streaming
   ```

2. **Test Users Created**
   - **Admin User**: `admin@test.com` / `password123`
   - **Regular User**: `user@test.com` / `password123`

## Testing Flow

### Step 1: Login as Admin
1. Click "Login" button
2. Use admin credentials: `admin@test.com` / `password123`
3. You should see "ADMIN" badge next to your name
4. Stream Management panel will appear on the left

### Step 2: Create a Stream
1. In the "Stream Management" panel (left side):
   - Enter stream title: "My Test Stream"
   - Enter description: "Testing live streaming functionality"
   - Optionally check "Paid Stream" and set price
   - Optionally set scheduled time
2. Click "Create Stream"
3. Stream should appear in "My Streams" section

### Step 3: Start the Stream
1. In "My Streams" section, find your created stream
2. Click "Start" button
3. Stream status should change to "LIVE"
4. Stream should appear in the "Live Streams" section

### Step 4: Test Viewing (Same Browser)
1. In the "Live Streams" section, click "Join Stream"
2. Video container should initialize (may show loading spinner)
3. Stream details should appear in the right panel
4. You should see viewer count increase

### Step 5: Test Chat
1. In the chat section (bottom right), type a message
2. Click "Send"
3. Message should appear in the chat area
4. Messages show username and timestamp

### Step 6: Test with Second User (New Browser/Incognito)
1. Open new browser or incognito window
2. Go to `/test-streaming`
3. Login with regular user: `user@test.com` / `password123`
4. Join the live stream
5. Send chat messages from both users
6. Verify chat polling works (messages appear in real-time)

### Step 7: Test Stream Management
1. As admin, try ending the stream
2. Verify viewers are automatically disconnected
3. Try deleting ended streams
4. Test creating paid streams

## Features to Test

### ✅ Stream Management (Admin)
- [x] Create stream
- [x] Start stream
- [x] End stream
- [x] Delete stream
- [x] Update stream (for upcoming streams)
- [x] View my streams

### ✅ Stream Discovery
- [x] Latest live streams
- [x] Upcoming streams
- [x] Stream details
- [x] Stream status

### ✅ Stream Participation
- [x] Join stream
- [x] Leave stream
- [x] View stream viewers
- [x] Real-time viewer count

### ✅ Live Chat
- [x] Send messages
- [x] Receive messages (polling)
- [x] Message pagination
- [x] Admin badge for admin users

### ✅ Payment Integration (Mock)
- [x] Paid stream creation
- [x] Payment confirmation dialog
- [x] Access control for paid streams

## Expected Behavior

### Video Streaming
- **Note**: Video streaming requires actual Agora configuration
- Currently shows loading spinner (normal without proper Agora setup)
- In production, this would show live video from broadcaster

### Real-time Updates
- Chat messages update every 2 seconds
- Stream status updates every 5 seconds
- Viewer count updates in real-time

### Error Handling
- Authentication errors
- Permission errors (non-admin trying to create streams)
- Stream not found errors
- Network errors with user-friendly messages

## API Endpoints Being Tested

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/streams` | POST | Create stream |
| `/api/v1/streams/{id}/start` | POST | Start stream |
| `/api/v1/streams/{id}/end` | POST | End stream |
| `/api/v1/streams/{id}/join` | POST | Join stream |
| `/api/v1/streams/{id}/leave` | POST | Leave stream |
| `/api/v1/streams/{id}/chat` | GET/POST | Chat messages |
| `/api/v1/streams/latest` | GET | Live streams |
| `/api/v1/streams/upcoming` | GET | Upcoming streams |
| `/api/v1/streams/my-streams` | GET | Admin's streams |

## Troubleshooting

### "No live streams available"
- Make sure you've started a stream as admin first
- Check that the stream status is "live"

### Video not loading
- This is expected without proper Agora configuration
- In production, ensure Agora credentials are properly set

### Chat not updating
- Messages poll every 2 seconds
- Check browser console for errors
- Ensure you're logged in and joined the stream

### Permission errors
- Ensure you're using the admin account for stream management
- Regular users can only join and chat

## Production Notes

For production deployment:

1. **Configure Agora properly** in `.env`:
   ```
   AGORA_APP_ID=your_agora_app_id
   AGORA_APP_CERTIFICATE=your_agora_certificate
   ```

2. **Set up payment gateways**:
   ```
   STRIPE_KEY=your_stripe_key
   STRIPE_SECRET=your_stripe_secret
   ```

3. **Configure proper frontend URLs** for payment redirects

4. **Set up webhook endpoints** for payment confirmations

5. **Implement real-time notifications** using WebSockets or Server-Sent Events for better chat experience

The test interface provides a comprehensive way to verify all streaming functionality is working correctly!
