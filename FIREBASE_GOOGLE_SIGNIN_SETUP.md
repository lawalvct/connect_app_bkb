# Firebase Google Sign-In Setup Summary

## ✅ Current Configuration Status

Your backend is **READY** to work with Firebase Authentication! Here's what's configured:

### Firebase Project Details

-   **Project ID:** `connect-app-efa83`
-   **Project Number:** `1075408006474`
-   **Package Name:** `com.app.connectapp`
-   **Storage Bucket:** `connect-app-efa83.firebasestorage.app`

### OAuth Client IDs

1. **Android Client ID:** `1075408006474-6kog91c8p5286eph9itajqobe7ur1mtl.apps.googleusercontent.com`

    - Type: Android App
    - SHA-1 Certificate: `72ad6aa9b3fbd37a189e314758217d701ca127f3`

2. **Web Client ID (Backend Verification):** `1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com`
    - Type: Web Application
    - Used for: ID token verification on backend

---

## Backend Configuration

### .env Settings (Updated)

```env
# Google OAuth - Web Client ID for backend verification
GOOGLE_CLIENT_ID='1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com'
GOOGLE_CLIENT_SECRET='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'
GOOGLE_REDIRECT_URI=https://www.admin.connectinc.app/api/v1/auth/google/callback
```

### API Endpoints Ready

✅ `POST /api/v1/auth/google/token` - Token-based authentication
✅ `POST /api/v1/auth/google/user-data` - User data-based authentication (Recommended)

---

## How It Works

### Flow Diagram

```
Mobile App (React Native)
    ↓
1. User taps "Sign in with Google"
    ↓
2. Firebase Auth SDK handles OAuth flow
    ↓
3. App receives: ID Token + User Data
    ↓
4. App sends to: POST /api/v1/auth/google/user-data
    {
        "id": "firebase_uid",
        "email": "user@email.com",
        "name": "User Name",
        "avatar": "https://...",
        "id_token": "eyJhbGci...",
        "device_token": "fcm_token"
    }
    ↓
5. Backend verifies ID token (optional but recommended)
    ↓
6. Backend creates/updates user
    ↓
7. Backend returns API token
    {
        "token": "2|abc...",
        "user": {...},
        "is_new_user": true/false
    }
    ↓
8. App stores token and navigates to home
```

---

## What Your Backend Does

### ✅ Already Implemented Features

1. **Google ID Token Verification**

    - Verifies token authenticity using Google's public keys
    - Validates token data matches provided user data
    - Prevents token spoofing attacks

2. **Automatic User Management**

    - Creates new users on first sign-in
    - Updates existing users with social data
    - Auto-verifies email addresses
    - Generates unique usernames

3. **Profile Picture Handling**

    - Downloads Google profile pictures
    - Saves to your storage system
    - Creates profile upload records

4. **Security Features**

    - Provider validation (google, facebook, apple)
    - Comprehensive error logging
    - Device token storage for push notifications
    - 30-day token expiration

5. **Welcome Flow**
    - Sends welcome emails to new users
    - Returns `is_new_user` flag for onboarding

---

## Mobile App Integration Checklist

### React Native Setup

#### ✅ Required Packages

```bash
npm install @react-native-firebase/app
npm install @react-native-firebase/auth
npm install @react-native-google-signin/google-signin
npm install axios
```

#### ✅ Android Configuration

1. **Add google-services.json**

    - Location: `android/app/google-services.json`
    - File provided in project root

2. **Update android/build.gradle:**

```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

3. **Update android/app/build.gradle:**

```gradle
apply plugin: 'com.google.gms.google-services' // At bottom
```

#### ✅ Code Configuration

```javascript
import { GoogleSignin } from "@react-native-google-signin/google-signin";

GoogleSignin.configure({
    webClientId:
        "1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com",
});
```

---

## Testing Your Setup

### 1. Test with Postman

```bash
POST https://your-api.com/api/v1/auth/google/user-data
Content-Type: application/json

