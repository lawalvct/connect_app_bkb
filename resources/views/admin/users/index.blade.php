@extends('admin.layouts.app')

@section('title', 'User Management')


@section('content')
    <div x-data="userManagement()" x-init="loadUsers(); loadSocialCircles(); loadPendingVerificationsCount(); initExportStatus()">

        <!-- Header with Export -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">User Management</h2>
                <p class="text-gray-600">Manage all registered users</p>
            </div>
            <div class="flex space-x-3 items-center">
                <!-- Latest Export Link -->
                <div x-show="exportStatus && exportStatus.status === 'completed'" class="mr-2">
                    <a :href="exportStatus.download_url"
                       target="_blank"
                       class="inline-flex items-center text-sm text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 px-3 py-2 rounded-md">
                        <i class="fas fa-file-download mr-2"></i>
                        <span>
                            Download latest export
                            <span class="ml-1 text-xs text-green-600" x-text="exportStatus.format ? '(' + exportStatus.format.toUpperCase() + ')' : ''"></span>
                        </span>
                    </a>
                </div>
                <!-- Verification Button -->
                <button @click="showVerificationModal = true; loadPendingVerifications()"
                        type="button"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                    <i class="fas fa-id-card mr-2"></i>
                    ID Verifications
                    <span x-show="pendingVerificationsCount > 0"
                          x-text="pendingVerificationsCount"
                          class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center"></span>
                </button>

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
            <!-- Filter Header with Toggle -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="filtersVisible = !filtersVisible">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-filter text-gray-500"></i>
                    <h3 class="text-lg font-medium text-gray-700">Filters</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                          x-show="Object.values(filters).some(val => val !== '')">
                        Filters Applied
                    </span>
                </div>
                <div>
                    <i class="fas" :class="filtersVisible ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>
            </div>

            <!-- Filter Content -->
            <div class="p-6" x-show="filtersVisible" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
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

                    <!-- Verification Filter -->
                    <div>
                        <label for="verification" class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                        <select id="verification"
                                x-model="filters.verification"
                                @change="loadUsers()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Verification</option>
                            <option value="verified">Verified</option>
                            <option value="pending">Pending Verification</option>
                            <option value="rejected">Rejected Verification</option>
                            <option value="none">Not Submitted</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Country Filter -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select id="country"
                                x-model="filters.country"
                                @change="loadUsers()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Countries</option>
                            @foreach(\App\Models\Country::orderBy('name')->get() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
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

                            @foreach(\App\Models\SocialCircle::orderBy('order_by')->get() as $circle)
                                <option value="{{ $circle->id }}">{{ $circle->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div class="mt-4 flex justify-end">
                    <button @click="clearAllFilters()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Clear All Filters
                    </button>
                </div>


                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-7 gap-4">
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
                    <div class="text-center p-3 bg-orange-50 rounded-md">
                        <p class="text-sm text-orange-600">Verified</p>
                        <p class="text-xl font-bold text-orange-900" x-text="formatNumber(stats.verified_users)">0</p>
                    </div>
                    <div class="text-center p-3 bg-teal-50 rounded-md">
                        <p class="text-sm text-teal-600">Connected</p>
                        <p class="text-xl font-bold text-teal-900" x-text="formatNumber(stats.users_with_connections)">0</p>
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
                                Social Circles
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Connections
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Verification
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
                                                <span x-show="user.is_verified" class="ml-2">
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

                                <!-- Connections -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-handshake mr-1"></i>
                                            <span x-text="user.connections_count || 0"></span>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1" x-show="user.connections_count > 0">
                                        {{-- <span x-text="user.connections_count === 1 ? '1 connection' : `${user.connections_count} connections`"></span> --}}
                                    </div>
                                    <div class="text-xs text-gray-400 italic" x-show="!user.connections_count || user.connections_count === 0">

                                    </div>
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

                                <!-- Verification Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="user.verification_status === 'approved'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Verified
                                        </span>
                                    </template>
                                    <template x-if="user.verification_status === 'pending'">
                                        <button @click="openUserVerification(user)"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                            <i class="fas fa-clock mr-1"></i>
                                            Pending
                                        </button>
                                    </template>
                                    <template x-if="user.verification_status === 'rejected'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Rejected
                                        </span>
                                    </template>
                                    <template x-if="user.verification_status === 'none'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-user mr-1"></i>
                                            Not Submitted
                                        </span>
                                    </template>
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
                                        Posts: <span x-text="user.posts_count || 0"></span>
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
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
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

        <!-- ID Verification Modal -->
        <div x-show="showVerificationModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showVerificationModal = false">

            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">ID Card Verifications</h3>
                    <button @click="showVerificationModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="loadingVerifications" class="p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">Loading verifications...</p>
                </div>

                <!-- Verifications List -->
                <div x-show="!loadingVerifications" class="space-y-4 max-h-96 overflow-y-auto">
                    <template x-for="verification in pendingVerifications" :key="verification.id">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <!-- User Info -->
                                <div>
                                    <div class="flex items-center mb-3">
                                        <img class="h-10 w-10 rounded-full object-cover mr-3"
                                             :src="verification.user.profile_picture || '/images/default-avatar.png'"
                                             :alt="verification.user.name">
                                        <div>
                                            <h4 class="font-medium text-gray-900" x-text="verification.user.name"></h4>
                                            <p class="text-sm text-gray-500" x-text="verification.user.email"></p>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-sm">
                                        <p><span class="font-medium">ID Type:</span> <span x-text="verification.id_card_type.replace('_', ' ').toUpperCase()"></span></p>
                                        <p><span class="font-medium">Submitted:</span> <span x-text="formatDate(verification.submitted_at)"></span></p>
                                        <p><span class="font-medium">Status:</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i>
                                                Pending Review
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex space-x-2 mt-4">
                                        <button @click="approveVerification(verification.id)"
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm">
                                            <i class="fas fa-check mr-1"></i>
                                            Approve
                                        </button>
                                        <button @click="showRejectModal(verification)"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                                            <i class="fas fa-times mr-1"></i>
                                            Reject
                                        </button>
                                    </div>
                                </div>

                                <!-- ID Card Image -->
                                <div>
                                    <p class="font-medium text-gray-900 mb-2">ID Card Image:</p>
                                    <div class="border rounded-lg overflow-hidden">
                                        <img :src="verification.id_card_image_url"
                                             :alt="'ID Card for ' + verification.user.name"
                                             class="w-full h-48 object-contain bg-gray-100 cursor-pointer"
                                             @click="showImageModal(verification.id_card_image_url)">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Click image to view full size</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div x-show="pendingVerifications.length === 0" class="text-center py-8">
                        <i class="fas fa-id-card text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No pending verifications</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Verification Quick Modal -->
        <div x-show="userVerificationModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[70]"
             @click.self="userVerificationModal = false">

            <div class="relative top-24 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">User Verification</h3>
                    <button @click="userVerificationModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <template x-if="!userVerification">
                    <div class="p-8 text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Loading verification...</p>
                    </div>
                </template>

                <template x-if="userVerification">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center mb-3">
                                <img class="h-10 w-10 rounded-full object-cover mr-3"
                                     :src="userVerification.user.profile_picture || '/images/default-avatar.png'"
                                     :alt="userVerification.user.name">
                                <div>
                                    <h4 class="font-medium text-gray-900" x-text="userVerification.user.name"></h4>
                                    <p class="text-sm text-gray-500" x-text="userVerification.user.email"></p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <p><span class="font-medium">ID Type:</span> <span x-text="userVerification.id_card_type.replace('_', ' ').toUpperCase()"></span></p>
                                <p><span class="font-medium">Submitted:</span> <span x-text="formatDate(userVerification.submitted_at)"></span></p>
                                <p><span class="font-medium">Status:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                          :class="userVerification.admin_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : (userVerification.admin_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')">
                                        <i class="fas" :class="userVerification.admin_status === 'pending' ? 'fa-clock' : (userVerification.admin_status === 'approved' ? 'fa-check-circle' : 'fa-times-circle')"></i>
                                        <span class="ml-1 capitalize" x-text="userVerification.admin_status"></span>
                                    </span>
                                </p>
                            </div>

                            <div class="flex space-x-2 mt-4" x-show="userVerification.admin_status === 'pending'">
                                <button @click="approveUserVerification()"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-check mr-1"></i>
                                    Approve
                                </button>
                                <button @click="rejectUserVerification()"
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-times mr-1"></i>
                                    Reject
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 mb-2">ID Card Image:</p>
                            <div class="border rounded-lg overflow-hidden">
                                <img :src="userVerification.id_card_image_url"
                                     :alt="'ID Card for ' + userVerification.user.name"
                                     class="w-full h-64 object-contain bg-gray-100 cursor-pointer"
                                     @click="showImageModal(userVerification.id_card_image_url)">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Click image to view full size</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <div x-show="showImagePreview"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full z-[9999]"
             @click.self="showImagePreview = false">

            <div class="relative top-20 mx-auto p-5 w-11/12 max-w-4xl">
                <div class="bg-white rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h3 class="text-lg font-medium text-gray-900">ID Card Preview</h3>
                        <button @click="showImagePreview = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <img :src="previewImageUrl"
                             alt="ID Card Preview"
                             class="w-full h-auto max-h-96 object-contain bg-gray-100">
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Reason Modal -->
        <div x-show="showRejectReasonModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[60]"
             @click.self="showRejectReasonModal = false">

            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Reject Verification</h3>
                    <p class="text-sm text-gray-600 mt-1">Please provide a reason for rejection:</p>
                </div>

                <textarea x-model="rejectReason"
                          placeholder="Enter rejection reason..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                          rows="4"></textarea>

                <div class="flex space-x-2 mt-4">
                    <button @click="confirmRejectVerification()"
                            :disabled="!rejectReason.trim()"
                            class="bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white px-4 py-2 rounded text-sm">
                        Reject Verification
                    </button>
                    <button @click="showRejectReasonModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
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
            loading: false,
            selectedUsers: [],
            exportOpen: false,
            exportStatus: null,
            exportPollTimer: null,
            showVerificationModal: false,
            showImagePreview: false,
            showRejectReasonModal: false,
            pendingVerifications: [],
            pendingVerificationsCount: 0,
            loadingVerifications: false,
            previewImageUrl: '',
            selectedVerification: null,
            rejectReason: '',
            userVerificationModal: false,
            userVerification: null,
            filters: {
                search: '',
                status: '',
                country: '',
                verification: '',
                social_circles: '',
                date_from: '',
                date_to: ''
            },
            filtersVisible: false, // Start with filters collapsed
            searchTimeout: null,
            async initExportStatus() {
                await this.fetchExportStatus();
                // Start polling every 10 seconds
              //  this.exportPollTimer = setInterval(() => this.fetchExportStatus(), 10000);
            },

            async openUserVerification(user) {
                try {
                    this.userVerificationModal = true;
                    this.userVerification = null;
                    const response = await fetch(`/admin/api/verifications/user/${user.id}/latest`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        this.userVerification = data.verification;
                    } else {
                        this.showError(data.message || 'No verification found');
                        this.userVerificationModal = false;
                    }
                } catch (e) {
                    this.showError('Failed to load verification');
                    this.userVerificationModal = false;
                }
            },

            async approveUserVerification() {
                if (!this.userVerification) return;
                if (!confirm('Approve this verification?')) return;
                try {
                    const response = await fetch(`/admin/api/verifications/${this.userVerification.id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    this.showSuccess('Verification approved');
                    this.userVerificationModal = false;
                    await this.loadUsers(this.pagination.current_page);
                    await this.loadPendingVerificationsCount();
                } catch (e) {
                    this.showError('Failed to approve');
                }
            },

            async rejectUserVerification() {
                if (!this.userVerification) return;
                const reason = prompt('Enter rejection reason:');
                if (!reason) return;
                try {
                    const response = await fetch(`/admin/api/verifications/${this.userVerification.id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ reason })
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    this.showSuccess('Verification rejected');
                    this.userVerificationModal = false;
                    await this.loadUsers(this.pagination.current_page);
                    await this.loadPendingVerificationsCount();
                } catch (e) {
                    this.showError('Failed to reject');
                }
            },
            async fetchExportStatus() {
                try {
                    const response = await fetch('/admin/users/export-status', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (data && data.success) {
                        this.exportStatus = data.status;
                    }
                } catch (e) {
                    // ignore
                }
            },
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

            async loadPendingVerificationsCount() {
                try {
                    const response = await fetch('/admin/api/verifications/pending-count', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.pendingVerificationsCount = data.count || 0;
                    }
                } catch (error) {
                    console.error('Failed to load pending verifications count:', error);
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

            clearAllFilters() {
                this.filters = {
                    search: '',
                    status: '',
                    country: '',
                    verification: '',
                    social_circles: '',
                    date_from: '',
                    date_to: ''
                };
                this.loadUsers();
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

            async loadPendingVerifications() {
                this.loadingVerifications = true;
                try {
                    const response = await fetch('/admin/api/verifications/pending', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    this.pendingVerifications = data.verifications || [];
                    this.pendingVerificationsCount = this.pendingVerifications.length;
                } catch (error) {
                    console.error('Failed to load pending verifications:', error);
                    this.showError('Failed to load pending verifications');
                } finally {
                    this.loadingVerifications = false;
                }
            },

            showImageModal(imageUrl) {
                this.previewImageUrl = imageUrl;
                this.showImagePreview = true;
            },

            showRejectModal(verification) {
                this.selectedVerification = verification;
                this.rejectReason = '';
                this.showRejectReasonModal = true;
            },

            async approveVerification(verificationId) {
                if (!confirm('Are you sure you want to approve this verification?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/api/verifications/${verificationId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    this.showSuccess('Verification approved successfully');
                    await this.loadPendingVerifications();
                    await this.loadUsers(this.pagination.current_page);
                } catch (error) {
                    console.error('Failed to approve verification:', error);
                    this.showError('Failed to approve verification');
                }
            },

            async confirmRejectVerification() {
                if (!this.selectedVerification || !this.rejectReason.trim()) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/api/verifications/${this.selectedVerification.id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            reason: this.rejectReason
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    this.showSuccess('Verification rejected successfully');
                    this.showRejectReasonModal = false;
                    await this.loadPendingVerifications();
                    await this.loadUsers(this.pagination.current_page);
                } catch (error) {
                    console.error('Failed to reject verification:', error);
                    this.showError('Failed to reject verification');
                }
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
                    if (this.filters.verification) params.append('verification', this.filters.verification);
                    if (this.filters.country) params.append('country', this.filters.country);
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
                            <span>Preparing ${format.toUpperCase()} export...</span>
                        </div>
                    `;
                    document.body.appendChild(toast);

                    // Trigger direct download
                    window.location.href = exportUrl;

                    // Remove loading toast after a short delay
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }

                        // Show success message
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
                    }, 1000);

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
