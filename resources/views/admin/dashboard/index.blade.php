@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Welcome back, {{ auth('admin')->user()->name }}</p>
        </div>
        <div class="text-sm text-gray-500">
            Last login: {{ auth('admin')->user()->last_login_at ? auth('admin')->user()->last_login_at->format('M d, Y H:i') : 'Never' }}
        </div>
    </div>
@endsection

@section('content')
    <div x-data="dashboardData()" x-init="loadData()">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.totalUsers || '0'">0</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-sm text-green-600" x-text="stats.usersGrowth || '0%'">0%</span>
                    <span class="text-sm text-gray-500">vs last month</span>
                </div>
            </div>

            <!-- Active Posts -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-alt text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Posts</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.activePosts || '0'">0</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-sm text-green-600" x-text="stats.postsGrowth || '0%'">0%</span>
                    <span class="text-sm text-gray-500">vs last month</span>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="'$' + (stats.totalRevenue || '0')">$0</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-sm text-green-600" x-text="stats.revenueGrowth || '0%'">0%</span>
                    <span class="text-sm text-gray-500">vs last month</span>
                </div>
            </div>

            <!-- Active Streams -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-primary">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-video text-primary"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Live Streams</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.activeStreams || '0'">0</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-sm" :class="stats.streamsChange >= 0 ? 'text-green-600' : 'text-red-600'" x-text="stats.streamsChange || '0'">0</span>
                    <span class="text-sm text-gray-500">streams today</span>
                </div>
            </div>

        </div>

        <!-- Charts and Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Revenue Overview</h3>
                    <select class="text-sm border border-gray-300 rounded-md px-3 py-1 focus:outline-none focus:ring-primary focus:border-primary"
                            x-model="chartPeriod" @change="updateChart()">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- User Activity Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">User Activity</h3>
                    <div class="flex space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <span class="w-2 h-2 bg-blue-600 rounded-full mr-1"></span>
                            New Users
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-1"></span>
                            Active Users
                        </span>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="userChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Recent Activity and Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Activity -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                </div>
                <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                    <template x-for="activity in recentActivity" :key="activity.id">
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                         :class="getActivityIconBg(activity.type)">
                                        <i :class="getActivityIcon(activity.type)" class="text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900" x-text="activity.description"></p>
                                    <p class="text-xs text-gray-500" x-text="activity.time_ago"></p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                          :class="getActivityBadge(activity.type)" x-text="activity.type"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div x-show="recentActivity.length === 0" class="p-6 text-center text-gray-500">
                        <i class="fas fa-clock text-2xl mb-2"></i>
                        <p>No recent activity</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">

                    @can('manage-users')
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors group">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900">Manage Users</p>
                            <p class="text-xs text-gray-500">View and manage user accounts</p>
                        </div>
                    </a>
                    @endcan

                    @can('manage-posts')
                    <a href="{{ route('admin.posts.index') }}"
                       class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-md transition-colors group">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-green-900">Review Posts</p>
                            <p class="text-xs text-gray-500">Moderate user posts</p>
                        </div>
                    </a>
                    @endcan

                    @can('manage-ads')
                    <a href="{{ route('admin.ads.index') }}"
                       class="flex items-center p-3 bg-yellow-50 hover:bg-yellow-100 rounded-md transition-colors group">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ad text-yellow-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-yellow-900">Manage Ads</p>
                            <p class="text-xs text-gray-500">Review and approve ads</p>
                        </div>
                    </a>
                    @endcan

                    @can('view-analytics')
                    <a href="{{ route('admin.analytics') }}"
                       class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-md transition-colors group">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-bar text-purple-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-purple-900">View Analytics</p>
                            <p class="text-xs text-gray-500">Detailed reports and insights</p>
                        </div>
                    </a>
                    @endcan

                    @can('manage-settings')
                    <a href="{{ route('admin.settings') }}"
                       class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors group">
                        <div class="flex-shrink-0">
                            <i class="fas fa-cog text-gray-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 group-hover:text-gray-900">System Settings</p>
                            <p class="text-xs text-gray-500">Configure app settings</p>
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

            async loadData() {
                try {
                    const response = await fetch('/admin/api/dashboard-data');
                    const data = await response.json();

                    this.stats = data.stats;
                    this.recentActivity = data.recent_activity;

                    this.$nextTick(() => {
                        this.initCharts(data.charts);
                    });
                } catch (error) {
                    console.error('Failed to load dashboard data:', error);
                }
            },

            async updateChart() {
                try {
                    const response = await fetch(`/admin/api/dashboard-charts?period=${this.chartPeriod}`);
                    const data = await response.json();

                    this.updateRevenueChart(data.revenue);
                } catch (error) {
                    console.error('Failed to update chart:', error);
                }
            },

            initCharts(chartData) {
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                this.revenueChart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.revenue.labels,
                        datasets: [{
                            label: 'Revenue',
                            data: chartData.revenue.data,
                            borderColor: '#A20030',
                            backgroundColor: '#A200302B',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
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
                        labels: chartData.users.labels,
                        datasets: [
                            {
                                label: 'New Users',
                                data: chartData.users.new_users,
                                backgroundColor: '#3B82F6',
                                borderRadius: 4
                            },
                            {
                                label: 'Active Users',
                                data: chartData.users.active_users,
                                backgroundColor: '#10B981',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            },

            updateRevenueChart(data) {
                if (this.revenueChart) {
                    this.revenueChart.data.labels = data.labels;
                    this.revenueChart.data.datasets[0].data = data.data;
                    this.revenueChart.update();
                }
            },

            getActivityIcon(type) {
                const icons = {
                    'user_registered': 'fas fa-user-plus text-blue-600',
                    'post_created': 'fas fa-file-alt text-green-600',
                    'ad_approved': 'fas fa-check-circle text-green-600',
                    'ad_rejected': 'fas fa-times-circle text-red-600',
                    'payment_received': 'fas fa-dollar-sign text-yellow-600',
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
                    'payment_received': 'bg-yellow-100',
                    'stream_started': 'bg-purple-100',
                    'user_reported': 'bg-red-100'
                };
                return backgrounds[type] || 'bg-gray-100';
            },

            getActivityBadge(type) {
                const badges = {
                    'user_registered': 'bg-blue-100 text-blue-800',
                    'post_created': 'bg-green-100 text-green-800',
                    'ad_approved': 'bg-green-100 text-green-800',
                    'ad_rejected': 'bg-red-100 text-red-800',
                    'payment_received': 'bg-yellow-100 text-yellow-800',
                    'stream_started': 'bg-purple-100 text-purple-800',
                    'user_reported': 'bg-red-100 text-red-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            }
        }
    }
</script>
@endpush