{
  "id": "test_google_uid_123",
  "email": "test@gmail.com",
  "name": "Test User",
  "avatar": "https://lh3.googleusercontent.com/a/photo",
  "id_token": "eyJhbGci...",  # Optional for testing
  "device_token": "fcm_test_token"
}
```

**Expected Response:**

```json
{
  "success": true,
  "message": "Social login successful",
  "data": {
    "user": {
      "id": 123,
      "name": "Test User",
      "email": "test@gmail.com",
      "username": "testgmail",
      "is_verified": true,
      ...
    },
    "token": "2|abcdef123456...",
    "is_new_user": true
  }
}
```

### 2. Check Backend Logs

Monitor Laravel logs for:

-   ✅ "Social login with user data successful"
-   ✅ "Found random user from system"
-   ✅ User creation/update logs

### 3. Verify Database

Check `users` table for:

-   `social_id` = Google UID
-   `social_type` = 'google'
-   `email_verified_at` = timestamp
-   `is_verified` = true

---

## Common Issues & Solutions

### Issue 1: "Invalid Google ID token"

**Cause:** Web Client ID mismatch
**Solution:** Ensure mobile app uses Web Client ID: `1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com`

### Issue 2: "Token data mismatch"

**Cause:** ID token doesn't match user data
**Solution:** Ensure you're sending the correct Google UID in both `id` field and token

### Issue 3: Google Sign-In fails in app

**Cause:** SHA-1 certificate mismatch
**Solution:**

1. Get your debug/release SHA-1: `keytool -list -v -keystore ~/.android/debug.keystore`
2. Add to Firebase Console
3. Download new google-services.json

### Issue 4: "Network request failed"

**Cause:** Backend URL incorrect or unreachable
**Solution:** Verify API URL is correct and accessible from mobile device

---

## Security Best Practices

### ✅ Currently Implemented

-   HTTPS-only API calls
-   ID token verification (optional)
-   Provider validation
-   Secure token storage requirements
-   Comprehensive error logging

### 📋 Recommended for Production

1. **Rate Limiting**

    - Already configured in routes
    - Monitors suspicious activity

2. **Token Refresh**

    - 30-day token expiration set
    - Implement refresh token flow

3. **Device Management**

    - Device tokens stored
    - Can track login devices

4. **Audit Logging**
    - All auth attempts logged
    - Monitor for unusual patterns

---

## What's Different from Standard OAuth?

### Traditional OAuth Flow (Web)

```
App → Google OAuth → Callback URL → Backend → Token
```

### Firebase Auth Flow (Mobile)

```
App → Firebase SDK → Google → Firebase → ID Token → Backend → API Token
```

**Benefits:**

-   ✅ Faster authentication (fewer redirects)
-   ✅ Better mobile experience
-   ✅ Offline token verification
-   ✅ Works with React Native out of the box
-   ✅ No need for deep linking

---

## Next Steps

1. **Mobile App Development**

    - Follow [MOBILE_SOCIAL_LOGIN_GUIDE.md](MOBILE_SOCIAL_LOGIN_GUIDE.md)
    - Implement the React Native example code
    - Test on emulator first, then real device

2. **Testing**

    - Test with Postman endpoint
    - Test in development environment
    - Verify user creation in database

3. **Production Deployment**
    - Add production OAuth credentials
    - Update API URL in mobile app
    - Test with production Firebase project
    - Monitor logs for any issues

---

## Support & Documentation

-   **Laravel Backend:** Already configured ✅
-   **API Documentation:** [MOBILE_SOCIAL_LOGIN_GUIDE.md](MOBILE_SOCIAL_LOGIN_GUIDE.md)
-   **Firebase Console:** https://console.firebase.google.com/project/connect-app-efa83
-   **Google Cloud Console:** https://console.cloud.google.com/

---

## Summary

### ✅ What's Working

-   Backend API endpoints configured
-   Google OAuth credentials set
-   ID token verification enabled
-   User creation/update logic implemented
-   Profile picture download working
-   Welcome emails configured

### 🎯 What Mobile Team Needs to Do

1. Add Firebase packages to React Native app
2. Add google-services.json to Android project
3. Configure Google Sign-In with Web Client ID
4. Implement the sign-in flow (code provided)
5. Test with backend API
6. Handle token storage and navigation

### 🚀 Ready to Go!

Your backend is fully configured and ready to handle Firebase-based Google Sign-In from your React Native mobile app!
