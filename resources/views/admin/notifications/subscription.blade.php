@extends('admin.layouts.app')

@section('title', 'Notification Subscription')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-lg-flex">
                        <div>
                            <h5 class="mb-0">Admin Notification Subscription</h5>
                            <p class="text-sm mb-0">
                                Manage your notification preferences and devices
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Current Subscription Status -->
                    <div class="row mb-4" x-data="notificationSubscription()">
                        <div class="col-12">
                            <div class="alert alert-info" x-show="!isSubscribed" x-cloak>
                                <div class="d-flex align-items-center">
                                    <i class="material-icons text-info me-2">info</i>
                                    <div>
                                        <strong>Not Subscribed</strong><br>
                                        Subscribe to receive push notifications on this device
                                    </div>
                                    <button class="btn btn-info ms-auto" @click="subscribe()" :disabled="subscribing">
                                        <span x-show="!subscribing">Subscribe</span>
                                        <span x-show="subscribing">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Subscribing...
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="alert alert-success" x-show="isSubscribed" x-cloak>
                                <div class="d-flex align-items-center">
                                    <i class="material-icons text-success me-2">check_circle</i>
                                    <div>
                                        <strong>Subscribed</strong><br>
                                        You will receive push notifications on this device
                                    </div>
                                    <button class="btn btn-outline-danger ms-auto" @click="unsubscribe()" :disabled="unsubscribing">
                                        <span x-show="!unsubscribing">Unsubscribe</span>
                                        <span x-show="unsubscribing">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Unsubscribing...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="row mb-4" x-data="notificationPreferences()">
                        <div class="col-12">
                            <h6 class="text-dark text-sm">Notification Preferences</h6>
                            <div class="card">
                                <div class="card-body">
                                    <form @submit.prevent="updatePreferences()">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.new_users" id="newUsers">
                                                    <label class="form-check-label" for="newUsers">
                                                        New User Registrations
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Get notified when new users register
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.new_stories" id="newStories">
                                                    <label class="form-check-label" for="newStories">
                                                        New Stories
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Get notified when users post new stories
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.verification_requests" id="verificationRequests">
                                                    <label class="form-check-label" for="verificationRequests">
                                                        Verification Requests
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Get notified about new verification requests
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.reported_content" id="reportedContent">
                                                    <label class="form-check-label" for="reportedContent">
                                                        Reported Content
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Get notified about reported content
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.system_alerts" id="systemAlerts">
                                                    <label class="form-check-label" for="systemAlerts">
                                                        System Alerts
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Get notified about system issues and alerts
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           x-model="preferences.test_notifications" id="testNotifications">
                                                    <label class="form-check-label" for="testNotifications">
                                                        Test Notifications
                                                    </label>
                                                    <small class="form-text text-muted d-block">
                                                        Receive test notifications
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                                <span x-show="!saving">Save Preferences</span>
                                                <span x-show="saving">
                                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                    Saving...
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registered Devices -->
                    <div class="row mb-4" x-data="deviceManagement()">
                        <div class="col-12">
                            <h6 class="text-dark text-sm">Registered Devices</h6>
                            <div class="card">
                                <div class="card-body">
                                    <div x-show="loading" class="text-center py-3">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>

                                    <div x-show="!loading && devices.length === 0" class="text-center py-3 text-muted">
                                        No devices registered for notifications
                                    </div>

                                    <div x-show="!loading && devices.length > 0">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Device</th>
                                                        <th>Platform</th>
                                                        <th>Browser</th>
                                                        <th>Last Used</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="device in devices" :key="device.id">
                                                        <tr>
                                                            <td>
                                                                <span x-text="device.device_name || 'Unknown Device'"></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary" x-text="device.platform"></span>
                                                            </td>
                                                            <td>
                                                                <span x-text="device.browser || 'N/A'"></span>
                                                            </td>
                                                            <td>
                                                                <span x-text="formatDate(device.last_used_at)"></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge"
                                                                      :class="device.is_active ? 'bg-success' : 'bg-danger'"
                                                                      x-text="device.is_active ? 'Active' : 'Inactive'">
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger"
                                                                        @click="deactivateDevice(device.id)"
                                                                        x-show="device.is_active">
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
                        </div>
                    </div>

                    <!-- Test Notifications -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-dark text-sm">Test Notifications</h6>
                            <div class="card">
                                <div class="card-body" x-data="testNotifications()">
                                    <form @submit.prevent="sendTest()">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-control-label">Test Title</label>
                                                    <input type="text" class="form-control"
                                                           x-model="testData.title"
                                                           placeholder="Enter test notification title">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-control-label">Test Message</label>
                                                    <input type="text" class="form-control"
                                                           x-model="testData.body"
                                                           placeholder="Enter test notification message">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-info" :disabled="sending">
                                                <span x-show="!sending">Send Test Notification</span>
                                                <span x-show="sending">
                                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                    Sending...
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Firebase configuration - move this to your main layout if needed
const firebaseConfig = {
    apiKey: "{{ config('services.firebase.api_key') }}",
    authDomain: "{{ config('services.firebase.auth_domain') }}",
    projectId: "{{ config('services.firebase.project_id') }}",
    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
    appId: "{{ config('services.firebase.app_id') }}"
};

// Initialize Firebase if not already done
if (typeof firebase === 'undefined' || !firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
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

        async checkSubscriptionStatus() {
            try {
                const messaging = firebase.messaging();
                const currentToken = await messaging.getToken({
                    vapidKey: "{{ config('services.firebase.vapid_key') }}"
                });

                if (currentToken) {
                    this.currentToken = currentToken;
                    this.isSubscribed = true;
                }
            } catch (error) {
                console.error('Error checking subscription status:', error);
            }
        },

        async subscribe() {
            this.subscribing = true;
            try {
                const messaging = firebase.messaging();

                // Request permission
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error('Notification permission denied');
                }

                // Get token
                const token = await messaging.getToken({
                    vapidKey: "{{ config('services.firebase.vapid_key') }}"
                });

                if (token) {
                    // Send token to server
                    const response = await fetch('/admin/api/notifications/admin-fcm/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            fcm_token: token,
                            device_name: navigator.userAgent,
                            platform: 'web',
                            browser: this.getBrowserName()
                        })
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
                console.error('Error subscribing:', error);
                this.showNotification('Failed to subscribe: ' + error.message, 'error');
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
            title: 'Test Admin Notification',
            body: 'This is a test notification for admin panel'
        },
        sending: false,

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
        }
    }
}
</script>
@endpush
