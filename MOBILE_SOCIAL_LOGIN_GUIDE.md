# Mobile Social Login Integration Guide (Firebase Authentication)

## Overview

This guide explains how to integrate Google Sign-In using Firebase Authentication for mobile apps (iOS/Android/React Native) with the Connect App API.

**Important:** The mobile app uses Firebase Authentication, which handles the OAuth flow on the client side. The backend then verifies the user data and issues API tokens.

## Firebase Project Configuration

**Project ID:** `connect-app-efa83`
**Project Number:** `1075408006474`
**Package Name:** `com.app.connectapp`

### OAuth Client IDs

-   **Android Client ID:** `1075408006474-6kog91c8p5286eph9itajqobe7ur1mtl.apps.googleusercontent.com`
-   **Web Client ID (for ID token verification):** `1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com`

---

## Available Endpoints

### 1. Social Login with Access Token

**Endpoint:** `POST /api/v1/auth/{provider}/token`

**Supported Providers:** `google`, `facebook`, `apple`

**Request Body:**

```json
{
    "access_token": "ya29.a0AfB_byA...", // Token from Google/Facebook SDK
    "device_token": "fcm_device_token" // Optional: for push notifications
}
```

**Response:**

```json
{
    "success": true,
    "message": "Social login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            ...
        },
        "token": "2|abcdef123456...",
        "is_new_user": false
    }
}
```

---

### 2. Social Login with User Data (Recommended for Mobile)

**Endpoint:** `POST /api/v1/auth/{provider}/user-data`

**Supported Providers:** `google`, `facebook`, `apple`

**Request Body:**

```json
{
    "id": "1234567890", // Google/Facebook/Apple User ID
    "email": "john@example.com",
    "name": "John Doe",
    "avatar": "https://example.com/photo.jpg", // Optional
    "device_token": "fcm_device_token", // Optional
    "id_token": "eyJhbGciOiJSUzI1NiIs..." // Optional: Google ID Token for verification
}
```

**Response:**

```json
{
    "success": true,
    "message": "Social login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "profile": "https://storage.example.com/profile.jpg",
            "is_verified": true,
            ...
        },
        "token": "2|abcdef123456...",
        "is_new_user": true
    }
}
```

---

## Integration Steps

### For Android (Google Sign-In)

#### 1. Add Firebase and Google Sign-In to your Android app

```gradle
// build.gradle (project level)
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}

// build.gradle (app level)
apply plugin: 'com.google.gms.google-services' // At the bottom

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-auth'
    implementation 'com.google.android.gms:play-services-auth:20.7.0'
}
```

#### 2. Add google-services.json

-   Download from Firebase Console or use the provided file
-   Place in `app/` directory

#### 3. Configure Google Sign-In

```kotlin
import com.google.android.gms.auth.api.signin.GoogleSignIn
import com.google.android.gms.auth.api.signin.GoogleSignInOptions
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.GoogleAuthProvider

class LoginActivity : AppCompatActivity() {

    private lateinit var auth: FirebaseAuth
    private lateinit var googleSignInClient: GoogleSignInClient

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Initialize Firebase Auth
        auth = FirebaseAuth.getInstance()

        // Configure Google Sign-In with Web Client ID
        val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
            .requestIdToken("1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com")
            .requestEmail()
            .build()

        googleSignInClient = GoogleSignIn.getClient(this, gso)
    }

    private fun signIn() {
        val signInIntent = googleSignInClient.signInIntent
        startActivityForResult(signInIntent, RC_SIGN_IN)
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)

        if (requestCode == RC_SIGN_IN) {
            val task = GoogleSignIn.getSignedInAccountFromIntent(data)
            try {
                val account = task.getResult(ApiException::class.java)
                firebaseAuthWithGoogle(account.idToken!!)
            } catch (e: ApiException) {
                Log.e("GoogleSignIn", "Sign-in failed: ${e.statusCode}")
            }
        }
    }

    private fun firebaseAuthWithGoogle(idToken: String) {
        val credential = GoogleAuthProvider.getCredential(idToken, null)
        auth.signInWithCredential(credential)
            .addOnCompleteListener(this) { task ->
                if (task.isSuccessful) {
                    val user = auth.currentUser
                    sendToBackend(user, idToken)
                } else {
                    Log.e("FirebaseAuth", "Authentication failed", task.exception)
                }
            }
    }

    private fun sendToBackend(user: FirebaseUser?, idToken: String) {
        val client = OkHttpClient()
        val json = JSONObject().apply {
            put("id", user?.uid)
            put("email", user?.email)
            put("name", user?.displayName)
            put("avatar", user?.photoUrl?.toString())
            put("id_token", idToken)
            put("device_token", getDeviceToken())
        }

        val body = RequestBody.create(
            "application/json; charset=utf-8".toMediaType(),
            json.toString()
        )

        val request = Request.Builder()
            .url("https://your-api.com/api/v1/auth/google/user-data")
            .post(body)
            .build()

        client.newCall(request).enqueue(object : Callback {
            override fun onResponse(call: Call, response: Response) {
                val responseData = response.body?.string()
                // Save token and navigate to home
            }

            override fun onFailure(call: Call, e: IOException) {
                // Handle error
            }
        })
    }

    companion object {
        private const val RC_SIGN_IN = 9001
    }
}
```

