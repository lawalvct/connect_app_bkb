# Guest Stream Access - Implementation Summary

## ✅ Completed Implementation

### 1. Database Changes
- **Migration**: `2025_12_08_235108_add_guest_support_to_users_table.php`
- Added `is_guest` boolean column (default: false)
- Added `guest_expires_at` timestamp column (nullable)
- Phone column already nullable in database

### 2. Models Updated
- **User Model** (`app/Models/User.php`)
  - Added `is_guest` and `guest_expires_at` to fillable array
  - Added casts for both new columns
  
- **Stream Model** (`app/Models/Stream.php`)
  - Added `canGuestJoin()` method
  - Added `addGuestViewer()` method
  - Added `hasGuestPaid()` method

### 3. Controller Created
- **GuestStreamController** (`app/Http/Controllers/API/V1/GuestStreamController.php`)
  
  Implemented methods:
  - `register()` - Create or reuse guest user
  - `getLiveStreams()` - Get all live streams with pagination
  - `getStreamDetails()` - Get public stream information
  - `joinStream()` - Join stream with Agora token generation
  - `sendChatMessage()` - Send chat messages as guest
  - `initializeStripePayment()` - Initialize Stripe payment for paid streams
  - `initializeNombaPayment()` - Initialize Nomba payment for paid streams

### 4. Routes Added
- **File**: `routes/api/v1.php`

Public routes:
```
POST /api/v1/guest/register
GET  /api/v1/guest/streams
GET  /api/v1/guest/streams/{streamId}
```

Guest authenticated routes (require guest token):
```
POST /api/v1/guest/streams/{streamId}/join
POST /api/v1/guest/streams/{streamId}/chat
POST /api/v1/guest/streams/{streamId}/payment/stripe
POST /api/v1/guest/streams/{streamId}/payment/nomba
```

## 📋 API Endpoints Documentation

### POST /api/v1/guest/register
Create or reuse a guest user account.

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "force_guest": false
}
```

**Response (Success):**
```json
{
  "success": true,
  "guest_token": "1|abc123...",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "is_guest": true
  }
}
```

**Response (Email exists - registered user):**
```json
{
  "success": false,
  "message": "Email belongs to a registered account. Login recommended. To continue as guest, set force_guest=true.",
  "registered_user": true
}
```

### GET /api/v1/guest/streams
Get all live streams (no authentication required).

**Query Parameters:**
- `per_page` (optional): Number of streams per page (default: 20)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Live Stream Title",
      "description": "Stream description",
      "banner_image_url": "https://...",
      "is_paid": false,
      "price": 0,
      "currency": "USD",
      "free_minutes": 0,
      "status": "live",
      "current_viewers": 150,
      "started_at": "2025-12-08T20:00:00.000000Z"
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 20,
    "current_page": 1,
    "last_page": 2
  }
}
```

### GET /api/v1/guest/streams/{streamId}
Get public stream details (no authentication required).

**Response:**
```json
{
  "success": true,
  "stream": {
    "id": 1,
    "title": "Live Stream Title",
    "description": "Stream description",
    "banner_image_url": "https://...",
    "is_paid": true,
    "price": 5.00,
    "currency": "USD",
    "free_minutes": 5,
    "status": "live",
    "current_viewers": 150
  }
}
```

### POST /api/v1/guest/streams/{streamId}/join
Join a live stream (requires guest authentication).

**Headers:**
```
Authorization: Bearer {guest_token}
```

**Request:**
```json
{
  "guest_token": "1|abc123...",
  "platform": "web"
}
```

**Response:**
```json
{
  "success": true,
  "viewer": {
    "id": 456,
    "stream_id": 1,
    "user_id": 123
  },
  "agora": {
    "channel_name": "stream_1_abc123",
    "token": "agora_token_here",
    "uid": "123"
  }
}
```

### POST /api/v1/guest/streams/{streamId}/chat
Send a chat message (requires guest authentication).

**Headers:**
```
Authorization: Bearer {guest_token}
```

**Request:**
```json
{
  "message": "Hello everyone!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 789,
    "stream_id": 1,
    "user_id": 123,
    "username": "John Doe",
    "message": "Hello everyone!",
    "is_admin": false,
    "created_at": "2025-12-08T23:51:00Z"
  }
}
```

