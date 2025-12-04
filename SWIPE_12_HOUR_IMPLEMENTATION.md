# 12-Hour Rolling Window Swipe System Implementation

## Overview

The swipe system has been updated from a daily (calendar day) limit to a **12-hour rolling window** limit. This provides more flexibility for users and better engagement.

## Changes Summary

### Previous System

-   **Daily Limit**: 50 swipes per calendar day (resets at midnight)
-   **Connect Boost**: 100 swipes per calendar day
-   **Tracking**: Date-based (`swipe_date` field)

### New System

-   **Rolling Window**: 50 swipes per 12 hours from first swipe
-   **Connect Boost**: 100 swipes per 12 hours (50 base + 50 bonus)
-   **Tracking**: Timestamp-based (`swiped_at` field)
-   **Dynamic Reset**: Limit resets 12 hours after the oldest swipe in the window

## Technical Implementation

### 1. Database Changes

#### Migration: `add_timestamp_tracking_to_user_swipes_table`

```php
// Added to user_swipes table:
- swiped_at (timestamp, nullable) - Tracks individual swipe timestamps
- Index: user_swipes_time_idx (user_id, swiped_at) - For efficient queries
```

**Run Migration:**

```bash
php artisan migrate
```

### 2. Updated Models & Helpers

#### UserSwipeHelper (app/Helpers/UserSwipeHelper.php)

**New Methods:**

1. **getSwipeCountWithinHours($userId, $hours = 12)**

    - Counts swipes within the specified time window
    - Returns integer count

2. **canSwipeInWindow($userId, $baseLimit = 50, $hours = 12)**

    - Checks if user can swipe based on rolling window
    - Returns:
        ```php
        [
            'can_swipe' => bool,
            'swipes_used' => int,
            'limit' => int,
            'remaining_swipes' => int,
            'resets_at' => string|null,
            'has_boost' => bool
        ]
        ```

3. **recordSwipeWithTimestamp($userId, $swipeType)**
    - Records individual swipe with timestamp
    - Tracks swipe type (left, right, super)
    - Returns UserSwipe model

#### UserHelper (app/Helpers/UserHelper.php)

**Updated Methods:**

1. **getSwipeStats($userId)**

    - Now uses 12-hour rolling window
    - Returns:
        ```php
        {
            'total_swipes': int,
            'left_swipes': int,
            'right_swipes': int,
            'super_likes': int,
            'swipe_limit': int,
            'remaining_swipes': int,
            'resets_at': string|null,
            'has_boost': bool
        }
        ```

2. **incrementSwipeCount($userId, $swipeType)**
    - Now calls `recordSwipeWithTimestamp()` instead of `incrementSwipe()`
    - Records with timestamp for rolling window tracking

### 3. Controller Changes

#### ConnectionController (app/Http/Controllers/API/V1/ConnectionController.php)

**Added Swipe Limit Check:**

-   Checks 12-hour rolling window BEFORE processing swipe
-   Returns detailed limit information on rejection
-   Includes subscription prompt for free users

**Response on Limit Reached (429 - Too Many Requests):**

```json
{
    "status": 0,
    "message": "Swipe limit reached. You have used 50 out of 50 swipes in the last 12 hours. Limit resets at 2025-12-04 18:30:00",
    "data": {
        "swipes_used": 50,
        "swipe_limit": 50,
        "remaining_swipes": 0,
        "resets_at": "2025-12-04 18:30:00",
        "has_boost": false,
        "subscription_prompt": {
            "message": "Upgrade to Connect Boost for 50 more swipes every 12 hours!",
            "benefits": [
                "100 total swipes per 12 hours (50 base + 50 bonus)",
                "Priority in suggested profiles",
                "Enhanced profile visibility"
            ],
            "subscription_id": 4
        },
        "swipe_stats": {...},
        "suggested_user": {...},
        "social_circle_name": "Connect Travel"
    }
}
```

## Swipe Limits

### Free Users

-   **Limit**: 50 swipes per 12 hours
-   **Reset**: 12 hours after first swipe in window
-   **Prompt**: Subscription upsell when limit reached

### Connect Boost Users (Subscription ID: 4)

-   **Limit**: 100 swipes per 12 hours (50 base + 50 bonus)
-   **Reset**: 12 hours after first swipe in window
-   **No Prompt**: Already subscribed

### Unlimited/Premium Users

-   **Limit**: 999,999 (effectively unlimited)
-   **No Reset**: No practical limit

### Connect Travel (Social Circle ID: 11)

-   **Special Limit**: 10 swipes per 12 hours (separate tracking)
-   **Independent**: Does not affect general swipe limit
-   **Table**: `user_social_circle_swipes`

## How Rolling Window Works

### Example Timeline:

