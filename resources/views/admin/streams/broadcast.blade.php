@extends('admin.layouts.app')

@section('title', 'Live Broadcast - ' . $stream->title)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">L                            <input type="text"
                                   x-model="newMessage"
                                   placeholder="Type a message..."
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                   maxlength="500">
                            <button type="submit"
                                    :disabled="!newMessage || !newMessage.trim()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-paper-plane"></i>
                            </button>ast: {{ $stream->title }}</h1>
            <div class="flex items-center mt-2 space-x-4">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    @if($stream->status === 'live') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    <i class="fas fa-circle mr-1 @if($stream->status === 'live') text-red-500 animate-pulse @else text-yellow-500 @endif"></i>
                    {{ $stream->status === 'live' ? 'LIVE' : 'PREPARING' }}
                </span>
                <span class="text-sm text-gray-500">Channel: {{ $stream->channel_name }}</span>
            </div>
        </div>
        <div class="flex space-x-3">
            <button id="endBroadcastBtn" onclick="endBroadcast()"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    style="display: none;">
                <i class="fas fa-stop mr-2"></i>End Broadcast
            </button>
            <a href="{{ route('admin.streams.cameras', $stream) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-video mr-2"></i>Camera Management
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
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <!-- Audio Controls -->
                            <button @click="toggleAudio()"
                                    :class="audioEnabled ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700'"
                                    class="text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i :class="audioEnabled ? 'fas fa-microphone' : 'fas fa-microphone-slash'" class="mr-2"></i>
                                <span x-text="audioEnabled ? 'Mute' : 'Unmute'">Mute</span>
                            </button>

                            <!-- Video Controls -->
                            <button @click="toggleVideo()"
                                    :class="videoEnabled ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700'"
                                    class="text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i :class="videoEnabled ? 'fas fa-video' : 'fas fa-video-slash'" class="mr-2"></i>
                                <span x-text="videoEnabled ? 'Stop Video' : 'Start Video'">Stop Video</span>
                            </button>

                            <!-- Screen Share -->
                            <button @click="toggleScreenShare()"
                                    :class="screenSharing ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'"
                                    class="text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i :class="screenSharing ? 'fas fa-desktop' : 'fas fa-desktop'" class="mr-2"></i>
                                <span x-text="screenSharing ? 'Stop Sharing' : 'Share Screen'">Share Screen</span>
                            </button>

                            <!-- Multi-Camera Controls -->
                            <div class="relative">
                                <button @click="showCameras = !showCameras"
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-video mr-2"></i>Cameras
                                    <i class="fas fa-chevron-down ml-1"></i>
                                </button>
                                <div x-show="showCameras" @click.away="showCameras = false"
                                     class="absolute top-full left-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-64 z-50">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-900">Camera Sources</h4>
                                            <a href="{{ route('admin.streams.cameras', $stream) }}"
                                               class="text-blue-600 hover:text-blue-700 text-sm">
                                                Manage
                                            </a>
                                        </div>
                                        <div id="cameraSourcesList" class="space-y-2">
                                            <p class="text-sm text-gray-500">Loading camera sources...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Broadcast Button -->
                        <div>
                            <button x-show="!isStreaming" @click="startBroadcast()"
                                    :disabled="connecting || false"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md text-lg font-medium transition-colors disabled:opacity-50">
                                <span x-show="!connecting">
                                    <i class="fas fa-play mr-2"></i>Start Broadcast
                                </span>
                                <span x-show="connecting">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Connecting...
                                </span>
                            </button>
                            <button x-show="isStreaming" @click="stopBroadcast()"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-md text-lg font-medium transition-colors">
                                <i class="fas fa-stop mr-2"></i>Stop Broadcast
                            </button>
                        </div>
                    </div>

                    <!-- Stream Stats -->
                    <div x-show="isStreaming" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="font-medium text-gray-500">Bitrate</div>
                            <div class="text-lg font-bold" x-text="(stats?.bitrate || 0) + ' kbps'">0 kbps</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="font-medium text-gray-500">Resolution</div>
                            <div class="text-lg font-bold" x-text="stats?.resolution || '0x0'">0x0</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="font-medium text-gray-500">FPS</div>
                            <div class="text-lg font-bold" x-text="stats?.fps || 0">0</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="font-medium text-gray-500">Network</div>
                            <div class="text-lg font-bold" :class="(stats?.networkQuality || 0) >= 3 ? 'text-green-600' : (stats?.networkQuality || 0) >= 2 ? 'text-yellow-600' : 'text-red-600'"
                                 x-text="getNetworkStatus(stats?.networkQuality || 0)">Good</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat & Viewer Panel -->
        <div class="lg:col-span-1">
            <!-- Viewer List -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Live Viewers</h3>
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium" x-text="viewerCount || 0">0</span>
                    </div>
                </div>
                <div class="p-4 max-h-64 overflow-y-auto">
                    <div x-show="!viewers || viewers.length === 0" class="text-center text-gray-500 py-8">
                        <i class="fas fa-users text-4xl mb-2 opacity-50"></i>
                        <p>No viewers yet</p>
                    </div>
                    <div class="space-y-2" x-show="viewers && viewers.length > 0">
                        <template x-for="viewer in viewers" :key="viewer.id">
                            <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded">
                                <img :src="viewer.avatar || '/images/default-avatar.png'"
                                     :alt="viewer.name"
                                     class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <div class="text-sm font-medium" x-text="viewer.name"></div>
                                    <div class="text-xs text-gray-500" x-text="viewer.joinedAt"></div>
                                </div>
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Live Chat -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Live Chat</h3>
                </div>
                <div class="p-4">
                    <!-- Chat Messages -->
                    <div id="chatMessages" class="h-64 overflow-y-auto mb-4 bg-gray-50 rounded p-3">
                        <div x-show="!chatMessages || chatMessages.length === 0" class="text-center text-gray-500 py-8">
                            <i class="fas fa-comments text-4xl mb-2 opacity-50"></i>
                            <p>No messages yet</p>
                        </div>
                        <div class="space-y-2" x-show="chatMessages && chatMessages.length > 0">
                            <template x-for="message in chatMessages" :key="message.id">
                                <div class="flex items-start space-x-2">
                                    <img :src="message.avatar || '/images/default-avatar.png'"
                                         :alt="message.username"
                                         class="w-6 h-6 rounded-full">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium" x-text="message.username"></span>
                                            <span x-show="message.isAdmin" class="px-1 py-0.5 bg-red-100 text-red-800 text-xs rounded font-medium">ADMIN</span>
                                            <span class="text-xs text-gray-500" x-text="message.timestamp"></span>
                                        </div>
                                        <div class="text-sm text-gray-700" x-text="message.text"></div>
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
                                   placeholder="Type a message..."
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                   maxlength="500">
                            <button type="submit"
                                    :disabled="!newMessage.trim()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
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

            console.log('Live broadcast initialized successfully');
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

                // Play local video
                this.localVideoTrack.play('localVideo');

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
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded transition-colors">
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
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded transition-colors">
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
            try {
                console.log('Switching to camera:', deviceName, deviceId);

                if (this.localVideoTrack) {
                    // Stop current video track
                    this.localVideoTrack.stop();
                    this.localVideoTrack.close();
                }

                // Create new video track with selected camera
                this.localVideoTrack = await AgoraRTC.createCameraVideoTrack({
                    cameraId: deviceId,
                    encoderConfig: {
                        width: 1280,
                        height: 720,
                        frameRate: 30,
                        bitrateMin: 1000,
                        bitrateMax: 3000,
                    }
                });

                // Play new video
                this.localVideoTrack.play('localVideo');

                // If streaming, republish with new track
                if (this.isStreaming && this.agoraClient) {
                    try {
                        await this.agoraClient.unpublish([this.localVideoTrack]);
                        await this.agoraClient.publish([this.localVideoTrack]);
                        console.log('Republished with new camera source');
                    } catch (republishError) {
                        console.error('Error republishing with new camera:', republishError);
                    }
                }

                alert(`Switched to: ${deviceName}`);

            } catch (error) {
                console.error('Error switching camera source:', error);
                alert('Failed to switch camera: ' + error.message);
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
                    // Start screen sharing
                    this.localScreenTrack = await AgoraRTC.createScreenVideoTrack();

                    if (this.isStreaming) {
                        await this.agoraClient.unpublish(this.localVideoTrack);
                        await this.agoraClient.publish(this.localScreenTrack);
                    }

                    this.localScreenTrack.play('localVideo');
                    this.screenSharing = true;

                } else {
                    // Stop screen sharing
                    if (this.isStreaming) {
                        await this.agoraClient.unpublish(this.localScreenTrack);
                        await this.agoraClient.publish(this.localVideoTrack);
                    }

                    this.localScreenTrack.stop();
                    this.localScreenTrack.close();
                    this.localVideoTrack.play('localVideo');
                    this.screenSharing = false;
                }
            } catch (error) {
                console.error('Error toggling screen share:', error);
                alert('Failed to toggle screen share: ' + error.message);
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

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

#localVideo {
    background: #000;
}

#chatMessages::-webkit-scrollbar {
    width: 4px;
}

#chatMessages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#chatMessages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 2px;
}

#chatMessages::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endsection
