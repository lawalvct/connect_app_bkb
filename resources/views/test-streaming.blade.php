<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect App - Live Streaming</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS with fallback -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/bootstrap.min.css" rel="stylesheet">

    <!-- Load scripts before closing head to ensure proper loading order -->
    <script>
        // Check if external libraries loaded
        window.librariesLoaded = {
            bootstrap: false,
            vue: false,
            axios: false
        };

        // Fallback function
        function checkLibraries() {
            console.log('Checking libraries...');
            window.librariesLoaded.bootstrap = typeof bootstrap !== 'undefined';
            window.librariesLoaded.vue = typeof Vue !== 'undefined';
            window.librariesLoaded.axios = typeof axios !== 'undefined';

            console.log('Libraries status:', window.librariesLoaded);

            if (!window.librariesLoaded.vue) {
                console.error('Vue.js failed to load');
                document.body.innerHTML = '<div style="padding: 50px; text-align: center; font-family: Arial;"><h1>Loading Error</h1><p>Vue.js failed to load. Please use the simple test interface instead:</p><a href="/simple-streaming-test" style="background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Use Simple Test Interface</a></div>';
                return false;
            }
            return true;
        }
    </script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom styles -->
    <style>
        /* Fallback styles in case Bootstrap doesn't load */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8; /* Slightly off-white background */
        }

        .container-fluid {
            width: 100%;
            padding: 0 15px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }

        .col-12 { width: 100%; padding: 0 15px; }
        .col-md-4 { width: 33.333333%; padding: 0 15px; }
        .col-md-6 { width: 50%; padding: 0 15px; }

        @media (max-width: 768px) {
            .col-md-4, .col-md-6 { width: 100%; }

            .video-container {
                height: 250px; /* Smaller height on mobile */
            }

            .chat-container {
                height: 200px; /* Smaller height on mobile */
            }

            .navbar-brand {
                font-size: 16px; /* Smaller font on mobile */
            }
        }

        .card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Add subtle shadow for depth */
        }

        .card-header {
            padding: 15px;
            background-color: #8B0000; /* Dark red theme color */
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            color: white;
        }

        .card-body {
            padding: 15px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 2px;
            text-decoration: none;
            border: 1px solid;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary { background: #8B0000; color: white; border-color: #8B0000; } /* Dark red theme color */
        .btn-success { background: #28a745; color: white; border-color: #28a745; }
        .btn-danger { background: #dc3545; color: white; border-color: #dc3545; }
        .btn-outline-danger { background: transparent; color: #dc3545; border-color: #dc3545; }
        .btn-outline-secondary { background: transparent; color: #6c757d; border-color: #6c757d; }
        .btn-outline-light { background: transparent; color: white; border-color: white; }

        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .form-label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .navbar {
            background-color: #8B0000 !important; /* Dark red theme color */
            padding: 10px 15px;
            color: white;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-dialog {
            position: relative;
            margin: 50px auto;
            max-width: 500px;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 0;
        }

        .modal-header {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 15px;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        .input-group {
            display: flex;
        }

        .input-group .form-control {
            border-radius: 4px 0 0 4px;
        }

        .input-group .btn {
            border-radius: 0 4px 4px 0;
        }

        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        .mt-4 { margin-top: 24px; }
        .mb-4 { margin-bottom: 24px; }
        .me-2 { margin-right: 8px; }
        .ms-2 { margin-left: 8px; }
        .float-end { float: right; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .align-items-start { align-items: flex-start; }

        /* Fix display issues */
        .d-block { display: block !important; }
        .d-none { display: none !important; }
        .stream-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .stream-card:hover {
            box-shadow: 0 5px 15px rgba(139,0,0,0.1);
            transform: translateY(-2px);
        }

        .live-badge {
            background-color: #8B0000; /* Dark red theme color */
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .upcoming-badge {
            background-color: #FFA000; /* Amber color for better visibility */
            color: black;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .ended-badge {
            background-color: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .video-container {
            width: 100%;
            height: 400px;
            background-color: #000;
            border-radius: 8px;
            position: relative;
            margin-bottom: 20px;
            border: 2px solid #8B0000; /* Dark red border */
            overflow: hidden;
        }

        .chat-container {
            height: 300px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow-y: auto;
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #d3d3d3;
        }

        .chat-message {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 4px;
            background-color: white;
        }

        .admin-message {
            background-color: #FFEBEE; /* Light red background */
            border-left: 4px solid #8B0000; /* Dark red border */
        }

        .viewer-count {
            background-color: #A52A2A; /* Lighter dark red for contrast */
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        .controls {
            margin: 20px 0;
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px 15px;
            margin: 10px 0;
            position: relative;
        }

        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }

        /* Additional styles for improved appearance */
        .btn {
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            background-color: #A52A2A; /* Slightly lighter red on hover */
        }

        .chat-message {
            transition: all 0.2s ease;
        }

        .chat-message:hover {
            transform: translateX(2px);
        }

        /* Fix for Vue.js template syntax */
        [v-cloak] {
            display: none;
        }

        /* Improved form styling */
        .form-control:focus {
            border-color: rgba(139,0,0,0.5);
            box-shadow: 0 0 0 0.2rem rgba(139,0,0,0.25);
        }

        /* Improved modal styling */
        .modal-header {
            background-color: #8B0000;
            color: white;
        }

        .btn-close {
            color: white;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        /* Improved badge styling */
        .upcoming-badge {
            background-color: #FFA000; /* Amber color for better visibility */
        }

        /* Improved scrollbar for chat */
        .chat-container::-webkit-scrollbar {
            width: 8px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: #8B0000;
            border-radius: 10px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: #A52A2A;
        }
        /* Loading animation */
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .connecting-text {
            animation: pulse 1.5s infinite;
        }

        /* Improve form inputs */
        .form-control:focus {
            border-color: rgba(139,0,0,0.5);
            box-shadow: 0 0 0 0.2rem rgba(139,0,0,0.25);
        }

        /* Improve buttons */
        .btn-primary:focus, .btn-primary:active {
            background-color: #8B0000;
            border-color: #8B0000;
            box-shadow: 0 0 0 0.2rem rgba(139,0,0,0.25);
        }
    </style>
</head>
<body>
    <div id="app" v-cloak class="container-fluid mt-4">
        <!-- Loading screen -->
        <div id="loading-screen" class="text-center" style="padding: 100px 20px;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4 class="mt-3">🎥 Loading Connect App Streaming Interface...</h4>
            <p class="text-muted">Setting up Vue.js and external libraries</p>
            <div class="mt-4">
                <small class="text-muted">
                    If this takes too long, try the
                    <a href="/simple-streaming-test" class="text-decoration-none">Simple Test Interface</a>
                </small>
            </div>
        </div>
        <div class="row">
            <!-- Navigation -->
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-dark mb-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">Connect App - Live Streaming</span>
                        <div class="navbar-nav ms-auto">
                            <button class="btn btn-outline-light me-2" @click="showLogin = true" v-if="!user">Login</button>
                            <span class="navbar-text" v-if="user">
                                Welcome, @{{ user.username }}
                                <span class="badge ms-2" style="background-color: #8B0000; color: white;" v-if="user.is_admin">ADMIN</span>
                            </span>
                            <button class="btn btn-outline-light ms-2" @click="logout" v-if="user">Logout</button>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Login Modal -->
        <div class="modal" :class="{ 'd-block': showLogin }" v-if="showLogin">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Login</h5>
                        <button type="button" class="btn-close" @click="showLogin = false"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="login">
                            <div class="mb-3">
                                <label class="form-label">Email/Username</label>
                                <input type="text" class="form-control" v-model="loginForm.email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" v-model="loginForm.password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <div v-if="message.text" :class="message.type" style="margin-bottom: 15px;">
            @{{ message.text }}
            <button type="button" class="btn-close float-end" @click="message.text = ''"></button>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column - Stream Management (Admin) -->
            <div class="col-md-4" v-if="user && user.is_admin">
                <div class="card">
                    <div class="card-header">
                        <h5>Stream Management (Admin)</h5>
                    </div>
                    <div class="card-body">
                        <!-- Create Stream Form -->
                        <form @submit.prevent="createStream" class="mb-4">
                            <h6>Create New Stream</h6>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" v-model="streamForm.title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" v-model="streamForm.description" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" v-model="streamForm.is_paid">
                                        <label class="form-check-label">Paid Stream</label>
                                    </div>
                                </div>
                                <div class="col-6" v-if="streamForm.is_paid">
                                    <input type="number" class="form-control" v-model="streamForm.price" placeholder="Price" step="0.01">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label">Scheduled Time</label>
                                <input type="datetime-local" class="form-control" v-model="streamForm.scheduled_at">
                            </div>
                            <button type="submit" class="btn btn-primary">Create Stream</button>
                        </form>

                        <!-- My Streams -->
                        <div v-if="myStreams.length > 0">
                            <h6>My Streams</h6>
                            <div v-for="stream in myStreams" :key="stream.id" class="stream-card">
                                <h6>@{{ stream.title }}</h6>
                                <span :class="getBadgeClass(stream.status)">@{{ stream.status.toUpperCase() }}</span>
                                <div class="mt-2">
                                    <button v-if="stream.status === 'upcoming'"
                                            @click="startStream(stream.id)"
                                            class="btn btn-success btn-sm me-2">Start</button>
                                    <button v-if="stream.status === 'live'"
                                            @click="endStream(stream.id)"
                                            class="btn btn-danger btn-sm me-2">End</button>
                                    <button @click="deleteStream(stream.id)"
                                            v-if="stream.status !== 'live'"
                                            class="btn btn-outline-danger btn-sm">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center Column - Stream List -->
            <div :class="user && user.is_admin ? 'col-md-4' : 'col-md-6'">
                <div class="card">
                    <div class="card-header">
                        <h5>Live Streams</h5>
                        <button @click="loadStreams" class="btn btn-sm btn-outline-secondary float-end">Refresh</button>
                    </div>
                    <div class="card-body">
                        <div v-if="liveStreams.length === 0" class="text-center text-muted py-4">
                            <i class="fas fa-video" style="font-size: 24px; color: #8B0000; display: block; margin-bottom: 10px;"></i>
                            No live streams available
                        </div>
                        <div v-for="stream in liveStreams" :key="stream.id" class="stream-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6>@{{ stream.title }}</h6>
                                    <p class="text-muted mb-1">@{{ stream.description }}</p>
                                    <small>by @{{ stream.streamer.name }}</small>
                                </div>
                                <div class="text-end">
                                    <span :class="getBadgeClass(stream.status)">@{{ stream.status.toUpperCase() }}</span>
                                    <div v-if="stream.status === 'live'" class="viewer-count mt-1">
                                        @{{ stream.current_viewers }} viewers
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button v-if="stream.status === 'live'"
                                        @click="joinStream(stream)"
                                        :data-stream-id="stream.id"
                                        class="btn btn-primary btn-sm me-2">
                                    @{{ stream.is_paid ? 'Buy & Join ($' + stream.price + ')' : 'Join Stream' }}
                                </button>
                                <button @click="viewStreamDetails(stream)" class="btn btn-outline-secondary btn-sm">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Streams -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Upcoming Streams</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="upcomingStreams.length === 0" class="text-center text-muted py-4">
                            <i class="fas fa-calendar" style="font-size: 24px; color: #8B0000; display: block; margin-bottom: 10px;"></i>
                            No upcoming streams
                        </div>
                        <div v-for="stream in upcomingStreams" :key="stream.id" class="stream-card">
                            <h6>@{{ stream.title }}</h6>
                            <span class="upcoming-badge">UPCOMING</span>
                            <p class="mt-2 mb-0">Scheduled: @{{ formatDate(stream.scheduled_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Stream Viewer -->
            <div :class="user && user.is_admin ? 'col-md-4' : 'col-md-6'">
                <div class="card" v-if="currentStream">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>@{{ currentStream.title }}</h5>
                        <button @click="leaveCurrentStream" class="btn btn-sm btn-outline-danger">Leave</button>
                    </div>
                    <div class="card-body">
                        <!-- Video Container -->
                        <div class="video-container" id="video-container">
                            <div v-if="!videoInitialized" class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border text-light" role="status"></div>
                                <span class="text-light ms-2 connecting-text">Connecting to stream...</span>
                            </div>
                        </div>

                        <!-- Stream Info -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="live-badge">LIVE</span>
                                <span class="viewer-count ms-2">@{{ currentStreamViewers }} viewers</span>
                            </div>
                            <small class="text-muted">Streamer: @{{ currentStream.streamer.name }}</small>
                        </div>

                        <!-- Chat -->
                        <div class="chat-container" ref="chatContainer">
                            <div v-for="msg in chatMessages" :key="msg.id"
                                 :class="['chat-message', msg.is_admin ? 'admin-message' : '']">
                                <strong>@{{ msg.username }}:</strong>
                                <span v-if="msg.is_admin" class="badge ms-1" style="background-color: #8B0000; color: white;">ADMIN</span>
                                <div>@{{ msg.message }}</div>
                                <small class="text-muted">@{{ formatTime(msg.created_at) }}</small>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <form @submit.prevent="sendChatMessage" class="mt-3">
                            <div class="input-group">
                                <input type="text" class="form-control" v-model="chatMessage"
                                       placeholder="Type a message..." maxlength="500">
                                <button type="submit" class="btn btn-primary">Send</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- No Stream Selected -->
                <div class="card" v-else>
                    <div class="card-body text-center text-muted">
                        <h5>No Stream Selected</h5>
                        <p>Join a live stream to watch and chat</p>
                        <div class="mt-3">
                            <button @click="loadStreams" class="btn btn-primary">Browse Available Streams</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load external libraries -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/bootstrap.bundle.min.js"></script>

    <!-- Initialize app after libraries load -->
    <script>
        // Wait for DOM and libraries to load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if libraries loaded
            if (typeof Vue === 'undefined') {
                document.getElementById('app').innerHTML = `
                    <div class="container mt-5">
                        <div class="alert alert-danger text-center">
                            <h4>⚠️ Loading Error</h4>
                            <p>Vue.js failed to load from CDN. This might be due to network issues or blocked external resources.</p>
                            <a href="/simple-streaming-test" class="btn btn-primary">Use Simple Test Interface Instead</a>
                        </div>
                    </div>
                `;
                return;
            }

            if (typeof axios === 'undefined') {
                document.getElementById('app').innerHTML = `
                    <div class="container mt-5">
                        <div class="alert alert-warning text-center">
                            <h4>⚠️ Loading Error</h4>
                            <p>Axios failed to load. Using fetch API as fallback.</p>
                        </div>
                    </div>
                `;
                // Create axios fallback
                window.axios = {
                    get: (url, config) => fetch(url, { ...config, method: 'GET' }).then(r => ({ data: r.json() })),
                    post: (url, data, config) => fetch(url, { ...config, method: 'POST', body: JSON.stringify(data) }).then(r => ({ data: r.json() })),
                    put: (url, data, config) => fetch(url, { ...config, method: 'PUT', body: JSON.stringify(data) }).then(r => ({ data: r.json() })),
                    delete: (url, config) => fetch(url, { ...config, method: 'DELETE' }).then(r => ({ data: r.json() }))
                };
            }

            // Initialize Vue app
            const { createApp } = Vue;

        createApp({
            data() {
                return {
                    user: null,
                    showLogin: false,
                    loginForm: {
                        email: '',
                        password: ''
                    },
                    streamForm: {
                        title: '',
                        description: '',
                        is_paid: false,
                        price: 0,
                        scheduled_at: ''
                    },
                    message: {
                        text: '',
                        type: ''
                    },
                    liveStreams: [],
                    upcomingStreams: [],
                    myStreams: [],
                    currentStream: null,
                    currentStreamViewers: 0,
                    chatMessages: [],
                    chatMessage: '',
                    videoInitialized: false,
                    agoraClient: null,
                    lastChatId: 0,
                    chatInterval: null,
                    statusInterval: null
                }
            },
            mounted() {
                this.setupAxios();
                this.loadStreams();
                this.checkAuthStatus();
            },
            beforeUnmount() {
                this.clearIntervals();
            },
            methods: {
                setupAxios() {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                    }
                },

                async checkAuthStatus() {
                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        try {
                            const response = await axios.get('/api/v1/user');
                            this.user = response.data;
                            this.user.is_admin = true; // For testing purposes
                            this.loadMyStreams();
                        } catch (error) {
                            console.error('Auth check failed:', error);
                            localStorage.removeItem('auth_token');
                            delete axios.defaults.headers.common['Authorization'];
                            this.showMessage('Session expired. Please login again.', 'error');
                        }
                    }
                },

                async login() {
                    try {
                        const response = await axios.post('/api/v1/login', this.loginForm);

                        if (response.data.success) {
                            this.user = response.data.data.user;
                            this.user.is_admin = true; // For testing purposes

                            const token = response.data.data.token;
                            localStorage.setItem('auth_token', token);
                            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

                            this.showMessage('Login successful!', 'success');
                            this.showLogin = false;
                            this.loadMyStreams();
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        this.showMessage('Login failed: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                logout() {
                    localStorage.removeItem('auth_token');
                    delete axios.defaults.headers.common['Authorization'];
                    this.user = null;
                    this.myStreams = [];
                    this.leaveCurrentStream();
                },

                async loadStreams() {
                    try {
                        const [liveResponse, upcomingResponse] = await Promise.all([
                            axios.get('/api/v1/streams/latest?limit=10'),
                            axios.get('/api/v1/streams/upcoming?limit=10')
                        ]);

                        this.liveStreams = liveResponse.data.data.streams;
                        this.upcomingStreams = upcomingResponse.data.data.streams;
                    } catch (error) {
                        console.error('Load streams error:', error);
                        this.showMessage('Failed to load streams. Please try again.', 'error');
                    }
                },

                async loadMyStreams() {
                    if (!this.user || !this.user.is_admin) return;

                    try {
                        const response = await axios.get('/api/v1/streams/my-streams');
                        this.myStreams = response.data.data.streams;
                    } catch (error) {
                        this.showMessage('Failed to load your streams', 'error');
                    }
                },

                async createStream() {
                    try {
                        const response = await axios.post('/api/v1/streams', this.streamForm);

                        if (response.data.success) {
                            this.showMessage('Stream created successfully!', 'success');
                            this.streamForm = {
                                title: '',
                                description: '',
                                is_paid: false,
                                price: 0,
                                scheduled_at: ''
                            };
                            this.loadMyStreams();
                            this.loadStreams();
                        }
                    } catch (error) {
                        this.showMessage('Failed to create stream: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                async startStream(streamId) {
                    try {
                        const response = await axios.post(`/api/v1/streams/${streamId}/start`);

                        if (response.data.success) {
                            this.showMessage('Stream started successfully!', 'success');
                            this.loadMyStreams();
                            this.loadStreams();
                        }
                    } catch (error) {
                        this.showMessage('Failed to start stream: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                async endStream(streamId) {
                    try {
                        const response = await axios.post(`/api/v1/streams/${streamId}/end`);

                        if (response.data.success) {
                            this.showMessage('Stream ended successfully!', 'success');
                            this.loadMyStreams();
                            this.loadStreams();

                            if (this.currentStream && this.currentStream.id === streamId) {
                                this.leaveCurrentStream();
                            }
                        }
                    } catch (error) {
                        this.showMessage('Failed to end stream: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                async deleteStream(streamId) {
                    if (confirm('Are you sure you want to delete this stream?')) {
                        try {
                            const response = await axios.delete(`/api/v1/streams/${streamId}`);

                            if (response.data.success) {
                                this.showMessage('Stream deleted successfully!', 'success');
                                this.loadMyStreams();
                                this.loadStreams();
                            }
                        } catch (error) {
                            this.showMessage('Failed to delete stream: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                        }
                    }
                },

                async joinStream(stream) {
                    if (!this.user) {
                        this.showLogin = true;
                        return;
                    }

                    if (stream.is_paid) {
                        // For demo purposes, simulate payment
                        if (!confirm(`This stream costs $${stream.price}. Continue?`)) {
                            return;
                        }
                    }

                    try {
                        const response = await axios.post(`/api/v1/streams/${stream.id}/join`);

                        if (response.data.success) {
                            this.currentStream = stream;
                            this.initializeAgora(response.data.data.agora_config);
                            this.startChatPolling();
                            this.startStatusPolling();
                            this.showMessage('Joined stream successfully!', 'success');
                        }
                    } catch (error) {
                        this.showMessage('Failed to join stream: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                async leaveCurrentStream() {
                    if (!this.currentStream) return;

                    try {
                        await axios.post(`/api/v1/streams/${this.currentStream.id}/leave`);
                        this.currentStream = null;
                        this.chatMessages = [];
                        this.chatMessage = '';
                        this.lastChatId = 0;
                        this.videoInitialized = false;
                        this.clearIntervals();

                        if (this.agoraClient) {
                            await this.agoraClient.leave();
                            this.agoraClient = null;
                        }

                        // Clear video container
                        const container = document.getElementById('video-container');
                        container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="spinner-border text-light" role="status"></div><span class="text-light ms-2 connecting-text">Connecting to stream...</span></div>';
                    } catch (error) {
                        console.error('Error leaving stream:', error);
                        this.showMessage('Error leaving stream. Please try again.', 'error');
                    }
                },

                async initializeAgora(config) {
                    try {
                        // Initialize Agora client
                        this.agoraClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

                        // Join channel
                        await this.agoraClient.join(config.app_id, config.channel_name, config.token, parseInt(config.agora_uid));

                        // Subscribe to remote users
                        this.agoraClient.on("user-published", async (user, mediaType) => {
                            await this.agoraClient.subscribe(user, mediaType);

                            if (mediaType === "video") {
                                const container = document.getElementById('video-container');
                                container.innerHTML = '';
                                user.videoTrack.play(container);
                                this.videoInitialized = true;
                            }

                            if (mediaType === "audio") {
                                user.audioTrack.play();
                            }
                        });

                        this.agoraClient.on("user-unpublished", (user) => {
                            console.log("User left:", user);
                        });

                    } catch (error) {
                        this.showMessage('Failed to initialize video: ' + error.message, 'error');
                    }
                },

                async loadChat() {
                    if (!this.currentStream) return;

                    try {
                        const params = this.lastChatId > 0 ? `?after_id=${this.lastChatId}` : '?limit=20';
                        const response = await axios.get(`/api/v1/streams/${this.currentStream.id}/chat${params}`);

                        if (response.data.success) {
                            const messages = response.data.data.messages;
                            if (messages.length > 0) {
                                this.chatMessages.push(...messages);
                                this.lastChatId = Math.max(...messages.map(m => m.id));
                                this.$nextTick(() => {
                                    if (this.$refs.chatContainer) {
                                        this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
                                    }
                                });
                            }
                        }
                    } catch (error) {
                        console.error('Failed to load chat:', error);
                    }
                },

                async sendChatMessage() {
                    if (!this.chatMessage.trim() || !this.currentStream) return;

                    try {
                        const response = await axios.post(`/api/v1/streams/${this.currentStream.id}/chat`, {
                            message: this.chatMessage
                        });

                        if (response.data.success) {
                            this.chatMessage = '';
                            // Message will be loaded by polling
                        }
                    } catch (error) {
                        this.showMessage('Failed to send message: ' + (error.response?.data?.message || 'Unknown error'), 'error');
                    }
                },

                async updateStreamStatus() {
                    if (!this.currentStream) return;

                    try {
                        const response = await axios.get(`/api/v1/streams/${this.currentStream.id}/status`);
                        if (response.data.success) {
                            this.currentStreamViewers = response.data.data.current_viewers;

                            // If stream ended, leave automatically
                            if (response.data.data.status === 'ended') {
                                this.showMessage('Stream has ended', 'error');
                                this.leaveCurrentStream();
                            }
                        }
                    } catch (error) {
                        console.error('Failed to update stream status:', error);
                    }
                },

                startChatPolling() {
                    this.loadChat(); // Load initial chat
                    this.chatInterval = setInterval(() => {
                        this.loadChat();
                    }, 2000); // Poll every 2 seconds
                },

                startStatusPolling() {
                    this.statusInterval = setInterval(() => {
                        this.updateStreamStatus();
                    }, 5000); // Poll every 5 seconds
                },

                clearIntervals() {
                    if (this.chatInterval) {
                        clearInterval(this.chatInterval);
                        this.chatInterval = null;
                    }
                    if (this.statusInterval) {
                        clearInterval(this.statusInterval);
                        this.statusInterval = null;
                    }
                },

                viewStreamDetails(stream) {
                    // Create a more user-friendly modal instead of alert
                    const detailsHtml = `
                        <div class="modal" style="display: block; background: rgba(0,0,0,0.5);">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" style="background-color: #8B0000; color: white;">
                                        <h5 class="modal-title">${stream.title}</h5>
                                        <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Description:</strong> ${stream.description || 'No description provided'}</p>
                                        <p><strong>Streamer:</strong> ${stream.streamer.name}</p>
                                        <p><strong>Status:</strong> <span class="${this.getBadgeClass(stream.status)}" style="padding: 4px 8px;">${stream.status.toUpperCase()}</span></p>
                                        ${stream.is_paid ? '<p><strong>Price:</strong> $' + stream.price + '</p>' : ''}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                                        ${stream.status === 'live' ? '<button type="button" class="btn btn-primary" onclick="this.closest(\'.modal\').remove(); document.querySelector(\'[data-stream-id=\\\'' + stream.id + '\\\']\').click()">Join Stream</button>' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Add to body
                    const div = document.createElement('div');
                    div.innerHTML = detailsHtml;
                    document.body.appendChild(div.firstElementChild);
                },

                getBadgeClass(status) {
                    switch (status) {
                        case 'live': return 'live-badge';
                        case 'upcoming': return 'upcoming-badge';
                        case 'ended': return 'ended-badge';
                        default: return 'badge bg-secondary';
                    }
                },

                formatDate(date) {
                    return new Date(date).toLocaleString();
                },

                formatTime(date) {
                    return new Date(date).toLocaleTimeString();
                },

                showMessage(text, type) {
                    this.message = {
                        text: text,
                        type: type === 'success' ? 'success' : 'error'
                    };

                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        this.message.text = '';
                    }, 5000);

                    // Scroll to top to ensure message is visible
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }).mount('#app');

        }); // End DOMContentLoaded

        // Fallback in case DOMContentLoaded already fired
        if (document.readyState === 'loading') {
            // Still loading, wait for DOMContentLoaded
        } else {
            // Already loaded, check libraries immediately
            setTimeout(() => {
                if (typeof Vue === 'undefined') {
                    document.getElementById('app').innerHTML = `
                        <div class="container mt-5">
                            <div class="alert alert-danger text-center">
                                <h4>⚠️ Loading Error</h4>
                                <p>External libraries failed to load. Please try refreshing the page or use the simple test interface.</p>
                                <div class="mt-3">
                                    <a href="/simple-streaming-test" class="btn btn-primary me-2">Simple Test Interface</a>
                                    <button onclick="window.location.reload()" class="btn btn-outline-primary">Refresh Page</button>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }, 2000);
        }
    </script>
</body>
</html>
