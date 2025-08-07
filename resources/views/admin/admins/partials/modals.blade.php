<!-- Admin Details Modal -->
<div id="adminDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Admin Details</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('adminDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4">
            <div id="adminDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Status Change Confirmation Modal -->
<div id="statusChangeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Confirm Status Change</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('statusChangeModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4">
            <p class="text-gray-600">Are you sure you want to change this admin's status?</p>
            <div id="statusChangeDetails" class="mt-2"></div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t space-x-2">
            <button type="button" class="px-4 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600" onclick="closeModal('statusChangeModal')">
                Cancel
            </button>
            <button type="button" id="confirmStatusChange" class="px-4 py-2 bg-primary text-white text-sm rounded-md hover:bg-primary/90">
                Confirm
            </button>
        </div>
    </div>
</div>
<!-- Password Reset Modal -->
<div id="passwordResetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Password Reset</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('passwordResetModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">This will generate a new temporary password for the admin. The admin will be required to change it on next login.</p>
                    </div>
                </div>
            </div>
            <div id="newPasswordDisplay" class="hidden">
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><strong>New Password:</strong> <span id="newPasswordText" class="font-mono"></span>
                            <button type="button" class="ml-2 px-2 py-1 bg-green-200 text-green-800 text-xs rounded hover:bg-green-300" onclick="copyPassword()">
                                <i class="fas fa-copy"></i> Copy
                            </button></p>
                        </div>
                    </div>
                </div>
                <p class="text-gray-500 text-sm">Please share this password securely with the admin.</p>
            </div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t space-x-2">
            <button type="button" class="px-4 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600" onclick="closeModal('passwordResetModal')">
                Cancel
            </button>
            <button type="button" id="confirmPasswordReset" class="px-4 py-2 bg-yellow-500 text-white text-sm rounded-md hover:bg-yellow-600">
                Reset Password
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between pb-3 border-b bg-red-500 text-white p-4 -m-5 mb-4 rounded-t-md">
            <h3 class="text-lg font-semibold flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Delete
            </h3>
            <button type="button" class="text-red-100 hover:text-white" onclick="closeModal('deleteConfirmModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><strong>Warning:</strong> This action cannot be undone!</p>
                    </div>
                </div>
            </div>
            <p class="text-gray-600 mb-2">Are you sure you want to permanently delete this administrator account?</p>
            <div id="deleteAdminDetails"></div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t space-x-2">
            <button type="button" class="px-4 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600" onclick="closeModal('deleteConfirmModal')">
                Cancel
            </button>
            <button type="button" id="confirmDelete" class="px-4 py-2 bg-red-500 text-white text-sm rounded-md hover:bg-red-600 flex items-center">
                <i class="fas fa-trash mr-2"></i>Delete Admin
            </button>
        </div>
    </div>
</div>

<script>
// Modal helper functions
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Copy password function
function copyPassword() {
    const passwordText = document.getElementById('newPasswordText').textContent;
    navigator.clipboard.writeText(passwordText).then(function() {
        // Show feedback - could enhance this with a toast notification
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.classList.add('bg-green-300');
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-300');
        }, 2000);
    });
}
</script>
