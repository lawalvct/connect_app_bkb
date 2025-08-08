@extends('admin.layouts.app')

@section('title', 'User Analytics')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            User Analytics
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Detailed insights into user demographics and behavior
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

        <!-- User Demographics Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($demographics['total_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verified Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Verified Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($demographics['verified_users']) }}</dd>
                                <dd class="text-sm text-green-600">{{ number_format(($demographics['verified_users'] / max($demographics['total_users'], 1)) * 100, 1) }}% verified</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Premium Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($demographics['premium_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Accounts -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zm-2-2H7v4h6v-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Business Accounts</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($demographics['business_accounts']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            <!-- Registration Trends -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Registration Trends</h3>
                <canvas id="registrationTrendsChart" height="300"></canvas>
            </div>

            <!-- Gender Distribution -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Gender Distribution</h3>
                <canvas id="genderChart" height="300"></canvas>
            </div>
        </div>

        <!-- Age Distribution -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Age Distribution</h3>
            </div>
            <div class="p-6">
                <canvas id="ageChart" height="200"></canvas>
            </div>
        </div>

        <!-- Top Countries -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Top Countries</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($topCountries as $country)
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900">{{ $country->country ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">{{ number_format($country->user_count) }} users</div>
                        </div>
                        @empty
                        <div class="text-center text-sm text-gray-500">No country data available</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Most Active Users -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Most Active Users</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($activeUsers as $user)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                @if($user->profile_picture)
                                    <img class="h-8 w-8 rounded-full mr-3" src="{{ $user->profile_picture }}" alt="">
                                @else
                                    <div class="h-8 w-8 rounded-full bg-gray-300 mr-3"></div>
                                @endif
                                <div class="text-sm font-medium text-gray-900">{{ $user->username }}</div>
                            </div>
                            <div class="text-sm text-gray-500">{{ number_format($user->posts_count) }} posts</div>
                        </div>
                        @empty
                        <div class="text-center text-sm text-gray-500">No user data available</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Registration Trends Chart
const registrationCtx = document.getElementById('registrationTrendsChart').getContext('2d');
new Chart(registrationCtx, {
    type: 'line',
    data: {
        labels: @json($registrationTrends->pluck('date')),
        datasets: [{
            label: 'New Registrations',
            data: @json($registrationTrends->pluck('registrations')),
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gender Distribution Chart
const genderCtx = document.getElementById('genderChart').getContext('2d');
new Chart(genderCtx, {
    type: 'doughnut',
    data: {
        labels: @json($genderStats->pluck('gender')),
        datasets: [{
            data: @json($genderStats->pluck('count')),
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(251, 191, 36, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Age Distribution Chart
const ageCtx = document.getElementById('ageChart').getContext('2d');
new Chart(ageCtx, {
    type: 'bar',
    data: {
        labels: @json($ageStats->pluck('age_group')),
        datasets: [{
            label: 'Users',
            data: @json($ageStats->pluck('count')),
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
@endsection
