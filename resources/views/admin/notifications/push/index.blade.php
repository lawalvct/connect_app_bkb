@extends('admin.layouts.app')

@section('title', 'Push Notifications')
@section('page-title', 'Push Notifications')

@section('content')
<div x-data="pushNotifications()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Send Notification Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Send Push Notification</h3>
                    <p class="text-sm text-gray-500 mt-1">Send notifications to users via Firebase FCM</p>
                </div>

                <form @submit.prevent="sendNotification()" class="p-6 space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Notification Title *
                        </label>
                        <input type="text"
                               id="title"
                               x-model="form.title"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter notification title"
                               required>
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700 mb-2">
                            Message Body *
                        </label>
                        <textarea id="body"
                                  x-model="form.body"
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="Enter notification message"
                                  required></textarea>
                        <div class="text-xs text-gray-500 mt-1">
                            Characters: <span x-text="form.body.length"></span>/500
                        </div>
                    </div>

                    <!-- Target Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Send To *
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio"
                                       x-model="form.target_type"
                                       value="all"
                                       class="text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">All Users</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio"
                                       x-model="form.target_type"
                                       value="users"
                                       class="text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Specific Users</span>
                            </label>
                        </div>
                    </div>

                    <!-- User Selection (if specific users) -->
                    <div x-show="form.target_type === 'users'" class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">
                            Select Users
                        </label>

                        <!-- Search Users -->
                        <div class="relative">
                            <input type="text"
                                   x-model="userSearch"
                                   @input="searchUsers()"
                                   class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Search users by name or email">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>

                        <!-- User Results -->
                        <div x-show="searchResults.length > 0" class="border border-gray-200 rounded-lg max-h-40 overflow-y-auto">
                            <template x-for="user in searchResults" :key="user.id">
                                <div class="flex items-center justify-between p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            <span x-text="user.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900" x-text="user.name"></p>
                                            <p class="text-xs text-gray-500" x-text="user.email"></p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="toggleUser(user)"
                                            class="px-3 py-1 text-xs rounded-full"
                                            :class="isUserSelected(user.id) ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'">
                                        <span x-text="isUserSelected(user.id) ? 'Selected' : 'Select'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Selected Users -->
                        <div x-show="form.user_ids.length > 0" class="mt-3">
                            <p class="text-sm font-medium text-gray-700 mb-2">Selected Users (<span x-text="form.user_ids.length"></span>)</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="userId in form.user_ids" :key="userId">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-primary-light text-primary">
                                        <span x-text="getSelectedUserName(userId)"></span>
                                        <button type="button"
                                                @click="removeUser(userId)"
                                                class="ml-1 text-primary hover:text-red-500">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Data -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Data (Optional)
                        </label>
                        <textarea x-model="additionalDataText"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder='{"action": "open_page", "page": "profile"}'></textarea>
                        <p class="text-xs text-gray-500 mt-1">JSON format for additional payload data</p>
                    </div>

                    <!-- Send Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="sending"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!sending">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Notification
                            </span>
                            <span x-show="sending">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Sending...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics & Recent Activity -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Users</span>
                        <span class="text-lg font-semibold text-gray-900" x-text="stats.total_users"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Active Tokens</span>
                        <span class="text-lg font-semibold text-green-600" x-text="stats.active_tokens"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Sent Today</span>
                        <span class="text-lg font-semibold text-blue-600" x-text="stats.sent_today"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Templates -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Templates</h3>
                <div class="space-y-2">
                    <button @click="useTemplate('welcome')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">Welcome Message</div>
                        <div class="text-gray-500">Welcome new users</div>
                    </button>
                    <button @click="useTemplate('update')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">App Update</div>
                        <div class="text-gray-500">Notify about new features</div>
                    </button>
                    <button @click="useTemplate('maintenance')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">Maintenance Notice</div>
                        <div class="text-gray-500">Scheduled maintenance alert</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="showMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
             class="border px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'" class="mr-2"></i>
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>

