# Profile Upload Likes API - Frontend Integration Guide

## Overview
This API allows users to like/unlike profile photos and videos. Each upload tracks its like count and maintains a list of users who liked it.

---

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer {your_access_token}
```

---

## API Endpoints

### 1. Toggle Like/Unlike
**Endpoint:** `POST /api/v1/profile/uploads/{uploadId}/like`

**Description:** Like an upload if not liked, unlike if already liked (toggle behavior)

**Request:**
```bash
POST {{host}}/api/v1/profile/uploads/123/like
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Upload liked successfully",
    "data": {
        "liked": true,
        "like_count": 42
    }
}
```

**Success Response - Unlike (200):**
```json
{
    "success": true,
    "message": "Upload unliked successfully",
    "data": {
        "liked": false,
        "like_count": 41
    }
}
```

**Error Response (404):**
```json
{
    "success": false,
    "message": "Profile upload not found"
}
```

---

### 2. Get Users Who Liked an Upload
**Endpoint:** `GET /api/v1/profile/uploads/{uploadId}/likes`

**Description:** Get paginated list of users who liked a specific upload

**Query Parameters:**
- `per_page` (optional, default: 20) - Number of items per page
- `page` (optional, default: 1) - Current page number

**Request:**
```bash
GET {{host}}/api/v1/profile/uploads/123/likes?per_page=20&page=1
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Likes retrieved successfully",
    "data": {
        "upload_id": 123,
        "total_likes": 42,
        "likes": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "profile_url": "https://example.com/profiles/johndoe.jpg"
            },
            {
                "id": 2,
                "name": "Jane Smith",
                "username": "janesmith",
                "profile_url": "https://example.com/profiles/janesmith.jpg"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 42,
            "last_page": 3,
            "has_more": true
        }
    }
}
```

---

### 3. Check Like Status
**Endpoint:** `GET /api/v1/profile/uploads/{uploadId}/like-status`

**Description:** Check if the authenticated user has liked a specific upload

**Request:**
```bash
GET {{host}}/api/v1/profile/uploads/123/like-status
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Like status retrieved successfully",
    "data": {
        "upload_id": 123,
        "is_liked": true,
        "like_count": 42
    }
}
```

---

### 4. Get My Liked Uploads
**Endpoint:** `GET /api/v1/profile/uploads/my-likes`

**Description:** Get all uploads that the authenticated user has liked

**Query Parameters:**
- `per_page` (optional, default: 20) - Number of items per page
- `page` (optional, default: 1) - Current page number

**Request:**
```bash
GET {{host}}/api/v1/profile/uploads/my-likes?per_page=20&page=1
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Liked uploads retrieved successfully",
    "data": {
        "uploads": [
            {
                "id": 123,
                "user_id": 456,
                "file_name": "photo_123.jpg",
                "file_url": "https://example.com/uploads/photo_123.jpg",
                "file_type": "image",
                "caption": "Beautiful sunset",
                "like_count": 42,
                "liked_at": "2025-12-13T15:30:00.000000Z",
                "created_at": "2025-12-10T10:00:00.000000Z",
                "user": {
                    "id": 456,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_url": "https://example.com/profiles/janesmith.jpg"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 15,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

---

## Next.js Implementation Examples

### 1. API Service (lib/api/profileUploads.ts)

```typescript
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL;

interface ToggleLikeResponse {
    success: boolean;
    message: string;
    data: {
        liked: boolean;
        like_count: number;
    };
}

interface LikeStatusResponse {
    success: boolean;
    message: string;
    data: {
        upload_id: number;
        is_liked: boolean;
        like_count: number;
    };
}

interface User {
    id: number;
    name: string;
    username: string;
    profile_url: string;
}

interface LikesResponse {
    success: boolean;
    message: string;
    data: {
        upload_id: number;
        total_likes: number;
        likes: User[];
        pagination: {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
            has_more: boolean;
        };
    };
}

export const profileUploadApi = {
    // Toggle like/unlike
    toggleLike: async (uploadId: number, token: string): Promise<ToggleLikeResponse> => {
        const response = await fetch(`${API_BASE_URL}/api/v1/profile/uploads/${uploadId}/like`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });
        return response.json();
    },

    // Check like status
    checkLikeStatus: async (uploadId: number, token: string): Promise<LikeStatusResponse> => {
        const response = await fetch(`${API_BASE_URL}/api/v1/profile/uploads/${uploadId}/like-status`, {
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });
        return response.json();
    },

    // Get users who liked
    getLikes: async (uploadId: number, token: string, page = 1, perPage = 20): Promise<LikesResponse> => {
        const response = await fetch(
            `${API_BASE_URL}/api/v1/profile/uploads/${uploadId}/likes?page=${page}&per_page=${perPage}`,
            {
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
            }
        );
        return response.json();
    },

    // Get my liked uploads
    getMyLikedUploads: async (token: string, page = 1, perPage = 20) => {
        const response = await fetch(
            `${API_BASE_URL}/api/v1/profile/uploads/my-likes?page=${page}&per_page=${perPage}`,
            {
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
            }
        );
        return response.json();
    },
};
```

---

### 2. Like Button Component (components/LikeButton.tsx)

```typescript
'use client';

import { useState } from 'react';
import { Heart } from 'lucide-react';
import { profileUploadApi } from '@/lib/api/profileUploads';
import { useAuth } from '@/hooks/useAuth';

interface LikeButtonProps {
    uploadId: number;
    initialLiked: boolean;
    initialCount: number;
    onLikeChange?: (liked: boolean, count: number) => void;
}

export default function LikeButton({ 
    uploadId, 
    initialLiked, 
    initialCount,
    onLikeChange 
}: LikeButtonProps) {
    const { token } = useAuth();
    const [isLiked, setIsLiked] = useState(initialLiked);
    const [likeCount, setLikeCount] = useState(initialCount);
    const [isLoading, setIsLoading] = useState(false);

    const handleToggleLike = async () => {
        if (!token || isLoading) return;

        setIsLoading(true);
        
        // Optimistic update
        const previousLiked = isLiked;
        const previousCount = likeCount;
        setIsLiked(!isLiked);
        setLikeCount(prev => isLiked ? prev - 1 : prev + 1);

        try {
            const response = await profileUploadApi.toggleLike(uploadId, token);
            
            if (response.success) {
                setIsLiked(response.data.liked);
                setLikeCount(response.data.like_count);
                onLikeChange?.(response.data.liked, response.data.like_count);
            } else {
                // Revert on error
                setIsLiked(previousLiked);
                setLikeCount(previousCount);
            }
        } catch (error) {
            console.error('Failed to toggle like:', error);
            // Revert on error
            setIsLiked(previousLiked);
            setLikeCount(previousCount);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <button
            onClick={handleToggleLike}
            disabled={isLoading}
            className="flex items-center gap-2 transition-all hover:scale-110"
        >
            <Heart
                className={`w-6 h-6 ${
                    isLiked 
                        ? 'fill-red-500 text-red-500' 
                        : 'text-gray-400 hover:text-red-500'
                }`}
            />
            <span className="text-sm font-medium">{likeCount}</span>
        </button>
    );
}
```

---

### 3. Upload Card Component (components/UploadCard.tsx)

```typescript
'use client';

import Image from 'next/image';
import LikeButton from './LikeButton';
import { useState } from 'react';

interface Upload {
    id: number;
    file_url: string;
    file_type: 'image' | 'video';
    caption?: string;
    like_count: number;
    is_liked?: boolean;
}

interface UploadCardProps {
    upload: Upload;
}

export default function UploadCard({ upload }: UploadCardProps) {
    const [currentLikeCount, setCurrentLikeCount] = useState(upload.like_count);
    const [isLiked, setIsLiked] = useState(upload.is_liked || false);

    const handleLikeChange = (liked: boolean, count: number) => {
        setIsLiked(liked);
        setCurrentLikeCount(count);
    };

    return (
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
            {upload.file_type === 'image' ? (
                <Image
                    src={upload.file_url}
                    alt={upload.caption || 'Upload'}
                    width={400}
                    height={400}
                    className="w-full h-auto object-cover"
                />
            ) : (
                <video
                    src={upload.file_url}
                    controls
                    className="w-full h-auto"
                />
            )}
            
            <div className="p-4">
                {upload.caption && (
                    <p className="text-sm text-gray-700 mb-3">{upload.caption}</p>
                )}
                
                <div className="flex items-center justify-between">
                    <LikeButton
                        uploadId={upload.id}
                        initialLiked={isLiked}
                        initialCount={currentLikeCount}
                        onLikeChange={handleLikeChange}
                    />
                </div>
            </div>
        </div>
    );
}
```

---

### 4. Likes Modal Component (components/LikesModal.tsx)

```typescript
'use client';

import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import { profileUploadApi } from '@/lib/api/profileUploads';
import { useAuth } from '@/hooks/useAuth';
import Image from 'next/image';

interface User {
    id: number;
    name: string;
    username: string;
    profile_url: string;
}

interface LikesModalProps {
    uploadId: number;
    isOpen: boolean;
    onClose: () => void;
}

export default function LikesModal({ uploadId, isOpen, onClose }: LikesModalProps) {
    const { token } = useAuth();
    const [likes, setLikes] = useState<User[]>([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);

    useEffect(() => {
        if (isOpen && token) {
            loadLikes();
        }
    }, [isOpen, uploadId, page]);

    const loadLikes = async () => {
        if (!token) return;
        
        setLoading(true);
        try {
            const response = await profileUploadApi.getLikes(uploadId, token, page, 20);
            
            if (response.success) {
                setLikes(prev => page === 1 ? response.data.likes : [...prev, ...response.data.likes]);
                setHasMore(response.data.pagination.has_more);
            }
        } catch (error) {
            console.error('Failed to load likes:', error);
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg w-full max-w-md max-h-[80vh] flex flex-col">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b">
                    <h2 className="text-lg font-semibold">Likes</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Likes List */}
                <div className="flex-1 overflow-y-auto p-4">
                    {likes.map((user) => (
                        <div key={user.id} className="flex items-center gap-3 py-2">
                            <Image
                                src={user.profile_url || '/default-avatar.png'}
                                alt={user.name}
                                width={40}
                                height={40}
                                className="rounded-full"
                            />
                            <div>
                                <p className="font-medium">{user.name}</p>
                                <p className="text-sm text-gray-500">@{user.username}</p>
                            </div>
                        </div>
                    ))}

                    {loading && <p className="text-center py-4">Loading...</p>}

                    {hasMore && !loading && (
                        <button
                            onClick={() => setPage(prev => prev + 1)}
                            className="w-full py-2 text-blue-600 hover:text-blue-700"
                        >
                            Load More
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
```

---

### 5. My Liked Uploads Page (app/profile/liked/page.tsx)

```typescript
'use client';

import { useState, useEffect } from 'react';
import { profileUploadApi } from '@/lib/api/profileUploads';
import { useAuth } from '@/hooks/useAuth';
import UploadCard from '@/components/UploadCard';

export default function MyLikedUploadsPage() {
    const { token } = useAuth();
    const [uploads, setUploads] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);

    useEffect(() => {
        if (token) {
            loadLikedUploads();
        }
    }, [token, page]);

    const loadLikedUploads = async () => {
        if (!token) return;

        setLoading(true);
        try {
            const response = await profileUploadApi.getMyLikedUploads(token, page, 20);
            
            if (response.success) {
                setUploads(prev => page === 1 ? response.data.uploads : [...prev, ...response.data.uploads]);
                setHasMore(response.data.pagination.has_more);
            }
        } catch (error) {
            console.error('Failed to load liked uploads:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-2xl font-bold mb-6">My Liked Photos & Videos</h1>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {uploads.map((upload) => (
                    <UploadCard key={upload.id} upload={upload} />
                ))}
            </div>

            {loading && <p className="text-center py-8">Loading...</p>}

            {hasMore && !loading && (
                <button
                    onClick={() => setPage(prev => prev + 1)}
                    className="w-full mt-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Load More
                </button>
            )}

            {!loading && uploads.length === 0 && (
                <p className="text-center text-gray-500 py-8">
                    You haven't liked any uploads yet
                </p>
            )}
        </div>
    );
}
```

---

## Key Features

✅ **Toggle Like/Unlike** - Single endpoint for both actions  
✅ **Real-time Like Count** - Updates immediately  
✅ **Optimistic Updates** - UI updates before API response  
✅ **Pagination Support** - Handle large lists efficiently  
✅ **Error Handling** - Graceful fallbacks and reverts  
✅ **Loading States** - Prevent duplicate requests  
✅ **Responsive Design** - Works on all screen sizes  

---

## Error Handling

All endpoints return consistent error format:

```json
{
    "success": false,
    "message": "Error message here",
    "errors": "Detailed error information"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `401` - Unauthorized (invalid/missing token)
- `404` - Upload not found
- `500` - Server error

---

## Best Practices

1. **Optimistic Updates**: Update UI immediately, revert on error
2. **Debouncing**: Prevent rapid like/unlike spam
3. **Caching**: Cache like status to reduce API calls
4. **Error Messages**: Show user-friendly error messages
5. **Loading States**: Disable buttons during API calls
6. **Pagination**: Load more data as user scrolls

---

## Testing Checklist

- [ ] Like an upload
- [ ] Unlike an upload
- [ ] View list of users who liked
- [ ] Check like status on page load
- [ ] View my liked uploads
- [ ] Pagination works correctly
- [ ] Error handling works
- [ ] Loading states display properly
- [ ] Optimistic updates work
- [ ] Works on mobile devices

---

## Support

For issues or questions, contact the backend team or check the API logs.
