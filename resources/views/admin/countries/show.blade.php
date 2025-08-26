@extends('admin.layouts.app')

@section('title', 'Country Details - ' . $country->name)

@section('header')
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.countries.index') }}"
               class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $country->name }}</h1>
                <p class="text-gray-600">Country Details</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.countries.edit', $country) }}"
               class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-edit mr-2"></i>
                Edit Country
            </a>
            <button type="button"
                    onclick="confirmDelete()"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    {{ $country->users_count > 0 ? 'disabled' : '' }}>
                <i class="fas fa-trash mr-2"></i>
                Delete
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        <!-- Country Information -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Country Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Flag and Basic Info -->
                    <div class="lg:col-span-1">
                        <div class="text-center">
                            <div class="mx-auto h-32 w-48 rounded-lg flex items-center justify-center mb-4 bg-gray-50 border-2 border-gray-200">
                                <img src="https://flagcdn.com/w160/{{ strtolower($country->code) }}.png"
                                     alt="{{ $country->name }} Flag"
                                     class="h-24 w-36 object-cover rounded shadow-sm"
                                     onerror="this.parentElement.innerHTML='<div class=\'text-6xl\'>{{ $country->emoji ?? '🏳️' }}</div>'">
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $country->name }}</h2>
                            <div class="flex items-center justify-center mt-2">
                                <span class="text-4xl mr-2">{{ $country->emoji }}</span>
                                <span class="text-lg text-gray-600">{{ $country->capital }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Basic Information -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Basic Information</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">ISO Code:</span>
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $country->code }}</span>
                                    </div>
                                    @if($country->code3)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">ISO-3:</span>
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $country->code3 }}</span>
                                    </div>
                                    @endif
                                    @if($country->numeric_code)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Numeric:</span>
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $country->numeric_code }}</span>
                                    </div>
                                    @endif
                                    @if($country->phone_code)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Phone:</span>
                                        <span class="text-sm text-gray-900">+{{ $country->phone_code }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Status</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Active:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $country->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $country->active ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Users:</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $country->users_count }}</span>
                                    </div>
                                    @if($country->has_dst !== null)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">DST:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $country->has_dst ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $country->has_dst ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Geographic Information -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Geography</h4>
                                <div class="space-y-3">
                                    @if($country->region)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Region:</span>
                                        <span class="text-sm text-gray-900">{{ $country->region }}</span>
                                    </div>
                                    @endif
                                    @if($country->subregion)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Subregion:</span>
                                        <span class="text-sm text-gray-900">{{ $country->subregion }}</span>
                                    </div>
                                    @endif
                                    @if($country->timezone)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Timezone:</span>
                                        <span class="text-sm text-gray-900">{{ $country->timezone }}</span>
                                    </div>
                                    @endif
                                    @if($country->timezone_offset)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">UTC Offset:</span>
                                        <span class="text-sm text-gray-900">{{ $country->timezone_offset }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Currency Information -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Currency</h4>
                                <div class="space-y-3">
                                    @if($country->currency)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Name:</span>
                                        <span class="text-sm text-gray-900">{{ $country->currency }}</span>
                                    </div>
                                    @endif
                                    @if($country->currency_code)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Code:</span>
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $country->currency_code }}</span>
                                    </div>
                                    @endif
                                    @if($country->currency_symbol)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Symbol:</span>
                                        <span class="text-lg text-gray-900">{{ $country->currency_symbol }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Coordinates -->
                            @if($country->latitude || $country->longitude)
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Coordinates</h4>
                                <div class="space-y-3">
                                    @if($country->latitude)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Latitude:</span>
                                        <span class="text-sm text-gray-900">{{ $country->latitude }}°</span>
                                    </div>
                                    @endif
                                    @if($country->longitude)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Longitude:</span>
                                        <span class="text-sm text-gray-900">{{ $country->longitude }}°</span>
                                    </div>
                                    @endif
                                    @if($country->latitude && $country->longitude)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Map:</span>
                                        <a href="https://www.google.com/maps?q={{ $country->latitude }},{{ $country->longitude }}"
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            View on Google Maps
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Creation Info -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">System Info</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Created:</span>
                                        <span class="text-sm text-gray-900">{{ $country->created_at->format('M d, Y \a\t H:i') }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Updated:</span>
                                        <span class="text-sm text-gray-900">{{ $country->updated_at->format('M d, Y \a\t H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Users ({{ $country->users_count }})</h3>
                    @if($country->users_count > 0)
                    <button type="button"
                            onclick="exportUsers()"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-download mr-1"></i>
                        Export Users
                    </button>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @if($recentUsers && $recentUsers->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Joined
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentUsers as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                @if($user->avatar)
                                                    <img class="h-8 w-8 rounded-full"
                                                         src="{{ $user->avatar }}"
                                                         alt="{{ $user->name }}">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">
                                                            {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name ?? 'No Name' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ?? true ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $user->is_active ?? true ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($country->users_count > 10)
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500">
                                Showing 10 most recent users out of {{ $country->users_count }} total users.
                            </p>
                            <a href="{{ route('admin.users.index', ['country' => $country->id]) }}"
                               class="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800">
                                View all users from {{ $country->name }}
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg text-gray-500">No users from {{ $country->name }} yet</p>
                        <p class="text-sm text-gray-400">Users will appear here when they register from this country</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    function confirmDelete() {
        @if($country->users_count > 0)
            alert('Cannot delete country with {{ $country->users_count }} associated users');
            return;
        @endif

        if (confirm('Are you sure you want to delete {{ $country->name }}? This action cannot be undone.')) {
            fetch('{{ route('admin.countries.destroy', $country) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '{{ route('admin.countries.index') }}';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete country'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the country');
            });
        }
    }

    function exportUsers() {
        window.location.href = '{{ route('admin.countries.export') }}?country_id={{ $country->id }}';
    }
</script>
@endpush
