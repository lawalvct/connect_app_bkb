@extends('admin.layouts.app')

@section('title', 'Blog Management')
@section('page-title', 'Blog Management')

@section('content')
<div x-data="blogManagement()">
    <div class="mb-6 flex justify-between items-center">
        <div class="flex space-x-4">
            <input type="text" x-model="filters.search" @input.debounce.500ms="fetchBlogs()" 
                   placeholder="Search blogs..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            
            <select x-model="filters.status" @change="fetchBlogs()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>

            <select x-model="filters.type" @change="fetchBlogs()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Types</option>
                <option value="regular">Regular Blog</option>
                <option value="external">External Link</option>
            </select>
        </div>

        <a href="{{ route('admin.blogs.create') }}" 
           class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition">
            <i class="fas fa-plus mr-2"></i>Create Blog
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && blogs.length === 0">
                        <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No blogs found</td></tr>
                    </template>
                    <template x-for="blog in blogs" :key="blog.id">
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img x-show="blog.featured_image" :src="`/storage/${blog.featured_image}`" 
                                         class="w-12 h-12 rounded object-cover mr-3" alt="">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="blog.title"></div>
                                        <div class="text-xs text-gray-500" x-text="blog.excerpt?.substring(0, 50) + '...'"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span x-text="blog.type === 'external' ? 'External Link' : 'Regular Blog'" 
                                      :class="blog.type === 'external' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                      class="px-2 py-1 text-xs rounded-full"></span>
                            </td>
                            <td class="px-6 py-4">
                                <select @change="updateStatus(blog.id, $event.target.value)" 
                                        :value="blog.status"
                                        class="text-xs rounded-full px-2 py-1 border-0"
                                        :class="{
                                            'bg-green-100 text-green-800': blog.status === 'published',
                                            'bg-yellow-100 text-yellow-800': blog.status === 'draft',
                                            'bg-gray-100 text-gray-800': blog.status === 'archived'
                                        }">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="blog.views_count"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="blog.creator?.name"></td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(blog.created_at)"></td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex space-x-2">
                                    <a :href="`/admin/blogs/${blog.id}`" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a :href="`/admin/blogs/${blog.id}/edit`" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button @click="deleteBlog(blog.id)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> results
                </div>
                <div class="flex space-x-2">
                    <button @click="changePage(pagination.current_page - 1)" 
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border rounded disabled:opacity-50">Previous</button>
                    <button @click="changePage(pagination.current_page + 1)" 
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function blogManagement() {
    return {
        blogs: [],
        loading: false,
        filters: { search: '', status: '', type: '' },
        pagination: {},

        init() {
            this.fetchBlogs();
        },

        async fetchBlogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ ...this.filters, page });
                const response = await fetch(`/admin/api/blogs?${params}`);
                const data = await response.json();
                this.blogs = data.data;
                this.pagination = data;
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        async updateStatus(blogId, status) {
            try {
                const response = await fetch(`/admin/api/blogs/${blogId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status })
                });
                if (response.ok) this.fetchBlogs(this.pagination.current_page);
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteBlog(blogId) {
            if (!confirm('Delete this blog?')) return;
            try {
                const response = await fetch(`/admin/blogs/${blogId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (response.ok) this.fetchBlogs(this.pagination.current_page);
            } catch (error) {
                console.error('Error:', error);
            }
        },

        changePage(page) {
            this.fetchBlogs(page);
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
    }
}
</script>
@endpush
@endsection
