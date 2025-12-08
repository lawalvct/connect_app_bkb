@extends('admin.layouts.app')

@section('title', 'Live Broadcast - ' . $stream->title)
@section('page-title', 'Streaming')
@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-broadcast-tower mr-2 text-red-500"></i>
                Live Broadcast: {{ $stream->title }}
            </h1>
            <div class="flex items-center mt-2 space-x-4">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    @if($stream->status === 'live') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    <i class="fas fa-circle mr-1 @if($stream->status === 'live') text-red-500 animate-pulse @else text-yellow-500 @endif"></i>
                    {{ $stream->status === 'live' ? 'LIVE' : 'PREPARING' }}
                </span>
                <span class="text-sm text-gray-500">
                    <i class="fas fa-video mr-1"></i>
                    Channel: {{ $stream->channel_name }}
                </span>
                <span class="text-sm text-gray-500" x-data x-text="new Date().toLocaleString()">
                    <i class="fas fa-clock mr-1"></i>
                </span>
            </div>
        </div>
        <div class="flex space-x-3">
            <button id="endBroadcastBtn" onclick="endBroadcast()"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    style="display: none;">
                <i class="fas fa-stop mr-2"></i>End Broadcast
            </button>
            <a href="{{ route('admin.streams.cameras', $stream) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-video mr-2"></i>Camera Setup
            </a>
            <a href="{{ route('admin.streams.show', $stream) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Stream
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="liveBroadcast()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Broadcast Area -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Broadcast Camera</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Viewers: </span>
                            <span class="text-lg font-bold text-blue-600" x-text="viewerCount">0</span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Video Container -->
                    <div class="relative bg-black rounded-lg overflow-hidden mb-4" style="aspect-ratio: 16/9;">
                        <div id="localVideo" class="w-full h-full"></div>
                        <div x-show="!isStreaming" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50">
                            <div class="text-center text-white">
                                <i class="fas fa-video text-6xl mb-4 opacity-50"></i>
                                <p class="text-xl mb-2">Camera Preview</p>
                                <p class="text-sm opacity-75">Click "Start Broadcast" to begin streaming</p>
                            </div>
                        </div>
                        <!-- Live Indicator -->
                        <div x-show="isStreaming" class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold animate-pulse">
                            <i class="fas fa-circle mr-1"></i>LIVE
                        </div>
                        <!-- Duration -->
                        <div x-show="isStreaming" class="absolute top-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded text-sm">
                            <span x-text="formatDuration(streamDuration)">00:00:00</span>
                        </div>
                    </div>

                    <!-- Broadcasting Controls -->
                    <div class="space-y-6">
                        <!-- Main Stream Control -->
                        <div class="text-center">
                            <button x-show="!isStreaming" @click="startBroadcast()"
                                    :disabled="connecting || false"
                                    class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-12 py-4 rounded-xl text-xl font-bold transition-all shadow-xl transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!connecting" class="flex items-center">
                                    <i class="fas fa-broadcast-tower mr-3 text-2xl"></i>
                                    GO LIVE
                                </span>
                                <span x-show="connecting" class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-3 text-2xl"></i>
                                    CONNECTING...
                                </span>
                            </button>
                            <button x-show="isStreaming" @click="stopBroadcast()"
                                    class="bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-800 hover:to-gray-900 text-white px-12 py-4 rounded-xl text-xl font-bold transition-all shadow-xl transform hover:scale-105">
                                <i class="fas fa-stop-circle mr-3 text-2xl"></i>END STREAM
                            </button>
                        </div>

                        <!-- Stream Controls -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 text-center">Stream Controls</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Audio Controls -->
                                <button @click="toggleAudio()"
                                        :class="audioEnabled ? 'bg-blue-600 hover:bg-blue-700 border-blue-600' : 'bg-red-600 hover:bg-red-700 border-red-600'"
                                        class="flex flex-col items-center text-white px-4 py-4 rounded-xl text-sm font-medium transition-all transform hover:scale-105 border-2 shadow-lg">
                                    <i :class="audioEnabled ? 'fas fa-microphone text-2xl mb-2' : 'fas fa-microphone-slash text-2xl mb-2'"></i>
                                    <span class="font-bold" x-text="audioEnabled ? 'MICROPHONE ON' : 'MICROPHONE OFF'">MICROPHONE</span>
                                    <span class="text-xs mt-1 opacity-75" x-text="audioEnabled ? 'Click to mute' : 'Click to unmute'">Click to toggle</span>
                                </button>

                                <!-- Video Controls -->
                                <button @click="toggleVideo()"
                                        :class="videoEnabled ? 'bg-blue-600 hover:bg-blue-700 border-blue-600' : 'bg-red-600 hover:bg-red-700 border-red-600'"
                                        class="flex flex-col items-center text-white px-4 py-4 rounded-xl text-sm font-medium transition-all transform hover:scale-105 border-2 shadow-lg">
                                    <i :class="videoEnabled ? 'fas fa-video text-2xl mb-2' : 'fas fa-video-slash text-2xl mb-2'"></i>
                                    <span class="font-bold" x-text="videoEnabled ? 'CAMERA ON' : 'CAMERA OFF'">CAMERA</span>
                                    <span class="text-xs mt-1 opacity-75" x-text="videoEnabled ? 'Click to disable' : 'Click to enable'">Click to toggle</span>
                                </button>

                                <!-- Screen Share -->
                                <button @click="toggleScreenShare()"
                                        :class="screenSharing ? 'bg-green-600 hover:bg-green-700 border-green-600' : 'bg-gray-600 hover:bg-gray-700 border-gray-600'"
                                        class="flex flex-col items-center text-white px-4 py-4 rounded-xl text-sm font-medium transition-all transform hover:scale-105 border-2 shadow-lg">
                                    <i :class="screenSharing ? 'fas fa-desktop text-2xl mb-2 text-green-200' : 'fas fa-desktop text-2xl mb-2'" class="mb-2"></i>
                                    <span class="font-bold" x-text="screenSharing ? 'SHARING SCREEN' : 'SHARE SCREEN'">SCREEN SHARE</span>
                                    <span class="text-xs mt-1 opacity-75" x-text="screenSharing ? 'Click to stop' : 'Click to share'">Click to toggle</span>
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Controls -->
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <!-- Camera Source Selection -->
                            <div class="relative">
                                <button @click="showCameras = !showCameras"
                                        :disabled="switchingCamera"
                                        :class="switchingCamera ? 'bg-purple-400 cursor-not-allowed' : 'bg-purple-600 hover:bg-purple-700'"
                                        class="text-white px-6 py-3 rounded-lg text-sm font-semibold transition-colors shadow-md flex items-center">
                                    <i :class="switchingCamera ? 'fas fa-spinner fa-spin' : 'fas fa-video'" class="mr-2"></i>
                                    <span x-text="switchingCamera ? 'Switching...' : 'Camera Sources'">Camera Sources</span>
                                    <i class="fas fa-chevron-down ml-2" x-show="!switchingCamera"></i>
                                </button>
                                <div x-show="showCameras" @click.away="showCameras = false"
                                     class="absolute top-full left-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-xl p-4 min-w-80 z-50">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-semibold text-gray-900 flex items-center">
                                                <i class="fas fa-video mr-2 text-purple-600"></i>
                                                Camera Sources
                                            </h4>
                                            <a href="{{ route('admin.streams.cameras', $stream) }}"
                                               class="text-blue-600 hover:text-blue-700 text-sm font-medium hover:underline">
                                                <i class="fas fa-cog mr-1"></i>
                                                Setup
                                            </a>
                                        </div>

                                        <!-- RTMP Streaming Option -->
                                        <div class="mb-4 p-3 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg">
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="text-sm font-semibold text-purple-800 flex items-center">
                                                    <i class="fas fa-broadcast-tower mr-2"></i>
                                                    Professional Software
                                                </h5>
                                                <button @click="showRtmpDetails = !showRtmpDetails"
                                                        class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                                    <i :class="showRtmpDetails ? 'fas fa-eye-slash' : 'fas fa-eye'" class="mr-1"></i>
                                                    <span x-text="showRtmpDetails ? 'Hide' : 'Show'">Show</span>
                                                </button>
                                            </div>
                                            <p class="text-xs text-purple-600 mb-3">
                                                Use OBS, XSplit, ManyCam, or SplitCam for professional broadcasting
                                            </p>
                                            <div x-show="showRtmpDetails" x-transition class="space-y-3">
                                                <div class="text-xs">
                                                    <div class="font-medium text-gray-700 mb-1">RTMP Server:</div>
                                                    <div class="flex items-center space-x-2">
                                                        <code x-text="rtmpDetails?.rtmp_url || 'Loading...'" class="text-xs bg-white px-2 py-1 rounded border flex-1"></code>
                                                        <button @click="copyToClipboard(rtmpDetails?.rtmp_url)" class="text-purple-600 hover:text-purple-800">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="text-xs">
                                                    <div class="font-medium text-gray-700 mb-1">Stream Key:</div>
                                                    <div class="flex items-center space-x-2">
                                                        <code x-text="rtmpDetails?.stream_key || 'Loading...'" class="text-xs bg-white px-2 py-1 rounded border flex-1"></code>
                                                        <button @click="copyToClipboard(rtmpDetails?.stream_key)" class="text-purple-600 hover:text-purple-800">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <button @click="loadRtmpDetails()"
                                                        class="w-full text-xs bg-purple-600 text-white px-3 py-2 rounded hover:bg-purple-700 transition-colors">
                                                    <i class="fas fa-key mr-1"></i>
                                                    Get RTMP Details
                                                </button>
                                            </div>
                                        </div>

                                        <div id="cameraSourcesList" class="space-y-2">
                                            <p class="text-sm text-gray-500 text-center py-4">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                                Loading camera sources...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="flex items-center space-x-3">
                                <button @click="refreshStream()"
                                        class="text-gray-600 hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors flex items-center"
                                        title="Refresh Stream">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    <span class="text-sm font-medium">Refresh</span>
                                </button>
                                <button @click="toggleFullscreen()"
                                        class="text-gray-600 hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors flex items-center"
                                        title="Toggle Fullscreen">
                                    <i class="fas fa-expand-alt mr-2"></i>
                                    <span class="text-sm font-medium">Fullscreen</span>
                                </button>
                                <button @click="takeScreenshot()"
                                        class="text-gray-600 hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors flex items-center"
                                        title="Take Screenshot">
                                    <i class="fas fa-camera mr-2"></i>
                                    <span class="text-sm font-medium">Screenshot</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stream Stats -->
                    <div x-show="isStreaming" x-transition class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-blue-700">Bitrate</div>
                                <i class="fas fa-tachometer-alt text-blue-600"></i>
                            </div>
                            <div class="text-2xl font-bold text-blue-800" x-text="(stats?.bitrate || 0) + ' kbps'">0 kbps</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-purple-700">Resolution</div>
                                <i class="fas fa-expand-alt text-purple-600"></i>
                            </div>
                            <div class="text-2xl font-bold text-purple-800" x-text="stats?.resolution || '0x0'">0x0</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-green-700">FPS</div>
                                <i class="fas fa-video text-green-600"></i>
                            </div>
                            <div class="text-2xl font-bold text-green-800" x-text="stats?.fps || 0">0</div>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-yellow-700">Network</div>
                                <i class="fas fa-wifi text-yellow-600"></i>
                            </div>
                            <div class="text-2xl font-bold"
                                 :class="(stats?.networkQuality || 0) >= 3 ? 'text-green-600' : (stats?.networkQuality || 0) >= 2 ? 'text-yellow-600' : 'text-red-600'"
                                 x-text="getNetworkStatus(stats?.networkQuality || 0)">Good</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat & Viewer Panel -->
        <div class="lg:col-span-1">
            <!-- Viewer List -->
            <div class="bg-white shadow-lg rounded-lg mb-6 border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-users mr-2 text-blue-600"></i>
                            Live Viewers
                        </h3>
                        <div class="flex items-center space-x-2">
                            <span class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-md" x-text="viewerCount || 0">0</span>
                            <button @click="loadViewers()" class="text-blue-600 hover:text-blue-800 text-sm" title="Refresh viewers">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-4 max-h-64 overflow-y-auto">
                    <div x-show="!viewers || viewers.length === 0" class="text-center text-gray-500 py-8">
                        <i class="fas fa-users text-4xl mb-3 opacity-30"></i>
                        <p class="text-lg font-medium">No viewers yet</p>
                        <p class="text-sm">Waiting for your audience...</p>
                    </div>
                    <div class="space-y-3" x-show="viewers && viewers.length > 0">
                        <template x-for="viewer in viewers" :key="viewer.id">
                            <div class="flex items-center space-x-3 p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 hover:shadow-md transition-all">
                                <img :src="viewer.avatar || '/images/default-avatar.png'"
                                     :alt="viewer.name"
                                     class="w-10 h-10 rounded-full border-2 border-blue-200">
                                <div class="flex-1">
                                    <div class="text-sm font-semibold text-gray-900" x-text="viewer.name"></div>
                                    <div class="text-xs text-gray-500 flex items-center">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span x-text="viewer.joinedAt"></span>
                                    </div>
                                </div>
                                <div class="w-3 h-3 bg-green-500 rounded-full shadow-md animate-pulse"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Live Chat -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-comments mr-2 text-green-600"></i>
                            Live Chat
                        </h3>
                        <button @click="loadChat()" class="text-green-600 hover:text-green-800 text-sm" title="Refresh chat">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <!-- Chat Messages -->
                    <div id="chatMessages" class="h-64 overflow-y-auto mb-4 bg-gradient-to-b from-gray-50 to-gray-100 rounded-lg p-3 border border-gray-200">
                        <div x-show="!chatMessages || chatMessages.length === 0" class="text-center text-gray-500 py-8">
                            <i class="fas fa-comments text-4xl mb-3 opacity-30"></i>
                            <p class="text-lg font-medium">No messages yet</p>
                            <p class="text-sm">Start the conversation!</p>
                        </div>
                        <div class="space-y-3" x-show="chatMessages && chatMessages.length > 0">
                            <template x-for="message in chatMessages" :key="message.id">
                                <div class="flex items-start space-x-3 p-2 hover:bg-white rounded-lg transition-colors">
                                    <img :src="message.avatar || '/images/default-avatar.png'"
                                         :alt="message.username"
                                         class="w-8 h-8 rounded-full border-2"
                                         :class="message.isAdmin ? 'border-red-300' : 'border-blue-300'">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="text-sm font-semibold"
                                                  :class="message.isAdmin ? 'text-red-700' : 'text-gray-900'"
                                                  x-text="message.username"></span>
                                            <span x-show="message.isAdmin" class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded-full font-bold">ADMIN</span>
                                            <span class="text-xs text-gray-500" x-text="message.timestamp"></span>
                                        </div>
                                        <div class="text-sm text-gray-700 leading-relaxed" x-text="message.text"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Send Message -->
                    <form @submit.prevent="sendMessage()">
                        <div class="flex space-x-2">
                            <input type="text"
                                   x-model="newMessage"
                                   placeholder="Type your message as admin..."
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                   maxlength="500">
                            <button type="submit"
                                    :disabled="!newMessage.trim()"
                                    class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md transform hover:scale-105">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agora SDK -->
