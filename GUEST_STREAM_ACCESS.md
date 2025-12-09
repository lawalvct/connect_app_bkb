# Guest (Unregistered) Stream Access — Implementation Plan

A compact plan for allowing unregistered users to join live streams by providing email + name. This follows _Option C: Lightweight Guest User Approach_, checks whether email exists (and allows continuation with guidance), and shows the guest's name in chat.

---

## Summary ✅

-   **Approach**: Option C — create a minimal guest user record that can later be upgraded to a permanent account.
-   **Email behaviour**: Check if email exists. If it does:
    -   If it's a **guest** user → reuse existing guest record (return token).
    -   If it's a **registered** and active user → show a **soft warning** and allow continuation (guest flow) with a recommendation to login — include a `force_guest` option so the frontend can decide.
-   **Chat display**: Guests' submitted name will be used and displayed in stream chat messages.

---

## Database Changes 🔧

### Migration Tasks

-   Add `is_guest` boolean to `users` table (default `false`).
-   Make `phone` nullable (guests may not provide phone).
-   Optional: add `guest_expires_at` (datetime) for cleanup policies.

### Suggested Defaults

-   `is_guest`: boolean, default false
-   `guest_expires_at`: nullable datetime
-   `phone`: nullable

**Migration File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_guest_support_to_users_table.php`

---

## Core API Endpoints (Public / No Auth) 🔒

### POST `/api/v1/guest/register`

**Purpose**: Create or reuse a guest user. Performs email existence check.

**Request Payload**:

```json
{
    "name": "string (required)",
    "email": "string (required)",
    "force_guest": "boolean (optional)"
}
```

**Responses**:

-   **200 OK** — Returns guest token + user details

    ```json
    {
        "success": true,
        "guest_token": "abc123...",
        "user": {
            "id": 555,
            "name": "Ada James",
            "email": "ada@example.com",
            "is_guest": true
        }
    }
    ```

-   **200 (Exists)** — Returns existing guest token if guest already exists

-   **409 (Advice)** — Email belongs to registered user
    ```json
    {
        "success": false,
        "message": "Email belongs to a registered account. Login recommended. To continue as guest, set force_guest=true.",
        "registered_user": true
    }
    ```

---

### GET `/api/v1/guest/streams/{streamId}`

**Purpose**: Get public stream details (title, banner, price, free_minutes, is_paid etc.)

**Response**:

```json
{
    "success": true,
    "stream": {
        "id": 123,
        "title": "Live Stream Title",
        "description": "...",
        "banner_image_url": "...",
        "is_paid": true,
        "price": 5.0,
        "currency": "USD",
        "free_minutes": 5,
        "status": "live",
        "current_viewers": 150
    }
}
```

---

### POST `/api/v1/guest/streams/{streamId}/join`

**Purpose**: Join the stream (generate Agora token, create StreamViewer linking guest user)

**Request Payload**:

```json
{
    "guest_token": "string (required for guests)",
    "platform": "web|ios|android (optional)"
}
```

**Response**:

```json
{
    "success": true,
    "viewer": {
        "id": 789,
        "stream_id": 123,
        "user_id": 555
    },
    "agora": {
        "channel_name": "stream_123_abc",
        "token": "agora_token_here",
        "uid": "123456"
    }
}
```

**Error Responses**:

-   **402 Payment Required** — Stream is paid and user hasn't paid
-   **403 Forbidden** — Stream not live or max viewers reached

---

### POST `/api/v1/guest/streams/{streamId}/payment/stripe`

**Purpose**: Initialize Stripe payment flow for guest

**Request Payload**:

```json
{
    "guest_token": "string (required)",
    "payment_currency": "USD|NGN",
    "success_url": "https://...",
    "cancel_url": "https://..."
}
```

**Response**:

```json
{
    "success": true,
    "checkout_url": "https://checkout.stripe.com/...",
    "reference": "STREAM_ABC123"
}
```

---

### POST `/api/v1/guest/streams/{streamId}/payment/nomba`

**Purpose**: Initialize Nomba payment flow for guest

**Request Payload**:

```json
{
    "guest_token": "string (required)",
    "payment_currency": "USD|NGN"
}
```

**Response**:

```json
{
    "success": true,
    "checkout_url": "https://nomba.checkout/...",
    "reference": "STREAM_XYZ789"
}
```

---

### POST `/api/v1/guest/streams/{streamId}/chat`

**Purpose**: Send chat message as a guest

**Request Headers**:

```
Authorization: Bearer {guest_token}
```

**Request Payload**:

```json
{
    "message": "string (required, max 500 chars)"
}
```

**Response**:

```json
{
    "success": true,
    "message": "Message sent successfully",
    "data": {
        "id": 456,
        "stream_id": 123,
        "user_id": 555,
        "username": "Ada James",
        "message": "Hi everyone!",
        "is_admin": false,
        "created_at": "2025-12-09T10:30:00Z"
    }
}
```

---

## Controller Responsibilities (GuestStreamController) 🧭

### `register()`

**Flow**:

1. Validate `name` + `email`
2. Check email existence:
    - **If exists and is guest** → Return existing guest token
    - **If exists and is registered**:
        - Return 409 with message: "Email matches a registered account. Recommend login. To continue as guest, set force_guest=true."
        - If `force_guest=true`, allow creating or reusing a separate guest identity
    - **If not exists** → Create guest user:
        - `name` → user.name (used in chat)
        - `email` → user.email
        - `username` → generated unique (e.g., `guest_{id}_{rand}`)
        - `password` → random hashed password
        - `is_guest` → true
        - `registration_step` → 0
        - `guest_expires_at` → now + 30 days
3. Issue guest token (API token or JWT)
4. Return user details + token

---

### `getStreamDetails()`

**Flow**:

1. Find stream by ID
2. Return public stream information (title, banner, price, free_minutes, status, etc.)
3. No authentication required

---

### `joinStream()`

**Flow**:

1. Validate guest token + streamId
2. Retrieve guest user from token
3. Check stream status (must be 'live')
4. Invoke `Stream::canUserJoin()` logic (adapted for guest users)
5. Generate Agora token for guest user
6. Create `StreamViewer` record with `user_id` pointing to guest
7. Return viewer record + Agora credentials

---

### `initializeStripePayment()` / `initializeNombaPayment()`

**Flow**:

1. Validate guest token
2. Retrieve guest user
3. Check stream is paid
4. Create `StreamPayment` record with guest's `user_id`
5. Initialize payment gateway (reuse existing `StreamPaymentController` logic)
6. Return checkout URL + reference

---

### `sendChatMessage()`

**Flow**:

1. Validate guest token
2. Retrieve guest user
3. Check stream is live
4. Check if user has paid (for paid streams)
5. Apply rate limiting (1 message per 5 seconds for guests)
6. Create `StreamChat` record:
    - `user_id` → guest user ID
    - `username` → guest.name
    - `message` → validated message
    - `is_admin` → false
7. Return chat message

---

### `upgradeFlow()` (AuthController)

**Flow**:
When guest later signs up or logs in:

1. Verify email and provide full data
2. Merge guest record into real account:
    - Set `is_guest` → false
    - Preserve chat history, viewer records, and payments by keeping same `user_id`
    - Update `registration_step` to track progress
    - Set `phone` if provided
3. Or optionally create fresh user and link guest records

---

## Model Updates ✨

### Stream Model

Add the following methods to `app/Models/Stream.php`:

```php
/**
 * Check if a guest user can join the stream
 */
