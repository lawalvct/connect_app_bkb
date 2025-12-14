# getUsersByPost Function - Technical Documentation

## Overview

The `getUsersByPost` function is a **post-based user discovery system** that returns a curated feed of posts with media from users the authenticated user hasn't interacted with yet. It's designed to help users discover new connections through engaging content.

---

## Endpoint

**POST** `/api/v1/users/discover-by-post`

**Authentication:** Required (Bearer token)

---

## Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | No | If provided, finds posts from the same social circle as this post |
| `social_circle_ids` | array | No | Array of social circle IDs to filter posts by |
| `page` | integer | No | Page number for pagination (default: 1) |
| `per_page` | integer | No | Number of posts per page (default: 50, max: 50) |
| `country_id` | integer | No | Filter posts by users from specific country |

**Example Request:**
```json
{
  "social_circle_ids": [1, 3, 5],
  "page": 1,
  "per_page": 20,
  "country_id": 123
}
```

---

## How It Works

### **Step 1: Input Validation**
- Validates all input parameters
- Ensures post_id exists if provided
- Ensures social_circle_ids are valid
- Limits per_page to maximum of 50

### **Step 2: Smart Social Circle Selection**

The function intelligently determines which social circles to show posts from:

1. **If `post_id` is provided:**
   - Gets the social circle of that specific post
   - Adds it to the filter list

2. **If no social circles specified:**
   - Finds the 10 most popular social circles from the last 30 days
   - Based on post count in each circle
   - Ensures fresh, active content

3. **Gets authenticated user's social circles:**
   - Used for relevance scoring later
   - Prioritizes content from user's own circles

### **Step 3: User Exclusion Logic**

Excludes posts from users who:
- ✅ Have been swiped left/right by the authenticated user
- ✅ Are blocked by the authenticated user
- ✅ Are the authenticated user themselves

This ensures users only see **new potential connections**.

### **Step 4: Base Query Filters**

Only includes posts that meet ALL these criteria:
- ✅ `is_published = true` - Published posts only
- ✅ `deleted_at IS NULL` - Not deleted
- ✅ **Has media** - Photos or videos (no text-only posts)
- ✅ User is not deleted (`deleted_flag = 'N'`)
- ✅ User matches country filter (if provided)
- ✅ User is not in excluded list

### **Step 5: Multi-Criteria Discovery Algorithm**

This is the **core intelligence** of the function. It creates 5 different queries and mixes results:

| Category | Weight | Query Logic | Purpose |
|----------|--------|-------------|---------|
| **Relevant** | 10% | Posts from user's own social circles, sorted by engagement | Highest priority - shows content from user's interests |
| **Recent** | 30% | Newest posts, sorted by `created_at DESC` | Fresh content, trending topics |
| **Liked** | 25% | Posts with most likes, sorted by `likes_count DESC` | Popular, engaging content |
| **Commented** | 20% | Posts with most comments, sorted by `comments_count DESC` | Discussion-worthy content |
| **Viewed** | 15% | Posts with most views, sorted by `views_count DESC` | Viral or interesting content |

**Distribution Example (50 posts):**
- 5 posts from user's social circles (10%)
- 15 recent posts (30%)
- 13 most liked posts (25%)
- 10 most commented posts (20%)
- 7 most viewed posts (15%)

### **Step 6: Collection & Deduplication**

```php
foreach ($distributions as $type => $count) {
    // Get posts for this category
    // Exclude already-used post IDs
    // Add to collection
    // Track used IDs to prevent duplicates
}
```

**Process:**
1. Collects posts from each category based on percentages
2. Gets 2x the needed amount to account for duplicates
3. Prevents duplicate posts using `$usedPostIds` array
4. If not enough posts, fills remaining with random posts
5. **Shuffles** the final collection for variety

### **Step 7: Pagination**

```php
$offset = ($page - 1) * $perPage;
$paginatedPosts = $collectedPosts->slice($offset, $perPage);
```

- Applies offset based on page number
- Returns only the requested page of results
- Calculates total pages and "has_more" flag

### **Step 8: Response Formatting**

For each post, the response includes:

#### **Post Data:**
```json
{
  "id": 123,
  "content": "Post caption text",
  "type": "image",
  "location": "New York, USA",
  "likes_count": 42,
  "comments_count": 15,
  "shares_count": 8,
  "views_count": 250,
  "created_at": "2025-12-13T10:30:00.000000Z",
  "time_since_created": "2 hours ago",
  "discovery_type": "recent"
}
```