### POST /api/v1/guest/streams/{streamId}/payment/stripe
Initialize Stripe payment for paid stream.

**Headers:**
```
Authorization: Bearer {guest_token}
```

**Request:**
```json
{
  "guest_token": "1|abc123...",
  "payment_currency": "USD",
  "success_url": "https://yourapp.com/success",
  "cancel_url": "https://yourapp.com/cancel"
}
```

### POST /api/v1/guest/streams/{streamId}/payment/nomba
Initialize Nomba payment for paid stream.

**Headers:**
```
Authorization: Bearer {guest_token}
```

**Request:**
```json
{
  "guest_token": "1|abc123...",
  "payment_currency": "NGN"
}
```

## 🔑 Key Features

### Guest User Creation
- Generates unique username: `guest_{timestamp}_{random}`
- Creates random secure password
- Sets `is_guest` = true
- Sets expiration to 30 days from creation
- Issues Laravel Sanctum API token

### Email Handling
- **Existing guest**: Returns existing guest token
- **Existing registered user**: Returns 409 with recommendation to login
- **Force guest mode**: Allows creating guest even if email is registered (when `force_guest=true`)

### Stream Access
- Guests can view public stream details
- Guests can join free streams immediately
- Guests can join paid streams after payment
- Guests can chat using their provided name
- Guests receive Agora tokens for video streaming

### Payment Integration
- Reuses existing StreamPaymentController logic
- Supports both Stripe and Nomba payment gateways
- Payment records linked to guest user_id
- Can be upgraded to full account later

## 🔄 Future Enhancements (Not Yet Implemented)

### Guest to Registered User Upgrade
When a guest later registers with the same email:
1. Verify email and complete registration
2. Set `is_guest` = false
3. Preserve all guest data:
   - Chat history (same user_id)
   - Viewer records (same user_id)
   - Payment records (same user_id)
4. Update registration_step to track progress

### Cleanup Policy
- Add scheduled job to delete expired guest users
- Check `guest_expires_at` timestamp
- Remove guests who haven't upgraded after 30 days

### Rate Limiting for Guests
- Implement stricter rate limits for guest users
- Example: 1 chat message per 5 seconds
- Prevent spam from temporary accounts

## 🧪 Testing Checklist

- [ ] Test guest registration with new email
- [ ] Test guest registration with existing guest email
- [ ] Test guest registration with registered user email
- [ ] Test force_guest parameter
- [ ] Test getting all live streams (public)
- [ ] Test pagination for live streams
- [ ] Test getting stream details (public)
- [ ] Test joining free stream as guest
- [ ] Test joining paid stream without payment (should fail)
- [ ] Test sending chat message as guest
- [ ] Test guest name appears in chat
- [ ] Test Stripe payment initialization
- [ ] Test Nomba payment initialization
- [ ] Test Agora token generation for guests
- [ ] Test guest token expiration (30 days)
- [ ] Test multiple guests in same stream

## 📝 Notes

- Guest users use Laravel Sanctum for authentication
- Guest tokens expire after 7 days (Sanctum default, can be configured)
- Guest accounts expire after 30 days (stored in `guest_expires_at`)
- Chat messages display guest's provided name (not username)
- All existing stream logic (payments, viewers, chat) works with guests
- No changes needed to StreamChat, StreamViewer, or StreamPayment models

## 🚀 Deployment Steps

1. Run migration: `php artisan migrate`
2. Clear config cache: `php artisan config:clear`
3. Clear route cache: `php artisan route:clear`
4. Test all endpoints with Postman/API client
5. Update frontend to use new guest endpoints
6. Monitor guest user creation and cleanup

## 📚 Related Files

- Migration: `database/migrations/2025_12_08_235108_add_guest_support_to_users_table.php`
- Controller: `app/Http/Controllers/API/V1/GuestStreamController.php`
- Routes: `routes/api/v1.php`
- Models: `app/Models/User.php`, `app/Models/Stream.php`
- Documentation: `GUEST_STREAM_ACCESS.md` (original plan)