---

### For iOS (Google Sign-In)

#### 1. Add Google Sign-In SDK

```ruby
# Podfile
pod 'GoogleSignIn'
```

#### 2. Configure Google Sign-In

```swift
import GoogleSignIn

// In AppDelegate or SceneDelegate
let signInConfig = GIDConfiguration(clientID: "YOUR_CLIENT_ID")
GIDSignIn.sharedInstance.configuration = signInConfig
```

#### 3. Implement Sign-In and API Call

```swift
import GoogleSignIn

func signInWithGoogle() {
    guard let presentingViewController = self.view.window?.rootViewController else { return }

    GIDSignIn.sharedInstance.signIn(
        withPresenting: presentingViewController
    ) { signInResult, error in
        guard error == nil else { return }
        guard let user = signInResult?.user else { return }

        // Send to backend
        self.sendToBackend(
            id: user.userID ?? "",
            email: user.profile?.email ?? "",
            name: user.profile?.name ?? "",
            avatar: user.profile?.imageURL(withDimension: 200)?.absoluteString,
            idToken: user.idToken?.tokenString
        )
    }
}

func sendToBackend(id: String, email: String, name: String, avatar: String?, idToken: String?) {
    let url = URL(string: "https://your-api.com/api/v1/auth/google/user-data")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")

    let body: [String: Any] = [
        "id": id,
        "email": email,
        "name": name,
        "avatar": avatar ?? "",
        "id_token": idToken ?? "",
        "device_token": getDeviceToken() // Your FCM token
    ]

    request.httpBody = try? JSONSerialization.data(withJSONObject: body)

    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data, error == nil else { return }

        // Handle response - save token and user data
        if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
            // Process response
        }
    }.resume()
}
```

---

### For React Native

```javascript
// Install Firebase packages
// npm install @react-native-firebase/app @react-native-firebase/auth

import auth from "@react-native-firebase/auth";
import { GoogleSignin } from "@react-native-google-signin/google-signin";
import axios from "axios";

// Configure Google Sign-In with your Web Client ID
GoogleSignin.configure({
    webClientId:
        "1075408006474-ggb4os9qrfjc1dnq8qvp71lri4i7s2df.apps.googleusercontent.com", // From Firebase Console
    offlineAccess: false,
});

// Sign in function
async function signInWithGoogle() {
    try {
        // Check if device supports Google Play Services
        await GoogleSignin.hasPlayServices({
            showPlayServicesUpdateDialog: true,
        });

        // Get user info from Google
        const { idToken, user } = await GoogleSignin.signIn();

        // Create a Google credential with the ID token
        const googleCredential = auth.GoogleAuthProvider.credential(idToken);

        // Sign-in the user with the credential (Firebase Authentication)
        await auth().signInWithCredential(googleCredential);

        // Send user data to your backend
        const response = await axios.post(
            "https://your-api.com/api/v1/auth/google/user-data",
            {
                id: user.id,
                email: user.email,
                name: user.name,
                avatar: user.photo,
                id_token: idToken, // Important: Backend will verify this
                device_token: await getDeviceToken(), // Your FCM token
            }
        );

        // Save token and user data
        const { token, user: userData, is_new_user } = response.data.data;
        await AsyncStorage.setItem("auth_token", token);
        await AsyncStorage.setItem("user", JSON.stringify(userData));

        console.log("Login successful:", { is_new_user });
        return { token, user: userData, is_new_user };
    } catch (error) {
        if (error.code === "auth/account-exists-with-different-credential") {
            console.error("Account exists with different credential");
        } else if (error.code === "auth/network-request-failed") {
            console.error("Network error");
        } else {
            console.error("Google Sign-In Error:", error);
        }
        throw error;
    }
}

// Sign out function
async function signOut() {
    try {
        // Sign out from Google
        await GoogleSignin.signOut();

        // Sign out from Firebase
        await auth().signOut();

        // Clear local storage
        await AsyncStorage.removeItem("auth_token");
        await AsyncStorage.removeItem("user");

        console.log("Signed out successfully");
    } catch (error) {
        console.error("Sign out error:", error);
    }
}

// Check if user is already signed in
async function checkSignInStatus() {
    try {
        const isSignedIn = await GoogleSignin.isSignedIn();
        if (isSignedIn) {
            const currentUser = await GoogleSignin.getCurrentUser();
            return currentUser;
        }
        return null;
    } catch (error) {
        console.error("Error checking sign in status:", error);
        return null;
    }
}
```

