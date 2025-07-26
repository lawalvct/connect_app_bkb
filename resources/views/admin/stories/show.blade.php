@extends('admin.layouts.app')

@section('title', 'Story Details')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Story Details</h1>
            <p class="text-gray-600">View and manage story information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.stories.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Stories
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <!-- Story Content -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Story Content</h3>
                        <div class="flex items-center space-x-2">
                            @if($story->is_expired)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-clock mr-1"></i> Expired
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-play mr-1"></i> Active
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Story Display -->
                    <div class="mb-6">
                        @if($story->type === 'image' && $story->file_url)
                            <div class="max-w-md mx-auto">
                                <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 9/16;">
                                    <img src="{{ $story->full_file_url }}"
                                         alt="{{ $story->caption }}"
                                         class="w-full h-full object-cover">
                                    @if($story->caption)
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                                            <p class="text-white text-sm">{{ $story->caption }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif($story->type === 'video' && $story->file_url)
                            <div class="max-w-md mx-auto">
                                <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 9/16;">
                                    <video controls class="w-full h-full object-cover">
                                        <source src="{{ $story->full_file_url }}">
                                        Your browser does not support the video tag.
                                    </video>
                                    @if($story->caption)
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                                            <p class="text-white text-sm">{{ $story->caption }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif($story->type === 'text')
                            <div class="max-w-md mx-auto">
                                <div class="relative rounded-lg overflow-hidden flex items-center justify-center text-white p-8"
                                     style="aspect-ratio: 9/16; background-color: {{ $story->background_color ?? '#000000' }};">
                                    <div class="text-center">
                                        @if($story->content)
                                            <p class="text-lg font-medium mb-4"
                                               @if($story->font_settings)
                                                   style="
                                                       font-size: {{ $story->font_settings['size'] ?? '18' }}px;
                                                       font-family: {{ $story->font_settings['family'] ?? 'Arial' }};
                                                       font-weight: {{ $story->font_settings['weight'] ?? 'normal' }};
                                                   "
                                               @endif>
                                                {{ $story->content }}
                                            </p>
                                        @endif
                                        @if($story->caption)
                                            <p class="text-sm opacity-80">{{ $story->caption }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Story Details -->
                    <div class="space-y-4">
                        @if($story->caption)
                            <div>
                                <h4 class="text-md font-medium text-gray-900 mb-2">Caption</h4>
                                <p class="text-gray-700 bg-gray-50 rounded-lg p-3">{{ $story->caption }}</p>
                            </div>
                        @endif

                        @if($story->content && $story->type !== 'text')
                            <div>
                                <h4 class="text-md font-medium text-gray-900 mb-2">Content</h4>
                                <p class="text-gray-700 bg-gray-50 rounded-lg p-3">{{ $story->content }}</p>
                            </div>
                        @endif

                        @if($story->type === 'text' && $story->font_settings)
                            <div>
                                <h4 class="text-md font-medium text-gray-900 mb-2">Font Settings</h4>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium text-gray-600">Family:</span>
                                            <span class="text-gray-900">{{ $story->font_settings['family'] ?? 'Default' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Size:</span>
                                            <span class="text-gray-900">{{ $story->font_settings['size'] ?? '18' }}px</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Weight:</span>
                                            <span class="text-gray-900">{{ $story->font_settings['weight'] ?? 'normal' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Background:</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-4 h-4 rounded" style="background-color: {{ $story->background_color ?? '#000000' }}"></div>
                                                <span class="text-gray-900">{{ $story->background_color ?? '#000000' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Story Viewers -->
            @if($story->views && $story->views->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Viewers ({{ $story->views_count }})</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($story->views->take(12) as $view)
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <img src="{{ $view->user->profile_url ?? '/images/default-avatar.png' }}"
                                 alt="{{ $view->user->name }}"
                                 class="h-8 w-8 rounded-full">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $view->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $view->viewed_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($story->views->count() > 12)
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500">And {{ $story->views->count() - 12 }} more viewers...</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Story Replies -->
            @if($story->replies && $story->replies->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Replies ({{ $story->replies->count() }})</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($story->replies as $reply)
                        <div class="flex space-x-3 p-4 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <img src="{{ $reply->user->profile_url ?? '/images/default-avatar.png' }}"
                                     alt="{{ $reply->user->name }}"
                                     class="h-8 w-8 rounded-full">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $reply->user->name }}</p>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $reply->type === 'text' ? 'bg-gray-100 text-gray-800' :
                                           ($reply->type === 'emoji' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ ucfirst($reply->type) }}
                                    </span>
                                    <p class="text-xs text-gray-500">{{ $reply->created_at->diffForHumans() }}</p>
                                </div>
                                @if($reply->type === 'text' || $reply->type === 'emoji')
                                    <p class="text-sm text-gray-700">{{ $reply->content }}</p>
                                @elseif($reply->type === 'media' && $reply->file_url)
                                    <div class="mt-2">
                                        <img src="{{ $reply->full_file_url }}"
                                             alt="Reply media"
                                             class="max-w-xs rounded-lg">
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <!-- Story Information -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Story Information</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Author</dt>
                            <dd class="mt-1">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $story->user->profile_url ?? '/images/default-avatar.png' }}"
                                         alt="{{ $story->user->name }}"
                                         class="h-8 w-8 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $story->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $story->user->email }}</p>
                                    </div>
                                </div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Story Type</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $story->type === 'text' ? 'bg-gray-100 text-gray-800' :
                                       ($story->type === 'image' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                    @if($story->type === 'text')
                                        <i class="fas fa-font mr-1"></i>
                                    @elseif($story->type === 'image')
                                        <i class="fas fa-image mr-1"></i>
                                    @else
                                        <i class="fas fa-video mr-1"></i>
                                    @endif
                                    {{ ucfirst($story->type) }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Privacy</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $story->privacy === 'all_connections' ? 'bg-green-100 text-green-800' :
                                       ($story->privacy === 'close_friends' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $story->privacy)) }}
                                </span>
                            </dd>
                        </div>

                        @if($story->privacy === 'custom' && $story->custom_viewers)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Custom Viewers</dt>
                                <dd class="mt-1">
                                    <span class="text-sm text-gray-900">{{ count($story->custom_viewers) }} users</span>
                                </dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Allow Replies</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $story->allow_replies ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $story->allow_replies ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $story->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Expires</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $story->expires_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>

                        @if($story->is_expired)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-clock mr-1"></i> Expired
                                    </span>
                                </dd>
                            </div>
                        @else
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Time Left</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="countdown">
                                    <script>
                                        function updateCountdown() {
                                            const expiresAt = new Date('{{ $story->expires_at->toISOString() }}');
                                            const now = new Date();
                                            const timeLeft = expiresAt - now;

                                            if (timeLeft <= 0) {
                                                document.getElementById('countdown').innerHTML = '<span class="text-red-600">Expired</span>';
                                                return;
                                            }

                                            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                                            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                                            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                                            document.getElementById('countdown').innerHTML = `${hours}h ${minutes}m ${seconds}s`;
                                        }

                                        updateCountdown();
                                        setInterval(updateCountdown, 1000);
                                    </script>
                                </dd>
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
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $story->views_count }}</div>
                            <div class="text-sm text-blue-600">Views</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $story->replies->count() }}</div>
                            <div class="text-sm text-green-600">Replies</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Story Actions -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="deleteStory({{ $story->id }})"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Story
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    async function deleteStory(storyId) {
        if (!confirm('Are you sure you want to delete this story? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`/admin/stories/${storyId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.location.href = '/admin/stories';
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting story:', error);
            alert('Failed to delete story');
        }
    }
</script>
@endpush
