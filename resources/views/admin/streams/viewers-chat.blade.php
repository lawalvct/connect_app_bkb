@extends('admin.layouts.app')

@section('title', 'Viewers & Chat - ' . $stream->title)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-users-cog mr-2 text-blue-500"></i>
                Viewers & Chat Management
            </h1>
            <p class="text-gray-600 mt-1">Monitor and interact with your live stream audience</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.streams.broadcast', $stream) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-broadcast-tower mr-2"></i>Back to Broadcast
            </a>
            <a href="{{ route('admin.streams.show', $stream) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Stream Details
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="viewersChat()" x-init="init()">

    <!-- Stream Status Bar -->
    <div class="bg-white rounded-lg shadow-md mb-6 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <img src="{{ $stream->banner_image_url ?? '/images/placeholder-stream.jpg' }}"
                         alt="{{ $stream->title }}"
                         class="w-24 h-16 object-cover rounded-lg">
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $stream->title }}</h2>
                    <div class="flex items-center space-x-4 mt-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($stream->status === 'live') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            <i class="fas fa-circle mr-1 @if($stream->status === 'live') text-red-500 animate-pulse @else text-yellow-500 @endif"></i>
                            {{ strtoupper($stream->status) }}
                        </span>
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-video mr-1"></i>
                            Channel: {{ $stream->channel_name }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-blue-600" x-text="viewerCount">0</div>
                <div class="text-sm text-gray-600">Live Viewers</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Viewer List Panel -->
        <div class="bg-white shadow-lg rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-users mr-2 text-blue-600"></i>
                        Live Viewers
                    </h3>
                    <div class="flex items-center space-x-3">
                        <span class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-md" x-text="viewerCount || 0">0</span>
                        <button @click="loadViewers()" class="text-blue-600 hover:text-blue-800 p-2" title="Refresh viewers">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Search Viewers -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text"
                               x-model="viewerSearch"
                               placeholder="Search viewers..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Viewer List -->
                <div class="max-h-[600px] overflow-y-auto">
                    <div x-show="!viewers || viewers.length === 0" class="text-center text-gray-500 py-12">
                        <i class="fas fa-users text-6xl mb-4 opacity-30"></i>
                        <p class="text-xl font-medium">No viewers yet</p>
                        <p class="text-sm">Waiting for your audience...</p>
                    </div>

                    <div class="space-y-3" x-show="viewers && viewers.length > 0">
                        <template x-for="viewer in filteredViewers" :key="viewer.id">
                            <div class="flex items-center space-x-3 p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 hover:shadow-lg transition-all">
                                <img :src="viewer.avatar || '/images/default-avatar.png'"
                                     :alt="viewer.name"
                                     class="w-12 h-12 rounded-full border-2 border-blue-200">
                                <div class="flex-1">
                                    <div class="text-base font-semibold text-gray-900" x-text="viewer.name"></div>
                                    <div class="text-sm text-gray-600" x-text="viewer.email"></div>
                                    <div class="text-xs text-gray-500 flex items-center mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span x-text="'Joined: ' + viewer.joinedAt"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end space-y-1">
                                    <div class="w-3 h-3 bg-green-500 rounded-full shadow-md animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Online</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Chat Panel -->
        <div class="bg-white shadow-lg rounded-lg border border-gray-200 flex flex-col" style="height: calc(100vh - 300px);">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-comments mr-2 text-green-600"></i>
                        Live Chat
                        <span class="ml-2 text-sm text-gray-600" x-text="'(' + (chatMessages?.length || 0) + ' messages)'"></span>
                    </h3>
                    <button @click="loadChat()" class="text-green-600 hover:text-green-800 p-2" title="Refresh chat">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 bg-gradient-to-b from-gray-50 to-gray-100">
                <div x-show="!chatMessages || chatMessages.length === 0" class="text-center text-gray-500 py-12">
                    <i class="fas fa-comments text-6xl mb-4 opacity-30"></i>
                    <p class="text-xl font-medium">No messages yet</p>
                    <p class="text-sm">Start the conversation!</p>
                </div>

                <div class="space-y-4" x-show="chatMessages && chatMessages.length > 0">
                    <template x-for="message in chatMessages" :key="message.id">
                        <div class="flex items-start space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                            <img :src="message.avatar || '/images/default-avatar.png'"
                                 :alt="message.username"
                                 class="w-10 h-10 rounded-full border-2 flex-shrink-0"
                                 :class="message.isAdmin ? 'border-red-300' : 'border-blue-300'">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-sm font-semibold truncate"
                                          :class="message.isAdmin ? 'text-red-700' : 'text-gray-900'"
                                          x-text="message.username"></span>
                                    <span x-show="message.isAdmin" class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded-full font-bold flex-shrink-0">ADMIN</span>
                                    <span class="text-xs text-gray-500 flex-shrink-0" x-text="message.timestamp"></span>
                                </div>
                                <div class="text-sm text-gray-700 leading-relaxed break-words" x-text="message.text"></div>
                            </div>
                            <button @click="deleteMessage(message.id)"
                                    class="text-red-500 hover:text-red-700 text-sm flex-shrink-0"
                                    title="Delete message">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Send Message Form -->
            <div class="p-4 border-t border-gray-200 bg-white flex-shrink-0">
                <form @submit.prevent="sendMessage()">
                    <div class="flex space-x-2">
                        <input type="text"
                               x-model="newMessage"
                               placeholder="Type your message as admin..."
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               maxlength="500">
                        <button type="submit"
                                :disabled="!newMessage.trim()"
                                class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-8 py-3 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md transform hover:scale-105 flex items-center space-x-2">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function viewersChat() {
    return {
        streamId: {{ $stream->id }},
        viewerCount: 0,
        viewers: [],
        chatMessages: [],
        newMessage: '',
        viewerSearch: '',
        viewersRefreshTimer: null,
        chatRefreshTimer: null,

        init() {
            // Initial load
            this.loadViewers();
            this.loadChat();

            // Auto-refresh every 10 seconds
            this.viewersRefreshTimer = setInterval(() => {
                this.updateViewers();
            }, 10000);

            this.chatRefreshTimer = setInterval(() => {
                this.updateChat();
            }, 10000);
        },

        async loadViewers() {
            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/viewers`);
                const data = await response.json();

                if (data.success) {
                    this.viewers = data.viewers.map(viewer => ({
                        id: viewer.id,
                        name: viewer.user?.name || 'Anonymous',
                        email: viewer.user?.email || '',
                        avatar: viewer.user?.profile_photo_url || null,
                        joinedAt: new Date(viewer.joined_at).toLocaleTimeString()
                    }));
                    this.viewerCount = this.viewers.length;
                }
            } catch (error) {
                console.error('Failed to load viewers:', error);
            }
        },

        async updateViewers() {
            // Silent update without UI feedback
            await this.loadViewers();
        },

        async loadChat() {
            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/chats`);
                const data = await response.json();

                if (data.success) {
                    this.chatMessages = data.data.map(msg => ({
                        id: msg.id,
                        username: msg.username || 'Anonymous',
                        text: msg.message,
                        avatar: msg.user_profile_url || null,
                        timestamp: new Date(msg.created_at).toLocaleTimeString(),
                        isAdmin: msg.is_admin || false
                    }));

                    // Auto-scroll to bottom
                    this.$nextTick(() => {
                        const chatContainer = document.getElementById('chatMessages');
                        if (chatContainer) {
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }
                    });
                }
            } catch (error) {
                console.error('Failed to load chat:', error);
            }
        },

        async updateChat() {
            // Silent update
            await this.loadChat();
        },

        async sendMessage() {
            if (!this.newMessage.trim()) return;

            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/chats/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: this.newMessage
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.newMessage = '';
                    await this.loadChat();
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            } catch (error) {
                console.error('Failed to send message:', error);
                alert('Failed to send message');
            }
        },

        async deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;

            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/chats/${messageId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadChat();
                } else {
                    alert('Failed to delete message');
                }
            } catch (error) {
                console.error('Failed to delete message:', error);
            }
        },

        get filteredViewers() {
            if (!this.viewerSearch) return this.viewers;

            const search = this.viewerSearch.toLowerCase();
            return this.viewers.filter(viewer =>
                viewer.name.toLowerCase().includes(search) ||
                viewer.email.toLowerCase().includes(search)
            );
        }
    }
}
</script>
@endsection
