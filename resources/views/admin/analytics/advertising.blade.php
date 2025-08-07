@extends('admin.layouts.app')

@section('title', 'Advertising Analytics')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Advertising Analytics
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Advertisement performance and revenue insights
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <!-- Date Range Picker -->
                        <form method="GET" class="flex space-x-3">
                            <input type="date" name="start_date" value="{{ $startDate }}" 
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <input type="date" name="end_date" value="{{ $endDate }}" 
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Apply
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Navigation -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <nav class="flex space-x-8 mb-8" aria-label="Analytics Navigation">
            <a href="{{ route('admin.analytics.index') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.index') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Overview
            </a>
            <a href="{{ route('admin.analytics.users') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.users') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Users
            </a>
            <a href="{{ route('admin.analytics.content') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.content') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Content
            </a>
            <a href="{{ route('admin.analytics.revenue') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.revenue') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Revenue
            </a>
            <a href="{{ route('admin.analytics.advertising') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.advertising') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Advertising
            </a>
            <a href="{{ route('admin.analytics.streaming') }}" 
               class="border-b-2 {{ request()->routeIs('admin.analytics.streaming') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 text-sm font-medium">
                Streaming
            </a>
        </nav>

        <!-- Ad Performance Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Ads -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zm-2-2H7v4h6v-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Ads</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($adStats['total_ads']) }}</dd>
                                <dd class="text-sm text-green-600">{{ number_format($adStats['active_ads']) }} active</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Impressions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Impressions</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($adStats['total_impressions']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Clicks -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.415l.707-.708zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.415zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Clicks</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($adStats['total_clicks']) }}</dd>
                                <dd class="text-sm text-blue-600">{{ number_format($adStats['avg_ctr'], 2) }}% CTR</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Ad Spend -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Ad Spend</dt>
                                <dd class="text-lg font-medium text-gray-900">${{ number_format($adStats['total_ad_spend'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Performance Trends Chart -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Advertisement Trends</h3>
            </div>
            <div class="p-6">
                <canvas id="adTrendsChart" height="300"></canvas>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            <!-- Ad Status Distribution -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ad Status Distribution</h3>
                <canvas id="adStatusChart" height="300"></canvas>
            </div>

            <!-- Ad Types -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ad Types Performance</h3>
                <canvas id="adTypesChart" height="300"></canvas>
            </div>
        </div>

        <!-- Budget Analysis -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Budget Analysis</h3>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 p-6">
                <div class="text-center">
                    <div class="text-2xl font-semibold text-blue-600">${{ number_format($budgetStats['total_budget_allocated'], 2) }}</div>
                    <div class="text-sm text-gray-500">Total Budget Allocated</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-green-600">${{ number_format($budgetStats['total_spent'], 2) }}</div>
                    <div class="text-sm text-gray-500">Total Spent</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-purple-600">${{ number_format($budgetStats['avg_daily_budget'], 2) }}</div>
                    <div class="text-sm text-gray-500">Avg Daily Budget</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-yellow-600">{{ number_format($budgetStats['budget_utilization'], 1) }}%</div>
                    <div class="text-sm text-gray-500">Budget Utilization</div>
                </div>
            </div>
        </div>

        <!-- Top Performing Ads -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Performing Ads</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Advertiser</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topAds as $ad)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ $ad->ad_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ad->user ? $ad->user->username : 'Unknown' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($ad->current_impressions) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($ad->clicks) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($ad->ctr, 2) }}%</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($ad->total_spent, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($ad->status === 'active') bg-green-100 text-green-800
                                    @elseif($ad->status === 'paused') bg-yellow-100 text-yellow-800
                                    @elseif($ad->status === 'completed') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($ad->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No ads found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Country Performance -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Countries by Ad Spend</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($countryStats as $country)
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-900">{{ $country->country ?? 'Unknown' }}</div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500">{{ number_format($country->ad_count) }} ads</span>
                            <span class="text-sm font-medium text-gray-900">${{ number_format($country->spend, 2) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-sm text-gray-500">No country targeting data available</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Ad Trends Chart
const adTrendsCtx = document.getElementById('adTrendsChart').getContext('2d');
new Chart(adTrendsCtx, {
    type: 'line',
    data: {
        labels: @json($adTrends->pluck('date')),
        datasets: [{
            label: 'Ads Created',
            data: @json($adTrends->pluck('ads_created')),
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.1,
            yAxisID: 'y'
        }, {
            label: 'Daily Spend ($)',
            data: @json($adTrends->pluck('daily_spend')),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Ad Status Chart
const adStatusCtx = document.getElementById('adStatusChart').getContext('2d');
new Chart(adStatusCtx, {
    type: 'doughnut',
    data: {
        labels: @json($adStatusStats->pluck('status')),
        datasets: [{
            data: @json($adStatusStats->pluck('count')),
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',
                'rgba(251, 191, 36, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Ad Types Chart
const adTypesCtx = document.getElementById('adTypesChart').getContext('2d');
new Chart(adTypesCtx, {
    type: 'bar',
    data: {
        labels: @json($adTypes->pluck('type')),
        datasets: [{
            label: 'Total Spend ($)',
            data: @json($adTypes->pluck('total_spend')),
            backgroundColor: 'rgba(168, 85, 247, 0.8)',
            borderColor: 'rgb(168, 85, 247)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection
