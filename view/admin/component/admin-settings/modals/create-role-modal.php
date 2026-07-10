<!-- Create Role Modal -->
<div id="createRoleModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900"><i class="fa-solid fa-plus-circle text-indigo-500 mr-2"></i> Create New Role</h2>
            <button onclick="closeModal('createRoleModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/create-role">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="role_name" name="name" placeholder="e.g., manager, editor" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="flex space-x-2">
                <button type="button" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm" onclick="closeModal('createRoleModal')">Cancel</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Create Role</button>
            </div>
        </form>
    </div>
</div>