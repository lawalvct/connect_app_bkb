# Two Browser Call Testing Guide

## Quick Setup for Testing Audio & Video Calls

### Test Users

-   **User 1 (Oz Lawal)**: ID 3114, Email: lawalthb@gmail.com, Password: 12345678
-   **User 2 (Vick)**: ID 4098, Email: vick@gmail.com, Password: 12345678

### Prerequisites

✅ Laravel server running (php artisan serve)
✅ Two browsers (or two browser windows/incognito mode)
✅ Modern interface accessible at: `http://localhost:8000/test-calls-modern`

---

## Step-by-Step Testing Instructions

### BROWSER 1 (User 1 - Oz Lawal)

1. **Open first browser/window**

    - Navigate to: `http://localhost:8000/test-calls-modern`

2. **Login as User 1**

    - Click on "Oz Lawal" quick login card (ID: 3114)
    - OR manually enter:
        - Email: `lawalthb@gmail.com`
        - Password: `12345678`
    - Click "Login"

3. **Select Conversation**

    - Wait for conversations to load
    - Click on any conversation that includes both users
    - **IMPORTANT**: Note the Conversation ID from the "Call Configuration" section

4. **Wait** - Keep this browser open and wait for User 2 to login

---

### BROWSER 2 (User 2 - Vick)

1. **Open second browser/window**

    - Navigate to: `http://localhost:8000/test-calls-modern`
    - Use incognito/private mode to avoid session conflicts

2. **Login as User 2**

    - Manually enter:
        - Email: `vick@gmail.com`
        - Password: `12345678`
    - Click "Login"

3. **Select Same Conversation**
    - Click on the SAME conversation as User 1
    - Verify both browsers show the same Conversation ID

---

## Testing Audio Call

### From Browser 1 (User 1 - Initiator):

1. **Set Call Type**

    - In "Call Configuration" section
    - Select: `🎤 Audio Call`

2. **Initiate Call**
    - Click: **"Initiate Audio Call"** button (blue button)
    - Watch Activity Logs for confirmation
    - You should see:
        - ✅ "Call initiated (ID: xxx)"
        - ✅ "Joined Agora channel"
        - ✅ "Audio track created"
        - ✅ "Local tracks published"

### From Browser 2 (User 2 - Receiver):

3. **Receive Incoming Call**

    - Blue "Incoming Call!" banner should appear automatically
    - Shows:
        - From: Oz Lawal
        - Type: audio

4. **Answer Call**

    - Click: **"Accept"** button (green button)
    - Watch Activity Logs for confirmation
    - You should see:
        - ✅ "Call answered"
        - ✅ "Joined Agora channel"
        - ✅ "Audio track created"

5. **Test Audio**

    - Speak into microphone on Browser 1
    - Verify audio is heard in Browser 2
    - Speak into microphone on Browser 2
    - Verify audio is heard in Browser 1

6. **Test Controls**

    - Click "Mute/Unmute" button to test microphone control
    - Verify status updates in "System Status" cards

7. **End Call**
    - Click: **"End Call"** button (red button) from either browser
    - Both browsers should show call ended

---

## Testing Video Call

### From Browser 1 (User 1 - Initiator):

1. **Set Call Type**

    - In "Call Configuration" section
    - Select: `📹 Video Call`

2. **Initiate Call**
    - Click: **"Initiate Video Call"** button (blue button)
    - Watch Activity Logs for confirmation
    - You should see:
        - ✅ "Call initiated (ID: xxx)"
        - ✅ "Joined Agora channel"
        - ✅ "Audio track created"
        - ✅ "Video track created"
        - ✅ "Local tracks published"
    - **Your camera** should appear in "Local Video" section

### From Browser 2 (User 2 - Receiver):

3. **Receive Incoming Call**

    - Blue "Incoming Call!" banner should appear
    - Shows:
        - From: Oz Lawal
        - Type: video

4. **Answer Call**

    - Click: **"Accept"** button (green button)
    - Watch Activity Logs
    - You should see:
        - ✅ "Call answered"
        - ✅ "Joined Agora channel"
        - ✅ "Audio and Video tracks created"
        - ✅ "Remote video playing"
    - **Your camera** appears in "Local Video"
    - **User 1's camera** appears in "Remote Video"

5. **Verify on Browser 1**

    - User 2's video should now appear in "Remote Video" section

6. **Test Video & Audio**

    - ✅ Verify both users see each other's video
    - ✅ Verify both users can hear each other
    - ✅ Test "Turn Off/On Camera" button
    - ✅ Test "Mute/Unmute" button

7. **End Call**
    - Click: **"End Call"** button from either browser

---

## What to Monitor

