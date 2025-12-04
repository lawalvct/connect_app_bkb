# Quick Start - Frontend Implementation

## 🚀 Immediate Action Required

### Step 1: Update Your `.env` File

Add these lines to your `.env`:

```env
AUTH_TOKEN_EXPIRATION_DAYS=30
AUTH_TOKEN_REMEMBER_EXPIRATION_DAYS=365
```

### Step 2: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## 📱 For Mobile App Developers (React Native / Flutter)

### Modify Login Request

```javascript
// OLD - Token expires in 1 day ❌
await login({ email, password });

// NEW - Token lasts 365 days ✅
await login({ email, password, remember: true });
```

### Add Token Check on App Launch

```javascript
import * as SecureStore from "expo-secure-store";

async function initializeApp() {
    const token = await SecureStore.getItemAsync("auth_token");

    if (!token) {
        navigateToLogin();
        return;
    }

    // Check if token is still valid
    const response = await fetch("https://yourapi.com/api/v1/check-token", {
        headers: { Authorization: `Bearer ${token}` },
    });

    if (response.ok) {
        const { data } = await response.json();
        console.log(`Token valid for ${data.expires_in_days} more days`);

        // Optional: Auto-refresh if expiring soon
        if (data.expires_soon) {
            await refreshToken(token);
        }

        navigateToHome();
    } else {
        navigateToLogin();
    }
}

async function refreshToken(oldToken) {
    const response = await fetch("https://yourapi.com/api/v1/refresh-token", {
        method: "POST",
        headers: { Authorization: `Bearer ${oldToken}` },
    });

    if (response.ok) {
        const { data } = await response.json();
        await SecureStore.setItemAsync("auth_token", data.token);
        console.log("Token refreshed successfully!");
    }
}
```

## 🌐 For Web App Developers (React / Vue / Next.js)

### Update Login Component

```javascript
// Login with remember option
const handleLogin = async (email, password) => {
    const response = await fetch("/api/v1/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            email,
            password,
            remember: true, // ← Add this for 1-year token
        }),
    });

    const { data } = await response.json();
    localStorage.setItem("auth_token", data.token);
};
```

### Add Token Validation on Page Load

```javascript
// In your App.js or main component
useEffect(() => {
    const validateToken = async () => {
        const token = localStorage.getItem("auth_token");
        if (!token) return;

        try {
            const response = await axios.get("/api/v1/check-token", {
                headers: { Authorization: `Bearer ${token}` },
            });

            if (response.data.data.expires_soon) {
                // Auto-refresh token
                const refreshResponse = await axios.post(
                    "/api/v1/refresh-token"
                );
                localStorage.setItem(
                    "auth_token",
                    refreshResponse.data.data.token
                );
            }
        } catch (error) {
            localStorage.removeItem("auth_token");
            window.location.href = "/login";
        }
    };

    validateToken();
}, []);
```

## 🧪 Test It Works

### Test 1: Login and Check Expiration

```bash
# Login (replace with your credentials)
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "remember": true
  }'

# Copy the token from response
export TOKEN="paste_token_here"

# Check token validity
curl -X GET http://localhost:8000/api/v1/check-token \
  -H "Authorization: Bearer $TOKEN"
```

Expected response:

```json
{
    "success": true,
    "message": "Token is valid",
    "data": {
        "valid": true,
        "expires_at": "2025-12-04T10:00:00.000000Z",
        "expires_in_days": 365,
        "expires_soon": false
    }
}
```

### Test 2: Refresh Token

```bash
curl -X POST http://localhost:8000/api/v1/refresh-token \
  -H "Authorization: Bearer $TOKEN"
```

## ✅ Verification Checklist

-   [ ] Added token config to `.env` file
-   [ ] Ran `php artisan config:clear`
-   [ ] Updated login requests to include `remember: true`
-   [ ] Implemented token check on app initialization
-   [ ] Tested login and received token
-   [ ] Verified token expiration is 30+ days
-   [ ] Tested refresh token endpoint
-   [ ] Token persists across app restarts

## 🔧 Common Issues

### Issue: Still getting logged out daily

**Solution**: Make sure you're sending `remember: true` in login request

### Issue: Token refresh returns 401

**Solution**: Current token may have already expired. User needs to re-login.

### Issue: Changes not taking effect

**Solution**: Run `php artisan config:clear` and restart server

## 📊 Database Verification

Check token expiration in database:

```sql
SELECT
  name,
  tokenable_id as user_id,
  expires_at,
  created_at,
  DATEDIFF(expires_at, NOW()) as days_remaining
FROM personal_access_tokens
ORDER BY created_at DESC
LIMIT 10;
```

Should show `days_remaining` as 30 or 365.

## 💡 Best Practice

For mobile apps, **always use `remember: true`** so users stay logged in long-term. Web apps can give users a checkbox to choose.

---

**Questions?** See `TOKEN_MANAGEMENT_GUIDE.md` for detailed documentation.
