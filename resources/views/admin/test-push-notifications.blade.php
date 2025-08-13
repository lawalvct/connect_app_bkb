<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Push Notification Test</title>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .test-section {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Admin Push Notification Test</h1>

        <!-- Status Display -->
        <div class="test-section">
            <h3>System Status</h3>
            <div id="status-display">
                <p><span id="firebase-status" class="badge bg-secondary">Checking...</span> Firebase Configuration</p>
                <p><span id="permission-status" class="badge bg-secondary">Checking...</span> Notification Permission</p>
                <p><span id="token-status" class="badge bg-secondary">Checking...</span> FCM Token</p>
                <p><span id="subscription-status" class="badge bg-secondary">Checking...</span> Server Subscription</p>
            </div>
        </div>

        <!-- Firebase Token -->
        <div class="test-section">
            <h3>Firebase Token</h3>
            <div class="form-group">
                <label>Current FCM Token:</label>
                <textarea id="fcm-token-display" class="form-control" rows="3" readonly placeholder="Token will appear here..."></textarea>
            </div>
            <button id="get-token-btn" class="btn btn-primary mt-2">Get/Refresh Token</button>
        </div>

        <!-- Subscription Management -->
        <div class="test-section">
            <h3>Subscription Management</h3>
            <div class="row">
                <div class="col-md-6">
                    <button id="subscribe-btn" class="btn btn-success w-100 mb-2">Subscribe to Notifications</button>
                </div>
                <div class="col-md-6">
                    <button id="unsubscribe-btn" class="btn btn-danger w-100 mb-2">Unsubscribe</button>
                </div>
            </div>
            <div id="subscription-result" class="mt-3"></div>
        </div>

        <!-- Test Notifications -->
        <div class="test-section">
            <h3>Test Notifications</h3>
            <form id="test-form">
                <div class="row">
                    <div class="col-md-6">
                        <label>Title:</label>
                        <input type="text" id="test-title" class="form-control" value="Test Admin Notification">
                    </div>
                    <div class="col-md-6">
                        <label>Message:</label>
                        <input type="text" id="test-message" class="form-control" value="This is a test notification for admin">
                    </div>
                </div>
                <button type="submit" class="btn btn-info mt-3">Send Test Notification</button>
            </form>
            <div id="test-result" class="mt-3"></div>
        </div>

        <!-- Device List -->
        <div class="test-section">
            <h3>Registered Devices</h3>
            <button id="load-devices-btn" class="btn btn-secondary mb-3">Load Devices</button>
            <div id="devices-list"></div>
        </div>

        <!-- Message Log -->
        <div class="test-section">
            <h3>Message Log</h3>
            <div id="message-log" style="background: #f8f9fa; padding: 15px; border-radius: 5px; height: 200px; overflow-y: scroll;">
                <p class="text-muted">Messages will appear here...</p>
            </div>
            <button id="clear-log-btn" class="btn btn-sm btn-outline-secondary mt-2">Clear Log</button>
        </div>
    </div>

    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "{{ config('services.firebase.api_key', 'YOUR_API_KEY') }}",
            authDomain: "{{ config('services.firebase.auth_domain', 'your-project.firebaseapp.com') }}",
            projectId: "{{ config('services.firebase.project_id', 'your-project-id') }}",
            storageBucket: "{{ config('services.firebase.storage_bucket', 'your-project.appspot.com') }}",
            messagingSenderId: "{{ config('services.firebase.messaging_sender_id', '123456789') }}",
            appId: "{{ config('services.firebase.app_id', 'your-app-id') }}"
        };

        const vapidKey = "{{ config('services.firebase.vapid_key', 'YOUR_VAPID_KEY') }}";

        let messaging;
        let currentToken = null;

        // Initialize Firebase
        function initFirebase() {
            try {
                firebase.initializeApp(firebaseConfig);
                messaging = firebase.messaging();

                updateStatus('firebase-status', 'bg-success', 'Configured');
                log('Firebase initialized successfully');

                return true;
            } catch (error) {
                updateStatus('firebase-status', 'bg-danger', 'Error');
                log('Firebase initialization error: ' + error.message, 'error');
                return false;
            }
        }

        // Update status badge
        function updateStatus(elementId, className, text) {
            const element = document.getElementById(elementId);
            element.className = 'badge ' + className;
            element.textContent = text;
        }

        // Log messages
        function log(message, type = 'info') {
            const logDiv = document.getElementById('message-log');
            const timestamp = new Date().toLocaleTimeString();
            const colorClass = type === 'error' ? 'text-danger' : (type === 'success' ? 'text-success' : 'text-dark');

            logDiv.innerHTML += `<p class="${colorClass}"><small>[${timestamp}]</small> ${message}</p>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Request notification permission
        async function requestPermission() {
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    updateStatus('permission-status', 'bg-success', 'Granted');
                    log('Notification permission granted');
                    return true;
                } else {
                    updateStatus('permission-status', 'bg-danger', 'Denied');
                    log('Notification permission denied', 'error');
                    return false;
                }
            } catch (error) {
                updateStatus('permission-status', 'bg-danger', 'Error');
                log('Permission request error: ' + error.message, 'error');
                return false;
            }
        }

        // Get FCM token
        async function getToken() {
            try {
                if (!messaging) {
                    log('Firebase messaging not initialized', 'error');
                    return null;
                }

                const permission = await requestPermission();
                if (!permission) return null;

                const token = await messaging.getToken({ vapidKey: vapidKey });

                if (token) {
                    currentToken = token;
                    document.getElementById('fcm-token-display').value = token;
                    updateStatus('token-status', 'bg-success', 'Generated');
                    log('FCM token generated successfully');
                    return token;
                } else {
                    updateStatus('token-status', 'bg-warning', 'No Token');
                    log('No FCM token available', 'warning');
                    return null;
                }
            } catch (error) {
                updateStatus('token-status', 'bg-danger', 'Error');
                log('Token generation error: ' + error.message, 'error');
                return null;
            }
        }

        // Subscribe to notifications
        async function subscribe() {
            try {
                if (!currentToken) {
                    await getToken();
                }

                if (!currentToken) {
                    throw new Error('No FCM token available');
                }

                const response = await fetch('/admin/api/notifications/admin-fcm/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        fcm_token: currentToken,
                        device_name: navigator.userAgent,
                        platform: 'web',
                        browser: getBrowserName()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    updateStatus('subscription-status', 'bg-success', 'Subscribed');
                    log('Successfully subscribed to notifications', 'success');
                    document.getElementById('subscription-result').innerHTML =
                        `<div class="alert alert-success">Subscribed successfully!</div>`;
                } else {
                    throw new Error(result.message || 'Subscription failed');
                }
            } catch (error) {
                updateStatus('subscription-status', 'bg-danger', 'Error');
                log('Subscription error: ' + error.message, 'error');
                document.getElementById('subscription-result').innerHTML =
                    `<div class="alert alert-danger">Subscription failed: ${error.message}</div>`;
            }
        }

        // Unsubscribe from notifications
        async function unsubscribe() {
            try {
                if (!currentToken) {
                    throw new Error('No FCM token to unsubscribe');
                }

                const response = await fetch('/admin/api/notifications/admin-fcm/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        fcm_token: currentToken
                    })
                });

                const result = await response.json();

                if (result.success) {
                    updateStatus('subscription-status', 'bg-warning', 'Unsubscribed');
                    log('Successfully unsubscribed from notifications', 'success');
                    document.getElementById('subscription-result').innerHTML =
                        `<div class="alert alert-warning">Unsubscribed successfully!</div>`;
                } else {
                    throw new Error(result.message || 'Unsubscription failed');
                }
            } catch (error) {
                log('Unsubscription error: ' + error.message, 'error');
                document.getElementById('subscription-result').innerHTML =
                    `<div class="alert alert-danger">Unsubscription failed: ${error.message}</div>`;
            }
        }

        // Send test notification
        async function sendTestNotification() {
            try {
                const title = document.getElementById('test-title').value;
                const body = document.getElementById('test-message').value;

                if (!title || !body) {
                    throw new Error('Please enter both title and message');
                }

                const response = await fetch('/admin/api/notifications/push/test-admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ title, body })
                });

                const result = await response.json();

                if (result.success) {
                    log(`Test notification sent! Sent: ${result.sent}, Failed: ${result.failed}`, 'success');
                    document.getElementById('test-result').innerHTML =
                        `<div class="alert alert-success">Test sent! Sent: ${result.sent}, Failed: ${result.failed}</div>`;
                } else {
                    throw new Error(result.message || 'Test failed');
                }
            } catch (error) {
                log('Test notification error: ' + error.message, 'error');
                document.getElementById('test-result').innerHTML =
                    `<div class="alert alert-danger">Test failed: ${error.message}</div>`;
            }
        }

        // Load registered devices
        async function loadDevices() {
            try {
                const response = await fetch('/admin/api/notifications/admin-fcm/tokens');
                const result = await response.json();

                if (result.success) {
                    const devices = result.tokens;
                    let html = '';

                    if (devices.length === 0) {
                        html = '<p class="text-muted">No devices registered</p>';
                    } else {
                        html = '<div class="table-responsive"><table class="table table-sm">';
                        html += '<thead><tr><th>Device</th><th>Platform</th><th>Browser</th><th>Status</th><th>Last Used</th></tr></thead><tbody>';

                        devices.forEach(device => {
                            const status = device.is_active ?
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-danger">Inactive</span>';

                            html += `<tr>
                                <td>${device.device_name || 'Unknown'}</td>
                                <td>${device.platform || 'N/A'}</td>
                                <td>${device.browser || 'N/A'}</td>
                                <td>${status}</td>
                                <td>${device.last_used_at ? new Date(device.last_used_at).toLocaleString() : 'Never'}</td>
                            </tr>`;
                        });

                        html += '</tbody></table></div>';
                    }

                    document.getElementById('devices-list').innerHTML = html;
                    log(`Loaded ${devices.length} devices`, 'success');
                } else {
                    throw new Error(result.message || 'Failed to load devices');
                }
            } catch (error) {
                log('Load devices error: ' + error.message, 'error');
                document.getElementById('devices-list').innerHTML =
                    `<div class="alert alert-danger">Failed to load devices: ${error.message}</div>`;
            }
        }

        // Get browser name
        function getBrowserName() {
            const userAgent = navigator.userAgent;
            if (userAgent.includes('Chrome')) return 'Chrome';
            if (userAgent.includes('Firefox')) return 'Firefox';
            if (userAgent.includes('Safari')) return 'Safari';
            if (userAgent.includes('Edge')) return 'Edge';
            return 'Unknown';
        }

        // Setup message listener
        function setupMessageListener() {
            if (messaging) {
                messaging.onMessage((payload) => {
                    log('Foreground message received: ' + payload.notification.title, 'success');

                    // Show browser notification
                    if (Notification.permission === 'granted') {
                        new Notification(payload.notification.title, {
                            body: payload.notification.body,
                            icon: '/admin-assets/img/logo.png'
                        });
                    }
                });
            }
        }

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Firebase
            if (initFirebase()) {
                setupMessageListener();

                // Auto-check permission
                if (Notification.permission === 'granted') {
                    updateStatus('permission-status', 'bg-success', 'Granted');
                } else if (Notification.permission === 'denied') {
                    updateStatus('permission-status', 'bg-danger', 'Denied');
                } else {
                    updateStatus('permission-status', 'bg-warning', 'Default');
                }
            }

            // Event listeners
            document.getElementById('get-token-btn').addEventListener('click', getToken);
            document.getElementById('subscribe-btn').addEventListener('click', subscribe);
            document.getElementById('unsubscribe-btn').addEventListener('click', unsubscribe);
            document.getElementById('load-devices-btn').addEventListener('click', loadDevices);
            document.getElementById('clear-log-btn').addEventListener('click', function() {
                document.getElementById('message-log').innerHTML = '<p class="text-muted">Messages will appear here...</p>';
            });

            document.getElementById('test-form').addEventListener('submit', function(e) {
                e.preventDefault();
                sendTestNotification();
            });
        });
    </script>
</body>
</html>
