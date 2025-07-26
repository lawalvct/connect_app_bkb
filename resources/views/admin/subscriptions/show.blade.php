@extends('admin.layouts.app')

@section('title', 'Subscription Details')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscription Details</h1>
            <p class="text-gray-600">View and manage user subscription</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subscriptions.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Subscriptions
            </a>
            @if($subscription->status === 'active')
                <button type="button"
                        onclick="extendSubscription()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-clock mr-2"></i>
                    Extend
                </button>
            @endif
            <button type="button"
                    onclick="editStatus()"
                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-edit mr-2"></i>
                Edit Status
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="subscriptionDetails()" x-init="init()">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Subscription Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Subscription Information -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Subscription Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- User Info -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Subscriber</h4>
                                <div class="flex items-center">
                                    <img class="h-12 w-12 rounded-full"
                                         src="{{ $subscription->user->profile_picture ?? '/images/default-avatar.png' }}"
                                         alt="{{ $subscription->user->username ?? 'User' }}"
                                         onerror="this.src='/images/default-avatar.png'">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $subscription->user->username ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $subscription->user->email ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Plan Info -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Plan Details</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Plan:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->subscription->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Duration:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->subscription->duration_days ?? 'N/A' }} days
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Amount:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->currency }} {{ number_format($subscription->amount, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        @if($subscription->subscription && $subscription->subscription->features)
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Plan Features</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($subscription->subscription->features as $feature)
                                        <div class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            <span class="text-sm text-gray-600">{{ $feature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Payment Method:</span>
                                    <span class="text-sm font-medium text-gray-900 capitalize">
                                        {{ $subscription->payment_method ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Payment Status:</span>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($subscription->payment_status === 'completed') bg-green-100 text-green-800
                                        @elseif($subscription->payment_status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($subscription->payment_status === 'failed') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($subscription->payment_status ?? 'N/A') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Transaction Reference:</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $subscription->transaction_reference ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @if($subscription->customer_id)
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Customer ID:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->customer_id }}
                                        </span>
                                    </div>
                                @endif
                                @if($subscription->stripe_subscription_id)
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Stripe Subscription:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->stripe_subscription_id }}
                                        </span>
                                    </div>
                                @endif
                                @if($subscription->paid_at)
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Paid At:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription->paid_at->format('M d, Y H:i') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Details JSON -->
                        @if($subscription->payment_details)
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Payment Details</h4>
                                <div class="bg-gray-50 rounded-md p-4">
                                    <pre class="text-xs text-gray-600 overflow-x-auto">{{ json_encode($subscription->payment_details, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Subscription Timeline</h3>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li>
                                    <div class="relative pb-8">
                                        <div class="absolute top-4 left-4 mt-0.5 h-full w-0.5 bg-gray-300"></div>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="bg-green-500 h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-plus text-white text-xs"></i>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Subscription created
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    {{ $subscription->created_at->format('M d, Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                @if($subscription->started_at)
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="absolute top-4 left-4 mt-0.5 h-full w-0.5 bg-gray-300"></div>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="bg-blue-500 h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-play text-white text-xs"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">
                                                            Subscription started
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        {{ $subscription->started_at->format('M d, Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                @if($subscription->paid_at)
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="absolute top-4 left-4 mt-0.5 h-full w-0.5 bg-gray-300"></div>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="bg-green-500 h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-credit-card text-white text-xs"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">
                                                            Payment completed
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        {{ $subscription->paid_at->format('M d, Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                @if($subscription->cancelled_at)
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="bg-red-500 h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-times text-white text-xs"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">
                                                            Subscription cancelled
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        {{ $subscription->cancelled_at->format('M d, Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                <li>
                                    <div class="relative">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="bg-gray-400 h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-calendar text-white text-xs"></i>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        @if($subscription->expires_at > now())
                                                            Expires in {{ $subscription->expires_at->diffForHumans() }}
                                                        @else
                                                            Expired {{ $subscription->expires_at->diffForHumans() }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    {{ $subscription->expires_at->format('M d, Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Status Card -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Status</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Subscription Status</label>
                                <div class="mt-1">
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                        @if($subscription->status === 'active') bg-green-100 text-green-800
                                        @elseif($subscription->status === 'expired') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Auto Renewal</label>
                                <div class="mt-1">
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                        @if($subscription->auto_renew === 'Y') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $subscription->auto_renew === 'Y' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            @if($subscription->boost_count > 0)
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Boost Count</label>
                                    <div class="mt-1">
                                        <span class="text-lg font-bold text-primary">
                                            {{ $subscription->boost_count }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        @if($subscription->status === 'active')
                            <button onclick="cancelSubscription()"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-ban mr-2"></i>
                                Cancel Subscription
                            </button>
                        @elseif($subscription->status === 'cancelled')
                            <button onclick="reactivateSubscription()"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-redo mr-2"></i>
                                Reactivate
                            </button>
                        @endif

                        <a href="{{ route('admin.users.show', $subscription->user_id) }}"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-block text-center">
                            <i class="fas fa-user mr-2"></i>
                            View User Profile
                        </a>

                        @if($subscription->subscription)
                            <a href="{{ route('admin.subscriptions.plans.show', $subscription->subscription_id) }}"
                               class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-block text-center">
                                <i class="fas fa-layer-group mr-2"></i>
                                View Plan Details
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Metadata -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Metadata</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">ID:</span>
                                <span class="font-medium text-gray-900">#{{ $subscription->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium text-gray-900">{{ $subscription->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Updated:</span>
                                <span class="font-medium text-gray-900">{{ $subscription->updated_at->format('M d, Y') }}</span>
                            </div>
                            @if($subscription->created_by)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created By:</span>
                                    <span class="font-medium text-gray-900">Admin #{{ $subscription->created_by }}</span>
                                </div>
                            @endif
                            @if($subscription->updated_by)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Updated By:</span>
                                    <span class="font-medium text-gray-900">Admin #{{ $subscription->updated_by }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Edit Status Modal -->
        <div x-show="showEditModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showEditModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Update Subscription Status</h3>
                    <form @submit.prevent="updateStatus()">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select x-model="editForm.status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                                <option value="active">Active</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea x-model="editForm.reason" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                                      placeholder="Optional reason for status change..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showEditModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Extend Modal -->
        <div x-show="showExtendModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showExtendModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Extend Subscription</h3>
                    <form @submit.prevent="extendSub()">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Days to Add</label>
                            <input type="number" x-model="extendForm.days" min="1" max="365" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea x-model="extendForm.reason" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                                      placeholder="Optional reason for extension..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showExtendModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                Extend Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function subscriptionDetails() {
            return {
                showEditModal: false,
                showExtendModal: false,
                editForm: {
                    status: '{{ $subscription->status }}',
                    reason: ''
                },
                extendForm: {
                    days: '',
                    reason: ''
                },

                init() {
                    // Any initialization logic
                },

                updateStatus() {
                    fetch(`{{ route('admin.subscriptions.update-status', $subscription->id) }}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.editForm)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showEditModal = false;
                            alert('Subscription status updated successfully');
                            location.reload();
                        } else {
                            alert(data.message || 'Error updating subscription status');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating subscription:', error);
                        alert('Error updating subscription status');
                    });
                },

                extendSub() {
                    fetch(`{{ route('admin.subscriptions.extend', $subscription->id) }}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.extendForm)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showExtendModal = false;
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message || 'Error extending subscription');
                        }
                    })
                    .catch(error => {
                        console.error('Error extending subscription:', error);
                        alert('Error extending subscription');
                    });
                }
            }
        }

        function editStatus() {
            Alpine.store('subscriptionDetails').showEditModal = true;
        }

        function extendSubscription() {
            Alpine.store('subscriptionDetails').showExtendModal = true;
        }

        function cancelSubscription() {
            if (confirm('Are you sure you want to cancel this subscription?')) {
                // Implementation for cancellation
                alert('Cancel functionality would be implemented here');
            }
        }

        function reactivateSubscription() {
            if (confirm('Are you sure you want to reactivate this subscription?')) {
                // Implementation for reactivation
                alert('Reactivation functionality would be implemented here');
            }
        }
    </script>
@endsection
