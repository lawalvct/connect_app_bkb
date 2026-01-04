# Quick Reference: New Firebase Setup

## ⚡ Quick Start Checklist

### 1. Get Firebase Server Key

```
1. Go to: https://console.firebase.google.com/project/connect-app-efa83/settings/cloudmessaging
2. Find "Cloud Messaging API (Legacy)" section
3. Copy "Server key"
4. Update in .env: FIREBASE_SERVER_KEY=your_server_key
```

### 2. Download Service Account JSON

```
1. Go to: https://console.firebase.google.com/project/connect-app-efa83/settings/serviceaccounts/adminsdk
2. Click "Generate new private key"
3. Save and replace: storage/app/firebase-credentials.json
```

### 3. Get VAPID Keys (Web Push)

```
1. Go to: https://console.firebase.google.com/project/connect-app-efa83/settings/cloudmessaging
2. Under "Web Push certificates", click "Generate key pair"
3. Update in .env:
   FIREBASE_VAPID_KEY=your_public_key
   VAPID_PUBLIC_KEY=your_public_key
   VAPID_PRIVATE_KEY=your_private_key
```

### 4. Test

```bash
php test_new_push_notifications.php
```

---

## 📋 What's Different?

| Component  | Old (connect-app-fbaca) | New (connect-app-efa83) |
| ---------- | ----------------------- | ----------------------- |
| Project ID | connect-app-fbaca       | connect-app-efa83       |
| Sender ID  | 878521426508            | 1075408006474           |
| API Key    | AIzaSyCR4...            | AIzaSyAK6...            |
| App ID     | 1:878521426508:web:...  | 1:1075408006474:web:... |

---

## 🎯 Token Types Supported

1. **Expo Tokens** (Mobile - React Native/Expo)

    - Format: `ExponentPushToken[xxxxxx]`
    - Service: ExpoNotificationService
    - API: https://exp.host/--/api/v2/push/send

2. **FCM Tokens** (Mobile - Native)

    - Format: Long string with colons
    - Service: FirebaseService (v1 or Legacy)
    - API: FCM HTTP v1 or Legacy

3. **Web Push** (Browser)
    - Stored with endpoint/keys
    - Service: WebPushService
    - Uses VAPID keys

---

## 🧪 Test Commands

```bash
# Test push notification setup
php test_new_push_notifications.php

# Check database for tokens
php artisan tinker
>>> DB::table('user_fcm_tokens')->where('fcm_token', 'LIKE', 'ExponentPushToken%')->count()

# Check recent notification logs
>>> DB::table('push_notification_logs')->orderBy('created_at', 'desc')->limit(5)->get()

# Test Expo token detection
>>> App\Services\ExpoNotificationService::isExpoPushToken('ExponentPushToken[xxx]')
```

---

## 🔧 Common Issues & Fixes

### Issue: "Service account not found"

**Fix**: Download new service account JSON from connect-app-efa83 project

### Issue: "Invalid FCM server key"

**Fix**: Get new server key from Cloud Messaging settings

### Issue: Expo notifications not working

**Check**:

-   Token format starts with "ExponentPushToken["
-   Token is stored in user_fcm_tokens table
-   Expo app is properly configured

### Issue: Web push not working

**Fix**: Generate new VAPID keys for the new project

---

## 📱 Mobile App Integration

### Expo/React Native

```javascript
import * as Notifications from "expo-notifications";

// Get token
const token = (await Notifications.getExpoPushTokenAsync()).data;

// Send to backend
await fetch("/api/v1/user/fcm-token", {
    method: "POST",
    headers: { Authorization: `Bearer ${accessToken}` },
    body: JSON.stringify({ fcm_token: token }),
});
```

### Google Sign-In Response

```json
{
    "id_token": "eyJhbGci...",
    "device_token": "ExponentPushToken[xxx]"
}
```

Backend stores `device_token` in `user_fcm_tokens.fcm_token`

---

## 📊 Monitoring

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i "push\|expo\|fcm"
```

### Database Queries

```sql
-- Count by status
SELECT status, COUNT(*) FROM push_notification_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY status;

-- Recent failures
SELECT * FROM push_notification_logs
WHERE status = 'failed'
ORDER BY created_at DESC LIMIT 10;

-- Token types
SELECT
  CASE
    WHEN fcm_token LIKE 'ExponentPushToken%' THEN 'Expo'
    ELSE 'FCM'
  END as token_type,
  COUNT(*) as count
FROM user_fcm_tokens
WHERE is_active = 1
GROUP BY token_type;
```

---

## ✅ Verification

After setup, verify:

-   [ ] Server key updated in .env
-   [ ] Service account JSON replaced
-   [ ] VAPID keys generated
-   [ ] Test script runs without errors
-   [ ] Mobile app receives notifications
-   [ ] Logs show 'sent' status
-   [ ] Database logs match sent count

---

## 🔗 Quick Links

-   [Firebase Console - connect-app-efa83](https://console.firebase.google.com/project/connect-app-efa83)
-   [Cloud Messaging Settings](https://console.firebase.google.com/project/connect-app-efa83/settings/cloudmessaging)
-   [Service Accounts](https://console.firebase.google.com/project/connect-app-efa83/settings/serviceaccounts/adminsdk)
-   [Expo Push Docs](https://docs.expo.dev/push-notifications/overview/)

---

**Need Help?** See full guide: `FIREBASE_PUSH_NOTIFICATION_SETUP_NEW.md`
