@extends('admin.layouts.app')

@section('title', 'Stream Details')
@section('page-title', 'Stream Details')

@section('header')
    <div class="flex justify-between items-center">

                <div class="flex space-x-3">
            @if($stream->status === 'scheduled')
                <button id="startStreamBtn" onclick="startStream({{ $stream->id }})"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-play mr-2"></i>Start Stream
                </button>
            @elseif($stream->status === 'live')
                <a href="{{ route('admin.streams.broadcast', $stream) }}"
                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-video mr-2"></i>Go Live
                </a>
                <button id="endStreamBtn" onclick="endStream({{ $stream->id }})"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-stop mr-2"></i>End Stream
                </button>
            @endif
            @if(in_array($stream->status, ['upcoming', 'live']))
                <a href="{{ route('admin.streams.broadcast', $stream) }}"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-broadcast-tower mr-2"></i>Broadcast Studio
                </a>
                <a href="{{ route('admin.streams.cameras', $stream) }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-video mr-2"></i>Camera Management
                </a>
            @endif
            <a href="{{ route('admin.streams.edit', $stream) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit Stream
            </a>
            <a href="{{ route('admin.streams.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Streams
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="streamDetails()">
    <!-- Stream Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="flex items-start space-x-4">
                        @if($stream->banner_image_url)
                        <div class="relative">
                            <img src="{{ $stream->banner_image_url }}"
                                 alt="{{ $stream->title }}"
                                 class="w-32 h-20 object-cover rounded-lg">
                            @if($stream->status === 'live')
                            <div class="absolute top-1 right-1 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                                LIVE
                            </div>
                            @endif
                        </div>
                        @endif
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-xl font-bold text-gray-900">{{ $stream->title }}</h2>
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    @if($stream->status === 'live') bg-red-100 text-red-800
                                    @elseif($stream->status === 'scheduled') bg-yellow-100 text-yellow-800
                                    @elseif($stream->status === 'ended') bg-gray-100 text-gray-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ ucfirst($stream->status) }}
                                </span>
                            </div>
                            @if($stream->description)
                            <p class="text-gray-600 mb-4">{{ $stream->description }}</p>
                            @endif
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-500">Free Time:</span>
                                    <p>{{ $stream->free_minutes }} minutes</p>
                                </div>
                                @if($stream->price > 0)
                                <div>
                                    <span class="font-medium text-gray-500">Price:</span>
                                    <p>{{ $stream->currency }} {{ number_format($stream->price, 2) }}</p>
                                </div>
                                @endif
                                <div>
                                    <span class="font-medium text-gray-500">Creator:</span>
                                    <p>{{ $stream->user ? $stream->user->name : 'Unknown User' }}</p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500">Created:</span>
                                    <p>{{ $stream->created_at->format('M j, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Viewers:</span>
                                <span class="font-semibold" x-text="stats.currentViewers">{{ $stream->streamViewers()->where('left_at', null)->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Viewers:</span>
                                <span class="font-semibold" x-text="stats.totalViewers">{{ $stream->streamViewers()->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Messages:</span>
                                <span class="font-semibold" x-text="stats.totalMessages">{{ $stream->streamChats()->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Revenue:</span>
                                <span class="font-semibold">{{ $stream->currency }} {{ number_format($stream->streamPayments()->sum('amount'), 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'viewers'"
                        :class="activeTab === 'viewers' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-users mr-2"></i>Viewers (<span x-text="stats.currentViewers">{{ $stream->streamViewers()->where('left_at', null)->count() }}</span>)
                </button>
                <button @click="activeTab = 'chat'"
                        :class="activeTab === 'chat' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-comments mr-2"></i>Chat (<span x-text="stats.totalMessages">{{ $stream->streamChats()->count() }}</span>)
                </button>
                <button @click="activeTab = 'analytics'"
                        :class="activeTab === 'analytics' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>Analytics
                </button>
                <button @click="activeTab = 'payments'"
                        :class="activeTab === 'payments' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-money-bill mr-2"></i>Payments
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Viewers Tab -->
            <div x-show="activeTab === 'viewers'">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Current Viewers</h3>
                    <button @click="refreshViewers()" class="text-sm text-primary hover:text-primary-dark">
                        <i class="fas fa-refresh mr-1"></i>Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Watch Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" x-html="viewersTable">
                            @forelse($stream->streamViewers()->with('user')->latest()->get() as $viewer)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <img class="h-8 w-8 rounded-full" src="{{ $viewer->user ? ($viewer->user->profile_picture ?? '/images/default-avatar.png') : '/images/default-avatar.png' }}" alt="">
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $viewer->user ? $viewer->user->name : 'Unknown User' }}</div>
                                            <div class="text-sm text-gray-500">{{ $viewer->user ? $viewer->user->email : 'No email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $viewer->joined_at->format('M j, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($viewer->left_at)
                                        {{ $viewer->joined_at->diffInMinutes($viewer->left_at) }} minutes
                                    @else
                                        {{ $viewer->joined_at->diffInMinutes(now()) }} minutes
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($viewer->left_at)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Left</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Watching</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="#" class="text-primary hover:text-primary-dark">View Profile</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No viewers yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chat Tab -->
            <div x-show="activeTab === 'chat'">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Stream Chat</h3>
                    <div class="flex space-x-2">
                        <button @click="refreshChat()" class="text-sm text-primary hover:text-primary-dark">
                            <i class="fas fa-refresh mr-1"></i>Refresh
                        </button>
                        <button @click="showAdminMessage = !showAdminMessage" class="bg-primary text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                            <i class="fas fa-plus mr-1"></i>Send Message
                        </button>
                    </div>
                </div>

                <!-- Admin Message Form -->
                <div x-show="showAdminMessage" x-transition class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <form @submit.prevent="sendAdminMessage()">
                        <div class="flex space-x-3">
                            <input type="text"
                                   x-model="adminMessage"
                                   placeholder="Type your admin message..."
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                   required>
                            <button type="submit"
                                    :disabled="!adminMessage.trim() || sendingMessage"
                                    class="bg-primary text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50">
                                <span x-show="!sendingMessage">Send</span>
                                <span x-show="sendingMessage">Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto" x-html="chatMessages">
                    @forelse($stream->streamChats()->with('user')->latest()->limit(50)->get()->reverse() as $chat)
                    <div class="flex items-start space-x-3 mb-3">
                        <div class="flex-shrink-0">
                            <img class="h-8 w-8 rounded-full" src="{{ $chat->user ? ($chat->user->profile_picture ?? '/images/default-avatar.png') : '/images/default-avatar.png' }}" alt="">
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-900">{{ $chat->user ? $chat->user->name : 'Unknown User' }}</span>
                                @if($chat->is_admin)
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">ADMIN</span>
                                @endif
                                <span class="text-xs text-gray-500">{{ $chat->created_at->format('H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $chat->message }}</p>
                        </div>
                        <button onclick="deleteMessage({{ $chat->id }})" class="text-red-500 hover:text-red-700 text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center">No messages yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Analytics Tab -->
            <div x-show="activeTab === 'analytics'">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Stream Analytics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-eye text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-900">Peak Viewers</p>
                                <p class="text-2xl font-bold text-blue-600">{{ $stream->streamViewers()->count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clock text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-900">Avg. Watch Time</p>
                                <p class="text-2xl font-bold text-green-600">
                                    @php
                                        $avgWatchTime = $stream->streamViewers()
                                            ->whereNotNull('left_at')
                                            ->get()
                                            ->avg(function($viewer) {
                                                return $viewer->joined_at->diffInMinutes($viewer->left_at);
                                            });
                                    @endphp
                                    {{ round($avgWatchTime ?? 0) }}m
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-comments text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-900">Engagement Rate</p>
                                <p class="text-2xl font-bold text-yellow-600">
                                    @php
                                        $totalViewers = $stream->streamViewers()->count();
                                        $totalMessages = $stream->streamChats()->count();
                                        $engagementRate = $totalViewers > 0 ? round(($totalMessages / $totalViewers) * 100) : 0;
                                    @endphp
                                    {{ $engagementRate }}%
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-money-bill text-purple-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-purple-900">Revenue</p>
                                <p class="text-2xl font-bold text-purple-600">{{ $stream->currency }} {{ number_format($stream->streamPayments()->sum('amount'), 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interaction Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-thumbs-up text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-900">Total Likes</p>
                                <p class="text-2xl font-bold text-red-600">{{ $stream->likes_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-thumbs-down text-orange-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-orange-900">Total Dislikes</p>
                                <p class="text-2xl font-bold text-orange-600">{{ $stream->dislikes_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-share text-indigo-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-indigo-900">Total Shares</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ $stream->shares_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Duration Info -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-gray-900 mb-3">Stream Duration</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-500">Created:</span>
                            <p>{{ $stream->created_at->format('M j, Y H:i') }}</p>
                        </div>
                        @if($stream->started_at)
                        <div>
                            <span class="font-medium text-gray-500">Started:</span>
                            <p>{{ $stream->started_at->format('M j, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($stream->ended_at)
                        <div>
                            <span class="font-medium text-gray-500">Ended:</span>
                            <p>{{ $stream->ended_at->format('M j, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($stream->started_at)
                        <div>
                            <span class="font-medium text-gray-500">Duration:</span>
                            <p>
                                @if($stream->ended_at)
                                    {{ $stream->started_at->diffInMinutes($stream->ended_at) }} minutes
                                @else
                                    {{ $stream->started_at->diffInMinutes(now()) }} minutes (ongoing)
                                @endif
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payments Tab -->
            <div x-show="activeTab === 'payments'">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($stream->streamPayments()->with('user')->latest()->get() as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <img class="h-8 w-8 rounded-full" src="{{ $payment->user ? ($payment->user->profile_picture ?? '/images/default-avatar.png') : '/images/default-avatar.png' }}" alt="">
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $payment->user ? $payment->user->name : 'Unknown User' }}</div>
                                            <div class="text-sm text-gray-500">{{ $payment->user ? $payment->user->email : 'No email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payment->payment_method ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payment->created_at->format('M j, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($payment->status === 'completed') bg-green-100 text-green-800
                                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No payments yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function streamDetails() {
    return {
        activeTab: 'viewers',
        stats: {
            currentViewers: {{ $stream->streamViewers()->where('left_at', null)->count() }},
            totalViewers: {{ $stream->streamViewers()->count() }},
            totalMessages: {{ $stream->streamChats()->count() }}
        },
        showAdminMessage: false,
        adminMessage: '',
        sendingMessage: false,
        viewersTable: '',
        chatMessages: '',

        async refreshViewers() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/viewers`);
                const data = await response.json();

                if (data.success) {
                    this.stats.currentViewers = data.viewers.filter(v => !v.left_at).length;
                    this.stats.totalViewers = data.viewers.length;
                    this.updateViewersTable(data.viewers);
                }
            } catch (error) {
                console.error('Error refreshing viewers:', error);
            }
        },

        async refreshChat() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/chats`);
                const data = await response.json();

                if (data.success) {
                    this.stats.totalMessages = data.chats.length;
                    this.updateChatMessages(data.chats);
                }
            } catch (error) {
                console.error('Error refreshing chat:', error);
            }
        },

        async sendAdminMessage() {
            if (!this.adminMessage.trim()) return;

            this.sendingMessage = true;
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/chats`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: this.adminMessage
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.adminMessage = '';
                    this.showAdminMessage = false;
                    await this.refreshChat();
                } else {
                    alert(data.message || 'Error sending message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message');
            } finally {
                this.sendingMessage = false;
            }
        },

        updateViewersTable(viewers) {
            // Update viewers table HTML dynamically
            // Implementation would generate table rows
        },

        updateChatMessages(chats) {
            // Update chat messages HTML dynamically
            // Implementation would generate chat message HTML
        }
    }
}

async function startStream(streamId) {
    if (!confirm('Are you sure you want to start this stream?')) return;

    try {
        const response = await fetch(`/admin/api/streams/${streamId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('Stream started successfully!');
            location.reload();
        } else {
            alert(data.message || 'Error starting stream');
        }
    } catch (error) {
        console.error('Error starting stream:', error);
        alert('Error starting stream');
    }
}

async function endStream(streamId) {
    if (!confirm('Are you sure you want to end this stream?')) return;

    try {
        const response = await fetch(`/admin/api/streams/${streamId}/end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('Stream ended successfully!');
            location.reload();
        } else {
            alert(data.message || 'Error ending stream');
        }
    } catch (error) {
        console.error('Error ending stream:', error);
        alert('Error ending stream');
    }
}

async function deleteMessage(chatId) {
    if (!confirm('Are you sure you want to delete this message?')) return;

    try {
        const response = await fetch(`/admin/api/chats/${chatId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error deleting message');
        }
    } catch (error) {
        console.error('Error deleting message:', error);
        alert('Error deleting message');
    }
}
</script>
@endsection
