@extends('admin.layouts.app')

@section('title', 'Ad Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ad Management</h1>
            <p class="text-gray-600">Manage advertisement campaigns and approvals</p>
        </div>
        <div class="flex space-x-3">
            <button @click="exportAds()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export CSV
            </button>
            <button @click="showBulkActions = !showBulkActions"
                    x-show="selectedAds.length > 0"
                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-tasks mr-2"></i>
                Bulk Actions
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="adManagement()" x-init="loadAds(); loadStats(); loadCountries(); loadSocialCircles()">

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Ads</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by name, description or user..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadAds()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Ad Type</label>
                        <select id="type"
                                x-model="filters.type"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Types</option>
                            <option value="banner">Image</option>
                            <option value="video">Video</option>
                            {{-- <option value="carousel">Carousel</option>
                            <option value="story">Story</option>
                            <option value="feed">Feed</option> --}}
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="stopped">Stopped</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Admin Status Filter -->
                    <div>
                        <label for="admin_status" class="block text-sm font-medium text-gray-700 mb-1">Admin Status</label>
                        <select id="admin_status"
                                x-model="filters.admin_status"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Admin Status</option>
                            <option value="pending">Pending Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                        <select id="payment_status"
                                x-model="filters.payment_status"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Payments</option>
                            <option value="completed">Paid</option>
                            <option value="pending">Pending Payment</option>
                            <option value="failed">Payment Failed</option>
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Target Country</label>
                        <select id="country"
                                x-model="filters.country"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Countries</option>
                            <template x-for="country in countries" :key="country.id">
                                <option :value="country.id" x-text="country.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Social Circle Filter -->
                    <div>
                        <label for="social_circle" class="block text-sm font-medium text-gray-700 mb-1">Target Social Circle</label>
                        <select id="social_circle"
                                x-model="filters.social_circle"
                                @change="loadAds()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Social Circles</option>
                            <template x-for="circle in socialCircles" :key="circle.id">
                                <option :value="circle.id" x-text="circle.name"></option>
                            </template>
                        </select>
                    </div>

                </div>

                <!-- Date Range Filter Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date"
                               id="date_from"
                               x-model="filters.date_from"
                               @change="loadAds()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date"
                               id="date_to"
                               x-model="filters.date_to"
                               @change="loadAds()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Quick Date Presets -->
                    {{-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quick Presets</label>
                        <div class="flex flex-wrap gap-2">
                            <button @click="setDateRange('today')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                Today
                            </button>
                            <button @click="setDateRange('yesterday')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                Yesterday
                            </button>
                            <button @click="setDateRange('this_week')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                This Week
                            </button>
                            <button @click="setDateRange('this_month')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                This Month
                            </button>
                        </div>
                    </div> --}}

                    <!-- Clear Filters -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actions</label>
                        <button @click="clearFilters()"
                                class="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                            <i class="fas fa-times mr-1"></i>
                            Clear All Filters
                        </button>
                    </div>

                </div>

                <!-- Quick Actions -->
                <div class="mt-4 flex justify-end items-center">
                    <div class="text-sm text-gray-500">
                        <span x-text="ads.total || '0'">0</span> ads found
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-8 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Ads</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total_ads || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Pending Review</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.pending_review || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Approved</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.approved || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Rejected</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.rejected || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Active</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.active_ads || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">With Payment</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="stats.ads_with_payment || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-pink-50 rounded-md">
                        <p class="text-sm text-pink-600">Draft</p>
                        <p class="text-xl font-bold text-pink-900" x-text="stats.draft_ads || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-emerald-50 rounded-md">
                        <p class="text-sm text-emerald-600">Revenue</p>
                        <p class="text-xl font-bold text-emerald-900" x-text="'$' + (stats.total_revenue || '0')">$0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div x-show="showBulkActions && selectedAds.length > 0"
             x-transition
             class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-4 border-l-4 border-yellow-400">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-400 mr-2"></i>
                        <span class="font-medium" x-text="`${selectedAds.length} ads selected`"></span>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="bulkApprove()"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-check mr-1"></i>
                            Approve Selected
                        </button>
                        <button @click="showBulkRejectModal = true"
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-times mr-1"></i>
                            Reject Selected
                        </button>
                        <button @click="selectedAds = []; showBulkActions = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ads Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Advertisements</h3>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox"
                               @change="toggleSelectAll()"
                               :checked="isAllSelected()"
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="text-sm text-gray-600">Select All</span>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                    <span class="text-gray-600">Loading ads...</span>
                </div>
            </div>

            <!-- Ads List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" @change="toggleSelectAll()" :checked="isAllSelected()"
                                           class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ad Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Advertiser
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type & Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Performance
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Review Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Target Countries
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Target Social Circles
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="ad in ads.data" :key="ad.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               :value="ad.id"
                                               x-model="selectedAds"
                                               class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <!-- Ad Media Preview -->
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <template x-if="ad.media_files && ad.media_files.length > 0">
                                                    <img :src="getMediaThumbnail(ad.media_files[0])"
                                                         :alt="ad.ad_name"
                                                         class="h-12 w-12 rounded-lg object-cover">
                                                </template>
                                                <template x-if="!ad.media_files || ad.media_files.length === 0">
                                                    <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-ad text-gray-400"></i>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900" x-text="ad.ad_name"></div>
                                                <div class="text-sm text-gray-500 truncate max-w-xs" x-text="ad.description || 'No description'"></div>
                                                <div class="text-xs text-gray-400">
                                                    Campaign: <span x-text="formatDate(ad.start_date)"></span> - <span x-text="formatDate(ad.end_date)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900" x-text="ad.user ? ad.user.username : 'Unknown'"></div>
                                        <div class="text-sm text-gray-500" x-text="ad.user ? ad.user.email : 'N/A'"></div>
                                        <div class="text-xs text-gray-400" x-text="'Created: ' + formatDate(ad.created_at)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getTypeBadge(ad.type)"
                                              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              x-text="ad.type ? ad.type.charAt(0).toUpperCase() + ad.type.slice(1) : 'Unknown'"></span>
                                        <div class="mt-1">
                                            <span :class="getStatusBadge(ad.status)"
                                                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                  x-text="ad.status ? ad.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="text-sm font-medium" x-text="'Budget: $' + (ad.budget || '0')"></div>
                                        <div class="text-sm text-gray-500" x-text="'Spent: $' + (ad.total_spent || '0')"></div>
                                        <div class="text-sm text-gray-500" x-text="'Impressions: ' + (ad.current_impressions || '0')"></div>
                                        <div class="text-sm text-gray-500" x-text="'Clicks: ' + (ad.clicks || '0') + ' (CTR: ' + (ad.ctr || '0') + '%)'"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template x-if="ad.latest_payment">
                                            <div>
                                                <span :class="getPaymentStatusBadge(ad.latest_payment.status)"
                                                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                      x-text="ad.latest_payment.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                                <div class="text-sm text-gray-500" x-text="'$' + (ad.latest_payment.amount || '0')"></div>
                                            </div>
                                        </template>
                                        <template x-if="!ad.latest_payment">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">No Payment</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getAdminStatusBadge(ad.admin_status)"
                                              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              x-text="ad.admin_status ? ad.admin_status.charAt(0).toUpperCase() + ad.admin_status.slice(1) : 'Unknown'"></span>
                                        <template x-if="ad.reviewed_at">
                                            <div class="text-xs text-gray-400 mt-1" x-text="'Reviewed: ' + formatDate(ad.reviewed_at)"></div>
                                        </template>
                                        <template x-if="ad.admin_comments">
                                            <div class="text-xs text-gray-500 mt-1 truncate max-w-xs" x-text="ad.admin_comments"></div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template x-if="ad.target_countries_data && ad.target_countries_data.length > 0">
                                            <div class="space-y-1">
                                                <template x-for="country in ad.target_countries_data.slice(0, 3)" :key="country.id">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                                                          x-text="country.name"></span>
                                                </template>
                                                <template x-if="ad.target_countries_data.length > 3">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600"
                                                          x-text="`+${ad.target_countries_data.length - 3} more`"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!ad.target_countries_data || ad.target_countries_data.length === 0">
                                            <span class="text-xs text-gray-400">No targeting</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <template x-if="ad.placement_social_circles && ad.placement_social_circles.length > 0">
                                            <div class="space-y-1">
                                                <template x-for="circle in ad.placement_social_circles.slice(0, 3)" :key="circle.id">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                                                          x-text="circle.name"></span>
                                                </template>
                                                <template x-if="ad.placement_social_circles.length > 3">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600"
                                                          x-text="`+${ad.placement_social_circles.length - 3} more`"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!ad.placement_social_circles || ad.placement_social_circles.length === 0">
                                            <span class="text-xs text-gray-400">No targeting</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open"
                                                    class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open"
                                                 @click.away="open = false"
                                                 x-transition
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                                <div class="py-1">
                                                    <a :href="`/admin/ads/${ad.id}`"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-eye mr-2"></i>
                                                        View Details
                                                    </a>

                                                    <template x-if="ad.admin_status === 'pending' && ad.latest_payment && ad.latest_payment.status === 'completed'">
                                                        <button @click="approveAd(ad.id)"
                                                                class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-gray-100">
                                                            <i class="fas fa-check mr-2"></i>
                                                            Approve Ad
                                                        </button>
                                                    </template>

                                                    <template x-if="ad.admin_status === 'pending'">
                                                        <button @click="rejectAd(ad.id)"
                                                                class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                            <i class="fas fa-times mr-2"></i>
                                                            Reject Ad
                                                        </button>
                                                    </template>

                                                    <template x-if="ad.status === 'active'">
                                                        <button @click="pauseAd(ad.id)"
                                                                class="block w-full text-left px-4 py-2 text-sm text-yellow-700 hover:bg-gray-100">
                                                            <i class="fas fa-pause mr-2"></i>
                                                            Pause Ad
                                                        </button>
                                                    </template>

                                                    <template x-if="ad.status === 'paused'">
                                                        <button @click="resumeAd(ad.id)"
                                                                class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-gray-100">
                                                            <i class="fas fa-play mr-2"></i>
                                                            Resume Ad
                                                        </button>
                                                    </template>

                                                    <template x-if="ad.status === 'active' || ad.status === 'paused'">
                                                        <button @click="stopAd(ad.id)"
                                                                class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                            <i class="fas fa-stop mr-2"></i>
                                                            Stop Ad
                                                        </button>
                                                    </template>

                                                    <div class="border-t border-gray-100"></div>
                                                    <button @click="deleteAd(ad.id)"
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                        <i class="fas fa-trash mr-2"></i>
                                                        Delete Ad
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <button @click="changePage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                                Previous
                            </button>
                            <button @click="changePage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium" x-text="pagination.from || 0"></span> to
                                    <span class="font-medium" x-text="pagination.to || 0"></span> of
                                    <span class="font-medium" x-text="pagination.total || 0"></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button @click="changePage(pagination.current_page - 1)"
                                            :disabled="pagination.current_page <= 1"
                                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>

                                    <template x-for="page in getPageNumbers()" :key="page">
                                        <button @click="changePage(page)"
                                                :class="page === pagination.current_page ? 'bg-primary text-white border-primary' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'"
                                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                x-text="page">
                                        </button>
                                    </template>

                                    <button @click="changePage(pagination.current_page + 1)"
                                            :disabled="pagination.current_page >= pagination.last_page"
                                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-show="showRejectModal || showBulkRejectModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <span x-show="showRejectModal">Reject Advertisement</span>
                            <span x-show="showBulkRejectModal" x-text="`Reject ${selectedAds.length} Advertisements`"></span>
                        </h3>
                        <button @click="showRejectModal = false; showBulkRejectModal = false; rejectReason = ''"
                                class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mb-4">
                        <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for rejection <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reject_reason"
                                  x-model="rejectReason"
                                  rows="4"
                                  placeholder="Please provide a detailed reason for rejecting this ad..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button @click="showRejectModal = false; showBulkRejectModal = false; rejectReason = ''"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmReject()"
                                :disabled="!rejectReason.trim()"
                                :class="!rejectReason.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-700'"
                                class="px-4 py-2 bg-red-600 text-white rounded-md transition-colors">
                            Reject Ad<span x-show="showBulkRejectModal">s</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    function adManagement() {
        return {
            ads: { data: [], total: 0, current_page: 1, last_page: 1 },
            stats: {},
            pagination: {},
            loading: false,
            selectedAds: [],
            showBulkActions: false,
            showRejectModal: false,
            showBulkRejectModal: false,
            rejectAdId: null,
            rejectReason: '',
            countries: [],
            socialCircles: [],
            filters: {
                search: '',
                type: '',
                status: '',
                admin_status: '',
                payment_status: '',
                country: '',
                social_circle: '',
                date_from: '',
                date_to: ''
            },
            searchTimeout: null,

            async loadAds(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/ads?${params}`, {
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

                    this.ads = data.ads || data;
                    this.pagination = {
                        current_page: this.ads.current_page || 1,
                        last_page: this.ads.last_page || 1,
                        from: this.ads.from || 0,
                        to: this.ads.to || 0,
                        total: this.ads.total || 0
                    };
                    this.selectedAds = [];
                } catch (error) {
                    console.error('Failed to load ads:', error);
                    this.showError('Failed to load ads: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async loadStats() {
                try {
                    const response = await fetch('/admin/api/ads/stats', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();
                    this.stats = data;
                } catch (error) {
                    console.error('Failed to load stats:', error);
                    this.stats = {};
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadAds();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadAds(page);
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
                    this.selectedAds = [];
                } else {
                    this.selectedAds = this.ads.data.map(ad => ad.id);
                }
            },

            isAllSelected() {
                return this.ads.data && this.ads.data.length > 0 && this.selectedAds.length === this.ads.data.length;
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    type: '',
                    status: '',
                    admin_status: '',
                    payment_status: '',
                    country: '',
                    social_circle: '',
                    date_from: '',
                    date_to: ''
                };
                this.loadAds();
            },

            setDateRange(preset) {
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);

                const startOfWeek = new Date(today);
                const day = startOfWeek.getDay();
                const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1);
                startOfWeek.setDate(diff);

                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

                switch (preset) {
                    case 'today':
                        this.filters.date_from = this.formatDateForInput(today);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                    case 'yesterday':
                        this.filters.date_from = this.formatDateForInput(yesterday);
                        this.filters.date_to = this.formatDateForInput(yesterday);
                        break;
                    case 'this_week':
                        this.filters.date_from = this.formatDateForInput(startOfWeek);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                    case 'this_month':
                        this.filters.date_from = this.formatDateForInput(startOfMonth);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                }
                this.loadAds();
            },

            formatDateForInput(date) {
                return date.toISOString().split('T')[0];
            },

            async loadCountries() {
                try {
                    const response = await fetch('/admin/api/countries', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.countries = data.countries || [];
                    }
                } catch (error) {
                    console.error('Failed to load countries:', error);
                }
            },

            async loadSocialCircles() {
                try {
                    const response = await fetch('/admin/api/social-circles', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.socialCircles = data.social_circles || [];
                    }
                } catch (error) {
                    console.error('Failed to load social circles:', error);
                }
            },

            getTypeBadge(type) {
                const badges = {
                    'banner': 'bg-blue-100 text-blue-800',
                    'video': 'bg-purple-100 text-purple-800',
                    'carousel': 'bg-green-100 text-green-800',
                    'story': 'bg-yellow-100 text-yellow-800',
                    'feed': 'bg-indigo-100 text-indigo-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            },

            getStatusBadge(status) {
                const badges = {
                    'draft': 'bg-gray-100 text-gray-800',
                    'pending_review': 'bg-yellow-100 text-yellow-800',
                    'active': 'bg-green-100 text-green-800',
                    'paused': 'bg-orange-100 text-orange-800',
                    'stopped': 'bg-red-100 text-red-800',
                    'rejected': 'bg-red-100 text-red-800',
                    'completed': 'bg-blue-100 text-blue-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },

            getAdminStatusBadge(adminStatus) {
                const badges = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'approved': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return badges[adminStatus] || 'bg-gray-100 text-gray-800';
            },

            getPaymentStatusBadge(paymentStatus) {
                const badges = {
                    'completed': 'bg-green-100 text-green-800',
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'processing': 'bg-blue-100 text-blue-800',
                    'failed': 'bg-red-100 text-red-800',
                    'cancelled': 'bg-gray-100 text-gray-800',
                    'refunded': 'bg-purple-100 text-purple-800'
                };
                return badges[paymentStatus] || 'bg-gray-100 text-gray-800';
            },

            getMediaThumbnail(media) {
                if (typeof media === 'string') {
                    return media;
                }
                if (media && media.thumbnail_path) {
                    return media.thumbnail_path;
                }
                if (media && media.file_path) {
                    return media.file_path;
                }
                return '/images/placeholder.jpg';
            },

            async approveAd(adId) {
                if (!confirm('Are you sure you want to approve this ad?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/ads/${adId}/approve`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            admin_comments: 'Approved by admin'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error approving ad:', error);
                    this.showError('Failed to approve ad');
                }
            },

            rejectAd(adId) {
                this.rejectAdId = adId;
                this.showRejectModal = true;
                this.rejectReason = '';
            },

            async confirmReject() {
                if (!this.rejectReason.trim()) {
                    this.showError('Please provide a reason for rejection');
                    return;
                }

                try {
                    if (this.showBulkRejectModal) {
                        await this.bulkRejectAds();
                    } else {
                        await this.rejectSingleAd(this.rejectAdId);
                    }
                } catch (error) {
                    console.error('Error rejecting ad(s):', error);
                    this.showError('Failed to reject ad(s)');
                } finally {
                    this.showRejectModal = false;
                    this.showBulkRejectModal = false;
                    this.rejectReason = '';
                    this.rejectAdId = null;
                }
            },

            async rejectSingleAd(adId) {
                const response = await fetch(`/admin/ads/${adId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        admin_comments: this.rejectReason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message);
                    this.loadAds(this.pagination.current_page);
                    this.loadStats();
                } else {
                    this.showError(data.message);
                }
            },

            bulkApprove() {
                if (this.selectedAds.length === 0) {
                    return;
                }

                if (!confirm(`Are you sure you want to approve ${this.selectedAds.length} selected ads?`)) {
                    return;
                }

                this.bulkApproveAds();
            },

            async bulkApproveAds() {
                try {
                    const response = await fetch('/admin/api/ads/bulk-approve', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ad_ids: this.selectedAds,
                            admin_comments: 'Bulk approved by admin'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                        this.selectedAds = [];
                        this.showBulkActions = false;
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error bulk approving ads:', error);
                    this.showError('Failed to approve ads');
                }
            },

            async bulkRejectAds() {
                const response = await fetch('/admin/api/ads/bulk-reject', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ad_ids: this.selectedAds,
                        admin_comments: this.rejectReason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message);
                    this.loadAds(this.pagination.current_page);
                    this.loadStats();
                    this.selectedAds = [];
                    this.showBulkActions = false;
                } else {
                    this.showError(data.message);
                }
            },

            async pauseAd(adId) {
                if (!confirm('Are you sure you want to pause this ad?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/ads/${adId}/pause`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error pausing ad:', error);
                    this.showError('Failed to pause ad');
                }
            },

            async resumeAd(adId) {
                if (!confirm('Are you sure you want to resume this ad?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/ads/${adId}/resume`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error resuming ad:', error);
                    this.showError('Failed to resume ad');
                }
            },

            async stopAd(adId) {
                if (!confirm('Are you sure you want to stop this ad? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/ads/${adId}/stop`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error stopping ad:', error);
                    this.showError('Failed to stop ad');
                }
            },

            async deleteAd(adId) {
                if (!confirm('Are you sure you want to delete this ad? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/ads/${adId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadAds(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error deleting ad:', error);
                    this.showError('Failed to delete ad');
                }
            },

            async exportAds() {
                try {
                    const response = await fetch('/admin/ads/export', {
                        method: 'GET',
                    });

                    if (response.ok) {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `ads_export_${new Date().toISOString().slice(0, 10)}.csv`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        this.showSuccess('Ads exported successfully');
                    } else {
                        this.showError('Failed to export ads');
                    }
                } catch (error) {
                    console.error('Error exporting ads:', error);
                    this.showError('Failed to export ads');
                }
            },

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            },

            showSuccess(message) {
                // You can implement a toast notification here
                alert(message);
            },

            showError(message) {
                // You can implement a toast notification here
                alert('Error: ' + message);
            }
        }
    }
</script>
@endpush