<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.19.0.js"></script>

<script>
function liveBroadcast() {
    const component = {
        // Stream info
        streamId: {{ $stream->id }},
        channelName: '{{ $stream->channel_name }}',
        appId: '{{ config('services.agora.app_id') }}',
        token: null,
        uid: null,

        // State
        isStreaming: false,
        connecting: false,
        audioEnabled: true,
        videoEnabled: true,
        screenSharing: false,
        streamDuration: 0,
        viewerCount: 0,
        selectedCameraName: 'Default Camera',
        showCameras: false,
        switchingCamera: false, // Add lock for camera operations
        showRtmpDetails: false,
        rtmpDetails: null,

        // Agora client
        agoraClient: null,
        localAudioTrack: null,
        localVideoTrack: null,
        localScreenTrack: null,

        // Chat & Viewers
        viewers: [],
        chatMessages: [],
        newMessage: '',

        // Stats
        stats: {
            bitrate: 0,
            resolution: '0x0',
            fps: 0,
            networkQuality: 0
        },

        // Timers
        durationTimer: null,
        statsTimer: null,
        viewersRefreshTimer: null,
        chatRefreshTimer: null,

        async init() {
            console.log('Initializing live broadcast for stream:', this.streamId);
            console.log('Alpine.js component context:', this);

            // Small delay to ensure DOM is ready
            await new Promise(resolve => setTimeout(resolve, 100));

            // Initialize Agora client
            this.agoraClient = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
            this.agoraClient.setClientRole("host");

            // Setup event listeners
            this.setupAgoraEventListeners();

            // Get streaming token
            await this.getStreamToken();

            // Initialize local tracks (camera and microphone)
            await this.initializeLocalTracks();

            // Load camera sources
            console.log('Loading camera sources...');
            await this.loadCameraSources();

            // Load viewers and chat
            this.loadViewers();
            this.loadChat();
            
            // Start auto-refresh for viewers and chat
            this.startAutoRefresh();

            console.log('Live broadcast initialized successfully');
        },
        
        startAutoRefresh() {
            // Refresh viewers every 5 seconds
            this.viewersRefreshTimer = setInterval(() => {
                this.updateViewers();
            }, 5000);
            
            // Refresh chat every 5 seconds
            this.chatRefreshTimer = setInterval(() => {
                this.updateChat();
            }, 5000);
            
            console.log('Auto-refresh started for viewers and chat');
        },
        
        stopAutoRefresh() {
            if (this.viewersRefreshTimer) {
                clearInterval(this.viewersRefreshTimer);
                this.viewersRefreshTimer = null;
            }
            
            if (this.chatRefreshTimer) {
                clearInterval(this.chatRefreshTimer);
                this.chatRefreshTimer = null;
            }
            
            console.log('Auto-refresh stopped');
        },

        // Initial load methods
        loadViewers() {
            this.updateViewers();
        },

        loadChat() {
            this.updateChat();
        },

        async getStreamToken() {
            try {
                console.log('Requesting token for stream:', this.streamId);

                // First, let's test if Agora is configured
                const testResponse = await fetch('/admin/api/test-agora', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                if (testResponse.ok) {
                    const testData = await testResponse.json();
                    console.log('Agora test response:', testData);
                }

                const url = `/admin/api/streams/${this.streamId}/token`;
                console.log('Token URL:', url);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    const text = await response.text();
                    console.log('Error response:', text);
                    throw new Error(`HTTP ${response.status}: ${text}`);
                }

                const data = await response.json();
                console.log('Token response:', data);

                if (data.success) {
                    this.token = data.token;
                    this.uid = data.uid;
                    console.log('Token received:', { uid: this.uid });
                } else {
                    throw new Error(data.message || 'Failed to get token');
                }
            } catch (error) {
                console.error('Error getting token:', error);
                alert('Failed to get streaming token: ' + error.message);
            }
        },

        async initializeLocalTracks() {
            try {
                console.log('Initializing local tracks...');

                // Create audio track
                this.localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();

                // Create video track
                this.localVideoTrack = await AgoraRTC.createCameraVideoTrack({
                    encoderConfig: {
                        width: 1280,
                        height: 720,
                        frameRate: 30,
                        bitrateMin: 1000,
                        bitrateMax: 3000,
                    }
                });

                // Play local video with proper DOM element clearing
                const localVideoElement = document.getElementById('localVideo');
                if (localVideoElement) {
                    localVideoElement.innerHTML = '';
                    this.localVideoTrack.play(localVideoElement);
                    console.log('Initial local video track playing');
                }

                console.log('Local tracks initialized');
            } catch (error) {
                console.error('Error initializing tracks:', error);
                alert('Failed to access camera/microphone: ' + error.message);
            }
        },

        async loadCameraSources() {
            try {
                // First, load cameras from the backend (those added in camera management)
                console.log('Loading cameras from backend for stream:', this.streamId);
                const response = await fetch(`/admin/api/streams/${this.streamId}/cameras`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                console.log('Backend cameras response status:', response.status);
                let backendCameras = [];
                if (response.ok) {
                    const data = await response.json();
                    console.log('Backend cameras response data:', data);
                    if (data.success) {
                        backendCameras = data.data || [];
                        console.log('Backend cameras:', backendCameras);
                    }
                } else {
                    console.error('Failed to load backend cameras:', response.status, await response.text());
                }

                // Also get available local camera devices
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                console.log('Available local camera devices:', videoDevices);

                // Update camera sources list in dropdown
                const cameraList = document.getElementById('cameraSourcesList');
                console.log('Camera list element found:', !!cameraList);

                if (cameraList) {
                    let cameraOptions = '';

                    // Add backend cameras first (these have priority)
                    if (backendCameras.length > 0) {
                        console.log('Adding backend cameras to dropdown:', backendCameras.length);
                        cameraOptions += '<div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b">Configured Cameras</div>';
                        backendCameras.forEach(camera => {
                            cameraOptions += `
                                <button onclick="window.broadcastComponent.switchToBackendCamera('${camera.id}', '${camera.camera_name}', '${camera.device_id || ''}')"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded transition-colors"
                                        :disabled="switchingCamera"
                                        :class="switchingCamera ? 'opacity-50 cursor-not-allowed' : ''">
                                    <i class="fas fa-video mr-2 text-blue-500"></i>
                                    <span class="font-medium">${camera.camera_name}</span>
                                    ${camera.is_primary ? '<span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">PRIMARY</span>' : ''}
                                    <div class="text-xs text-gray-500">${camera.device_type || 'Unknown'} • ${camera.resolution || '720p'}</div>
                                </button>
                            `;
                        });
                    }

                    // Add local devices
                    if (videoDevices.length > 0) {
                        console.log('Adding local devices to dropdown:', videoDevices.length);
                        if (backendCameras.length > 0) {
                            cameraOptions += '<div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-t mt-2">Local Devices</div>';
                        } else {
                            cameraOptions += '<div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b">Available Cameras</div>';
                        }
                        videoDevices.forEach((device, index) => {
                            // Check if this device is already configured in backend
                            const isConfigured = backendCameras.some(bc => bc.device_id === device.deviceId);
                            if (!isConfigured) {
                                cameraOptions += `
                                    <button onclick="window.broadcastComponent.switchCameraSource('${device.deviceId}', '${device.label || 'Camera'}')"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded transition-colors"
                                            :disabled="switchingCamera"
                                            :class="switchingCamera ? 'opacity-50 cursor-not-allowed' : ''">
                                        <i class="fas fa-video mr-2 text-gray-400"></i>
                                        <span>${device.label || 'Camera ' + (index + 1)}</span>
                                        <div class="text-xs text-gray-500">Local device</div>
                                    </button>
                                `;
                            }
                        });
                    }

                    if (cameraOptions) {
                        console.log('Setting camera dropdown HTML:', cameraOptions.length, 'characters');
                        cameraList.innerHTML = cameraOptions;
                        console.log('Camera dropdown updated successfully');
                    } else {
                        console.log('No cameras found, showing fallback message');
                        cameraList.innerHTML = `
                            <p class="text-sm text-gray-500 px-3 py-2">No cameras available</p>
                            <a href="{{ route('admin.streams.cameras', $stream) }}" class="block text-sm text-blue-600 hover:text-blue-700 px-3 py-2">
                                <i class="fas fa-plus mr-2"></i>Add cameras in Camera Management
                            </a>
                        `;
                    }
                } else {
                    console.error('Camera list element not found!');
                }

                // Auto-select primary camera if available
                const primaryCamera = backendCameras.find(camera => camera.is_primary);
                if (primaryCamera && primaryCamera.device_id) {
                    console.log('Auto-selecting primary camera:', primaryCamera.camera_name);
                    await this.switchToBackendCamera(primaryCamera.id, primaryCamera.camera_name, primaryCamera.device_id);
                }

            } catch (error) {
                console.error('Error loading camera sources:', error);
                const cameraList = document.getElementById('cameraSourcesList');
                if (cameraList) {
                    cameraList.innerHTML = '<p class="text-sm text-red-500 px-3 py-2">Error loading cameras</p>';
                }
            }
        },

        async switchToBackendCamera(cameraId, cameraName, deviceId) {
            try {
                console.log('Switching to backend camera:', cameraName, 'Device ID:', deviceId);

                if (deviceId) {
                    // Use the configured device ID
                    await this.switchCameraSource(deviceId, cameraName);
                } else {
                    // Fallback to camera name matching
                    await this.switchCameraSource(null, cameraName);
                }

                // Update UI to show selected camera
                this.selectedCameraName = cameraName;

            } catch (error) {
                console.error('Error switching to backend camera:', error);
                alert('Failed to switch to camera: ' + error.message);
            }
        },

        async switchCameraSource(deviceId, deviceName) {
            // Prevent multiple simultaneous camera operations
            if (this.switchingCamera) {
                console.log('Camera switch already in progress, ignoring request');
                return;
            }

            this.switchingCamera = true;

            try {
                console.log('Switching to camera:', deviceName, deviceId);

                // Store reference to old video track
                const oldVideoTrack = this.localVideoTrack;

                // Create new video track with selected camera
                const newVideoTrack = await AgoraRTC.createCameraVideoTrack({
                    cameraId: deviceId,
                    encoderConfig: {
                        width: 1280,
                        height: 720,
                        frameRate: 30,
                        bitrateMin: 1000,
                        bitrateMax: 3000,
                    }
                });

                console.log('New video track created successfully');

                // If streaming, use simple replacement approach
                if (this.isStreaming && this.agoraClient) {
                    console.log('Simple camera replacement for live stream...');

                    try {
                        // Stop old track first to free up resources
                        if (oldVideoTrack) {
                            oldVideoTrack.stop();
                            console.log('Old track stopped');
                        }

                        // Update reference immediately
                        this.localVideoTrack = newVideoTrack;

                        // Use helper function to update preview
                        this.updateVideoPreview(this.localVideoTrack);
                        console.log('New track playing locally');

                        // For live streams, let's try a different approach
                        // Instead of unpublish/publish, let's restart the stream
                        console.log('Restarting stream with new camera...');

                        // Leave and rejoin with new track
                        await this.agoraClient.unpublish();
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Publish with new track
                        await this.agoraClient.publish([this.localAudioTrack, this.localVideoTrack]);
                        console.log('Stream restarted with new camera');

                        // Clean up old track
                        if (oldVideoTrack) {
                            try {
                                oldVideoTrack.close();
                                console.log('Old track cleaned up');
                            } catch (cleanupError) {
                                console.error('Error cleaning up old track:', cleanupError);
                            }
                        }

                    } catch (streamError) {
                        console.error('Error during stream camera switch:', streamError);

                        // Try to restore with a completely fresh start
                        try {
                            console.log('Attempting complete stream recovery...');

                            // Stop everything
                            await this.agoraClient.unpublish().catch(e => console.log('Unpublish error ignored:', e));

                            // Clean up failed track
                            if (newVideoTrack && newVideoTrack !== this.localVideoTrack) {
                                newVideoTrack.stop();
                                newVideoTrack.close();
                            }

                            // Create a fresh video track (default camera)
                            this.localVideoTrack = await AgoraRTC.createCameraVideoTrack({
                                encoderConfig: {
                                    width: 1280,
                                    height: 720,
                                    frameRate: 30,
                                    bitrateMin: 1000,
                                    bitrateMax: 3000,
                                }
                            });

                            // Use helper function to update preview
                            this.updateVideoPreview(this.localVideoTrack);

                            // Restart stream
                            await new Promise(resolve => setTimeout(resolve, 500));
                            await this.agoraClient.publish([this.localAudioTrack, this.localVideoTrack]);

                            console.log('Stream recovered with default camera');
                            throw new Error(`Camera switch failed. Stream recovered with default camera. Consider using OBS Virtual Camera for smoother switching.`);

                        } catch (recoveryError) {
                            console.error('Recovery failed:', recoveryError);
                            throw new Error(`Camera switch failed and recovery unsuccessful. Please restart the broadcast or use OBS/ManyCam virtual camera.`);
                        }
                    }

                } else {
                    // Not streaming, simple local replacement
                    console.log('Replacing video track locally (not streaming)...');

                    if (oldVideoTrack) {
                        oldVideoTrack.stop();
                        oldVideoTrack.close();
                    }

                    this.localVideoTrack = newVideoTrack;

                    // Use helper function to update preview
                    this.updateVideoPreview(this.localVideoTrack);
                    console.log('Local preview updated for new camera (not streaming)');
                }

                // Update selected camera name
                this.selectedCameraName = deviceName;
                console.log(`Successfully switched to: ${deviceName}`);

            } catch (error) {
                console.error('Error switching camera source:', error);
                alert(error.message);
            } finally {
                // Always release the lock
                this.switchingCamera = false;
            }
        },

        setupAgoraEventListeners() {
            // User published
            this.agoraClient.on("user-published", async (user, mediaType) => {
                console.log('User published:', user.uid, mediaType);
            });

            // User unpublished
            this.agoraClient.on("user-unpublished", (user, mediaType) => {
                console.log('User unpublished:', user.uid, mediaType);
            });

            // User joined
            this.agoraClient.on("user-joined", (user) => {
                console.log('User joined:', user.uid);
                this.updateViewerCount();
            });

            // User left
            this.agoraClient.on("user-left", (user) => {
                console.log('User left:', user.uid);
                this.updateViewerCount();
            });

            // Network quality
            this.agoraClient.on("network-quality", (stats) => {
                this.stats.networkQuality = stats.uplinkNetworkQuality;
            });
        },

        async startBroadcast() {
            if (!this.token || !this.localAudioTrack || !this.localVideoTrack) {
                alert('Not ready to broadcast. Please wait...');
                return;
            }

            this.connecting = true;

            try {
                console.log('Starting broadcast...');

                // Join channel
                await this.agoraClient.join(this.appId, this.channelName, this.token, this.uid);

                // Publish tracks
                await this.agoraClient.publish([this.localAudioTrack, this.localVideoTrack]);

                // Update stream status
                await this.updateStreamStatus('live');

                this.isStreaming = true;
                this.connecting = false;

                // Start timers
                this.startTimers();

                // Show end broadcast button
                document.getElementById('endBroadcastBtn').style.display = 'block';

                console.log('Broadcast started successfully');

            } catch (error) {
                console.error('Error starting broadcast:', error);
                alert('Failed to start broadcast: ' + error.message);
                this.connecting = false;
            }
        },

        async stopBroadcast() {
            try {
                console.log('Stopping broadcast...');

                // Stop timers
                this.stopTimers();

                // Unpublish tracks
                if (this.agoraClient) {
                    await this.agoraClient.unpublish();
                    await this.agoraClient.leave();
                }

                // Update stream status
                await this.updateStreamStatus('ended');

                this.isStreaming = false;

                // Hide end broadcast button
                document.getElementById('endBroadcastBtn').style.display = 'none';

                console.log('Broadcast stopped successfully');

                // Redirect to stream details
                setTimeout(() => {
                    window.location.href = `/admin/streams/${this.streamId}`;
                }, 2000);

            } catch (error) {
                console.error('Error stopping broadcast:', error);
                alert('Failed to stop broadcast: ' + error.message);
            }
        },

        async toggleAudio() {
            if (this.localAudioTrack) {
                await this.localAudioTrack.setEnabled(!this.audioEnabled);
                this.audioEnabled = !this.audioEnabled;
            }
        },

        async toggleVideo() {
            if (this.localVideoTrack) {
                await this.localVideoTrack.setEnabled(!this.videoEnabled);
                this.videoEnabled = !this.videoEnabled;
            }
        },

        async toggleScreenShare() {
            try {
                if (!this.screenSharing) {
                    console.log('Starting screen sharing...');

                    // Create screen share track first
                    this.localScreenTrack = await AgoraRTC.createScreenVideoTrack();

                    if (this.isStreaming && this.agoraClient) {
                        console.log('Replacing camera with screen share...');

                        // Unpublish camera track first and wait
                        await this.agoraClient.unpublish([this.localVideoTrack]);
                        console.log('Camera track unpublished');

                        // Longer delay for stability
                        await new Promise(resolve => setTimeout(resolve, 200));

                        // Publish screen share track
                        await this.agoraClient.publish([this.localScreenTrack]);
                        console.log('Screen share track published');
                    }

                    // Play screen share locally
                    this.localScreenTrack.play('localVideo');
                    this.screenSharing = true;
                    console.log('Screen sharing started successfully');

                } else {
                    console.log('Stopping screen sharing...');

                    if (this.isStreaming && this.agoraClient) {
                        console.log('Replacing screen share with camera...');

                        // Unpublish screen share track first and wait
                        await this.agoraClient.unpublish([this.localScreenTrack]);
                        console.log('Screen share track unpublished');

                        // Longer delay for stability
                        await new Promise(resolve => setTimeout(resolve, 200));

                        // Publish camera track
                        await this.agoraClient.publish([this.localVideoTrack]);
                        console.log('Camera track republished');
                    }

                    // Clean up screen share track
                    this.localScreenTrack.stop();
                    this.localScreenTrack.close();
                    this.localScreenTrack = null;

                    // Play camera locally
                    this.localVideoTrack.play('localVideo');
                    this.screenSharing = false;
                    console.log('Screen sharing stopped successfully');
                }
            } catch (error) {
                console.error('Error toggling screen share:', error);
                alert('Failed to toggle screen share: ' + error.message);

                // Try to restore previous state
                if (this.screenSharing && this.localScreenTrack) {
                    try {
                        this.localScreenTrack.stop();
                        this.localScreenTrack.close();
                        this.localScreenTrack = null;
                    } catch (cleanupError) {
                        console.error('Error cleaning up screen share track:', cleanupError);
                    }
                }

                this.screenSharing = false;

                // Ensure camera is playing locally
                if (this.localVideoTrack) {
                    this.localVideoTrack.play('localVideo');
                }
            }
        },

        async updateStreamStatus(status) {
            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/${status === 'live' ? 'start' : 'end'}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();
                if (!data.success) {
                    console.error('Failed to update stream status:', data.message);
                }
            } catch (error) {
                console.error('Error updating stream status:', error);
            }
        },

        startTimers() {
            // Duration timer
            this.durationTimer = setInterval(() => {
                this.streamDuration++;
            }, 1000);

            // Stats timer
            this.statsTimer = setInterval(() => {
                this.updateStats();
            }, 5000);
        },

        stopTimers() {
            if (this.durationTimer) {
                clearInterval(this.durationTimer);
                this.durationTimer = null;
            }

            if (this.statsTimer) {
                clearInterval(this.statsTimer);
                this.statsTimer = null;
            }
            
            // Also stop auto-refresh timers
            this.stopAutoRefresh();
        },

        async updateStats() {
            if (this.agoraClient && this.isStreaming) {
                try {
                    const stats = this.agoraClient.getRTCStats();
                    this.stats.bitrate = Math.round(stats.sendBitrate || 0);

                    if (this.localVideoTrack) {
                        const videoStats = this.localVideoTrack.getStats();
                        this.stats.resolution = `${videoStats.sendResolutionWidth || 0}x${videoStats.sendResolutionHeight || 0}`;
                        this.stats.fps = videoStats.sendFrameRate || 0;
                    }
                } catch (error) {
                    console.error('Error getting stats:', error);
                }
            }
        },

        startPolling() {
            // Poll for viewers every 10 seconds
            setInterval(() => {
                this.updateViewers();
                this.updateChat();
            }, 10000);

            // Initial fetch
            this.updateViewers();
            this.updateChat();
        },

        async updateViewers() {
            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/viewers`);
                const data = await response.json();

                if (data.success) {
                    this.viewers = data.viewers.filter(v => !v.left_at).map(viewer => ({
                        id: viewer.id,
                        name: viewer.user?.name || 'Anonymous',
                        avatar: viewer.user?.profile_picture,
                        joinedAt: new Date(viewer.joined_at).toLocaleTimeString()
                    }));
                    this.viewerCount = this.viewers.length;
                }
            } catch (error) {
                console.error('Error updating viewers:', error);
            }
        },

        async updateChat() {
            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/chats`);
                const data = await response.json();

                if (data.success) {
                    this.chatMessages = data.data.map(msg => ({
                        id: msg.id,
                        username: msg.username || msg.user?.name || 'Anonymous',
                        text: msg.message,
                        avatar: msg.user_profile_url || msg.user?.profile_picture,
                        isAdmin: msg.is_admin,
                        timestamp: new Date(msg.created_at).toLocaleTimeString()
                    }));

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

        async sendMessage() {
            if (!this.newMessage.trim()) return;

            try {
                const response = await fetch(`/admin/api/streams/${this.streamId}/chats`, {
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
        },

        formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        getNetworkStatus(quality) {
            switch(quality) {
                case 1: return 'Excellent';
                case 2: return 'Good';
                case 3: return 'Fair';
                case 4: return 'Poor';
                case 5: return 'Bad';
                default: return 'Unknown';
            }
        },

        updateViewerCount() {
            this.viewerCount = this.agoraClient.remoteUsers.length;
        },

        // RTMP Streaming Methods
        async loadRtmpDetails() {
            try {
                console.log('Loading RTMP details for stream:', this.streamId);

                const response = await fetch(`/admin/api/streams/${this.streamId}/rtmp-details`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('RTMP details loaded:', data);

                    if (data.success) {
                        this.rtmpDetails = data.data;
                        this.showRtmpDetails = true;

                        // Show detailed RTMP setup modal
                        this.showRtmpSetupModal(data.data);
                    } else {
                        alert('Failed to get RTMP details: ' + data.message);
                    }
                } else {
                    const errorText = await response.text();
                    console.error('RTMP details error:', errorText);
                    alert('Failed to load RTMP details');
                }
            } catch (error) {
                console.error('Error loading RTMP details:', error);
                alert('Error loading RTMP details: ' + error.message);
            }
        },

        showRtmpSetupModal(rtmpData) {
            const modalContent = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="this.remove()">
                    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto" onclick="event.stopPropagation()">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold">RTMP Streaming Setup</h3>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-800 mb-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Connection Details
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <strong>RTMP Server:</strong>
                                        <code class="bg-gray-100 px-2 py-1 rounded">${rtmpData.rtmp_url}</code>
                                        <button onclick="navigator.clipboard.writeText('${rtmpData.rtmp_url}')" class="ml-2 text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <strong>Stream Key:</strong>
                                        <code class="bg-gray-100 px-2 py-1 rounded">${rtmpData.stream_key}</code>
                                        <button onclick="navigator.clipboard.writeText('${rtmpData.stream_key}')" class="ml-2 text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-blue-800 mb-2">
                                        <i class="fas fa-video mr-2"></i>ManyCam Setup
                                    </h4>
                                    <ol class="text-xs space-y-1 text-blue-700">
                                        ${rtmpData.software_guides?.manycam?.steps?.map(step => `<li>${step}</li>`).join('') || ''}
                                    </ol>
                                </div>

                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-green-800 mb-2">
                                        <i class="fas fa-cut mr-2"></i>SplitCam Setup
                                    </h4>
                                    <ol class="text-xs space-y-1 text-green-700">
                                        ${rtmpData.software_guides?.splitcam?.steps?.map(step => `<li>${step}</li>`).join('') || ''}
                                    </ol>
                                </div>
                            </div>

                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Recommended Settings</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                    <div>
                                        <strong>Resolution:</strong><br>
                                        <span class="text-gray-600">${rtmpData.recommended_settings?.resolution || '1920x1080'}</span>
                                    </div>
                                    <div>
                                        <strong>Bitrate:</strong><br>
                                        <span class="text-gray-600">${rtmpData.recommended_settings?.bitrate || '3000 kbps'}</span>
                                    </div>
                                    <div>
                                        <strong>FPS:</strong><br>
                                        <span class="text-gray-600">${rtmpData.recommended_settings?.fps || '30'}</span>
                                    </div>
                                    <div>
                                        <strong>Audio:</strong><br>
                                        <span class="text-gray-600">${rtmpData.recommended_settings?.audio_bitrate || '128 kbps'}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-2">
                                <button onclick="this.closest('.fixed').remove()"
                                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                    Close
                                </button>
                                <a href="/admin/streams/${this.streamId}/cameras"
                                   class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Manage Cameras
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalContent);
        },

        // New helper functions for improved UX
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showNotification('Copied to clipboard!', 'success');
            } catch (err) {
                console.error('Failed to copy text: ', err);
                this.showNotification('Failed to copy text', 'error');
            }
        },

        showNotification(message, type = 'info') {
            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all transform translate-x-full`;

            if (type === 'success') {
                toast.className += ' bg-green-600';
            } else if (type === 'error') {
                toast.className += ' bg-red-600';
            } else {
                toast.className += ' bg-blue-600';
            }

            toast.textContent = message;
            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        },

        refreshStream() {
            this.loadViewers();
            this.loadChat();
            this.showNotification('Stream refreshed', 'success');
        },

        // Helper function to properly update video preview
        updateVideoPreview(videoTrack, elementId = 'localVideo') {
            try {
                const videoElement = document.getElementById(elementId);
                if (!videoElement) {
                    console.error('Video element not found:', elementId);
                    return false;
                }

                // Clear any existing video content
                videoElement.innerHTML = '';

                // Wait a brief moment for DOM to update
                setTimeout(() => {
                    videoTrack.play(videoElement);
                    console.log('Video preview updated for element:', elementId);
                }, 50);

                return true;
            } catch (error) {
                console.error('Error updating video preview:', error);
                return false;
            }
        },

        toggleFullscreen() {
            const videoContainer = document.getElementById('localVideo').parentElement;
            if (!document.fullscreenElement) {
                videoContainer.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                    this.showNotification('Fullscreen not supported', 'error');
                });
            } else {
                document.exitFullscreen();
            }
        },

        async takeScreenshot() {
            try {
                const video = document.querySelector('#localVideo video');
                if (video) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);

                    const link = document.createElement('a');
                    link.download = `stream-screenshot-${Date.now()}.png`;
                    link.href = canvas.toDataURL();
                    link.click();

                    this.showNotification('Screenshot saved!', 'success');
                } else {
                    this.showNotification('No video stream available', 'error');
                }
            } catch (error) {
                console.error('Screenshot error:', error);
                this.showNotification('Failed to take screenshot', 'error');
            }
        }
    };

    // Make component globally accessible for onclick handlers
    window.broadcastComponent = component;
    return component;
}

// End broadcast function for header button
async function endBroadcast() {
    if (confirm('Are you sure you want to end the broadcast?')) {
        // This will be handled by the Alpine component
        const broadcastComponent = document.querySelector('[x-data="liveBroadcast()"]').__x.$data;
        await broadcastComponent.stopBroadcast();
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // The Alpine component will auto-initialize
});
</script>

<style>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-slide-in {
    animation: slideInRight 0.3s ease-out;
}

#localVideo {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
    border-radius: 0.5rem;
}

/* Enhanced scrollbar styling */
#chatMessages::-webkit-scrollbar {
    width: 6px;
}

#chatMessages::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #9ca3af 0%, #6b7280 100%);
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #6b7280 0%, #4b5563 100%);
}

/* Button hover effects */
.transform:hover {
    transform: translateY(-1px);
}

/* Gradient backgrounds for cards */
.gradient-card {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
}

/* Live indicator enhancement */
.live-pulse::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ef4444, #dc2626, #b91c1c);
    border-radius: inherit;
    z-index: -1;
    animation: pulse 2s ease-in-out infinite;
}

/* Toast notification positioning */
.toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1000;
}
    border-radius: 2px;
}

#chatMessages::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endsection