public function canGuestJoin(User $guest): bool
{
    // Use existing canUserJoin logic
    return $this->canUserJoin($guest);
}

/**
 * Add a guest viewer to the stream
 */
public function addGuestViewer(User $guest, string $agoraUid = null, string $agoraToken = null): StreamViewer
{
    // Use existing addViewer logic
    return $this->addViewer($guest, $agoraUid, $agoraToken);
}

/**
 * Check if guest has paid for the stream
 */
public function hasGuestPaid(User $guest): bool
{
    // Use existing hasUserPaid logic
    return $this->hasUserPaid($guest);
}
```

---

### StreamChat Model

**Already supports guest users**:

-   Accepts `username` field (will use guest's `name`)
-   Accepts `user_profile_url` (can be null for guests)
-   `user_id` can reference guest user records

No changes needed — just ensure guest-created messages populate `username` from guest `name`.

---

### StreamViewer Model

**Already supports guest users**:

-   `user_id` can reference guest user records
-   No changes needed

---

### StreamPayment Model

**Already supports guest users**:

-   `user_id` can reference guest user records
-   No changes needed

---

## Authentication / Tokens 🔑

### Guest Token Approach

**Recommended**: Use Laravel Sanctum API tokens

-   Create token on guest registration
-   Token expiry: 7 days (renewable on activity)
-   Store token in frontend (localStorage/sessionStorage)
-   Send token in `Authorization: Bearer {token}` header

**Alternative**: JWT tokens with 7-day expiration

### Token Generation

```php
// In GuestStreamController::register()
$token = $guest->createToken('guest-access', ['guest'], now()->addDays(7))->plainTextToken;
```

### Token Validation

```php
// Middleware or controller method
$guest = auth()->guard('sanctum')->user();
if (!$guest || !$guest->is_guest) {
    return response()->json(['error' => 'Invalid guest token'], 401);
}
```

---

## UI & UX / Edge Cases 💡

### UX Prompt (Email Exists)

If email exists and is a registered account:

**Frontend displays**:

> "That email appears to be registered. Would you like to log in or continue as guest?"

**Options**:

-   **Login** → Redirect to login page
-   **Continue as Guest** → Send `force_guest=true` to `/guest/register`

---

### Chat Display

-   Use guest `name` for message display
-   Optionally show small badge "Guest" next to the name
-   Different text color or icon to distinguish guests from registered users

**Example UI**:

```
[Guest] Ada James: Hi everyone! 👋
```

---

### Rate Limiting

**Recommended limits**:

-   Guests: 1 message per 5 seconds
-   Registered users: 1 message per 2 seconds
-   Stream creators: No limit

**Implementation**: Use Laravel rate limiting middleware

---

### Data Retention

**Cleanup Policy**:

-   Automatically delete guest accounts that haven't been upgraded after 30 days
-   Use `guest_expires_at` field to track expiration
-   Create scheduled job: `php artisan make:command CleanupExpiredGuests`

**Cleanup Logic**:

```php
// Delete guests older than 30 days who never upgraded
User::where('is_guest', true)
    ->where('guest_expires_at', '<', now())
    ->delete();
