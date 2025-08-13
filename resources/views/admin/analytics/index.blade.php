@extends('admin.layouts.app')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')

@section('content')
<div class="min-h-screen bg-background">
    <!-- Page Header -->
    <div class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-8">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h1  class="text-xl font-semibold text-gray-900">
                            📊 Analytics Dashboard
                        </h1>
                        <p class="text-gray-600">
                            Complete platform insights and performance metrics
                        </p>
                    </div>
                    <div class="mt-6 flex md:mt-0 md:ml-6">
                        <!-- Date Range Picker -->
                        <form method="GET" class="flex space-x-4">
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-700 mb-1">From</label>
                                <input type="date" name="start_date" value="{{ $startDate }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-700 mb-1">To</label>
                                <input type="date" name="end_date" value="{{ $endDate }}"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            </div>
                            <div class="flex flex-col justify-end">
                                <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-lg shadow-sm text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-filter mr-2"></i>
                                    Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Quick Stats Cards -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📈 Platform Overview</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Users -->
                <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-xl rounded-2xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <i class="fas fa-users text-3xl text-white"></i>
                                </div>
                            </div>
                            <div class="ml-6 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-blue-100 truncate">Total Users</dt>
                                    <dd class="text-3xl font-bold text-white">{{ number_format($stats['total_users']) }}</dd>
                                    <dd class="text-sm text-blue-100 flex items-center">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        +{{ number_format($stats['new_users_today']) }} today
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 right-0 transform translate-x-8 translate-y-8">
                        <i class="fas fa-users text-8xl text-white opacity-10"></i>
                    </div>
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                </div>

                <!-- Total Posts -->
                <div class="relative bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-xl rounded-2xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <i class="fas fa-newspaper text-3xl text-white"></i>
                                </div>
                            </div>
                            <div class="ml-6 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-green-100 truncate">Content Posts</dt>
                                    <dd class="text-3xl font-bold text-white">{{ number_format($stats['total_posts']) }}</dd>
                                    <dd class="text-sm text-green-100 flex items-center">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ number_format($stats['total_stories']) }} stories
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 right-0 transform translate-x-8 translate-y-8">
                        <i class="fas fa-newspaper text-8xl text-white opacity-10"></i>
                    </div>
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                </div>

                <!-- Active Subscriptions -->
                <div class="relative bg-gradient-to-br from-yellow-500 to-yellow-600 overflow-hidden shadow-xl rounded-2xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <i class="fas fa-crown text-3xl text-white"></i>
                                </div>
                            </div>
                            <div class="ml-6 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-yellow-100 truncate">Premium Users</dt>
                                    <dd class="text-3xl font-bold text-white">{{ number_format($stats['active_subscriptions']) }}</dd>
                                    <dd class="text-sm text-yellow-100 flex items-center">
                                        <i class="fas fa-star mr-1"></i>
                                        Active subscriptions
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 right-0 transform translate-x-8 translate-y-8">
                        <i class="fas fa-crown text-8xl text-white opacity-10"></i>
                    </div>
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                </div>

                <!-- Total Revenue -->
                <div class="relative bg-gradient-to-br from-primary to-red-700 overflow-hidden shadow-xl rounded-2xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <i class="fas fa-dollar-sign text-3xl text-white"></i>
                                </div>
                            </div>
                            <div class="ml-6 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-red-100 truncate">Total Revenue</dt>
                                    <dd class="text-3xl font-bold text-white">${{ number_format($stats['total_revenue'], 2) }}</dd>
                                    <dd class="text-sm text-red-100 flex items-center">
                                        <i class="fas fa-ad mr-1"></i>
                                        {{ number_format($stats['active_ads']) }} active ads
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 right-0 transform translate-x-8 translate-y-8">
                        <i class="fas fa-dollar-sign text-8xl text-white opacity-10"></i>
                    </div>
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📊 Performance Trends</h2>
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- User Growth Chart -->
                <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-line text-blue-600"></i>
                                </div>
                                User Growth
                            </h3>
                            <p class="text-sm text-gray-600 mt-2">New user registrations over time</p>
                        </div>
                        @if($userGrowth->isEmpty())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                No Data
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                {{ $userGrowth->count() }} days
                            </span>
                        @endif
                    </div>
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                    @if($userGrowth->isEmpty())
                        <div class="text-center mt-6 p-6 bg-gray-50 rounded-xl">
                            <i class="fas fa-chart-line text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-500">No user registration data available for the selected period.</p>
                            <button onclick="window.location.href='{{ route('admin.users.index') }}'" class="mt-3 inline-flex items-center px-4 py-2 text-sm font-medium text-primary bg-primary-light rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-users mr-2"></i>
                                Manage Users
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Revenue Trends Chart -->
                <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-bar text-green-600"></i>
                                </div>
                                Revenue Trends
                            </h3>
                            <p class="text-sm text-gray-600 mt-2">Daily revenue from subscriptions</p>
                        </div>
                        @if($revenueData->isEmpty())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                No Data
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                {{ $revenueData->count() }} days
                            </span>
                        @endif
                    </div>
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="revenueTrendsChart"></canvas>
                    </div>
                    @if($revenueData->isEmpty())
                        <div class="text-center mt-6 p-6 bg-gray-50 rounded-xl">
                            <i class="fas fa-dollar-sign text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-500">No revenue data available for the selected period.</p>
                            <button onclick="window.location.href='{{ route('admin.subscriptions.index') }}'" class="mt-3 inline-flex items-center px-4 py-2 text-sm font-medium text-primary bg-primary-light rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-crown mr-2"></i>
                                Manage Subscriptions
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>



        <!-- Quick Action Cards & Popular Countries Section -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Quick Actions -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">⚡ Quick Actions</h2>
                <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <button onclick="window.location.href='{{ route('admin.users.index') }}'" class="flex items-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">User Management</div>
                                <div class="text-sm text-gray-600">Manage all users</div>
                            </div>
                        </button>

                        <button onclick="window.location.href='{{ route('admin.posts.index') }}'" class="flex items-center p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-newspaper text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Content Posts</div>
                                <div class="text-sm text-gray-600">Manage posts & stories</div>
                            </div>
                        </button>

                        <button onclick="window.location.href='{{ route('admin.subscriptions.index') }}'" class="flex items-center p-4 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-crown text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Subscriptions</div>
                                <div class="text-sm text-gray-600">Premium plans</div>
                            </div>
                        </button>

                        <button onclick="window.location.href='{{ route('admin.ads.index') }}'" class="flex items-center p-4 bg-red-50 rounded-xl hover:bg-red-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-ad text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Advertisements</div>
                                <div class="text-sm text-gray-600">Manage ads</div>
                            </div>
                        </button>

                        <button onclick="window.location.href='{{ route('admin.streams.index') }}'" class="flex items-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-video text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Live Streams</div>
                                <div class="text-sm text-gray-600">Streaming management</div>
                            </div>
                        </button>

                        <button onclick="window.location.href='{{ route('admin.notifications.push.index') }}'" class="flex items-center p-4 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-bell text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Notifications</div>
                                <div class="text-sm text-gray-600">Send notifications</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Popular Countries -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🌍 Top Countries</h2>
                <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100">
                    <div class="space-y-4">
                        @forelse($popularCountries as $index => $country)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                                        <span class="text-white font-bold text-sm">#{{ $index + 1 }}</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $country->country ?? 'Unknown' }}</div>
                                        <div class="text-sm text-gray-600">{{ number_format($country->user_count) }} users</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-gradient-to-r from-primary to-red-600 h-2 rounded-full" style="width: {{ min(100, ($country->user_count / $popularCountries->first()->user_count) * 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">{{ number_format(($country->user_count / $popularCountries->sum('user_count')) * 100, 1) }}%</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <i class="fas fa-globe text-4xl text-gray-400 mb-3"></i>
                                <p class="text-sm text-gray-500">No country data available</p>
                            </div>
                        @endforelse
                    </div>
                    @if($popularCountries->isNotEmpty())
                        <div class="mt-6 text-center">
                            <button onclick="window.location.href='{{ route('admin.users.index') }}'" class="inline-flex items-center px-6 py-3 text-sm font-medium text-primary bg-primary-light rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-users mr-2"></i>
                                View All Users
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content Engagement Overview -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🎯 Content Engagement</h2>
            <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100">
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="text-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors duration-200">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-heart text-white text-xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-blue-600">{{ number_format($contentEngagement['total_likes']) }}</div>
                        <div class="text-sm text-gray-600 mt-1">Total Likes</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-colors duration-200">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-comments text-white text-xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-green-600">{{ number_format($contentEngagement['total_comments']) }}</div>
                        <div class="text-sm text-gray-600 mt-1">Comments</div>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors duration-200">
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-share text-white text-xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-purple-600">{{ number_format($contentEngagement['total_shares']) }}</div>
                        <div class="text-sm text-gray-600 mt-1">Shares</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition-colors duration-200">
                        <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-eye text-white text-xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-yellow-600">{{ number_format($contentEngagement['total_views']) }}</div>
                        <div class="text-sm text-gray-600 mt-1">Post Views</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-xl hover:bg-red-100 transition-colors duration-200">
                        <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-clock text-white text-xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-red-600">{{ number_format($contentEngagement['story_views']) }}</div>
                        <div class="text-sm text-gray-600 mt-1">Story Views</div>
                    </div>
                </div>
                <div class="mt-6 flex justify-center">
                    <button onclick="window.location.href='{{ route('admin.posts.index') }}'" class="inline-flex items-center px-6 py-3 text-sm font-medium text-primary bg-primary-light rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                        <i class="fas fa-newspaper mr-2"></i>
                        Manage Content
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
<script>
    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // User Growth Chart
        const userCtx = document.getElementById('userGrowthChart');
        if (userCtx) {
            new Chart(userCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($userGrowth->pluck('date')->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))) !!},
                    datasets: [{
                        label: 'New Users',
                        data: {!! json_encode($userGrowth->pluck('count')) !!},
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14,
                                family: 'Inter'
                            },
                            bodyFont: {
                                size: 13,
                                family: 'Inter'
                            },
                            cornerRadius: 8,
                            padding: 12
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        // Revenue Trends Chart
        const revenueCtx = document.getElementById('revenueTrendsChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($revenueData->pluck('date')->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))) !!},
                    datasets: [{
                        label: 'Revenue ($)',
                        data: {!! json_encode($revenueData->pluck('revenue')) !!},
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: '#22C55E',
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                },
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14,
                                family: 'Inter'
                            },
                            bodyFont: {
                                size: 13,
                                family: 'Inter'
                            },
                            cornerRadius: 8,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }
    });
</script>
@endpush

@endsection
