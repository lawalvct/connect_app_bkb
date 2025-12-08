# 🔥 Firebase Push Notification Fix - Missing Server Key

## ❌ Current Problem

Your notification is failing because:

```
Error: "FCM v1 and Legacy API unavailable"
```

**Root Cause**: Your `FIREBASE_SERVER_KEY` in `.env` is actually a **VAPID key** (for Web Push), not a **Firebase Cloud Messaging Server Key**.

Current value:

```
FIREBASE_SERVER_KEY=0DTTvwlPv2QQFm-8-2_ldPKs8HEEFUIbodSHGrDin9I  ❌ (This is VAPID, not FCM)
```

## ✅ Solution: Get the Correct Firebase Server Key

### Step 1: Go to Firebase Console

1. Visit: https://console.firebase.google.com/
2. Select your project: **connect-app-fbaca**

### Step 2: Get the Server Key (Legacy API)

1. Click the **⚙️ Settings icon** (top left)
2. Select **Project settings**
3. Go to the **Cloud Messaging** tab
4. Scroll down to **Cloud Messaging API (Legacy)**
5. **IMPORTANT**: If you see "Cloud Messaging API (Legacy) disabled", you need to enable it:

    - Click **⋮ (three dots)** or **Manage API**
    - Enable "Firebase Cloud Messaging API (Legacy)"

6. Copy the **Server key** (it should look like):
    ```
    AAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    ```
    (Starts with `AAAA` and is ~152+ characters long)

### Step 3: Update Your .env File

Replace the current `FIREBASE_SERVER_KEY` with the correct one:

```env
# OLD (VAPID key - keep this as is for web push)
FIREBASE_VAPID_KEY=0DTTvwlPv2QQFm-8-2_ldPKs8HEEFUIbodSHGrDin9I

# NEW (Add the real FCM Server Key from Firebase Console)
FIREBASE_SERVER_KEY=AAAAxxxxxxx...your-long-server-key-here
```

### Step 4: Clear Cache and Test

```bash
php artisan config:clear
php artisan cache:clear

# Test again with your payload
```

---

## 🔍 Alternative: Use Only FCM HTTP v1 API (Recommended)

If you don't want to use the Legacy API, you can rely **only on FCM HTTP v1 API** which uses the service account JSON file.

### Verify Service Account Credentials

Your service account file is at: `storage/app/firebase-credentials.json`

1. **Check if it's valid**:

```bash
php artisan tinker --execute="echo json_encode(json_decode(file_get_contents(storage_path('app/firebase-credentials.json')), true), JSON_PRETTY_PRINT);"
```

2. **Look for these required fields**:
    - `project_id`: should be "connect-app-fbaca"
    - `private_key`: should start with "-----BEGIN PRIVATE KEY-----"
    - `client_email`: should end with "@connect-app-fbaca.iam.gserviceaccount.com"

### If Service Account is Valid

The FCM v1 API should work! The issue might be:

1. **OAuth token generation failing** - Check logs:

    ```bash
    tail -f storage/logs/laravel.log | grep "FCM"
    ```

2. **Network/Firewall blocking OAuth2** - Test connection:
    ```bash
    php artisan tinker --execute="echo json_encode(\Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/token')->status());"
    ```

---

## 🧪 Test Firebase Connection

Run this in tinker to test the connection:

```bash
php artisan tinker
```

Then paste this code:

```php
$service = app(\App\Services\FirebaseService::class);
$token = 'cIfXnpaIDTc99tZs82hRiz:APA91bG-Nq1eO3fsIpjQd4DynRmhIcdaN-Kle45ZuUtA74Eqd_29vz5jkbB2YmFd56No73arnAkPrKuC_GJgd9_rwDsJNXfpFmnm53Aadko1iGPKCnPBhas';
$result = $service->sendNotification($token, 'Test', 'Testing FCM', [], 3114);
echo $result ? "SUCCESS!" : "FAILED - Check logs";
```

---

## 📝 What Each Key Does

| Key                         | Purpose                      | Format         | Length     |
| --------------------------- | ---------------------------- | -------------- | ---------- |
| `FIREBASE_SERVER_KEY`       | FCM Legacy API (mobile push) | `AAAAxxxxx...` | ~152 chars |
| `FIREBASE_VAPID_KEY`        | Web Push (browsers)          | Base64 string  | ~87 chars  |
| `FIREBASE_CREDENTIALS_PATH` | FCM v1 API (service account) | JSON file path | -          |

---

## ✅ Quick Fix Checklist

-   [ ] Get FCM Server Key from Firebase Console → Cloud Messaging tab
-   [ ] Update `.env` with correct `FIREBASE_SERVER_KEY`
-   [ ] Keep `FIREBASE_VAPID_KEY` as is (it's correct for web push)
-   [ ] Run `php artisan config:clear`
-   [ ] Test notification again
-   [ ] Check `storage/logs/laravel.log` for detailed errors

---

## 🎯 Expected Behavior After Fix

### ✅ Success Response

```json
{
    "success": true,
    "message": "Push notifications sent successfully",
    "sent": 1,
    "failed": 0,
    "mode": "immediate"
}
```

### ✅ Success Log Entry

```sql
SELECT * FROM push_notification_logs WHERE user_id = 3114 ORDER BY id DESC LIMIT 1;

-- Should show:
status: "sent"
response: {"success": true, "method": "fcm_legacy"}  -- or "fcm_v1"
error_message: NULL
```

---

## 🆘 Still Not Working?

If you get the correct Server Key and it still fails, check:

1. **FCM Token is valid** (user's device token):

    ```sql
    SELECT * FROM user_fcm_tokens WHERE user_id = 3114 AND is_active = 1;
    ```

2. **User's app is using the correct Firebase project**:

    - Android: Check `google-services.json` package name
    - iOS: Check `GoogleService-Info.plist` bundle ID

3. **Firebase Cloud Messaging API is enabled**:

    - Go to: https://console.cloud.google.com/apis/library/fcm.googleapis.com
    - Make sure it's **enabled** for your project

4. **Test with Firebase Console directly**:
    - Firebase Console → Cloud Messaging → Send test message
    - Use the same FCM token
    - If this fails, the token is invalid/expired

---

## 💡 Recommended Setup (Production)

Use **FCM HTTP v1 API only** (most secure, doesn't require server key):

1. ✅ Keep service account JSON file: `storage/app/firebase-credentials.json`
2. ✅ Enable FCM API in Google Cloud Console
3. ✅ Remove dependency on Legacy Server Key
4. ✅ Use queue for bulk sends: `"use_queue": true`

This way you don't need to manage the legacy `FIREBASE_SERVER_KEY` at all!
