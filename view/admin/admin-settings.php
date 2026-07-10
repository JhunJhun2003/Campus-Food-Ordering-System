<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
requirePermission('manage_settings');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

// Get settings
$settings = $adminController->getSettings();

// Get payment methods
$paymentController = getPaymentController();
$paymentMethods = $paymentController->getAllMethods();

// ============================================
// ACCESS CONTROL - Get roles and permissions
// ============================================

use App\AccessControl\Presentation\Http\Controllers\AccessControlController;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use Inc\Database;

$db = Database::getConnection();
$accessControlRepo = new AccessControlRepository($db);

$getAllRolesUseCase = new \App\AccessControl\Application\Usecases\GetAllRolesUseCase($accessControlRepo);
$getAllPermissionsUseCase = new \App\AccessControl\Application\Usecases\GetAllPermissionsUseCase($accessControlRepo);
$assignRoleToUserUseCase = new \App\AccessControl\Application\Usecases\AssignRoleToUserUseCase($accessControlRepo);
$checkPermissionUseCase = new \App\AccessControl\Application\Usecases\CheckPermissionUseCase($accessControlRepo);
$createRoleUseCase = new \App\AccessControl\Application\Usecases\CreateRoleUseCase($accessControlRepo);
$updateRoleUseCase = new \App\AccessControl\Application\Usecases\UpdateRoleUseCase($accessControlRepo);
$deleteRoleUseCase = new \App\AccessControl\Application\Usecases\DeleteRoleUseCase($accessControlRepo);
$syncRolePermissionsUseCase = new \App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase($accessControlRepo);

$controller = new AccessControlController(
    $getAllRolesUseCase,
    $getAllPermissionsUseCase,
    $assignRoleToUserUseCase,
    $checkPermissionUseCase,
    $createRoleUseCase,
    $updateRoleUseCase,
    $deleteRoleUseCase,
    $syncRolePermissionsUseCase
);

$accessControlData = $controller->index();
$roles = $accessControlData['roles'] ?? [];
$permissions = $accessControlData['permissions'] ?? [];

// ============================================
// 3. HANDLE FORM SUBMISSIONS
// ============================================

$success = '';
$error = '';
$editPayment = null;

// Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $result = $adminController->updateSettingsFromRequest();
    
    if (empty($result['failed'])) {
        $success = 'Settings updated successfully!';
        $settings = $adminController->getSettings();
    } else {
        $error = 'Failed to update some settings.';
    }
}

// Add Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment_method'])) {
    $name = trim($_POST['payment_name'] ?? '');
    $accountName = trim($_POST['payment_account_name'] ?? '');
    $accountNumber = trim($_POST['payment_account_number'] ?? '');
    
    if (empty($name)) {
        $error = 'Payment method name is required.';
    } else {
        $result = $paymentController->addMethod($name, $accountName, $accountNumber);
        if ($result['success']) {
            $success = $result['message'];
            $paymentMethods = $paymentController->getAllMethods();
        } else {
            $error = $result['message'];
        }
    }
}

// Update Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_method'])) {
    $id = (int) ($_POST['payment_id'] ?? 0);
    $data = [
        'method_name' => trim($_POST['payment_name'] ?? ''),
        'account_name' => trim($_POST['payment_account_name'] ?? ''),
        'account_number' => trim($_POST['payment_account_number'] ?? ''),
        'is_active' => isset($_POST['payment_is_active']) ? 1 : 0
    ];
    $result = $paymentController->updateMethod($id, $data);
    if ($result['success']) {
        $success = $result['message'];
        $paymentMethods = $paymentController->getAllMethods();
    } else {
        $error = $result['message'];
    }
}

// Delete Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_method'])) {
    $id = (int) ($_POST['payment_id'] ?? 0);
    $result = $paymentController->deleteMethod($id);
    if ($result['success']) {
        $success = $result['message'];
        $paymentMethods = $paymentController->getAllMethods();
    } else {
        $error = $result['message'];
    }
}

// Get edit data
if (isset($_GET['edit_payment'])) {
    $editId = (int) $_GET['edit_payment'];
    foreach ($paymentMethods as $pm) {
        if ($pm->getId() == $editId) {
            $editPayment = $pm;
            break;
        }
    }
}

$activeTab = $_GET['tab'] ?? 'general';