```

**Optional**: Anonymize instead of delete to preserve analytics

---

## Security & Privacy ⚠️

### Email Ownership

-   If a guest uses an email already owned by a registered user, perform **no** account takeover
-   The guest identity should be separate unless the user upgrades and proves ownership
-   Email verification required for upgrade to prevent abuse

### Token Security

-   Send no sensitive tokens in URLs (use POST body or headers only)
-   Tokens should be short-lived (7 days) with renewal on activity
-   Invalidate all guest tokens on upgrade to full account

### Payment Security

-   Guest payments use same security measures as registered users
-   Payment records linked to guest `user_id` for accountability
-   Prevent duplicate payments for same stream

### Privacy

-   Guests can only see public stream information
-   No access to user profiles, private streams, or sensitive data
-   Chat messages are public but can be moderated

---

## Examples — Payloads & Responses 📦

### Register Guest (New)

**Request**:

```http
POST /api/v1/guest/register
Content-Type: application/json

{
  "name": "Ada James",
  "email": "ada@example.com"
}
```

**Response**:

```json
{
    "success": true,
    "guest_token": "abc123xyz789...",
    "user": {
        "id": 555,
        "name": "Ada James",
        "email": "ada@example.com",
        "is_guest": true,
        "guest_expires_at": "2026-01-08T10:30:00Z"
    }
}
```

---

### Register Guest (Email Belongs to Registered User)

**Request**:

```http
POST /api/v1/guest/register
Content-Type: application/json

