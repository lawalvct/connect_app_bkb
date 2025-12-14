# getUsersBySocialCircle Function - Technical Documentation

## Overview

The `getUsersBySocialCircle` function is the **core user discovery/swiping system** that returns profiles of users from specific social circles for the swipe/match feature. This is the primary endpoint for the Tinder-like discovery experience.

---

## Endpoint

**POST** `/api/v1/users/discover`

**Authentication:** Required (Bearer token)

**Rate Limiting:** Protected by `swipe.limit` middleware

---

## Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `social_id` | array | No | Array of social circle IDs to filter users by |
| `country_id` | integer | No | Filter users by specific country |
| `last_id` | integer | No | Last user ID seen (for cursor-based pagination) |
| `limit` | integer | No | Number of users to return (default: 10, max: 50) |

**Example Request:**
```json
{
  "social_id": [1, 3, 5],
  "country_id": 123,
  "last_id": 1500,
  "limit": 10
}
```

---

## How It Works

### **Step 1: Input Validation**

```php
$validator = Validator::make($request->all(), [
    'social_id' => 'nullable|array',
    'social_id.*' => 'integer',
    'country_id' => 'nullable|integer',
    'last_id' => 'nullable|integer',
    'limit' => 'nullable|integer|min:1|max:50'
]);
```

- Validates all parameters
- Ensures social_id is an array of integers
- Limits results to max 50 users per request

### **Step 2: Extensive Debug Logging**

The function includes comprehensive logging for troubleshooting:

#### **2.1 Check if Current User is in Requested Circles**
```php
$userInCircle = DB::table('user_social_circles')
    ->where('user_id', $user->id)
    ->whereIn('social_id', $socialIds)
    ->where('deleted_flag', 'N')
    ->exists();
```
**Purpose:** Verify user has access to these circles

#### **2.2 Count Total Users in Social Circles**
```php
$totalUsersInSocialCircles = DB::table('users')
    ->join('user_social_circles', 'users.id', '=', 'user_social_circles.user_id')
    ->whereIn('user_social_circles.social_id', $socialIds)
    ->where('users.deleted_flag', 'N')
    ->whereNull('users.deleted_at')
    ->count();
```
**Purpose:** Know the total pool size

#### **2.3 Count Users Above ID 500**
```php
$usersAbove500 = DB::table('users')
    ->where('users.id', '>=', 500)
    ->count();
```
**Purpose:** Exclude test/demo accounts (IDs < 500)

#### **2.4 Count Available Users (After Exclusions)**
```php
$totalUsersInCircles = DB::table('users')
    ->whereIn('user_social_circles.social_id', $socialIds)
    ->where('users.id', '!=', $user->id)
    ->where('users.id', '>=', 500)
    ->count();
```
**Purpose:** Know how many users are actually available

#### **2.5 Get Social Circle Statistics**
```php
$socialCircleStats = DB::table('social_circles')
    ->leftJoin('user_social_circles', ...)
    ->select('social_circles.id', 'social_circles.name', 
             DB::raw('COUNT(user_social_circles.user_id) as user_count'))
    ->groupBy('social_circles.id', 'social_circles.name')
    ->get();
```
**Purpose:** See which circles have the most users

#### **2.6 Get Current User's Social Circles**
```php
$userSocialCircles = DB::table('user_social_circles')
    ->where('user_social_circles.user_id', $user->id)
    ->get();
```
**Purpose:** Know what circles the user belongs to

#### **2.7 Count Users with Country Filter**
```php
if ($countryId) {
    $usersWithCountry = DB::table('users')
        ->where('users.country_id', $countryId)
        ->count();
}
```
**Purpose:** Verify country filter isn't too restrictive

### **Step 3: Primary User Retrieval**

```php
$getData = UserHelper::getLatestSocialCircleUsers(
    $socialIds, 
    $user->id, 
    $lastId, 
    $countryId, 
    $limit
);
```

**What `getLatestSocialCircleUsers` does:**
- Gets users from specified social circles
- Excludes current user
- Excludes users already swiped (left/right)
- Excludes blocked users
- Excludes test users (ID < 500)
- Applies country filter if provided
- Uses cursor pagination with `last_id`
- Returns latest users with some randomness

