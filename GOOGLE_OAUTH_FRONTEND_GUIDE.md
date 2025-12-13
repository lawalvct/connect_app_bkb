# Google OAuth Frontend Integration Guide

## ✅ Backend Changes Complete

The backend has been updated to properly redirect back to the frontend after Google authentication instead of displaying JSON.

---

## 🔧 What Changed in Backend

### 1. AuthController.php

-   **Before:** Returned JSON response with user data and token
-   **After:** Redirects to frontend callback URL with token in query params

### 2. .env Configuration

Added:

```env
FRONTEND_URL=http://localhost:3000
```

For production, update to:

```env
FRONTEND_URL=https://www.connectinc.app
```

---

## 📱 FRONTEND IMPLEMENTATION

### Flow Overview

```
1. User clicks "Sign in with Google"
2. Frontend redirects → https://admin.connectinc.app/api/v1/auth/google
3. Backend redirects → Google OAuth
4. User authorizes on Google
5. Google redirects → https://admin.connectinc.app/api/v1/auth/google/callback
6. Backend processes:
   - Creates/finds user
   - Generates token
   - Gets active subscriptions (same as email/password login)
   - Encodes full user data + token as base64
7. Backend redirects → http://localhost:3000/auth/callback?data=eyJ1c2VyIjp7...
8. Frontend decodes data and completes login (same flow as email/password)
```

**Key Point:** Google login now returns the **same response format** as email/password login!

---

## 🎯 REQUIRED FRONTEND CHANGES

### Step 1: Create Auth Callback Route

Create a new route/page: `/auth/callback`

#### React/Next.js Example:

```javascript
// pages/auth/callback.js or app/auth/callback/page.js

"use client"; // if using Next.js 13+ App Router

import { useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";

export default function GoogleAuthCallback() {
    const router = useRouter();
    const searchParams = useSearchParams();

    useEffect(() => {
        // Get encoded auth data from URL params
        const encodedData = searchParams.get("data");

        if (encodedData) {
            try {
                // Decode the auth data (same format as email/password login)
                const decodedData = atob(decodeURIComponent(encodedData));
                const authData = JSON.parse(decodedData);

                // Store token and user data (same as login flow)
                localStorage.setItem("auth_token", authData.token);
                localStorage.setItem("user", JSON.stringify(authData.user));

                console.log("Google login successful:", authData.message);

                // Redirect to home/dashboard
                router.push("/home");
            } catch (error) {
                console.error("Failed to parse auth data:", error);
                router.push(
                    "/login?error=" +
                        encodeURIComponent("Authentication data invalid")
                );
            }
        } else {
            // Handle error case
            const errorMessage =
                searchParams.get("message") || "Authentication failed";
            console.error("Google auth failed:", errorMessage);
            router.push("/login?error=" + encodeURIComponent(errorMessage));
        }
    }, [searchParams, router]);

    return (
        <div className="flex items-center justify-center min-h-screen">
            <div className="text-center">
                <h2 className="text-xl font-semibold mb-4">
                    Completing sign in...
                </h2>
                <div className="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full" />
            </div>
        </div>
    );
}
```

#### React Native Example:

```javascript
// screens/GoogleAuthCallback.js

import React, { useEffect } from "react";
import { View, Text, ActivityIndicator } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { useNavigation } from "@react-navigation/native";
import { WebBrowser } from "expo-web-browser";

export default function GoogleAuthCallback({ route }) {
    const navigation = useNavigation();
    const { token, user_id, name, email } = route.params;

    useEffect(() => {
        handleCallback();
    }, []);

    const handleCallback = async () => {
        try {
            // Parse the data from URL (passed via deep link)
            const { data } = route.params;

            if (data) {
                // Decode the auth data (same format as email/password login)
                const decodedData = atob(decodeURIComponent(data));
                const authData = JSON.parse(decodedData);

                // Store token and user data (same as login flow)
                await AsyncStorage.multiSet([
                    ["auth_token", authData.token],
                    ["user", JSON.stringify(authData.user)],
                ]);

                console.log("Google login successful:", authData.message);

                // Navigate to home
                navigation.reset({
                    index: 0,
                    routes: [{ name: "Home" }],
                });
            } else {
                // Handle error
                navigation.navigate("Login", {
                    error: "Authentication failed",
                });
            }
        } catch (error) {
            console.error("Auth callback error:", error);
            navigation.navigate("Login", { error: error.message });
        }
    };

    return (
        <View
            style={{ flex: 1, justifyContent: "center", alignItems: "center" }}
        >
            <ActivityIndicator size="large" />
            <Text style={{ marginTop: 16 }}>Completing sign in...</Text>
        </View>
    );
}
```

