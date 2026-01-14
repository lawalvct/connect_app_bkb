@extends('admin.layouts.app')

@section('title', 'Create Blog')
@section('page-title', 'Create Blog')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data" x-data="{ type: 'regular' }">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required
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
                <input type="url" name="external_url" value="{{ old('external_url') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('external_url')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                <input type="file" name="featured_image" accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('featured_image')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                <textarea name="excerpt" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('excerpt') }}</textarea>
                @error('excerpt')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div x-show="type === 'regular'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                <textarea id="blog-content" name="content">{{ old('content') }}</textarea>
                @error('content')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title') }}" maxlength="255"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                        @error('meta_title')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                        <textarea name="meta_description" rows="3" maxlength="500"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('meta_description') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                        @error('meta_description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}" maxlength="255"
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
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">Create Blog</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/xxeafvl0or3eww2lkza2cpthrrr8emm11v8xzizxp2278vah/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
