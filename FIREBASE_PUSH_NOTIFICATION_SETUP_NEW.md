# Firebase & Push Notification Setup Guide - New Configuration

## Overview

This guide covers the complete setup for push notifications with the new Firebase project `connect-app-efa83`, supporting both:

-   **FCM (Firebase Cloud Messaging)** for native Android/iOS apps
-   **Expo Push Notifications** for React Native apps built with Expo

---

## 🔥 Step 1: Generate New Firebase Credentials

### A. Get Firebase Server Key (Legacy API)

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: **connect-app-efa83**
3. Click the gear icon ⚙️ → **Project Settings**
4. Go to **Cloud Messaging** tab
5. Under "Cloud Messaging API (Legacy)", find **Server Key**
6. Copy and update in `.env`:
    ```env
    FIREBASE_SERVER_KEY=your_server_key_here
    ```

### B. Generate Service Account Credentials

1. In Firebase Console, go to **Project Settings** → **Service Accounts**
2. Click **Generate New Private Key**
3. Save the downloaded JSON file
4. Replace the content of `storage/app/firebase-credentials.json` with the new file content
5. The file should have this structure:
    ```json
    {
      "type": "service_account",
      "project_id": "connect-app-efa83",
      "private_key_id": "...",
      "private_key": "-----BEGIN PRIVATE KEY-----\n...",
      "client_email": "firebase-adminsdk-...@connect-app-efa83.iam.gserviceaccount.com",
      ...
    }
    ```

### C. Generate VAPID Keys (for Web Push)

1. In Firebase Console → **Project Settings** → **Cloud Messaging**
2. Under "Web configuration", find **Web Push certificates**
3. Click **Generate key pair** if you don't have one
4. Copy the key pair and update in `.env`:
    ```env
    FIREBASE_VAPID_KEY=your_vapid_public_key_here
    VAPID_PUBLIC_KEY=your_vapid_public_key_here
    VAPID_PRIVATE_KEY=your_vapid_private_key_here
    ```

---

## 📱 Step 2: Updated Configuration

### .env File (Already Updated)

```env
# Firebase Configuration - NEW PROJECT (connect-app-efa83)
FIREBASE_SERVER_KEY=NEEDS_TO_BE_GENERATED_FROM_FIREBASE_CONSOLE
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=connect-app-efa83
FIREBASE_DATABASE_URL=https://connect-app-efa83.firebaseio.com

# Firebase Web App Configuration (for frontend)
FIREBASE_API_KEY=AIzaSyAK6OMyJ3omcjQ21X5mekMOOfA7uONdu3g
FIREBASE_AUTH_DOMAIN=connect-app-efa83.firebaseapp.com
FIREBASE_STORAGE_BUCKET=connect-app-efa83.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=1075408006474
FIREBASE_APP_ID=1:1075408006474:web:95219cd681cfb70274a5b3
FIREBASE_MEASUREMENT_ID=G-7H94PFKPVQ
FIREBASE_VAPID_KEY=NEEDS_TO_BE_GENERATED_FROM_FIREBASE_CONSOLE
```

### Google OAuth (Already Configured)

```env
GOOGLE_CLIENT_ID=1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
```

---

## 🚀 Step 3: How It Works Now

### Token Detection & Routing

The system now automatically detects the token type and routes to the appropriate service:

```php
// In FirebaseService::sendNotification()

1. If token format is "ExponentPushToken[xxx]" or "ExponentialPushToken[xxx]"
   → Route to ExpoNotificationService
   → Send via Expo Push API (https://exp.host/--/api/v2/push/send)

2. If token is a standard FCM token
   → Try FCM HTTP v1 API (with service account auth)
   → Fallback to FCM Legacy API (with server key)
   → For admin notifications, fallback to Web Push
```

### Supported Token Formats

1. **Expo Push Tokens** (for Expo/React Native apps):

    - Format: `ExponentPushToken[xxxxxxxxxxxxxx]`
    - Example from your login: `ExponentPushToken[1b4NasOze1qsCSXIH6jl9n]`
    - Service: `ExpoNotificationService`

2. **FCM Tokens** (for native Android/iOS):

    - Format: Long alphanumeric string with colons
    - Example: `eGu7:APA91bF...`
    - Service: `FirebaseService` (FCM v1 or Legacy API)

3. **Web Push Tokens** (for web browsers):
    - Stored with endpoint, p256dh, and auth keys
    - Service: `WebPushService`

---