{
  "name": "Ada James",
  "email": "existing@user.com"
}
```

**Response**:

```json
{
    "success": false,
    "message": "Email belongs to a registered account. Login recommended. To continue as guest, set force_guest=true.",
    "registered_user": true
}
```

---

### Continue as Guest (Force)

**Request**:

```http
POST /api/v1/guest/register
Content-Type: application/json

{
  "name": "Ada James",
  "email": "existing@user.com",
  "force_guest": true
}
```

**Response**:

```json
{
    "success": true,
    "guest_token": "def456uvw123...",
    "user": {
        "id": 556,
        "name": "Ada James",
        "email": "existing@user.com",
        "username": "guest_556_a7b2c",
        "is_guest": true
    },
    "note": "Created as guest. Recommend login to access full features."
}
```

---

### Join Stream

**Request**:

```http
POST /api/v1/guest/streams/123/join
Authorization: Bearer abc123xyz789...
Content-Type: application/json

{
  "platform": "web"
}
```

**Response**:

```json
{
    "success": true,
    "viewer": {
        "id": 789,
        "stream_id": 123,
        "user_id": 555,
        "is_active": true,
        "joined_at": "2025-12-09T10:30:00Z"
    },
    "agora": {
        "channel_name": "stream_123_abc8d2f",
        "token": "006abc123...",
        "uid": "555000"
    }
}
```

---

### Send Chat (Guest)

**Request**:

```http
POST /api/v1/guest/streams/123/chat
Authorization: Bearer abc123xyz789...
Content-Type: application/json

{
  "message": "Hi everyone! 👋"
}
```

**Response**:

```json
{
    "success": true,
    "message": "Message sent successfully",
    "data": {
        "id": 456,
        "stream_id": 123,
        "user_id": 555,
        "username": "Ada James",
        "message": "Hi everyone! 👋",
        "user_profile_url": null,
        "is_admin": false,
        "created_at": "2025-12-09T10:30:15Z"
    }
}
```

---

## Implementation Milestones (High-Level) 🛠️

### Phase 1: Database & Models

-   [ ] Create migration: `is_guest`, nullable `phone`, `guest_expires_at`
-   [ ] Run migration
-   [ ] Update User model fillable fields
-   [ ] Add guest-related methods to Stream model

### Phase 2: Guest Controller & Routes

-   [ ] Create `GuestStreamController`
-   [ ] Implement `register()` with email checking
-   [ ] Implement `getStreamDetails()`
-   [ ] Implement `joinStream()` with Agora token generation
-   [ ] Implement `sendChatMessage()` with rate limiting
-   [ ] Add routes to `routes/api/v1.php` (public, no auth)

### Phase 3: Payment Integration

-   [ ] Implement `initializeStripePayment()` for guests
-   [ ] Implement `initializeNombaPayment()` for guests
-   [ ] Test payment callbacks with guest user_id
-   [ ] Ensure currency conversion works for guests

### Phase 4: Authentication & Upgrade

-   [ ] Implement guest token generation (Sanctum)
-   [ ] Add token validation middleware
-   [ ] Update `AuthController` for guest upgrade flow
-   [ ] Test guest → full user conversion

### Phase 5: Chat & Viewers

-   [ ] Test guest chat messages display with name
-   [ ] Test guest viewers in live stream
-   [ ] Add guest badge/indicator in UI (frontend)
-   [ ] Implement chat rate limiting for guests

### Phase 6: Cleanup & Security

-   [ ] Create `CleanupExpiredGuests` command
-   [ ] Schedule cleanup job in `app/Console/Kernel.php`
-   [ ] Add guest access logging
-   [ ] Security audit: token handling, payment flows

### Phase 7: QA & Testing

-   [ ] Unit tests: guest registration, email checks
-   [ ] Integration tests: join stream, payment, chat
-   [ ] E2E tests: guest flow from registration to upgrade
-   [ ] Load testing: guest chat rate limits
-   [ ] Security testing: token validation, payment security

---

## Routes Summary 📋

**File**: `routes/api/v1.php`

```php
// Guest Stream Access (Public - No Auth)
Route::prefix('guest')->group(function () {
    Route::post('/register', [GuestStreamController::class, 'register']);
    Route::get('/streams/{stream}', [GuestStreamController::class, 'getStreamDetails']);

    // Protected by guest token validation
    Route::middleware(['auth:sanctum', 'guest.user'])->group(function () {
        Route::post('/streams/{stream}/join', [GuestStreamController::class, 'joinStream']);
        Route::post('/streams/{stream}/chat', [GuestStreamController::class, 'sendChatMessage']);
        Route::post('/streams/{stream}/payment/stripe', [GuestStreamController::class, 'initializeStripePayment']);
        Route::post('/streams/{stream}/payment/nomba', [GuestStreamController::class, 'initializeNombaPayment']);
    });
});
```

---

## Scheduled Tasks 📅

**File**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup expired guest users (daily at 2 AM)
    $schedule->command('guests:cleanup')->dailyAt('02:00');
}
```

