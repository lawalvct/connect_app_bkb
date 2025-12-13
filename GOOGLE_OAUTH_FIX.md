# Google OAuth "redirect_uri_mismatch" Error - Fix Guide

## 🔴 Error

```
Error 400: redirect_uri_mismatch
Access blocked: This app's request is invalid
```

## 🔍 Root Cause

The redirect URI in your `.env` file doesn't match what's configured in Google Cloud Console.

**Current `.env` setting:**

```env
GOOGLE_REDIRECT_URI=https://www.connectinc.app/api/v1/auth/google/callback
```

---

## ✅ SOLUTION

### Step 1: Determine Your Actual Callback URL

Your backend route is: `/api/v1/auth/google/callback`

**For Local Development:**

-   Backend URL: `http://localhost:8000/api/v1/auth/google/callback`
-   Or: `http://127.0.0.1:8000/api/v1/auth/google/callback`

**For Production:**

-   Backend URL: `https://admin.connectinc.app/api/v1/auth/google/callback`
-   Or: `https://www.connectinc.app/api/v1/auth/google/callback`
-   Or: `https://api.connectinc.app/api/v1/auth/google/callback`

### Step 2: Update Google Cloud Console

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project: **Connect App** (Project ID might be `connect-app-fbaca`)
3. Navigate to: **APIs & Services** → **Credentials**
4. Find your OAuth 2.0 Client ID: `1067512101207-hs3d6gkrjku81b1iq29u2aooteeen29g.apps.googleusercontent.com`
5. Click on the client ID to edit
6. Under **Authorized redirect URIs**, add ALL of these:

    ```
    http://localhost:8000/api/v1/auth/google/callback
    http://127.0.0.1:8000/api/v1/auth/google/callback
    https://admin.connectinc.app/api/v1/auth/google/callback
    https://www.connectinc.app/api/v1/auth/google/callback
    https://api.connectinc.app/api/v1/auth/google/callback
    ```

7. Click **Save**

### Step 3: Update Your `.env` File

#### For Local Development:

```env
GOOGLE_CLIENT_ID='1067512101207-hs3d6gkrjku81b1iq29u2aooteeen29g.apps.googleusercontent.com'
GOOGLE_CLIENT_SECRET='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'
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback
```

#### For Production (aaPanel Server):

```env
GOOGLE_CLIENT_ID='1067512101207-hs3d6gkrjku81b1iq29u2aooteeen29g.apps.googleusercontent.com'
GOOGLE_CLIENT_SECRET='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'
GOOGLE_REDIRECT_URI=https://admin.connectinc.app/api/v1/auth/google/callback
```

### Step 4: Clear Config Cache

**Local:**

```bash
php artisan config:clear
php artisan optimize:clear
```

**Production (via aaPanel SSH):**

```bash
cd /www/wwwroot/admin.connectinc.app
php artisan config:clear
php artisan optimize:clear
```

---

## 🔍 DEBUGGING

### Check Current Configuration

```bash
php artisan tinker
>>> config('services.google.redirect')
```

Should output your redirect URI.

### Test the Redirect URL

```bash
curl -I https://admin.connectinc.app/api/v1/auth/google/callback
```

Should return a valid HTTP response (not 404).

### Common Issues

#### 1. Wrong Domain

❌ `.env` has: `https://www.connectinc.app`
✅ Actual backend: `https://admin.connectinc.app`

**Fix:** Match the domain where your Laravel backend is hosted.

#### 2. Missing HTTPS in Production

❌ `http://admin.connectinc.app`
✅ `https://admin.connectinc.app`

**Fix:** Always use HTTPS in production.

#### 3. Cached Config

❌ Changed `.env` but still getting error
✅ Clear config cache

**Fix:** Run `php artisan config:clear`

#### 4. Frontend vs Backend URL

The redirect URI should point to your **BACKEND** Laravel API, not your frontend!

❌ Frontend: `http://localhost:3000/auth/callback`
✅ Backend: `http://localhost:8000/api/v1/auth/google/callback`

---

## 📱 FRONTEND INTEGRATION

Your frontend should call the backend Google auth URL:

### React/Next.js Example:

```javascript
// Initiate Google Sign In
const handleGoogleSignIn = () => {
    // Call your Laravel backend
    window.location.href = "https://admin.connectinc.app/api/v1/auth/google";
};

// The flow:
// 1. User clicks "Sign in with Google"
// 2. Frontend redirects to: https://admin.connectinc.app/api/v1/auth/google
// 3. Backend redirects to Google OAuth
// 4. User authorizes
// 5. Google redirects back to: https://admin.connectinc.app/api/v1/auth/google/callback
// 6. Backend processes and returns user data or token
```

### Mobile App (React Native) Example:

```javascript
import { WebBrowser } from "expo-web-browser";

const handleGoogleSignIn = async () => {
    const result = await WebBrowser.openAuthSessionAsync(
        "https://admin.connectinc.app/api/v1/auth/google",
        "your-app-scheme://auth"
    );

    if (result.type === "success") {
        // Handle success
    }
};
```

---

## 🎯 QUICK FIX CHECKLIST

For immediate fix, do these in order:

1. [ ] Identify your actual backend URL (check browser network tab or server logs)
2. [ ] Go to [Google Cloud Console Credentials](https://console.cloud.google.com/apis/credentials)
3. [ ] Add the correct redirect URI to your OAuth client
4. [ ] Update `.env` file with the same URI
5. [ ] Run `php artisan config:clear`
6. [ ] Test again

---

## 🚀 PRODUCTION DEPLOYMENT

When deploying to production:

1. **Update `.env` on production server:**

    ```bash
    cd /www/wwwroot/admin.connectinc.app
    nano .env  # or vi .env
    ```

2. **Change the line:**

    ```env
    GOOGLE_REDIRECT_URI=https://admin.connectinc.app/api/v1/auth/google/callback
    ```

3. **Clear caches:**

    ```bash
    php artisan config:clear
    php artisan optimize:clear
    ```

4. **Verify it works:**
    ```bash
    php artisan tinker
    >>> config('services.google.redirect')
    # Should output: "https://admin.connectinc.app/api/v1/auth/google/callback"
    ```

---

## 📝 NOTES

-   Google allows multiple redirect URIs per client ID
-   Add both development and production URLs
-   URLs are case-sensitive
-   Must match exactly (including trailing slash or not)
-   Changes in Google Console take effect immediately (no waiting)

---

**Last Updated:** December 13, 2025
