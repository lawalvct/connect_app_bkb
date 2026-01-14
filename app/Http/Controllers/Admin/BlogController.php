<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        return view('admin.blogs.index');
    }

    public function getBlogs(Request $request)
    {
        $query = Blog::with(['creator', 'updater'])->active();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('excerpt', 'like', "%{$request->search}%");
            });
        }

        $blogs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($blogs);
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required_if:type,regular|nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'type' => 'required|in:regular,external',
            'external_url' => 'required_if:type,external|nullable|url',
            'status' => 'required|in:draft,published,archived',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = auth('admin')->id();

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blogs', 'public');
        }

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $blog = Blog::create($validated);

        AdminActivityLog::log('create', "Created blog: {$blog->title}", Blog::class, $blog->id);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog created successfully');
    }

    public function show(Blog $blog)
    {
        $blog->load(['creator', 'updater']);
        return view('admin.blogs.show', compact('blog'));
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required_if:type,regular|nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'type' => 'required|in:regular,external',
            'external_url' => 'required_if:type,external|nullable|url',
            'status' => 'required|in:draft,published,archived',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $changes = [];
        foreach (['title', 'status', 'type'] as $field) {
            if (isset($validated[$field]) && $blog->$field !== $validated[$field]) {
                $changes[$field] = ['old' => $blog->$field, 'new' => $validated[$field]];
            }
        }

        $validated['slug'] = Str::slug($validated['title']);
        $validated['updated_by'] = auth('admin')->id();

        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('blogs', 'public');
        }

        if ($validated['status'] === 'published' && !$blog->published_at) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        AdminActivityLog::log('update', "Updated blog: {$blog->title}", Blog::class, $blog->id, $changes);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated successfully');
    }

    public function destroy(Blog $blog)
    {
        $blog->update(['deleted_flag' => 'Y']);

        AdminActivityLog::log('delete', "Deleted blog: {$blog->title}", Blog::class, $blog->id);

        return response()->json(['success' => true, 'message' => 'Blog deleted successfully']);
    }

    public function updateStatus(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived',
        ]);

        $oldStatus = $blog->status;
        $blog->update([
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' && !$blog->published_at ? now() : $blog->published_at,
            'updated_by' => auth('admin')->id(),
        ]);

        AdminActivityLog::log('status_change', "Changed blog status from {$oldStatus} to {$validated['status']}: {$blog->title}", Blog::class, $blog->id);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }
}