<script>
// Ensure the function is globally available
window.pushNotifications = function() {
    return {
        form: {
            title: '',
            body: '',
            target_type: 'all',
            user_ids: [],
            data: {}
        },
        sending: false,
        userSearch: '',
        searchResults: [],
        selectedUsers: [],
        additionalDataText: '',
        stats: {
            total_users: 0,
            active_tokens: 0,
            sent_today: 0
        },
        showMessage: false,
        message: '',
        messageType: 'success',

        init() {
            this.loadStats();
        },

        async loadStats() {
            try {
                const response = await fetch('/admin/api/notifications/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async searchUsers() {
            if (this.userSearch.length < 2) {
                this.searchResults = [];
                return;
            }

            try {
                const response = await fetch(`/admin/api/users/search?q=${encodeURIComponent(this.userSearch)}`);
                const data = await response.json();
                if (data.success) {
                    this.searchResults = data.users;
                }
            } catch (error) {
                console.error('Failed to search users:', error);
            }
        },

        toggleUser(user) {
            const index = this.form.user_ids.indexOf(user.id);
            if (index > -1) {
                this.form.user_ids.splice(index, 1);
                this.selectedUsers = this.selectedUsers.filter(u => u.id !== user.id);
            } else {
                this.form.user_ids.push(user.id);
                this.selectedUsers.push(user);
            }
        },

        removeUser(userId) {
            const index = this.form.user_ids.indexOf(userId);
            if (index > -1) {
                this.form.user_ids.splice(index, 1);
                this.selectedUsers = this.selectedUsers.filter(u => u.id !== userId);
            }
        },

        isUserSelected(userId) {
            return this.form.user_ids.includes(userId);
        },

        getSelectedUserName(userId) {
            const user = this.selectedUsers.find(u => u.id === userId);
            return user ? user.name : `User ${userId}`;
        },

        useTemplate(type) {
            const templates = {
                welcome: {
                    title: 'Welcome to ConnectApp!',
                    body: 'Thanks for joining our community. Start connecting with friends and sharing your moments!'
                },
                update: {
                    title: 'New Features Available!',
                    body: 'We\'ve added exciting new features to enhance your experience. Update now to try them out!'
                },
                maintenance: {
                    title: 'Scheduled Maintenance',
                    body: 'We\'ll be performing scheduled maintenance. The app may be temporarily unavailable.'
                }
            };

            if (templates[type]) {
                this.form.title = templates[type].title;
                this.form.body = templates[type].body;
            }
        },

        async sendNotification() {
            if (!this.form.title || !this.form.body) {
                this.showError('Please fill in all required fields');
                return;
            }

            if (this.form.target_type === 'users' && this.form.user_ids.length === 0) {
                this.showError('Please select at least one user');
                return;
            }

            this.sending = true;

            try {
                // Parse additional data
                if (this.additionalDataText.trim()) {
                    try {
                        this.form.data = JSON.parse(this.additionalDataText);
                    } catch (e) {
                        this.showError('Invalid JSON format in additional data');
                        this.sending = false;
                        return;
                    }
                }

                const response = await fetch('/admin/api/notifications/push/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(`Notification sent successfully! Sent: ${data.sent}, Failed: ${data.failed}`);
                    this.resetForm();
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to send notification');
                }
            } catch (error) {
                console.error('Send notification error:', error);
                this.showError('Failed to send notification');
            } finally {
                this.sending = false;
            }
        },

        resetForm() {
            this.form = {
                title: '',
                body: '',
                target_type: 'all',
                user_ids: [],
                data: {}
            };
            this.additionalDataText = '';
            this.selectedUsers = [];
            this.userSearch = '';
            this.searchResults = [];
        },

        showSuccess(msg) {
            this.message = msg;
            this.messageType = 'success';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        },

        showError(msg) {
            this.message = msg;
            this.messageType = 'error';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        }
    }
}
</script>
@endsection