**Key Exclusions:**
1. ✅ Current authenticated user
2. ✅ Users already swiped left
3. ✅ Users already swiped right
4. ✅ Blocked users
5. ✅ Test accounts (ID < 500)
6. ✅ Deleted users

### **Step 4: Fallback Strategy #1 - User's Own Circles**

```php
if ($getData->isEmpty() && $userSocialCircles->isNotEmpty()) {
    $userOwnSocialIds = $userSocialCircles->pluck('id')->toArray();
    $getData = UserHelper::getLatestSocialCircleUsers(
        $userOwnSocialIds, 
        $user->id, 
        $lastId, 
        $countryId, 
        $limit
    );
}
```

**When:** No users found in requested circles

**Action:** Try getting users from the authenticated user's own social circles

**Purpose:** Ensure users always have someone to swipe on

### **Step 5: Fallback Strategy #2 - Any Users**

```php
if ($getData->isEmpty()) {
    $getData = UserHelper::getAnyLatestUsers(
        $user->id, 
        $lastId, 
        $countryId, 
        $limit
    );
}
```

**When:** Still no users found after fallback #1

**Action:** Get any users (ID >= 500) regardless of social circles

**Purpose:** Last resort to provide swipeable profiles

### **Step 6: Advertisement Injection**

```php
$swipeCount = UserSwipe::getTodayRecord($user->id)->total_swipes ?? 0;

if ($swipeCount > 0 && $swipeCount % 10 === 0) {
    $ads = Ad::getAdsForDiscovery($user->id, 1);
    if ($ads->isNotEmpty()) {
        $adData = [
            'type' => 'advertisement',
            'ad_data' => $ads->first(),
            'is_ad' => true
        ];
        $getData = $getData->push($adData);
    }
}
```

**Logic:**
- Check user's swipe count for today
- Every 10 swipes, inject an ad
- Ad appears as a card in the swipe deck
- Marked with `is_ad: true` flag

**Example:** After 10, 20, 30, 40... swipes, user sees an ad

### **Step 7: Enrich User Data**

```php
$getData = $getData->map(function($userItem) use ($user) {
    // Get connection count
    $connectionCount = UserRequestsHelper::getConnectionCount($userItem->id);
    $userItem->total_connections = $connectionCount;
    
    // Check if connected
    $isConnected = UserRequestsHelper::areUsersConnected($user->id, $userItem->id);
    $userItem->is_connected_to_current_user = $isConnected;
    
    // Add country details
    if ($userItem->country) {
        $userItem->country_details = new CountryResource($userItem->country);
    }
    
    return $userItem;
});
```

