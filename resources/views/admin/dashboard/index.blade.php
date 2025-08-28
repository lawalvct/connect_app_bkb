@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Welcome back, {{ auth('admin')->user()->name }}</p>
        </div>
        <div class="text-sm text-gray-500 bg-white px-4 py-2 rounded-lg shadow">
            <i class="fas fa-clock mr-2"></i>
            Last login: {{ auth('admin')->user()->last_login_at ? auth('admin')->user()->last_login_at->format('M d, Y H:i') : 'Never' }}
        </div>
    </div>
@endsection

@section('content')
    <div x-data="dashboardData()" x-init="loadData()">

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Total Users -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Users</p>
                        <p class="text-3xl font-bold" x-text="stats.totalUsers || '0'">0</p>
                        <div class="flex items-center mt-2">
                            <span class="text-blue-100 text-sm" x-text="stats.usersGrowth || '0%'">0%</span>
                            <span class="text-blue-100 text-xs ml-1">growth</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-blue-400 bg-opacity-30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-blue-100 text-sm">
                    <i class="fas fa-user-check mr-1"></i>
                    <span x-text="stats.activeUsers || '0'">0</span> active users
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Total Revenue</p>
                        <p class="text-3xl font-bold" x-text="'$' + (stats.totalRevenue || '0')">$0</p>
                        <div class="flex items-center mt-2">
                            <span class="text-green-100 text-sm" x-text="stats.revenueGrowth || '0%'">0%</span>
                            <span class="text-green-100 text-xs ml-1">growth</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-green-400 bg-opacity-30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-green-100 text-sm">
                    <i class="fas fa-calendar-check mr-1"></i>
                    $<span x-text="stats.monthlyRevenue || '0'">0</span> this month
                </div>
            </div>

            <!-- Live Streams -->
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Live Streams</p>
                        <p class="text-3xl font-bold" x-text="stats.liveStreams || '0'">0</p>
                        <div class="flex items-center mt-2">
                            <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse mr-2"></div>
                            <span class="text-purple-100 text-xs">streaming now</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-purple-400 bg-opacity-30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-video text-2xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-purple-100 text-sm">
                    <i class="fas fa-eye mr-1"></i>
                    <span x-text="stats.totalViewers || '0'">0</span> total viewers
                </div>
            </div>

            <!-- Active Stories -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Active Stories</p>
                        <p class="text-3xl font-bold" x-text="stats.activeStories || '0'">0</p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-clock text-orange-200 mr-1"></i>
                            <span class="text-orange-100 text-xs">24h expiry</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-orange-400 bg-opacity-30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-orange-100 text-sm">
                    <i class="fas fa-eye mr-1"></i>
                    <span x-text="stats.storyViews || '0'">0</span> total views
                </div>
            </div>

        </div>

        <!-- Secondary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-400">
                <div class="text-2xl font-bold text-gray-900" x-text="stats.totalPosts || '0'">0</div>
                <div class="text-sm text-gray-600">Total Posts</div>
                <div class="text-xs text-blue-600" x-text="stats.postsGrowth || '0%'">0% growth</div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-400">
                <div class="text-2xl font-bold text-gray-900" x-text="stats.activeSubscriptions || '0'">0</div>
                <div class="text-sm text-gray-600">Subscriptions</div>
                <div class="text-xs text-green-600">Active</div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-400">
                <div class="text-2xl font-bold text-gray-900" x-text="stats.activeAds || '0'">0</div>
                <div class="text-sm text-gray-600">Active Ads</div>
                <div class="text-xs text-yellow-600" x-text="stats.pendingAds || '0'">0 pending</div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-purple-400">
                <div class="text-2xl font-bold text-gray-900" x-text="stats.totalStreams || '0'">0</div>
                <div class="text-sm text-gray-600">Total Streams</div>
                <div class="text-xs text-purple-600" x-text="stats.scheduledStreams || '0'">0 scheduled</div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-indigo-400">
                <div class="text-2xl font-bold text-gray-900" x-text="stats.verifiedUsers || '0'">0</div>
                <div class="text-sm text-gray-600">Verified Users</div>
                <div class="text-xs text-indigo-600">ID Verified</div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-pink-400">
                <div class="text-2xl font-bold text-gray-900" x-text="'$' + (stats.adsRevenue || '0')">$0</div>
                <div class="text-sm text-gray-600">Ads Revenue</div>
                <div class="text-xs text-pink-600">Total Budget</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <!-- Revenue Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Revenue Trends</h3>
                        <p class="text-gray-600 text-sm">Daily subscription revenue over time</p>
                    </div>
                    <select class="text-sm border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            x-model="chartPeriod" @change="updateChart()">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- User Activity Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">User Activity</h3>
                        <p class="text-gray-600 text-sm">New registrations vs active users</p>
                    </div>
                    <div class="flex space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                            New Users
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                            Active Users
                        </span>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="userChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Additional Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <!-- Content Activity Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Content Activity</h3>
                        <p class="text-gray-600 text-sm">Posts, stories, and streams over time</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="contentChart"></canvas>
                </div>
            </div>

            <!-- Engagement Overview -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Platform Overview</h3>
                        <p class="text-gray-600 text-sm">Distribution of content types</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Recent Activity and Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Activity -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900">Recent Activity</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-sm text-gray-600">Live Feed</span>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    <template x-for="activity in recentActivity" :key="activity.id">
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm"
                                         :class="getActivityIconBg(activity.type)">
                                        <i :class="getActivityIcon(activity.type)" class="text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900" x-text="activity.description"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="activity.time_ago"></p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium capitalize"
                                          :class="getActivityBadge(activity.type)" x-text="activity.type.replace('_', ' ')"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div x-show="recentActivity.length === 0" class="p-12 text-center text-gray-500">
                        <i class="fas fa-clock text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg font-medium">No recent activity</p>
                        <p class="text-sm">Activity will appear here as it happens</p>
                    </div>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900">Quick Actions</h3>
                    <p class="text-gray-600 text-sm">Manage your platform efficiently</p>
                </div>
                <div class="p-6 space-y-4">

                    @can('manage-users')
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-900">Manage Users</p>
                            <p class="text-xs text-gray-600">View and manage user accounts</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600"></i>
                        </div>
                    </a>
                    @endcan

                    @can('manage-posts')
                    <a href="{{ route('admin.posts.index') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-file-alt text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-green-900">Review Posts</p>
                            <p class="text-xs text-gray-600">Moderate user posts and content</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-600"></i>
                        </div>
                    </a>
                    @endcan

                    @can('manage-streams')
                    <a href="{{ route('admin.streams.index') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-video text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-purple-900">Manage Streams</p>
                            <p class="text-xs text-gray-600">Monitor live streams and broadcasts</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-purple-600"></i>
                        </div>
                    </a>
                    @endcan

                    @can('manage-ads')
                    <a href="{{ route('admin.ads.index') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-bullhorn text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-yellow-900">Manage Ads</p>
                            <p class="text-xs text-gray-600">Review and approve advertisements</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-yellow-600"></i>
                        </div>
                    </a>
                    @endcan

                    @can('view-analytics')
                    <a href="{{ route('admin.analytics') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-chart-bar text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-900">View Analytics</p>
                            <p class="text-xs text-gray-600">Detailed reports and insights</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-indigo-600"></i>
                        </div>
                    </a>
                    @endcan

                    @can('manage-settings')
                    <a href="{{ route('admin.settings') }}"
                       class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 rounded-lg transition-all duration-200 group shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-cog text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-gray-900">System Settings</p>
                            <p class="text-xs text-gray-600">Configure application settings</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-gray-600"></i>
                        </div>
                    </a>
                    @endcan

                </div>
            </div>

        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function dashboardData() {
        return {
            stats: {},
            recentActivity: [],
            chartPeriod: '30',
            revenueChart: null,
            userChart: null,
            contentChart: null,
            engagementChart: null,

            async loadData() {
                try {
                    const response = await fetch('/admin/api/dashboard-data');
                    const data = await response.json();

                    this.stats = data.stats;
                    this.recentActivity = data.recent_activity || [];

                    this.$nextTick(() => {
                        this.initCharts(data.charts);
                    });
                } catch (error) {
                    console.error('Failed to load dashboard data:', error);
                    this.handleError('Failed to load dashboard data');
                }
            },

            async updateChart() {
                try {
                    const response = await fetch(`/admin/api/dashboard-charts?period=${this.chartPeriod}`);
                    const data = await response.json();

                    this.updateRevenueChart(data.revenue);
                } catch (error) {
                    console.error('Failed to update chart:', error);
                    this.handleError('Failed to update chart data');
                }
            },

            initCharts(chartData) {
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                this.revenueChart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.revenue?.labels || [],
                        datasets: [{
                            label: 'Revenue ($)',
                            data: chartData.revenue?.data || [],
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: $' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    },
                                    color: '#6B7280'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: '#6B7280'
                                }
                            }
                        }
                    }
                });

                // User Activity Chart
                const userCtx = document.getElementById('userChart').getContext('2d');
                this.userChart = new Chart(userCtx, {
                    type: 'bar',
                    data: {
                        labels: chartData.users?.labels || [],
                        datasets: [
                            {
                                label: 'New Users',
                                data: chartData.users?.new_users || [],
                                backgroundColor: '#3B82F6',
                                borderRadius: 6,
                                borderSkipped: false,
                            },
                            {
                                label: 'Active Users',
                                data: chartData.users?.active_users || [],
                                backgroundColor: '#10B981',
                                borderRadius: 6,
                                borderSkipped: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: '#6B7280'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6B7280'
                                }
                            }
                        }
                    }
                });

                // Content Activity Chart
                const contentCtx = document.getElementById('contentChart').getContext('2d');
                this.contentChart = new Chart(contentCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [
                            {
                                label: 'Posts',
                                data: chartData.posts || [],
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 4
                            },
                            {
                                label: 'Stories',
                                data: chartData.stories?.data || [],
                                borderColor: '#F59E0B',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 4
                            },
                            {
                                label: 'Streams',
                                data: chartData.streams?.data || [],
                                borderColor: '#8B5CF6',
                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 20,
                                    color: '#6B7280'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: '#6B7280'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: '#6B7280'
                                }
                            }
                        }
                    }
                });

                // Engagement Chart (Doughnut)
                const engagementCtx = document.getElementById('engagementChart').getContext('2d');
                this.engagementChart = new Chart(engagementCtx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.engagement?.labels || [],
                        datasets: [{
                            data: chartData.engagement?.data || [],
                            backgroundColor: [
                                '#3B82F6',  // Blue for Posts
                                '#F59E0B',  // Yellow for Stories
                                '#8B5CF6',  // Purple for Streams
                                '#10B981'   // Green for Ads
                            ],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    boxWidth: 12,
                                    color: '#6B7280'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            },

            updateRevenueChart(data) {
                if (this.revenueChart) {
                    this.revenueChart.data.labels = data.labels;
                    this.revenueChart.data.datasets[0].data = data.data;
                    this.revenueChart.update('active');
                }
            },

            handleError(message) {
                // Show error notification - you can customize this
                console.error(message);
                // You could show a toast notification here
            },

            getActivityIcon(type) {
                const icons = {
                    'user_registered': 'fas fa-user-plus text-blue-600',
                    'post_created': 'fas fa-file-alt text-green-600',
                    'ad_approved': 'fas fa-check-circle text-green-600',
                    'ad_rejected': 'fas fa-times-circle text-red-600',
                    'ad_submitted': 'fas fa-clock text-yellow-600',
                    'payment_received': 'fas fa-dollar-sign text-green-600',
                    'stream_started': 'fas fa-video text-purple-600',
                    'user_reported': 'fas fa-flag text-red-600'
                };
                return icons[type] || 'fas fa-info-circle text-gray-600';
            },

            getActivityIconBg(type) {
                const backgrounds = {
                    'user_registered': 'bg-blue-100',
                    'post_created': 'bg-green-100',
                    'ad_approved': 'bg-green-100',
                    'ad_rejected': 'bg-red-100',
                    'ad_submitted': 'bg-yellow-100',
                    'payment_received': 'bg-green-100',
                    'stream_started': 'bg-purple-100',
                    'user_reported': 'bg-red-100'
                };
                return backgrounds[type] || 'bg-gray-100';
            },

            getActivityBadge(type) {
                const badges = {
                    'user_registered': 'bg-blue-100 text-blue-800 border border-blue-200',
                    'post_created': 'bg-green-100 text-green-800 border border-green-200',
                    'ad_approved': 'bg-green-100 text-green-800 border border-green-200',
                    'ad_rejected': 'bg-red-100 text-red-800 border border-red-200',
                    'ad_submitted': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                    'payment_received': 'bg-green-100 text-green-800 border border-green-200',
                    'stream_started': 'bg-purple-100 text-purple-800 border border-purple-200',
                    'user_reported': 'bg-red-100 text-red-800 border border-red-200'
                };
                return badges[type] || 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }
    }

    // Auto refresh data every 30 seconds
    setInterval(() => {
        if (window.dashboardInstance) {
            window.dashboardInstance.loadData();
        }
    }, 30000);
</script>
@endpush
