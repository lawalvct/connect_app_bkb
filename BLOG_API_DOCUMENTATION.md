# Blog API Documentation

## Overview
Public API endpoints for accessing blog content. No authentication required.

**Base URL:** `/api/v1/blogs`

---

## Endpoints

### 1. Get All Blogs (Paginated)

**Endpoint:** `GET /api/v1/blogs`

**Description:** Retrieve all published blogs with pagination and search functionality.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search in title and excerpt |
| `page` | integer | No | Page number (default: 1) |

**Request Example:**
```bash
GET /api/v1/blogs?search=technology&page=1
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Blogs retrieved successfully",
  "data": {
    "blogs": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "title": "Getting Started with Laravel",
          "slug": "getting-started-with-laravel",
          "excerpt": "Learn the basics of Laravel framework",
          "content": "Full blog content here...",
          "featured_image": "blogs/laravel-guide.jpg",
          "type": "regular",
          "external_url": null,
          "status": "published",
          "views_count": 1250,
          "meta_title": "Laravel Tutorial - Complete Guide",
          "meta_description": "A comprehensive guide to getting started with Laravel PHP framework",
          "meta_keywords": "laravel, php, tutorial, web development",
          "published_at": "2025-01-15T10:30:00.000000Z",
          "created_at": "2025-01-14T08:00:00.000000Z",
          "updated_at": "2025-01-15T10:30:00.000000Z",
          "creator": {
            "id": 1,
            "name": "John Doe"
          }
        },
        {
          "id": 2,
          "title": "Top 10 Web Design Trends",
          "slug": "top-10-web-design-trends",
          "excerpt": "Discover the latest trends in web design",
          "content": null,
          "featured_image": "blogs/design-trends.jpg",
          "type": "external",
          "external_url": "https://example.com/design-trends",
          "status": "published",
          "views_count": 890,
          "meta_title": "Web Design Trends 2025",
          "meta_description": "Explore the top web design trends shaping 2025",
          "meta_keywords": "web design, trends, UI, UX",
          "published_at": "2025-01-14T15:00:00.000000Z",
          "created_at": "2025-01-14T12:00:00.000000Z",
          "updated_at": "2025-01-14T15:00:00.000000Z",
          "creator": {
            "id": 2,
            "name": "Jane Smith"
          }
        }
      ],
      "first_page_url": "http://api.example.com/api/v1/blogs?page=1",
      "from": 1,
      "last_page": 5,
      "last_page_url": "http://api.example.com/api/v1/blogs?page=5",
      "next_page_url": "http://api.example.com/api/v1/blogs?page=2",
      "path": "http://api.example.com/api/v1/blogs",
      "per_page": 10,
      "prev_page_url": null,
      "to": 10,
      "total": 50
    }
  }
}
```

---

### 2. Get Latest Blogs

**Endpoint:** `GET /api/v1/blogs/latest`

**Description:** Retrieve the 5 most recent published blogs.

**Request Example:**
```bash
GET /api/v1/blogs/latest
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Latest blogs retrieved successfully",
  "data": {
    "blogs": [
      {
        "id": 5,
        "title": "AI in Modern Development",
        "slug": "ai-in-modern-development",
        "excerpt": "How AI is transforming software development",
        "content": "Artificial intelligence is revolutionizing...",
        "featured_image": "blogs/ai-development.jpg",
        "type": "regular",
        "external_url": null,
        "status": "published",
        "views_count": 450,
        "meta_title": "AI in Software Development",
        "meta_description": "Discover how AI is changing the development landscape",
        "meta_keywords": "AI, development, machine learning",
        "published_at": "2025-01-15T14:00:00.000000Z",
        "created_at": "2025-01-15T10:00:00.000000Z",
        "updated_at": "2025-01-15T14:00:00.000000Z",
        "creator": {
          "id": 1,
          "name": "John Doe"
        }
      }
    ]
  }
}
```

---

### 3. Get Single Blog by Slug

**Endpoint:** `GET /api/v1/blogs/{slug}`

**Description:** Retrieve a single blog by its slug. Automatically increments view count.

**URL Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Blog slug (URL-friendly identifier) |