---

## Testing Checklist ✅

### Unit Tests

-   [ ] Guest user creation with unique username generation
-   [ ] Email existence check (registered vs guest vs new)
-   [ ] Token generation and validation
-   [ ] Guest expiration date calculation

### Integration Tests

-   [ ] Guest registration → token issued
-   [ ] Guest join stream → viewer created + Agora token
-   [ ] Guest send chat → message saved with name
-   [ ] Guest payment → payment record created
-   [ ] Guest upgrade → is_guest set to false

### E2E Tests

-   [ ] Full guest flow: register → view stream details → join → chat → pay
-   [ ] Guest upgrade flow: guest → login → verify → full user
-   [ ] Expired guest cleanup
-   [ ] Rate limiting enforcement

---

## Notes & Considerations 📝

1. **Username Generation**: Ensure generated guest usernames are unique (e.g., `guest_{user_id}_{random}`)

2. **Email Uniqueness**: With `force_guest=true`, you may have duplicate emails (one registered, one guest). Consider:

    - Adding unique constraint on `email + is_guest` combination
    - Or using a separate `guest_email` field

3. **Agora UID**: Ensure guest users get unique Agora UIDs (can reuse existing generation logic)

4. **Analytics**: Track guest vs registered user metrics for business insights

5. **Upgrade Incentives**: Consider offering benefits for upgrading (e.g., "Upgrade to send unlimited messages")

6. **Mobile Apps**: Ensure guest flow works on iOS/Android apps with token storage

7. **Web Sockets**: If using Pusher/WebSockets for real-time chat, ensure guest tokens are validated

---

## Future Enhancements 🚀

-   **Social Login for Guests**: Allow guests to upgrade via Google/Facebook
-   **Guest Watchlist**: Let guests save favorite streams (stored locally or with token)
-   **Guest Referrals**: Track which guests came from shared links
-   **Anonymous Mode**: Option for fully anonymous viewing (no email required) with limitations
-   **Guest Analytics Dashboard**: Admin view of guest conversion rates
-   **Auto-Upgrade Prompts**: Smart prompts encouraging guests to register based on behavior

---

## Support & Documentation 📚

-   **Backend**: Laravel 10.x, Sanctum authentication
-   **Frontend**: React Native (mobile), Web (HTML/JS)
-   **Streaming**: Agora RTC
-   **Payments**: Stripe, Nomba
-   **Database**: MySQL

For questions or issues, refer to:

-   Laravel Sanctum docs: https://laravel.com/docs/10.x/sanctum
-   Agora RTC docs: https://docs.agora.io/
-   Stripe API: https://stripe.com/docs/api
-   Nomba API: (internal documentation)

---

**Document Version**: 1.0
**Last Updated**: December 9, 2025
**Status**: Ready for Implementation
