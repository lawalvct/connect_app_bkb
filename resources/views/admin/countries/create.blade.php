@extends('admin.layouts.app')

@section('title', 'Create Country')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Country</h1>
            <p class="text-gray-600">Add a new country to the system</p>
        </div>
        <a href="{{ route('admin.countries.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Countries
        </a>
    </div>
@endsection

@section('content')

    <div class="max-w-6xl mx-auto">
        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md">
            <form action="{{ route('admin.countries.store') }}" method="POST" class="p-6">
                @csrf

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
                               value="{{ old('name') }}"
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
                               value="{{ old('code') }}"
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
                               value="{{ old('code3') }}"
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
                               value="{{ old('numeric_code') }}"
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
                               value="{{ old('phone_code') }}"
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
                               value="{{ old('capital') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g. Abuja.">
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
                               value="{{ old('emoji') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g. 🇬🇧">
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
                               value="{{ old('currency') }}"
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
                               value="{{ old('currency_code') }}"
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
                               value="{{ old('currency_symbol') }}"
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
                            <option value="Africa" {{ old('region') == 'Africa' ? 'selected' : '' }}>Africa</option>
                            <option value="Americas" {{ old('region') == 'Americas' ? 'selected' : '' }}>Americas</option>
                            <option value="Asia" {{ old('region') == 'Asia' ? 'selected' : '' }}>Asia</option>
                            <option value="Europe" {{ old('region') == 'Europe' ? 'selected' : '' }}>Europe</option>
                            <option value="Oceania" {{ old('region') == 'Oceania' ? 'selected' : '' }}>Oceania</option>
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
                               value="{{ old('subregion') }}"
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
                               value="{{ old('timezone') }}"
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
                               value="{{ old('timezone_offset') }}"
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
                                   {{ old('has_dst') ? 'checked' : '' }}
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
                               value="{{ old('latitude') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g. 37.0902">
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
                               value="{{ old('longitude') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g. -95.7129">
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
                                   {{ old('active', true) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <label for="active" class="ml-2 text-sm text-gray-700">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.countries.index') }}"
                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        Cancel
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Create Country
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