**Request Example:**
```bash
GET /api/v1/blogs/getting-started-with-laravel
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Blog retrieved successfully",
  "data": {
    "blog": {
      "id": 1,
      "title": "Getting Started with Laravel",
      "slug": "getting-started-with-laravel",
      "excerpt": "Learn the basics of Laravel framework",
      "content": "# Introduction\n\nLaravel is a powerful PHP framework...\n\n## Installation\n\nTo install Laravel, run:\n```bash\ncomposer create-project laravel/laravel my-app\n```",
      "featured_image": "blogs/laravel-guide.jpg",
      "type": "regular",
      "external_url": null,
      "status": "published",
      "views_count": 1251,
      "meta_title": "Laravel Tutorial - Complete Guide",
      "meta_description": "A comprehensive guide to getting started with Laravel PHP framework",
      "meta_keywords": "laravel, php, tutorial, web development",
      "published_at": "2025-01-15T10:30:00.000000Z",
      "created_at": "2025-01-14T08:00:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z",
      "creator": {
        "id": 1,
        "name": "John Doe"
      }
    }
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Blog not found",
  "data": []
}
```

---

## Blog Types

### Regular Blog
- `type`: `"regular"`
- Contains full `content` field
- `external_url` is `null`
- Content is displayed within the app

### External Link Blog
- `type`: `"external"`
- `content` is `null`
- Contains `external_url` with link to external website
- Featured image acts as a banner/preview

---

## Image URLs

Featured images are stored in the `storage/blogs/` directory. To display images:

```javascript
const imageUrl = `${BASE_URL}/storage/${blog.featured_image}`;
// Example: https://api.example.com/storage/blogs/laravel-guide.jpg
```

---

## Frontend Implementation Examples

### React/Next.js Example

```typescript
// types/blog.ts
export interface Blog {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string | null;
  featured_image: string | null;
  type: 'regular' | 'external';
  external_url: string | null;
  status: string;
  views_count: number;
  meta_title: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  published_at: string;
  creator: {
    id: number;
    name: string;
  };
}

// services/blogService.ts
const API_BASE = 'https://api.example.com/api/v1';

export const blogService = {
  async getBlogs(page = 1, search = '') {
    const params = new URLSearchParams({ page: page.toString() });
    if (search) params.append('search', search);
    
    const response = await fetch(`${API_BASE}/blogs?${params}`);
    return response.json();
  },

  async getLatestBlogs() {
    const response = await fetch(`${API_BASE}/blogs/latest`);
    return response.json();
  },

  async getBlogBySlug(slug: string) {
    const response = await fetch(`${API_BASE}/blogs/${slug}`);
    return response.json();
  }
};

// components/BlogCard.tsx
export function BlogCard({ blog }: { blog: Blog }) {
  const imageUrl = blog.featured_image 
    ? `${API_BASE}/storage/${blog.featured_image}`
    : '/placeholder.jpg';

  if (blog.type === 'external') {
    return (
      <a href={blog.external_url} target="_blank" rel="noopener noreferrer">
        <img src={imageUrl} alt={blog.title} />
        <h3>{blog.title}</h3>
        <p>{blog.excerpt}</p>
        <span>Read on external site →</span>
      </a>
    );
  }

  return (
    <Link href={`/blog/${blog.slug}`}>
      <img src={imageUrl} alt={blog.title} />
      <h3>{blog.title}</h3>
      <p>{blog.excerpt}</p>
      <span>{blog.views_count} views</span>
    </Link>
  );
}
```

### Vue.js Example

```javascript
// composables/useBlog.js
export function useBlog() {
  const API_BASE = 'https://api.example.com/api/v1';

  const getBlogs = async (page = 1, search = '') => {
    const params = new URLSearchParams({ page });
    if (search) params.append('search', search);
    
    const response = await fetch(`${API_BASE}/blogs?${params}`);
    return await response.json();
  };

  const getBlogBySlug = async (slug) => {
    const response = await fetch(`${API_BASE}/blogs/${slug}`);
    return await response.json();
  };

  return { getBlogs, getBlogBySlug };
}
```

---

## SEO Implementation

Use the meta fields for SEO optimization:

```html
<!-- Regular Blog Page -->
<head>
  <title>{blog.meta_title || blog.title}</title>
  <meta name="description" content="{blog.meta_description || blog.excerpt}" />
  <meta name="keywords" content="{blog.meta_keywords}" />
  
  <!-- Open Graph -->
  <meta property="og:title" content="{blog.meta_title || blog.title}" />
  <meta property="og:description" content="{blog.meta_description || blog.excerpt}" />
  <meta property="og:image" content="{imageUrl}" />
  <meta property="og:type" content="article" />
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{blog.meta_title || blog.title}" />
  <meta name="twitter:description" content="{blog.meta_description || blog.excerpt}" />
  <meta name="twitter:image" content="{imageUrl}" />
</head>
```

---

## Notes

- All endpoints are **public** (no authentication required)
- Only **published** blogs are returned
- View count increments automatically when fetching single blog
- Images are served from `/storage/` directory
- Pagination returns 10 items per page
- Search is case-insensitive and searches both title and excerpt
- External blogs redirect users to external URLs
- Regular blogs display content within the app
