# API Token Management Guide

## Overview

Your backend now supports long-lived authentication tokens for mobile apps and web applications. Users won't need to re-login frequently.

## Configuration Changes

### 1. Token Expiration Settings

Added to `.env` file:

```env
# Authentication Token Configuration
AUTH_TOKEN_EXPIRATION_DAYS=30        # Regular login: 30 days
AUTH_TOKEN_REMEMBER_EXPIRATION_DAYS=365  # Remember me: 1 year
```

### 2. How It Works

#### Default Behavior

-   **Regular Login**: Token expires in **30 days**
-   **Remember Me Login**: Token expires in **365 days** (1 year)
-   **No Expiration Override**: Sanctum uses `expires_at` from database (configured in `config/sanctum.php`)

#### Customization

You can change token expiration by updating your `.env` file:

```env
# Keep users logged in for 90 days
AUTH_TOKEN_EXPIRATION_DAYS=90

# Keep "remember me" users for 2 years
AUTH_TOKEN_REMEMBER_EXPIRATION_DAYS=730
```

## New API Endpoints

### 1. Refresh Token

**Endpoint**: `POST /api/v1/refresh-token`
**Auth**: Required (Bearer token)
**Description**: Extends token expiration without requiring re-login

**Request**:

```http
POST /api/v1/refresh-token
Authorization: Bearer {current_token}
```

**Response**:

```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "new_token_here",
        "token_type": "Bearer",
        "expires_in": 1735819200,
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "john@example.com"
        }
    }
}
```

### 2. Check Token Validity

**Endpoint**: `GET /api/v1/check-token`
**Auth**: Required (Bearer token)
**Description**: Verifies if current token is still valid

**Request**:

```http
GET /api/v1/check-token
Authorization: Bearer {token}
```

**Response**:

```json
{
    "success": true,
    "message": "Token is valid",
    "data": {
        "valid": true,
        "expires_at": "2025-01-03T10:30:00.000000Z",
        "expires_in_days": 25,
        "expires_soon": false,
        "user": {
            "id": 1,
            "username": "johndoe"
        }
    }
}
```

## Frontend Implementation Guide

### For Mobile Apps (React Native / Flutter)

#### 1. Store Token on Login

```javascript
// After successful login
const loginResponse = await fetch("/api/v1/login", {
    method: "POST",
    body: JSON.stringify({ email, password, remember: true }),
});

const { token } = await loginResponse.json();

// Store token securely
await SecureStore.setItemAsync("auth_token", token);
```

#### 2. Check Token on App Start

```javascript
// When app launches
const checkAuth = async () => {
    const token = await SecureStore.getItemAsync("auth_token");

    if (!token) {
        // Redirect to login
        return;
    }

    // Check if token is still valid
    const response = await fetch("/api/v1/check-token", {
        headers: { Authorization: `Bearer ${token}` },
    });

    if (response.ok) {
        const { data } = await response.json();

        // Token is valid
        if (data.expires_soon) {
            // Token expires in less than 7 days, refresh it
            await refreshToken();
        }
    } else {
        // Token invalid, redirect to login
        await SecureStore.deleteItemAsync("auth_token");
        // Navigate to login screen
    }
};
```

#### 3. Automatic Token Refresh

```javascript
// Refresh token before it expires
const refreshToken = async () => {
    const oldToken = await SecureStore.getItemAsync("auth_token");

    const response = await fetch("/api/v1/refresh-token", {
        method: "POST",
        headers: { Authorization: `Bearer ${oldToken}` },
    });

    if (response.ok) {
        const { data } = await response.json();
        await SecureStore.setItemAsync("auth_token", data.token);
        console.log("Token refreshed successfully");
    }
};
```

### For Web Apps (React / Vue / Angular)

#### 1. Store Token in localStorage

```javascript
// After login
localStorage.setItem("auth_token", token);
localStorage.setItem("token_expires_at", expiresAt);
```

#### 2. Check Token on Page Load

```javascript
// App initialization
const initAuth = async () => {
    const token = localStorage.getItem("auth_token");

    if (!token) return;

    try {
        const response = await axios.get("/api/v1/check-token", {
            headers: { Authorization: `Bearer ${token}` },
        });

        if (response.data.data.expires_soon) {
            await refreshToken();
        }
    } catch (error) {
        // Token invalid
        localStorage.removeItem("auth_token");
        // Redirect to login
    }
};
```

#### 3. Axios Interceptor for Auto-Refresh

```javascript
// Add interceptor to handle token refresh
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // If 401 error and haven't retried yet
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            try {
                const refreshResponse = await axios.post(
                    "/api/v1/refresh-token"
                );
                const { token } = refreshResponse.data.data;

                localStorage.setItem("auth_token", token);
                axios.defaults.headers.common[
                    "Authorization"
                ] = `Bearer ${token}`;
                originalRequest.headers["Authorization"] = `Bearer ${token}`;

                return axios(originalRequest);
            } catch (refreshError) {
                // Refresh failed, logout user
                localStorage.removeItem("auth_token");
                window.location.href = "/login";
            }
        }

        return Promise.reject(error);
    }
);
```

## Best Practices

### 1. Refresh Strategy

-   **Option A**: Refresh token when it has < 7 days remaining
-   **Option B**: Refresh token every 7 days proactively
-   **Option C**: Refresh on 401 error (lazy refresh)

### 2. Security Considerations

-   ✅ Use HTTPS in production
-   ✅ Store tokens securely (SecureStore for mobile, httpOnly cookies for web if possible)
-   ✅ Implement token refresh to avoid frequent re-login
-   ✅ Clear tokens on logout
-   ✅ Validate tokens on critical operations

### 3. User Experience

-   Show countdown or notification when token is about to expire
-   Auto-refresh in background
-   Don't interrupt user with login prompts if token can be refreshed

## Testing

### Test Token Expiration

```bash
# Login and get token
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123","remember":true}'

# Check token validity
curl -X GET http://localhost:8000/api/v1/check-token \
  -H "Authorization: Bearer {your_token}"

# Refresh token
curl -X POST http://localhost:8000/api/v1/refresh-token \
  -H "Authorization: Bearer {your_token}"
```

## Migration from Old System

If you have existing users with old tokens:

1. Old tokens will continue to work until they expire
2. Users will get new long-lived tokens on next login
3. Or they can call `/refresh-token` to get a new token immediately

## Troubleshooting

### Users Keep Getting Logged Out

-   Check `.env` file has the new token configuration
-   Run `php artisan config:clear`
-   Verify frontend is storing tokens properly
-   Check token expiration in database: `SELECT expires_at FROM personal_access_tokens`

### Token Refresh Fails

-   Ensure token hasn't already expired
-   Check user still exists and is active
-   Verify Sanctum middleware is applied correctly

## Support

For questions or issues, contact the backend team.
