@extends('admin.layouts.app')

@section('title', 'Notification Subscription')
@section('page-title', 'Notification Subscription')

@section('content')
<div x-data="{ subscriptionData: null, devicesData: null }" x-init="loadInitialData()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Subscription Status & Management -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Current Subscription Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Subscription Status</h3>
                    <p class="text-sm text-gray-500 mt-1">Manage your push notification subscription</p>
                </div>

                <div class="p-6" x-data="notificationSubscription()">
                    <!-- Not Subscribed -->
                    <div x-show="!isSubscribed" x-cloak class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                <div>
                                    <h4 class="text-sm font-medium text-blue-900">Not Subscribed</h4>
                                    <p class="text-sm text-blue-700">Subscribe to receive push notifications on this device</p>
                                </div>
                            </div>
                            <button @click="subscribe()"
                                    :disabled="subscribing"
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                <span x-show="!subscribing">Subscribe</span>
                                <span x-show="subscribing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Subscribing...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Subscribed -->
                    <div x-show="isSubscribed" x-cloak class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <div>
                                    <h4 class="text-sm font-medium text-green-900">Subscribed</h4>
                                    <p class="text-sm text-green-700">You will receive push notifications on this device</p>
                                </div>
                            </div>
                            <button @click="unsubscribe()"
                                    :disabled="unsubscribing"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                <span x-show="!unsubscribing">Unsubscribe</span>
                                <span x-show="unsubscribing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Unsubscribing...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Notification Preferences</h3>
                    <p class="text-sm text-gray-500 mt-1">Choose which types of notifications you want to receive</p>
                </div>

                <div class="p-6" x-data="notificationPreferences()">
                    <form @submit.prevent="updatePreferences()" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- New User Registrations -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.new_users"
                                           id="newUsers"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="newUsers" class="font-medium text-gray-900">New User Registrations</label>
                                    <p class="text-gray-500">Get notified when new users register</p>
                                </div>
                            </div>

                            <!-- New Stories -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.new_stories"
                                           id="newStories"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="newStories" class="font-medium text-gray-900">New Stories</label>
                                    <p class="text-gray-500">Get notified when users post new stories</p>
                                </div>
                            </div>

                            <!-- Verification Requests -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.verification_requests"
                                           id="verificationRequests"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="verificationRequests" class="font-medium text-gray-900">Verification Requests</label>
                                    <p class="text-gray-500">Get notified about new verification requests</p>
                                </div>
                            </div>

                            <!-- Reported Content -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.reported_content"
                                           id="reportedContent"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="reportedContent" class="font-medium text-gray-900">Reported Content</label>
                                    <p class="text-gray-500">Get notified about reported content</p>
                                </div>
                            </div>

                            <!-- System Alerts -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.system_alerts"
                                           id="systemAlerts"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="systemAlerts" class="font-medium text-gray-900">System Alerts</label>
                                    <p class="text-gray-500">Get notified about system issues and alerts</p>
                                </div>
                            </div>

                            <!-- Test Notifications -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           x-model="preferences.test_notifications"
                                           id="testNotifications"
                                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                                </div>
                                <div class="text-sm">
                                    <label for="testNotifications" class="font-medium text-gray-900">Test Notifications</label>
                                    <p class="text-gray-500">Receive test notifications</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit"
                                    :disabled="saving"
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                <span x-show="!saving">Save Preferences</span>
                                <span x-show="saving" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Test Notifications -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Test Notifications</h3>
                    <p class="text-sm text-gray-500 mt-1">Send a test notification to verify your setup</p>
                </div>

                <div class="p-6" x-data="testNotifications()">
                    <form @submit.prevent="sendTest()" class="space-y-4">
                        <div>
                            <label for="testTitle" class="block text-sm font-medium text-gray-700 mb-2">Test Title</label>
                            <input type="text"
                                   id="testTitle"
                                   x-model="testData.title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter test notification title">
                        </div>

                        <div>
                            <label for="testMessage" class="block text-sm font-medium text-gray-700 mb-2">Test Message</label>
                            <textarea id="testMessage"
                                      x-model="testData.body"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                      placeholder="Enter test notification message"></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                    :disabled="sending"
                                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                                <span x-show="!sending">Send Test (Server)</span>
                                <span x-show="sending" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </button>

                            <button type="button"
                                    @click="sendClientTest()"
                                    :disabled="sendingClient"
                                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                                <span x-show="!sendingClient">Send Test (Browser)</span>
                                <span x-show="sendingClient" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </button>
                        </div>

                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mr-2 mt-0.5"></i>
                                <div class="text-sm text-blue-700">
                                    <p><strong>Server Test:</strong> Uses Laravel backend + FCM API (may fail if FCM Legacy API is disabled)</p>
                                    <p class="mt-1"><strong>Browser Test:</strong> Uses browser's native notification API (works locally)</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Stats</h3>
                </div>

                <div class="p-6 space-y-4" x-data="{ stats: { devices: 0, lastNotification: null } }" x-init="loadStats()">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Registered Devices</span>
                        <span class="text-lg font-semibold text-gray-900" x-text="stats.devices"></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="px-2 py-1 text-xs rounded-full"
                              :class="stats.devices > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                              x-text="stats.devices > 0 ? 'Active' : 'Inactive'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registered Devices Table -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Registered Devices</h3>
                    <p class="text-sm text-gray-500 mt-1">Manage devices that can receive notifications</p>
                </div>
                <button @click="loadDevices()"
                        class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <div class="p-6" x-data="deviceManagement()">
            <div x-show="loading" class="flex justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div x-show="!loading && devices.length === 0" class="text-center py-8 text-gray-500">
                <i class="fas fa-mobile-alt text-4xl mb-4"></i>
                <p class="text-lg font-medium">No devices registered</p>
                <p class="text-sm">Subscribe to notifications to register this device</p>
            </div>

            <div x-show="!loading && devices.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Browser</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="device in devices" :key="device.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="device.device_name || 'Unknown Device'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full" x-text="device.platform"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="device.browser || 'N/A'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(device.last_used_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="device.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="device.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="deactivateDevice(device.id)"
                                            x-show="device.is_active"
                                            class="text-red-600 hover:text-red-900">
                                        Deactivate
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

