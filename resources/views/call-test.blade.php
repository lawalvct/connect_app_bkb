<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect App - Call Testing Interface</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.19.3.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .form-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover:not(:disabled) {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .status-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .status-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .status-card.active {
            background: #d4edda;
            border-color: #28a745;
        }

        .status-card.error {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .video-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .video-container {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 16/9;
        }

        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .controls-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logs-section {
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-entry {
            margin-bottom: 5px;
            padding: 2px 0;
        }

        .log-entry.error {
            color: #ff4444;
        }

        .log-entry.success {
            color: #44ff44;
        }

        .log-entry.info {
            color: #4444ff;
        }

        .call-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .call-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .participant-list {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .participant {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        .participant.joined {
            border-color: #28a745;
            background: #d4edda;
        }

        .participant.invited {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .device-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .response-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }

        .clear-logs-btn {
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .video-section {
                grid-template-columns: 1fr;
            }

            .form-section {
                grid-template-columns: 1fr;
            }

            .device-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-phone"></i> Connect App Call Testing</h1>
                <p>Test audio and video calling functionality with Agora integration</p>
            </div>

            <!-- Configuration Section -->
            <div class="card">
                <h3><i class="fas fa-cog"></i> Configuration</h3>
                <div class="form-section">
                    <div>
                        <div class="form-group">
                            <label for="api-url">API Base URL</label>
                            <input id="api-url" v-model="config.apiUrl" type="text" placeholder="http://localhost:8000/api/v1">
                        </div>
                        <div class="form-group">
                            <label for="bearer-token">Bearer Token</label>
                            <textarea id="bearer-token" v-model="config.token" placeholder="Enter your bearer token here" rows="3"></textarea>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label for="conversation-id">Conversation ID</label>
                            <input id="conversation-id" v-model="config.conversationId" type="number" placeholder="2">
                        </div>
                        <div class="form-group">
                            <label for="call-type">Call Type</label>
                            <select id="call-type" v-model="config.callType" title="Select call type">
                                <option value="audio">Audio Call</option>
                                <option value="video">Video Call</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Selection -->
            <div class="card">
                <h3><i class="fas fa-video"></i> Device Selection</h3>
                <div class="device-section">
                    <div class="form-group">
                        <label for="camera-select">Camera</label>
                        <select id="camera-select" v-model="selectedCamera" title="Select camera device">
                            <option value="">Select Camera</option>
                            <option v-for="device in devices.cameras" :key="device.deviceId" :value="device.deviceId">
                                @{{ device.label || `Camera ${device.deviceId}` }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="microphone-select">Microphone</label>
                        <select id="microphone-select" v-model="selectedMicrophone" title="Select microphone device">
                            <option value="">Select Microphone</option>
                            <option v-for="device in devices.microphones" :key="device.deviceId" :value="device.deviceId">
                                @{{ device.label || `Microphone ${device.deviceId}` }}
                            </option>
                        </select>
                    </div>
                </div>
                <button @click="getDevices" class="btn btn-primary">
                    <i class="fas fa-refresh"></i> Refresh Devices
                </button>
            </div>

            <!-- Call Status -->
            <div class="status-section">
                <div class="status-card" :class="{ active: callStatus.connected, error: callStatus.error }">
                    <h4><i class="fas fa-phone"></i> Call Status</h4>
                    <p>@{{ callStatus.message }}</p>
                    <small v-if="callStatus.duration">Duration: @{{ callStatus.duration }}s</small>
                </div>
                <div class="status-card" :class="{ active: agoraStatus.connected }">
                    <h4><i class="fas fa-satellite"></i> Agora Status</h4>
                    <p>@{{ agoraStatus.message }}</p>
                </div>
                <div class="status-card" :class="{ active: mediaStatus.camera && mediaStatus.microphone }">
                    <h4><i class="fas fa-microphone"></i> Media Status</h4>
                    <p>Camera: @{{ mediaStatus.camera ? 'On' : 'Off' }}</p>
                    <p>Microphone: @{{ mediaStatus.microphone ? 'On' : 'Off' }}</p>
                </div>
            </div>

            <!-- Current Call Info -->
            <div v-if="currentCall" class="call-info">
                <h4>Current Call Information</h4>
                <p><strong>Call ID:</strong> @{{ currentCall.id }}</p>
                <p><strong>Type:</strong> @{{ currentCall.call_type }}</p>
                <p><strong>Status:</strong> @{{ currentCall.status }}</p>
                <p><strong>Channel:</strong> @{{ agoraConfig?.channel_name }}</p>
                <div class="participant-list">
                    <div v-for="participant in currentCall.participants" :key="participant.user_id"
                         class="participant" :class="participant.status">
                        @{{ participant.name }} (@{{ participant.status }})
                    </div>
                </div>
            </div>

            <!-- Video Section -->
            <div class="card">
                <h3><i class="fas fa-video"></i> Video Streams</h3>
                <div class="video-section">
                    <div class="video-container">
                        <div class="video-label">Local Video</div>
                        <div id="local-video"></div>
                    </div>
                    <div class="video-container">
                        <div class="video-label">Remote Video</div>
                        <div id="remote-video"></div>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="card">
                <h3><i class="fas fa-gamepad"></i> Call Controls</h3>
                <div class="controls-section">
                    <!-- Initiate Call -->
                    <button @click="initiateCall" :disabled="loading || currentCall" class="btn btn-primary">
                        <div v-if="loading" class="loading"></div>
                        <i v-else class="fas fa-phone"></i>
                        Initiate @{{ config.callType.charAt(0).toUpperCase() + config.callType.slice(1) }} Call
                    </button>

                    <!-- Answer Call -->
                    <button @click="answerCall" :disabled="!currentCall || currentCall.status !== 'initiated' || userIsInitiator" class="btn btn-success">
                        <i class="fas fa-phone-check"></i> Answer Call
                    </button>

                    <!-- Reject Call -->
                    <button @click="rejectCall" :disabled="!currentCall || currentCall.status !== 'initiated' || userIsInitiator" class="btn btn-danger">
                        <i class="fas fa-phone-slash"></i> Reject Call
                    </button>

                    <!-- End Call -->
                    <button @click="endCall" :disabled="!currentCall" class="btn btn-warning">
                        <i class="fas fa-phone-hangup"></i> End Call
                    </button>

                    <br><br>

                    <!-- Media Controls -->
                    <button @click="toggleCamera" :disabled="!agoraStatus.connected" class="btn" :class="mediaStatus.camera ? 'btn-warning' : 'btn-success'">
                        <i :class="mediaStatus.camera ? 'fas fa-video-slash' : 'fas fa-video'"></i>
                        @{{ mediaStatus.camera ? 'Turn Off Camera' : 'Turn On Camera' }}
                    </button>

                    <button @click="toggleMicrophone" :disabled="!agoraStatus.connected" class="btn" :class="mediaStatus.microphone ? 'btn-warning' : 'btn-success'">
                        <i :class="mediaStatus.microphone ? 'fas fa-microphone-slash' : 'fas fa-microphone'"></i>
                        @{{ mediaStatus.microphone ? 'Mute' : 'Unmute' }}
                    </button>

                    <!-- Call History -->
                    <button @click="getCallHistory" class="btn btn-primary">
                        <i class="fas fa-history"></i> Get Call History
                    </button>
                </div>
            </div>

            <!-- Response Preview -->
            <div v-if="lastResponse" class="card">
                <h3><i class="fas fa-code"></i> Last API Response</h3>
                <div class="response-preview">@{{ lastResponse }}</div>
            </div>

            <!-- Logs -->
            <div class="card">
                <h3><i class="fas fa-terminal"></i> Activity Logs</h3>
                <div class="logs-section">
                    <div v-for="log in logs" :key="log.id" class="log-entry" :class="log.type">
                        [@{{ log.timestamp }}] @{{ log.message }}
                    </div>
                </div>
                <button @click="clearLogs" class="btn btn-danger clear-logs-btn">
                    <i class="fas fa-trash"></i> Clear Logs
                </button>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    config: {
                        apiUrl: '{{ config("app.url") }}/api/v1',
                        token: '',
                        conversationId: 2,
                        callType: 'audio'
                    },
                    loading: false,
                    currentCall: null,
                    agoraConfig: null,
                    agoraClient: null,
                    localTracks: {
                        video: null,
                        audio: null
                    },
                    remoteTracks: {},
                    callStatus: {
                        connected: false,
                        message: 'Not connected',
                        duration: 0,
                        error: false
                    },
                    agoraStatus: {
                        connected: false,
                        message: 'Not connected'
                    },
                    mediaStatus: {
                        camera: false,
                        microphone: false
                    },
                    devices: {
                        cameras: [],
                        microphones: []
                    },
                    selectedCamera: '',
                    selectedMicrophone: '',
                    logs: [],
                    lastResponse: '',
                    logId: 0,
                    callTimer: null,
                    startTime: null
                }
            },
            computed: {
                userIsInitiator() {
                    return this.currentCall && this.currentCall.initiator && this.currentCall.participants.find(p => p.status === 'joined')?.user_id === this.currentCall.initiator.id;
                }
            },
            async mounted() {
                this.log('Application started', 'info');
                await this.initializeAgora();
                await this.getDevices();
            },
            methods: {
                async initializeAgora() {
                    try {
                        this.agoraClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

                        // Set up event listeners
                        this.agoraClient.on("user-published", this.handleUserPublished);
                        this.agoraClient.on("user-unpublished", this.handleUserUnpublished);
                        this.agoraClient.on("user-left", this.handleUserLeft);

                        this.log('Agora client initialized', 'success');
                    } catch (error) {
                        this.log(`Agora initialization failed: ${error.message}`, 'error');
                    }
                },

                async getDevices() {
                    try {
                        const devices = await AgoraRTC.getDevices();
                        this.devices.cameras = devices.filter(device => device.kind === 'videoinput');
                        this.devices.microphones = devices.filter(device => device.kind === 'audioinput');
                        this.log(`Found ${this.devices.cameras.length} cameras and ${this.devices.microphones.length} microphones`, 'info');
                    } catch (error) {
                        this.log(`Failed to get devices: ${error.message}`, 'error');
                    }
                },

                async initiateCall() {
                    this.loading = true;
                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/initiate`, {
                            conversation_id: this.config.conversationId,
                            call_type: this.config.callType
                        }, {
                            headers: {
                                'Authorization': `Bearer ${this.config.token}`,
                                'Content-Type': 'application/json'
                            }
                        });

                        this.lastResponse = JSON.stringify(response.data, null, 2);
                        this.currentCall = response.data.data.call;
                        this.agoraConfig = response.data.data.agora_config;

                        this.log(`Call initiated successfully. Call ID: ${this.currentCall.id}`, 'success');
                        this.callStatus.connected = true;
                        this.callStatus.message = 'Call initiated';

                        // Join Agora channel
                        await this.joinAgoraChannel();

                        this.startCallTimer();

                    } catch (error) {
                        this.log(`Failed to initiate call: ${error.response?.data?.message || error.message}`, 'error');
                        this.callStatus.error = true;
                        this.callStatus.message = 'Call initiation failed';
                        this.lastResponse = JSON.stringify(error.response?.data || error.message, null, 2);
                    }
                    this.loading = false;
                },

                async answerCall() {
                    if (!this.currentCall) return;

                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/answer`, {}, {
                            headers: {
                                'Authorization': `Bearer ${this.config.token}`,
                                'Content-Type': 'application/json'
                            }
                        });

                        this.lastResponse = JSON.stringify(response.data, null, 2);
                        this.currentCall = response.data.data.call;

                        this.log('Call answered successfully', 'success');
                        this.callStatus.message = 'Call answered';

                        // Join Agora channel if not already joined
                        if (!this.agoraStatus.connected) {
                            await this.joinAgoraChannel();
                        }

                    } catch (error) {
                        this.log(`Failed to answer call: ${error.response?.data?.message || error.message}`, 'error');
                        this.lastResponse = JSON.stringify(error.response?.data || error.message, null, 2);
                    }
                },

                async rejectCall() {
                    if (!this.currentCall) return;

                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/reject`, {}, {
                            headers: {
                                'Authorization': `Bearer ${this.config.token}`,
                                'Content-Type': 'application/json'
                            }
                        });

                        this.lastResponse = JSON.stringify(response.data, null, 2);

                        this.log('Call rejected successfully', 'success');
                        this.resetCallState();

                    } catch (error) {
                        this.log(`Failed to reject call: ${error.response?.data?.message || error.message}`, 'error');
                        this.lastResponse = JSON.stringify(error.response?.data || error.message, null, 2);
                    }
                },

                async endCall() {
                    if (!this.currentCall) return;

                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/end`, {}, {
                            headers: {
                                'Authorization': `Bearer ${this.config.token}`,
                                'Content-Type': 'application/json'
                            }
                        });

                        this.lastResponse = JSON.stringify(response.data, null, 2);

                        this.log('Call ended successfully', 'success');
                        await this.leaveAgoraChannel();
                        this.resetCallState();

                    } catch (error) {
                        this.log(`Failed to end call: ${error.response?.data?.message || error.message}`, 'error');
                        this.lastResponse = JSON.stringify(error.response?.data || error.message, null, 2);
                    }
                },

                async getCallHistory() {
                    try {
                        const response = await axios.get(`${this.config.apiUrl}/calls/history`, {
                            headers: {
                                'Authorization': `Bearer ${this.config.token}`
                            }
                        });

                        this.lastResponse = JSON.stringify(response.data, null, 2);
                        this.log(`Retrieved ${response.data.data.calls.length} calls from history`, 'info');

                    } catch (error) {
                        this.log(`Failed to get call history: ${error.response?.data?.message || error.message}`, 'error');
                        this.lastResponse = JSON.stringify(error.response?.data || error.message, null, 2);
                    }
                },

                async joinAgoraChannel() {
                    if (!this.agoraConfig || !this.agoraClient) return;

                    try {
                        // Join the channel
                        await this.agoraClient.join(
                            this.agoraConfig.app_id,
                            this.agoraConfig.channel_name,
                            this.agoraConfig.token,
                            this.agoraConfig.uid
                        );

                        this.log(`Joined Agora channel: ${this.agoraConfig.channel_name}`, 'success');
                        this.agoraStatus.connected = true;
                        this.agoraStatus.message = 'Connected to Agora';

                        // Create and publish local tracks
                        await this.createLocalTracks();

                    } catch (error) {
                        this.log(`Failed to join Agora channel: ${error.message}`, 'error');
                        this.agoraStatus.message = 'Agora connection failed';
                    }
                },

                async createLocalTracks() {
                    try {
                        // Create audio track
                        if (this.selectedMicrophone || this.devices.microphones.length > 0) {
                            this.localTracks.audio = await AgoraRTC.createMicrophoneAudioTrack({
                                microphoneId: this.selectedMicrophone || this.devices.microphones[0]?.deviceId
                            });
                            this.mediaStatus.microphone = true;
                        }

                        // Create video track for video calls
                        if (this.config.callType === 'video' && (this.selectedCamera || this.devices.cameras.length > 0)) {
                            this.localTracks.video = await AgoraRTC.createCameraVideoTrack({
                                cameraId: this.selectedCamera || this.devices.cameras[0]?.deviceId
                            });
                            this.mediaStatus.camera = true;

                            // Play local video
                            this.localTracks.video.play('local-video');
                        }

                        // Publish tracks
                        const tracks = Object.values(this.localTracks).filter(track => track);
                        if (tracks.length > 0) {
                            await this.agoraClient.publish(tracks);
                            this.log('Local tracks published', 'success');
                        }

                    } catch (error) {
                        this.log(`Failed to create local tracks: ${error.message}`, 'error');
                    }
                },

                async leaveAgoraChannel() {
                    try {
                        // Stop local tracks
                        if (this.localTracks.audio) {
                            this.localTracks.audio.stop();
                            this.localTracks.audio.close();
                            this.localTracks.audio = null;
                        }
                        if (this.localTracks.video) {
                            this.localTracks.video.stop();
                            this.localTracks.video.close();
                            this.localTracks.video = null;
                        }

                        // Leave channel
                        await this.agoraClient.leave();

                        this.log('Left Agora channel', 'info');
                        this.agoraStatus.connected = false;
                        this.agoraStatus.message = 'Disconnected';
                        this.mediaStatus.camera = false;
                        this.mediaStatus.microphone = false;

                        // Clear video elements
                        document.getElementById('local-video').innerHTML = '';
                        document.getElementById('remote-video').innerHTML = '';

                    } catch (error) {
                        this.log(`Error leaving channel: ${error.message}`, 'error');
                    }
                },

                async toggleCamera() {
                    if (!this.localTracks.video) return;

                    if (this.mediaStatus.camera) {
                        await this.localTracks.video.setEnabled(false);
                        this.mediaStatus.camera = false;
                        this.log('Camera turned off', 'info');
                    } else {
                        await this.localTracks.video.setEnabled(true);
                        this.mediaStatus.camera = true;
                        this.log('Camera turned on', 'info');
                    }
                },

                async toggleMicrophone() {
                    if (!this.localTracks.audio) return;

                    if (this.mediaStatus.microphone) {
                        await this.localTracks.audio.setEnabled(false);
                        this.mediaStatus.microphone = false;
                        this.log('Microphone muted', 'info');
                    } else {
                        await this.localTracks.audio.setEnabled(true);
                        this.mediaStatus.microphone = true;
                        this.log('Microphone unmuted', 'info');
                    }
                },

                handleUserPublished(user, mediaType) {
                    this.log(`User ${user.uid} published ${mediaType}`, 'info');

                    this.agoraClient.subscribe(user, mediaType).then(() => {
                        if (mediaType === 'video') {
                            user.videoTrack.play('remote-video');
                        } else if (mediaType === 'audio') {
                            user.audioTrack.play();
                        }
                    });
                },

                handleUserUnpublished(user, mediaType) {
                    this.log(`User ${user.uid} unpublished ${mediaType}`, 'info');
                },

                handleUserLeft(user) {
                    this.log(`User ${user.uid} left the channel`, 'info');
                    document.getElementById('remote-video').innerHTML = '';
                },

                startCallTimer() {
                    this.startTime = Date.now();
                    this.callTimer = setInterval(() => {
                        if (this.startTime) {
                            this.callStatus.duration = Math.floor((Date.now() - this.startTime) / 1000);
                        }
                    }, 1000);
                },

                resetCallState() {
                    this.currentCall = null;
                    this.agoraConfig = null;
                    this.callStatus.connected = false;
                    this.callStatus.message = 'Not connected';
                    this.callStatus.duration = 0;
                    this.callStatus.error = false;

                    if (this.callTimer) {
                        clearInterval(this.callTimer);
                        this.callTimer = null;
                    }
                    this.startTime = null;
                },

                log(message, type = 'info') {
                    const timestamp = new Date().toLocaleTimeString();
                    this.logs.push({
                        id: this.logId++,
                        timestamp,
                        message,
                        type
                    });

                    // Keep only last 100 logs
                    if (this.logs.length > 100) {
                        this.logs = this.logs.slice(-100);
                    }

                    // Auto scroll to bottom
                    this.$nextTick(() => {
                        const logsElement = document.querySelector('.logs-section');
                        if (logsElement) {
                            logsElement.scrollTop = logsElement.scrollHeight;
                        }
                    });
                },

                clearLogs() {
                    this.logs = [];
                    this.lastResponse = '';
                }
            },

            beforeUnmount() {
                if (this.callTimer) {
                    clearInterval(this.callTimer);
                }
                this.leaveAgoraChannel();
            }
        }).mount('#app');
    </script>
</body>
</html>
