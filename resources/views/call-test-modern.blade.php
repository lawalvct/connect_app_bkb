<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectApp - Professional Call Testing Suite</title>

    <!-- External Libraries -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.19.3.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f3f4f6;
            --border: #e5e7eb;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Modern Card */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        /* Header */
        .header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* User Info Badge */
        .user-badge {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            box-shadow: var(--shadow);
        }

        .user-badge i {
            color: var(--success);
        }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Modern Button */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: var(--danger-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Login Screen */
        .login-container {
            max-width: 500px;
            margin: 10vh auto;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 3rem;
            text-align: center;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        /* Test User Cards */
        .user-grid {
            display: grid;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .user-card {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }

        .user-card:hover {
            border-color: var(--primary);
            background: #f0f4ff;
            transform: scale(1.02);
        }

        .user-card.selected {
            border-color: var(--primary);
            background: #e3f2fd;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .user-card h4 {
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .user-card p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0.1rem 0;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .status-card {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .status-card.active {
            background: #d1fae5;
            border-color: var(--success);
        }

        .status-card.error {
            background: #fee2e2;
            border-color: var(--danger);
        }

        .status-card h4 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        /* Video Section */
        .video-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .video-wrapper {
            position: relative;
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 16/9;
        }

        .video-wrapper .video-container {
            width: 100%;
            height: 100%;
        }

        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-label {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 10;
        }

        .video-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            opacity: 0.5;
        }

        /* Call Info Banner */
        .call-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        .call-banner.incoming {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.01); }
        }

        .call-banner h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .call-banner .call-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Participant Pills */
        .participant-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .participant-pill {
            background: white;
            border: 2px solid var(--border);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .participant-pill.joined {
            background: #d1fae5;
            border-color: var(--success);
        }

        .participant-pill i {
            font-size: 0.75rem;
        }

        /* Logs Terminal */
        .logs-terminal {
            background: #1a1a1a;
            color: #00ff00;
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.6;
        }

        .log-entry {
            margin-bottom: 0.25rem;
        }

        .log-entry.error { color: #ff4444; }
        .log-entry.success { color: #44ff44; }
        .log-entry.info { color: #4444ff; }
        .log-entry.warning { color: #ffaa00; }

        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            border: 2px solid var(--success);
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            border: 2px solid var(--danger);
            color: #991b1b;
        }

        .alert-info {
            background: #dbeafe;
            border: 2px solid var(--info);
            color: #1e40af;
        }

        /* Conversation List */
        .conversation-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .conversation-item {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .conversation-item:hover {
            border-color: var(--primary);
            background: #f0f4ff;
            transform: translateX(4px);
        }

        .conversation-item.selected {
            border-color: var(--primary);
            background: #e3f2fd;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .conversation-item h4 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .conversation-item p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: var(--success);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: white;
        }

        .badge-info {
            background: var(--info);
            color: white;
        }

        /* Control Panel */
        .control-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 12px;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: 1fr;
            }

            .grid-2, .grid-3, .grid-4 {
                grid-template-columns: 1fr;
            }

            .app-container {
                padding: 1rem;
            }

            .card {
                padding: 1.5rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Incoming Call Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s;
        }

        .incoming-call-modal {
            background: white;
            border-radius: 24px;
            padding: 0;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .modal-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            text-transform: capitalize;
        }

        .modal-body {
            padding: 2rem;
        }

        .caller-info {
            text-align: center;
        }

        .caller-avatar {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid var(--primary);
        }

        .caller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .caller-avatar i {
            font-size: 3rem;
            color: var(--primary);
        }

        .caller-info h3 {
            margin: 0.5rem 0 0.25rem;
            font-size: 1.5rem;
            color: var(--dark);
        }

        .caller-info p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .call-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin-top: 1rem;
            font-weight: 600;
            color: var(--primary);
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            padding: 1.5rem 2rem 2rem;
        }

        .modal-actions button {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-reject {
            background: var(--danger);
            color: white;
        }

        .btn-reject:hover {
            background: var(--danger-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-accept {
            background: var(--success);
            color: white;
        }

        .btn-accept:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .animate-ring {
            animation: ring 1s ease-in-out infinite;
        }

        @keyframes ring {
            0%, 100% { transform: rotate(0deg); }
            10%, 30% { transform: rotate(-15deg); }
            20%, 40% { transform: rotate(15deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
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
                    <i class="fas fa-phone-alt" style="font-size: 3rem; color: var(--primary);"></i>
                    <h2>ConnectApp Call Testing</h2>
                    <p style="color: #6b7280;">Professional call testing interface</p>
                </div>

                <div v-if="loginError" class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>@{{ loginError }}</span>
                </div>

                <div v-if="loginSuccess" class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Login successful! Loading interface...</span>
                </div>

                <!-- Quick Test Users -->
                <div class="user-grid">
                    <div v-for="testUser in testUsers" :key="testUser.id"
                         class="user-card"
                         :class="{ selected: selectedUserId === testUser.id }"
                         @click="selectUser(testUser)">
                        <h4><i class="fas fa-user-circle"></i> @{{ testUser.name }}</h4>
                        <p>@{{ testUser.email }}</p>
                        <p><span class="badge badge-info">ID: @{{ testUser.id }}</span></p>
                    </div>
                </div>

                <!-- Manual Login -->
                <div style="margin-top: 2rem;">
                    <div class="form-group">
                        <label>Email</label>
                        <input v-model="loginForm.email" type="email" placeholder="Enter email">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input v-model="loginForm.password" type="password" placeholder="Enter password">
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button @click="login" :disabled="loginLoading" class="btn btn-primary" style="flex: 1;">
                            <div v-if="loginLoading" class="spinner"></div>
                            <i v-else class="fas fa-sign-in-alt"></i>
                            <span>@{{ loginLoading ? 'Logging in...' : 'Login' }}</span>
                        </button>

                        <button @click="loadTestUsers" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Interface -->
        <div v-if="isAuthenticated" class="app-container">
            <div class="header">
                <h1><i class="fas fa-phone-volume"></i> ConnectApp Call Testing Suite</h1>
                <p>Professional Audio & Video Call Testing Interface</p>

                <div v-if="currentUser" class="user-badge">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>@{{ currentUser.name }}</strong> (@{{ currentUser.email }})</span>
                    <button @click="logout" class="btn btn-sm btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>

            <!-- Incoming Call Banner -->
            <div v-if="currentCall && currentCall.status === 'initiated' && !userIsInitiator" class="call-banner incoming">
                <h3><i class="fas fa-phone-volume"></i> Incoming Call!</h3>
                <p><strong>From:</strong> @{{ currentCall.initiator?.name || 'Unknown' }}</p>
                <p><strong>Type:</strong> <span class="badge badge-success">@{{ currentCall.call_type }}</span></p>
                <div class="call-actions">
                    <button @click="answerCall" class="btn btn-success btn-lg">
                        <i class="fas fa-phone"></i> Accept
                    </button>
                    <button @click="rejectCall" class="btn btn-danger btn-lg">
                        <i class="fas fa-phone-slash"></i> Decline
                    </button>
                </div>
            </div>

            <!-- Active Call Banner -->
            <div v-if="currentCall && currentCall.status === 'connected'" class="call-banner">
                <h3><i class="fas fa-phone"></i> Call Active</h3>
                <p><strong>Call ID:</strong> @{{ currentCall.id }}</p>
                <p><strong>Type:</strong> <span class="badge badge-warning">@{{ currentCall.call_type }}</span></p>
                <div class="participant-list">
                    <div v-for="participant in currentCall.participants" :key="participant.user_id"
                         class="participant-pill" :class="participant.status">
                        <i class="fas fa-user"></i>
                        <span>@{{ participant.name }} (@{{ participant.status }})</span>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>System Status</h3>
                </div>

                <div class="status-grid">
                    <div class="status-card" :class="{ active: callStatus.connected }">
                        <h4><i class="fas fa-phone"></i> Call</h4>
                        <p>@{{ callStatus.message }}</p>
                    </div>

                    <div class="status-card" :class="{ active: agoraStatus.connected }">
                        <h4><i class="fas fa-broadcast-tower"></i> Agora</h4>
                        <p>@{{ agoraStatus.message }}</p>
                    </div>

                    <div class="status-card" :class="{ active: mediaStatus.camera }">
                        <h4><i class="fas fa-video"></i> Camera</h4>
                        <p>@{{ mediaStatus.camera ? 'Active' : 'Inactive' }}</p>
                    </div>

                    <div class="status-card" :class="{ active: mediaStatus.microphone }">
                        <h4><i class="fas fa-microphone"></i> Microphone</h4>
                        <p>@{{ mediaStatus.microphone ? 'Active' : 'Muted' }}</p>
                    </div>
                </div>
            </div>

            <!-- Conversations -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comments"></i>
                    <h3>Conversations</h3>
                    <button @click="loadConversations" :disabled="conversationsLoading" class="btn btn-primary btn-sm" style="margin-left: auto;">
                        <div v-if="conversationsLoading" class="spinner"></div>
                        <i v-else class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <div v-if="conversations.length === 0" class="alert alert-info" style="text-align: center;">
                    <div>
                        <i class="fas fa-info-circle"></i>
                        <p style="margin: 0.5rem 0;"><strong>No conversations found</strong></p>
                        <p style="margin: 0.5rem 0; font-size: 0.9rem;">Create a test conversation between User 3114 and User 4098</p>
                        <button @click="createTestConversation" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i> Create Test Conversation
                        </button>
                    </div>
                </div>

                <div class="conversation-list" v-if="conversations.length > 0">
                    <div v-for="conversation in conversations" :key="conversation.id"
                         class="conversation-item"
                         :class="{ selected: config.conversationId === conversation.id }"
                         @click="selectConversation(conversation)">
                        <h4>@{{ conversation.name || 'Conversation ' + conversation.id }}</h4>
                        <p><i class="fas fa-users"></i> @{{ conversation.participants?.map(p => p.name).join(', ') }}</p>
                        <p><i class="fas fa-clock"></i> @{{ formatDate(conversation.updated_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Call Configuration -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cog"></i>
                    <h3>Call Configuration</h3>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Conversation ID</label>
                        <input v-model="config.conversationId" type="number" readonly>
                    </div>

                    <div class="form-group">
                        <label>Call Type</label>
                        <select v-model="config.callType">
                            <option value="audio">🎤 Audio Call</option>
                            <option value="video">📹 Video Call</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Video Streams -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-video"></i>
                    <h3>Video Streams</h3>
                </div>

                <div class="video-grid">
                    <div class="video-wrapper">
                        <div class="video-label">Local Video</div>
                        <div id="local-video" class="video-container"></div>
                        <div v-if="!mediaStatus.camera" class="video-overlay">
                            <i class="fas fa-video-slash"></i>
                        </div>
                    </div>

                    <div class="video-wrapper">
                        <div class="video-label">Remote Video</div>
                        <div id="remote-video" class="video-container"></div>
                        <div v-if="!hasRemoteVideo" class="video-overlay">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call Controls -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-sliders-h"></i>
                    <h3>Call Controls</h3>
                </div>

                <div class="control-panel">
                    <button @click="initiateCall" :disabled="loading || currentCall || !config.conversationId" class="btn btn-primary btn-lg">
                        <i class="fas fa-phone"></i>
                        Initiate @{{ config.callType.charAt(0).toUpperCase() + config.callType.slice(1) }} Call
                    </button>

                    <button @click="endCall" :disabled="!currentCall" class="btn btn-danger btn-lg">
                        <i class="fas fa-phone-slash"></i>
                        End Call
                    </button>

                    <button @click="toggleCamera" :disabled="!agoraStatus.connected || config.callType !== 'video'" class="btn btn-warning">
                        <i :class="mediaStatus.camera ? 'fas fa-video-slash' : 'fas fa-video'"></i>
                        @{{ mediaStatus.camera ? 'Turn Off Camera' : 'Turn On Camera' }}
                    </button>

                    <button @click="toggleMicrophone" :disabled="!agoraStatus.connected" class="btn btn-warning">
                        <i :class="mediaStatus.microphone ? 'fas fa-microphone-slash' : 'fas fa-microphone'"></i>
                        @{{ mediaStatus.microphone ? 'Mute Mic' : 'Unmute Mic' }}
                    </button>

                    <button @click="getCallHistory" class="btn btn-info">
                        <i class="fas fa-history"></i>
                        Call History
                    </button>

                    <button @click="runDiagnostics" class="btn btn-outline">
                        <i class="fas fa-stethoscope"></i>
                        Diagnostics
                    </button>
                </div>
            </div>

            <!-- Incoming Call Modal -->
            <div v-if="incomingCall.show" class="modal-overlay" @click.self="rejectIncomingCall">
                <div class="incoming-call-modal">
                    <div class="modal-header">
                        <i class="fas fa-phone-volume animate-ring"></i>
                        <h2>Incoming @{{ incomingCall.data && incomingCall.data.call_type ? incomingCall.data.call_type : 'Call' }}</h2>
                    </div>

                    <div class="modal-body">
                        <div class="caller-info">
                            <div class="caller-avatar">
                                <img v-if="incomingCall.data && incomingCall.data.initiator && incomingCall.data.initiator.avatar_url"
                                     :src="incomingCall.data.initiator.avatar_url"
                                     :alt="incomingCall.data.initiator.name">
                                <i v-else class="fas fa-user-circle"></i>
                            </div>
                            <h3>@{{ incomingCall.data && incomingCall.data.initiator ? incomingCall.data.initiator.name : 'Unknown' }}</h3>
                            <p>@{{ incomingCall.data && incomingCall.data.initiator ? incomingCall.data.initiator.username : '' }}</p>
                            <div class="call-type-badge">
                                <i :class="incomingCall.data && incomingCall.data.call_type === 'video' ? 'fas fa-video' : 'fas fa-phone'"></i>
                                @{{ incomingCall.data && incomingCall.data.call_type === 'video' ? 'Video Call' : 'Audio Call' }}
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button @click="rejectIncomingCall" class="btn-reject">
                            <i class="fas fa-phone-slash"></i>
                            Reject
                        </button>
                        <button @click="acceptIncomingCall" class="btn-accept">
                            <i class="fas fa-phone"></i>
                            Accept
                        </button>
                    </div>
                </div>
            </div>

            <!-- Activity Logs -->\n            <div class="card">
                <div class="card-header">
                    <i class="fas fa-terminal"></i>
                    <h3>Activity Logs</h3>
                    <button @click="clearLogs" class="btn btn-danger btn-sm" style="margin-left: auto;">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>

                <div class="logs-terminal">
                    <div v-for="log in logs" :key="log.id" class="log-entry" :class="log.type">
                        [@{{ log.timestamp }}] @{{ log.message }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    // Authentication
                    isAuthenticated: false,
                    currentUser: null,
                    authToken: '',
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

                    // Configuration
                    config: {
                        apiUrl: '{{ config("app.url") }}/api/v1',
                        conversationId: null,
                        callType: 'audio'
                    },

                    // Call State
                    loading: false,
                    currentCall: null,
                    agoraConfig: null,
                    agoraClient: null,
                    pusher: null,

                    // Media
                    localTracks: {
                        video: null,
                        audio: null
                    },
                    hasRemoteVideo: false,

                    // Incoming Call Modal
                    incomingCall: {
                        show: false,
                        data: null
                    },

                    // Status
                    callStatus: {
                        connected: false,
                        message: 'Not connected',
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

                    // Logs
                    logs: [],
                    logId: 0
                }
            },

            computed: {
                userIsInitiator() {
                    return this.currentUser && this.currentCall &&
                           this.currentCall.initiator &&
                           this.currentCall.initiator.id === this.currentUser.id;
                }
            },

            async mounted() {
                this.log('Application initialized', 'success');
                await this.loadTestUsers();
                await this.initializeAgora();
            },

            methods: {
                // Authentication
                async loadTestUsers() {
                    this.testUsers = [
                        { id: 3114, name: 'Oz Lawal', email: 'lawalthb@gmail.com' },
                        { id: 4098, name: 'Vick', email: 'vick@gmail.com' }
                    ];
                    this.log('Test users loaded', 'info');
                },

                selectUser(user) {
                    this.selectedUserId = user.id;
                    this.loginForm.email = user.email;
                    this.loginForm.password = '12345678';
                },

                async createTestConversation() {
                    this.conversationsLoading = true;
                    try {
                        // Create a conversation between the two test users
                        const response = await axios.post(`${this.config.apiUrl}/conversations`, {
                            type: 'private',
                            participant_ids: [3114, 4098],
                            name: 'Test Call Conversation'
                        });

                        if (response.data.success) {
                            this.log('Test conversation created successfully', 'success');
                            await this.loadConversations();
                        }
                    } catch (error) {
                        this.log(`Failed to create conversation: ${error.response?.data?.message || error.message}`, 'error');
                    }
                    this.conversationsLoading = false;
                },

                async login() {
                    if (!this.loginForm.email || !this.loginForm.password) {
                        this.loginError = 'Please enter email and password';
                        return;
                    }

                    this.loginLoading = true;
                    this.loginError = '';

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

                            axios.defaults.headers.common['Authorization'] = `Bearer ${this.authToken}`;

                            this.log(`Logged in as ${this.currentUser.name}`, 'success');

                            setTimeout(() => {
                                this.loadConversations();
                                this.initializePusher();
                            }, 1000);
                        }
                    } catch (error) {
                        this.loginError = error.response?.data?.message || 'Login failed';
                        this.log(`Login failed: ${this.loginError}`, 'error');
                    }

                    this.loginLoading = false;
                },

                logout() {
                    this.isAuthenticated = false;
                    this.currentUser = null;
                    this.authToken = '';
                    delete axios.defaults.headers.common['Authorization'];
                    this.resetCallState();
                    this.log('Logged out', 'info');
                },

                // Pusher
                initializePusher() {
                    try {
                        this.pusher = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
                            cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
                            encrypted: true
                        });

                        this.log('Pusher initialized', 'success');
                    } catch (error) {
                        this.log(`Pusher initialization failed: ${error.message}`, 'error');
                    }
                },

                subscribeToCallEvents(conversationId) {
                    if (!this.pusher) return;

                    const channel = this.pusher.subscribe(`conversation.${conversationId}`);

                    channel.bind('call.initiated', (data) => {
                        this.log('Incoming call detected via Pusher', 'success');
                        this.handleIncomingCall(data);
                    });

                    channel.bind('call.answered', (data) => {
                        this.log('Call answered', 'success');
                    });

                    channel.bind('call.ended', (data) => {
                        this.log('Call ended', 'info');
                        this.resetCallState();
                    });

                    this.log(`Subscribed to conversation.${conversationId}`, 'success');
                },

                handleIncomingCall(data) {
                    if (data.initiator.id !== this.currentUser.id) {
                        this.log(`📞 Incoming ${data.call_type} call from ${data.initiator.name}`, 'success');

                        // Show incoming call modal
                        this.incomingCall.show = true;
                        this.incomingCall.data = {
                            id: data.call_id,
                            call_type: data.call_type,
                            agora_channel_name: data.agora_channel_name,
                            initiator: data.initiator,
                            participants: data.participants || [],
                            started_at: data.started_at
                        };

                        // Play ringtone (optional)
                        this.playRingtone();
                    }
                },

                playRingtone() {
                    // Optional: Add ringtone audio
                    this.log('🔔 Ringing...', 'info');
                },

                stopRingtone() {
                    // Optional: Stop ringtone audio
                },

                async acceptIncomingCall() {
                    if (!this.incomingCall.data) return;

                    this.stopRingtone();
                    this.loading = true;

                    try {
                        this.log('Accepting incoming call...', 'info');

                        // Leave any existing Agora session first
                        if (this.agoraStatus.connected) {
                            this.log('⚠️ Cleaning up previous call session...', 'warning');
                            await this.leaveAgoraChannel();
                            await new Promise(resolve => setTimeout(resolve, 300));
                        }

                        // Set current call from incoming data
                        this.currentCall = {
                            id: this.incomingCall.data.id,
                            call_type: this.incomingCall.data.call_type,
                            status: 'initiated',
                            initiator: this.incomingCall.data.initiator,
                            participants: this.incomingCall.data.participants
                        };
                        this.config.callType = this.incomingCall.data.call_type;

                        // Close modal
                        this.incomingCall.show = false;

                        // Answer the call via API
                        await this.answerCall();

                    } catch (error) {
                        this.log(`Failed to accept call: ${error.message}`, 'error');
                        this.incomingCall.show = false;
                    } finally {
                        this.loading = false;
                    }
                },

                rejectIncomingCall() {
                    if (!this.incomingCall.data) return;

                    this.stopRingtone();
                    this.log('Call rejected', 'info');

                    // Reject via API
                    this.rejectCall(this.incomingCall.data.id);

                    // Close modal
                    this.incomingCall.show = false;
                    this.incomingCall.data = null;
                },

                // Conversations
                async loadConversations() {
                    this.conversationsLoading = true;
                    try {
                        const response = await axios.get(`${this.config.apiUrl}/conversations`);

                        if (response.data.success) {
                            let conversationsData = response.data.data;
                            if (conversationsData && typeof conversationsData === 'object' && conversationsData.conversations) {
                                conversationsData = conversationsData.conversations;
                            }
                            this.conversations = Array.isArray(conversationsData) ? conversationsData : [];
                            this.log(`Loaded ${this.conversations.length} conversations`, 'success');

                            if (this.conversations.length > 0 && !this.config.conversationId) {
                                this.selectConversation(this.conversations[0]);
                            }
                        }
                    } catch (error) {
                        this.log(`Failed to load conversations: ${error.message}`, 'error');
                    }
                    this.conversationsLoading = false;
                },

                selectConversation(conversation) {
                    this.config.conversationId = conversation.id;
                    this.log(`Selected conversation: ${conversation.name || conversation.id}`, 'info');
                    this.subscribeToCallEvents(conversation.id);
                },

                formatDate(dateString) {
                    if (!dateString) return 'Unknown';
                    return new Date(dateString).toLocaleString();
                },

                // Agora
                async initializeAgora() {
                    try {
                        this.agoraClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

                        this.agoraClient.on("user-published", async (user, mediaType) => {
                            try {
                                this.log(`📢 User ${user.uid} published ${mediaType}`, 'info');

                                // Retry mechanism - wait for user to be fully in the channel
                                let attempts = 0;
                                const maxAttempts = 10;
                                let subscribed = false;

                                while (attempts < maxAttempts && !subscribed) {
                                    attempts++;

                                    try {
                                        // Check if user exists in remote users
                                        const remoteUsers = this.agoraClient.remoteUsers;
                                        const userInChannel = remoteUsers.find(u => u.uid === user.uid);

                                        if (!userInChannel) {
                                            const waitTime = Math.min(100 * attempts, 1000); // 100ms, 200ms, 300ms... up to 1s
                                            this.log(`⏳ Attempt ${attempts}/${maxAttempts}: User ${user.uid} not yet in channel, waiting ${waitTime}ms...`, 'warning');
                                            await new Promise(resolve => setTimeout(resolve, waitTime));
                                            continue;
                                        }

                                        // User found, try to subscribe
                                        this.log(`🔄 Attempt ${attempts}: Subscribing to user ${user.uid} - ${mediaType}`, 'info');
                                        await this.agoraClient.subscribe(user, mediaType);
                                        this.log(`✓ Subscribed to user ${user.uid} - ${mediaType}`, 'success');
                                        subscribed = true;

                                        if (mediaType === 'video') {
                                            const remoteContainer = document.getElementById('remote-video');
                                            if (remoteContainer && user.videoTrack) {
                                                this.log('🎥 Playing remote video track...', 'info');
                                                remoteContainer.innerHTML = '';

                                                // Play with retry
                                                try {
                                                    await user.videoTrack.play('remote-video');
                                                    this.hasRemoteVideo = true;
                                                    this.log('✓ Remote video playing successfully!', 'success');
                                                } catch (playError) {
                                                    this.log(`⚠️ Video play failed: ${playError.message}, retrying...`, 'warning');
                                                    await new Promise(resolve => setTimeout(resolve, 500));
                                                    await user.videoTrack.play('remote-video');
                                                    this.hasRemoteVideo = true;
                                                    this.log('✓ Remote video playing (retry successful)!', 'success');
                                                }
                                            } else {
                                                this.log('❌ Remote video container or track not found', 'error');
                                            }
                                        }

                                        if (mediaType === 'audio') {
                                            if (user.audioTrack) {
                                                user.audioTrack.play();
                                                this.log('✓ Remote audio playing', 'success');
                                            } else {
                                                this.log('⚠️ Remote audio track not found', 'warning');
                                            }
                                        }

                                    } catch (subscribeError) {
                                        if (subscribeError.code === 'INVALID_REMOTE_USER') {
                                            const waitTime = Math.min(100 * attempts, 1000);
                                            this.log(`⏳ Attempt ${attempts}/${maxAttempts}: User not ready yet, waiting ${waitTime}ms...`, 'warning');
                                            await new Promise(resolve => setTimeout(resolve, waitTime));
                                        } else {
                                            // Other error, don't retry
                                            throw subscribeError;
                                        }
                                    }
                                }

                                if (!subscribed) {
                                    this.log(`✗ Failed to subscribe to user ${user.uid} after ${maxAttempts} attempts`, 'error');
                                }

                            } catch (error) {
                                this.log(`✗ Error in user-published: ${error.code || 'UNKNOWN'} - ${error.message}`, 'error');
                                console.error('Full error:', error);
                            }
                        });

                        this.agoraClient.on("user-unpublished", (user, mediaType) => {
                            this.log(`User ${user.uid} unpublished ${mediaType}`, 'info');
                            if (mediaType === 'video') {
                                this.hasRemoteVideo = false;
                                const remoteContainer = document.getElementById('remote-video');
                                if (remoteContainer) {
                                    remoteContainer.innerHTML = '';
                                }
                            }
                        });

                        this.agoraClient.on("user-joined", (user) => {
                            this.log(`User ${user.uid} joined the channel`, 'info');
                        });

                        this.agoraClient.on("user-left", (user, reason) => {
                            this.log(`User ${user.uid} left the channel: ${reason}`, 'info');
                            this.hasRemoteVideo = false;
                            const remoteContainer = document.getElementById('remote-video');
                            if (remoteContainer) {
                                remoteContainer.innerHTML = '';
                            }
                        });

                        this.log('Agora client initialized', 'success');
                    } catch (error) {
                        this.log(`Agora initialization failed: ${error.message}`, 'error');
                    }
                },

                async initiateCall() {
                    if (!this.config.conversationId) {
                        this.log('Please select a conversation', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/initiate`, {
                            conversation_id: this.config.conversationId,
                            call_type: this.config.callType
                        });

                        this.currentCall = response.data.data.call;
                        this.agoraConfig = response.data.data.agora_config;

                        this.log(`Call initiated (ID: ${this.currentCall.id})`, 'success');
                        this.callStatus.connected = true;
                        this.callStatus.message = 'Call initiated';

                        await this.joinAgoraChannel();
                    } catch (error) {
                        this.log(`Failed to initiate call: ${error.response?.data?.message || error.message}`, 'error');
                    }
                    this.loading = false;
                },

                async answerCall() {
                    if (!this.currentCall) return;

                    try {
                        const response = await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/answer`);

                        this.currentCall = response.data.data.call;
                        this.agoraConfig = response.data.data.agora_config;

                        this.log('Call answered', 'success');
                        this.callStatus.message = 'Call connected';

                        await this.joinAgoraChannel();
                    } catch (error) {
                        this.log(`Failed to answer call: ${error.message}`, 'error');
                    }
                },

                async rejectCall(callId = null) {
                    const targetCallId = callId || this.currentCall?.id;
                    if (!targetCallId) return;

                    try {
                        await axios.post(`${this.config.apiUrl}/calls/${targetCallId}/reject`);
                        this.log('Call rejected', 'success');
                        this.resetCallState();
                    } catch (error) {
                        this.log(`Failed to reject call: ${error.message}`, 'error');
                    }
                },

                async endCall() {
                    if (!this.currentCall) return;

                    try {
                        await axios.post(`${this.config.apiUrl}/calls/${this.currentCall.id}/end`);
                        this.log('Call ended', 'success');
                        await this.leaveAgoraChannel();
                        this.resetCallState();
                    } catch (error) {
                        this.log(`Failed to end call: ${error.message}`, 'error');
                    }
                },

                async joinAgoraChannel() {
                    if (!this.agoraConfig || !this.agoraClient) {
                        this.log('Missing Agora configuration', 'error');
                        return;
                    }

                    try {
                        // Leave any existing channel first
                        if (this.agoraStatus.connected) {
                            this.log('⚠️ Already connected to a channel, leaving first...', 'warning');
                            await this.leaveAgoraChannel();
                            // Small delay to ensure clean disconnect
                            await new Promise(resolve => setTimeout(resolve, 500));
                        }

                        this.log(`🔄 Joining Agora channel: ${this.agoraConfig.channel_name}`, 'info');

                        await this.agoraClient.join(
                            this.agoraConfig.app_id,
                            this.agoraConfig.channel_name,
                            this.agoraConfig.token,
                            this.agoraConfig.uid
                        );

                        this.log(`✓ Joined Agora channel: ${this.agoraConfig.channel_name}`, 'success');
                        this.agoraStatus.connected = true;
                        this.agoraStatus.message = 'Connected';

                        await this.createLocalTracks();
                    } catch (error) {
                        this.log(`✗ Failed to join Agora: ${error.message}`, 'error');
                        console.error('Join Agora error:', error);
                    }
                },

                async createLocalTracks() {
                    try {
                        const tracksToPublish = [];

                        // Create audio track
                        this.localTracks.audio = await AgoraRTC.createMicrophoneAudioTrack();
                        this.mediaStatus.microphone = true;
                        tracksToPublish.push(this.localTracks.audio);
                        this.log('Audio track created', 'success');

                        // Create video track only for video calls
                        if (this.config.callType === 'video') {
                            this.localTracks.video = await AgoraRTC.createCameraVideoTrack();
                            this.mediaStatus.camera = true;
                            tracksToPublish.push(this.localTracks.video);
                            this.log('Video track created', 'success');

                            // Play local video
                            const localContainer = document.getElementById('local-video');
                            if (localContainer && this.localTracks.video) {
                                await this.localTracks.video.play('local-video');
                                this.log('Local video playing', 'success');
                            }
                        }

                        // Publish all tracks
                        if (tracksToPublish.length > 0) {
                            await this.agoraClient.publish(tracksToPublish);
                            const trackTypes = tracksToPublish.map(t => t.trackMediaType).join(', ');
                            this.log(`✓ Published ${tracksToPublish.length} track(s): ${trackTypes}`, 'success');
                            this.log(`🎥 My UID: ${this.agoraConfig.uid} | Publishing to channel: ${this.agoraConfig.channel_name}`, 'info');
                        }
                    } catch (error) {
                        this.log(`Failed to create tracks: ${error.message}`, 'error');
                        console.error('Create tracks error:', error);
                    }
                },

                async leaveAgoraChannel() {
                    try {
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

                        if (this.agoraClient && this.agoraStatus.connected) {
                            await this.agoraClient.leave();
                        }

                        this.agoraStatus.connected = false;
                        this.agoraStatus.message = 'Disconnected';
                        this.mediaStatus.camera = false;
                        this.mediaStatus.microphone = false;
                        this.hasRemoteVideo = false;

                        document.getElementById('local-video').innerHTML = '';
                        document.getElementById('remote-video').innerHTML = '';

                        this.log('Left Agora channel', 'info');
                    } catch (error) {
                        this.log(`Error leaving channel: ${error.message}`, 'error');
                    }
                },

                async toggleCamera() {
                    if (!this.localTracks.video) {
                        this.log('Camera not available (audio call or not initialized)', 'warning');
                        return;
                    }

                    try {
                        const newState = !this.mediaStatus.camera;
                        await this.localTracks.video.setEnabled(newState);
                        this.mediaStatus.camera = newState;
                        this.log(`Camera ${this.mediaStatus.camera ? 'enabled' : 'disabled'}`, 'success');
                    } catch (error) {
                        this.log(`Failed to toggle camera: ${error.message}`, 'error');
                    }
                },

                async toggleMicrophone() {
                    if (!this.localTracks.audio) {
                        this.log('Microphone not available', 'warning');
                        return;
                    }

                    try {
                        const newState = !this.mediaStatus.microphone;
                        await this.localTracks.audio.setEnabled(newState);
                        this.mediaStatus.microphone = newState;
                        this.log(`Microphone ${this.mediaStatus.microphone ? 'enabled' : 'disabled'}`, 'success');
                    } catch (error) {
                        this.log(`Failed to toggle microphone: ${error.message}`, 'error');
                    }
                },

                async getCallHistory() {
                    try {
                        const response = await axios.get(`${this.config.apiUrl}/calls/history`);
                        this.log(`Retrieved ${response.data.data.calls.length} calls`, 'success');
                    } catch (error) {
                        this.log(`Failed to get history: ${error.message}`, 'error');
                    }
                },

                runDiagnostics() {
                    this.log('=== DIAGNOSTICS ===', 'info');
                    this.log(`Auth: ${this.isAuthenticated ? 'Yes' : 'No'}`, 'info');
                    this.log(`User: ${this.currentUser?.name || 'None'} (ID: ${this.currentUser?.id || 'N/A'})`, 'info');
                    this.log(`Call: ${this.currentCall ? 'Active (ID: ' + this.currentCall.id + ')' : 'None'}`, 'info');
                    this.log(`Call Type: ${this.config.callType || 'N/A'}`, 'info');
                    this.log(`Conversation: ${this.config.conversationId || 'None'}`, 'info');
                    this.log(`Agora: ${this.agoraStatus.connected ? 'Connected' : 'Disconnected'}`, 'info');
                    this.log(`Agora Channel: ${this.agoraConfig?.channel_name || 'N/A'}`, 'info');
                    this.log(`My Agora UID: ${this.agoraConfig?.uid || 'N/A'}`, 'info');
                    this.log(`Camera: ${this.mediaStatus.camera ? 'On' : 'Off'}`, 'info');
                    this.log(`Microphone: ${this.mediaStatus.microphone ? 'On' : 'Off'}`, 'info');

                    // Local tracks
                    this.log(`--- Local Tracks ---`, 'info');
                    this.log(`Video Track: ${this.localTracks.video ? 'Created' : 'None'}`, 'info');
                    this.log(`Audio Track: ${this.localTracks.audio ? 'Created' : 'None'}`, 'info');

                    if (this.localTracks.video) {
                        this.log(`  Video Enabled: ${this.localTracks.video.enabled ? 'Yes' : 'No'}`, 'info');
                        this.log(`  Video Playing: ${this.localTracks.video.isPlaying ? 'Yes' : 'No'}`, 'info');
                    }

                    if (this.agoraClient && this.agoraStatus.connected) {
                        // Published tracks
                        const publishedTracks = this.agoraClient.localTracks;
                        this.log(`--- Published Tracks ---`, 'info');
                        this.log(`Published: ${publishedTracks.length} track(s)`, 'info');
                        publishedTracks.forEach((track, index) => {
                            this.log(`  Track ${index + 1}: ${track.trackMediaType} (enabled: ${track.enabled})`, 'info');
                        });

                        // Remote users
                        const remoteUsers = this.agoraClient.remoteUsers;
                        this.log(`--- Remote Users ---`, 'info');
                        this.log(`Total Remote Users: ${remoteUsers.length}`, 'info');
                        remoteUsers.forEach((user, index) => {
                            this.log(`  User ${index + 1}: UID ${user.uid}`, 'info');
                            this.log(`    Has Video: ${user.hasVideo ? 'Yes' : 'No'}`, 'info');
                            this.log(`    Has Audio: ${user.hasAudio ? 'Yes' : 'No'}`, 'info');
                            this.log(`    Video Track: ${user.videoTrack ? 'Available' : 'None'}`, 'info');
                            this.log(`    Audio Track: ${user.audioTrack ? 'Available' : 'None'}`, 'info');
                            if (user.videoTrack) {
                                this.log(`    Video Playing: ${user.videoTrack.isPlaying ? 'Yes' : 'No'}`, 'info');
                            }
                        });
                    }

                    this.log('===================', 'info');
                },

                resetCallState() {
                    this.currentCall = null;
                    this.agoraConfig = null;
                    this.callStatus.connected = false;
                    this.callStatus.message = 'Not connected';
                    this.callStatus.error = false;
                },

                // Logging
                log(message, type = 'info') {
                    const timestamp = new Date().toLocaleTimeString();
                    this.logs.unshift({
                        id: this.logId++,
                        message,
                        type,
                        timestamp
                    });

                    if (this.logs.length > 100) {
                        this.logs.pop();
                    }

                    console.log(`[${timestamp}] ${type.toUpperCase()}: ${message}`);
                },

                clearLogs() {
                    this.logs = [];
                    this.log('Logs cleared', 'info');
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
