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
// ACCESS CONTROL
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
        if ($pm->id    == $editId) {
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
<script>
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
}
</script>

<!-- ============================================ -->
<!-- PAGE HEADER -->
<!-- ============================================ -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">Settings</h1>
        <p class="text-sm text-slate-500">Manage your system configuration</p>
    </div>
</div>

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

<!-- ============================================ -->
<!-- TABS -->
<!-- ============================================ -->
<div class="mb-6 border-b border-slate-200">
    <nav class="flex space-x-6" id="settingsTabs">
        <button onclick="switchTab('general')" class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'general' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="general">
            <i class="fa-solid fa-sliders mr-2"></i>General
        </button>
        <button onclick="switchTab('payment')" class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'payment' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="payment">
            <i class="fa-solid fa-credit-card mr-2"></i>Payment Methods
        </button>
        <button onclick="switchTab('access-control')" class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'access-control' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="access-control">
            <i class="fa-solid fa-lock mr-2"></i>Access Control
        </button>
        <button onclick="switchTab('system')" class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'system' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="system">
            <i class="fa-solid fa-server mr-2"></i>System
        </button>
    </nav>
</div>

<!-- ============================================ -->
<!-- TAB 1: GENERAL SETTINGS -->
<!-- ============================================ -->
<div id="tab-general" class="tab-content <?php echo $activeTab === 'general' ? '' : 'hidden'; ?>">
    <form method="POST" action="">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-sliders text-indigo-500"></i>
                    <span>General</span>
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Site Name</label>
                        <input type="text" name="setting_site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'FOODIE'); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
                        <input type="email" name="setting_site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'admin@foodie.com'); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" name="setting_site_phone" value="<?php echo htmlspecialchars($settings['site_phone'] ?? '+1234567890'); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Timezone</label>
                        <select name="setting_timezone" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            <option value="Asia/Manila" <?php echo ($settings['timezone'] ?? '') == 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila</option>
                            <option value="Asia/Singapore" <?php echo ($settings['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                            <option value="Asia/Tokyo" <?php echo ($settings['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo</option>
                            <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                            <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-truck text-indigo-500"></i>
                    <span>Order</span>
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Default Preparation Time (minutes)</label>
                        <input type="number" name="setting_preparation_time" value="<?php echo htmlspecialchars($settings['preparation_time'] ?? 15); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cancellation Time (minutes)</label>
                        <input type="number" name="setting_cancellation_time" value="<?php echo htmlspecialchars($settings['cancellation_time'] ?? 5); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                        <select name="setting_currency" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="PHP" <?php echo ($settings['currency'] ?? '') == 'PHP' ? 'selected' : ''; ?>>PHP (₱)</option>
                            <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="GBP" <?php echo ($settings['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="submit" name="save_settings" class="inline-flex items-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Settings</span>
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- TAB 2: PAYMENT METHODS -->
<!-- ============================================ -->
<div id="tab-payment" class="tab-content <?php echo $activeTab === 'payment' ? '' : 'hidden'; ?>">
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-credit-card text-indigo-500"></i>
                    <span>Payment Methods</span>
                </h2>
                <p class="text-sm text-slate-500">Manage payment methods available at checkout</p>
            </div>
            <button onclick="openAddPaymentModal()" class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Method</span>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Number</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($paymentMethods)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No payment methods added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($paymentMethods as $pm): ?>
    <tr>
        <td class="px-6 py-4 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($pm->getName()); ?></td>
        <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($pm->getAccountName() ?? '-'); ?></td>
        <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($pm->getAccountNumber() ?? '-'); ?></td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $pm->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo $pm->isActive() ? 'Active' : 'Inactive'; ?>
            </span>
        </td>
        <td class="px-6 py-4 text-right space-x-2">
            <a href="admin-settings.php?edit_payment=<?php echo $pm->getId(); ?>&tab=payment" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this payment method?');">
                <input type="hidden" name="delete_payment_method" value="1">
                <input type="hidden" name="payment_id" value="<?php echo $pm->getId(); ?>">
                <input type="hidden" name="tab" value="payment">
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TAB 3: ACCESS CONTROL -->
<!-- ============================================ -->
<div id="tab-access-control" class="tab-content <?php echo $activeTab === 'access-control' ? '' : 'hidden'; ?>">
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-users text-indigo-500"></i>
                    <span>Roles & Permissions</span>
                </h2>
                <p class="text-sm text-slate-500">Manage user roles and their permissions</p>
            </div>
            <button onclick="openCreateRoleModal()" class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Create Role</span>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($roles as $role): ?>
                    <div class="bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <span class="font-semibold text-slate-900"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></span>
                                <?php if ($role['id'] === 1): ?>
                                    <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold ml-2">
                                        <i class="fa-solid fa-crown mr-1"></i> Full Access
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500"><?php echo count($role['permissions']); ?> permissions</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($role['id'] !== 1): ?>
                                    <button onclick="editRole(<?php echo $role['id']; ?>)" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded transition-colors">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (!in_array($role['id'], [1, 2, 3])): ?>
                                    <button onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')" class="p-1.5 text-slate-400 hover:text-red-600 rounded transition-colors">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($role['id'] !== 1): ?>
                                    <button onclick="managePermissions(<?php echo $role['id']; ?>)" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded transition-colors">
                                        <i class="fa-solid fa-key text-xs"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 flex items-center px-2">
                                        <i class="fa-solid fa-lock mr-1"></i> Built-in
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <?php if ($role['id'] === 1): ?>
                                <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-xs">
                                    <i class="fa-solid fa-check-circle mr-1"></i> All permissions granted
                                </span>
                            <?php else: ?>
                                <?php 
                                $displayPermissions = array_slice($role['permissions'], 0, 5);
                                foreach ($displayPermissions as $perm): 
                                ?>
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs">
                                        <?php echo htmlspecialchars($perm['display_name']); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($role['permissions']) > 5): ?>
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-400 rounded text-xs">
                                        +<?php echo count($role['permissions']) - 5; ?> more
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TAB 4: SYSTEM SETTINGS -->
<!-- ============================================ -->
<div id="tab-system" class="tab-content <?php echo $activeTab === 'system' ? '' : 'hidden'; ?>">
    <form method="POST" action="">
        <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-server text-indigo-500"></i>
                <span>System Settings</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Maintenance Mode -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Maintenance Mode</label>
                    <div class="flex items-center space-x-4">
                        <select name="setting_maintenance_mode" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Off - System fully accessible</option>
                            <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>On - Maintenance mode active</option>
                        </select>
                        <?php if (($settings['maintenance_mode'] ?? '0') == '1'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fa-solid fa-circle mr-1.5 text-red-500 animate-pulse"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fa-solid fa-circle mr-1.5 text-green-500"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <i class="fa-solid fa-info-circle text-indigo-500 mr-1.5"></i>
                            <span class="font-medium">When maintenance mode is ON:</span>
                        </p>
                        <ul class="text-xs text-slate-500 mt-1.5 space-y-1 list-disc list-inside">
                            <li>Customers and staff cannot login or register</li>
                            <li>Logged-in customers and staff will be automatically logged out</li>
                            <li>Admin can still access the system</li>
                            <li>Guests can still view the landing page and browse menu</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Notification Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notification Email</label>
                    <input type="email" name="setting_notification_email" value="<?php echo htmlspecialchars($settings['notification_email'] ?? 'orders@foodie.com'); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    <p class="text-xs text-slate-400 mt-2">Email address for system notifications</p>
                    
                    <!-- Preview of maintenance message -->
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs font-medium text-yellow-800">Maintenance Message Preview:</p>
                        <p class="text-xs text-yellow-700 mt-1">"The system is currently under maintenance. Login and registration are temporarily unavailable. Please try again later."</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="save_settings" class="inline-flex items-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Settings</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- MODALS -->
<!-- ============================================ -->

<!-- Add Payment Method Modal -->
<div id="addPaymentModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Add Payment Method</h2>
            <button onclick="closeAddPaymentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="add_payment_method" value="1">
            <input type="hidden" name="tab" value="payment">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method Name <span class="text-red-500">*</span></label>
                <input type="text" name="payment_name" placeholder="e.g., K Pay, Wave Pay" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
                <input type="text" name="payment_account_name" placeholder="e.g., Foodie Restaurant" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Number</label>
                <input type="text" name="payment_account_number" placeholder="e.g., 0987654321" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Add Payment Method</button>
            <button type="button" onclick="closeAddPaymentModal()" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Payment Method Modal -->
<?php if ($editPayment): ?>
<div id="editPaymentModal" class="modal-overlay fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Edit Payment Method</h2>
            <a href="admin-settings.php?tab=payment" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="update_payment_method" value="1">
            <input type="hidden" name="payment_id" value="<?php echo $editPayment['id']; ?>">
            <input type="hidden" name="tab" value="payment">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method Name <span class="text-red-500">*</span></label>
                <input type="text" name="payment_name" value="<?php echo htmlspecialchars($editPayment['method_name']); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
                <input type="text" name="payment_account_name" value="<?php echo htmlspecialchars($editPayment['account_name'] ?? ''); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Number</label>
                <input type="text" name="payment_account_number" value="<?php echo htmlspecialchars($editPayment['account_number'] ?? ''); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-slate-700">Active</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="payment_is_active" class="sr-only peer" <?php echo $editPayment['is_active'] ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update Payment Method</button>
            <a href="admin-settings.php?tab=payment" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm block text-center">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

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

<!-- Edit Role Modal -->
<div id="editRoleModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900"><i class="fa-solid fa-pen text-indigo-500 mr-2"></i> Edit Role</h2>
            <button onclick="closeModal('editRoleModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/update-role">
            <input type="hidden" name="role_id" id="edit_role_id" value="">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_role_name" name="name" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="flex space-x-2">
                <button type="button" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Permissions Modal -->
<div id="managePermissionsModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900"><i class="fa-solid fa-key text-indigo-500 mr-2"></i> Manage Permissions</h2>
            <button onclick="closeModal('managePermissionsModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/sync-permissions">
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

<!-- Toast -->
<div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

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

</main>
</body>
</html>