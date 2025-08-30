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

        /* Login Specific Styles */
        .login-container {
            max-width: 500px;
            margin: 10vh auto;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
        }

        .user-selection {
            margin-bottom: 20px;
        }

        .user-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }

        .user-card:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .user-card.selected {
            border-color: #667eea;
            background: #e3f2fd;
        }

        .user-card h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .user-card p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .user-info {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info h4 {
            color: #155724;
            margin-bottom: 5px;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #5a6268;
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

        .hidden {
            display: none;
        }

        .conversations-section {
            margin-bottom: 20px;
        }

        .conversation-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .conversation-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .conversation-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .conversation-item.selected {
            border-color: #667eea;
            background: #e3f2fd;
        }

        .conversation-item h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .conversation-item p {
            color: #666;
            font-size: 14px;
            margin: 2px 0;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
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
        <!-- Login Screen -->
        <div v-if="!isAuthenticated" class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2><i class="fas fa-phone"></i> Connect App Call Test</h2>
                    <p>Login to test calling functionality</p>
                </div>

                <div v-if="loginError" class="alert alert-danger">
                    @{{ loginError }}
                </div>

                <div v-if="loginSuccess" class="alert alert-success">
                    Login successful! Loading interface...
                </div>

                <!-- Quick User Selection -->
                <div class="user-selection">
                    <h4>Quick Login - Select a Test User:</h4>
                    <div v-for="testUser in testUsers" :key="testUser.id"
                         class="user-card"
                         :class="{ selected: selectedUserId === testUser.id }"
                         @click="selectUser(testUser)">
                        <h4>@{{ testUser.name }}</h4>
                        <p>Email: @{{ testUser.email }}</p>
                        <p>ID: @{{ testUser.id }}</p>
                    </div>
                </div>

                <!-- Custom Login Form -->
                <div class="form-group">
                    <label for="login-email">Or Login with Custom Credentials:</label>
                    <input id="login-email" v-model="loginForm.email" type="email" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input id="login-password" v-model="loginForm.password" type="password" placeholder="Password" required>
                </div>

                <button @click="login" :disabled="loginLoading" class="btn btn-primary">
                    <div v-if="loginLoading" class="loading"></div>
                    <i v-else class="fas fa-sign-in-alt"></i>
                    @{{ loginLoading ? 'Logging in...' : 'Login' }}
                </button>

                <button @click="loadTestUsers" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Refresh Test Users
                </button>
            </div>
        </div>

        <!-- Main Call Interface (shown after login) -->
        <div v-if="isAuthenticated" class="container">
            <div class="header">
                <h1><i class="fas fa-phone"></i> Connect App Call Testing</h1>
                <p>Test audio and video calling functionality with Agora integration</p>

                <!-- User Info Bar -->
                <div v-if="currentUser" class="user-info">
                    <h4>Logged in as: @{{ currentUser.name }} (@{{ currentUser.email }})</h4>
                    <button @click="logout" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>

            <!-- Conversations Section -->
            <div class="card">
                <h3><i class="fas fa-comments"></i> Your Conversations</h3>
                <div class="conversations-section">
                    <button @click="loadConversations" class="btn btn-primary" :disabled="conversationsLoading">
                        <div v-if="conversationsLoading" class="loading"></div>
                        <i v-else class="fas fa-refresh"></i>
                        @{{ conversationsLoading ? 'Loading...' : 'Load Conversations' }}
                    </button>

                    <div v-if="conversations.length > 0" class="conversation-list">
                        <div v-for="conversation in conversations" :key="conversation.id"
                             class="conversation-item"
                             :class="{ selected: config.conversationId === conversation.id }"
                             @click="selectConversation(conversation)">
                            <h4>@{{ conversation.name || 'Conversation ' + conversation.id }}</h4>
                            <p><strong>Participants:</strong> @{{ conversation.participants?.map(p => p.name).join(', ') }}</p>
                            <p><strong>Last Activity:</strong> @{{ formatDate(conversation.updated_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Section -->
            <div class="card">
                <h3><i class="fas fa-cog"></i> Call Configuration</h3>
                <div class="form-section">
                    <div>
                        <div class="form-group">
                            <label for="conversation-id">Selected Conversation ID</label>
                            <input id="conversation-id" v-model="config.conversationId" type="number" readonly>
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
                <div class="status-card" :class="{ active: pollingActive }">
                    <h4><i class="fas fa-radar"></i> Call Polling</h4>
                    <p>@{{ pollingActive ? 'Active - Checking for calls' : 'Inactive' }}</p>
                    <small v-if="pollingActive">Every 5 seconds</small>
                </div>
            </div>

            <!-- Incoming Call Alert -->
            <div v-if="currentCall && currentCall.status === 'initiated' && !userIsInitiator" class="card" style="border: 3px solid #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                <h3 style="color: #155724;"><i class="fas fa-phone-volume"></i> 📞 Incoming Call!</h3>
                <div style="text-align: center; padding: 20px;">
                    <h4 style="color: #155724;">Call from: @{{ currentCall.initiator?.name || 'Unknown User' }}</h4>
                    <p style="color: #155724;"><strong>Type:</strong> @{{ currentCall.call_type?.charAt(0).toUpperCase() + currentCall.call_type?.slice(1) }} Call</p>
                    <p style="color: #155724;"><strong>Call ID:</strong> @{{ currentCall.id }}</p>

                    <div style="margin-top: 20px;">
                        <button @click="answerCall" class="btn btn-success" style="margin-right: 10px; font-size: 18px; padding: 15px 30px;">
                            <i class="fas fa-phone"></i> Accept Call
                        </button>
                        <button @click="rejectCall" class="btn btn-danger" style="font-size: 18px; padding: 15px 30px;">
                            <i class="fas fa-phone-slash"></i> Decline Call
                        </button>
                    </div>
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
                    <button @click="initiateCall" :disabled="loading || currentCall || !config.conversationId" class="btn btn-primary">
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

                    <!-- Manual Check for Incoming Calls -->
                    <button @click="checkForIncomingCalls" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Check for Incoming Calls
                    </button>

                    <!-- Test Simulate Incoming Call -->
                    <button @click="simulateIncomingCall" class="btn btn-info">
                        <i class="fas fa-test-tube"></i> Simulate Incoming Call (Test)
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
                    // Authentication state
                    isAuthenticated: false,
                    currentUser: null,
                    authToken: '',

                    // Login form
                    loginForm: {
                        email: '',
                        password: '12345678'
                    },
                    loginError: '',
                    loginSuccess: false,
                    loginLoading: false,
                    selectedUserId: null,
                    testUsers: [],

                    // Conversations
                    conversations: [],
                    conversationsLoading: false,

                    config: {
                        apiUrl: '{{ config("app.url") }}/api/v1',
                        conversationId: null,
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
                    startTime: null,
                    callPollingInterval: null,
                    pollingActive: false
                }
            },
            computed: {
                userIsInitiator() {
                    return this.currentUser && this.currentCall && this.currentCall.initiator && this.currentCall.initiator.id === this.currentUser.id;
                }
            },
            async mounted() {
                this.log('Application started', 'info');
                await this.loadTestUsers();
                await this.initializeAgora();
                await this.getDevices();
            },
            methods: {
                async loadTestUsers() {
                    try {
                        // Simulate test users - in real app you'd fetch from an endpoint
                        this.testUsers = [
                            { id: 3114, name: 'Oz Lawal', email: 'lawal@example.com' },
                            { id: 3152, name: 'Gerson', email: 'gerson@example.com' },
                            { id: 3001, name: 'Alice Johnson', email: 'alice@example.com' },
                            { id: 3002, name: 'Bob Smith', email: 'bob@example.com' },
                            { id: 3003, name: 'Carol Brown', email: 'carol@example.com' }
                        ];

                        this.log(`Loaded ${this.testUsers.length} test users`, 'info');
                    } catch (error) {
                        this.log(`Failed to load test users: ${error.message}`, 'error');
                    }
                },

                selectUser(user) {
                    this.selectedUserId = user.id;
                    this.loginForm.email = user.email;
                    this.loginForm.password = '12345678';
                },

                async login() {
                    if (!this.loginForm.email || !this.loginForm.password) {
                        this.loginError = 'Please enter email and password';
                        return;
                    }

                    this.loginLoading = true;
                    this.loginError = '';
                    this.loginSuccess = false;

                    try {
                        const response = await axios.post(`${this.config.apiUrl}/login`, {
                            email: this.loginForm.email,
                            password: this.loginForm.password
                        });

                        if (response.data.success) {
                            this.authToken = response.data.data.token;
                            this.currentUser = response.data.data.user;
                            this.isAuthenticated = true;
                            this.loginSuccess = true;

                            // Set axios default header
                            axios.defaults.headers.common['Authorization'] = `Bearer ${this.authToken}`;

                            this.log(`Successfully logged in as ${this.currentUser.name}`, 'success');

                            // Auto load conversations after login
                            setTimeout(() => {
                                this.loadConversations();
                                this.startCallPolling();

                                // Request notification permission
                                if ('Notification' in window && Notification.permission === 'default') {
                                    Notification.requestPermission();
                                }
                            }, 1000);

                        } else {
                            this.loginError = response.data.message || 'Login failed';
                        }
                    } catch (error) {
                        this.loginError = error.response?.data?.message || 'Login failed. Please check your credentials.';
                        this.log(`Login failed: ${this.loginError}`, 'error');
                    }

                    this.loginLoading = false;
                },

                logout() {
                    this.isAuthenticated = false;
                    this.currentUser = null;
                    this.authToken = '';
                    this.conversations = [];
                    this.config.conversationId = null;

                    // Clear axios default header
                    delete axios.defaults.headers.common['Authorization'];

                    // Reset call state
                    this.resetCallState();
                    this.leaveAgoraChannel();
                    this.stopCallPolling();

                    this.log('Logged out successfully', 'info');
                },

                startCallPolling() {
                    if (this.pollingActive) return;

                    this.pollingActive = true;
                    this.log('Started polling for incoming calls', 'info');

                    this.callPollingInterval = setInterval(async () => {
                        await this.checkForIncomingCalls();
                    }, 5000); // Poll every 5 seconds
                },

                stopCallPolling() {
                    if (this.callPollingInterval) {
                        clearInterval(this.callPollingInterval);
                        this.callPollingInterval = null;
                        this.pollingActive = false;
                        this.log('Stopped polling for incoming calls', 'info');
                    }
                },

                async checkForIncomingCalls() {
                    try {
                        // Only check if we're not already in a call
                        if (this.currentCall) {
                            this.log('Skipping poll - already in call', 'info');
                            return;
                        }

                        this.log('🔍 Polling for incoming calls...', 'info');

                        // Use call history to check for recent initiated calls
                        const response = await axios.get(`${this.config.apiUrl}/calls/history`);

                        this.log(`Poll response: ${JSON.stringify(response.data)}`, 'info');

                        if (response.data.success && response.data.data && response.data.data.calls) {
                            this.log(`Found ${response.data.data.calls.length} calls in history`, 'info');

                            const recentCalls = response.data.data.calls.filter(call => {
                                // Look for calls initiated in the last 2 minutes that are still active
                                const callTime = new Date(call.started_at);
                                const now = new Date();
                                const timeDiff = (now - callTime) / 1000; // seconds

                                this.log(`Checking call ${call.id}: status=${call.status}, timeDiff=${timeDiff}s, initiator=${call.initiator?.id}, currentUser=${this.currentUser?.id}`, 'info');
                                this.log(`Call time: ${call.started_at}, Now: ${now.toISOString()}, Parsed time: ${callTime.toISOString()}`, 'info');

                                return call.status === 'initiated' &&
                                       !isNaN(timeDiff) && // Valid time calculation
                                       timeDiff >= 0 && // Not in future
                                       timeDiff < 120 && // Within last 2 minutes
                                       call.initiator?.id && this.currentUser?.id && // Both IDs exist
                                       call.initiator.id !== this.currentUser.id; // Not initiated by current user
                            });

                            this.log(`Found ${recentCalls.length} relevant incoming calls`, 'info');

                            if (recentCalls.length > 0) {
                                const incomingCall = recentCalls[0]; // Get the most recent one
                                this.currentCall = incomingCall;
                                this.log(`📞 INCOMING CALL DETECTED! Call ID: ${incomingCall.id}`, 'success');
                                this.log(`From: ${incomingCall.initiator?.name || 'Unknown'}`, 'info');
                                this.callStatus.connected = true;
                                this.callStatus.message = 'Incoming call';

                                // Show a browser notification if supported
                                if ('Notification' in window && Notification.permission === 'granted') {
                                    new Notification('Incoming Call', {
                                        body: `Call from ${incomingCall.initiator?.name || 'Unknown'}`,
                                        icon: '/favicon.ico'
                                    });
                                }
                            }
                        } else {
                            this.log('No calls found in history or invalid response', 'info');
                        }
                    } catch (error) {
                        // Don't spam logs with polling errors unless it's important
                        if (error.response?.status !== 404) {
                            this.log(`❌ Error checking for incoming calls: ${error.response?.data?.message || error.message}`, 'error');
                            this.log(`Full error: ${JSON.stringify(error.response?.data)}`, 'error');
                        }
                    }
                },

                simulateIncomingCall() {
                    // Create a fake incoming call for testing
                    this.currentCall = {
                        id: 999,
                        status: 'initiated',
                        call_type: 'video',
                        conversation_id: this.config.conversationId,
                        initiator: {
                            id: 9999,
                            name: 'Test Caller',
                            email: 'test@example.com'
                        },
                        participants: [],
                        created_at: new Date().toISOString()
                    };

                    this.callStatus.connected = true;
                    this.callStatus.message = 'Simulated incoming call';
                    this.log('🧪 Simulated incoming call created for testing', 'success');
                },

                async loadConversations() {
                    this.conversationsLoading = true;
                    try {
                        this.log('Loading conversations...', 'info');
                        this.log(`API URL: ${this.config.apiUrl}/conversations`, 'info');
                        this.log(`Auth Token: ${this.authToken ? 'Present' : 'Missing'}`, 'info');

                        const response = await axios.get(`${this.config.apiUrl}/conversations`);

                        this.log(`API Response Status: ${response.status}`, 'info');
                        this.log(`API Response Data: ${JSON.stringify(response.data)}`, 'info');

                        if (response.data.success) {
                            // Handle different possible response structures
                            let conversationsData = response.data.data;

                            // If data is an object with conversations property
                            if (conversationsData && typeof conversationsData === 'object' && conversationsData.conversations) {
                                conversationsData = conversationsData.conversations;
                            }

                            // Ensure it's an array
                            this.conversations = Array.isArray(conversationsData) ? conversationsData : [];

                            this.log(`Loaded ${this.conversations.length} conversations`, 'success');

                            // Auto-select first conversation if none selected
                            if (this.conversations.length > 0 && !this.config.conversationId) {
                                this.selectConversation(this.conversations[0]);
                            } else if (this.conversations.length === 0) {
                                this.log('No conversations found', 'info');
                            }
                        } else {
                            this.log(`Failed to load conversations: ${response.data.message || 'Unknown error'}`, 'error');
                        }
                    } catch (error) {
                        this.log(`Error loading conversations: ${error.response?.data?.message || error.message}`, 'error');
                        this.log(`Error status: ${error.response?.status}`, 'error');
                        this.log(`Full error response: ${JSON.stringify(error.response?.data)}`, 'error');

                        // Check if it's an authentication error
                        if (error.response?.status === 401) {
                            this.log('Authentication error - token may have expired', 'error');
                        }
                    }
                    this.conversationsLoading = false;
                },

                selectConversation(conversation) {
                    this.config.conversationId = conversation.id;
                    this.log(`Selected conversation: ${conversation.name || conversation.id}`, 'info');
                },

                formatDate(dateString) {
                    if (!dateString) return 'Unknown';
                    return new Date(dateString).toLocaleString();
                },

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
                    if (!this.config.conversationId) {
                        this.log('Please select a conversation first', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/initiate`, {
                            conversation_id: this.config.conversationId,
                            call_type: this.config.callType
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
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/answer`);

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
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/reject`);

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
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/end`);

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
                        const response = await axios.get(`${this.config.apiUrl}/calls/history`);

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
                        if (this.agoraClient && this.agoraStatus.connected) {
                            await this.agoraClient.leave();
                        }

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
                this.stopCallPolling();
                this.leaveAgoraChannel();
            }
        }).mount('#app');
    </script>
</body>
</html>
