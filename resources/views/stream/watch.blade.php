<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        #remoteVideo {
            background: #000;
            width: 100%;
            height: 100%;
        }

        .chat-container {
            height: 400px;
        }

        .chat-messages {
            height: 300px;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .live-indicator {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen" x-data="streamViewer()" x-init="init()">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $stream->title }}</h1>
                        <div class="flex items-center space-x-4 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                @if($stream->status === 'live') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                <i class="fas fa-circle mr-1 @if($stream->status === 'live') text-red-500 live-indicator @else text-gray-500 @endif"></i>
                                {{ $stream->status === 'live' ? 'LIVE' : strtoupper($stream->status) }}
                            </span>
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-users mr-1"></i>
                                <span x-text="viewerCount">{{ $stream->viewers()->where('is_active', true)->count() }}</span> viewers
                            </span>
                            @if($stream->price > 0)
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-money-bill mr-1"></i>
                                {{ $stream->currency }} {{ number_format($stream->price, 2) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">by {{ $stream->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $stream->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Video Player -->
                <div class="lg:col-span-3">
                    <div class="bg-black rounded-lg overflow-hidden shadow-lg" style="aspect-ratio: 16/9;">
                        <div id="remoteVideo" class="relative w-full h-full">
                            <!-- Loading State -->
                            <div x-show="!isConnected && !connectionError" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                                <div class="text-center text-white">
                                    <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
                                    <p class="text-lg">Connecting to stream...</p>
                                </div>
                            </div>

                            <!-- Connection Error -->
                            <div x-show="connectionError" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                                <div class="text-center text-white">
                                    <i class="fas fa-exclamation-triangle text-4xl mb-4 text-yellow-500"></i>
                                    <p class="text-lg mb-4">Failed to connect to stream</p>
                                    <button @click="reconnect()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                        <i class="fas fa-redo mr-2"></i>Try Again
                                    </button>
                                </div>
                            </div>

                            <!-- Stream Offline -->
                            <div x-show="!isLive && !connecting" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                                <div class="text-center text-white">
                                    <i class="fas fa-video-slash text-4xl mb-4 opacity-50"></i>
                                    <p class="text-lg">Stream is offline</p>
                                    <p class="text-sm opacity-75">Check back later</p>
                                </div>
                            </div>

                            <!-- Live Indicator -->
                            <div x-show="isLive && isConnected" class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold live-indicator">
                                <i class="fas fa-circle mr-1"></i>LIVE
                            </div>

                            <!-- Viewer Count -->
                            <div x-show="isLive && isConnected" class="absolute top-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-users mr-1"></i>
                                <span x-text="viewerCount">0</span> viewers
                            </div>
                        </div>
                    </div>

                    <!-- Stream Info -->
                    <div class="bg-white rounded-lg shadow mt-6 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $stream->title }}</h2>
                        @if($stream->description)
                        <p class="text-gray-600 mb-4">{{ $stream->description }}</p>
                        @endif

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i>{{ $stream->user->name }}</span>
                                <span><i class="fas fa-calendar mr-1"></i>{{ $stream->created_at->format('M j, Y') }}</span>
                                @if($stream->free_minutes > 0)
                                <span><i class="fas fa-clock mr-1"></i>{{ $stream->free_minutes }} free minutes</span>
                                @endif
                            </div>

                            <!-- Payment Button -->
                            @if($stream->price > 0 && !$hasPaid)
                            <button @click="showPayment = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium">
                                <i class="fas fa-credit-card mr-2"></i>
                                Pay {{ $stream->currency }} {{ number_format($stream->price, 2) }}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Chat Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow chat-container">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Live Chat</h3>
                        </div>

                        <!-- Chat Messages -->
                        <div id="chatMessages" class="chat-messages overflow-y-auto p-4 space-y-3">
                            <div x-show="chatMessages.length === 0" class="text-center text-gray-500 py-8">
                                <i class="fas fa-comments text-3xl mb-2 opacity-50"></i>
                                <p>No messages yet</p>
                                <p class="text-xs">Be the first to say hello!</p>
                            </div>

                            <template x-for="message in chatMessages" :key="message.id">
                                <div class="flex items-start space-x-2">
                                    <img :src="message.avatar || '/images/default-avatar.png'"
                                         :alt="message.username"
                                         class="w-6 h-6 rounded-full flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-900 truncate" x-text="message.username"></span>
                                            <span x-show="message.isAdmin" class="px-1 py-0.5 bg-red-100 text-red-800 text-xs rounded font-medium">ADMIN</span>
                                            <span class="text-xs text-gray-500" x-text="message.timestamp"></span>
                                        </div>
                                        <p class="text-sm text-gray-700 break-words" x-text="message.text"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Send Message -->
                        <div class="p-4 border-t border-gray-200">
                            <form @submit.prevent="sendMessage()" x-show="canChat">
                                <div class="flex space-x-2">
                                    <input type="text"
                                           x-model="newMessage"
                                           placeholder="Type a message..."
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                           maxlength="500">
                                    <button type="submit"
                                            :disabled="!newMessage.trim()"
                                            class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>

                            <div x-show="!canChat" class="text-center text-gray-500 py-4">
                                <p class="text-sm">Please log in to chat</p>
                                <a href="/login" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
            hasPaid: {{ $hasPaid ? 'true' : 'false' }},
            canChat: {{ auth()->check() ? 'true' : 'false' }},

            // Connection state
            isConnected: false,
            connecting: false,
            connectionError: false,
            viewerCount: {{ $stream->viewers()->where('is_active', true)->count() }},

            // Agora client
            agoraClient: null,
            remoteUsers: [],

            // Chat
            chatMessages: [],
            newMessage: '',

            // Payment
            showPayment: false,

            async init() {
                console.log('Initializing stream viewer...');

                if (this.isLive) {
                    await this.connectToStream();
                }

                // Start polling for chat and viewer count
                this.startPolling();

                console.log('Stream viewer initialized');
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
                        console.log('Viewer token received:', { uid: this.uid });
                    } else {
                        throw new Error(data.message || 'Failed to get token');
                    }
                } catch (error) {
                    console.error('Error getting viewer token:', error);
                    throw error;
                }
            },

            setupAgoraEventListeners() {
                // User published (broadcaster started streaming)
                this.agoraClient.on("user-published", async (user, mediaType) => {
                    console.log('User published:', user.uid, mediaType);

                    // Subscribe to the remote user
                    await this.agoraClient.subscribe(user, mediaType);

                    if (mediaType === "video") {
                        // Play the remote video track
                        user.videoTrack.play("remoteVideo");
                    }

                    if (mediaType === "audio") {
                        // Play the remote audio track
                        user.audioTrack.play();
                    }
                });

                // User unpublished (broadcaster stopped streaming)
                this.agoraClient.on("user-unpublished", (user, mediaType) => {
                    console.log('User unpublished:', user.uid, mediaType);
                });

                // User joined
                this.agoraClient.on("user-joined", (user) => {
                    console.log('User joined:', user.uid);
                });

                // User left
                this.agoraClient.on("user-left", (user) => {
                    console.log('User left:', user.uid);
                });
            },

            async reconnect() {
                this.connectionError = false;
                await this.connectToStream();
            },

            startPolling() {
                // Update chat and viewer count every 5 seconds
                setInterval(() => {
                    this.updateChat();
                    this.updateViewerCount();
                }, 5000);

                // Initial fetch
                this.updateChat();
                this.updateViewerCount();
            },

            async updateChat() {
                try {
                    const response = await fetch(`/api/streams/${this.streamId}/chats`);
                    const data = await response.json();

                    if (data.success) {
                        this.chatMessages = data.data.map(msg => ({
                            id: msg.id,
                            username: msg.username || msg.user?.name || 'Anonymous',
                            text: msg.message,
                            avatar: msg.user_profile_url || msg.user?.profile_picture,
                            isAdmin: msg.is_admin,
                            timestamp: new Date(msg.created_at).toLocaleTimeString()
                        })).reverse();

                        // Scroll to bottom
                        this.$nextTick(() => {
                            const chatContainer = document.getElementById('chatMessages');
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        });
                    }
                } catch (error) {
                    console.error('Error updating chat:', error);
                }
            },

            async updateViewerCount() {
                try {
                    const response = await fetch(`/api/streams/${this.streamId}/viewers`);
                    const data = await response.json();

                    if (data.success) {
                        this.viewerCount = data.viewers.filter(v => !v.left_at).length;
                    }
                } catch (error) {
                    console.error('Error updating viewer count:', error);
                }
            },

            async sendMessage() {
                if (!this.newMessage.trim() || !this.canChat) return;

                try {
                    const response = await fetch(`/api/streams/${this.streamId}/chat`, {
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
                        this.updateChat();
                    } else {
                        alert('Failed to send message: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                }
            }
        }
    }
    </script>
</body>
</html>
