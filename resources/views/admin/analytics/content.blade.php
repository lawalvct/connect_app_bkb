@extends('admin.layouts.app')

@section('title', 'Content Analytics')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Content Analytics
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Insights into content performance and engagement
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

        <!-- Content Overview Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Posts -->
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Posts</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($contentStats['total_posts']) }}</dd>
                                <dd class="text-sm text-green-600">+{{ number_format($contentStats['posts_today']) }} today</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Stories -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Stories</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($contentStats['total_stories']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Streams -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Streams</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($contentStats['total_streams']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Engagement -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Engagement</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($engagementStats['avg_engagement'], 1) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Creation Trends -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Content Creation Trends</h3>
            </div>
            <div class="p-6">
                <canvas id="contentTrendsChart" height="300"></canvas>
            </div>
        </div>

        <!-- Engagement Overview -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Total Engagement Metrics</h3>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 p-6">
                <div class="text-center">
                    <div class="text-2xl font-semibold text-blue-600">{{ number_format($engagementStats['total_likes']) }}</div>
                    <div class="text-sm text-gray-500">Total Likes</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-green-600">{{ number_format($engagementStats['total_comments']) }}</div>
                    <div class="text-sm text-gray-500">Total Comments</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-purple-600">{{ number_format($engagementStats['total_shares']) }}</div>
                    <div class="text-sm text-gray-500">Total Shares</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-yellow-600">{{ number_format($engagementStats['total_views']) }}</div>
                    <div class="text-sm text-gray-500">Total Views</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            <!-- Content Type Distribution -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Content Type Distribution</h3>
                <canvas id="contentTypeChart" height="300"></canvas>
            </div>

            <!-- Story Analytics -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Story Analytics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500">Total Stories</span>
                        <span class="text-sm text-gray-900">{{ number_format($storyStats['total_stories']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500">Story Views</span>
                        <span class="text-sm text-gray-900">{{ number_format($storyStats['story_views']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500">Avg Views per Story</span>
                        <span class="text-sm text-gray-900">{{ number_format($storyStats['avg_story_views'], 1) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500">Active Stories</span>
                        <span class="text-sm text-gray-900">{{ number_format($storyStats['active_stories']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Posts -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Performing Posts</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Engagement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Likes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topPosts as $post)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 truncate max-w-xs">{{ Str::limit($post->content, 50) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($post->user->profile_picture)
                                        <img class="h-8 w-8 rounded-full mr-3" src="{{ $post->user->profile_picture }}" alt="">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-gray-300 mr-3"></div>
                                    @endif
                                    <div class="text-sm font-medium text-gray-900">{{ $post->user->username }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ number_format($post->total_engagement) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($post->views_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($post->likes_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($post->comments_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($post->shares_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $post->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No posts found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Content Creation Trends Chart
const contentTrendsCtx = document.getElementById('contentTrendsChart').getContext('2d');
new Chart(contentTrendsCtx, {
    type: 'line',
    data: {
        labels: @json($contentTrends->pluck('date')),
        datasets: [{
            label: 'Posts Created',
            data: @json($contentTrends->pluck('posts')),
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

// Content Type Distribution Chart
const contentTypeCtx = document.getElementById('contentTypeChart').getContext('2d');
new Chart(contentTypeCtx, {
    type: 'doughnut',
    data: {
        labels: @json($contentTypes->pluck('content_type')),
        datasets: [{
            data: @json($contentTypes->pluck('count')),
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
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
</script>
@endpush
@endsection
