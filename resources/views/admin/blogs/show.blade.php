@extends('admin.layouts.app')

@section('title', 'View Blog')
@section('page-title', 'View Blog')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $blog->title }}</h1>
                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                    <span><i class="fas fa-user mr-1"></i>{{ $blog->creator->name }}</span>
                    <span><i class="fas fa-calendar mr-1"></i>{{ $blog->created_at->format('M d, Y') }}</span>
                    <span><i class="fas fa-eye mr-1"></i>{{ $blog->views_count }} views</span>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.blogs.edit', $blog) }}" 
                   class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('admin.blogs.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Back</a>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <span class="text-sm font-medium text-gray-700">Type:</span>
                <span class="ml-2 px-3 py-1 text-xs rounded-full {{ $blog->type === 'external' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                    {{ $blog->type === 'external' ? 'External Link' : 'Regular Blog' }}
                </span>
            </div>

            <div>
                <span class="text-sm font-medium text-gray-700">Status:</span>
                <span class="ml-2 px-3 py-1 text-xs rounded-full 
                    {{ $blog->status === 'published' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $blog->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $blog->status === 'archived' ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ ucfirst($blog->status) }}
                </span>
            </div>

            @if($blog->featured_image)
            <div>
                <span class="text-sm font-medium text-gray-700 block mb-2">Featured Image:</span>
                <img src="{{ asset('storage/' . $blog->featured_image) }}" class="max-w-md rounded-lg shadow" alt="{{ $blog->title }}">
            </div>
            @endif

            @if($blog->excerpt)
            <div>
                <span class="text-sm font-medium text-gray-700 block mb-2">Excerpt:</span>
                <p class="text-gray-600">{{ $blog->excerpt }}</p>
            </div>
            @endif

            @if($blog->type === 'external')
            <div>
                <span class="text-sm font-medium text-gray-700 block mb-2">External URL:</span>
                <a href="{{ $blog->external_url }}" target="_blank" class="text-primary hover:underline">
                    {{ $blog->external_url }} <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </div>
            @else
            <div>
                <span class="text-sm font-medium text-gray-700 block mb-2">Content:</span>
                <div class="prose max-w-none text-gray-700">
                    {!! nl2br(e($blog->content)) !!}
                </div>
            </div>
            @endif

            @if($blog->published_at)
            <div>
                <span class="text-sm font-medium text-gray-700">Published At:</span>
                <span class="ml-2 text-gray-600">{{ $blog->published_at->format('M d, Y H:i') }}</span>
            </div>
            @endif

            @if($blog->updater)
            <div>
                <span class="text-sm font-medium text-gray-700">Last Updated By:</span>
                <span class="ml-2 text-gray-600">{{ $blog->updater->name }} on {{ $blog->updated_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
