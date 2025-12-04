# Token Persistence Implementation Summary

## Problem

Users were being logged out every day because API tokens expired after 24 hours, requiring frequent re-login on mobile/web apps.

## Solution Implemented

### 1. Extended Token Expiration ✅

-   **Default tokens**: Now last **30 days** (previously 1 day)
-   **"Remember me" tokens**: Now last **365 days** / 1 year (previously 6 months)
-   Configurable via `.env` file

### 2. New API Endpoints ✅

#### `/api/v1/refresh-token` (POST)

-   Allows frontend to extend token without re-login
-   Returns new token with fresh expiration
-   Requires authentication

#### `/api/v1/check-token` (GET)

-   Checks if current token is valid
-   Returns expiration info and warning if expires soon
-   Useful for app initialization

### 3. Configuration Files Updated ✅

**`.env.example`**:

```env
AUTH_TOKEN_EXPIRATION_DAYS=30
AUTH_TOKEN_REMEMBER_EXPIRATION_DAYS=365
```

**`config/auth.php`**:

-   Added token expiration configuration
-   Links to `.env` variables

**`config/sanctum.php`**:

-   Updated comments to explain token persistence
-   Set `expiration` to `null` (uses database `expires_at`)

**`app/Services/AuthService.php`**:

-   Updated `createToken()` method
-   Uses configurable expiration from config

**`routes/api/v1.php`**:

-   Added routes for token refresh and check
-   Protected with `auth:sanctum` middleware

## Files Modified

1. ✅ `app/Services/AuthService.php` - Token creation logic
2. ✅ `config/auth.php` - Added token config
3. ✅ `config/sanctum.php` - Updated comments
4. ✅ `.env.example` - Added token settings
5. ✅ `routes/api/v1.php` - New endpoints
6. ✅ `app/Http/Controllers/API/V1/AuthController.php` - New methods

## What Frontend Needs to Do

### 1. Update Login Flow

```javascript
// Add remember parameter to login
await login({ email, password, remember: true }); // Get 1-year token
```

### 2. Check Token on App Start

```javascript
const token = await getStoredToken();
const response = await fetch("/api/v1/check-token", {
    headers: { Authorization: `Bearer ${token}` },
});

if (!response.ok) {
    // Redirect to login
}
```

### 3. Implement Auto-Refresh

```javascript
// Refresh token when it has < 7 days remaining
if (data.expires_soon) {
    await fetch("/api/v1/refresh-token", { method: "POST" });
}
```

## Environment Variables to Add

Add to your `.env` file:

```env
# Token expiration (in days)
AUTH_TOKEN_EXPIRATION_DAYS=30
AUTH_TOKEN_REMEMBER_EXPIRATION_DAYS=365
```

## Testing

1. **Login and check expiration**:

```bash
# Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password","remember":true}'

# Check token
curl -X GET http://localhost:8000/api/v1/check-token \
  -H "Authorization: Bearer {token}"
```

2. **Verify in database**:

```sql
SELECT name, expires_at, created_at
FROM personal_access_tokens
ORDER BY created_at DESC
LIMIT 5;
```

## Benefits

✅ **Users stay logged in** for 30 days (or 1 year with "remember me")
✅ **Better mobile app experience** - No frequent login prompts
✅ **Token refresh available** - Extend without re-authentication
✅ **Configurable** - Easy to adjust expiration via `.env`
✅ **Backward compatible** - Existing tokens still work
✅ **Secure** - Tokens still expire, just with longer periods

## Next Steps for Frontend Team

1. Test the new endpoints (`/refresh-token` and `/check-token`)
2. Implement token storage (SecureStore for mobile, localStorage for web)
3. Add auto-refresh logic when token expires soon
4. Update login to always send `remember: true` for mobile apps
5. Implement token check on app initialization

## Documentation

Full implementation guide available in: `TOKEN_MANAGEMENT_GUIDE.md`

---

**Note**: Run `php artisan config:clear` after updating `.env` file to apply changes.
