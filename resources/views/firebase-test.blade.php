<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Firebase Test - ConnectApp Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#A20030',
                        'primary-light': '#A200302B',
                        'background': '#FAFAFA'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Firebase Configuration Test</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Configuration Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Configuration Status</h2>

                <div id="config-status" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Firebase SDK Loaded</span>
                        <span id="firebase-status" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Checking...</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Messaging Available</span>
                        <span id="messaging-status" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Checking...</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Service Worker</span>
                        <span id="sw-status" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Checking...</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Permission</span>
                        <span id="permission-status" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Checking...</span>
                    </div>
                </div>
            </div>

            <!-- Test Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Test Actions</h2>

                <div class="space-y-3">
                    <button id="request-permission"
                            class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed">
                        Request Permission
                    </button>

                    <button id="get-token"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Get FCM Token
                    </button>

                    <button id="register-sw"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Register Service Worker
                    </button>
                </div>
            </div>
        </div>

        <!-- Configuration Details -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Configuration Details</h2>
            <div class="bg-gray-50 rounded-lg p-4">
                <pre id="config-details" class="text-sm text-gray-700 whitespace-pre-wrap"></pre>
            </div>
        </div>

        <!-- Token Display -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6" style="display: none;" id="token-section">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">FCM Token</h2>
            <div class="bg-gray-50 rounded-lg p-4">
                <pre id="token-display" class="text-sm text-gray-700 whitespace-pre-wrap break-all"></pre>
            </div>
            <button id="copy-token"
                    class="mt-3 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Copy Token
            </button>
        </div>

        <!-- Console Log -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Console Log</h2>
            <div id="console-log" class="bg-gray-900 text-green-400 rounded-lg p-4 h-64 overflow-y-auto font-mono text-sm">
                <div>Firebase Test Console - Ready</div>
            </div>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

    <script>
        // Console logger
        function log(message, type = 'info') {
            const console_log = document.getElementById('console-log');
            const timestamp = new Date().toLocaleTimeString();
            const colorClass = type === 'error' ? 'text-red-400' : type === 'success' ? 'text-green-400' : 'text-blue-400';
            console_log.innerHTML += `<div class="${colorClass}">[${timestamp}] ${message}</div>`;
            console_log.scrollTop = console_log.scrollHeight;
            console.log(`[Firebase Test] ${message}`);
        }

        // Status updater
        function updateStatus(id, status, type = 'success') {
            const element = document.getElementById(id);
            const classes = type === 'success' ? 'bg-green-100 text-green-800' :
                           type === 'error' ? 'bg-red-100 text-red-800' :
                           'bg-yellow-100 text-yellow-800';
            element.className = `px-2 py-1 text-xs rounded-full ${classes}`;
            element.textContent = status;
        }

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "{{ config('services.firebase.api_key') }}",
            authDomain: "{{ config('services.firebase.auth_domain') }}",
            projectId: "{{ config('services.firebase.project_id') }}",
            storageBucket: "{{ config('services.firebase.storage_bucket') }}",
            messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
            appId: "{{ config('services.firebase.app_id') }}"
        };

        // Display configuration
        document.getElementById('config-details').textContent = JSON.stringify(firebaseConfig, null, 2);

        // Check Firebase availability
        if (typeof firebase !== 'undefined') {
            log('Firebase SDK loaded successfully', 'success');
            updateStatus('firebase-status', 'Loaded', 'success');

            // Initialize Firebase
            try {
                firebase.initializeApp(firebaseConfig);
                log('Firebase initialized successfully', 'success');
            } catch (error) {
                log('Firebase initialization error: ' + error.message, 'error');
                updateStatus('firebase-status', 'Error', 'error');
            }

            // Check messaging
            try {
                const messaging = firebase.messaging();
                log('Firebase Messaging available', 'success');
                updateStatus('messaging-status', 'Available', 'success');
            } catch (error) {
                log('Firebase Messaging error: ' + error.message, 'error');
                updateStatus('messaging-status', 'Error', 'error');
            }
        } else {
            log('Firebase SDK not loaded', 'error');
            updateStatus('firebase-status', 'Not Loaded', 'error');
        }

        // Check service worker support
        if ('serviceWorker' in navigator) {
            log('Service Worker supported', 'success');
            updateStatus('sw-status', 'Supported', 'success');
        } else {
            log('Service Worker not supported', 'error');
            updateStatus('sw-status', 'Not Supported', 'error');
        }

        // Check notification permission
        if ('Notification' in window) {
            const permission = Notification.permission;
            log(`Notification permission: ${permission}`);
            updateStatus('permission-status', permission, permission === 'granted' ? 'success' : 'warning');
        }

        // Request permission button
        document.getElementById('request-permission').addEventListener('click', async function() {
            try {
                const permission = await Notification.requestPermission();
                log(`Permission result: ${permission}`, permission === 'granted' ? 'success' : 'error');
                updateStatus('permission-status', permission, permission === 'granted' ? 'success' : 'error');
            } catch (error) {
                log('Permission request error: ' + error.message, 'error');
            }
        });

        // Get token button
        document.getElementById('get-token').addEventListener('click', async function() {
            if (typeof firebase === 'undefined') {
                log('Firebase not available', 'error');
                return;
            }

            try {
                const messaging = firebase.messaging();
                const token = await messaging.getToken({
                    vapidKey: "{{ config('services.firebase.vapid_key') }}"
                });

                if (token) {
                    log('FCM Token received successfully', 'success');
                    document.getElementById('token-display').textContent = token;
                    document.getElementById('token-section').style.display = 'block';
                } else {
                    log('No registration token available', 'error');
                }
            } catch (error) {
                log('Token retrieval error: ' + error.message, 'error');
            }
        });

        // Register service worker button
        document.getElementById('register-sw').addEventListener('click', async function() {
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                    log('Service Worker registered successfully', 'success');
                    updateStatus('sw-status', 'Registered', 'success');
                } catch (error) {
                    log('Service Worker registration error: ' + error.message, 'error');
                    updateStatus('sw-status', 'Error', 'error');
                }
            }
        });

        // Copy token button
        document.getElementById('copy-token').addEventListener('click', function() {
            const token = document.getElementById('token-display').textContent;
            navigator.clipboard.writeText(token).then(function() {
                log('Token copied to clipboard', 'success');
            });
        });
    </script>
</body>
</html>