## 🧪 Step 4: Testing Push Notifications

### Test Script

Run this test to verify push notifications work:

```bash
php test_new_push_notifications.php
```

### Manual Test via Admin Panel

1. Go to Admin Dashboard → Notifications
2. Send a test notification to a user
3. Check `push_notification_logs` table for delivery status

### Test with Mobile App

When a user logs in with Google on mobile, they send:

```json
{
    "id_token": "eyJhbGci...",
    "device_token": "ExponentPushToken[1b4NasOze1qsCSXIH6jl9n]"
}
```

Your backend should:

1. Validate the `id_token` with Google
2. Store `device_token` in `user_fcm_tokens` table
3. When sending notification, detect it's an Expo token
4. Route to Expo service automatically

---

## 📊 Monitoring & Logs

### Check Notification Logs

```sql
-- Recent push notifications
SELECT * FROM push_notification_logs
ORDER BY created_at DESC
LIMIT 20;

-- Count by status
SELECT status, COUNT(*) as count
FROM push_notification_logs
GROUP BY status;

-- Check for Expo tokens
SELECT * FROM user_fcm_tokens
WHERE fcm_token LIKE 'ExponentPushToken%';
```

### Laravel Logs

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i "push\|expo\|fcm"
```

---

## 🔧 Troubleshooting

### Issue 1: FCM Authentication Failed

**Solution**: Ensure you've generated and saved the new service account JSON from the `connect-app-efa83` project.

### Issue 2: Expo Notifications Not Received

**Possible causes**:

-   Expo app not configured correctly on mobile
-   Token not stored in database
-   Token format incorrect

**Check**:

```php
// Validate token format
php artisan tinker
>>> App\Services\ExpoNotificationService::isExpoPushToken('ExponentPushToken[1b4NasOze1qsCSXIH6jl9n]')
=> true
```

### Issue 3: Web Push Not Working

**Solution**: Generate new VAPID keys from Firebase Console for the new project.

---

## 🎯 Next Steps

1. ✅ Update `.env` with Firebase credentials (DONE)
2. ⚠️ Download new service account JSON from Firebase Console
3. ⚠️ Generate Firebase Server Key and VAPID keys
4. ✅ Expo notification service added (DONE)
5. ✅ Auto-detection of token types (DONE)
6. 🧪 Test with real mobile devices

---

## 📝 Important Notes

### Google Sign-In Configuration

Your Google OAuth client IDs from the token:

-   **azp** (authorized party): `1075408006474-6kog91c8p5286eph9itajqobe7ur1mtl.apps.googleusercontent.com`
-   **aud** (audience): `1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com`

These are both under the same project (1075408006474), which matches your new Firebase project.

### Firebase Project Consistency

Make sure all mobile apps and web apps use the same Firebase configuration:

-   Project ID: `connect-app-efa83`
-   Messaging Sender ID: `1075408006474`
-   App ID: `1:1075408006474:web:95219cd681cfb70274a5b3`

---

## 📞 API Integration

### Mobile App (Expo/React Native)

```javascript
// In your mobile app
import * as Notifications from "expo-notifications";

// Get Expo Push Token
const token = (await Notifications.getExpoPushTokenAsync()).data;
// Returns: "ExponentPushToken[xxxxxx]"

// Send to backend
await fetch("/api/v1/user/fcm-token", {
    method: "POST",
    body: JSON.stringify({ fcm_token: token }),
});
```

### Backend API Endpoint

Your backend should accept the token at:

-   Endpoint: `POST /api/v1/user/fcm-token` or similar
-   Stores in: `user_fcm_tokens` table
-   Column: `fcm_token` (can store both FCM and Expo tokens)

---

## ✅ Verification Checklist

-   [ ] Firebase Server Key updated in `.env`
-   [ ] New service account JSON downloaded and saved
-   [ ] VAPID keys generated and updated
-   [ ] Test notification sent successfully
-   [ ] Mobile app receives notifications
-   [ ] Web app receives notifications (if applicable)
-   [ ] Logs show successful delivery
-   [ ] `push_notification_logs` table records match

---

## 🔗 Useful Links

-   [Firebase Console](https://console.firebase.google.com/project/connect-app-efa83)
-   [Expo Push Notifications Documentation](https://docs.expo.dev/push-notifications/overview/)
-   [FCM HTTP v1 API Documentation](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
-   [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/)

---

**Last Updated**: January 4, 2026
**Project**: Connect App - New Firebase Configuration (connect-app-efa83)
