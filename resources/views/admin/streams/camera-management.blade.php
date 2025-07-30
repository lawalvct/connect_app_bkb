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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="cameraManagement()">
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
                        <div class="absolute top-4 left-4 bg-black bg-opacity-75 text-white px-3 py-2 rounded">
                            <i class="fas fa-video mr-2"></i>
                            <span x-text="activeCameraName">Primary Camera</span>
                        </div>
                        <div x-show="isLive" class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold animate-pulse">
                            <i class="fas fa-circle mr-1"></i>LIVE
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
                            <i class="fas fa-plus mr-2"></i>Add Camera
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
                        <div x-show="cameras.length === 0" class="col-span-full text-center py-12">
                            <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No cameras configured</h3>
                            <p class="text-gray-500 mb-4">Add your first camera to start multi-camera streaming</p>
                            <button @click="showAddCameraModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-plus mr-2"></i>Add Camera
                            </button>
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

<script>
function cameraManagement() {
    return {
        cameras: [],
        switchHistory: [],
        activeCameraName: 'Primary Camera',
        isLive: false,
        showAddCameraModal: false,
        adding: false,
        newCamera: {
            camera_name: '',
            device_type: '',
            resolution: ''
        },
        mixerSettings: {
            layout_type: 'single',
            transition_effect: 'cut',
            transition_duration: 1000
        },

        async init() {
            await this.loadCameras();
            await this.loadMixerSettings();
            await this.loadSwitchHistory();
            this.startPolling();
        },

        async loadCameras() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/cameras`);
                const data = await response.json();
                if (data.success) {
                    this.cameras = data.data;
                    const primaryCamera = this.cameras.find(c => c.is_primary);
                    if (primaryCamera) {
                        this.activeCameraName = primaryCamera.camera_name;
                    }
                }
            } catch (error) {
                console.error('Failed to load cameras:', error);
            }
        },

        async loadMixerSettings() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/mixer-settings`);
                const data = await response.json();
                if (data.success) {
                    this.mixerSettings = { ...this.mixerSettings, ...data.data };
                }
            } catch (error) {
                console.error('Failed to load mixer settings:', error);
            }
        },

        async loadSwitchHistory() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/camera-switches`);
                const data = await response.json();
                if (data.success) {
                    this.switchHistory = data.data;
                }
            } catch (error) {
                console.error('Failed to load switch history:', error);
            }
        },

        async addCamera() {
            this.adding = true;
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/cameras`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newCamera)
                });

                const data = await response.json();
                if (data.success) {
                    this.cameras.push(data.data);
                    this.newCamera = { camera_name: '', device_type: '', resolution: '' };
                    this.showAddCameraModal = false;
                    this.showNotification('Camera added successfully', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to add camera', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to add camera', 'error');
            }
            this.adding = false;
        },

        async removeCamera(cameraId) {
            if (!confirm('Are you sure you want to remove this camera?')) return;

            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/cameras/${cameraId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.cameras = this.cameras.filter(c => c.id !== cameraId);
                    this.showNotification('Camera removed successfully', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to remove camera', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to remove camera', 'error');
            }
        },

        async switchCamera(cameraId) {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/switch-camera`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ camera_id: cameraId })
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadCameras();
                    await this.loadSwitchHistory();
                    this.showNotification('Camera switched successfully', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to switch camera', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to switch camera', 'error');
            }
        },

        async toggleCameraConnection(cameraId, isActive) {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/cameras/${cameraId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_active: isActive })
                });

                const data = await response.json();
                if (data.success) {
                    const camera = this.cameras.find(c => c.id === cameraId);
                    if (camera) {
                        camera.is_active = data.data.is_active;
                        camera.status = data.data.status;
                        camera.last_seen_at = data.data.last_seen_at;
                    }
                    this.showNotification('Camera status updated successfully', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to update camera status', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to update camera status', 'error');
            }
        },

        async updateMixerSettings() {
            try {
                const response = await fetch(`/admin/api/streams/{{ $stream->id }}/mixer-settings`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.mixerSettings)
                });

                const data = await response.json();
                if (data.success) {
                    this.mixerSettings = { ...this.mixerSettings, ...data.data };
                }
            } catch (error) {
                console.error('Failed to update mixer settings:', error);
            }
        },

        startPolling() {
            setInterval(() => {
                this.loadCameras();
            }, 5000); // Poll every 5 seconds
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString();
        },

        showNotification(message, type) {
            // Basic notification - can be enhanced with a proper notification system
            if (type === 'success') {
                console.log('✓ ' + message);
            } else {
                console.error('✗ ' + message);
            }
            // You can implement a more sophisticated notification system here
        }
    }
}
</script>
@endsection
