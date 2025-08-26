@extends('admin.layouts.app')

@section('title', 'Edit Country - ' . $country->name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Country</h1>
            <p class="text-gray-600">Update {{ $country->name }} information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.countries.show', $country) }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-eye mr-2"></i>
                View Details
            </a>
            <a href="{{ route('admin.countries.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Countries
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <!-- Current Country Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Info</h3>

                    <!-- Flag Display -->
                    <div class="text-center mb-4">
                        <div class="mx-auto h-20 w-32 rounded-lg flex items-center justify-center bg-gray-50 border-2 border-gray-200">
                            <img src="https://flagcdn.com/w80/{{ strtolower($country->code) }}.png"
                                 alt="{{ $country->name }} Flag"
                                 class="h-16 w-24 object-cover rounded shadow-sm"
                                 onerror="this.parentElement.innerHTML='<div class=\'text-3xl\'>{{ $country->emoji ?? '🏳️' }}</div>'">
                        </div>
                        <p class="text-sm text-gray-600 mt-2">{{ $country->name }}</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Users:</span>
                            <span class="text-sm font-medium text-gray-900">{{ $country->users_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $country->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $country->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Region:</span>
                            <span class="text-sm text-gray-900">{{ $country->region ?? 'Not set' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Created:</span>
                            <span class="text-sm text-gray-900">{{ $country->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <!-- Delete Button -->
                    @if($country->users_count == 0)
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <button type="button"
                                    onclick="confirmDelete()"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Country
                            </button>
                        </div>
                    @else
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                <p class="text-xs text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Cannot delete: {{ $country->users_count }} users associated
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Edit Form -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md">
                    <form action="{{ route('admin.countries.update', $country) }}" method="POST" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Basic Info -->
                            <div class="md:col-span-3">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">Basic Information</h3>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Country Name *</label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       required
                                       value="{{ old('name', $country->name) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Nigeria">
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ISO Code 2 -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">ISO Code (2) *</label>
                                <input type="text"
                                       id="code"
                                       name="code"
                                       required
                                       maxlength="2"
                                       value="{{ old('code', $country->code) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                                       placeholder="e.g. NG">
                                @error('code')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ISO Code 3 -->
                            <div>
                                <label for="code3" class="block text-sm font-medium text-gray-700 mb-2">ISO Code (3)</label>
                                <input type="text"
                                       id="code3"
                                       name="code3"
                                       maxlength="3"
                                       value="{{ old('code3', $country->code3) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                                       placeholder="e.g. NGA">
                                @error('code3')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Numeric Code -->
                            <div>
                                <label for="numeric_code" class="block text-sm font-medium text-gray-700 mb-2">Numeric Code</label>
                                <input type="text"
                                       id="numeric_code"
                                       name="numeric_code"
                                       maxlength="3"
                                       value="{{ old('numeric_code', $country->numeric_code) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. 844">
                                @error('numeric_code')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone Code -->
                            <div>
                                <label for="phone_code" class="block text-sm font-medium text-gray-700 mb-2">Phone Code</label>
                                <input type="text"
                                       id="phone_code"
                                       name="phone_code"
                                       value="{{ old('phone_code', $country->phone_code) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. 234">
                                @error('phone_code')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Capital -->
                            <div>
                                <label for="capital" class="block text-sm font-medium text-gray-700 mb-2">Capital</label>
                                <input type="text"
                                       id="capital"
                                       name="capital"
                                       value="{{ old('capital', $country->capital) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Abuja">
                                @error('capital')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Emoji -->
                            <div>
                                <label for="emoji" class="block text-sm font-medium text-gray-700 mb-2">Flag Emoji</label>
                                <input type="text"
                                       id="emoji"
                                       name="emoji"
                                       value="{{ old('emoji', $country->emoji) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. 🇳🇬">
                                @error('emoji')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Currency Info -->
                            <div class="md:col-span-3 mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">Currency Information</h3>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <input type="text"
                                       id="currency"
                                       name="currency"
                                       value="{{ old('currency', $country->currency) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Naira">
                                @error('currency')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Currency Code -->
                            <div>
                                <label for="currency_code" class="block text-sm font-medium text-gray-700 mb-2">Currency Code</label>
                                <input type="text"
                                       id="currency_code"
                                       name="currency_code"
                                       maxlength="3"
                                       value="{{ old('currency_code', $country->currency_code) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                                       placeholder="e.g. NGN">
                                @error('currency_code')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Currency Symbol -->
                            <div>
                                <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                                <input type="text"
                                       id="currency_symbol"
                                       name="currency_symbol"
                                       value="{{ old('currency_symbol', $country->currency_symbol) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. ₦">
                                @error('currency_symbol')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Geographic Info -->
                            <div class="md:col-span-3 mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">Geographic Information</h3>
                            </div>

                            <!-- Region -->
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                                <select id="region"
                                        name="region"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Region</option>
                                    <option value="Africa" {{ old('region', $country->region) == 'Africa' ? 'selected' : '' }}>Africa</option>
                                    <option value="Americas" {{ old('region', $country->region) == 'Americas' ? 'selected' : '' }}>Americas</option>
                                    <option value="Asia" {{ old('region', $country->region) == 'Asia' ? 'selected' : '' }}>Asia</option>
                                    <option value="Europe" {{ old('region', $country->region) == 'Europe' ? 'selected' : '' }}>Europe</option>
                                    <option value="Oceania" {{ old('region', $country->region) == 'Oceania' ? 'selected' : '' }}>Oceania</option>
                                </select>
                                @error('region')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Subregion -->
                            <div>
                                <label for="subregion" class="block text-sm font-medium text-gray-700 mb-2">Subregion</label>
                                <input type="text"
                                       id="subregion"
                                       name="subregion"
                                       value="{{ old('subregion', $country->subregion) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. West Africa">
                                @error('subregion')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Timezone -->
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Default Timezone</label>
                                <input type="text"
                                       id="timezone"
                                       name="timezone"
                                       value="{{ old('timezone', $country->timezone) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Africa/Lagos">
                                @error('timezone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Timezone Offset -->
                            <div>
                                <label for="timezone_offset" class="block text-sm font-medium text-gray-700 mb-2">Timezone Offset</label>
                                <input type="text"
                                       id="timezone_offset"
                                       name="timezone_offset"
                                       value="{{ old('timezone_offset', $country->timezone_offset) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. +01:00">
                                @error('timezone_offset')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Has DST -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Daylight Saving Time</label>
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           id="has_dst"
                                           name="has_dst"
                                           value="1"
                                           {{ old('has_dst', $country->has_dst) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                    <label for="has_dst" class="ml-2 text-sm text-gray-700">Has Daylight Saving Time</label>
                                </div>
                            </div>

                            <!-- Coordinates -->
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                                <input type="number"
                                       id="latitude"
                                       name="latitude"
                                       step="any"
                                       min="-90"
                                       max="90"
                                       value="{{ old('latitude', $country->latitude) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. 9.0820">
                                @error('latitude')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                                <input type="number"
                                       id="longitude"
                                       name="longitude"
                                       step="any"
                                       min="-180"
                                       max="180"
                                       value="{{ old('longitude', $country->longitude) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. 8.6753">
                                @error('longitude')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="md:col-span-3 mt-6">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           id="active"
                                           name="active"
                                           value="1"
                                           {{ old('active', $country->active) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                    <label for="active" class="ml-2 text-sm text-gray-700">Active</label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.countries.show', $country) }}"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Update Country
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function confirmDelete() {
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
</script>
@endpush
