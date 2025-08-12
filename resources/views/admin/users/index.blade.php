@extends('admin.layouts.app')

@section('title', 'User Management')


@section('content')
    <div x-data="userManagement()" x-init="loadUsers(); loadSocialCircles(); loadCountries()">>

        <!-- Header with Export -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">User Management</h2>
                <p class="text-gray-600">Manage all registered users</p>
            </div>
            <div class="flex space-x-3">
                <!-- Export Dropdown -->
                <div class="relative">
                    <button @click="exportOpen = !exportOpen"
                            type="button"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        Export
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="exportOpen"
                         @click.away="exportOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                        <div class="py-1">
                            <button @click="exportUsers('csv'); exportOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-file-csv mr-2 text-green-600"></i>
                                Export as CSV
                            </button>
                            <button @click="exportUsers('excel'); exportOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-file-excel mr-2 text-green-600"></i>
                                Export as Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Users</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by name or email..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadUsers()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadUsers()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select id="country"
                                x-model="filters.country"
                                @change="loadUsers()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Countries</option>
                            <template x-for="country in countries" :key="country.id">
                                <option :value="country.id" x-text="country.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Registration Date</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="date"
                                       x-model="filters.date_from"
                                       @change="loadUsers()"
                                       placeholder="From"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div>
                                <input type="date"
                                       x-model="filters.date_to"
                                       @change="loadUsers()"
                                       placeholder="To"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>
                        <div x-show="filters.date_from || filters.date_to" class="mt-1">
                            <button @click="filters.date_from = ''; filters.date_to = ''; loadUsers()"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times mr-1"></i>Clear dates
                            </button>
                        </div>
                    </div>

                    <!-- Social Circle Filter -->
                    <div>
                        <label for="social_circles" class="block text-sm font-medium text-gray-700 mb-1">Social Circles</label>
                        <select id="social_circles"
                                x-model="filters.social_circles"
                                @change="loadUsers()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Users</option>
                            <option value="has_circles">With Circles</option>
                            <option value="no_circles">No Circles</option>

                            @foreach(\App\Models\SocialCircle::all() as $circle)
                                <option value="{{ $circle->id }}">{{ $circle->name }}</option>
                            @endforeach
                        </select>


                        </select>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Users</p>
                        <p class="text-xl font-bold text-blue-900" x-text="formatNumber(stats.total)">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active</p>
                        <p class="text-xl font-bold text-green-900" x-text="formatNumber(stats.active)">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Suspended</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="formatNumber(stats.suspended)">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Banned</p>
                        <p class="text-xl font-bold text-red-900" x-text="formatNumber(stats.banned)">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">In Circles</p>
                        <p class="text-xl font-bold text-purple-900" x-text="formatNumber(stats.with_social_circles)">0</p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md">

            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox"
                               @change="toggleSelectAll()"
                               :checked="isAllSelected()"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="ml-3 text-sm text-gray-600">
                            <span x-show="selectedUsers.length === 0">Select users</span>
                            <span x-show="selectedUsers.length > 0" x-text="selectedUsers.length + ' selected'"></span>
                        </span>
                    </div>

                    <!-- Bulk Actions -->
                    <div x-show="selectedUsers.length > 0" class="flex items-center space-x-2">
                        <button @click="bulkSuspend()"
                                class="text-sm text-yellow-600 hover:text-yellow-800 font-medium">
                            <i class="fas fa-pause mr-1"></i>
                            Suspend
                        </button>
                        <button @click="bulkActivate()"
                                class="text-sm text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-play mr-1"></i>
                            Activate
                        </button>
                        <button @click="bulkBan()"
                                class="text-sm text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-ban mr-1"></i>
                            Ban
                        </button>
                    </div>

                    <!-- Pagination Info -->
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="formatNumber(pagination.from || 0)"></span> to <span x-text="formatNumber(pagination.to || 0)"></span>
                        of <span x-text="formatNumber(pagination.total || 0)"></span> users
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading users...</p>
            </div>

            <!-- Table Content -->
            <div x-show="!loading" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Social Circles
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Registration
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Activity
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="user in users" :key="user.id">
                            <tr class="hover:bg-gray-50">
                                <!-- Checkbox -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox"
                                           :value="user.id"
                                           x-model="selectedUsers"
                                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                </td>

                                <!-- User Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 :src="user.profile_picture || '/images/default-avatar.png'"
                                                 :alt="user.name">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="user.name"></div>
                                            <div class="text-sm text-gray-500">
                                                ID: <span x-text="user.id"></span>
                                                <span x-show="user.email_verified_at" class="ml-2">
                                                    <i class="fas fa-check-circle text-green-500" title="Verified"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="user.email"></div>
                                    <div class="text-sm text-gray-500" x-text="user.phone || 'No phone'"></div>
                                </td>

                                <!-- Country -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <template x-if="user.country">
                                            <div class="flex items-center">

                                                <span class="text-sm text-gray-900" x-text="user.country.name"></span>
                                            </div>
                                        </template>
                                        <template x-if="!user.country">
                                            <span class="text-sm text-gray-400 italic">No country</span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="getStatusBadge(user.status)" x-text="user.status"></span>
                                </td>

                                <!-- Social Circles -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-users mr-1"></i>
                                            <span x-text="user.social_circles_count || 0"></span>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1" x-show="user.social_circles_names && user.social_circles_names.length > 0">
                                        <template x-for="(circle, index) in user.social_circles_names?.slice(0, 2)" :key="index">
                                            <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded mr-1 mb-1"
                                                  x-text="circle"
                                                  :style="user.social_circles_colors && user.social_circles_colors[circle] ?
                                                    `background-color: ${user.social_circles_colors[circle]}20; color: ${user.social_circles_colors[circle]}` : ''"></span>
                                        </template>
                                        <span x-show="user.social_circles_names && user.social_circles_names.length > 2"
                                              class="text-xs text-gray-400"
                                              x-text="`+${user.social_circles_names.length - 2} more`"></span>
                                    </div>
                                    <div class="text-xs text-gray-400 italic" x-show="!user.social_circles_names || user.social_circles_names.length === 0">
                                        No circles
                                    </div>
                                </td>

                                <!-- Registration -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div x-text="formatDate(user.created_at)"></div>
                                    <div class="text-xs text-gray-500" x-text="user.created_at_human"></div>
                                </td>

                                <!-- Activity -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Last: <span x-text="user.last_login_at ? formatDate(user.last_login_at) : 'Never'"></span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Posts: <span x-text="user.posts_count || 0"></span> |
                                        Streams: <span x-text="user.streams_count || 0"></span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click="viewUser(user)"
                                                class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button @click="editUser(user)"
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open"
                                                    class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open"
                                                 @click.away="open = false"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                                <div class="py-1">
                                                    <button @click="suspendUser(user); open = false"
                                                            x-show="user.status === 'active'"
                                                            class="block w-full text-left px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-50">
                                                        <i class="fas fa-pause mr-2"></i>
                                                        Suspend User
                                                    </button>
                                                    <button @click="activateUser(user); open = false"
                                                            x-show="user.status !== 'active'"
                                                            class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                                                        <i class="fas fa-play mr-2"></i>
                                                        Activate User
                                                    </button>
                                                    <button @click="banUser(user); open = false"
                                                            x-show="user.status !== 'banned'"
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                        <i class="fas fa-ban mr-2"></i>
                                                        Ban User
                                                    </button>
                                                    <button @click="resetPassword(user); open = false"
                                                            class="block w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50">
                                                        <i class="fas fa-key mr-2"></i>
                                                        Reset Password
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="users.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p class="text-lg">No users found</p>
                                <p class="text-sm">Try adjusting your search filters</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.total > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span x-text="formatNumber(pagination.from)"></span> to <span x-text="formatNumber(pagination.to)"></span> of
                                <span x-text="formatNumber(pagination.total)"></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <button @click="changePage(pagination.current_page - 1)"
                                        :disabled="pagination.current_page <= 1"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <template x-for="page in getPageNumbers()" :key="page">
                                    <button @click="changePage(page)"
                                            :class="page === pagination.current_page ?
                                                   'bg-primary text-white' :
                                                   'bg-white text-gray-500 hover:bg-gray-50'"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                                        <span x-text="page"></span>
                                    </button>
                                </template>
                                <button @click="changePage(pagination.current_page + 1)"
                                        :disabled="pagination.current_page >= pagination.last_page"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    function userManagement() {
        return {
            users: [],
            stats: {},
            pagination: {},
            socialCircles: [],
            countries: [],
            loading: false,
            selectedUsers: [],
            exportOpen: false,
            filters: {
                search: '',
                status: '',
                country: '',
                // verified: '', // Commented out
                social_circles: '',
                date_from: '',
                date_to: ''
            },
            searchTimeout: null,
             async loadSocialCircles() {
                console.log('Loading social circles...');

                // Set dummy data first for testing - this ensures the binding works
                this.socialCircles = [
                    { id: 0, name: "Loading..." }
                ];

                try {
                    // Direct AJAX request using XMLHttpRequest for maximum compatibility
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', '/admin/api/social-circles', true);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (csrfToken) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    }

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            console.log('Success response:', xhr.responseText);

                            try {
                                const data = JSON.parse(xhr.responseText);
                                console.log('Parsed data:', data);

                                // Clear the dummy data
                                this.socialCircles = [];

                                // Check if we have social circles and it's an array
                                if (data && data.social_circles && Array.isArray(data.social_circles)) {
                                    console.log(`Found ${data.social_circles.length} social circles`);

                                    // Add each item individually to ensure reactivity
                                    data.social_circles.forEach(circle => {
                                        if (circle && circle.id && circle.name) {
                                            this.socialCircles.push({
                                                id: circle.id,
                                                name: circle.name
                                            });
                                        }
                                    });

                                    console.log('Social circles loaded:', this.socialCircles);

                                    // Add this to help debug
                                    document.getElementById('social-circles-debug').textContent =
                                        `Loaded ${this.socialCircles.length} circles`;
                                } else {
                                    console.error('No social circles found in data:', data);
                                    document.getElementById('social-circles-debug').textContent =
                                        'No social circles in response';
                                }
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                document.getElementById('social-circles-debug').textContent =
                                    'Error parsing JSON: ' + e.message;
                            }
                        } else {
                            console.error('HTTP error:', xhr.status, xhr.statusText);
                            document.getElementById('social-circles-debug').textContent =
                                `Error: ${xhr.status} ${xhr.statusText}`;
                        }
                    };

                    xhr.onerror = () => {
                        console.error('Network error');
                        document.getElementById('social-circles-debug').textContent = 'Network error';
                    };

                    xhr.send();

                } catch (error) {
                    console.error('Failed to load social circles:', error);
                    document.getElementById('social-circles-debug').textContent =
                        'Error: ' + error.message;
                }
            },

            async loadCountries() {
                try {
                    const response = await fetch('/admin/api/countries', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    // Handle both the new format and legacy format
                    if (data.success && Array.isArray(data.countries)) {
                        this.countries = data.countries;
                    } else if (Array.isArray(data)) {
                        this.countries = data;
                    }
                } catch (error) {
                    console.error('Failed to load countries:', error);
                    this.countries = [];
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadUsers();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadUsers(page);
                }
            },

            async loadUsers(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/users?${params}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        if (response.status === 401) {
                            window.location.href = '/admin/login';
                            return;
                        }
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    this.users = data.users.data || [];
                    this.pagination = {
                        current_page: data.users.current_page || 1,
                        last_page: data.users.last_page || 1,
                        from: data.users.from || 0,
                        to: data.users.to || 0,
                        total: data.users.total || 0
                    };
                    this.stats = data.stats || { total: 0, active: 0, suspended: 0, banned: 0 };
                    this.selectedUsers = [];
                } catch (error) {
                    console.error('Failed to load users:', error);
                    this.showError('Failed to load users: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },



            getPageNumbers() {
                const pages = [];
                const current = this.pagination.current_page;
                const last = this.pagination.last_page;

                let start = Math.max(1, current - 2);
                let end = Math.min(last, current + 2);

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }

                return pages;
            },

            toggleSelectAll() {
                if (this.isAllSelected()) {
                    this.selectedUsers = [];
                } else {
                    this.selectedUsers = this.users.map(user => user.id);
                }
            },

            isAllSelected() {
                return this.users.length > 0 && this.selectedUsers.length === this.users.length;
            },

            getStatusBadge(status) {
                const badges = {
                    'active': 'bg-green-100 text-green-800',
                    'suspended': 'bg-yellow-100 text-yellow-800',
                    'banned': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString();
            },

            formatNumber(number) {
                return Number(number || 0).toLocaleString();
            },

            async suspendUser(user) {
                if (confirm(`Are you sure you want to suspend ${user.name}?`)) {
                    await this.updateUserStatus(user.id, 'suspended');
                }
            },

            async activateUser(user) {
                await this.updateUserStatus(user.id, 'active');
            },

            async banUser(user) {
                if (confirm(`Are you sure you want to ban ${user.name}? This action is serious.`)) {
                    await this.updateUserStatus(user.id, 'banned');
                }
            },

            async updateUserStatus(userId, status) {
                try {
                    const response = await fetch(`/admin/api/users/${userId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status })
                    });

                    if (response.ok) {
                        this.showSuccess(`User ${status} successfully`);
                        this.loadUsers(this.pagination.current_page);
                    } else {
                        throw new Error('Failed to update user status');
                    }
                } catch (error) {
                    this.showError('Failed to update user status');
                }
            },

            async bulkSuspend() {
                if (this.selectedUsers.length === 0) return;
                if (confirm(`Suspend ${this.selectedUsers.length} selected users?`)) {
                    await this.bulkUpdateStatus('suspended');
                }
            },

            async bulkActivate() {
                if (this.selectedUsers.length === 0) return;
                await this.bulkUpdateStatus('active');
            },

            async bulkBan() {
                if (this.selectedUsers.length === 0) return;
                if (confirm(`Ban ${this.selectedUsers.length} selected users? This is a serious action.`)) {
                    await this.bulkUpdateStatus('banned');
                }
            },

            async bulkUpdateStatus(status) {
                try {
                    const response = await fetch('/admin/api/users/bulk-status', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            user_ids: this.selectedUsers,
                            status: status
                        })
                    });

                    if (response.ok) {
                        this.showSuccess(`${this.selectedUsers.length} users ${status} successfully`);
                        this.selectedUsers = [];
                        this.loadUsers(this.pagination.current_page);
                    } else {
                        throw new Error('Failed to update users');
                    }
                } catch (error) {
                    this.showError('Failed to update users');
                }
            },

            viewUser(user) {
                window.open(`/admin/users/${user.id}`, '_blank');
            },

            editUser(user) {
                window.location.href = `/admin/users/${user.id}/edit`;
            },

            async resetPassword(user) {
                if (confirm(`Send password reset email to ${user.name}?`)) {
                    try {
                        const response = await fetch(`/admin/api/users/${user.id}/reset-password`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (response.ok) {
                            this.showSuccess('Password reset email sent successfully');
                        } else {
                            throw new Error('Failed to send reset email');
                        }
                    } catch (error) {
                        this.showError('Failed to send password reset email');
                    }
                }
            },

            showSuccess(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            },

            showError(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            },

            exportUsers(format = 'csv') {
                try {
                    // Validate format
                    if (!['csv', 'excel', 'xlsx'].includes(format)) {
                        throw new Error('Invalid export format');
                    }

                    // Get current filters from this Alpine.js component
                    const params = new URLSearchParams();

                    if (this.filters.search) params.append('search', this.filters.search);
                    if (this.filters.status) params.append('status', this.filters.status);
                    if (this.filters.social_circles) params.append('social_circles', this.filters.social_circles);
                    if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.append('date_to', this.filters.date_to);

                    // Add format parameter
                    params.append('format', format);

                    const exportUrl = `/admin/users/export?${params.toString()}`;

                    // Show loading state
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-blue-600';
                    toast.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            <span>Checking export size...</span>
                        </div>
                    `;
                    document.body.appendChild(toast);

                    // Use fetch to check if export will be queued or immediate
                    fetch(exportUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        // Check if response is JSON (queued) or file download (immediate)
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            // For immediate download, trigger download via window.location
                            window.location.href = exportUrl;
                            return { immediate: true };
                        }
                    })
                    .then(data => {
                        // Remove loading toast
                        if (toast.parentNode) {
                            toast.remove();
                        }

                        if (data.immediate) {
                            // Show immediate download success
                            const successToast = document.createElement('div');
                            successToast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-green-600';
                            successToast.innerHTML = `
                                <div class="flex items-center">
                                    <i class="fas fa-download mr-2"></i>
                                    <span>${format.toUpperCase()} export download started!</span>
                                </div>
                            `;
                            document.body.appendChild(successToast);
                            setTimeout(() => successToast.remove(), 3000);
                        } else if (data.queued) {
                            // Show queued export message
                            const queueToast = document.createElement('div');
                            queueToast.className = 'fixed top-4 right-4 bg-orange-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 border border-orange-600 max-w-md';
                            queueToast.innerHTML = `
                                <div class="flex items-start">
                                    <i class="fas fa-clock mr-3 mt-1"></i>
                                    <div>
                                        <div class="font-semibold mb-1">Export Queued</div>
                                        <div class="text-sm">
                                            ${data.total_records} records found. <br>
                                            You'll receive an email when ready!
                                        </div>
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(queueToast);
                            setTimeout(() => queueToast.remove(), 8000);
                        } else if (data.success) {
                            // Regular success message
                            const successToast = document.createElement('div');
                            successToast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-green-600';
                            successToast.innerHTML = `
                                <div class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    <span>${data.message}</span>
                                </div>
                            `;
                            document.body.appendChild(successToast);
                            setTimeout(() => successToast.remove(), 5000);
                        }
                    })
                    .catch(error => {
                        console.error('Export error:', error);

                        // Remove loading toast
                        if (toast.parentNode) {
                            toast.remove();
                        }

                        // Show error message
                        const errorToast = document.createElement('div');
                        errorToast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-red-600';
                        errorToast.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Export failed: ${error.message}</span>
                            </div>
                        `;
                        document.body.appendChild(errorToast);
                        setTimeout(() => errorToast.remove(), 5000);
                    });

                } catch (error) {
                    console.error('Export error:', error);

                    // Show error message
                    const errorToast = document.createElement('div');
                    errorToast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-red-600';
                    errorToast.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span>Export failed: ${error.message}</span>
                        </div>
                    `;
                    document.body.appendChild(errorToast);
                    setTimeout(() => errorToast.remove(), 5000);
                }
            }
        }
    }

    function bulkAction() {
        alert('Bulk action feature would be implemented here');
    }
</script>
@endpush