<script>
// Debug: Log configuration values
console.log('Firebase Configuration Debug:');
console.log('API Key:', "{{ config('services.firebase.api_key') }}");
console.log('Project ID:', "{{ config('services.firebase.project_id') }}");
console.log('Auth Domain:', "{{ config('services.firebase.auth_domain') }}");

// Debug: Check admin authentication
@auth('admin')
console.log('✅ Admin authenticated:', "{{ auth('admin')->user()->email }}");
@else
console.error('❌ Admin NOT authenticated - subscription will fail!');
console.log('🔑 Please login at: http://localhost:8000/admin/login');
@endauth

// Firebase configuration
const firebaseConfig = {
    apiKey: "{{ config('services.firebase.api_key') }}",
    authDomain: "{{ config('services.firebase.auth_domain') }}",
    projectId: "{{ config('services.firebase.project_id') }}",
    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
    appId: "{{ config('services.firebase.app_id') }}"
};

// Debug: Log final config object
console.log('Final Firebase Config:', firebaseConfig);

// Check for empty values
const emptyKeys = Object.keys(firebaseConfig).filter(key => !firebaseConfig[key] || firebaseConfig[key] === '');
if (emptyKeys.length > 0) {
    console.error('Missing Firebase configuration keys:', emptyKeys);
    alert('Firebase configuration incomplete. Missing keys: ' + emptyKeys.join(', ') + '. Please check your .env file.');
}

// Initialize Firebase
if (!firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
}

// Initialize messaging and register service worker
let messaging;
try {
    messaging = firebase.messaging();

    // Register service worker for messaging
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/firebase-messaging-sw.js')
            .then((registration) => {
                console.log('Service Worker registered successfully:', registration);
                messaging.useServiceWorker(registration);
            })
            .catch((error) => {
                console.error('Service Worker registration failed:', error);
            });
    }
} catch (error) {
    console.error('Failed to initialize Firebase messaging:', error);
}

