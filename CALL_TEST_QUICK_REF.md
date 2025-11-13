# 🎯 ConnectApp Call Testing - Quick Reference

## 🚀 Access URLs

```
Professional Interface: http://your-domain/test-calls-modern
Technical Interface:   http://your-domain/test-calls
```

## 👤 Test Accounts

| User     | Email              | Password | User ID |
| -------- | ------------------ | -------- | ------- |
| Oz Lawal | lawalthb@gmail.com | 12345678 | 3114    |
| Gerson   | gerson@example.com | 12345678 | 3152    |

## 📞 Quick Test Scenarios

### 🎤 Audio Call Test (2 minutes)

```
1. User 1: Login → Select conversation
2. User 1: Set "Audio Call" → Click "Initiate"
3. User 2: Login → See incoming call → Accept
4. Both: Test microphone toggle
5. User 1: Click "End Call"
✅ Success: Clear audio, working toggles
```

### 📹 Video Call Test (3 minutes)

```
1. User 1: Login → Select conversation
2. User 1: Set "Video Call" → Click "Initiate"
3. User 1: Verify local video shows
4. User 2: Login → See incoming call → Accept
5. Both: Verify both video streams visible
6. Both: Test camera/mic toggles
7. User 1: Click "End Call"
✅ Success: Clear video, working controls
```

## 🎯 API Endpoints Being Tested

```bash
POST   /api/v1/login                    # Authentication
GET    /api/v1/conversations            # Load conversations
POST   /api/v1/calls/initiate           # Start call
POST   /api/v1/calls/{id}/answer        # Accept call
POST   /api/v1/calls/{id}/reject        # Decline call
POST   /api/v1/calls/{id}/end           # End call
GET    /api/v1/calls/history            # Call history
```

## 🔑 Key Features

✅ Real-time incoming call detection (Pusher)
✅ Automatic Agora channel join
✅ Audio & Video streaming
✅ Camera/Microphone toggles
✅ Call history tracking
✅ Professional UI/UX
✅ Activity logging
✅ Status indicators

## 🎨 Status Colors

| Color    | Meaning            |
| -------- | ------------------ |
| 🟢 Green | Active/Connected   |
| 🔴 Red   | Error/Disconnected |
| ⚪ Gray  | Inactive/Waiting   |

## 🐛 Quick Troubleshooting

| Issue                      | Solution                         |
| -------------------------- | -------------------------------- |
| No video showing           | Check browser camera permissions |
| Can't hear audio           | Check microphone permissions     |
| Call not connecting        | Run diagnostics button           |
| Incoming call not detected | Check Pusher connection in logs  |
| Login fails                | Verify email/password correct    |

## 📱 Browser Compatibility

✅ Chrome (Recommended)
✅ Edge
✅ Firefox
⚠️ Safari (May need permissions)

## 🎓 Demo Script (1 minute)

```
"Let me show you our call testing interface..."

1. [Login] "Quick login with test users"
2. [Show Status] "Real-time system status"
3. [Select Conv] "Choose a conversation"
4. [Initiate] "Start an audio or video call"
5. [Show Incoming] "Other user sees this"
6. [Accept] "Call connects automatically"
7. [Show Video] "Both streams active"
8. [Toggles] "Full media control"
9. [End] "Clean termination"

"And that's how easy it is to test our call system!"
```

## 💡 Pro Tips

1. **Use Diagnostics** - Quick system health check
2. **Check Logs** - All activity is logged
3. **Test Both Types** - Audio and video separately
4. **Multiple Devices** - Best for real testing
5. **Check Permissions** - Camera/mic must be allowed

## 📞 Support Checklist

Before reporting issues:

-   [ ] Checked activity logs
-   [ ] Ran diagnostics
-   [ ] Verified browser permissions
-   [ ] Tested in Chrome
-   [ ] Checked console for errors

## 🎯 Success Criteria

A successful call test should show:
✅ User logs in smoothly
✅ Conversations load
✅ Call initiates without errors
✅ Other user receives notification
✅ Audio/video streams work
✅ Toggles function correctly
✅ Call ends cleanly
✅ No errors in logs

---

**Print this card and keep it handy for quick reference!**

Last Updated: November 13, 2025
