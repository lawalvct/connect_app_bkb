# Live Streaming API Documentation

## Overview
This API provides comprehensive live streaming functionality for the Connect App, including stream management, viewer participation, live chat, and payment processing through Stripe and Nomba gateways.

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication
Most endpoints require Bearer token authentication using Laravel Sanctum:
```
Authorization: Bearer {your-token}
```

---

## Stream Management Endpoints

### 1. Create/Schedule Stream (Admin Only)
**POST** `/streams`

Creates a new stream. Only users with admin role can create streams.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "title": "My Live Stream",
    "description": "Stream description",
    "banner_image": "file", // Optional image file
    "scheduled_at": "2025-07-25T15:00:00Z", // Optional
    "is_paid": true, // Optional, default: false
    "price": 10.00, // Required if is_paid is true
    "currency": "USD", // Optional, default: USD
    "max_viewers": 100 // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Stream created successfully",
    "data": {
        "stream": {
            "id": 1,
            "title": "My Live Stream",
            "description": "Stream description",
            "banner_image_url": "https://domain.com/storage/stream-banners/image.jpg",
            "status": "upcoming",
            "is_live": false,
            "is_paid": true,
            "price": "10.00",
            "currency": "USD",
            "max_viewers": 100,
            "current_viewers": 0,
            "duration": null,
            "scheduled_at": "2025-07-25T15:00:00Z",
            "started_at": null,
            "ended_at": null,
            "created_at": "2025-07-24T20:30:00Z",
            "updated_at": "2025-07-24T20:30:00Z",
            "streamer": {
                "id": 1,
                "username": "admin_user",
                "name": "Admin User",
                "profile_picture": "https://domain.com/profile.jpg"
            }
        }
    }
}
```

### 2. Start Stream (Admin Only)
**POST** `/streams/{id}/start`

Starts a scheduled stream. Only stream owner can start their stream.

**Response:**
```json
{
    "success": true,
    "message": "Stream started successfully",
    "data": {
        "stream": {
            "id": 1,
            "status": "live",
            "is_live": true,
            "started_at": "2025-07-24T20:45:00Z"
        },
        "agora_config": {
            "app_id": "your-agora-app-id",
            "channel_name": "stream_1_abc123",
            "agora_uid": "123456",
            "token": "agora-rtc-token",
            "role": "publisher"
        }
    }
}
```

### 3. End Stream (Admin Only)
**POST** `/streams/{id}/end`

Ends a live stream. Only stream owner can end their stream.

**Response:**
```json
{
    "success": true,
    "message": "Stream ended successfully",
    "data": {
        "stream": {
            "id": 1,
            "status": "ended",
            "is_live": false,
            "ended_at": "2025-07-24T21:45:00Z",
            "current_viewers": 0
        }
    }
}
```

### 4. Update Stream (Admin Only)
**PUT** `/streams/{id}`

Updates stream details. Only available for upcoming streams.

**Request Body:** Same as create stream

### 5. Delete Stream (Admin Only)
**DELETE** `/streams/{id}`

Deletes a stream. Cannot delete live streams.

### 6. Get My Streams (Admin Only)
**GET** `/streams/my-streams`

Returns all streams created by the authenticated admin user.

---

## Stream Discovery Endpoints

### 7. Get Latest Live Streams
**GET** `/streams/latest?limit=10`

Returns currently live streams.

**Response:**
```json
{
    "success": true,
    "message": "Latest live streams retrieved successfully",
    "data": {
        "streams": [
            {
                "id": 1,
                "title": "Live Gaming Session",
                "status": "live",
                "is_live": true,
                "current_viewers": 45,
                "streamer": {
                    "id": 1,
                    "username": "streamer_user",
                    "name": "Streamer User"
                }
            }
        ]
    }
}
```

### 8. Get Upcoming Streams
**GET** `/streams/upcoming?limit=10`

Returns scheduled upcoming streams.

### 9. Get Stream Details
**GET** `/streams/{id}`

Returns detailed information about a specific stream.

### 10. Check Stream Status
**GET** `/streams/{id}/status`

Returns current status of a stream.

**Response:**
```json
{
    "success": true,
    "message": "Stream status retrieved successfully",
    "data": {
        "stream_id": 1,
        "status": "live",
        "is_live": true,
        "current_viewers": 45,
        "started_at": "2025-07-24T20:45:00Z",
        "ended_at": null
    }
}
```

---

## Stream Participation Endpoints

### 11. Join Stream
**POST** `/streams/{id}/join`

Allows a user to join a live stream.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Joined stream successfully",
    "data": {
        "stream": {
            "id": 1,
            "title": "Live Stream"
        },
        "agora_config": {
            "app_id": "your-agora-app-id",
            "channel_name": "stream_1_abc123",
            "agora_uid": "789012",
            "token": "agora-rtc-token",
            "role": "subscriber"
        },
        "viewer": {
            "id": 123,
            "joined_at": "2025-07-24T20:50:00Z"
        }
    }
}
```

