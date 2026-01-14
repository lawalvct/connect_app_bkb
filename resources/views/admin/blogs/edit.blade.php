@extends('admin.layouts.app')

@section('title', 'Edit Blog')
@section('page-title', 'Edit Blog')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.blogs.update', $blog) }}" method="POST" enctype="multipart/form-data" x-data="{ type: '{{ $blog->type }}' }">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title', $blog->title) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('title')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Blog Type</label>
                <select name="type" x-model="type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="regular">Regular Blog</option>
                    <option value="external">External Link</option>
                </select>
            </div>

            <div x-show="type === 'external'">
                <label class="block text-sm font-medium text-gray-700 mb-2">External URL</label>
                <input type="url" name="external_url" value="{{ old('external_url', $blog->external_url) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('external_url')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                @if($blog->featured_image)
                    <img src="{{ asset('storage/' . $blog->featured_image) }}" class="w-32 h-32 object-cover rounded mb-2" alt="">
                @endif
                <input type="file" name="featured_image" accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('featured_image')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                <textarea name="excerpt" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('excerpt', $blog->excerpt) }}</textarea>
                @error('excerpt')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div x-show="type === 'regular'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                <textarea id="blog-content" name="content">{{ old('content', $blog->content) }}</textarea>
                @error('content')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="draft" {{ $blog->status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $blog->status === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ $blog->status === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $blog->meta_title) }}" maxlength="255"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                        @error('meta_title')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                        <textarea name="meta_description" rows="3" maxlength="500"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('meta_description', $blog->meta_description) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                        @error('meta_description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $blog->meta_keywords) }}" maxlength="255"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                        @error('meta_keywords')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.blogs.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">Update Blog</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#blog-content',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
</script>
@endpush
