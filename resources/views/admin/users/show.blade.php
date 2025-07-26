@extends('admin.layouts.app')

@section('title', 'User Details - ' . $user->name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Details</h1>
            <p class="text-gray-600">View and manage user information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Users
            </a>
            <a href="{{ route('admin.users.edit', $user) }}"
               class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-edit mr-2"></i>
                Edit User
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- User Information Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-start space-x-6">
                        <!-- Profile Picture -->
                        <div class="flex-shrink-0">
                            <img class="h-20 w-20 rounded-full object-cover border-4 border-gray-200"
                                 src="{{ $user->profile_picture ?? '/images/default-avatar.png' }}"
                                 alt="{{ $user->name }}">
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($user->is_banned)
                                        bg-red-100 text-red-800
                                    @elseif(!$user->is_active)
                                        bg-yellow-100 text-yellow-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif">
                                    @if($user->is_banned)
                                        Banned
                                    @elseif(!$user->is_active)
                                        Suspended
                                    @else
                                        Active
                                    @endif
                                </span>
                            </div>

                            <div class="mt-2 space-y-2">
                                <p class="text-gray-600">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    {{ $user->email }}
                                    @if($user->email_verified_at)
                                        <i class="fas fa-check-circle text-green-500 ml-2" title="Verified"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-500 ml-2" title="Unverified"></i>
                                    @endif
                                </p>

                                @if($user->phone)
                                <p class="text-gray-600">
                                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                                    {{ $user->phone }}
                                </p>
                                @endif

                                <p class="text-gray-600">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    Joined {{ $user->created_at->format('M d, Y') }}
                                </p>

                                @if($user->last_login_at)
                                <p class="text-gray-600">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    Last login {{ $user->last_login_at->diffForHumans() }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Information -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Activity Overview</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-900">{{ $user->posts_count ?? 0 }}</div>
                            <div class="text-sm text-blue-600">Posts Created</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-900">0</div>
                            <div class="text-sm text-green-600">Streams</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-900">{{ $user->socialCircles ? $user->socialCircles->count() : 0 }}</div>
                            <div class="text-sm text-purple-600">Social Circles</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Circles -->
            @if($user->socialCircles && $user->socialCircles->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Social Circles ({{ $user->socialCircles->count() }})</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($user->socialCircles as $circle)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center space-x-3">
                                @if($circle->logo)
                                <div class="flex-shrink-0">
                                   <img src="/uploads/logo/{{ $circle->logo }}" alt="{{ $circle->name }}"
         class="w-10 h-10 rounded-full object-cover">
                                </div>
                                @else
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium"
                                     style="background-color: {{ $circle->color ?? '#6B7280' }}">
                                    {{ substr($circle->name, 0, 2) }}
                                </div>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $circle->name }}</p>
                                    @if($circle->description)
                                    <p class="text-xs text-gray-500 truncate">{{ Str::limit($circle->description, 50) }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($circle->color)
                            <div class="mt-3">
                                <div class="w-full h-2 rounded-full" style="background-color: {{ $circle->color }}20;">
                                    <div class="h-2 rounded-full" style="background-color: {{ $circle->color }}; width: 100%;"></div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Social Circles</h3>
                </div>
                <div class="p-6">
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">This user is not a member of any social circles yet.</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Posts -->
            @if($user->posts && $user->posts->count() > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Posts</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($user->posts->take(5) as $post)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-gray-900">{{ Str::limit($post->content ?? $post->title ?? 'Post content', 100) }}</p>
                                    <p class="text-sm text-gray-500 mt-2">{{ $post->created_at->diffForHumans() }}</p>
                                </div>
                                @if($post->image)
                                <div class="ml-4">
                                    <img src="{{ $post->image }}" alt="Post image" class="w-16 h-16 object-cover rounded">
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

        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">

                    @if($user->is_active && !$user->is_banned)
                    <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('Are you sure you want to suspend this user?')"
                                class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-pause mr-2"></i>
                            Suspend User
                        </button>
                    </form>
                    @elseif(!$user->is_active && !$user->is_banned)
                    <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-play mr-2"></i>
                            Activate User
                        </button>
                    </form>
                    @endif

                    @if(!$user->is_banned)
                    <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="w-full"
                          onsubmit="return confirm('Are you sure you want to ban this user? This is a serious action.')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="ban" value="1">
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-ban mr-2"></i>
                            Ban User
                        </button>
                    </form>
                    @else
                    <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-unlock mr-2"></i>
                            Unban User
                        </button>
                    </form>
                    @endif

                    <button onclick="resetPassword({{ $user->id }})"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>

                    <hr class="my-4">

                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Delete User
                        </button>
                    </form>
                </div>
            </div>

            <!-- User Statistics -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Account Details</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">User ID:</span>
                        <span class="text-gray-900">{{ $user->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Registration:</span>
                        <span class="text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email Verified:</span>
                        <span class="text-gray-900">
                            @if($user->email_verified_at)
                                <i class="fas fa-check text-green-500"></i> Yes
                            @else
                                <i class="fas fa-times text-red-500"></i> No
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="text-gray-900">
                            @if($user->is_banned)
                                <span class="text-red-600">Banned</span>
                            @elseif(!$user->is_active)
                                <span class="text-yellow-600">Suspended</span>
                            @else
                                <span class="text-green-600">Active</span>
                            @endif
                        </span>
                    </div>
                    @if($user->last_login_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Login:</span>
                        <span class="text-gray-900">{{ $user->last_login_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    async function resetPassword(userId) {
        if (confirm('Send password reset email to this user?')) {
            try {
                const response = await fetch(`/admin/api/users/${userId}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (response.ok) {
                    showToast('Password reset email sent successfully', 'success');
                } else {
                    showToast('Failed to send password reset email', 'error');
                }
            } catch (error) {
                showToast('Failed to send password reset email', 'error');
            }
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 text-white ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
@endpush