#### **Media Array:**
```json
"media": [
  {
    "id": 456,
    "type": "image",
    "url": "https://example.com/media/photo.jpg",
    "thumbnail_url": "https://example.com/media/thumb.jpg",
    "width": 1080,
    "height": 1920,
    "file_size": 2048576,
    "file_size_human": "2 MB"
  }
]
```

#### **Social Circle:**
```json
"social_circle": {
  "id": 3,
  "name": "Connect Travel",
  "logo": "https://example.com/logos/travel.png",
  "color": "#FF5733"
}
```

#### **User Data:**
```json
"user": {
  "id": 789,
  "name": "John Doe",
  "username": "johndoe",
  "profile_url": "https://example.com/profiles/johndoe.jpg",
  "bio": "Travel enthusiast",
  "country": {
    "id": 1,
    "name": "United States",
    "code": "US"
  }
}
```

#### **User Additional Data:**
```json
"user_additional_data": {
  "total_connections": 150,
  "is_connected_to_current_user": false,
  "posts_count": 25,
  "recent_posts_count": 5
}
```

---

## Complete Response Example

```json
{
  "status": 1,
  "message": "Posts with user details retrieved successfully",
  "data": [
    {
      "id": 123,
      "content": "Amazing sunset at the beach!",
      "type": "image",
      "likes_count": 42,
      "comments_count": 15,
      "created_at": "2025-12-13T10:30:00.000000Z",
      "time_since_created": "2 hours ago",
      "discovery_type": "recent",
      "media": [...],
      "social_circle": {...},
      "user": {...},
      "user_additional_data": {...}
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 250,
    "last_page": 5,
    "has_more_pages": true
  },
  "meta": {
    "social_circles_used": [1, 3, 5, 7],
    "auth_user_circles": [1, 3],
    "discovery_method": "post_based_with_criteria",
    "criteria_distribution": {
      "relevant": 5,
      "recent": 15,
      "liked": 13,
      "commented": 10,
      "viewed": 7
    }
  }
}
```

---

## Key Features

### 1. **Smart Discovery**
- Mixes different post types for variety
- Prevents monotonous feeds
- Balances fresh and popular content

### 2. **Relevance Scoring**
- Prioritizes user's social circles (10%)
- Shows content aligned with user interests
- Increases engagement likelihood

### 3. **Media-Only Posts**
- Only shows posts with photos/videos
- More engaging than text-only
- Better for visual discovery

### 4. **No Duplicates**
- Excludes already-swiped users
- Prevents seeing same people repeatedly
- Tracks used post IDs within response

### 5. **Engagement-Based**
- Shows popular content (likes, comments, views)
- Increases quality of discovery
- Users see what others find interesting

### 6. **Fresh Content**
- 30% of feed is recent posts
- Ensures up-to-date content
- Prevents stale feeds

### 7. **Connection Info**
- Shows if users are already connected
- Displays connection count (social proof)
- Shows user's posting activity

---

## Use Cases

### **Primary Use Case: Discover People Through Posts**

**User Flow:**
1. User opens "Discover" tab in app
2. Sees curated feed of posts from potential connections
3. Browses posts, likes interesting content
4. Clicks on user profile from post
5. Swipes right to connect

**Benefits:**
- More context than profile-only swiping
- See personality through content
- Discover based on interests (social circles)
- Higher quality matches

### **Secondary Use Cases:**

1. **Social Circle Exploration**
   - User joins "Connect Travel" circle
   - Sees posts from other travelers
   - Discovers travel buddies

2. **Country-Based Discovery**
   - User traveling to Japan
   - Filters by `country_id` for Japan
   - Finds locals to connect with

3. **Post-Based Discovery**
   - User likes a specific post
   - Provides `post_id` parameter
   - Gets more posts from same social circle

---

## Algorithm Advantages

### **Why This Approach?**

1. **Variety:** Mixing 5 different criteria prevents boring feeds
2. **Relevance:** User's social circles get priority
3. **Engagement:** Popular content is more likely to interest users
4. **Freshness:** Recent posts keep feed current
5. **Quality:** Media-only ensures visual appeal
6. **Efficiency:** Pagination prevents overwhelming users

### **Compared to Simple Approaches:**

| Approach | Problem | This Solution |
|----------|---------|---------------|
| Random posts | No relevance | Prioritizes user's circles |
| Only recent | Misses popular content | Mixes recent + popular |
| Only popular | Stale content | Includes fresh posts |
| All posts | Overwhelming | Smart filtering + pagination |

