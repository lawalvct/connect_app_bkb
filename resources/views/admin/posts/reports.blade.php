@extends('admin.layouts.app')

@section('title', 'Post Reports Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Post Reports</h1>
            <p class="text-gray-600">Manage and review all reported posts</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="reportManagement()" x-init="loadReports()">
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Pending Reports</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(report, idx) in reports" :key="report.id">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="idx + 1"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a :href="'/admin/posts/' + report.post_id" class="text-blue-600 hover:underline" x-text="'Post #' + report.post_id"></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.reason_text"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.reporter_name"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusBadge(report.status)">
                                            <span x-text="report.status_text"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.created_at"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a :href="'/admin/posts/' + report.post_id" class="text-indigo-600 hover:text-indigo-900">View Post</a>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="reports.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-flag text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No reports found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function reportManagement() {
    return {
        reports: [],
        async loadReports() {
            try {
                const response = await fetch('/admin/api/post-reports?status=pending', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.reports = data.reports || [];
            } catch (e) {
                this.reports = [];
            }
        },
        getStatusBadge(status) {
            const badges = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'under_review': 'bg-blue-100 text-blue-800',
                'dismissed': 'bg-gray-100 text-gray-800',
                'action_taken': 'bg-red-100 text-red-800',
                'resolved': 'bg-green-100 text-green-800'
            };
            return badges[status] || 'bg-gray-100 text-gray-800';
        }
    }
}
</script>
@endpush
