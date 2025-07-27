<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Live Stream</title>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.18.0.js"></script>
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
            display: flex;
            flex-direction: column;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status.connecting {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .status.live {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status.offline {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .main-content {
            flex: 1;
            display: flex;
            gap: 1rem;
            padding: 1rem;
        }

        .video-container {
            flex: 1;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-placeholder {
            color: white;
            text-align: center;
            font-size: 1.2rem;
        }

        .video-placeholder .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .sidebar {
            width: 300px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1rem;
            color: white;
        }

        .stream-info {
            margin-bottom: 2rem;
        }

        .stream-info h3 {
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .stream-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .viewer-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .viewer-count .number {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            display: block;
        }

        .viewer-count .label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: none;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #remote-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remote-video-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔴 Live Stream Viewer</h1>
        <div class="status connecting" id="status">Connecting...</div>
    </div>

    <div class="main-content">
        <div class="video-container" id="videoContainer">
            <div class="video-placeholder" id="placeholder">
                <div class="icon">📺</div>
                <div>Looking for live stream...</div>
            </div>
        </div>

        <div class="sidebar">
            <div class="stream-info">
                <h3 id="streamTitle">Loading stream info...</h3>
                <p id="streamDescription">Please wait while we find the active stream...</p>
            </div>

            <div class="controls">
                <button class="btn btn-primary" id="joinBtn" onclick="joinStream()" disabled>
                    Join Stream
                </button>
                <button class="btn btn-danger" id="leaveBtn" onclick="leaveStream()" style="display: none;">
                    Leave Stream
                </button>
            </div>

            <div class="viewer-count">
                <span class="number" id="viewerCount">0</span>
                <span class="label">Viewers</span>
            </div>

            <div class="error-message" id="errorMessage"></div>
        </div>
    </div>

    <script>
        // Configuration
        const userId = {{ $userId }};
        const baseUrl = '{{ url("/") }}';
        const apiUrl = baseUrl + '/api/v1';

        // Agora configuration
        let agoraClient = null;
        let currentStream = null;
        let isJoined = false;
        let remoteUsers = {};

        // DOM elements
        const statusEl = document.getElementById('status');
        const streamTitleEl = document.getElementById('streamTitle');
        const streamDescriptionEl = document.getElementById('streamDescription');
        const joinBtn = document.getElementById('joinBtn');
        const leaveBtn = document.getElementById('leaveBtn');
        const viewerCountEl = document.getElementById('viewerCount');
        const errorMessageEl = document.getElementById('errorMessage');
        const videoContainer = document.getElementById('videoContainer');
        const placeholder = document.getElementById('placeholder');

        // Initialize the application
        async function init() {
            console.log('Initializing stream viewer for user:', userId);

            // Initialize Agora client
            agoraClient = AgoraRTC.createClient({mode: "rtc", codec: "vp8"});

            // Set up event listeners
            agoraClient.on("user-published", handleUserPublished);
            agoraClient.on("user-unpublished", handleUserUnpublished);
            agoraClient.on("user-left", handleUserLeft);

            // Find active stream for this user
            await findActiveStream();

            // Update viewer count periodically
            setInterval(updateViewerCount, 5000);
        }

        async function findActiveStream() {
            try {
                updateStatus('connecting', 'Looking for live stream...');

                // Get the current base URL and construct the API endpoint
                const baseUrl = window.location.origin + window.location.pathname.replace(/\/watch\/.*$/, '');
                const apiUrl = baseUrl + '/api/streams/latest';

                console.log('Making API request to:', apiUrl);

                // Get latest streams and find one from this user that's live
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response. Check API endpoint.');
                }

                const data = await response.json();

                if (data.success && data.data && data.data.streams && data.data.streams.length > 0) {
                    // First, try to find stream from the specified user that's live
                    let userStream = data.data.streams.find(stream =>
                        stream.user_id == userId && stream.status === 'live'
                    );

                    // If no stream from the specific user, show any available live stream
                    if (!userStream) {
                        userStream = data.data.streams.find(stream => stream.status === 'live');
                    }

                    if (userStream) {
                        currentStream = userStream;
                        updateStreamInfo(userStream);

                        // Update status message based on whether it's the requested user's stream
                        if (userStream.user_id == userId) {
                            updateStatus('live', 'User Stream Found');
                        } else {
                            updateStatus('live', `Live Stream Available (by ${userStream.streamer?.name || userStream.streamer?.username || 'Streamer'})`);
                        }

                        joinBtn.disabled = false;
                        return;
                    }
                }

                // No live stream found at all
                updateStatus('offline', 'No Live Stream');
                updateStreamInfo({
                    title: 'No Active Stream',
                    description: `No live streams are currently available. Please check back later.`
                });

                // Retry after 10 seconds
                setTimeout(findActiveStream, 10000);

            } catch (error) {
                console.error('Error finding stream:', error);
                showError('Failed to find stream: ' + error.message);
                updateStatus('offline', 'Connection Error');

                // Retry after 10 seconds
                setTimeout(findActiveStream, 10000);
            }
        }

       async function joinStream() {
    if (!currentStream) {
        showError('No active stream found');
        return;
    }

    try {
        joinBtn.disabled = true;
        updateStatus('connecting', 'Joining stream...');

        const appId = '{{ config("services.agora.app_id") }}';
        let channelName = currentStream.channel_name;

        // Check if channel_name exists and is valid
        if (!channelName || typeof channelName !== 'string') {
            console.error('Invalid channel name:', channelName);
            console.log('Current stream object:', currentStream);
            throw new Error('Invalid or missing channel name');
        }

        // Clean the channel name to ensure it meets Agora requirements
        // Remove invalid characters and ensure it's within 64 bytes
        channelName = channelName.replace(/[^a-zA-Z0-9\s!#$%&()+\-:;<=>?@[\]^_{|}~,]/g, '');
        channelName = channelName.substring(0, 64);

        console.log('Agora App ID:', appId);
        console.log('Original Channel Name:', currentStream.channel_name);
        console.log('Cleaned Channel Name:', channelName);

        if (!appId) {
            throw new Error('Agora App ID is not configured');
        }

        if (!channelName || channelName.trim() === '') {
            throw new Error('Invalid channel name after cleaning');
        }

        // Generate a random UID for the viewer
        const viewerUid = Math.floor(Math.random() * 100000) + 200000;

        // Get token from server
        updateStatus('connecting', 'Getting access token...');
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/watch\/.*$/, '');
        const tokenUrl = baseUrl + '/api/streams/viewer-token';

        console.log('Requesting token from:', tokenUrl);

        const tokenResponse = await fetch(tokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                channel_name: channelName,
                uid: viewerUid
            })
        });

        if (!tokenResponse.ok) {
            throw new Error(`Token request failed: ${tokenResponse.status}`);
        }

        const tokenData = await tokenResponse.json();

        if (!tokenData.success) {
            throw new Error(tokenData.message || 'Failed to get token');
        }

        console.log('Token received successfully');

        // Join with the received token
        updateStatus('connecting', 'Connecting to stream...');
        await agoraClient.join(appId, channelName, tokenData.data.token, viewerUid);

        isJoined = true;
        updateStatus('live', 'Watching Live');
        joinBtn.style.display = 'none';
        leaveBtn.style.display = 'inline-block';

        placeholder.innerHTML = '<div class="loading">Waiting for stream video...</div>';

    } catch (error) {
        console.error('Error joining stream:', error);
        showError('Failed to join stream: ' + error.message);
        updateStatus('offline', 'Join Failed');
        joinBtn.disabled = false;
    }
}

        async function leaveStream() {
            try {
                if (isJoined) {
                    await agoraClient.leave();
                    isJoined = false;
                }

                // Clear remote videos
                Object.keys(remoteUsers).forEach(uid => {
                    const container = document.getElementById(`remote-${uid}`);
                    if (container) {
                        container.remove();
                    }
                });
                remoteUsers = {};

                // Reset UI
                updateStatus('offline', 'Disconnected');
                joinBtn.style.display = 'inline-block';
                joinBtn.disabled = false;
                leaveBtn.style.display = 'none';

                placeholder.innerHTML = '<div class="icon">📺</div><div>Disconnected from stream</div>';
                videoContainer.appendChild(placeholder);

            } catch (error) {
                console.error('Error leaving stream:', error);
                showError('Error leaving stream: ' + error.message);
            }
        }

        async function handleUserPublished(user, mediaType) {
            console.log('User published:', user.uid, mediaType);

            // Subscribe to the remote user
            await agoraClient.subscribe(user, mediaType);

            if (mediaType === 'video') {
                // Create video container
                const remoteVideoContainer = document.createElement('div');
                remoteVideoContainer.id = `remote-${user.uid}`;
                remoteVideoContainer.className = 'remote-video-container';

                // Hide placeholder
                if (placeholder.parentNode) {
                    placeholder.remove();
                }

                // Add video container
                videoContainer.appendChild(remoteVideoContainer);

                // Play the remote video
                user.videoTrack.play(remoteVideoContainer);

                remoteUsers[user.uid] = user;
            }

            if (mediaType === 'audio') {
                // Play the remote audio
                user.audioTrack.play();
            }
        }

        function handleUserUnpublished(user, mediaType) {
            console.log('User unpublished:', user.uid, mediaType);

            if (mediaType === 'video') {
                const container = document.getElementById(`remote-${user.uid}`);
                if (container) {
                    container.remove();
                }
                delete remoteUsers[user.uid];

                // Show placeholder if no more videos
                if (Object.keys(remoteUsers).length === 0) {
                    placeholder.innerHTML = '<div class="icon">📺</div><div>Stream ended or paused</div>';
                    videoContainer.appendChild(placeholder);
                }
            }
        }

        function handleUserLeft(user) {
            console.log('User left:', user.uid);

            const container = document.getElementById(`remote-${user.uid}`);
            if (container) {
                container.remove();
            }
            delete remoteUsers[user.uid];

            // Show placeholder if no more videos
            if (Object.keys(remoteUsers).length === 0) {
                placeholder.innerHTML = '<div class="icon">📺</div><div>Streamer has left</div>';
                videoContainer.appendChild(placeholder);
            }
        }

        function updateStatus(type, text) {
            statusEl.className = `status ${type}`;
            statusEl.textContent = text;
        }

        function updateStreamInfo(stream) {
            streamTitleEl.textContent = stream.title || 'Live Stream';
            streamDescriptionEl.textContent = stream.description || 'No description available';
        }

        async function updateViewerCount() {
            // Temporarily disable viewer count updates to avoid authentication issues
            // The core streaming functionality will work without this
            return;

            if (currentStream) {
                try {
                    // Use the web-based endpoint that bypasses API middleware
                    const baseUrl = window.location.origin + window.location.pathname.replace(/\/watch\/.*$/, '');
                    const viewersUrl = baseUrl + `/api/streams/${currentStream.id}/viewers`;

                    const response = await fetch(viewersUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const data = await response.json();
                            if (data.success && data.data) {
                                viewerCountEl.textContent = data.data.length || 0;
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error updating viewer count:', error);
                    // Don't show error for viewer count updates, just log it
                }
            }
        }

        function showError(message) {
            errorMessageEl.textContent = message;
            errorMessageEl.style.display = 'block';
            setTimeout(() => {
                errorMessageEl.style.display = 'none';
            }, 5000);
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', init);

        // Clean up when page unloads
        window.addEventListener('beforeunload', () => {
            if (isJoined) {
                agoraClient.leave();
            }
        });
    </script>
</body>
</html>