// ============================================
// 4. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Settings';
$activePage = 'settings';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<main class="flex-1 p-8 overflow-y-auto">
    
    <?php include __DIR__ . '/component/admin-settings/header.php'; ?>
    
    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 flex items-center space-x-2">
            <i class="fa-solid fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 flex items-center space-x-2">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <?php include __DIR__ . '/component/admin-settings/tabs.php'; ?>
    
    <!-- Tab Content -->
    <div class="tab-content-wrapper">
        <?php include __DIR__ . '/component/admin-settings/tab-general.php'; ?>
        <?php include __DIR__ . '/component/admin-settings/tab-payment.php'; ?>
        <?php include __DIR__ . '/component/admin-settings/tab-access-control.php'; ?>
        <?php include __DIR__ . '/component/admin-settings/tab-system.php'; ?>
    </div>
    
    <!-- Modals -->
    <?php include __DIR__ . '/component/admin-settings/modals/add-payment-modal.php'; ?>
    <?php if ($editPayment): ?>
        <?php include __DIR__ . '/component/admin-settings/modals/edit-payment-modal.php'; ?>
    <?php endif; ?>
    <?php include __DIR__ . '/component/admin-settings/modals/create-role-modal.php'; ?>
    <?php include __DIR__ . '/component/admin-settings/modals/edit-role-modal.php'; ?>
    <?php include __DIR__ . '/component/admin-settings/modals/manage-permissions-modal.php'; ?>
    
    <!-- Toast -->
    <div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

</main>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// TAB SWITCHING
// ============================================
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-slate-500');
    });
    const targetContent = document.getElementById('tab-' + tabId);
    if (targetContent) {
        targetContent.classList.remove('hidden');
    }
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active', 'border-indigo-600', 'text-indigo-600');
        activeBtn.classList.remove('border-transparent', 'text-slate-500');
    }
    // Update URL
    history.pushState(null, '', '?tab=' + tabId);
}

// ============================================
// MODAL FUNCTIONS
// ============================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// ============================================
// PAYMENT METHODS
// ============================================
function openAddPaymentModal() {
    openModal('addPaymentModal');
}

function closeAddPaymentModal() {
    closeModal('addPaymentModal');
}

// ============================================
// ACCESS CONTROL
// ============================================
function openCreateRoleModal() {
    openModal('createRoleModal');
}

function editRole(roleId) {
    const roles = <?php echo json_encode($roles); ?>;
    const role = roles.find(r => r.id === roleId);
    
    if (role) {
        document.getElementById('edit_role_id').value = roleId;
        document.getElementById('edit_role_name').value = role.name;
        openModal('editRoleModal');
    } else {
        showToast('Role not found', 'error');
    }
}

function deleteRole(roleId, roleName) {
    if (confirm(`Are you sure you want to delete the role "${roleName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Campus-Food-Ordering-System/access-control/delete-role';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'role_id';
        input.value = roleId;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function managePermissions(roleId) {
    if (roleId === 1) {
        showToast('Admin role has all permissions by default.', 'info');
        return;
    }
    
    document.getElementById('perm_role_id').value = roleId;
    const container = document.getElementById('permissionsContainer');
    container.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin text-2xl mr-2"></i> Loading permissions...</div>';
    
    openModal('managePermissionsModal');
    
    fetch(`/Campus-Food-Ordering-System/access-control/get-role-permissions?role_id=${roleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const allPermissions = <?php echo json_encode($permissions); ?>;
                const grouped = {};
                
                allPermissions.forEach(p => {
                    const module = p.module || 'general';
                    if (!grouped[module]) grouped[module] = [];
                    grouped[module].push(p);
                });
                
                let html = '';
                for (const [module, perms] of Object.entries(grouped)) {
                    html += `<div class="mb-4">
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">${module.charAt(0).toUpperCase() + module.slice(1)}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">`;
                    perms.forEach(p => {
                        const checked = data.permissions.some(rp => rp.id === p.id) ? 'checked' : '';
                        html += `<div class="flex items-center space-x-2">
                            <input type="checkbox" id="perm_${p.id}" name="permissions[]" value="${p.id}" ${checked} class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <label for="perm_${p.id}" class="text-sm text-slate-700 cursor-pointer">${p.display_name || p.name}</label>
                        </div>`;
                    });
                    html += `</div></div>`;
                }
                container.innerHTML = html || '<div class="text-center text-slate-500 py-4">No permissions available.</div>';
            } else {
                container.innerHTML = `<div class="text-red-500 text-center p-4">Error loading permissions: ${data.error || 'Unknown error'}</div>`;
            }
        })
        .catch(error => {
            container.innerHTML = `<div class="text-red-500 text-center p-4">Error loading permissions. Please try again.</div>`;
            console.error('Error:', error);
        });
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg transform transition-all duration-300 z-50 max-w-md';
    const colors = {
        success: { bg: '#10B981', text: 'white' },
        error: { bg: '#EF4444', text: 'white' },
        info: { bg: '#3B82F6', text: 'white' }
    };
    const style = colors[type] || colors.success;
    toast.style.background = style.bg;
    toast.style.color = style.text;
    setTimeout(() => {
        toast.classList.remove('translate-y-24', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}

// ============================================
// INITIALIZE ON PAGE LOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has tab parameter
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam && document.querySelector(`.tab-btn[data-tab="${tabParam}"]`)) {
        switchTab(tabParam);
    } else {
        switchTab('general');
    }
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    });
});

<?php if ($success): ?> showToast('<?php echo htmlspecialchars($success); ?>', 'success'); <?php endif; ?>
<?php if ($error): ?> showToast('<?php echo htmlspecialchars($error); ?>', 'error'); <?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>