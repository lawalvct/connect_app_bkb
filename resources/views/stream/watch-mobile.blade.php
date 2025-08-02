<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>{{ $stream->title }} - Live Stream</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --primary-color: #A20030;
            --primary-light: #A200302B;
            --background: #FAFAFA;
        }

        body {
            background-color: var(--background);
            overscroll-behavior: none;
        }

        #remoteVideo {
            background: #000;
            width: 100%;
            height: 100%;
        }

        .chat-overlay {
            backdrop-filter: blur(10px);
            background: rgba(250, 250, 250, 0.85);
            border: 1px solid rgba(162, 0, 48, 0.2);
        }

        .chat-collapsed {
            transform: translateX(calc(100% - 60px));
            transition: transform 0.3s ease-in-out;
        }

        .chat-expanded {
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
        }

        .chat-message {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .live-indicator {
            animation: pulse 2s infinite;
        }

        .viewer-count-badge {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .mobile-optimized {
            height: 100vh;
            height: 100dvh; /* For newer browsers */
        }

        /* Custom scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(162, 0, 48, 0.1);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(162, 0, 48, 0.3);
            border-radius: 2px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(162, 0, 48, 0.5);
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(162, 0, 48, 0.3);
            border-top: 3px solid #A20030;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Message input focus */
        .message-input:focus {
            border-color: #A20030;
            box-shadow: 0 0 0 3px rgba(162, 0, 48, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-overlay {
                width: 320px;
                max-width: 90vw;
            }
        }

        @media (max-width: 480px) {
            .chat-overlay {
                width: 280px;
                max-width: 85vw;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-optimized overflow-hidden" x-data="streamViewer()" x-init="init()">
        <!-- Video Container (Full Screen) -->
        <div class="relative w-full h-full bg-black">
            <!-- Video Player -->
            <div id="remoteVideo" class="w-full h-full">
                <!-- Loading State -->
                <div x-show="!isConnected && !connectionError && isLive"
                     class="absolute inset-0 flex items-center justify-center bg-gray-900">
                    <div class="text-center text-white">
                        <div class="spinner mx-auto mb-4"></div>
                        <p class="text-lg">Connecting to stream...</p>
                        <p class="text-sm opacity-75 mt-2">Please wait</p>
                    </div>
                </div>

                <!-- Connection Error -->
                <div x-show="connectionError"
                     class="absolute inset-0 flex items-center justify-center bg-gray-900">
                    <div class="text-center text-white px-4">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4" style="color: #A20030;"></i>
                        <p class="text-lg mb-4">Connection Failed</p>
                        <p class="text-sm opacity-75 mb-6">Unable to connect to the live stream</p>
                        <button @click="reconnect()"
                                class="px-6 py-2 rounded-lg text-white font-medium"
                                style="background-color: #A20030;">
                            <i class="fas fa-redo mr-2"></i>Try Again
                        </button>
                    </div>
                </div>

                <!-- Stream Offline -->
                <div x-show="!isLive && !connecting"
                     class="absolute inset-0 flex items-center justify-center bg-gray-900">
                    <div class="text-center text-white px-4">
                        <i class="fas fa-video-slash text-4xl mb-4 opacity-50"></i>
                        <p class="text-lg mb-2">Stream Offline</p>
                        <p class="text-sm opacity-75">This stream is currently not broadcasting</p>
                        <button @click="location.reload()"
                                class="mt-4 px-4 py-2 rounded-lg text-white text-sm"
                                style="background-color: #A20030;">
                            <i class="fas fa-refresh mr-2"></i>Check Again
                        </button>
                    </div>
                </div>
            </div>

            <!-- Top Overlay - Stream Info -->
            <div class="absolute top-0 left-0 right-0 p-4 bg-gradient-to-b from-black/70 to-transparent">
                <div class="flex items-start justify-between">
                    <!-- Stream Title & Status -->
                    <div class="flex-1 mr-4">
                        <h1 class="text-white font-bold text-lg leading-tight mb-1">{{ $stream->title }}</h1>
                        <div class="flex items-center space-x-3">
                            <!-- Live Indicator -->
                            <span x-show="isLive"
                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold text-white live-indicator"
                                  style="background-color: #A20030;">
                                <i class="fas fa-circle mr-1 text-xs"></i>LIVE
                            </span>
                            <!-- Offline Indicator -->
                            <span x-show="!isLive"
                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-600 text-white">
                                <i class="fas fa-circle mr-1 text-xs"></i>OFFLINE
                            </span>
                            <!-- Streamer Name -->
                            <span class="text-white/80 text-sm">{{ $stream->user->name }}</span>
                        </div>
                    </div>

                    <!-- Viewer Count -->
                    <div class="viewer-count-badge px-3 py-1 rounded-full text-white text-sm font-medium">
                        <i class="fas fa-users mr-1"></i>
                        <span x-text="formatViewerCount(viewerCount)">{{ $stream->viewers()->where('is_active', true)->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Overlay - Controls -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/70 to-transparent">
                <div class="flex items-center justify-between">
                    <!-- Stream Description Toggle -->
                    <button @click="showInfo = !showInfo"
                            class="text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-info-circle text-lg"></i>
                    </button>

                    <!-- Center Controls -->
                    <div class="flex items-center space-x-4">
                        <!-- Fullscreen Toggle -->
                        <button @click="toggleFullscreen()"
                                class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-expand text-lg"></i>
                        </button>
                    </div>

                    <!-- Chat Toggle -->
                    <button @click="toggleChat()"
                            class="relative text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-comment text-lg"></i>
                        <!-- Unread Messages Badge -->
                        <span x-show="unreadCount > 0"
                              x-text="unreadCount > 99 ? '99+' : unreadCount"
                              class="absolute -top-2 -right-2 min-w-[18px] h-[18px] text-xs font-bold text-white rounded-full flex items-center justify-center"
                              style="background-color: #A20030;">
                        </span>
                    </button>
                </div>
            </div>

            <!-- Stream Info Panel -->
            <div x-show="showInfo"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-4"
                 class="absolute inset-x-4 bottom-20 chat-overlay rounded-lg p-4 max-h-48 overflow-y-auto">

                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-gray-800">About this stream</h3>
                    <button @click="showInfo = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if($stream->description)
                <p class="text-gray-700 text-sm mb-3">{{ $stream->description }}</p>
                @endif

                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                    <div>
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $stream->created_at->format('M j, Y') }}
                    </div>
                    @if($stream->free_minutes > 0)
                    <div>
                        <i class="fas fa-clock mr-1"></i>
                        {{ $stream->free_minutes }} free minutes
                    </div>
                    @endif
                    @if($stream->price > 0)
                    <div>
                        <i class="fas fa-money-bill mr-1"></i>
                        {{ $stream->currency }} {{ number_format($stream->price, 2) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Floating Chat Panel -->
        <div class="fixed bottom-4 right-4 chat-overlay rounded-lg shadow-lg z-50 transition-transform duration-300"
             :class="chatExpanded ? 'chat-expanded' : 'chat-collapsed'"
             style="width: 350px; height: 400px;">

            <!-- Chat Header -->
            <div class="flex items-center justify-between p-3 border-b border-gray-200">
                <div class="flex items-center space-x-2">
                    <h3 class="font-semibold text-gray-800 text-sm">Live Chat</h3>
                    <span x-show="onlineViewers > 0"
                          class="text-xs px-2 py-1 rounded-full text-white"
                          style="background-color: #A20030;"
                          x-text="onlineViewers + ' online'">
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Minimize/Expand Button -->
                    <button @click="toggleChat()"
                            class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i :class="chatExpanded ? 'fas fa-chevron-right' : 'fas fa-chevron-left'"></i>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div class="chat-messages overflow-y-auto p-3 space-y-2"
                 style="height: 280px;"
                 id="chatMessages"
                 x-ref="chatMessages">

                <!-- No Messages State -->
                <div x-show="chatMessages.length === 0"
                     class="text-center text-gray-500 py-8">
                    <i class="fas fa-comments text-2xl mb-2 opacity-50"></i>
                    <p class="text-sm">No messages yet</p>
                    <p class="text-xs opacity-75">Be the first to chat!</p>
                </div>

                <!-- Chat Messages -->
                <template x-for="message in chatMessages" :key="message.id">
                    <div class="chat-message flex items-start space-x-2">
                        <!-- Avatar -->
                        <img :src="message.avatar || '/images/default-avatar.png'"
                             :alt="message.username"
                             class="w-6 h-6 rounded-full flex-shrink-0 mt-0.5">

                        <!-- Message Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-sm font-medium text-gray-800 truncate"
                                      x-text="message.username"></span>
                                <span x-show="message.isAdmin"
                                      class="px-1 py-0.5 text-xs font-bold text-white rounded"
                                      style="background-color: #A20030;">
                                    MOD
                                </span>
                                <span class="text-xs text-gray-500"
                                      x-text="message.timestamp"></span>
                            </div>
                            <p class="text-sm text-gray-700 break-words leading-relaxed"
                               x-text="message.text"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Message Input -->
            <div class="p-3 border-t border-gray-200">
                <form @submit.prevent="sendMessage()" x-show="canChat">
                    <div class="flex space-x-2">
                        <input type="text"
                               x-model="newMessage"
                               placeholder="Type your message..."
                               class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none message-input"
                               maxlength="500"
                               autocomplete="off">
                        <button type="submit"
                                :disabled="!newMessage.trim() || sending"
                                class="px-3 py-2 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background-color: #A20030;">
                            <i :class="sending ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
                        </button>
                    </div>
                </form>

                <!-- Login Prompt -->
                <div x-show="!canChat" class="text-center py-3">
                    <p class="text-sm text-gray-600 mb-2">Join the conversation</p>
                    <a href="/login"
                       class="inline-block px-4 py-2 text-sm text-white rounded-lg"
                       style="background-color: #A20030;">
                        Login to Chat
                    </a>
                </div>
            </div>

            <!-- Chat Toggle Button (when collapsed) -->
            <div x-show="!chatExpanded"
                 class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-full">
                <button @click="toggleChat()"
                        class="w-12 h-12 text-white rounded-l-lg shadow-lg flex items-center justify-center relative"
                        style="background-color: #A20030;">
                    <i class="fas fa-comment"></i>
                    <!-- Unread Badge -->
                    <span x-show="unreadCount > 0"
                          x-text="unreadCount > 9 ? '9+' : unreadCount"
                          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] text-xs font-bold bg-white rounded-full flex items-center justify-center"
                          style="color: #A20030;">
                    </span>
                </button>
            </div>
        </div>

        <!-- Payment Modal (if needed) -->
        <div x-show="showPayment"
             x-cloak
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full">
                <h3 class="text-lg font-bold mb-4" style="color: #A20030;">Premium Stream</h3>
                <p class="text-gray-600 mb-4">This stream requires payment to continue watching.</p>
                <div class="flex justify-end space-x-3">
                    <button @click="showPayment = false"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg">
                        Cancel
                    </button>
                    <button class="px-4 py-2 text-white rounded-lg"
                            style="background-color: #A20030;">
                        Pay {{ $stream->currency }} {{ number_format($stream->price, 2) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Agora SDK -->
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.19.0.js"></script>

    <script>
    function streamViewer() {
        return {
            // Stream info
            streamId: {{ $stream->id }},
            channelName: '{{ $stream->channel_name }}',
            appId: '{{ env('AGORA_APP_ID') }}',
            token: null,
            uid: null,
            isLive: {{ $stream->status === 'live' ? 'true' : 'false' }},
            hasPaid: {{ isset($hasPaid) && $hasPaid ? 'true' : 'false' }},
            canChat: {{ isset($canChat) && $canChat ? 'true' : 'false' }},

            // User info (for MVP flexibility)
            userId: {{ isset($userId) && $userId ? $userId : 'null' }},
            userName: '{{ isset($user) && $user ? $user->name : 'Guest' }}',
            userAvatar: '{{ isset($user) && $user && $user->profile_picture ? $user->profile_picture : '/images/default-avatar.png' }}',

            // Connection state
            isConnected: false,
            connecting: false,
            connectionError: false,
            viewerCount: {{ $stream->viewers()->where('is_active', true)->count() }},
            onlineViewers: 0,

            // UI state
            showInfo: false,
            chatExpanded: false,
            showPayment: false,
            unreadCount: 0,
            sending: false,

            // Agora client
            agoraClient: null,
            remoteUsers: [],

            // Chat
            chatMessages: [],
            newMessage: '',
            lastMessageId: 0,

            // Request management flags
            isUpdatingChat: false,
            isUpdatingViewers: false,
            requestController: null,

            async init() {
                console.log('Initializing enhanced stream viewer...');

                // Initialize request controller
                this.requestController = new AbortController();

                // Check if payment is required
                @if($stream->price > 0 && !isset($hasPaid))
                this.showPayment = true;
                @endif

                if (this.isLive && this.hasPaid) {
                    await this.connectToStream();
                }

                // Start polling for chat and viewer updates
                this.startPolling();

                // Initialize chat state
                this.loadInitialChat();

                // Handle visibility change for unread count
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && this.chatExpanded) {
                        this.unreadCount = 0;
                    }
                });

                console.log('Enhanced stream viewer initialized');
            },

            async connectToStream() {
                if (this.connecting) return;

                this.connecting = true;
                this.connectionError = false;

                try {
                    console.log('Connecting to stream...');

                    // Get viewer token
                    await this.getViewerToken();

                    if (!this.token) {
                        throw new Error('Failed to get viewer token');
                    }

                    // Initialize Agora client
                    this.agoraClient = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
                    this.agoraClient.setClientRole("audience");

                    // Set up event listeners
                    this.setupAgoraEventListeners();

                    // Join channel
                    await this.agoraClient.join(this.appId, this.channelName, this.token, this.uid);

                    this.isConnected = true;
                    this.connecting = false;

                    console.log('Connected to stream successfully');

                } catch (error) {
                    console.error('Error connecting to stream:', error);
                    this.connectionError = true;
                    this.connecting = false;
                }
            },

            async getViewerToken() {
                try {
                    const response = await fetch(`/api/streams/${this.streamId}/viewer-token`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.token = data.token;
                        this.uid = data.uid;
                        console.log('Viewer token received');
                    } else {
                        throw new Error(data.message || 'Failed to get token');
                    }
                } catch (error) {
                    console.error('Error getting viewer token:', error);
                    throw error;
                }
            },

            setupAgoraEventListeners() {
                this.agoraClient.on("user-published", async (user, mediaType) => {
                    console.log('User published:', user.uid, mediaType);

                    await this.agoraClient.subscribe(user, mediaType);

                    if (mediaType === "video") {
                        user.videoTrack.play("remoteVideo");
                    }

                    if (mediaType === "audio") {
                        user.audioTrack.play();
                    }
                });

                this.agoraClient.on("user-unpublished", (user, mediaType) => {
                    console.log('User unpublished:', user.uid, mediaType);
                });
            },

            async reconnect() {
                this.connectionError = false;
                await this.connectToStream();
            },

            toggleChat() {
                this.chatExpanded = !this.chatExpanded;
                if (this.chatExpanded) {
                    this.unreadCount = 0;
                    this.$nextTick(() => {
                        this.scrollChatToBottom();
                    });
                }
            },

            toggleFullscreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            },

            formatViewerCount(count) {
                if (count >= 1000000) {
                    return Math.floor(count / 1000000) + 'M';
                } else if (count >= 1000) {
                    return Math.floor(count / 1000) + 'K';
                } else {
                    return count.toString();
                }
            },

            startPolling() {
                // Stagger the initial calls to avoid simultaneous requests
                setTimeout(() => this.updateViewerCount(), 500);
                setTimeout(() => this.updateChat(), 1000);

                // Update viewer count every 5 seconds (reduced frequency)
                setInterval(() => {
                    if (!this.isUpdatingViewers) {
                        this.updateViewerCount();
                    }
                }, 5000);

                // Update chat every 3 seconds (but only if not already updating)
                setInterval(() => {
                    if (!this.isUpdatingChat) {
                        this.updateChat();
                    }
                }, 3000);
            },

            async loadInitialChat() {
                if (this.isUpdatingChat) return;

                this.isUpdatingChat = true;

                try {
                    const response = await fetch(`/api/streams/${this.streamId}/chats?limit=20`, {
                        signal: this.requestController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success && data.data && data.data.length > 0) {
                        this.chatMessages = this.formatChatMessages(data.data).reverse();
                        this.lastMessageId = Math.max(...this.chatMessages.map(m => m.id));
                        this.$nextTick(() => {
                            this.scrollChatToBottom();
                        });
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Error loading initial chat:', error);
                    }
                } finally {
                    this.isUpdatingChat = false;
                }
            },

            async updateChat() {
                if (this.isUpdatingChat) return; // Prevent multiple simultaneous requests

                this.isUpdatingChat = true;

                try {
                    const url = this.lastMessageId > 0
                        ? `/api/v1/streams/${this.streamId}/mvp-chats?after_id=${this.lastMessageId}&limit=10`
                        : `/api/v1/streams/${this.streamId}/mvp-chats?limit=20`;

                    const response = await fetch(url, {
                        signal: this.requestController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success && data.messages && data.messages.length > 0) {
                        const newMessages = this.formatChatMessages(data.messages);

                        if (this.lastMessageId > 0) {
                            // Add new messages
                            newMessages.forEach(msg => {
                                this.chatMessages.push(msg);
                                if (!this.chatExpanded) {
                                    this.unreadCount++;
                                }
                            });

                            // Keep only last 100 messages
                            if (this.chatMessages.length > 100) {
                                this.chatMessages = this.chatMessages.slice(-100);
                            }
                        } else {
                            // Initial load or refresh
                            this.chatMessages = newMessages.reverse();
                        }

                        this.lastMessageId = Math.max(...this.chatMessages.map(m => m.id));

                        // Auto scroll if chat is expanded and user is near bottom
                        if (this.chatExpanded) {
                            this.$nextTick(() => {
                                this.scrollChatToBottom();
                            });
                        }
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Error updating chat:', error);
                        // Exponential backoff on error
                        setTimeout(() => {
                            this.isUpdatingChat = false;
                        }, 5000);
                        return;
                    }
                } finally {
                    setTimeout(() => {
                        this.isUpdatingChat = false;
                    }, 1000); // Minimum 1 second between requests
                }
            },

            formatChatMessages(messages) {
                return messages.map(msg => ({
                    id: msg.id,
                    username: msg.username || msg.user?.name || 'Anonymous',
                    text: msg.message,
                    avatar: msg.user_profile_url || msg.user?.profile_picture || '/images/default-avatar.png',
                    isAdmin: msg.is_admin,
                    timestamp: new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})
                }));
            },

            async updateViewerCount() {
                if (this.isUpdatingViewers) return; // Prevent multiple simultaneous requests

                this.isUpdatingViewers = true;

                try {
                    const response = await fetch(`/api/streams/${this.streamId}/viewers`, {
                        signal: this.requestController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.viewerCount = data.active_count || data.total_count || 0;
                        this.onlineViewers = data.active_count || 0;
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Error updating viewer count:', error);
                        // Exponential backoff on error
                        setTimeout(() => {
                            this.isUpdatingViewers = false;
                        }, 10000);
                        return;
                    }
                } finally {
                    setTimeout(() => {
                        this.isUpdatingViewers = false;
                    }, 2000); // Minimum 2 seconds between requests
                }
            },

            async sendMessage() {
                if (!this.newMessage.trim() || !this.canChat || this.sending) return;

                this.sending = true;

                try {
                    const response = await fetch(`/api/v1/streams/${this.streamId}/mvp-chat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            message: this.newMessage,
                            user_id: this.userId,
                            username: this.userName
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.newMessage = '';
                        // Force immediate chat update
                        setTimeout(() => this.updateChat(), 100);
                    } else {
                        alert('Failed to send message: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                } finally {
                    this.sending = false;
                }
            },

            scrollChatToBottom() {
                const chatContainer = this.$refs.chatMessages;
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }
        }
    }
    </script>
</body>
</html>
