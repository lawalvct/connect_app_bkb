@extends('admin.layouts.app')

@section('title', 'Camera Management - ' . $stream->title)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Camera Management: {{ $stream->title }}</h1>
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
            <a href="{{ route('admin.streams.broadcast', $stream) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-video mr-2"></i>Go Live
            </a>
            <a href="{{ route('admin.streams.show', $stream) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Stream
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="cameraManagement()" x-init="init()">
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

        <!-- Camera Grid -->
        <div class="xl:col-span-3">
            <!-- Active Camera Preview -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Live Preview</h3>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500">Active Camera:</span>
                            <span class="font-medium text-blue-600" x-text="activeCameraName">Primary Camera</span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                                        <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
                        <div id="activeCameraPreview" class="w-full h-full"></div>
                        <div class="absolute top-4 left-4 bg-black bg-opacity-75 text-white px-3 py-2 rounded" x-text="activeCameraName">
                            Primary Camera
                        </div>
                        <div x-show="isLive" class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold animate-pulse">
                            LIVE
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Grid -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Camera Sources</h3>
                        <button @click="showAddCameraModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Camera Device
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="camera in cameras" :key="camera.id">
                            <div class="relative border rounded-lg overflow-hidden bg-gray-50"
                                 :class="camera.is_primary ? 'border-blue-500 border-2' : 'border-gray-200'">

                                <!-- Camera Preview -->
                                <div class="relative bg-black" style="aspect-ratio: 16/9;">
                                    <div :id="'camera-preview-' + camera.id" class="w-full h-full"></div>

                                    <!-- Camera Status Overlay -->
                                    <div x-show="!camera.is_active" class="absolute inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center">
                                        <div class="text-center text-white">
                                            <i class="fas fa-video-slash text-3xl mb-2 opacity-50"></i>
                                            <p class="text-sm">Camera Offline</p>
                                        </div>
                                    </div>

                                    <!-- Camera Name -->
                                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-sm">
                                        <span x-text="camera.camera_name"></span>
                                    </div>

                                    <!-- Primary Badge -->
                                    <div x-show="camera.is_primary" class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                        PRIMARY
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-bold"
                                         :class="camera.is_active ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                                        <i :class="camera.is_active ? 'fas fa-circle' : 'fas fa-times-circle'" class="mr-1"></i>
                                        <span x-text="camera.is_active ? 'LIVE' : 'OFFLINE'"></span>
                                    </div>
                                </div>

                                <!-- Camera Controls -->
                                <div class="p-3 bg-white">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900" x-text="camera.camera_name"></h4>
                                        <div class="flex space-x-1">
                                            <!-- Switch to Primary -->
                                            <button x-show="!camera.is_primary && camera.is_active"
                                                    @click="switchCamera(camera.id)"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs font-medium transition-colors">
                                                <i class="fas fa-exchange-alt mr-1"></i>Switch
                                            </button>

                                            <!-- Remove Camera -->
                                            <button x-show="!camera.is_primary"
                                                    @click="removeCamera(camera.id)"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs font-medium transition-colors">
                                                <i class="fas fa-trash mr-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500 space-y-1">
                                        <div class="flex justify-between">
                                            <span>Device:</span>
                                            <span class="capitalize" x-text="camera.device_type || 'Unknown'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Resolution:</span>
                                            <span x-text="camera.resolution || 'Auto'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Status:</span>
                                            <span class="capitalize" x-text="camera.status"></span>
                                        </div>
                                    </div>

                                    <!-- Connection Toggle -->
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <button @click="toggleCameraConnection(camera.id, !camera.is_active)"
                                                :class="camera.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                                                class="w-full text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                                            <i :class="camera.is_active ? 'fas fa-stop' : 'fas fa-play'" class="mr-2"></i>
                                            <span x-text="camera.is_active ? 'Disconnect' : 'Connect'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div x-show="cameras.length === 0 && !detectingDevices" class="col-span-full text-center py-12">
                            <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No cameras configured</h3>
                            <p class="text-gray-500 mb-4">Detect available camera devices to start multi-cam streaming</p>
                            <button @click="detectDevices()" :disabled="detectingDevices"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md disabled:opacity-50 mr-3">
                                <i class="fas fa-search mr-2"></i>Detect Camera Devices
                            </button>
                            <button @click="showAddCameraModal = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-plus mr-2"></i>Add Manual Camera
                            </button>
                        </div>

                        <!-- Detecting State -->
                        <div x-show="detectingDevices" class="col-span-full text-center py-12">
                            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Detecting Devices...</h3>
                            <p class="text-gray-500">Scanning for available camera devices</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="xl:col-span-1">
            <!-- Mixer Settings -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Mixer Settings</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Layout Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
                        <select x-model="mixerSettings.layout_type" @change="updateMixerSettings()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="single">Single Camera</option>
                            <option value="picture_in_picture">Picture in Picture</option>
                            <option value="split_screen">Split Screen</option>
                            <option value="quad_view">Quad View</option>
                        </select>
                    </div>

                    <!-- Transition Effect -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transition Effect</label>
                        <select x-model="mixerSettings.transition_effect" @change="updateMixerSettings()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="cut">Cut</option>
                            <option value="fade">Fade</option>
                            <option value="slide">Slide</option>
                            <option value="zoom">Zoom</option>
                        </select>
                    </div>

                    <!-- Transition Duration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transition Duration</label>
                        <div class="flex items-center space-x-2">
                            <input type="range" x-model="mixerSettings.transition_duration" @change="updateMixerSettings()"
                                   min="100" max="5000" step="100"
                                   class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <span class="text-sm text-gray-600" x-text="mixerSettings.transition_duration + 'ms'">1000ms</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Switch History -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Switch History</h3>
                </div>
                <div class="p-4 max-h-64 overflow-y-auto">
                    <div x-show="switchHistory.length === 0" class="text-center text-gray-500 py-8">
                        <i class="fas fa-history text-3xl mb-2 opacity-50"></i>
                        <p class="text-sm">No camera switches yet</p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="switch in switchHistory" :key="switch.id">
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exchange-alt text-blue-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-gray-900">
                                        <span x-text="switch.from_camera || 'None'"></span> →
                                        <span x-text="switch.to_camera"></span>
                                    </div>
                                    <div class="text-gray-500" x-text="formatDateTime(switch.switched_at)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Detection Modal -->
    <div x-show="showDeviceModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.away="showDeviceModal = false"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
         style="display: none;">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Available Camera Devices</h3>
                    <button @click="showDeviceModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
                <div x-show="availableDevices.length === 0" class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-4"></i>
                    <p class="text-gray-600">No camera devices found. Make sure cameras are connected and permissions are granted.</p>
                </div>
                <div x-show="availableDevices.length > 0" class="space-y-3">
                    <p class="text-sm text-gray-600 mb-4">Found <span x-text="availableDevices.length"></span> camera device(s). Click "Add" to use them:</p>
                    <template x-for="device in availableDevices" :key="device.deviceId">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900" x-text="device.label || 'Unknown Camera'"></h4>
                                <p class="text-xs text-gray-500" x-text="'ID: ' + device.deviceId.substring(0, 20) + '...'"></p>
                            </div>
                            <button @click="addDeviceAsCamera(device)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium">
                                <i class="fas fa-plus mr-1"></i>Add
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button @click="showDeviceModal = false"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Close
                </button>
                <button @click="detectDevices()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-refresh mr-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Add Camera Modal -->
    <div x-show="showAddCameraModal" @click.away="showAddCameraModal = false"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Add New Camera</h3>
            </div>
            <form @submit.prevent="addCamera()">
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label for="camera_name" class="block text-sm font-medium text-gray-700 mb-2">Camera Name</label>
                        <input type="text" id="camera_name" x-model="newCamera.camera_name" required
                               placeholder="e.g., Main Camera, Side Angle, Close-up"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="device_type" class="block text-sm font-medium text-gray-700 mb-2">Device Type</label>
                        <select id="device_type" x-model="newCamera.device_type"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select device type</option>
                            <option value="phone">Phone</option>
                            <option value="laptop">Laptop</option>
                            <option value="camera">Professional Camera</option>
                            <option value="tablet">Tablet</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="camera_device" class="block text-sm font-medium text-gray-700 mb-2">Camera Device</label>
                        <div class="space-y-2">
                            <!-- Detect Devices Button -->
                            <button type="button" @click="detectDevicesForForm()" :disabled="detectingDevices"
                                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 disabled:opacity-50">
                                <i :class="detectingDevices ? 'fas fa-spinner fa-spin' : 'fas fa-search'" class="mr-2"></i>
                                <span x-text="detectingDevices ? 'Detecting...' : 'Detect Available Cameras'"></span>
                            </button>

                            <!-- Device Selection -->
                            <select id="camera_device" x-model="newCamera.device_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a camera device</option>
                                <template x-for="device in availableDevices" :key="device.deviceId">
                                    <option :value="device.deviceId"
                                            :disabled="isDeviceInUse(device.deviceId)"
                                            x-text="(device.label || 'Unknown Camera') + (isDeviceInUse(device.deviceId) ? ' (In Use)' : '')"></option>
                                </template>
                            </select>

                            <!-- Device Info -->
                            <div x-show="newCamera.device_id" class="text-xs text-gray-500 space-y-1">
                                <template x-for="device in availableDevices" :key="device.deviceId">
                                    <div x-show="device.deviceId === newCamera.device_id">
                                        <div>Device ID: <span x-text="device.deviceId.substring(0, 20) + '...'"></span></div>
                                        <div x-show="isDeviceInUse(device.deviceId)" class="text-orange-600 font-medium">
                                            ⚠️ <span x-text="getDeviceUsageInfo(device.deviceId)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Test Camera Button -->
                            <button type="button" x-show="newCamera.device_id && !isDeviceInUse(newCamera.device_id)"
                                    @click="testSelectedDevice()" :disabled="testing"
                                    class="w-full bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-md text-sm font-medium border border-green-300 disabled:opacity-50">
                                <i :class="testing ? 'fas fa-spinner fa-spin' : 'fas fa-eye'" class="mr-2"></i>
                                <span x-text="testing ? 'Testing...' : 'Test Camera Preview'"></span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="resolution" class="block text-sm font-medium text-gray-700 mb-2">Resolution</label>
                        <select id="resolution" x-model="newCamera.resolution"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Auto</option>
                            <option value="480p">480p</option>
                            <option value="720p">720p</option>
                            <option value="1080p">1080p</option>
                            <option value="4K">4K</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" @click="showAddCameraModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="adding"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors disabled:opacity-50">
                        <span x-show="!adding">Add Camera</span>
                        <span x-show="adding"><i class="fas fa-spinner fa-spin mr-2"></i>Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Agora SDK -->
