@extends('admin.layouts.app')

@section('title', 'Ad Details - ' . $ad->ad_name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.ads.index') }}" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-ad mr-2"></i>
                            Ads
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-900 font-medium">{{ $ad->ad_name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $ad->ad_name }}</h1>
            <p class="text-gray-600">Ad Campaign Details and Management</p>
        </div>
        <div class="flex space-x-3">
            @if($ad->admin_status === 'pending' && $ad->latestPayment && $ad->latestPayment->status === 'completed')
                <button onclick="approveAd({{ $ad->id }})"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Approve Ad
                </button>
                <button onclick="showRejectModal({{ $ad->id }})"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Reject Ad
                </button>
            @endif

            @if($ad->status === 'active')
                <button onclick="pauseAd({{ $ad->id }})"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-pause mr-2"></i>
                    Pause
                </button>
            @endif

            @if($ad->status === 'paused')
                <button onclick="resumeAd({{ $ad->id }})"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-play mr-2"></i>
                    Resume
                </button>
            @endif

            @if(in_array($ad->status, ['active', 'paused']))
                <button onclick="stopAd({{ $ad->id }})"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-stop mr-2"></i>
                    Stop
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-blue-600">${{ number_format($ad->budget, 2) }}</div>
                <div class="text-sm text-gray-500">Total Budget</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-green-600">${{ number_format($ad->total_spent, 2) }}</div>
                <div class="text-sm text-gray-500">Amount Spent</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-purple-600">{{ number_format($ad->current_impressions) }}</div>
                <div class="text-sm text-gray-500">Impressions</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-orange-600">{{ number_format($ad->clicks) }}</div>
                <div class="text-sm text-gray-500">Clicks</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $ad->ctr }}%</div>
                <div class="text-sm text-gray-500">CTR</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md text-center">
                <div class="text-2xl font-bold text-pink-600">{{ number_format($ad->conversions) }}</div>
                <div class="text-sm text-gray-500">Conversions</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Ad Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ad Preview -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Ad Preview</h3>
                    </div>
                    <div class="p-6">
                        @if($ad->media_files && count($ad->media_files) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                @foreach($ad->media_files as $index => $media)
                                    <div class="relative">
                                        @if(isset($media['type']) && str_starts_with($media['type'], 'video'))
                                            <video controls class="w-full h-48 object-cover rounded-lg">
                                                <source src="{{ $media['file_path'] ?? $media }}" type="{{ $media['type'] ?? 'video/mp4' }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @else
                                            <img src="{{ $media['file_path'] ?? $media }}"
                                                 alt="Ad Media {{ $index + 1 }}"
                                                 class="w-full h-48 object-cover rounded-lg">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $ad->ad_name }}</h4>
                                @if($ad->description)
                                    <p class="text-gray-600 mt-1">{{ $ad->description }}</p>
                                @endif
                            </div>

                            @if($ad->call_to_action)
                                <div class="inline-block">
                                    <span class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium">
                                        {{ $ad->call_to_action }}
                                    </span>
                                </div>
                            @endif

                            @if($ad->destination_url)
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-external-link-alt mr-1"></i>
                                    <a href="{{ $ad->destination_url }}" target="_blank" class="hover:text-primary">
                                        {{ $ad->destination_url }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Campaign Details -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Campaign Details</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Basic Information</h4>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Ad Type:</dt>
                                        <dd class="text-sm font-medium text-gray-900">{{ ucfirst($ad->type) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Status:</dt>
                                        <dd>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($ad->status === 'active') bg-green-100 text-green-800
                                                @elseif($ad->status === 'paused') bg-orange-100 text-orange-800
                                                @elseif($ad->status === 'draft') bg-gray-100 text-gray-800
                                                @elseif($ad->status === 'rejected') bg-red-100 text-red-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $ad->status)) }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Campaign Period:</dt>
                                        <dd class="text-sm font-medium text-gray-900">
                                            {{ $ad->start_date->format('M d, Y') }} - {{ $ad->end_date->format('M d, Y') }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Days Remaining:</dt>
                                        <dd class="text-sm font-medium text-gray-900">{{ $ad->days_remaining }} days</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Budget & Performance</h4>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Daily Budget:</dt>
                                        <dd class="text-sm font-medium text-gray-900">${{ number_format($ad->daily_budget ?? 0, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Target Impressions:</dt>
                                        <dd class="text-sm font-medium text-gray-900">{{ number_format($ad->target_impressions) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Progress:</dt>
                                        <dd class="text-sm font-medium text-gray-900">{{ $ad->progress_percentage }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Cost per Click:</dt>
                                        <dd class="text-sm font-medium text-gray-900">${{ number_format($ad->cost_per_click, 4) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        @if($ad->target_audience)
                            {{-- <div class="mt-6">
                                <h4 class="font-medium text-gray-900 mb-3">Target Audience</h4>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($ad->target_audience, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div> --}}
                        @endif
                    </div>
                </div>

                <!-- Payment History -->
                @if($ad->payments && $ad->payments->count() > 0)
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Payment History</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Gateway
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reference
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($ad->payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $payment->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                ${{ number_format($payment->amount, 2) }}
                                                @if($payment->currency !== 'USD')
                                                    <span class="text-gray-500">({{ $payment->currency }})</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ ucfirst($payment->payment_gateway) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    @if($payment->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                                    @elseif($payment->status === 'failed') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $payment->gateway_reference ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Admin Actions -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Admin Review</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Admin Status:</span>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($ad->admin_status === 'approved') bg-green-100 text-green-800
                                @elseif($ad->admin_status === 'rejected') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ ucfirst($ad->admin_status) }}
                            </span>
                        </div>

                        @if($ad->reviewed_at)
                            <div>
                                <span class="text-sm font-medium text-gray-700">Reviewed:</span>
                                <p class="text-sm text-gray-500">{{ $ad->reviewed_at->format('M d, Y H:i') }}</p>
                                @if($ad->reviewer)
                                    <p class="text-sm text-gray-500">by {{ $ad->reviewer->username }}</p>
                                @endif
                            </div>
                        @endif

                        @if($ad->admin_comments)
                            <div>
                                <span class="text-sm font-medium text-gray-700">Admin Comments:</span>
                                <p class="text-sm text-gray-600 mt-1 p-2 bg-gray-50 rounded">{{ $ad->admin_comments }}</p>
                            </div>
                        @endif

                        @if($ad->admin_status === 'pending')
                            <div class="border-t pt-4">
                                <div class="space-y-2">
                                    @if($ad->latestPayment && $ad->latestPayment->status === 'completed')
                                        <button onclick="approveAd({{ $ad->id }})"
                                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                            <i class="fas fa-check mr-2"></i>
                                            Approve Ad
                                        </button>
                                    @else
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Payment required before approval
                                            </p>
                                        </div>
                                    @endif

                                    <button onclick="showRejectModal({{ $ad->id }})"
                                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                        <i class="fas fa-times mr-2"></i>
                                        Reject Ad
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Advertiser Info -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Advertiser</h3>
                    </div>
                    <div class="p-6">
                        @if($ad->user)
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center">
                                    @if($ad->user->avatar)
                                        <img src="{{ $ad->user->avatar }}" alt="{{ $ad->user->username }}" class="h-10 w-10 rounded-full">
                                    @else
                                        <i class="fas fa-user text-gray-500"></i>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $ad->user->username }}</p>
                                    <p class="text-sm text-gray-500">{{ $ad->user->email }}</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Member Since:</span>
                                    <span class="text-gray-900">{{ $ad->user->created_at->format('M Y') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Total Ads:</span>
                                    <span class="text-gray-900">{{ $ad->user->ads()->where('deleted_flag', 'N')->count() }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $ad->user->id) }}"
                                   class="text-primary hover:text-primary-dark text-sm font-medium">
                                    View User Profile →
                                </a>
                            </div>
                        @else
                            <p class="text-gray-500">Advertiser information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Social Circles Targeting -->
                @if($ad->target_social_circles && count($ad->target_social_circles) > 0)
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Targeted Social Circles</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-2">
                                @foreach($ad->placementSocialCircles as $circle)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span class="text-sm font-medium text-gray-900">{{ $circle->name }}</span>
                                        <span class="text-xs text-gray-500">{{ $circle->members_count ?? 0 }} members</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Timeline</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-plus text-blue-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Ad Created</p>
                                    <p class="text-sm text-gray-500">{{ $ad->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>

                            @if($ad->latestPayment)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-credit-card text-green-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Payment {{ ucfirst($ad->latestPayment->status) }}</p>
                                        <p class="text-sm text-gray-500">{{ $ad->latestPayment->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($ad->reviewed_at)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-6 h-6 {{ $ad->admin_status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                        <i class="fas {{ $ad->admin_status === 'approved' ? 'fa-check text-green-600' : 'fa-times text-red-600' }} text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Ad {{ ucfirst($ad->admin_status) }}</p>
                                        <p class="text-sm text-gray-500">{{ $ad->reviewed_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($ad->activated_at)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-play text-purple-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Campaign Started</p>
                                        <p class="text-sm text-gray-500">{{ $ad->activated_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($ad->paused_at)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-pause text-yellow-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Campaign Paused</p>
                                        <p class="text-sm text-gray-500">{{ $ad->paused_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($ad->stopped_at)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-stop text-red-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Campaign Stopped</p>
                                        <p class="text-sm text-gray-500">{{ $ad->stopped_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Reject Advertisement</h3>
                    <button onclick="hideRejectModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reject_reason"
                              rows="4"
                              placeholder="Please provide a detailed reason for rejecting this ad..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="hideRejectModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmReject()"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                        Reject Ad
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentAdId = null;

    function showRejectModal(adId) {
        currentAdId = adId;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('reject_reason').value = '';
    }

    function hideRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        currentAdId = null;
    }

    async function approveAd(adId) {
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
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error approving ad:', error);
            alert('Error: Failed to approve ad');
        }
    }

    async function confirmReject() {
        const reason = document.getElementById('reject_reason').value.trim();

        if (!reason) {
            alert('Please provide a reason for rejection');
            return;
        }

        try {
            const response = await fetch(`/admin/ads/${currentAdId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    admin_comments: reason
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                hideRejectModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error rejecting ad:', error);
            alert('Error: Failed to reject ad');
        }
    }

    async function pauseAd(adId) {
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
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error pausing ad:', error);
            alert('Error: Failed to pause ad');
        }
    }

    async function resumeAd(adId) {
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
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error resuming ad:', error);
            alert('Error: Failed to resume ad');
        }
    }

    async function stopAd(adId) {
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
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error stopping ad:', error);
            alert('Error: Failed to stop ad');
        }
    }
</script>
@endpush
