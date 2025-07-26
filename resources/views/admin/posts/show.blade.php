@extends('admin.layouts.app')

@section('title', 'Post Details')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Post Details</h1>
            <p class="text-gray-600">View and manage post information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.posts.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Posts
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <!-- Post Content -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Post Content</h3>
                        <div class="flex items-center space-x-2">
                            @if($post->status === 'published')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i> Published
                                </span>
                            @elseif($post->status === 'draft')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-edit mr-1"></i> Draft
                                </span>
                            @elseif($post->status === 'scheduled')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-clock mr-1"></i> Scheduled
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="prose max-w-none">
                        @if($post->content)
                            <p class="text-gray-900 whitespace-pre-wrap">{{ $post->content }}</p>
                        @else
                            <p class="text-gray-500 italic">No text content</p>
                        @endif
                    </div>

                    @if($post->media && $post->media->count() > 0)
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Media</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($post->media as $media)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        @if($media->type === 'image')
                                            <img src="{{ $media->file_url }}"
                                                 alt="{{ $media->alt_text }}"
                                                 class="w-full h-48 object-cover">
                                        @elseif($media->type === 'video')
                                            <video controls class="w-full h-48">
                                                <source src="{{ $media->file_url }}" type="{{ $media->mime_type }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @else
                                            <div class="flex items-center justify-center h-48 bg-gray-100">
                                                <div class="text-center">
                                                    <i class="fas fa-file text-4xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">{{ $media->original_name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $media->type }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="p-3 bg-gray-50">
                                            <p class="text-xs text-gray-600">
                                                {{ $media->original_name }} ({{ number_format($media->file_size / 1024, 1) }} KB)
                                            </p>
                                            @if($media->alt_text)
                                                <p class="text-xs text-gray-500 mt-1">Alt: {{ $media->alt_text }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($post->location)
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-2">Location</h4>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-sm text-gray-900">{{ $post->location['address'] ?? 'Unknown location' }}</p>
                                @if(isset($post->location['lat']) && isset($post->location['lng']))
                                    <p class="text-xs text-gray-500 mt-1">
                                        Coordinates: {{ $post->location['lat'] }}, {{ $post->location['lng'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Comments Section -->
            @if($post->comments && $post->comments->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Comments ({{ $post->comments_count }})</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($post->comments->take(5) as $comment)
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img src="{{ $comment->user->avatar_url ?? '/images/default-avatar.png' }}"
                                     alt="{{ $comment->user->name }}"
                                     class="h-8 w-8 rounded-full">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</p>
                                </div>
                                <p class="text-sm text-gray-700 mt-1">{{ $comment->content }}</p>
                                @if($comment->likes_count > 0)
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-heart text-red-400 text-xs mr-1"></i>
                                        <span class="text-xs text-gray-500">{{ $comment->likes_count }} likes</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Reports Section -->
            @if($post->reports && $post->reports->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-red-600">
                        <i class="fas fa-flag mr-2"></i>
                        Reports ({{ $post->reports->count() }})
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($post->reports as $report)
                        <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">{{ $report->user->name }}</p>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ ucfirst(str_replace('_', ' ', $report->reason)) }}
                                        </span>
                                        <p class="text-xs text-gray-500">{{ $report->created_at->diffForHumans() }}</p>
                                    </div>
                                    @if($report->description)
                                        <p class="text-sm text-gray-700 mt-2">{{ $report->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                           ($report->status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <!-- Post Information -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Post Information</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Author</dt>
                            <dd class="mt-1">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $post->user->avatar_url ?? '/images/default-avatar.png' }}"
                                         alt="{{ $post->user->name }}"
                                         class="h-8 w-8 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $post->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $post->user->email }}</p>
                                    </div>
                                </div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Social Circle</dt>
                            <dd class="mt-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-4 h-4 rounded-full"
                                         style="background-color: {{ $post->socialCircle->color ?? '#6B7280' }}"></div>
                                    <span class="text-sm text-gray-900">{{ $post->socialCircle->name }}</span>
                                </div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Post Type</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $post->type === 'text' ? 'bg-gray-100 text-gray-800' :
                                       ($post->type === 'image' ? 'bg-blue-100 text-blue-800' :
                                        ($post->type === 'video' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800')) }}">
                                    {{ ucfirst($post->type) }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $post->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>

                        @if($post->published_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Published</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $post->published_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                        @endif

                        @if($post->scheduled_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Scheduled For</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $post->scheduled_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                        @endif

                        @if($post->is_edited)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Edited</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $post->edited_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Engagement Stats -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Engagement</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $post->likes_count }}</div>
                            <div class="text-sm text-red-600">Likes</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $post->comments_count }}</div>
                            <div class="text-sm text-blue-600">Comments</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $post->shares_count }}</div>
                            <div class="text-sm text-green-600">Shares</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $post->views_count }}</div>
                            <div class="text-sm text-purple-600">Views</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post Actions -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    @if($post->is_published)
                        <button onclick="updatePostStatus({{ $post->id }}, 'draft')"
                                class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Convert to Draft
                        </button>
                    @else
                        <button onclick="updatePostStatus({{ $post->id }}, 'published')"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-check mr-2"></i>
                            Publish Post
                        </button>
                    @endif

                    <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this post?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Post
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Likes -->
            @if($post->likes && $post->likes->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Likes</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($post->likes->take(5) as $like)
                        <div class="flex items-center space-x-3">
                            <img src="{{ $like->user->avatar_url ?? '/images/default-avatar.png' }}"
                                 alt="{{ $like->user->name }}"
                                 class="h-6 w-6 rounded-full">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ $like->user->name }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="text-sm">{{ $like->reaction_emoji }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    async function updatePostStatus(postId, status) {
        try {
            const response = await fetch(`/admin/api/posts/${postId}/status`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: status })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                location.reload(); // Refresh the page to see the changes
            } else {
                alert(data.message || 'Failed to update post status');
            }
        } catch (error) {
            alert('Failed to update post status');
        }
    }
</script>
@endpush
