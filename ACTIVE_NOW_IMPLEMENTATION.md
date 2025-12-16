# Active Now Feature Implementation Guide

## Current Issue

The "Active Now" section in `RightSidebar.jsx` is **not actually working** as intended. It currently displays the first 3 connections from `getConnections()`, but this endpoint doesn't filter for online/active users - it just returns all connections. There's no real-time presence/online status being tracked.

```javascript
// Current implementation (NOT working)
const activeConnections = connectionsData?.data?.slice(0, 3) || [];
```

## Implementation Steps

### 1. Backend Setup (Laravel)

#### Add Database Column

Add `last_active_at` column to users table:

```php
// database/migrations/xxxx_add_last_active_at_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('last_active_at')->nullable();
});
```

#### Create Middleware to Track Activity

```php
// app/Http/Middleware/UpdateLastActive.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UpdateLastActive
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            Auth::user()->update(['last_active_at' => now()]);
        }
        return $next($request);
    }
}
```

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \App\Http\Middleware\UpdateLastActive::class,
    ],
];
```

#### Create API Endpoint

Add to `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/connections/online', [ConnectionController::class, 'getOnlineConnections']);
});
```

Add method to `ConnectionController.php`:

```php
public function getOnlineConnections(Request $request)
{
    $user = $request->user();

    // Get connections that were active in the last 5 minutes
    $connections = $user->connections()
        ->whereNotNull('last_active_at')
        ->where('last_active_at', '>=', now()->subMinutes(5))
        ->orderBy('last_active_at', 'desc')
        ->get();

    return response()->json(['data' => $connections]);
}
```

### 2. Frontend Setup (Next.js)

#### Create API Function

Add to `src/components/Utils/api.js`:

```javascript
export const getOnlineConnections = async () => {
  const response = await fetch('/api/getOnlineConnections', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.message || 'Failed to fetch online connections');
  }

  return response.json();
};
```

#### Create Next.js API Route

Create `src/pages/api/getOnlineConnections.js`:

```javascript
import { parse } from 'cookie';
import { API_URL } from '@/components/Utils/api';

export default async function handler(req, res) {
  if (req.method !== 'GET') {
    return res.status(405).json({ message: 'Method not allowed' });
  }

  const cookies = parse(req.headers.cookie || '');
  const token = cookies.token;

  if (!token) {
    return res.status(401).json({ message: 'Unauthorized: No token found' });
  }

  try {
    const response = await fetch(`${API_URL}/connections/online`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json();

    if (!response.ok) {
      return res.status(response.status).json(data);
    }

    return res.status(200).json(data);
  } catch (error) {
    console.error('Error fetching online connections:', error);
    return res.status(500).json({
      message: 'Server error fetching online connections',
      error: error.message,
    });
  }
}
```

#### Update RightSidebar Component

Update `src/components/Layout/RightSidebar.jsx`:

```javascript
import { useQuery } from '@tanstack/react-query';
import {
  explore,
  getConnections,
  getConnectionRequest,
  getOnlineConnections, // Add this import
} from '../Utils/api';

const RightSidebar = () => {
  // ... existing queries ...

  // Add query for online connections
  const { data: onlineConnectionsData } = useQuery({
    queryKey: ['onlineConnections'],
    queryFn: getOnlineConnections,
    refetchInterval: 30000, // Refetch every 30 seconds
    enabled: true,
  });

  // Replace the existing activeConnections line
  const activeConnections = onlineConnectionsData?.data?.slice(0, 3) || [];

  // ... rest of component
};
```

## Optional: Real-Time Updates with Pusher/Laravel Broadcasting

For instant real-time presence updates (instead of 30-second polling):

### Backend: Laravel Broadcasting Setup

1. Install Pusher:

```bash
composer require pusher/pusher-php-server
```

2. Configure `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

3. Create Presence Channel in `routes/channels.php`:

```php
Broadcast::channel('online', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'profile_url' => $user->profile_url,
        ];
    }
});
```

### Frontend: Pusher Client Setup

1. Install Pusher JS:

```bash
npm install pusher-js
```

2. Update RightSidebar with real-time presence:

```javascript
import { useEffect, useState } from 'react';
import Pusher from 'pusher-js';

const RightSidebar = () => {
  const [onlineUserIds, setOnlineUserIds] = useState(new Set());

  // Initialize Pusher
  useEffect(() => {
    const pusher = new Pusher('YOUR_PUSHER_KEY', {
      cluster: 'YOUR_CLUSTER',
      authEndpoint: '/api/broadcasting/auth',
    });

    const channel = pusher.subscribe('presence-online');

    // When subscription succeeds, get all current members
    channel.bind('pusher:subscription_succeeded', (members) => {
      const ids = new Set();
      members.each((member) => ids.add(member.id));
      setOnlineUserIds(ids);
    });

    // When a member joins
    channel.bind('pusher:member_added', (member) => {
      setOnlineUserIds((prev) => new Set([...prev, member.id]));
    });

    // When a member leaves
    channel.bind('pusher:member_removed', (member) => {
      setOnlineUserIds((prev) => {
        const updated = new Set(prev);
        updated.delete(member.id);
        return updated;
      });
    });

    return () => {
      channel.unbind_all();
      pusher.unsubscribe('presence-online');
      pusher.disconnect();
    };
  }, []);

  // Filter connections by online status
  const activeConnections =
    connectionsData?.data
      ?.filter((connection) => onlineUserIds.has(connection.id))
      .slice(0, 3) || [];

  // ... rest of component
};
```

## Summary

### Basic Implementation (Recommended for MVP)

- ✅ Add `last_active_at` column to users table
- ✅ Create middleware to track user activity
- ✅ Create `/connections/online` API endpoint
- ✅ Update frontend to fetch and display online connections
- ✅ Use 30-second polling for updates

### Advanced Implementation (Real-time)

- 🚀 Set up Laravel Broadcasting with Pusher
- 🚀 Create presence channels
- 🚀 Integrate Pusher JS client
- 🚀 Get instant online/offline updates

## Testing

1. Log in with multiple accounts
2. Check if users appear in "Active Now" when browsing
3. Test if users disappear after 5 minutes of inactivity
4. Verify real-time updates (if using Pusher)

## Performance Considerations

- The 5-minute activity window can be adjusted based on your needs
- Consider caching online users list in Redis for better performance
- Polling interval can be increased to reduce server load (currently 30s)
- For large user bases, consider pagination or limiting results