---

## Performance Considerations

### **Optimizations:**

1. **Eager Loading:**
   ```php
   ->with(['user.profileImages', 'media', 'socialCircle'])
   ```
   Prevents N+1 query problems

2. **Indexed Queries:**
   - `social_circle_id` indexed
   - `created_at` indexed
   - `likes_count`, `comments_count`, `views_count` indexed

3. **Pagination:**
   - Limits results per page
   - Prevents loading entire dataset

4. **Caching Opportunities:**
   - Popular social circles (30 days)
   - User's social circles
   - Excluded user IDs

---

## Frontend Integration Tips

### **Infinite Scroll Implementation:**

```javascript
let page = 1;
let loading = false;
let hasMore = true;

async function loadMorePosts() {
  if (loading || !hasMore) return;
  
  loading = true;
  const response = await fetch('/api/v1/users/discover-by-post', {
    method: 'POST',
    body: JSON.stringify({
      social_circle_ids: [1, 3, 5],
      page: page,
      per_page: 20
    })
  });
  
  const data = await response.json();
  
  // Append posts to feed
  appendPosts(data.data);
  
  // Update pagination
  hasMore = data.pagination.has_more_pages;
  page++;
  loading = false;
}

// Trigger on scroll
window.addEventListener('scroll', () => {
  if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
    loadMorePosts();
  }
});
```

### **Filter Implementation:**

```javascript
function filterByCircle(circleId) {
  page = 1; // Reset pagination
  posts = []; // Clear existing posts
  
  loadPosts({
    social_circle_ids: [circleId],
    page: 1,
    per_page: 20
  });
}

function filterByCountry(countryId) {
  page = 1;
  posts = [];
  
  loadPosts({
    country_id: countryId,
    page: 1,
    per_page: 20
  });
}
```

---

## Error Handling

### **Common Errors:**

| Status | Error | Cause | Solution |
|--------|-------|-------|----------|
| 401 | User not authenticated | Missing/invalid token | Re-authenticate |
| 400 | Validation failed | Invalid parameters | Check input format |
| 404 | Post not found | Invalid post_id | Verify post exists |
| 500 | Server error | Database/logic error | Check logs |

### **Empty Results:**

```json
{
  "status": 0,
  "message": "No posts found for discovery",
  "data": [],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 0,
    "last_page": 0
  }
}
```

**Possible Reasons:**
- All users in circles already swiped
- No posts with media in selected circles
- All users blocked
- Country filter too restrictive

---

## Testing Scenarios

### **Test Cases:**

1. ✅ **Basic Discovery:** No parameters, returns mixed posts
2. ✅ **Social Circle Filter:** Specific circles, returns only those
3. ✅ **Country Filter:** Specific country, returns only those users
4. ✅ **Post-Based:** Provide post_id, returns same circle
5. ✅ **Pagination:** Multiple pages, no duplicates
6. ✅ **Empty Results:** All users swiped, returns empty
7. ✅ **Media Only:** Verify no text-only posts
8. ✅ **Exclusions:** Verify blocked/swiped users excluded
9. ✅ **Distribution:** Verify percentages roughly match
10. ✅ **Shuffle:** Verify posts are randomized

---

## Logging & Debugging

The function logs:
- Input parameters
- Social circles used
- Query results count
- Distribution breakdown
- Errors with stack traces

**Check logs for:**
```
getUsersByPost called
getUsersByPost results
getUsersByPost failed
```

---

## Future Enhancements

### **Potential Improvements:**

1. **Machine Learning:**
   - Learn user preferences
   - Adjust distribution weights
   - Personalized discovery

2. **A/B Testing:**
   - Test different distributions
   - Optimize engagement rates

3. **Real-Time Updates:**
   - WebSocket for new posts
   - Live feed updates

4. **Advanced Filters:**
   - Age range
   - Gender preference
   - Distance-based

5. **Caching:**
   - Cache popular posts
   - Redis for fast retrieval

---

## Summary

The `getUsersByPost` function is a sophisticated discovery system that:
- ✅ Shows engaging posts from potential connections
- ✅ Mixes fresh and popular content intelligently
- ✅ Prioritizes user's interests (social circles)
- ✅ Prevents duplicate discoveries
- ✅ Provides rich user context
- ✅ Scales with pagination
- ✅ Optimized for performance

**Perfect for:** Social discovery apps where content drives connections.