### Step 2: Update Google Sign In Button

#### Web (React/Next.js):

```javascript
const handleGoogleSignIn = () => {
    // Simply redirect to backend Google auth endpoint
    window.location.href = "https://admin.connectinc.app/api/v1/auth/google";
};

// In your JSX:
<button onClick={handleGoogleSignIn}>Sign in with Google</button>;
```

#### Mobile (React Native):

```javascript
import * as WebBrowser from "expo-web-browser";
import * as Linking from "expo-linking";

// Initialize WebBrowser
WebBrowser.maybeCompleteAuthSession();

const handleGoogleSignIn = async () => {
    try {
        // Open browser for Google OAuth
        const result = await WebBrowser.openAuthSessionAsync(
            "https://admin.connectinc.app/api/v1/auth/google",
            "connectapp://auth/callback" // Your app's deep link scheme
        );

        if (result.type === "success") {
            // Parse callback URL
            const { queryParams } = Linking.parse(result.url);

            if (queryParams.token) {
                // Navigate to callback screen with params
                navigation.navigate("GoogleAuthCallback", queryParams);
            }
        }
    } catch (error) {
        console.error("Google sign in error:", error);
        Alert.alert("Error", "Failed to sign in with Google");
    }
};
```

### Step 3: Add Error Handling Route

Create `/auth/error` route to handle authentication errors:

```javascript
// pages/auth/error.js

"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

export default function AuthError() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const [errorMessage, setErrorMessage] = useState("");

    useEffect(() => {
        const message = searchParams.get("message") || "Authentication failed";
        setErrorMessage(message);
    }, [searchParams]);

    return (
        <div className="flex items-center justify-center min-h-screen">
            <div className="text-center max-w-md p-6">
                <h2 className="text-2xl font-bold text-red-600 mb-4">
                    Authentication Failed
                </h2>
                <p className="text-gray-700 mb-6">{errorMessage}</p>
                <button
                    onClick={() => router.push("/login")}
                    className="btn btn-primary"
                >
                    Back to Login
                </button>
            </div>
        </div>
    );
}
```

---

## 🔐 TOKEN STORAGE BEST PRACTICES

### Web (Next.js/React):

**Development:**

```javascript
// OK for development
localStorage.setItem("auth_token", token);
```

**Production (Recommended):**

```javascript
// Use httpOnly cookies (requires backend support)
// Or use a secure token storage library like:
import { SecureStore } from "@auth/secure-store";

SecureStore.setItem("auth_token", token);
```

### Mobile (React Native):

```javascript
// Always use AsyncStorage or SecureStore
import AsyncStorage from "@react-native-async-storage/async-storage";
// OR
import * as SecureStore from "expo-secure-store";

// For sensitive data like tokens:
await SecureStore.setItemAsync("auth_token", token);
```

---

## 🚀 TESTING

### Local Testing (Development):

1. **Start your frontend:**

    ```bash
    npm run dev
    # Make sure it's running on http://localhost:3000
    ```

2. **Make sure backend .env has:**

    ```env
    FRONTEND_URL=http://localhost:3000
    GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback
    ```

3. **Google Console should have:**

    ```
    http://localhost:8000/api/v1/auth/google/callback
    ```

4. **Test the flow:**
    - Click "Sign in with Google"
    - Should redirect to Google
    - After auth, should redirect to: `http://localhost:3000/auth/callback?token=...`

### Production Testing:

1. **Update production .env:**

    ```env
    FRONTEND_URL=https://www.connectinc.app
    GOOGLE_REDIRECT_URI=https://admin.connectinc.app/api/v1/auth/google/callback
    ```

2. **Google Console should have:**

    ```
    https://admin.connectinc.app/api/v1/auth/google/callback
    ```

3. **Test:**
    - Visit: https://www.connectinc.app
    - Click "Sign in with Google"
    - Should work seamlessly

---

## 🔍 DEBUGGING

### Check Network Tab

1. Open browser DevTools → Network tab
2. Click "Sign in with Google"
3. You should see these redirects:
    ```
    → https://admin.connectinc.app/api/v1/auth/google
    → https://accounts.google.com/o/oauth2/auth?...
    → https://admin.connectinc.app/api/v1/auth/google/callback
    → http://localhost:3000/auth/callback?token=...
    ```

### Common Issues

#### Issue 1: Stuck on JSON page