// Alpine.js components
function notificationSubscription() {
    return {
        isSubscribed: false,
        subscribing: false,
        unsubscribing: false,
        currentToken: null,

        init() {
            this.checkSubscriptionStatus();
        },

        // Helper function to convert VAPID key
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },

        async checkSubscriptionStatus() {
            try {
                if (!messaging) {
                    console.warn('Firebase messaging not initialized');
                    return;
                }

                const currentToken = await messaging.getToken({
                    vapidKey: "{{ config('services.firebase.vapid_key') }}"
                });

                if (currentToken) {
                    this.currentToken = currentToken;
                    this.isSubscribed = true;
                    console.log('Current FCM token:', currentToken);
                } else {
                    console.log('No FCM token available');
                }
            } catch (error) {
                console.error('Error checking subscription status:', error);
            }
        },

        async subscribe() {
            this.subscribing = true;
            try {
                if (!messaging) {
                    throw new Error('Firebase messaging not initialized');
                }

                // Request permission first
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error('Notification permission denied');
                }

                // Get token with VAPID key
                console.log('Attempting to get FCM token with VAPID key...');
                const vapidKey = "{{ config('services.firebase.vapid_key') }}";
                console.log('VAPID key (first 20 chars):', vapidKey.substring(0, 20) + '...');

                let token;
                try {
                    // Try with VAPID key first
                    token = await messaging.getToken({
                        vapidKey: vapidKey
                    });
                } catch (vapidError) {
                    console.warn('Failed with VAPID key, trying without:', vapidError.message);
                    // If VAPID key fails, try without it for testing
                    try {
                        token = await messaging.getToken();
                    } catch (noVapidError) {
                        throw new Error('Failed to get token both with and without VAPID key: ' + noVapidError.message);
                    }
                }

                if (token) {
                    console.log('FCM token obtained:', token);

                    // Also get Web Push subscription for server-side push
                    let webPushSubscription = null;
                    try {
                        if ('serviceWorker' in navigator && 'PushManager' in window) {
                            const registration = await navigator.serviceWorker.ready;
                            const vapidPublicKey = "{{ config('services.vapid.public_key') }}";

                            if (vapidPublicKey && vapidPublicKey !== 'your-vapid-public-key-here') {
                                webPushSubscription = await registration.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                                });
                                console.log('Web Push subscription obtained');
                            }
                        }
                    } catch (webPushError) {
                        console.warn('Failed to get Web Push subscription:', webPushError.message);
                        // Continue without Web Push - FCM might still work
                    }

                    // Send token and web push subscription to server
                    const subscriptionData = {
                        fcm_token: token,
                        device_name: navigator.userAgent,
                        platform: 'web',
                        browser: this.getBrowserName()
                    };

                    // Add Web Push data if available
                    if (webPushSubscription) {
                        subscriptionData.push_endpoint = webPushSubscription.endpoint;
                        subscriptionData.push_p256dh = btoa(String.fromCharCode.apply(null, new Uint8Array(webPushSubscription.getKey('p256dh'))));
                        subscriptionData.push_auth = btoa(String.fromCharCode.apply(null, new Uint8Array(webPushSubscription.getKey('auth'))));
                        subscriptionData.subscription_type = 'both'; // FCM + Web Push
                    } else {
                        subscriptionData.subscription_type = 'fcm'; // FCM only
                    }

                    const response = await fetch('/admin/api/notifications/admin-fcm/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(subscriptionData)
                    });

                    const result = await response.json();
                    if (result.success) {
                        this.isSubscribed = true;
                        this.currentToken = token;
                        this.showNotification('Successfully subscribed to notifications!', 'success');
                    } else {
                        throw new Error(result.message);
                    }
                }
            } catch (error) {
                console.error('Subscription error:', error);

                // Provide specific guidance based on error type
                let errorMessage = 'Failed to subscribe: ' + error.message;

                if (error.message.includes('authentication credential')) {
                    errorMessage += '\n\n🔑 SOLUTION: You need to get the correct VAPID key from Firebase Console:\n';
                    errorMessage += '1. Go to: https://console.firebase.google.com/project/connect-app-fbaca\n';
                    errorMessage += '2. Project Settings → Cloud Messaging → Web Push certificates\n';
                    errorMessage += '3. Generate key pair or copy existing key\n';
                    errorMessage += '4. Update FIREBASE_VAPID_KEY in your .env file\n';
                    errorMessage += '5. Run: php artisan config:clear';
                } else if (error.message.includes('permission')) {
                    errorMessage += '\n\n🔔 Please allow notifications when prompted by your browser.';
                } else if (error.message.includes('not initialized')) {
                    errorMessage += '\n\n⚙️ Firebase messaging failed to initialize. Check browser console for details.';
                }

                this.showNotification(errorMessage, 'error');
            }
            this.subscribing = false;
        },

        async unsubscribe() {
            this.unsubscribing = true;
            try {
                if (this.currentToken) {
                    const response = await fetch('/admin/api/notifications/admin-fcm/unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            fcm_token: this.currentToken
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        this.isSubscribed = false;
                        this.currentToken = null;
                        this.showNotification('Successfully unsubscribed from notifications!', 'success');
                    } else {
                        throw new Error(result.message);
                    }
                }
            } catch (error) {
                console.error('Error unsubscribing:', error);
                this.showNotification('Failed to unsubscribe: ' + error.message, 'error');
            }
            this.unsubscribing = false;
        },

        getBrowserName() {
            const userAgent = navigator.userAgent;
            if (userAgent.includes('Chrome')) return 'Chrome';
            if (userAgent.includes('Firefox')) return 'Firefox';
            if (userAgent.includes('Safari')) return 'Safari';
            if (userAgent.includes('Edge')) return 'Edge';
            return 'Unknown';
        },

        showNotification(message, type) {
            // Implementation depends on your notification system
            alert(message);
        }
    }
}

