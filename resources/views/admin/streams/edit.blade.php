@extends('admin.layouts.app')

@section('title', 'Edit Stream')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Stream</h1>
            <nav class="flex mt-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-primary">Dashboard</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><a href="{{ route('admin.streams.index') }}" class="text-gray-500 hover:text-primary">Streams</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><span class="text-gray-900">Edit: {{ $stream->title }}</span></li>
                </ol>
            </nav>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.streams.show', $stream) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-eye mr-2"></i>View Stream
            </a>
            <a href="{{ route('admin.streams.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Streams
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Stream Information</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($stream->status === 'live') bg-red-100 text-red-800
                            @elseif($stream->status === 'scheduled') bg-yellow-100 text-yellow-800
                            @elseif($stream->status === 'ended') bg-gray-100 text-gray-800
                            @else bg-blue-100 text-blue-800 @endif">
                            {{ ucfirst($stream->status) }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <form id="editStreamForm" x-data="streamForm()" @submit.prevent="submitForm()">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-6">
                            <!-- Stream Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2 required">Stream Title</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                       id="title"
                                       name="title"
                                       x-model="form.title"
                                       required
                                       placeholder="Enter stream title">
                                <div x-show="errors.title" class="text-red-500 text-sm mt-1" x-text="errors.title?.[0]"></div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                          id="description"
                                          name="description"
                                          x-model="form.description"
                                          rows="4"
                                          placeholder="Describe your stream content..."></textarea>
                                <div x-show="errors.description" class="text-red-500 text-sm mt-1" x-text="errors.description?.[0]"></div>
                            </div>

                            <!-- Current Banner -->
                            @if($stream->banner_image)
                            <div x-show="!imagePreview">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Banner</label>
                                <div class="relative inline-block">
                                    <img src="{{ Storage::disk('public')->url($stream->banner_image) }}"
                                         alt="Current banner"
                                         class="h-32 rounded-lg">
                                    <button type="button"
                                            @click="showImageUpload = true"
                                            class="absolute top-2 right-2 bg-blue-500 text-white rounded-full p-1 hover:bg-blue-600">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            @endif

                            <!-- Banner Image Upload -->
                            <div x-show="showImageUpload || !hasCurrentBanner">
                                <label for="banner_image" class="block text-sm font-medium text-gray-700 mb-2">
                                    @if($stream->banner_image) Update @else Add @endif Banner Image
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-primary transition-colors">
                                    <div class="space-y-1 text-center">
                                        <div x-show="!imagePreview">
                                            <i class="fas fa-image text-4xl text-gray-400"></i>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="banner_image" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary-dark focus-within:outline-none">
                                                    <span>Upload a banner</span>
                                                    <input id="banner_image" name="banner_image" type="file" class="sr-only" accept="image/*" @change="handleImageUpload($event)">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                        </div>
                                        <div x-show="imagePreview" class="relative">
                                            <img :src="imagePreview" class="max-h-48 rounded-lg" alt="Banner preview">
                                            <button type="button" @click="removeImage()"
                                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @if($stream->banner_image)
                                <div class="mt-2">
                                    <button type="button"
                                            @click="showImageUpload = false"
                                            class="text-sm text-gray-500 hover:text-gray-700">
                                        Cancel update
                                    </button>
                                </div>
                                @endif
                                <div x-show="errors.banner_image" class="text-red-500 text-sm mt-1" x-text="errors.banner_image?.[0]"></div>
                            </div>

                            <!-- Pricing Section -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Free Minutes -->
                                <div>
                                    <label for="free_minutes" class="block text-sm font-medium text-gray-700 mb-2 required">Free Minutes</label>
                                    <input type="number"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                           id="free_minutes"
                                           name="free_minutes"
                                           x-model="form.free_minutes"
                                           @input="updatePricing()"
                                           min="0"
                                           required
                                           placeholder="0">
                                    <p class="text-gray-500 text-xs mt-1">Set to 0 for paid-only streams</p>
                                    <div x-show="errors.free_minutes" class="text-red-500 text-sm mt-1" x-text="errors.free_minutes?.[0]"></div>
                                </div>

                                <!-- Payment Amount -->
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Payment Amount</label>
                                    <input type="number"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                           id="price"
                                           name="price"
                                           x-model="form.price"
                                           :required="form.free_minutes == 0"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                    <div x-show="errors.price" class="text-red-500 text-sm mt-1" x-text="errors.price?.[0]"></div>
                                </div>

                                <!-- Currency -->
                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2 required">Currency</label>
                                    <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                            id="currency"
                                            name="currency"
                                            x-model="form.currency"
                                            required>
                                        <option value="NGN">NGN - Nigerian Naira</option>
                                        <option value="USD">USD - US Dollar</option>
                                        <option value="EUR">EUR - Euro</option>
                                        <option value="GBP">GBP - British Pound</option>
                                    </select>
                                    <div x-show="errors.currency" class="text-red-500 text-sm mt-1" x-text="errors.currency?.[0]"></div>
                                </div>
                            </div>

                            <!-- Max Viewers -->
                            <div>
                                <label for="max_viewers" class="block text-sm font-medium text-gray-700 mb-2">Maximum Viewers</label>
                                <input type="number"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                       id="max_viewers"
                                       name="max_viewers"
                                       x-model="form.max_viewers"
                                       min="1"
                                       placeholder="Leave empty for unlimited">
                                <p class="text-gray-500 text-xs mt-1">Optional: Set a limit on concurrent viewers</p>
                                <div x-show="errors.max_viewers" class="text-red-500 text-sm mt-1" x-text="errors.max_viewers?.[0]"></div>
                            </div>

                            <!-- Stream Type (Only if not live) -->
                            @if($stream->status !== 'live')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-4">Stream Type</label>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="radio"
                                               id="immediate"
                                               name="stream_type"
                                               value="immediate"
                                               x-model="form.stream_type"
                                               @change="updateStreamType()"
                                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                        <label for="immediate" class="ml-3 block text-sm text-gray-700">
                                            <span class="font-medium">Go Live Immediately</span>
                                            <span class="block text-gray-500">Start broadcasting right after update</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio"
                                               id="scheduled"
                                               name="stream_type"
                                               value="scheduled"
                                               x-model="form.stream_type"
                                               @change="updateStreamType()"
                                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                        <label for="scheduled" class="ml-3 block text-sm text-gray-700">
                                            <span class="font-medium">Schedule for Later</span>
                                            <span class="block text-gray-500">Set a specific date and time</span>
                                        </label>
                                    </div>
                                </div>
                                <div x-show="errors.stream_type" class="text-red-500 text-sm mt-1" x-text="errors.stream_type?.[0]"></div>
                            </div>

                            <!-- Scheduled Date & Time -->
                            <div x-show="form.stream_type === 'scheduled'" x-transition>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2 required">Scheduled Date & Time</label>
                                <input type="datetime-local"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                       id="scheduled_at"
                                       name="scheduled_at"
                                       x-model="form.scheduled_at"
                                       :required="form.stream_type === 'scheduled'"
                                       :min="new Date().toISOString().slice(0, 16)">
                                <div x-show="errors.scheduled_at" class="text-red-500 text-sm mt-1" x-text="errors.scheduled_at?.[0]"></div>
                            </div>
                            @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-yellow-800">Stream is Live</h3>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            Stream type and scheduling cannot be modified while the stream is live.
                                            End the stream to make these changes.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-8">
                            <a href="{{ route('admin.streams.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                            <button type="submit"
                                    :disabled="submitting"
                                    class="px-6 py-2 bg-primary text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!submitting">
                                    <i class="fas fa-save mr-2"></i>Update Stream
                                </span>
                                <span x-show="submitting">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <!-- Stream Preview -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-primary">Stream Preview</h3>
                </div>
                <div class="p-6">
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="text-center">
                            <div class="mb-3 relative">
                                <div x-show="imagePreview" class="relative">
                                    <img :src="imagePreview" class="w-full h-32 object-cover rounded-lg" alt="Banner">
                                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        {{ $stream->status === 'live' ? 'LIVE' : 'PREVIEW' }}
                                    </div>
                                </div>
                                <div x-show="!imagePreview && hasCurrentBanner" class="relative">
                                    <img src="{{ $stream->banner_image ? Storage::disk('public')->url($stream->banner_image) : '' }}"
                                         class="w-full h-32 object-cover rounded-lg" alt="Current banner">
                                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        {{ $stream->status === 'live' ? 'LIVE' : 'PREVIEW' }}
                                    </div>
                                </div>
                                <div x-show="!imagePreview && !hasCurrentBanner" class="w-full h-32 bg-gray-300 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-500"></i>
                                </div>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 mb-2" x-text="form.title || 'Stream Title'"></h4>
                            <p class="text-gray-600 text-sm mb-4" x-text="form.description || 'Stream description will appear here'"></p>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium">Status:</span>
                                    <span class="capitalize">{{ $stream->status }}</span>
                                </div>
                                @if($stream->status !== 'live')
                                <div class="flex justify-between">
                                    <span class="font-medium">Type:</span>
                                    <span class="capitalize" x-text="form.stream_type || 'immediate'"></span>
                                </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="font-medium">Free Time:</span>
                                    <span x-text="form.free_minutes ? form.free_minutes + ' minutes' : 'None'"></span>
                                </div>
                                <div class="flex justify-between" x-show="form.price > 0">
                                    <span class="font-medium">Price:</span>
                                    <span x-text="form.currency + ' ' + (form.price || '0.00')"></span>
                                </div>
                                <div class="flex justify-between" x-show="form.max_viewers">
                                    <span class="font-medium">Max Viewers:</span>
                                    <span x-text="form.max_viewers"></span>
                                </div>
                                @if($stream->status !== 'live')
                                <div class="flex justify-between" x-show="form.stream_type === 'scheduled' && form.scheduled_at">
                                    <span class="font-medium">Scheduled:</span>
                                    <span x-text="formatScheduledDate(form.scheduled_at)"></span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stream Stats -->
            <div class="bg-white shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-green-600">Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium">Total Viewers:</span>
                            <span>{{ $stream->streamViewers()->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Current Viewers:</span>
                            <span>{{ $stream->streamViewers()->where('left_at', null)->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Total Messages:</span>
                            <span>{{ $stream->streamChats()->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Revenue:</span>
                            <span>{{ $stream->currency }} {{ number_format($stream->streamPayments()->sum('amount'), 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Created:</span>
                            <span>{{ $stream->created_at->format('M j, Y') }}</span>
                        </div>
                        @if($stream->started_at)
                        <div class="flex justify-between">
                            <span class="font-medium">Started:</span>
                            <span>{{ $stream->started_at->format('M j, Y H:i') }}</span>
                        </div>
                        @endif
                        @if($stream->ended_at)
                        <div class="flex justify-between">
                            <span class="font-medium">Ended:</span>
                            <span>{{ $stream->ended_at->format('M j, Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function streamForm() {
    return {
        form: {
            title: '{{ $stream->title }}',
            description: '{{ $stream->description }}',
            free_minutes: {{ $stream->free_minutes ?? 0 }},
            price: '{{ $stream->price }}',
            currency: '{{ $stream->currency }}',
            max_viewers: '{{ $stream->max_viewers }}',
            stream_type: '{{ $stream->stream_type ?? 'immediate' }}',
            scheduled_at: '{{ $stream->scheduled_at ? $stream->scheduled_at->format('Y-m-d\TH:i') : '' }}'
        },
        errors: {},
        submitting: false,
        imagePreview: null,
        showImageUpload: false,
        hasCurrentBanner: {{ $stream->banner_image ? 'true' : 'false' }},

        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeImage() {
            this.imagePreview = null;
            document.getElementById('banner_image').value = '';
        },

        updatePricing() {
            // If free minutes is 0, require payment amount
            if (this.form.free_minutes == 0 && !this.form.price) {
                this.form.price = '1.00';
            }
        },

        updateStreamType() {
            // Clear scheduled date when switching to immediate
            if (this.form.stream_type === 'immediate') {
                this.form.scheduled_at = '';
            }
        },

        formatScheduledDate(dateTime) {
            if (!dateTime) return '';
            return new Date(dateTime).toLocaleString();
        },

        async submitForm() {
            this.submitting = true;
            this.errors = {};

            try {
                const formData = new FormData();

                // Add form fields
                Object.keys(this.form).forEach(key => {
                    if (this.form[key] !== null && this.form[key] !== '') {
                        formData.append(key, this.form[key]);
                    }
                });

                // Add banner image if selected
                const bannerInput = document.getElementById('banner_image');
                if (bannerInput.files[0]) {
                    formData.append('banner_image', bannerInput.files[0]);
                }

                // Add CSRF token and method
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('_method', 'PUT');

                const response = await fetch('/admin/streams/{{ $stream->id }}', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Stream updated successfully!');
                    window.location.href = '/admin/streams/{{ $stream->id }}';
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        alert(data.message || 'Error updating stream');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating stream. Please try again.');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>

<style>
.required::after {
    content: " *";
    color: #e53e3e;
}
</style>
@endsection
