<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends BaseController
{
    public function index(Request $request)
    {
        $query = Blog::with('creator:id,name')->active()->published();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('excerpt', 'like', "%{$request->search}%");
            });
        }

        $blogs = $query->orderBy('published_at', 'desc')->paginate(10);

        return $this->sendResponse('Blogs retrieved successfully', ['blogs' => $blogs]);
    }

    public function show($slug)
    {
        $blog = Blog::with('creator:id,name')->active()->published()->where('slug', $slug)->first();

        if (!$blog) {
            return $this->sendError('Blog not found', [], 404);
        }

        $blog->incrementViews();

        return $this->sendResponse('Blog retrieved successfully', ['blog' => $blog]);
    }

    public function latest()
    {
        $blogs = Blog::with('creator:id,name')
            ->active()
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        return $this->sendResponse('Latest blogs retrieved successfully', ['blogs' => $blogs]);
    }
}