**Adds:**
1. **total_connections** - How many connections this user has (social proof)
2. **is_connected_to_current_user** - Already connected? (shouldn't happen but safety check)
3. **country_details** - Full country information

### **Step 8: Format Response**

```php
$getData = \App\Http\Resources\V1\UserResource::collection($getData);
```

**UserResource includes:**
- User ID, name, username
- Profile images (all uploaded photos)
- Bio, age, gender
- Country details
- Social circles
- Profile URL (with legacy support)
- Verification status

---

## Response Format

### **Success Response (200):**

```json
{
  "message": "Successfully!",
  "status": 1,
  "data": [
    {
      "id": 1234,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "bio": "Travel enthusiast and photographer",
      "age": 28,
      "gender": "male",
      "profile_images": [
        {
          "id": 1,
          "image_url": "https://example.com/profiles/photo1.jpg",
          "is_main": true,
          "order": 1
        },
        {
          "id": 2,
          "image_url": "https://example.com/profiles/photo2.jpg",
          "is_main": false,
          "order": 2
        }
      ],
      "country": {
        "id": 1,
        "name": "United States",
        "code": "US",
        "flag": "🇺🇸"
      },
      "social_circles": [
        {
          "id": 1,
          "name": "Connect Travel",
          "logo": "https://example.com/logos/travel.png"
        },
        {
          "id": 3,
          "name": "Connect Fitness",
          "logo": "https://example.com/logos/fitness.png"
        }
      ],
      "total_connections": 150,
      "is_connected_to_current_user": false,
      "is_verified": true,
      "last_activity_at": "2025-12-13T10:30:00.000000Z"
    }
  ],
  "debug": {
    "total_in_circles": 250,
    "user_in_circle": true,
    "filters_applied": {
      "country_id": 123,
      "social_ids": [1, 3, 5],
      "current_user_excluded": 789,
      "testing_users_excluded": "Users with ID < 500 excluded"
    },
    "detailed_stats": {
      "all_users_in_circles": 300,
      "users_above_500_in_circles": 280,
      "current_user_social_circles": [1, 3, 5, 7],
      "requested_social_ids": [1, 3, 5]
    }
  }
}
```

### **Empty Response (200):**

```json
{
  "message": "No users available.",
  "status": 0,
  "data": [],
  "debug": {
    "total_in_circles": 0,
    "user_in_circle": false,
    "possible_reasons": [
      "All users already swiped",
      "All users blocked",
      "No users in specified social circles",
      "No users matching country filter",
      "Testing users (ID < 500) excluded"
    ],
    "detailed_stats": {
      "all_users_in_circles": 0,
      "users_above_500_in_circles": 0,
      "current_user_social_circles": [1, 3],
      "requested_social_ids": [1, 3, 5]
    }
  }
}
```

### **Validation Error (200):**

```json
{
  "message": "The social id.0 must be an integer.",
  "status": 0,
  "data": []
}
```

---

## Key Features

### 1. **Smart Fallback System**

**3-Tier Fallback:**
1. Try requested social circles
2. Try user's own social circles
3. Try any available users

**Benefit:** Users always have profiles to swipe on

### 2. **Comprehensive Exclusions**

Prevents showing:
- Already swiped users (no duplicates)
- Blocked users (safety)
- Test accounts (quality)
- Current user (obvious)
- Deleted users (data integrity)

### 3. **Cursor-Based Pagination**

```php
last_id: 1500
```

**How it works:**
- Returns users with ID > last_id
- More efficient than offset pagination
- Prevents duplicates when new users join
- Better for infinite scroll

### 4. **Advertisement Integration**

- Non-intrusive (every 10 swipes)
- Clearly marked (`is_ad: true`)
- Monetization opportunity
- Doesn't disrupt user experience

### 5. **Rich User Profiles**

Each profile includes:
- Multiple photos
- Bio and basic info
- Social circles (shared interests)
- Connection count (social proof)
- Country details
- Verification status

### 6. **Extensive Debug Information**

**Production-ready debugging:**
- Total users in circles
- Filter statistics
- Exclusion reasons
- User's own circles
- Helps troubleshoot "no users" issues

### 7. **Test Account Filtering**

```php
where('users.id', '>=', 500)
```

**Purpose:**
- Excludes demo/test accounts
- Ensures real user experience
- Maintains data quality

---

## Use Cases

### **Primary Use Case: Swipe/Match Discovery**

**User Flow:**
1. User opens app, selects "Connect Travel" circle
2. App calls endpoint with `social_id: [11]`
3. Gets 10 users from Connect Travel
4. User swipes through profiles
5. When reaching last profile, app calls again with `last_id: 1234`
6. Gets next 10 users
7. Every 10 swipes, sees an ad

### **Secondary Use Cases:**

#### **1. Country-Specific Discovery**
```json
{
  "social_id": [1, 3],
  "country_id": 123,
  "limit": 20
}
```
**Scenario:** User traveling to Japan, wants to meet locals

#### **2. Multiple Circle Discovery**
```json
{
  "social_id": [1, 3, 5, 7],
  "limit": 15
}
```
**Scenario:** User interested in multiple topics

#### **3. Infinite Scroll**
```json
// First request
{ "social_id": [1], "limit": 10 }

// Second request (after swiping through first 10)
{ "social_id": [1], "last_id": 1500, "limit": 10 }

// Third request
{ "social_id": [1], "last_id": 1510, "limit": 10 }
```

---

## Algorithm Flow Diagram

```
┌─────────────────────────────────────┐
│  User Requests Discovery            │
│  social_id: [1, 3]                  │
│  country_id: 123                    │
│  limit: 10                          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Validate Input                     │
│  - Check social_id format           │
│  - Validate limit (max 50)          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Debug Logging                      │
│  - Count total users in circles     │
│  - Check user's circles             │
│  - Log statistics                   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Get Users from Requested Circles   │
│  - Exclude swiped users             │
│  - Exclude blocked users            │
│  - Exclude test accounts (ID < 500) │
│  - Apply country filter             │
│  - Use cursor pagination            │
└──────────────┬──────────────────────┘
               │
               ▼
        ┌──────┴──────┐
        │  Users       │
        │  Found?      │
        └──┬───────┬───┘
           │ YES   │ NO
           │       │
           │       ▼
           │  ┌─────────────────────────┐
           │  │  Fallback #1:           │
           │  │  Try User's Own Circles │
           │  └──────────┬──────────────┘
           │             │
           │             ▼
           │      ┌──────┴──────┐
           │      │  Users       │
           │      │  Found?      │
           │      └──┬───────┬───┘
           │         │ YES   │ NO
           │         │       │
           │         │       ▼
           │         │  ┌─────────────────┐
           │         │  │  Fallback #2:   │
           │         │  │  Any Users      │
           │         │  └────────┬────────┘
           │         │           │
           ▼         ▼           ▼
┌─────────────────────────────────────┐
│  Check Swipe Count                  │
│  If count % 10 == 0:                │
│    Inject Advertisement             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Enrich User Data                   │
│  - Add connection count             │
│  - Add is_connected flag            │
│  - Add country details              │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Format with UserResource           │
│  - Profile images                   │
│  - Social circles                   │
│  - All user details                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Return Response                    │
│  - User profiles                    │
│  - Debug information                │
└─────────────────────────────────────┘
```

---

## Performance Considerations

### **Optimizations:**

1. **Eager Loading:**
   ```php
   ->with(['profileImages', 'country', 'socialCircles'])
   ```
   Prevents N+1 queries

2. **Cursor Pagination:**
   - More efficient than offset
   - Uses indexed `id` column
   - Scales better with large datasets

3. **Indexed Columns:**
   - `user_social_circles.social_id`
   - `user_social_circles.user_id`
   - `users.id`
   - `users.country_id`
   - `users.deleted_flag`

4. **Limit Results:**
   - Max 50 users per request
   - Prevents overwhelming response
   - Reduces memory usage

5. **Caching Opportunities:**
   - User's social circles (rarely change)
   - Swiped user IDs (cache for session)
   - Blocked user IDs (cache for session)
   - Social circle statistics (cache for 1 hour)

---

## Frontend Integration

### **React Native Example:**

```javascript
import { useState, useEffect } from 'react';

const DiscoveryScreen = () => {
  const [users, setUsers] = useState([]);
  const [lastId, setLastId] = useState(null);
  const [loading, setLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);

  const loadUsers = async () => {
    if (loading || !hasMore) return;
    
    setLoading(true);
    
    try {
      const response = await fetch('/api/v1/users/discover', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          social_id: [1, 3, 5],
          country_id: selectedCountry,
          last_id: lastId,
          limit: 10
        })
      });
      
      const data = await response.json();
      
      if (data.status === 1 && data.data.length > 0) {
        // Filter out ads if you want to handle them separately
        const newUsers = data.data.filter(item => !item.is_ad);
        const ads = data.data.filter(item => item.is_ad);
        
        setUsers(prev => [...prev, ...newUsers]);
        setLastId(newUsers[newUsers.length - 1]?.id);
        setHasMore(newUsers.length === 10);
        
        // Handle ads separately
        if (ads.length > 0) {
          showAd(ads[0]);
        }
      } else {
        setHasMore(false);
      }
    } catch (error) {
      console.error('Failed to load users:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  const handleSwipe = async (userId, direction) => {
    // Send swipe to backend
    await swipeUser(userId, direction);
    
    // Remove from local state
    setUsers(prev => prev.filter(u => u.id !== userId));
    
    // Load more if running low
    if (users.length < 5) {
      loadUsers();
    }
  };

  return (
    <SwipeCards
      users={users}
      onSwipe={handleSwipe}
      onLoadMore={loadUsers}
    />
  );
};
```

### **Infinite Scroll Pattern:**

```javascript
const handleCardSwiped = (userId) => {
  // Remove swiped user
  setUsers(prev => prev.filter(u => u.id !== userId));
  
  // Preload more users when 3 cards left
  if (users.length <= 3 && hasMore && !loading) {
    loadUsers();
  }
};
```

---

## Error Handling

### **Common Scenarios:**

| Scenario | Response | Frontend Action |
|----------|----------|-----------------|
| No users available | `status: 0, data: []` | Show "No more users" message |
| Invalid social_id | `status: 0, message: "validation error"` | Show error toast |
| User not in circle | Returns users anyway (fallback) | Continue normally |
| Network error | Exception | Retry with exponential backoff |
| Rate limit hit | 429 status | Show "Slow down" message |

---

## Testing Scenarios

### **Test Cases:**

1. ✅ **Basic Discovery:** Request with social_id, returns users
2. ✅ **Country Filter:** Add country_id, returns only those users
3. ✅ **Pagination:** Use last_id, returns next batch
4. ✅ **Empty Results:** All users swiped, returns empty
5. ✅ **Fallback #1:** No users in requested circles, tries user's circles
6. ✅ **Fallback #2:** Still no users, returns any users
7. ✅ **Ad Injection:** After 10 swipes, ad appears
8. ✅ **Exclusions:** Verify swiped/blocked users not returned
9. ✅ **Test Accounts:** Verify users with ID < 500 excluded
10. ✅ **Multiple Circles:** Request multiple social_ids, returns from all

---

## Debug Information Usage

The `debug` object in response helps troubleshoot:

```json
"debug": {
  "total_in_circles": 0,
  "user_in_circle": false,
  "possible_reasons": [
    "All users already swiped",
    "No users in specified social circles"
  ]
}
```

**Use cases:**
- User reports "no users" → Check debug info
- Low user count → Check `total_in_circles`
- User not in circle → Check `user_in_circle`
- Filter too restrictive → Check `detailed_stats`

---

## Logging & Monitoring

### **Key Logs:**

```
getUsersBySocialCircle called
Current user in social circle
Total users in social circles (all users)
Users with ID >= 500 in social circles
Social circle statistics
Current user belongs to social circles
```

### **Metrics to Monitor:**

1. **Average users returned per request**
2. **Percentage of empty responses**
3. **Fallback usage rate** (how often fallbacks trigger)
4. **Ad injection rate** (every 10 swipes)
5. **Response time** (should be < 500ms)

---

## Best Practices

### **For Frontend:**

1. **Preload Users:** Load next batch when 3-5 cards remaining
2. **Handle Ads:** Show ads in a special card format
3. **Cache Locally:** Store swiped user IDs to prevent re-showing
4. **Error Handling:** Gracefully handle empty responses
5. **Loading States:** Show skeleton while loading

### **For Backend:**

1. **Monitor Exclusions:** Track how many users are excluded
2. **Optimize Queries:** Use indexes on all filter columns
3. **Cache Aggressively:** Cache user's circles, swiped IDs
4. **Log Extensively:** Debug info helps troubleshoot
5. **Test Fallbacks:** Ensure fallbacks work correctly

---

## Summary

The `getUsersBySocialCircle` function is a sophisticated discovery system that:

- ✅ Returns swipeable user profiles from social circles
- ✅ Implements 3-tier fallback for availability
- ✅ Excludes already-swiped and blocked users
- ✅ Uses cursor-based pagination for efficiency
- ✅ Injects ads every 10 swipes
- ✅ Enriches profiles with connection data
- ✅ Provides extensive debug information
- ✅ Filters out test accounts (ID < 500)
- ✅ Supports country filtering
- ✅ Handles multiple social circles

**Perfect for:** Tinder-like swipe/match discovery features in social apps.