### 12. Leave Stream
**POST** `/streams/{id}/leave`

Allows a user to leave a stream.

### 13. Get Stream Viewers
**GET** `/streams/{id}/viewers`

Returns list of active viewers for a stream.

**Response:**
```json
{
    "success": true,
    "message": "Stream viewers retrieved successfully",
    "data": {
        "stream_id": 1,
        "total_viewers": 45,
        "viewers": [
            {
                "id": 123,
                "user": {
                    "id": 2,
                    "username": "viewer1",
                    "name": "John Doe",
                    "profile_picture": "https://domain.com/profile.jpg"
                },
                "joined_at": "2025-07-24T20:50:00Z"
            }
        ]
    }
}
```

---

## Live Chat Endpoints

### 14. Get Stream Chat Messages
**GET** `/streams/{id}/chat?limit=50&after_id=123&before_id=456`

Retrieves chat messages for a stream with pagination support.

**Query Parameters:**
- `limit`: Number of messages to retrieve (default: 50)
- `after_id`: Get messages after this message ID
- `before_id`: Get messages before this message ID

**Response:**
```json
{
    "success": true,
    "message": "Stream chat retrieved successfully",
    "data": {
        "stream_id": 1,
        "messages": [
            {
                "id": 1,
                "stream_id": 1,
                "user_id": 2,
                "username": "viewer1",
                "message": "Great stream!",
                "user_profile_url": "https://domain.com/profile.jpg",
                "is_admin": false,
                "created_at": "2025-07-24T20:51:00Z"
            }
        ],
        "has_more": true,
        "last_message_id": 50
    }
}
```

### 15. Send Chat Message
**POST** `/streams/{id}/chat`

Sends a message to the stream chat.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "message": "This is a great stream!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Message sent successfully",
    "data": {
        "message": {
            "id": 51,
            "stream_id": 1,
            "user_id": 2,
            "username": "viewer1",
            "message": "This is a great stream!",
            "user_profile_url": "https://domain.com/profile.jpg",
            "is_admin": false,
            "created_at": "2025-07-24T20:52:00Z"
        }
    }
}
```

---

## Payment Endpoints

### 16. Initialize Stripe Payment
**POST** `/streams/{id}/payment/stripe/initialize`

Initializes Stripe payment for paid stream access.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "success_url": "https://yourapp.com/success", // Optional
    "cancel_url": "https://yourapp.com/cancel" // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Stripe payment session created successfully",
    "data": {
        "payment": {
            "id": 1,
            "reference": "STREAM_ABC123DEF456",
            "amount": "10.00",
            "currency": "USD",
            "status": "pending"
        },
        "stripe": {
            "session_id": "cs_test_123456",
            "checkout_url": "https://checkout.stripe.com/pay/cs_test_123456"
        },
        "stream": {
            "id": 1,
            "title": "Live Stream",
            "price": "10.00",
            "currency": "USD"
        }
    }
}
```

### 17. Initialize Nomba Payment
**POST** `/streams/{id}/payment/nomba/initialize`

Initializes Nomba payment for paid stream access.

**Request Body:**
```json
{
    "currency": "NGN", // Required: NGN or USD
    "callback_url": "https://yourapp.com/callback" // Optional
}
```

### 18. Verify Payment
**POST** `/streams/payments/verify`

Verifies payment status for both Stripe and Nomba.

**Request Body:**
```json
{
    "reference": "STREAM_ABC123DEF456",
    "gateway": "stripe" // or "nomba"
}
```

### 19. Get Payment Status
**GET** `/streams/payments/{paymentId}/status`

Returns status of a specific payment.

### 20. Get User's Stream Payments
**GET** `/streams/payments/my-payments`

Returns all stream payments made by the authenticated user.

---

## Webhook Endpoints (No Authentication Required)

### 21. Stripe Webhook
**POST** `/streams/webhooks/stripe`

Handles Stripe payment confirmations.

### 22. Nomba Webhook
**POST** `/streams/webhooks/nomba`

Handles Nomba payment confirmations.

---

## Error Responses

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error message",
    "data": "Detailed error information",
    "code": 400
}
```

**Common Error Codes:**
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden (Admin role required)
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

---

## Implementation Notes

### Agora Integration
- The API uses Agora RTC for video streaming
- Publishers (streamers) get full permissions
- Subscribers (viewers) get view-only permissions
- Tokens are valid for 1-2 hours and auto-renewed

### Payment Security
- All payment processes use secure tokens and references
- Webhook endpoints verify payment authenticity
- User access is granted only after successful payment verification

### Real-time Features
- Chat messages can be polled using `after_id` parameter
- Stream status should be checked periodically for live updates
- Viewer counts are updated in real-time

### Role-based Access
- Only admin users can create, start, and end streams
- All authenticated users can join and participate in streams
- Payment is required for paid streams before joining

This completes the comprehensive live streaming API implementation for your Connect App!
