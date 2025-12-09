# Guest Stream Access - Postman Collection Guide

## Base URL
```
http://localhost/api/v1
```

## ⚠️ IMPORTANT: Route Structure
All guest routes start with `/api/v1/guest/` (NOT `/api/v1/streams/guest/`)

Correct: `{{host}}/api/v1/guest/streams/214`  
Wrong: `{{host}}/api/v1/streams/guest/214`

## Collection: Guest Stream Access

### 1. Register Guest User

**Endpoint:** `POST /guest/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
  "name": "Guest User",
  "email": "guest@example.com"
}
```

**Expected Response (201):**
```json
{
  "success": true,
  "guest_token": "1|abc123def456...",
  "user": {
    "id": 123,
    "name": "Guest User",
    "email": "guest@example.com",
    "is_guest": true
  }
}
```

**Save the `guest_token` for subsequent requests!**

---

### 2. Get All Live Streams (Public)

**Endpoint:** `GET /guest/streams`

**Full URL Example:** `{{host}}/api/v1/guest/streams`

**Headers:**
```
Accept: application/json
```

**Query Parameters (Optional):**
- `per_page` - Number of streams per page (default: 20)
- `page` - Page number (default: 1)

**Example:** `GET /guest/streams?per_page=10&page=1`

**Expected Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Live Gaming Stream",
      "description": "Playing the latest games",
      "banner_image_url": "https://example.com/banner1.jpg",
      "is_paid": false,
      "price": 0,
      "currency": "USD",
      "free_minutes": 0,
      "status": "live",
      "current_viewers": 150,
      "started_at": "2025-12-08T20:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Cooking Show",
      "description": "Making delicious meals",
      "banner_image_url": "https://example.com/banner2.jpg",
      "is_paid": true,
      "price": 5.00,
      "currency": "USD",
      "free_minutes": 5,
      "status": "live",
      "current_viewers": 85,
      "started_at": "2025-12-08T19:30:00.000000Z"
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

---

### 3. Get Stream Details (Public)

**Endpoint:** `GET /guest/streams/{streamId}`

**Full URL Example:** `{{host}}/api/v1/guest/streams/214`

**Headers:**
```
Accept: application/json
```

**Expected Response (200):**
```json
{
  "success": true,
  "stream": {
    "id": 1,
    "title": "My Live Stream",
    "description": "Stream description here",
    "banner_image_url": "https://example.com/banner.jpg",
    "is_paid": false,
    "price": 0,
    "currency": "USD",
    "free_minutes": 0,
    "status": "live",
    "current_viewers": 42
  }
}
```

---

### 4. Join Stream

**Endpoint:** `POST /guest/streams/{streamId}/join`

**Full URL Example:** `{{host}}/api/v1/guest/streams/214/join`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {guest_token}
```

**Body (JSON):**
```json
{
  "guest_token": "1|abc123def456...",
  "platform": "web"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "viewer": {
    "id": 456,
    "stream_id": 1,
    "user_id": 123
  },
  "agora": {
    "channel_name": "stream_1_xyz789",
    "token": "006abc123...",
    "uid": "123"
  }
}
```

---

### 5. Send Chat Message

**Endpoint:** `POST /guest/streams/{streamId}/chat`

**Full URL Example:** `{{host}}/api/v1/guest/streams/214/chat`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {guest_token}
```

**Body (JSON):**
```json
{
  "message": "Hello from guest user!"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 789,
    "stream_id": 1,
    "user_id": 123,
    "username": "Guest User",
    "message": "Hello from guest user!",
    "is_admin": false,
    "created_at": "2025-12-08T23:51:00.000000Z"
  }
}
```

---

### 6. Initialize Stripe Payment

**Endpoint:** `POST /guest/streams/{streamId}/payment/stripe`

**Full URL Example:** `{{host}}/api/v1/guest/streams/214/payment/stripe`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {guest_token}
```

**Body (JSON):**
```json
{
  "guest_token": "1|abc123def456...",
  "payment_currency": "USD",
  "success_url": "http://localhost:3000/payment/success",
  "cancel_url": "http://localhost:3000/payment/cancel"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "checkout_url": "https://checkout.stripe.com/...",
  "reference": "STREAM_ABC123"
}
```

---

### 7. Initialize Nomba Payment

**Endpoint:** `POST /guest/streams/{streamId}/payment/nomba`

**Full URL Example:** `{{host}}/api/v1/guest/streams/214/payment/nomba`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {guest_token}
```

**Body (JSON):**
```json
{
  "guest_token": "1|abc123def456...",
  "payment_currency": "NGN"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "checkout_url": "https://nomba.checkout/...",
  "reference": "STREAM_XYZ789"
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Stream is not live"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Stream not found"
}
```

### 402 Payment Required
```json
{
  "success": false,
  "message": "Payment required to join this stream"
}
```

### 409 Conflict (Email exists)
```json
{
  "success": false,
  "message": "Email belongs to a registered account. Login recommended. To continue as guest, set force_guest=true.",
  "registered_user": true
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  }
}
```

---

## Testing Flow

### Scenario 1: Free Stream Access
1. Get all live streams → Browse available streams
2. Register guest user → Save token
3. Get stream details → Verify stream is free
4. Join stream → Get Agora credentials
5. Send chat message → Verify message appears

### Scenario 2: Paid Stream Access
1. Get all live streams → Browse available streams
2. Register guest user → Save token
3. Get stream details → Verify stream is paid
4. Try to join stream → Should fail with 402
5. Initialize payment (Stripe/Nomba) → Get checkout URL
6. Complete payment (external)
7. Join stream → Should succeed
8. Send chat message → Verify message appears

### Scenario 3: Existing Email
1. Register with existing guest email → Should return existing token
2. Register with registered user email → Should return 409
3. Register with force_guest=true → Should create/return guest

---

## Environment Variables Required

Make sure these are set in your `.env` file:

```env
# Agora Configuration
AGORA_APP_ID=your_agora_app_id
AGORA_APP_CERTIFICATE=your_agora_certificate

# Stripe Configuration
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# Nomba Configuration
NOMBA_API_KEY=your_nomba_key
NOMBA_SECRET=your_nomba_secret
```

---

## Notes

- Guest tokens are valid for 7 days (Sanctum default)
- Guest accounts expire after 30 days
- Use the same token for all authenticated requests
- Chat messages will display the guest's provided name
- Payment records are linked to the guest user_id
- Guests can later upgrade to full accounts (preserving history)
