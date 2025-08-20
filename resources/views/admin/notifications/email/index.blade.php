
@extends('admin.layouts.app')

@section('title', 'Send Email Notification')
@section('page-title', 'Send Email Notification')

@section('content')
<div x-data="emailNotificationManager()" x-init="init()">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Send Email Notification</h1>
                <p class="mt-1 text-sm text-gray-600">Send email notifications to all users, selected users, social circles, or countries.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button @click="resetForm()" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Email
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-3">
            <form @submit.prevent="sendEmail()" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
                <!-- Target Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" x-model="form.target_type" value="all" class="form-radio text-primary" required>
                            <span class="ml-2">All Users</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" x-model="form.target_type" value="selected" class="form-radio text-primary">
                            <span class="ml-2">Selected Users</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" x-model="form.target_type" value="circle" class="form-radio text-primary">
                            <span class="ml-2">Social Circle</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" x-model="form.target_type" value="country" class="form-radio text-primary">
                            <span class="ml-2">Country</span>
                        </label>
                    </div>
                </div>

                <!-- Dynamic Target Inputs -->
                <div x-show="form.target_type === 'selected'" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Search Users</label>
                    <input type="text" x-model="userSearch" @input.debounce.300ms="searchUsers()" placeholder="Type to search users..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                    <div x-show="userResults.length > 0" class="bg-gray-50 border border-gray-200 rounded-lg p-2 max-h-40 overflow-y-auto">
                        <template x-for="user in userResults" :key="user.id">
                            <div class="flex items-center justify-between py-1 px-2 hover:bg-gray-100 rounded cursor-pointer" @click="addUser(user)">
                                <span x-text="user.name + ' (' + user.email + ')'" class="text-sm"></span>
                                <span class="text-xs text-primary">Add</span>
                            </div>
                        </template>
                    </div>
                    <div x-show="form.users.length > 0" class="flex flex-wrap gap-2 mt-2">
                        <template x-for="(user, idx) in form.users" :key="user.id">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                <span x-text="user.name"></span>
                                <button type="button" @click="removeUser(idx)" class="ml-2 text-blue-600 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>

                <div x-show="form.target_type === 'circle'" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Select Social Circle</label>
                    <select x-model="form.circle_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                        <option value="">Select a circle</option>
                        <template x-for="circle in circles" :key="circle.id">
                            <option :value="circle.id" x-text="circle.name"></option>
                        </template>
                    </select>
                </div>

                <div x-show="form.target_type === 'country'" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Select Country</label>
                    <select x-model="form.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                        <option value="">Select a country</option>
                        <template x-for="country in countries" :key="country">
                            <option :value="country" x-text="country"></option>
                        </template>
                    </select>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.subject" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Enter email subject" required>
                </div>

                <!-- Body -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Body <span class="text-red-500">*</span></label>
                    <textarea x-model="form.body" rows="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm" placeholder="Enter email body (HTML supported)" required></textarea>
                </div>

                <!-- Send Button -->
                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <button type="submit" :disabled="sending" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!sending">Send Email</span>
                        <span x-show="sending" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar Stats -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Users</span>
                        <span class="text-lg font-bold text-gray-900" x-text="stats.total_users || 0"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Circles</span>
                        <span class="text-lg font-bold text-blue-600" x-text="stats.total_circles || 0"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Countries</span>
                        <span class="text-lg font-bold text-green-600" x-text="stats.total_countries || 0"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-show="showToast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform translate-y-2" class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="toastType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'" class="border px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center">
                <svg x-show="toastType === 'success'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="toastType === 'error'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="toastMessage"></span>
            </div>
        </div>
    </div>
</div>

<script>
window.emailNotificationManager = function() {
    return {
        // Data
        stats: { total_users: 0, total_circles: 0, total_countries: 0 },
        circles: [],
        countries: [],
        userResults: [],
        userSearch: '',
        sending: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',

        // Form
        form: {
            target_type: 'all',
            users: [],
            circle_id: '',
            country: '',
            subject: '',
            body: ''
        },

        init() {
            this.loadStats();
            this.loadCircles();
            this.loadCountries();
        },

        resetForm() {
            this.form = {
                target_type: 'all',
                users: [],
                circle_id: '',
                country: '',
                subject: '',
                body: ''
            };
            this.userResults = [];
            this.userSearch = '';
        },

        async loadStats() {
            try {
                const res = await fetch('/admin/api/notifications/email/stats');
                const data = await res.json();
                if (data.success) {
                    this.stats = data.stats;
                }
            } catch (e) { }
        },

        async loadCircles() {
            try {
                const res = await fetch('/admin/api/social-circles');
                const data = await res.json();
                if (data.success) {
                    this.circles = data.circles;
                }
            } catch (e) { }
        },

        async loadCountries() {
            try {
                const res = await fetch('/admin/api/countries');
                const data = await res.json();
                if (data.success) {
                    this.countries = data.countries;
                }
            } catch (e) { }
        },

        async searchUsers() {
            if (!this.userSearch || this.userSearch.length < 2) {
                this.userResults = [];
                return;
            }
            try {
                const res = await fetch(`/admin/api/users/search?q=${encodeURIComponent(this.userSearch)}`);
                const data = await res.json();
                if (data.success) {
                    this.userResults = data.users;
                }
            } catch (e) { this.userResults = []; }
        },

        addUser(user) {
            if (!this.form.users.find(u => u.id === user.id)) {
                this.form.users.push(user);
            }
            this.userResults = [];
            this.userSearch = '';
        },

        removeUser(idx) {
            this.form.users.splice(idx, 1);
        },

        async sendEmail() {
            this.sending = true;
            try {
                let payload = {
                    target_type: this.form.target_type,
                    subject: this.form.subject,
                    body: this.form.body
                };
                if (this.form.target_type === 'selected') {
                    payload.users = this.form.users.map(u => u.id);
                } else if (this.form.target_type === 'circle') {
                    payload.circle_id = this.form.circle_id;
                } else if (this.form.target_type === 'country') {
                    payload.country = this.form.country;
                }
                const res = await fetch('/admin/notifications/email/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.showSuccess(data.message || 'Email sent successfully');
                    this.resetForm();
                } else {
                    this.showError(data.message || 'Failed to send email');
                }
            } catch (e) {
                this.showError('Failed to send email');
            } finally {
                this.sending = false;
            }
        },

        showSuccess(message) {
            this.toastMessage = message;
            this.toastType = 'success';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 5000);
        },
        showError(message) {
            this.toastMessage = message;
            this.toastType = 'error';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 5000);
        }
    }
}
</script>
@endsection