**Important Setup Steps for React Native:**

1. **Add google-services.json to your Android project:**

    - Place `google-services.json` in `android/app/` directory

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
apply plugin: 'com.google.gms.google-services' // At the bottom
```

4. **For iOS, add GoogleService-Info.plist:**
    - Download from Firebase Console
    - Add to Xcode project

---

### For Flutter

```yaml
# pubspec.yaml
dependencies:
    google_sign_in: ^6.1.5
    http: ^1.1.0
```

```dart
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

final GoogleSignIn _googleSignIn = GoogleSignIn(
  scopes: ['email', 'profile'],
);

Future<void> signInWithGoogle() async {
  try {
    final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();

    if (googleUser == null) return;

    final GoogleSignInAuthentication googleAuth = await googleUser.authentication;

    // Send to backend
    final response = await http.post(
      Uri.parse('https://your-api.com/api/v1/auth/google/user-data'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'id': googleUser.id,
        'email': googleUser.email,
        'name': googleUser.displayName,
        'avatar': googleUser.photoUrl,
        'id_token': googleAuth.idToken,
        'device_token': await getDeviceToken(), // Your FCM token
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final token = data['data']['token'];
      final user = data['data']['user'];
      final isNewUser = data['data']['is_new_user'];

      // Save token and user data
      // Navigate to appropriate screen
    }
  } catch (error) {
    print('Google Sign-In Error: $error');
  }
}
```

---

## Important Notes

### Security

1. **Always use HTTPS** for API calls
2. **ID Token Verification**: Include `id_token` when available for Google login - backend will verify it
3. **Store tokens securely** using KeyChain (iOS) or KeyStore (Android)
4. Never log or expose tokens in production

### Backend Features

-   ✅ Automatic user creation on first login
-   ✅ Email verification bypass for social logins
-   ✅ Username auto-generation from email
-   ✅ Profile picture download from social provider
-   ✅ Device token storage for push notifications
-   ✅ Welcome email sent to new users
-   ✅ Google ID token verification (when provided)
-   ✅ Returns `is_new_user` flag to handle onboarding

### Error Handling

Common error responses:

```json
{
    "success": false,
    "message": "Social login failed: Invalid provider",
    "errors": null
}
```

Handle these scenarios:

-   Invalid provider
-   Network errors
-   Invalid token
-   Email already exists (handled automatically)
-   Missing required fields

### Testing

Test endpoints:

-   Staging: `https://staging-api.example.com/api/v1/auth/google/user-data`
-   Production: `https://api.example.com/api/v1/auth/google/user-data`

---

## Postman Example

```bash
curl -X POST https://your-api.com/api/v1/auth/google/user-data \
  -H "Content-Type: application/json" \
  -d '{
    "id": "1234567890",
    "email": "user@example.com",
    "name": "Test User",
    "avatar": "https://example.com/photo.jpg",
    "id_token": "eyJhbGciOiJSUzI1NiIs...",
    "device_token": "fcm_token_here"
  }'
```

---

## Need Help?

-   Check logs in backend for detailed error messages
-   Verify Google Console configuration
-   Ensure web client ID matches backend configuration
-   Test with Postman first before mobile integration
