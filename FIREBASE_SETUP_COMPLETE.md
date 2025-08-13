# Firebase Push Notification Setup Guide

## 🚀 Quick Fix for "firebase is not defined" Error

The error occurs because the Firebase SDK wasn't properly loaded. I've fixed this by:

### 1. ✅ Added Firebase SDK Scripts

Updated the subscription page to load Firebase SDK before using it:

```html
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>
```

### 2. ✅ Enhanced Configuration

Updated `config/services.php` with complete Firebase web app settings:

```php
'firebase' => [
    'server_key' => env('FIREBASE_SERVER_KEY'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    // ... existing settings
    // NEW: Web app configuration
    'api_key' => env('FIREBASE_API_KEY'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
    'vapid_key' => env('FIREBASE_VAPID_KEY'),
],
```

### 3. ✅ Dynamic Service Worker

Created a dynamic service worker route that uses your Laravel configuration:

-   Route: `/firebase-messaging-sw.js`
-   Automatically includes your Firebase config from `.env`

### 4. ✅ Added Firebase Test Page

Created a comprehensive test page: `/firebase-test`

-   Tests Firebase SDK loading
-   Checks configuration
-   Tests token generation
-   Debug console

## 🔧 Required Setup Steps

### Step 1: Configure Environment Variables

Add these to your `.env` file (get values from Firebase Console):

```bash
# Firebase Configuration
FIREBASE_SERVER_KEY=your-firebase-server-key
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=your-app-id
FIREBASE_VAPID_KEY=your-vapid-key
```

### Step 2: Get Firebase Configuration

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project (or create new one)
3. Go to Project Settings → General tab
4. Scroll to "Your apps" section
5. Click "Web app" or add new web app
6. Copy the configuration values to your `.env`

### Step 3: Generate VAPID Key

1. In Firebase Console → Project Settings
2. Go to "Cloud Messaging" tab
3. In "Web configuration" section
4. Generate or copy the "Web Push certificates" key
5. Add to `FIREBASE_VAPID_KEY` in `.env`

## 🧪 Testing Steps

### Test 1: Basic Configuration Test

1. Visit: `http://your-domain/firebase-test`
2. Check if all status indicators are green
3. Click "Request Permission" and allow notifications
4. Click "Get FCM Token" to verify token generation

### Test 2: Admin Subscription Test

1. Visit: `http://your-domain/admin/notifications/subscription`
2. Click "Subscribe" button
3. Should see "Successfully subscribed" message
4. Device should appear in the devices table

### Test 3: Send Test Notification

1. In subscription page, fill test notification form
2. Click "Send Test Notification"
3. Should receive notification in browser

## 🐛 Troubleshooting

### Problem: "firebase is not defined"

-   **Cause**: Firebase SDK not loaded
-   **Solution**: ✅ Fixed - SDK now loads before our scripts

### Problem: Configuration errors

-   **Cause**: Missing `.env` variables
-   **Solution**: Set all required Firebase environment variables

### Problem: No notifications received

-   **Cause**: Permission not granted or service worker not registered
-   **Solution**: Use test page to check permissions and service worker

### Problem: Token not generated

-   **Cause**: Invalid VAPID key or configuration
-   **Solution**: Verify Firebase console settings match `.env` values

## 📁 Files Modified

1. **Fixed Main Issue**:

    - `resources/views/admin/notifications/subscription.blade.php` - Added Firebase SDK loading

2. **Enhanced Configuration**:

    - `config/services.php` - Added web app Firebase settings
    - `.env.example` - Added Firebase environment variables template

3. **Added Dynamic Service Worker**:

    - `routes/web.php` - Added `/firebase-messaging-sw.js` route

4. **Added Test Tools**:
    - `resources/views/firebase-test.blade.php` - Firebase configuration test page
    - `routes/web.php` - Added `/firebase-test` route

## ✅ Next Steps

1. **Configure `.env`**: Add your Firebase project values
2. **Test Firebase**: Visit `/firebase-test` to verify setup
3. **Test Subscription**: Visit `/admin/notifications/subscription`
4. **Production**: Move Firebase scripts to your main layout for better performance

The main issue has been resolved - Firebase SDK now loads properly before your scripts use it! 🎉
