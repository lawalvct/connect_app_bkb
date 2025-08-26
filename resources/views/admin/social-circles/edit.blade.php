@extends('admin.layouts.app')

@section('title', 'Edit Social Circle - ' . $socialCircle->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Social Circle</h1>
            <p class="text-gray-600 mt-1">Update the social circle information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.social-circles.show', $socialCircle) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                <i class="fas fa-eye mr-2"></i>
                View Details
            </a>
            <a href="{{ route('admin.social-circles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Social Circles
            </a>
        </div>
    </div>

    <!-- Current Logo Display -->
    @if($socialCircle->logo_full_url)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Logo</h3>
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full flex items-center justify-center"
                     style="background-color: {{ $socialCircle->color ?? '#6B7280' }}">
                    <img src="{{ $socialCircle->logo_full_url }}"
                         alt="{{ $socialCircle->name }}"
                         class="h-14 w-14 rounded-full object-cover">
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $socialCircle->name }}</p>
                    <p class="text-sm text-gray-500">Current logo will be replaced if you upload a new one</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('admin.social-circles.update', $socialCircle) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required
                           value="{{ old('name', $socialCircle->name) }}"
                           class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2"
                           placeholder="Enter social circle name">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <input type="color" name="color"
                           value="{{ old('color', $socialCircle->color ?? '#6B7280') }}"
                           class="w-full h-10 rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    @error('color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2"
                              placeholder="Enter description (optional)">{{ old('description', $socialCircle->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2 file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-sm text-gray-500 mt-1">Max size: 2MB. Formats: jpeg, png, jpg, gif, svg</p>
                    <p class="text-sm text-gray-400 mt-1">Leave empty to keep current logo</p>
                    @error('logo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="order_by"
                           value="{{ old('order_by', $socialCircle->order_by ?? 0) }}"
                           class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2"
                           placeholder="0" min="0">
                    @error('order_by')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Settings -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Settings</label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_default" value="1"
                                   {{ old('is_default', $socialCircle->is_default) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Default social circle</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $socialCircle->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Active</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_private" value="1"
                                   {{ old('is_private', $socialCircle->is_private) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Private circle</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-gray-600">Current Members</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $socialCircle->users->count() }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-gray-600">Created</p>
                        <p class="text-sm text-gray-900">{{ $socialCircle->created_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $socialCircle->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-gray-600">Last Updated</p>
                        <p class="text-sm text-gray-900">{{ $socialCircle->updated_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $socialCircle->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                <div class="flex gap-3">
                    <a href="{{ route('admin.social-circles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                        Cancel
                    </a>
                    <a href="{{ route('admin.social-circles.show', $socialCircle) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                        <i class="fas fa-eye mr-2"></i>
                        View Details
                    </a>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="confirmDelete()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Update Social Circle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Social Circle</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete "<strong>{{ $socialCircle->name }}</strong>"?
                    This action cannot be undone and will affect all {{ $socialCircle->users->count() }} members of this circle.
                </p>
                <div class="flex space-x-3">
                    <button type="button"
                            onclick="hideDeleteModal()"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="button"
                            onclick="deleteSocialCircle()"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    async function deleteSocialCircle() {
        try {
            const response = await fetch(`/admin/social-circles/{{ $socialCircle->id }}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                alert('Social circle deleted successfully');
                window.location.href = '{{ route("admin.social-circles.index") }}';
            } else {
                alert('Failed to delete social circle: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting social circle:', error);
            alert('Failed to delete social circle');
        } finally {
            hideDeleteModal();
        }
    }

    // Preview color change
    document.querySelector('input[name="color"]').addEventListener('change', function(e) {
        const colorPreview = document.querySelector('.current-logo-bg');
        if (colorPreview) {
            colorPreview.style.backgroundColor = e.target.value;
        }
    });
</script>
@endpush
