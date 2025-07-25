<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Streaming Test - Connect App</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .auth-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stream-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .stream-item h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-live {
            background: #dc3545;
            color: white;
        }
        
        .badge-upcoming {
            background: #ffc107;
            color: black;
        }
        
        .badge-ended {
            background: #6c757d;
            color: white;
        }
        
        .badge-admin {
            background: #007bff;
            color: white;
        }
        
        .response {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .user-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .loading {
            color: #666;
            font-style: italic;
        }
        
        .endpoint-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .endpoint-list h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .method {
            font-weight: bold;
            color: #007bff;
        }
        
        .url {
            color: #333;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎥 Live Streaming API Test Interface</h1>
            <p>Test all streaming endpoints for the Connect App</p>
        </div>

        <!-- Authentication Section -->
        <div class="auth-section">
            <div id="auth-status">
                <h3>🔐 Authentication</h3>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" id="login-email" value="admin@test.com" placeholder="Email/Username">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="login-password" value="password123" placeholder="Password">
                </div>
                <button class="btn" onclick="login()">Login</button>
                <button class="btn btn-secondary" onclick="logout()">Logout</button>
                <div id="user-info"></div>
            </div>
        </div>

        <div class="grid">
            <!-- Stream Management -->
            <div class="card">
                <h3>📡 Stream Management (Admin)</h3>
                
                <div class="form-group">
                    <label>Stream Title:</label>
                    <input type="text" id="stream-title" value="Test Live Stream" placeholder="Stream Title">
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea id="stream-description" placeholder="Stream Description">This is a test live stream</textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="stream-paid"> Paid Stream
                    </label>
                </div>
                
                <div class="form-group" id="price-group" style="display: none;">
                    <label>Price ($):</label>
                    <input type="number" id="stream-price" value="10" step="0.01" placeholder="Price">
                </div>
                
                <button class="btn" onclick="createStream()">Create Stream</button>
                <button class="btn btn-secondary" onclick="loadMyStreams()">Load My Streams</button>
                
                <div id="my-streams"></div>
            </div>

            <!-- Stream Discovery -->
            <div class="card">
                <h3>🔍 Stream Discovery</h3>
                
                <button class="btn" onclick="loadLiveStreams()">Load Live Streams</button>
                <button class="btn btn-secondary" onclick="loadUpcomingStreams()">Load Upcoming</button>
                
                <div id="live-streams"></div>
                <div id="upcoming-streams"></div>
            </div>

            <!-- Stream Interaction -->
            <div class="card">
                <h3>🎬 Stream Interaction</h3>
                
                <div class="form-group">
                    <label>Stream ID:</label>
                    <input type="number" id="target-stream-id" placeholder="Enter Stream ID">
                </div>
                
                <button class="btn" onclick="joinStream()">Join Stream</button>
                <button class="btn btn-danger" onclick="leaveStream()">Leave Stream</button>
                <button class="btn btn-secondary" onclick="getStreamStatus()">Get Status</button>
                <button class="btn btn-secondary" onclick="getViewers()">Get Viewers</button>
                
                <div class="form-group">
                    <label>Chat Message:</label>
                    <input type="text" id="chat-message" placeholder="Type a message...">
                    <button class="btn" onclick="sendChatMessage()">Send Chat</button>
                </div>
                
                <button class="btn btn-secondary" onclick="loadChat()">Load Chat</button>
                
                <div id="current-stream-info"></div>
            </div>
        </div>

        <!-- API Response -->
        <div class="card">
            <h3>📡 API Response</h3>
            <div id="api-response" class="response">Ready to test API endpoints...</div>
        </div>

        <!-- Available Endpoints -->
        <div class="endpoint-list">
            <h3>🚀 Available Endpoints</h3>
            
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams</span> - Create stream
            </div>
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams/{id}/start</span> - Start stream
            </div>
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams/{id}/end</span> - End stream
            </div>
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams/{id}/join</span> - Join stream
            </div>
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams/{id}/leave</span> - Leave stream
            </div>
            <div class="endpoint">
                <span class="method">GET</span> <span class="url">/api/v1/streams/latest</span> - Get live streams
            </div>
            <div class="endpoint">
                <span class="method">GET</span> <span class="url">/api/v1/streams/upcoming</span> - Get upcoming streams
            </div>
            <div class="endpoint">
                <span class="method">GET</span> <span class="url">/api/v1/streams/my-streams</span> - Get admin's streams
            </div>
            <div class="endpoint">
                <span class="method">GET</span> <span class="url">/api/v1/streams/{id}/chat</span> - Get chat messages
            </div>
            <div class="endpoint">
                <span class="method">POST</span> <span class="url">/api/v1/streams/{id}/chat</span> - Send chat message
            </div>
        </div>
    </div>

    <script>
        let authToken = '';
        let currentUser = null;
        
        // Setup CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Show/hide paid stream options
        document.getElementById('stream-paid').addEventListener('change', function() {
            const priceGroup = document.getElementById('price-group');
            priceGroup.style.display = this.checked ? 'block' : 'none';
        });
        
        async function makeRequest(method, url, data = null) {
            const headers = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            };
            
            if (authToken) {
                headers['Authorization'] = `Bearer ${authToken}`;
            }
            
            const options = {
                method: method,
                headers: headers
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            try {
                showResponse('Loading...', 'loading');
                const response = await fetch(url, options);
                const result = await response.json();
                
                if (response.ok) {
                    showResponse(JSON.stringify(result, null, 2), 'success');
                    return result;
                } else {
                    showResponse(JSON.stringify(result, null, 2), 'error');
                    return null;
                }
            } catch (error) {
                showResponse('Network Error: ' + error.message, 'error');
                return null;
            }
        }
        
        function showResponse(message, type = '') {
            const responseDiv = document.getElementById('api-response');
            responseDiv.textContent = message;
            responseDiv.className = 'response ' + type;
        }
        
        function updateUserInfo() {
            const userInfoDiv = document.getElementById('user-info');
            if (currentUser) {
                userInfoDiv.innerHTML = `
                    <div class="user-info">
                        <strong>Logged in as:</strong> ${currentUser.username} (${currentUser.email})
                        <span class="badge badge-admin">ADMIN</span>
                    </div>
                `;
            } else {
                userInfoDiv.innerHTML = '<p>Not logged in</p>';
            }
        }
        
        async function login() {
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            const result = await makeRequest('POST', '/api/v1/login', {
                email: email,
                password: password
            });
            
            if (result && result.success) {
                authToken = result.data.token;
                currentUser = result.data.user;
                currentUser.is_admin = true; // For testing
                updateUserInfo();
            }
        }
        
        function logout() {
            authToken = '';
            currentUser = null;
            updateUserInfo();
            showResponse('Logged out', 'success');
        }
        
        async function createStream() {
            const data = {
                title: document.getElementById('stream-title').value,
                description: document.getElementById('stream-description').value,
                is_paid: document.getElementById('stream-paid').checked,
                currency: 'USD'
            };
            
            if (data.is_paid) {
                data.price = parseFloat(document.getElementById('stream-price').value);
            }
            
            await makeRequest('POST', '/api/v1/streams', data);
        }
        
        async function loadMyStreams() {
            const result = await makeRequest('GET', '/api/v1/streams/my-streams');
            
            if (result && result.success) {
                const myStreamsDiv = document.getElementById('my-streams');
                let html = '<h4>My Streams:</h4>';
                
                result.data.streams.forEach(stream => {
                    html += `
                        <div class="stream-item">
                            <h4>${stream.title}</h4>
                            <span class="badge badge-${stream.status}">${stream.status.toUpperCase()}</span>
                            <p>${stream.description}</p>
                            <div>
                                ${stream.status === 'upcoming' ? `<button class="btn btn-success" onclick="startStream(${stream.id})">Start</button>` : ''}
                                ${stream.status === 'live' ? `<button class="btn btn-danger" onclick="endStream(${stream.id})">End</button>` : ''}
                                ${stream.status !== 'live' ? `<button class="btn btn-danger" onclick="deleteStream(${stream.id})">Delete</button>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                myStreamsDiv.innerHTML = html;
            }
        }
        
        async function startStream(streamId) {
            await makeRequest('POST', `/api/v1/streams/${streamId}/start`);
            loadMyStreams();
        }
        
        async function endStream(streamId) {
            await makeRequest('POST', `/api/v1/streams/${streamId}/end`);
            loadMyStreams();
        }
        
        async function deleteStream(streamId) {
            if (confirm('Are you sure you want to delete this stream?')) {
                await makeRequest('DELETE', `/api/v1/streams/${streamId}`);
                loadMyStreams();
            }
        }
        
        async function loadLiveStreams() {
            const result = await makeRequest('GET', '/api/v1/streams/latest');
            
            if (result && result.success) {
                const liveStreamsDiv = document.getElementById('live-streams');
                let html = '<h4>Live Streams:</h4>';
                
                if (result.data.streams.length === 0) {
                    html += '<p>No live streams available</p>';
                } else {
                    result.data.streams.forEach(stream => {
                        html += `
                            <div class="stream-item">
                                <h4>${stream.title}</h4>
                                <span class="badge badge-live">LIVE</span>
                                <p>Viewers: ${stream.current_viewers}</p>
                                <p>Streamer: ${stream.streamer.name}</p>
                                <button class="btn" onclick="setStreamId(${stream.id})">Select Stream</button>
                            </div>
                        `;
                    });
                }
                
                liveStreamsDiv.innerHTML = html;
            }
        }
        
        async function loadUpcomingStreams() {
            const result = await makeRequest('GET', '/api/v1/streams/upcoming');
            
            if (result && result.success) {
                const upcomingStreamsDiv = document.getElementById('upcoming-streams');
                let html = '<h4>Upcoming Streams:</h4>';
                
                if (result.data.streams.length === 0) {
                    html += '<p>No upcoming streams</p>';
                } else {
                    result.data.streams.forEach(stream => {
                        html += `
                            <div class="stream-item">
                                <h4>${stream.title}</h4>
                                <span class="badge badge-upcoming">UPCOMING</span>
                                <p>Scheduled: ${new Date(stream.scheduled_at).toLocaleString()}</p>
                            </div>
                        `;
                    });
                }
                
                upcomingStreamsDiv.innerHTML = html;
            }
        }
        
        function setStreamId(streamId) {
            document.getElementById('target-stream-id').value = streamId;
            showResponse(`Stream ID ${streamId} selected`, 'success');
        }
        
        async function joinStream() {
            const streamId = document.getElementById('target-stream-id').value;
            if (!streamId) {
                showResponse('Please enter a stream ID', 'error');
                return;
            }
            
            const result = await makeRequest('POST', `/api/v1/streams/${streamId}/join`);
            
            if (result && result.success) {
                const infoDiv = document.getElementById('current-stream-info');
                infoDiv.innerHTML = `
                    <div class="user-info">
                        <strong>Joined Stream:</strong> ${result.data.stream.title}<br>
                        <strong>Agora UID:</strong> ${result.data.agora_config.agora_uid}<br>
                        <strong>Channel:</strong> ${result.data.agora_config.channel_name}
                    </div>
                `;
            }
        }
        
        async function leaveStream() {
            const streamId = document.getElementById('target-stream-id').value;
            if (!streamId) {
                showResponse('Please enter a stream ID', 'error');
                return;
            }
            
            await makeRequest('POST', `/api/v1/streams/${streamId}/leave`);
            document.getElementById('current-stream-info').innerHTML = '';
        }
        
        async function getStreamStatus() {
            const streamId = document.getElementById('target-stream-id').value;
            if (!streamId) {
                showResponse('Please enter a stream ID', 'error');
                return;
            }
            
            await makeRequest('GET', `/api/v1/streams/${streamId}/status`);
        }
        
        async function getViewers() {
            const streamId = document.getElementById('target-stream-id').value;
            if (!streamId) {
                showResponse('Please enter a stream ID', 'error');
                return;
            }
            
            await makeRequest('GET', `/api/v1/streams/${streamId}/viewers`);
        }
        
        async function sendChatMessage() {
            const streamId = document.getElementById('target-stream-id').value;
            const message = document.getElementById('chat-message').value;
            
            if (!streamId || !message) {
                showResponse('Please enter stream ID and message', 'error');
                return;
            }
            
            const result = await makeRequest('POST', `/api/v1/streams/${streamId}/chat`, {
                message: message
            });
            
            if (result && result.success) {
                document.getElementById('chat-message').value = '';
            }
        }
        
        async function loadChat() {
            const streamId = document.getElementById('target-stream-id').value;
            if (!streamId) {
                showResponse('Please enter a stream ID', 'error');
                return;
            }
            
            await makeRequest('GET', `/api/v1/streams/${streamId}/chat?limit=20`);
        }
        
        // Initialize
        updateUserInfo();
    </script>
</body>
</html>
