@extends('admin.layouts.app')

@section('title', 'Social Circle Details - ' . $socialCircle->name)

@section('header')
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.social-circles.index') }}"
               class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $socialCircle->name }}</h1>
                <p class="text-gray-600">Social Circle Details</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.social-circles.edit', $socialCircle) }}"
               class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-edit mr-2"></i>
                Edit Circle
            </a>
            <button type="button"
                    onclick="confirmDelete()"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-trash mr-2"></i>
                Delete
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        <!-- Social Circle Information -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Circle Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Logo and Basic Info -->
                    <div class="lg:col-span-1">
                        <div class="text-center">
                            <div class="mx-auto h-32 w-32 rounded-full flex items-center justify-center mb-4"
                                 style="background-color: {{ $socialCircle->color ?? '#6B7280' }}">
                                @if($socialCircle->logo_full_url)
                                    <img src="{{ $socialCircle->logo_full_url }}"
                                         alt="{{ $socialCircle->name }}"
                                         class="h-28 w-28 rounded-full object-cover">
                                @else
                                    <span class="text-white font-bold text-4xl">
                                        {{ strtoupper(substr($socialCircle->name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $socialCircle->name }}</h2>
                            <p class="text-gray-600 mt-1">{{ $socialCircle->description ?? 'No description provided' }}</p>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Status Information -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Status</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Active:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $socialCircle->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $socialCircle->is_active ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Default:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $socialCircle->is_default ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $socialCircle->is_default ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Private:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $socialCircle->is_private ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $socialCircle->is_private ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Circle Properties -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Properties</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Color:</span>
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-200 mr-2"
                                                 style="background-color: {{ $socialCircle->color ?? '#6B7280' }}"></div>
                                            <span class="text-sm text-gray-900">{{ $socialCircle->color ?? '#6B7280' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Order:</span>
                                        <span class="text-sm text-gray-900">{{ $socialCircle->order_by ?? 'Not set' }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Members:</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $socialCircle->users->count() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Creation Info -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Creation Info</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Created:</span>
                                        <span class="text-sm text-gray-900">{{ $socialCircle->created_at->format('M d, Y \a\t H:i') }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Updated:</span>
                                        <span class="text-sm text-gray-900">{{ $socialCircle->updated_at->format('M d, Y \a\t H:i') }}</span>
                                    </div>
                                    @if($socialCircle->creator)
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Created by:</span>
                                        <span class="text-sm text-gray-900">{{ $socialCircle->creator->name }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Statistics</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Total Posts:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            @if(method_exists($socialCircle, 'posts') && $socialCircle->posts)
                                                {{ $socialCircle->posts->count() }}
                                            @else
                                                0
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 w-24">Active Users:</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $socialCircle->users->where('is_active', true)->count() }}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members List -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Users ({{ $socialCircle->users->count() }})</h3>
                    <button type="button"
                            onclick="exportMembers()"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-download mr-1"></i>
                        Export User
                    </button>
                </div>
            </div>
            <div class="p-6">
                @if($socialCircle->users->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
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
                                @foreach($socialCircle->users->take(20) as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($user->profile_image_url)
                                                    <img class="h-10 w-10 rounded-full object-cover"
                                                         src="{{ $user->profile_image_url }}"
                                                         alt="{{ $user->name }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-gray-600 font-medium text-sm">
                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $user->pivot->created_at ? $user->pivot->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="text-primary hover:text-primary font-medium">
                                            View Profile
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($socialCircle->users->count() > 20)
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-600">
                                Showing first 20 members. Total: {{ $socialCircle->users->count() }} members.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg text-gray-500">No members yet</p>
                        <p class="text-sm text-gray-400">Users will appear here when they join this social circle.</p>
                    </div>
                @endif
            </div>
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
                        This action cannot be undone and will affect all members of this circle.
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

    function exportMembers() {
        // Create CSV export of members
        const members = @json($socialCircle->users);
        let csvContent = "Name,Email,Status,Joined Date\n";

        members.forEach(member => {
            const joinedDate = member.pivot && member.pivot.created_at ?
                new Date(member.pivot.created_at).toLocaleDateString() : 'N/A';
            csvContent += `"${member.name}","${member.email}","${member.is_active ? 'Active' : 'Inactive'}","${joinedDate}"\n`;
        });

        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `{{ $socialCircle->name }}-members-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
</script>
@endpush
