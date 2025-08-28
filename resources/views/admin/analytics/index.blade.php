@extends('admin.layouts.app')

@section('title', 'Analytics & Reports')
@section('page-title', 'Analytics & Reports')

@section('content')
<div x-data="analyticsData()" x-init="initializeAnalytics()" class="min-h-screen bg-gray-50">

    <!-- Debug Panel (Remove in production) -->
    <div class="fixed bottom-4 right-4 z-50" x-data="{ showDebug: false }">
        <button @click="showDebug = !showDebug" class="bg-gray-800 text-white px-3 py-1 rounded-lg text-xs">
            Debug
        </button>
        <div x-show="showDebug" class="absolute bottom-10 right-0 bg-white border rounded-lg p-4 shadow-lg min-w-64">
            <div class="space-y-2 text-xs">
                <div>Chart.js Status: <span x-text="typeof Chart !== 'undefined' ? 'Loaded' : 'Not Loaded'"></span></div>
                <div>Alpine.js Status: <span x-text="typeof Alpine !== 'undefined' ? 'Loaded' : 'Not Loaded'"></span></div>
                <div>Charts Ready: <span x-text="chartjsReady ? 'Yes' : 'No'"></span></div>
                <div class="border-t pt-2">
                    <button onclick="debugCharts()" class="text-blue-600 underline">Run Debug</button>
                    <button onclick="initChartsManually()" class="text-green-600 underline ml-2">Retry Charts</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Header with Controls -->
    <div class="bg-white shadow-lg border-b-2 border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <!-- Main Header -->
                <div class="md:flex md:items-center md:justify-between mb-6">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-3 rounded-xl shadow-lg mr-4">
                                <i class="fas fa-chart-line text-2xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
                                <p class="text-gray-600 mt-1">Comprehensive business intelligence and performance analytics</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-4">
                        <!-- Export Buttons -->
                        <div class="flex items-center space-x-2">
                            <button @click="exportData('pdf')" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </button>
                            <button @click="exportData('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </button>
                            <button @click="exportData('csv')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                <i class="fas fa-file-csv mr-2"></i>
                                CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filter Controls -->
                <form method="GET" class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                        <!-- Date Range -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <div class="flex space-x-2">
                                <input type="date" name="start_date" value="{{ $startDate }}" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="date" name="end_date" value="{{ $endDate }}" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Comparison Period -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Compare With</label>
                            <select name="comparison" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="none">No Comparison</option>
                                <option value="previous_period">Previous Period</option>
                                <option value="previous_month">Previous Month</option>
                                <option value="previous_year">Previous Year</option>
                            </select>
                        </div>

                        <!-- User Segment -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">User Segment</label>
                            <select name="segment" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="all">All Users</option>
                                <option value="premium">Premium Users</option>
                                <option value="free">Free Users</option>
                                <option value="new">New Users</option>
                                <option value="churned">Churned Users</option>
                            </select>
                        </div>

                        <!-- Content Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                            <select name="content_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="all">All Content</option>
                                <option value="posts">Posts Only</option>
                                <option value="stories">Stories Only</option>
                                <option value="streams">Streams Only</option>
                            </select>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-lg">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Executive Summary Cards -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Executive Summary</h2>
                    <p class="text-gray-600 mt-1">Key performance indicators at a glance</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Real-time data</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        Last updated: <span x-text="lastUpdated">{{ now()->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Total Users with Detailed Metrics -->
                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full transform translate-x-16 -translate-y-16 opacity-60"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-blue-100 p-3 rounded-2xl">
                                <i class="fas fa-users text-2xl text-blue-600"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Users</div>
                                <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">New Today</span>
                                <div class="flex items-center">
                                    <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                                    <span class="font-semibold text-gray-900">{{ number_format($stats['new_users_today']) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Growth Rate</span>
                                <span class="text-green-600 font-semibold">+12.5%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: 78%"></div>
                            </div>
                            <div class="text-xs text-gray-500">78% of monthly target</div>
                        </div>
                    </div>
                </div>

                <!-- Revenue with Breakdown -->
                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-100 to-green-200 rounded-full transform translate-x-16 -translate-y-16 opacity-60"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-green-100 p-3 rounded-2xl">
                                <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Revenue</div>
                                <div class="text-3xl font-bold text-gray-900">${{ number_format($stats['total_revenue'], 2) }}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">This Month</span>
                                <div class="flex items-center">
                                    <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                                    <span class="font-semibold text-gray-900">${{ number_format($stats['total_revenue'] * 0.15, 2) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Monthly Growth</span>
                                <span class="text-green-600 font-semibold">+18.3%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: 65%"></div>
                            </div>
                            <div class="text-xs text-gray-500">65% of yearly target</div>
                        </div>
                    </div>
                </div>

                <!-- Content Engagement -->
                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full transform translate-x-16 -translate-y-16 opacity-60"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-purple-100 p-3 rounded-2xl">
                                <i class="fas fa-heart text-2xl text-purple-600"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Engagement</div>
                                <div class="text-3xl font-bold text-gray-900">{{ number_format($contentEngagement['total_likes'] + $contentEngagement['total_comments']) }}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Likes</span>
                                <span class="font-semibold text-gray-900">{{ number_format($contentEngagement['total_likes']) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Comments</span>
                                <span class="font-semibold text-gray-900">{{ number_format($contentEngagement['total_comments']) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: 82%"></div>
                            </div>
                            <div class="text-xs text-gray-500">Engagement rate: 8.2%</div>
                        </div>
                    </div>
                </div>

                <!-- Platform Activity -->
                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-100 to-orange-200 rounded-full transform translate-x-16 -translate-y-16 opacity-60"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-orange-100 p-3 rounded-2xl">
                                <i class="fas fa-activity text-2xl text-orange-600"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Active Sessions</div>
                                <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_streams']) }}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Live Streams</span>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-2"></div>
                                    <span class="font-semibold text-gray-900">{{ number_format($stats['total_streams'] * 0.1) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Avg Duration</span>
                                <span class="font-semibold text-gray-900">24m 15s</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-orange-600 h-2 rounded-full" style="width: 92%"></div>
                            </div>
                            <div class="text-xs text-gray-500">92% uptime today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Advanced Analytics Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-12">

            <!-- Multi-dimensional Chart Section -->
            <div class="xl:col-span-2 space-y-8">

                <!-- Revenue & User Growth Correlation -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-chart-line text-indigo-600 mr-3"></i>
                                    Revenue & User Growth Analysis
                                </h3>
                                <p class="text-gray-600 mt-1">Correlation between user acquisition and revenue generation</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-2">
                                    <div id="correlationChartStatus" class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-xs text-gray-600">Live Data</span>
                                </div>
                                <select x-model="chartTimeframe" @change="updateCharts()" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="7d">Last 7 days</option>
                                    <option value="30d" selected>Last 30 days</option>
                                    <option value="90d">Last 90 days</option>
                                    <option value="1y">Last year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="relative">
                            <div id="correlationChartContainer" class="h-96 w-full">
                                <canvas id="correlationChart" width="800" height="400" class="hidden"></canvas>
                                <!-- Fallback content - shown by default until chart loads -->
                                <div id="correlationChartFallback" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                                        <p class="text-gray-600">Loading chart data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                            <div class="text-center p-4 bg-indigo-50 rounded-xl">
                                <div class="text-2xl font-bold text-indigo-600">{{ number_format(($userGrowth->sum('count') / max($userGrowth->count(), 1)) * 0.87, 2) }}</div>
                                <div class="text-sm text-gray-600">Avg Daily Users</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-xl">
                                <div class="text-2xl font-bold text-green-600">${{ number_format(($stats['total_revenue'] / max($stats['total_users'], 1)), 2) }}</div>
                                <div class="text-sm text-gray-600">Revenue per User</div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-xl">
                                <div class="text-2xl font-bold text-purple-600">{{ number_format((($stats['new_users_today'] / max($stats['total_users'], 1)) * 100), 1) }}%</div>
                                <div class="text-sm text-gray-600">Daily Growth Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Performance Heatmap -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-50 to-red-50 px-8 py-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-fire text-orange-600 mr-3"></i>
                                    Content Performance Heatmap
                                </h3>
                                <p class="text-gray-600 mt-1">Daily engagement patterns across content types</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div id="heatmapChartStatus" class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-eye mr-1"></i>
                                    Heat Intensity
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="relative">
                            <div id="heatmapChartContainer" class="h-80 w-full">
                                <canvas id="heatmapChart" width="800" height="320" class="hidden"></canvas>
                                <!-- Fallback content - shown by default until chart loads -->
                                <div id="heatmapChartFallback" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mx-auto mb-4"></div>
                                        <p class="text-gray-600">Generating heatmap...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-between text-sm text-gray-600">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                    <span>High Activity (80-100%)</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                                    <span>Medium Activity (40-80%)</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                    <span>Low Activity (0-40%)</span>
                                </div>
                            </div>
                            <div class="text-xs bg-gray-100 px-3 py-1 rounded-full">
                                Peak hours: <span class="font-semibold">6-9 PM</span> | Peak day: <span class="font-semibold">Sunday</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar Analytics -->
            <div class="space-y-6">

                <!-- Real-time Activity Feed -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-3"></div>
                                Live Activity
                            </h3>
                            <span class="text-xs text-gray-500">Real-time</span>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <div class="divide-y divide-gray-100">
                            @foreach($recentActivity->take(8) as $activity)
                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                                        <i class="fas fa-user text-white text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 font-medium">{{ $activity->description ?? 'Recent activity' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at ? $activity->created_at->diffForHumans() : 'Just now' }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Geographic Distribution -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-globe text-blue-600 mr-3"></i>
                            Geographic Distribution
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($popularCountries->take(8) as $index => $country)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-indigo-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $country->country }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($country->user_count) }} users</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($country->user_count / $popularCountries->first()->user_count) * 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700">{{ number_format(($country->user_count / $stats['total_users']) * 100, 1) }}%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-tachometer-alt text-purple-600 mr-3"></i>
                            Performance Metrics
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-2xl">
                                <div class="text-2xl font-bold text-green-600">98.5%</div>
                                <div class="text-xs text-gray-600 mt-1">Platform Uptime</div>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl">
                                <div class="text-2xl font-bold text-blue-600">1.2s</div>
                                <div class="text-xs text-gray-600 mt-1">Avg Load Time</div>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl">
                                <div class="text-2xl font-bold text-purple-600">94.8%</div>
                                <div class="text-xs text-gray-600 mt-1">User Satisfaction</div>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl">
                                <div class="text-2xl font-bold text-orange-600">7.3</div>
                                <div class="text-xs text-gray-600 mt-1">Performance Score</div>
                            </div>
                        </div>

                        <!-- System Health Indicators -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Database Performance</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-green-600">92%</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Server Response</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 89%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-green-600">89%</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">CDN Efficiency</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 76%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-yellow-600">76%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Detailed Reports Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Top Performing Content -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <i class="fas fa-trophy text-green-600 mr-3"></i>
                                Top Performing Content
                            </h3>
                            <p class="text-gray-600 mt-1">Most engaging posts and content</p>
                        </div>
                        <button class="text-sm bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition-colors">
                            View All
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    @foreach($topPosts->take(6) as $index => $post)
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold text-sm">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ Str::limit($post->content ?? 'Post content', 60) }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            By {{ $post->user->username ?? 'Unknown' }} • {{ $post->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center space-x-4 text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-eye mr-1"></i>
                                        {{ number_format($post->views_count ?? 0) }}
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-heart mr-1"></i>
                                        {{ number_format($post->likes_count ?? 0) }}
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-comment mr-1"></i>
                                        {{ number_format($post->comments_count ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Revenue Analytics -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <i class="fas fa-chart-pie text-blue-600 mr-3"></i>
                                Revenue Breakdown
                            </h3>
                            <p class="text-gray-600 mt-1">Income sources and trends</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="h-64 relative mb-6">
                        <canvas id="revenueBreakdownChart"></canvas>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Subscriptions</span>
                            </div>
                            <span class="text-sm font-bold text-green-600">${{ number_format($stats['total_revenue'] * 0.65, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-blue-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Advertising</span>
                            </div>
                            <span class="text-sm font-bold text-blue-600">${{ number_format($stats['total_revenue'] * 0.25, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-purple-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Premium Features</span>
                            </div>
                            <span class="text-sm font-bold text-purple-600">${{ number_format($stats['total_revenue'] * 0.10, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Export and Actions -->
        {{-- <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
            <div class="text-center">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Generate Detailed Reports</h3>
                    <p class="text-gray-600">Export comprehensive analytics data for further analysis</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="{{ route('admin.analytics.users') }}" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4 rounded-xl font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-lg flex items-center justify-center">
                        <i class="fas fa-users mr-2"></i>
                        User Analytics
                    </a>
                    <a href="{{ route('admin.analytics.content') }}" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4 rounded-xl font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg flex items-center justify-center">
                        <i class="fas fa-newspaper mr-2"></i>
                        Content Report
                    </a>
                    <a href="{{ route('admin.analytics.revenue') }}" class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-6 py-4 rounded-xl font-medium hover:from-yellow-600 hover:to-yellow-700 transition-all duration-200 shadow-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        Revenue Report
                    </a>
                    <a href="{{ route('admin.analytics.advertising') }}" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-4 rounded-xl font-medium hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-lg flex items-center justify-center">
                        <i class="fas fa-ad mr-2"></i>
                        Ad Analytics
                    </a>
                </div>
            </div>
        </div> --}}
    </div>
</div>

<!-- Analytics Data Function - Must load BEFORE Alpine.js -->
<script>
// Global analytics data function for Alpine.js
function analyticsData() {
    return {
        lastUpdated: '{{ now()->format("M d, Y H:i") }}',
        chartTimeframe: '30d',
        correlationChart: null,
        heatmapChart: null,
        revenueChart: null,
        chartjsReady: false,

        initializeAnalytics() {
            console.log('🚀 Initializing analytics...');

            // Check if Chart.js is available immediately
            if (typeof Chart !== 'undefined') {
                console.log('✅ Chart.js found immediately');
                this.chartjsReady = true;
                this.startChartInitialization();
            } else {
                // Wait for Chart.js to load
                console.log('⏳ Waiting for Chart.js to load...');
                window.addEventListener('chartjs-ready', () => {
                    console.log('✅ Chart.js ready event received');
                    this.chartjsReady = true;
                    this.startChartInitialization();
                });

                // Fallback timeout
                setTimeout(() => {
                    if (!this.chartjsReady) {
                        console.error('❌ Chart.js loading timeout');
                        this.showChartError();
                    }
                }, 10000);
            }
        },

        startChartInitialization() {
            this.$nextTick(() => {
                setTimeout(() => {
                    if (typeof Chart === 'undefined') {
                        console.error('❌ Chart.js still not available');
                        this.showChartError();
                        return;
                    }

                    console.log('🎯 Starting chart initialization with Chart.js version:', Chart.version);
                    this.initCharts();
                    this.startRealTimeUpdates();
                }, 100);
            });
        },

        initCharts() {
            console.log('📊 Initializing all charts...');
            try {
                this.initCorrelationChart();
                this.initHeatmapChart();
                this.initRevenueBreakdownChart();
            } catch (error) {
                console.error('❌ Error initializing charts:', error);
                this.showChartError();
            }
        },

        showChartError() {
            console.log('⚠️ Showing chart error states');
            const errorHtml = `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                    <p class="text-red-600 mb-2">Chart failed to load</p>
                    <div class="space-y-2">
                        <button onclick="location.reload()" class="text-sm text-blue-600 underline block">Refresh Page</button>
                        <button onclick="window.analyticsData().initializeAnalytics()" class="text-sm text-green-600 underline block">Retry Charts</button>
                    </div>
                </div>
            `;

            const correlationFallback = document.getElementById('correlationChartFallback');
            const heatmapFallback = document.getElementById('heatmapChartFallback');

            if (correlationFallback) correlationFallback.innerHTML = errorHtml;
            if (heatmapFallback) heatmapFallback.innerHTML = errorHtml;
        },

        initCorrelationChart() {
            console.log('📈 Initializing correlation chart...');
            const ctx = document.getElementById('correlationChart');
            const fallback = document.getElementById('correlationChartFallback');

            if (!ctx) {
                console.warn('⚠️ Correlation chart canvas not found');
                return;
            }

            try {
                // Generate sample data
                const sampleDates = [];
                const sampleUsers = [];
                const sampleRevenue = [];

                for (let i = 29; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    sampleDates.push(date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'}));
                    sampleUsers.push(Math.floor(Math.random() * 100) + 20);
                    sampleRevenue.push(Math.floor(Math.random() * 5000) + 1000);
                }

                this.correlationChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: sampleDates,
                        datasets: [{
                            label: 'New Users',
                            data: sampleUsers,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }, {
                            label: 'Revenue ($)',
                            data: sampleRevenue,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            yAxisID: 'y1',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 10
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                title: {
                                    display: true,
                                    text: 'New Users',
                                    color: '#4F46E5'
                                },
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                title: {
                                    display: true,
                                    text: 'Revenue ($)',
                                    color: '#10B981'
                                },
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: true
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart',
                            onComplete: () => {
                                if (fallback) fallback.classList.add('hidden');
                                ctx.classList.remove('hidden');

                                const status = document.getElementById('correlationChartStatus');
                                if (status) {
                                    status.classList.remove('bg-red-500', 'bg-yellow-500');
                                    status.classList.add('bg-green-500');
                                }
                                console.log('✅ Correlation chart loaded successfully');
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('❌ Error creating correlation chart:', error);
                this.showChartError();
            }
        },

        initHeatmapChart() {
            console.log('🔥 Initializing heatmap chart...');
            const ctx = document.getElementById('heatmapChart');
            const fallback = document.getElementById('heatmapChartFallback');

            if (!ctx) {
                console.warn('⚠️ Heatmap chart canvas not found');
                return;
            }

            try {
                const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                const heatmapData = [];

                days.forEach((day, dayIndex) => {
                    for (let hour = 0; hour < 24; hour++) {
                        let intensity = 20;
                        if (dayIndex >= 5) intensity += 20;
                        if (hour >= 8 && hour <= 22) intensity += 30;
                        if (hour >= 18 && hour <= 21) intensity += 25;
                        intensity += Math.random() * 20 - 10;
                        intensity = Math.max(5, Math.min(100, intensity));

                        heatmapData.push({
                            x: hour,
                            y: dayIndex,
                            v: intensity
                        });
                    }
                });

                this.heatmapChart = new Chart(ctx, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Activity Intensity',
                            data: heatmapData,
                            backgroundColor: function(context) {
                                const value = context.parsed.v;
                                if (value >= 70) {
                                    return `rgba(239, 68, 68, ${0.4 + (value - 70) * 0.02})`;
                                } else if (value >= 40) {
                                    return `rgba(245, 158, 11, ${0.3 + (value - 40) * 0.01})`;
                                } else {
                                    return `rgba(34, 197, 94, ${0.2 + value * 0.01})`;
                                }
                            },
                            pointRadius: function(context) {
                                return Math.max(4, Math.min(12, context.parsed.v * 0.12));
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'linear',
                                min: 0,
                                max: 23,
                                title: { display: true, text: 'Hour of Day' },
                                ticks: {
                                    stepSize: 4,
                                    callback: function(value) {
                                        return value + ':00';
                                    }
                                }
                            },
                            y: {
                                type: 'linear',
                                min: 0,
                                max: 6,
                                title: { display: true, text: 'Day of Week' },
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) {
                                        return days[value] || '';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        const point = context[0];
                                        const hour = point.parsed.x;
                                        const day = days[point.parsed.y] || 'Unknown';
                                        return `${day} ${hour}:00`;
                                    },
                                    label: function(context) {
                                        return `Activity: ${Math.round(context.parsed.v)}%`;
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            onComplete: () => {
                                if (fallback) fallback.classList.add('hidden');
                                ctx.classList.remove('hidden');

                                const status = document.getElementById('heatmapChartStatus');
                                if (status) {
                                    status.classList.remove('bg-red-500', 'bg-yellow-500');
                                    status.classList.add('bg-orange-500');
                                }
                                console.log('✅ Heatmap chart loaded successfully');
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('❌ Error creating heatmap chart:', error);
                this.showChartError();
            }
        },

        initRevenueBreakdownChart() {
            const ctx = document.getElementById('revenueBreakdownChart');
            if (!ctx) {
                console.warn('⚠️ Revenue breakdown chart canvas not found');
                return;
            }

            const revenueData = [65, 25, 10];
            const revenueLabels = ['Subscriptions', 'Advertising', 'Premium Features'];
            const colors = ['#10B981', '#3B82F6', '#8B5CF6'];

            this.revenueChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        data: revenueData,
                        backgroundColor: colors,
                        borderColor: colors.map(color => color + '40'),
                        borderWidth: 3,
                        cutout: '60%',
                        hoverOffset: 8,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true
                        }
                    },
                    animation: {
                        duration: 1000
                    }
                }
            });
        },

        updateCharts() {
            console.log('🔄 Updating charts for timeframe:', this.chartTimeframe);
            // Implement chart updates
        },

        startRealTimeUpdates() {
            setInterval(() => {
                this.lastUpdated = new Date().toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }, 30000);
        },

        exportData(format) {
            console.log('📤 Exporting data as:', format);
            alert(`${format.toUpperCase()} export functionality would be implemented here`);
        }
    }
}

// Make it available globally for debugging
window.analyticsData = analyticsData;
</script>

@push('scripts')
<!-- Load Chart.js with fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"
        onerror="this.onerror=null; this.src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js';">
</script>

<!-- Backup Chart.js loader -->
<script>
if (typeof Chart === 'undefined') {
    console.warn('Primary Chart.js CDN failed, loading backup...');
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/chart.js@4.4.0/dist/chart.umd.js';
    script.onload = function() {
        console.log('Backup Chart.js loaded successfully');
        window.dispatchEvent(new Event('chartjs-loaded'));
    };
    script.onerror = function() {
        console.error('All Chart.js CDNs failed to load');
        // Show error message to user
        document.querySelectorAll('[id$="ChartFallback"]').forEach(fallback => {
            if (fallback) {
                fallback.innerHTML = `
                    <div class="text-center text-red-600">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Chart library failed to load</p>
                        <button onclick="location.reload()" class="mt-2 text-sm bg-red-100 px-3 py-1 rounded">Reload Page</button>
                    </div>
                `;
            }
        });
    };
    document.head.appendChild(script);
}
</script>

<!-- Chart.js Error Handling and Debugging -->
<script>
// Wait for Chart.js to be available
function waitForChart(callback, timeout = 5000) {
    const startTime = Date.now();

    function checkChart() {
        if (typeof Chart !== 'undefined') {
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkChart, 100);
        } else {
            console.error('Chart.js failed to load within timeout period');
            // Show error in all chart containers
            document.querySelectorAll('[id$="ChartFallback"]').forEach(fallback => {
                if (fallback) {
                    fallback.innerHTML = `
                        <div class="text-center text-red-600">
                            <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                            <p>Chart library timeout</p>
                            <button onclick="location.reload()" class="mt-2 text-sm bg-red-100 px-3 py-1 rounded">Reload Page</button>
                        </div>
                    `;
                }
            });
        }
    }

    checkChart();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, waiting for Chart.js...');

    waitForChart(function() {
        console.log('Chart.js loaded successfully, version:', Chart.version);

        // Add global Chart.js defaults
        Chart.defaults.font.family = "'Inter', 'system-ui', '-apple-system', 'sans-serif'";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6B7280';
        Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.1)';

        // Dispatch event to let Alpine.js know Chart.js is ready
        window.dispatchEvent(new Event('chartjs-ready'));
    });
});

// Additional debugging functions
window.debugCharts = function() {
    console.log('=== Chart Debug Information ===');
    console.log('Chart.js version:', typeof Chart !== 'undefined' ? Chart.version : 'Not loaded');
    console.log('Alpine.js loaded:', typeof Alpine !== 'undefined');

    const canvases = ['correlationChart', 'heatmapChart', 'revenueBreakdownChart'];
    canvases.forEach(id => {
        const canvas = document.getElementById(id);
        const container = canvas?.parentElement;
        console.log(`${id}:`, {
            canvas: canvas ? 'Found' : 'Missing',
            visible: canvas ? (canvas.offsetWidth > 0 && canvas.offsetHeight > 0) : false,
            containerSize: container ? `${container.offsetWidth}x${container.offsetHeight}` : 'No container',
            chartInstance: canvas && canvas.chart ? 'Has Chart' : 'No Chart'
        });
    });

    console.log('========================');
};

// Manual chart initialization function for debugging
window.initChartsManually = function() {
    console.log('Manual chart initialization...');
    if (typeof analyticsData === 'function') {
        const analytics = analyticsData();
        analytics.initializeAnalytics();
    } else {
        console.error('analyticsData function not found');
    }
};
</script>

<!-- Duplicate function removed - using the main analyticsData function above -->
@endpush

@endsection