### System Status Cards (4 indicators):

-   **Call**: Should show "Connected" during active call
-   **Agora**: Should show "Connected" when joined
-   **Camera**: Should show "Active" during video call
-   **Microphone**: Should show "Active" when unmuted

### Activity Logs Terminal:

Watch for these messages in real-time:

-   ✅ Green = Success (call initiated, tracks created)
-   🔵 Blue = Info (status updates)
-   ⚠️ Yellow = Warning (user actions)
-   ❌ Red = Error (problems to fix)

---

## Troubleshooting

### "No conversations found"

**Solution**: Create a conversation between both users first:

```php
// Run in Laravel Tinker or create via API
php artisan tinker
$user1 = User::find(3114);
$user2 = User::find(4098);
$conversation = Conversation::create([...]);
$conversation->participants()->attach([3114, 4098]);
```

### "Incoming call not appearing"

**Checklist**:

1. ✅ Both users logged into SAME conversation
2. ✅ Pusher credentials configured correctly
3. ✅ Browser console shows no Pusher errors
4. ✅ Activity logs show "Subscribed to conversation.{id}"

### "No video/audio"

**Checklist**:

1. ✅ Browser granted camera/microphone permissions
2. ✅ Activity logs show "Audio/Video track created"
3. ✅ Check browser DevTools for errors
4. ✅ Agora credentials configured correctly

### "Camera permission denied"

**Solution**:

1. Click lock icon in browser address bar
2. Allow camera and microphone access
3. Refresh page and try again

---

## Testing Checklist

### Audio Call Test:

-   [ ] User 1 can initiate audio call
-   [ ] User 2 receives incoming call notification
-   [ ] User 2 can answer call
-   [ ] Both users can hear each other
-   [ ] Mute/Unmute works on both sides
-   [ ] Either user can end call
-   [ ] Call ends properly on both browsers

### Video Call Test:

-   [ ] User 1 can initiate video call
-   [ ] User 2 receives incoming call notification
-   [ ] User 2 can answer call
-   [ ] Both users see each other's video
-   [ ] Both users can hear each other
-   [ ] Camera toggle works on both sides
-   [ ] Mute/Unmute works on both sides
-   [ ] Either user can end call
-   [ ] Video stops properly on both browsers

---

## Quick Commands Reference

### Start Laravel Server:

```powershell
cd C:\laragon\www\connect_app_bkb
php artisan serve
```

### Access URL:

```
http://localhost:8000/test-calls-modern
```

### Clear Caches (if needed):

```powershell
php artisan cache:clear
php artisan route:clear
php artisan config:clear
```

---

## Expected Flow

```
Browser 1 (User 1)          Browser 2 (User 2)
─────────────────────────────────────────────────
1. Login                    1. Login
2. Select conversation      2. Select SAME conversation
3. Choose Audio/Video       3. Wait...
4. Click "Initiate Call"
                            4. See "Incoming Call!" banner
                            5. Click "Accept"
6. See remote video/audio   6. See remote video/audio
7. Test controls            7. Test controls
8. Click "End Call"         8. Call ends automatically
```

---

## Success Indicators

✅ **Call Successfully Initiated When**:

-   Activity logs show: "Call initiated (ID: xxx)"
-   Call banner appears with call details
-   Agora status shows "Connected"
-   Local video/audio appears (for video calls)

✅ **Call Successfully Connected When**:

-   Both browsers show "Call Active" banner
-   Remote video/audio appears
-   Both System Status cards show green "Active"
-   Activity logs show "Remote video playing"

✅ **Everything Working When**:

-   You can see and hear each other
-   Controls (mute/camera) work instantly
-   Status indicators update in real-time
-   No red error messages in Activity Logs

---

## Demo Script (for Stakeholders)

> "Let me show you our audio and video calling feature. I'll use two browsers to simulate two users..."

1. **Login both users** (10 seconds)
2. **Select same conversation** (5 seconds)
3. **Initiate video call from Browser 1** (5 seconds)
4. **Accept call from Browser 2** (5 seconds)
5. **Show video streams working** (10 seconds)
6. **Toggle camera and microphone** (10 seconds)
7. **Show activity logs and status indicators** (10 seconds)
8. **End call** (5 seconds)

**Total demo time: ~60 seconds**

---

## Notes

-   Use **Chrome** or **Edge** for best Agora compatibility
-   Test in **incognito/private mode** for Browser 2 to avoid session conflicts
-   Keep **Activity Logs** visible to monitor connection status
-   If testing repeatedly, refresh browsers between tests
-   Audio feedback/echo is normal when testing on same device (use headphones)

---

**Ready to test? Follow the steps above and verify all checkboxes! 🚀**