**User starts swiping at 10:00 AM:**

| Time     | Action               | Swipes Used | Limit Resets At     |
| -------- | -------------------- | ----------- | ------------------- |
| 10:00 AM | Swipe #1             | 1/50        | 10:00 PM (next day) |
| 11:30 AM | Swipe #25            | 25/50       | 10:00 PM            |
| 2:00 PM  | Swipe #50            | 50/50       | 10:00 PM            |
| 3:00 PM  | **BLOCKED**          | 50/50       | 10:00 PM            |
| 10:01 PM | **Swipe #1 expires** | 49/50       | 11:30 PM            |
| 10:01 PM | Swipe #51            | 50/50       | 11:30 PM            |

**Key Points:**

-   Window is NOT calendar-based (not midnight reset)
-   Oldest swipe expires first (FIFO)
-   Reset time updates as old swipes expire
-   More natural user experience

## API Response Changes

### Updated Fields in Swipe Stats:

-   ~~`daily_limit`~~ → `swipe_limit` (new name)
-   Added: `resets_at` (timestamp when limit resets)
-   Added: `has_boost` (boolean indicating Connect Boost status)

### Backward Compatibility:

-   Old `swipe_date` field still populated for historical data
-   Both daily and timestamp tracking coexist
-   Existing swipe records remain valid

## Testing

### Test Scenarios:

1. **Free User Swipe Limit**

    ```bash
    # Make 50 swipes
    # Attempt 51st swipe - should get 429 error with subscription prompt
    ```

2. **Connect Boost User**

    ```bash
    # Make 100 swipes
    # Attempt 101st swipe - should get 429 error
    ```

3. **Rolling Window Reset**

    ```bash
    # Make swipes at different times
    # Wait 12 hours from first swipe
    # Verify oldest swipe expires and new swipe allowed
    ```

4. **Connect Travel Separate Limit**
    ```bash
    # Make 10 swipes in Connect Travel (social_id = 11)
    # Verify general swipes still available
    # Make 50 more swipes in other circles
    ```

## Frontend Integration

### Handling Limit Response:

```javascript
// When swipe request returns 429
if (response.status === 429) {
    const data = response.data.data;

    // Show limit message
    showLimitMessage(
        `You've used ${data.swipes_used} of ${data.swipe_limit} swipes.`
    );

    // Show when limit resets
    if (data.resets_at) {
        showResetTime(data.resets_at);
    }

    // Show subscription prompt for free users
    if (data.subscription_prompt && !data.has_boost) {
        showSubscriptionUpsell({
            message: data.subscription_prompt.message,
            benefits: data.subscription_prompt.benefits,
            subscriptionId: data.subscription_prompt.subscription_id,
        });
    }

    // Still show next suggested user
    if (data.suggested_user) {
        displayUser(data.suggested_user);
    }
}
```

### Display Swipe Stats:

```javascript
// Show remaining swipes
const stats = response.data.swipe_stats;

displaySwipeCounter({
    used: stats.total_swipes,
    limit: stats.swipe_limit,
    remaining: stats.remaining_swipes,
    resetsAt: stats.resets_at,
    hasBoost: stats.has_boost,
});
```

## Benefits of 12-Hour System

1. **User Flexibility**: Not tied to calendar days
2. **Better Engagement**: Users can swipe at any time
3. **Fair Distribution**: Rolling window prevents gaming the system
4. **Revenue Opportunity**: Clear upgrade path with subscription prompt
5. **Accurate Tracking**: Timestamp-based is more precise

## Migration Notes

### Data Preservation:

-   Existing `user_swipes` records remain unchanged
-   New `swiped_at` column is nullable (old records have NULL)
-   System handles both old and new records gracefully

### Performance:

-   Added index on `(user_id, swiped_at)` for fast queries
-   Queries only scan last 12 hours of data
-   Efficient for high-volume swipe operations

## Related Files

-   Migration: `database/migrations/2025_12_04_173603_add_timestamp_tracking_to_user_swipes_table.php`
-   Helper: `app/Helpers/UserSwipeHelper.php`
-   Helper: `app/Helpers/UserHelper.php`
-   Controller: `app/Http/Controllers/API/V1/ConnectionController.php`
-   Model: `app/Models/UserSwipe.php`

## Connect Travel Special Handling

Connect Travel (social_id = 11) maintains its own separate 10-swipe/12-hour limit using the `user_social_circle_swipes` table. This is **independent** of the general swipe limit:

-   User can make 50 general swipes + 10 Connect Travel swipes
-   Tracked separately in different tables
-   Both use 12-hour rolling windows
-   Different reset times possible

---

**Implementation Date**: December 4, 2025
**Version**: 1.0
**Status**: ✅ Complete