function notificationPreferences() {
    return {
        preferences: {
            new_users: true,
            new_stories: true,
            verification_requests: true,
            reported_content: true,
            system_alerts: true,
            test_notifications: true
        },
        saving: false,
        currentTokenId: null,

        init() {
            this.loadPreferences();
        },

        async loadPreferences() {
            try {
                const response = await fetch('/admin/api/notifications/admin-fcm/tokens');
                const result = await response.json();

                if (result.success && result.tokens.length > 0) {
                    // Use the first active token's preferences
                    const activeToken = result.tokens.find(t => t.is_active);
                    if (activeToken && activeToken.notification_preferences) {
                        this.preferences = { ...this.preferences, ...activeToken.notification_preferences };
                        this.currentTokenId = activeToken.id;
                    }
                }
            } catch (error) {
                console.error('Error loading preferences:', error);
            }
        },

        async updatePreferences() {
            if (!this.currentTokenId) {
                alert('No active subscription found. Please subscribe first.');
                return;
            }

            this.saving = true;
            try {
                const response = await fetch('/admin/api/notifications/admin-fcm/preferences', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        token_id: this.currentTokenId,
                        preferences: this.preferences
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Preferences updated successfully!');
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error updating preferences:', error);
                alert('Failed to update preferences: ' + error.message);
            }
            this.saving = false;
        }
    }
}

function deviceManagement() {
    return {
        devices: [],
        loading: true,

        init() {
            this.loadDevices();
        },

        async loadDevices() {
            this.loading = true;
            try {
                const response = await fetch('/admin/api/notifications/admin-fcm/tokens');
                const result = await response.json();

                if (result.success) {
                    this.devices = result.tokens;
                }
            } catch (error) {
                console.error('Error loading devices:', error);
            }
            this.loading = false;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleString();
        },

        async deactivateDevice(deviceId) {
            if (!confirm('Are you sure you want to deactivate this device?')) {
                return;
            }

            try {
                const device = this.devices.find(d => d.id === deviceId);
                if (device) {
                    const response = await fetch('/admin/api/notifications/admin-fcm/unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            fcm_token: device.fcm_token
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        await this.loadDevices();
                        alert('Device deactivated successfully!');
                    } else {
                        throw new Error(result.message);
                    }
                }
            } catch (error) {
                console.error('Error deactivating device:', error);
                alert('Failed to deactivate device: ' + error.message);
            }
        }
    }
}

function testNotifications() {

    return {
        testData: {
            title: 'Test Admin Notification333',
            body: 'This is a test notification for admin panel'
        },
        sending: false,
        sendingClient: false,

        async sendTest() {
            if (!this.testData.title || !this.testData.body) {
                alert('Please enter both title and message');
                return;
            }

            this.sending = true;
            try {
                const response = await fetch('/admin/api/notifications/push/test-admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.testData)
                });

                const result = await response.json();
                if (result.success) {
                    alert(`Test notification sent! Sent: ${result.sent}, Failed: ${result.failed}`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error sending test:', error);
                alert('Failed to send test notification: ' + error.message);
            }
            this.sending = false;
        },

        async sendClientTest() {
            if (!this.testData.title || !this.testData.body) {
                alert('Please enter both title and message');
                return;
            }

            this.sendingClient = true;
            try {
                // Check if notifications are supported
                if (!('Notification' in window)) {
                    throw new Error('This browser does not support notifications');
                }

                // Request permission if needed
                let permission = Notification.permission;
                if (permission === 'default') {
                    permission = await Notification.requestPermission();
                }

                if (permission === 'granted') {
                    // Create a browser notification
                    const notification = new Notification(this.testData.title, {
                        body: this.testData.body,
                        icon: '/favicon.ico',
                        badge: '/favicon.ico',
                        tag: 'admin-test-' + Date.now(),
                        requireInteraction: false
                    });

                    // Auto-close after 5 seconds
                    setTimeout(() => notification.close(), 5000);

                    alert('✅ Browser notification sent successfully! Check your notifications.');
                } else {
                    throw new Error('Notification permission was denied');
                }
            } catch (error) {
                console.error('Error sending client test:', error);
                alert('Failed to send browser notification: ' + error.message);
            }
            this.sendingClient = false;
        }
    }
}
</script>
@endpush
