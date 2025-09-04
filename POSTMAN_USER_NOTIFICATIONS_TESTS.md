# Postman User Notifications API Tests

## Environment Setup

Create a Postman environment with these variables:

-   `base_url`: http://localhost:8000 (or your domain)
-   `token`: (will be populated after login)

## Test Collection

### 1. Login (Required First)

```
Method: POST
URL: {{base_url}}/api/v1/login
Headers:
  Accept: application/json
  Content-Type: application/json
Body (JSON):
{
    "email": "test@example.com",
    "password": "password123"
}

Post-response Script:
pm.test("Login successful", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql(1);
    if (jsonData.data && jsonData.data.token) {
        pm.environment.set("token", jsonData.data.token);
    }
});
```

### 2. Get Unread Notification Count

```
Method: GET
URL: {{base_url}}/api/v1/user-notifications/count
Headers:
  Authorization: Bearer {{token}}
  Accept: application/json

Test Script:
pm.test("Get unread count", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql(1);
    pm.expect(jsonData.data).to.have.property("unread_count");
    console.log("Unread count:", jsonData.data.unread_count);
});
```

### 3. Get All User Notifications

```
Method: GET
URL: {{base_url}}/api/v1/user-notifications
Headers:
  Authorization: Bearer {{token}}
  Accept: application/json

Query Parameters (Optional):
  page: 1
  per_page: 20
  type: welcome
  is_read: false

Test Script:
pm.test("Get notifications", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql(1);
    pm.expect(jsonData.data).to.have.property("notifications");
    pm.expect(jsonData.data).to.have.property("unread_count");
    pm.expect(jsonData.data).to.have.property("pagination");

    if (jsonData.data.notifications.length > 0) {
        pm.environment.set("notification_id", jsonData.data.notifications[0].id);
        console.log("First notification ID:", jsonData.data.notifications[0].id);
        console.log("Total notifications:", jsonData.data.notifications.length);
    }
});
```

### 4. Mark Specific Notification as Read

```
Method: POST
URL: {{base_url}}/api/v1/user-notifications/{{notification_id}}/read
Headers:
  Authorization: Bearer {{token}}
  Accept: application/json

Test Script:
pm.test("Mark notification as read", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql(1);
    pm.expect(jsonData.data).to.have.property("unread_count");
    console.log("New unread count:", jsonData.data.unread_count);
});
```

### 5. Mark All Notifications as Read

```
Method: POST
URL: {{base_url}}/api/v1/user-notifications/read-all
Headers:
  Authorization: Bearer {{token}}
  Accept: application/json

Test Script:
pm.test("Mark all as read", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql(1);
    pm.expect(jsonData.data.unread_count).to.eql(0);
    console.log("All notifications marked as read");
});
```

## Expected Response Formats

### Get Notifications Response:

```json
{
    "status": 1,
    "message": "User notifications retrieved successfully",
    "data": {
        "notifications": [
            {
                "id": 1,
                "title": "Welcome to ConnectInc! 🎉💫",
                "message": "🌟 Welcome to your new social universe! 🌟...",
                "type": "welcome",
                "icon": "fa-heart",
                "priority": 10,
                "is_read": false,
                "read_at": null,
                "created_at": "2025-09-04T12:00:00.000000Z",
                "time_ago": "2 hours ago",
                "type_color": "text-purple-600",
                "type_badge": "bg-purple-100 text-purple-800",
                "data": {
                    "action_type": "welcome",
                    "show_tutorial": true,
                    "features_highlighted": ["swiping", "messaging", "calling"]
                }
            }
        ],
        "unread_count": 2,
        "pagination": {
            "total": 5,
            "count": 5,
            "per_page": 20,
            "current_page": 1,
            "total_pages": 1
        }
    }
}
```

### Count Response:

```json
{
    "status": 1,
    "message": "Unread notification count retrieved successfully",
    "data": {
        "unread_count": 2
    }
}
```

### Mark as Read Response:

```json
{
    "status": 1,
    "message": "Notification marked as read successfully",
    "data": {
        "unread_count": 1
    }
}
```

## Quick Copy-Paste Tests

### Test 1: Get Count

```
GET http://localhost:8000/api/v1/user-notifications/count
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Test 2: Get All Notifications

```
GET http://localhost:8000/api/v1/user-notifications
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Test 3: Mark as Read (replace 1 with actual ID)

```
POST http://localhost:8000/api/v1/user-notifications/1/read
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Test 4: Mark All as Read

```
POST http://localhost:8000/api/v1/user-notifications/read-all
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## cURL Examples

### Get Notifications:

```bash
curl -X GET "http://localhost:8000/api/v1/user-notifications" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Count:

```bash
curl -X GET "http://localhost:8000/api/v1/user-notifications/count" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Mark as Read:

```bash
curl -X POST "http://localhost:8000/api/v1/user-notifications/1/read" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Mark All as Read:

```bash
curl -X POST "http://localhost:8000/api/v1/user-notifications/read-all" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Testing Notes

1. **Authentication Required**: All endpoints require valid Bearer token
2. **New Users**: Automatically get welcome & tutorial notifications on registration
3. **Notification IDs**: Use actual notification IDs from GET response for mark-as-read
4. **Filtering**: Use query parameters to filter by type, read status, etc.
5. **Pagination**: Default 20 per page, use `page` parameter for more

## Common Test Scenarios

### Scenario 1: New User Flow

1. Register new user → Notifications auto-created
2. Login → Get token
3. Get count → Should show 2 unread (welcome + tutorial)
4. Get notifications → Should return welcome & tutorial
5. Mark welcome as read → Count should decrease to 1
6. Mark all as read → Count should be 0

### Scenario 2: Frontend Integration

1. Login
2. Get count for badge display
3. Get notifications for dropdown
4. Mark as read when user clicks
5. Update count in real-time