❌ **Problem:** Browser shows JSON instead of redirecting
✅ **Fix:** Backend updated - clear cache: `php artisan config:clear`

#### Issue 2: Callback route not found

❌ **Problem:** `/auth/callback` shows 404
✅ **Fix:** Create the callback route in your frontend

#### Issue 3: Token not received

❌ **Problem:** `searchParams.get('token')` is null
✅ **Fix:** Check browser URL - token should be in query params

#### Issue 4: CORS error

❌ **Problem:** Frontend can't access token
✅ **Fix:** Not applicable - backend redirects, no CORS needed

#### Issue 5: 405 Method Not Allowed Error

❌ **Problem:** Getting error: `The GET method is not supported for route api/v1/users/3114`
✅ **Fix:** Use correct endpoint `/api/v1/user/{id}` (not `users`)
✅ **Fix:** Make sure to include `Authorization: Bearer {token}` header

**Example:**

```javascript
// ❌ WRONG - uses /users/ (plural) and might be missing auth header
fetch("https://admin.connectinc.app/api/v1/users/3114");

// ✅ CORRECT - uses /user/ (singular) with auth header
fetch("https://admin.connectinc.app/api/v1/user/3114", {
    headers: {
        Authorization: "Bearer YOUR_TOKEN_HERE",
        Accept: "application/json",
    },
});
```

---

## 📝 EXAMPLE RESPONSE DATA

### ✅ New Format (Same as Email/Password Login)

After successful authentication, your callback URL will receive:

```
http://localhost:3000/auth/callback?data=eyJ1c2VyIjp7ImlkIjozMTE0LCJuYW1lIjoiTGF3YWwgVmljdG9yIi...
```

The `data` parameter contains a **base64-encoded JSON** with the same structure as email/password login:

**Decoded data structure:**

```javascript
{
  "message": "Login successful",
  "user": {
    "id": 3114,
    "name": "Lawal Victor",
    "email": "lawalthb@gmail.com",
    "username": "Lawalthb",
    "email_verified_at": "-000001-11-30T00:00:00.000000Z",
    "is_verified": false,
    "bio": "Updated bio description",
    "profile": "1757847916_68c6a16cc7705.jpeg",
    "profile_url": "https://admin.connectinc.app/uploads/profiles/1757847916_68c6a16cc7705.jpeg",
    "country_id": 160,
    "gender": null,
    "phone": "+2348132712715",
    "age": 16,
    "active_subscriptions": [],
    // ... other user fields
  },
  "token": "710|3asR3Hap2iauXDZm10dtLJKhrQMPEYWW04AV55nwa86b9a9b"
}
```

**To decode in your frontend:**

```javascript
const encodedData = searchParams.get("data");
const decodedData = atob(decodeURIComponent(encodedData));
const authData = JSON.parse(decodedData);

// Now you have:
// - authData.token
// - authData.user (full user object with all fields)
// - authData.message
```

**Benefits:**

-   ✅ Same response format as email/password login
-   ✅ No extra API call needed
-   ✅ Complete user data immediately available
-   ✅ Includes active subscriptions
-   ✅ Consistent frontend handling for both login methods

---

## ✅ CHECKLIST

### Backend (Already Done):

-   [x] Updated `handleGoogleCallback` to redirect
-   [x] Added `FRONTEND_URL` to `.env`
-   [x] Cleared config cache

### Frontend (Your Tasks):

-   [ ] Create `/auth/callback` route/page
-   [ ] Parse URL query params (token, user_id, name, email)
-   [ ] Store token securely
-   [ ] Redirect to home/dashboard after storing token
-   [ ] Create `/auth/error` route for error handling
-   [ ] Update "Sign in with Google" button to redirect to backend
-   [ ] Test local flow
-   [ ] Deploy and test production

---

## 🎯 QUICK START COMMANDS

**For Web (Next.js):**

```bash
# Create callback page
mkdir -p pages/auth
touch pages/auth/callback.js
touch pages/auth/error.js

# Or for App Router:
mkdir -p app/auth/callback
touch app/auth/callback/page.js
mkdir -p app/auth/error
touch app/auth/error/page.js
```

**For Mobile (React Native):**

```bash
# Create callback screen
mkdir -p screens/Auth
touch screens/Auth/GoogleAuthCallback.js
touch screens/Auth/AuthError.js

# Install required packages if not already installed
npm install expo-web-browser expo-linking @react-native-async-storage/async-storage
```

---

**Last Updated:** December 13, 2025