<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.19.0.js"></script>

<script>
function cameraManagement() {
    return {
        // Stream data
        streamId: {{ $stream->id }},
        channelName: '{{ $stream->channel_name }}',
        appId: '{{ config('services.agora.app_id') }}',

        // Camera management
        cameras: [],
        availableDevices: [],
        switchHistory: [],
        activeCameraName: 'Primary Camera',
        isLive: false,

        // UI State
        showAddCameraModal: false,
        showDeviceModal: false,
        adding: false,
        detectingDevices: false,
        testing: false,

        // Form data
        newCamera: {
            camera_name: '',
            device_type: '',
            resolution: '',
            device_id: null
        },

        // Mixer settings
        mixerSettings: {
            layout_type: 'single',
            transition_effect: 'cut',
            transition_duration: 1000
        },

        // Agora clients and tracks
        agoraClient: null,
        activeTracks: new Map(), // Map of camera id to video track

        async init() {
            console.log('Initializing camera management...');

            // Initialize Agora
            this.agoraClient = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
            this.agoraClient.setClientRole("host");

            await this.loadCameras();
            await this.loadMixerSettings();
            await this.loadSwitchHistory();
            this.startPolling();

            // Auto-detect devices on load (comment out for manual testing)
            // await this.detectDevices();

            console.log('Camera management initialized. Available devices:', this.availableDevices.length);
        },

        async detectDevices() {
            this.detectingDevices = true;
            console.log('Starting device detection...');

            try {
                console.log('Detecting camera devices...');

                // Request camera permissions
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                stream.getTracks().forEach(track => track.stop()); // Stop the test stream

                // Get list of devices
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                console.log('Found video devices:', videoDevices);
                this.availableDevices = videoDevices;

                // Force Alpine.js to update
                this.$nextTick(() => {
                    console.log('Alpine updated. Devices in state:', this.availableDevices.length);
                    console.log('Should show modal:', videoDevices.length > 0);

                    if (videoDevices.length > 0) {
                        this.showDeviceModal = true;
                        console.log('Modal should be visible now. showDeviceModal:', this.showDeviceModal);

                        // Force UI update after a brief delay
                        setTimeout(() => {
                            console.log('Final check - Modal visible:', this.showDeviceModal);
                        }, 100);
                    } else {
                        alert('No camera devices found. Please connect a camera and try again.');
                    }
                });

            } catch (error) {
                console.error('Error detecting devices:', error);
                alert('Could not access camera devices. Please ensure cameras are connected and permissions are granted.');
            }
            this.detectingDevices = false;
        },

        async detectDevicesForForm() {
            this.detectingDevices = true;
            console.log('Starting device detection for form...');

            try {
                // Request camera permissions
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                stream.getTracks().forEach(track => track.stop()); // Stop the test stream

                // Get list of devices
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                console.log('Found video devices for form:', videoDevices);
                this.availableDevices = videoDevices;

                if (videoDevices.length === 0) {
                    alert('No camera devices found. Please connect a camera and try again.');
                } else {
                    this.showNotification(`Found ${videoDevices.length} camera device(s)`, 'success');
                }

            } catch (error) {
                console.error('Error detecting devices for form:', error);
                alert('Could not access camera devices. Please ensure cameras are connected and permissions are granted.');
            }
            this.detectingDevices = false;
        },

        async testSelectedDevice() {
            if (!this.newCamera.device_id) return;

            this.testing = true;
            let testTrack = null;

            try {
                // Create a test video track
                testTrack = await AgoraRTC.createCameraVideoTrack({
                    cameraId: this.newCamera.device_id,
                    encoderConfig: this.getEncoderConfig(this.newCamera.resolution || '720p')
                });

                // Create a test preview window
                const testWindow = window.open('', 'CameraTest', 'width=640,height=480,scrollbars=no,resizable=yes');
                testWindow.document.write(`
                    <html>
                        <head>
                            <title>Camera Test Preview</title>
                            <style>
                                body { margin: 0; padding: 20px; background: #000; color: white; font-family: Arial, sans-serif; }
                                #preview { width: 100%; height: 360px; background: #222; border-radius: 8px; }
                                .controls { text-align: center; margin-top: 15px; }
                                button { background: #e53e3e; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
                                button:hover { background: #c53030; }
                            </style>
                        </head>
                        <body>
                            <h3>Camera Test: ${this.newCamera.camera_name || 'Unnamed Camera'}</h3>
                            <div id="preview"></div>
                            <div class="controls">
                                <button onclick="window.close()">Close Test</button>
                            </div>
                        </body>
                    </html>
                `);

                // Play the test video in the popup
                const previewElement = testWindow.document.getElementById('preview');
                testTrack.play(previewElement);

                // Clean up when window closes
                testWindow.addEventListener('beforeunload', () => {
                    if (testTrack) {
                        testTrack.stop();
                        testTrack.close();
                    }
                });

                this.showNotification('Camera test opened in new window', 'success');

            } catch (error) {
                console.error('Error testing camera:', error);
                this.showNotification('Failed to test camera: ' + error.message, 'error');

                // Clean up on error
                if (testTrack) {
                    testTrack.stop();
                    testTrack.close();
                }
            } finally {
                this.testing = false;
            }
        },

        async addDeviceAsCamera(device) {
            try {
                const cameraName = device.label || `Camera ${this.cameras.length + 1}`;

                // Create new camera entry
                const newCamera = {
                    camera_name: cameraName,
                    device_type: this.getDeviceType(device.label),
                    resolution: '720p', // Default resolution
                    device_id: device.deviceId,
                    is_active: false,
                    is_primary: this.cameras.length === 0 // First camera is primary
                };

                // Test the device by creating a preview
                await this.testCameraDevice(device.deviceId, newCamera);

                // Add to cameras list
                this.cameras.push({
                    ...newCamera,
                    id: Date.now(), // Temporary ID for UI
                    status: 'ready'
                });

                this.showDeviceModal = false;
                this.showNotification(`Camera "${cameraName}" added successfully`, 'success');

            } catch (error) {
                console.error('Error adding camera:', error);
                this.showNotification('Failed to add camera: ' + error.message, 'error');
            }
        },

        async testCameraDevice(deviceId, cameraData) {
            try {
                // Create video track for this device
                const videoTrack = await AgoraRTC.createCameraVideoTrack({
                    cameraId: deviceId,
                    encoderConfig: this.getEncoderConfig(cameraData.resolution)
                });

                // Store the track
                const cameraId = cameraData.id || Date.now();
                this.activeTracks.set(cameraId, videoTrack);

                // Play preview after DOM update
                this.$nextTick(() => {
                    const previewElement = document.getElementById(`camera-preview-${cameraId}`);
                    if (previewElement) {
                        videoTrack.play(previewElement);
                    }
                });

                return true;
            } catch (error) {
                console.error('Error testing camera device:', error);
                throw new Error('Failed to initialize camera: ' + error.message);
            }
        },

        getDeviceType(label) {
            if (!label) return 'unknown';
            const lowerLabel = label.toLowerCase();
            if (lowerLabel.includes('phone') || lowerLabel.includes('mobile')) return 'phone';
            if (lowerLabel.includes('laptop') || lowerLabel.includes('built-in')) return 'laptop';
            if (lowerLabel.includes('usb') || lowerLabel.includes('webcam')) return 'camera';
            if (lowerLabel.includes('tablet')) return 'tablet';
            return 'other';
        },

        isDeviceInUse(deviceId) {
            return this.cameras.some(camera => camera.device_id === deviceId);
        },

        getDeviceUsageInfo(deviceId) {
            const camera = this.cameras.find(camera => camera.device_id === deviceId);
            return camera ? `Used by: ${camera.camera_name}` : null;
        },

        getEncoderConfig(resolution) {
            const configs = {
                '480p': { width: 640, height: 480, frameRate: 30, bitrateMin: 400, bitrateMax: 1000 },
                '720p': { width: 1280, height: 720, frameRate: 30, bitrateMin: 1000, bitrateMax: 2000 },
                '1080p': { width: 1920, height: 1080, frameRate: 30, bitrateMin: 2000, bitrateMax: 4000 },
                '4K': { width: 3840, height: 2160, frameRate: 30, bitrateMin: 8000, bitrateMax: 15000 }
            };
            return configs[resolution] || configs['720p'];
        },

        async switchCamera(cameraId) {
            try {
                const camera = this.cameras.find(c => c.id === cameraId);
                if (!camera) throw new Error('Camera not found');

                // Set all cameras as non-primary
                this.cameras.forEach(c => c.is_primary = false);

                // Set selected camera as primary
                camera.is_primary = true;
                this.activeCameraName = camera.camera_name;

                // If streaming, switch the active video track
                if (this.isLive && this.agoraClient) {
                    const newTrack = this.activeTracks.get(cameraId);
                    if (newTrack) {
                        // Unpublish current track and publish new one
                        await this.agoraClient.unpublish();
                        await this.agoraClient.publish([newTrack]);

                        // Update main preview
                        const mainPreview = document.getElementById('activeCameraPreview');
                        if (mainPreview) {
                            mainPreview.innerHTML = '';
                            newTrack.play(mainPreview);
                        }
                    }
                }

                // Record switch in history
                this.recordCameraSwitch(camera.camera_name);

                this.showNotification('Switched to ' + camera.camera_name, 'success');

            } catch (error) {
                console.error('Error switching camera:', error);
                this.showNotification('Failed to switch camera: ' + error.message, 'error');
            }
        },

        async toggleCameraConnection(cameraId, shouldActivate) {
            try {
                const camera = this.cameras.find(c => c.id === cameraId);
                if (!camera) throw new Error('Camera not found');

                if (shouldActivate) {
                    // Activate camera - create video track if not exists
                    if (!this.activeTracks.has(cameraId)) {
                        await this.testCameraDevice(camera.device_id, camera);
                    }
                    camera.is_active = true;
                    camera.status = 'connected';
                } else {
                    // Deactivate camera - stop track
                    const track = this.activeTracks.get(cameraId);
                    if (track) {
                        track.stop();
                        track.close();
                        this.activeTracks.delete(cameraId);
                    }
                    camera.is_active = false;
                    camera.status = 'disconnected';

                    // Clear preview
                    const previewElement = document.getElementById(`camera-preview-${cameraId}`);
                    if (previewElement) {
                        previewElement.innerHTML = '<i class="fas fa-video text-2xl opacity-50"></i>';
                    }
                }

                this.showNotification(
                    `Camera ${shouldActivate ? 'connected' : 'disconnected'} successfully`,
                    'success'
                );

            } catch (error) {
                console.error('Error toggling camera connection:', error);
                this.showNotification('Failed to toggle camera connection: ' + error.message, 'error');
            }
        },

        async removeCamera(cameraId) {
            if (!confirm('Are you sure you want to remove this camera?')) return;

            try {
                const camera = this.cameras.find(c => c.id === cameraId);
                if (camera && camera.is_primary) {
                    throw new Error('Cannot remove primary camera. Switch to another camera first.');
                }

                // Stop and remove track
                const track = this.activeTracks.get(cameraId);
                if (track) {
                    track.stop();
                    track.close();
                    this.activeTracks.delete(cameraId);
                }

                // Remove from cameras list
                this.cameras = this.cameras.filter(c => c.id !== cameraId);

                this.showNotification('Camera removed successfully', 'success');

            } catch (error) {
                console.error('Error removing camera:', error);
                this.showNotification(error.message, 'error');
            }
        },

        async addCamera() {
            try {
                this.adding = true;

                // Validate form data
                if (!this.newCamera.camera_name.trim()) {
                    throw new Error('Camera name is required');
                }

                // Get device info if device_id is selected
                let selectedDevice = null;
                let deviceType = this.newCamera.device_type || 'other';

                if (this.newCamera.device_id) {
                    selectedDevice = this.availableDevices.find(d => d.deviceId === this.newCamera.device_id);
                    if (selectedDevice) {
                        // Auto-detect device type if not manually selected
                        if (!this.newCamera.device_type) {
                            deviceType = this.getDeviceType(selectedDevice.label);
                        }
                    }
                }

                // Create new camera entry
                const camera = {
                    id: Date.now(), // Temporary ID for UI
                    camera_name: this.newCamera.camera_name,
                    device_type: deviceType,
                    resolution: this.newCamera.resolution || '720p',
                    device_id: this.newCamera.device_id,
                    is_active: false,
                    is_primary: this.cameras.length === 0, // First camera is primary
                    status: 'ready'
                };

                // If a device is selected, test it and create a preview
                if (this.newCamera.device_id && selectedDevice) {
                    console.log('Testing camera device:', selectedDevice.label);
                    await this.testCameraDevice(this.newCamera.device_id, camera);
                    camera.is_active = true; // Auto-activate if device test succeeds
                    camera.status = 'connected';

                    this.showNotification(`Camera "${camera.camera_name}" added and connected successfully`, 'success');
                } else {
                    this.showNotification(`Camera "${camera.camera_name}" added (not connected to device)`, 'success');
                }

                // Add to cameras list
                this.cameras.push(camera);

                // Reset form
                this.newCamera = {
                    camera_name: '',
                    device_type: '',
                    resolution: '',
                    device_id: null
                };

                // Close modal
                this.showAddCameraModal = false;

            } catch (error) {
                console.error('Error adding camera:', error);
                this.showNotification(error.message, 'error');
            } finally {
                this.adding = false;
            }
        },

        recordCameraSwitch(toCameraName) {
            const currentPrimary = this.switchHistory.length > 0 ?
                this.switchHistory[0].to_camera : 'None';

            this.switchHistory.unshift({
                id: Date.now(),
                from_camera: currentPrimary,
                to_camera: toCameraName,
                switched_at: new Date().toISOString()
            });

            // Keep only last 20 switches
            if (this.switchHistory.length > 20) {
                this.switchHistory = this.switchHistory.slice(0, 20);
            }
        },

        async startStreaming() {
            try {
                this.isLive = true;

                // Get primary camera track
                const primaryCamera = this.cameras.find(c => c.is_primary);
                if (!primaryCamera) throw new Error('No primary camera selected');

                const primaryTrack = this.activeTracks.get(primaryCamera.id);
                if (!primaryTrack) throw new Error('Primary camera not connected');

                // Join Agora channel and publish
                await this.agoraClient.join(
                    this.appId,
                    this.channelName,
                    null, // token - should be fetched from server
                    null  // uid - auto assigned
                );

                await this.agoraClient.publish([primaryTrack]);

                console.log('Started streaming with camera:', primaryCamera.camera_name);

            } catch (error) {
                console.error('Error starting stream:', error);
                this.isLive = false;
                throw error;
            }
        },

        async stopStreaming() {
            try {
                if (this.agoraClient) {
                    await this.agoraClient.leave();
                }
                this.isLive = false;
                console.log('Stopped streaming');
            } catch (error) {
                console.error('Error stopping stream:', error);
            }
        },

        // Backend API methods
        async loadCameras() {
            try {
                console.log('Loading cameras from database...');
                const response = await fetch(`/admin/streams/${this.streamId}/cameras`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.cameras = data.data.map(camera => ({
                            ...camera,
                            is_active: camera.is_active || false,
                            is_primary: camera.is_primary || false,
                            status: camera.status || 'disconnected'
                        }));

                        // Set active camera name
                        const primaryCamera = this.cameras.find(c => c.is_primary);
                        if (primaryCamera) {
                            this.activeCameraName = primaryCamera.camera_name;
                        }

                        console.log('Loaded cameras:', this.cameras.length);
                    } else {
                        console.error('Failed to load cameras:', data.message);
                    }
                } else {
                    console.error('HTTP error loading cameras:', response.status);
                }
            } catch (error) {
                console.error('Error loading cameras:', error);
            }
        },

        async loadMixerSettings() {
            try {
                console.log('Loading mixer settings...');
                const response = await fetch(`/admin/streams/${this.streamId}/mixer-settings`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.mixerSettings = { ...this.mixerSettings, ...data.data };
                        console.log('Loaded mixer settings');
                    }
                }
            } catch (error) {
                console.error('Error loading mixer settings:', error);
            }
        },

        async loadSwitchHistory() {
            try {
                console.log('Loading switch history...');
                const response = await fetch(`/admin/streams/${this.streamId}/camera-switches`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.switchHistory = data.data;
                        console.log('Loaded switch history:', this.switchHistory.length);
                    }
                }
            } catch (error) {
                console.error('Error loading switch history:', error);
            }
        },

        async updateMixerSettings() {
            try {
                console.log('Updating mixer settings...');
                const response = await fetch(`/admin/streams/${this.streamId}/mixer-settings`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.mixerSettings)
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.log('Mixer settings updated');
                    }
                }
            } catch (error) {
                console.error('Error updating mixer settings:', error);
            }
        },

        startPolling() {
            // Poll for viewer updates, etc.
            setInterval(() => {
                // Update viewer count, etc.
            }, 5000);
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString();
        },

        showNotification(message, type) {
            console.log(`${type.toUpperCase()}: ${message}`);
            // You can implement a proper notification system here
            if (type === 'error') {
                alert('Error: ' + message);
            }
        }
    }
}
</script>
@endsection
