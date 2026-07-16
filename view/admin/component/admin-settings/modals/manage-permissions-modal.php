<!-- Manage Permissions Modal -->
<div id="managePermissionsModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900"><i class="fa-solid fa-key text-indigo-500 mr-2"></i> Manage Permissions</h2>
            <button onclick="closeModal('managePermissionsModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="?access_control_action=sync_permissions" id="syncPermissionsForm">
            <input type="hidden" name="access_control_action" value="sync_permissions">
            <input type="hidden" name="role_id" id="perm_role_id" value="">
            <div id="permissionsContainer" class="permissions-container mb-4">
                <div class="text-center py-8 text-slate-500">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mr-2"></i> Loading permissions...
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm" onclick="closeModal('managePermissionsModal')">Cancel</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                    <i class="fa-solid fa-save mr-2"></i> Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>