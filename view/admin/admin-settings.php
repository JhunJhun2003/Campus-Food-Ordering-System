<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;
use App\Payment\Presentation\Http\Controllers\PaymentController;
use App\AccessControl\Presentation\Http\Controllers\AccessControlController;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use Inc\Database; // Add this line

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

// Get settings
$settings = $adminController->getSettings();

// Get payment methods from Payment module
$paymentController = new PaymentController();
$paymentMethods = $paymentController->getAllMethods();

// ============================================
// ACCESS CONTROL INITIALIZATION - FIXED
// ============================================
// Get database connection using the Database class
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

// Get Access Control data
$accessControlData = $controller->index();
$roles = $accessControlData['roles'] ?? [];
$permissions = $accessControlData['permissions'] ?? [];
$groupedPermissions = $accessControlData['groupedPermissions'] ?? [];

// Handle form submission for settings
$success = '';
$error = '';
$updateResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $result = $adminController->updateSettingsFromRequest();
    
    if (empty($result['failed'])) {
        $success = 'Settings updated successfully!';
        $settings = $adminController->getSettings();
    } else {
        $error = 'Failed to update some settings.';
    }
}

// Handle Add Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment_method'])) {
    $name = trim($_POST['payment_name'] ?? '');
    $accountName = trim($_POST['payment_account_name'] ?? '');
    $accountNumber = trim($_POST['payment_account_number'] ?? '');
    
    if (empty($name)) {
        $error = 'Payment method name is required.';
    } else {
        $result = $paymentController->addMethod($name, $accountName, $accountNumber);
        if ($result['success']) {
            $success = 'Payment method added successfully!';
            $paymentMethods = $paymentController->getAllMethods();
        } else {
            $error = $result['message'];
        }
    }
}

// Handle Update Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_method'])) {
    $id = (int) ($_POST['payment_id'] ?? 0);
    $name = trim($_POST['payment_name'] ?? '');
    $accountName = trim($_POST['payment_account_name'] ?? '');
    $accountNumber = trim($_POST['payment_account_number'] ?? '');
    $isActive = isset($_POST['payment_is_active']) ? 1 : 0;
    
    $data = [
        'name' => $name,
        'account_name' => $accountName,
        'account_number' => $accountNumber,
        'is_active' => $isActive
    ];
    
    $result = $paymentController->updateMethod($id, $data);
    if ($result['success']) {
        $success = 'Payment method updated successfully!';
        $paymentMethods = $paymentController->getAllMethods();
    } else {
        $error = $result['message'];
    }
}

// Handle Delete Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_method'])) {
    $id = (int) ($_POST['payment_id'] ?? 0);
    $result = $paymentController->deleteMethod($id);
    if ($result['success']) {
        $success = 'Payment method deleted successfully!';
        $paymentMethods = $paymentController->getAllMethods();
    } else {
        $error = $result['message'];
    }
}

// Get edit data
$editPayment = null;
if (isset($_GET['edit_payment'])) {
    $editId = (int) $_GET['edit_payment'];
    foreach ($paymentMethods as $pm) {
        if ($pm['id'] == $editId) {
            $editPayment = $pm;
            break;
        }
    }
}

// Get active tab from URL
$activeTab = $_GET['tab'] ?? 'general';
?>
<!-- Rest of your HTML remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .sidebar-link.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }
        .sidebar-link:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
        .setting-section {
            transition: all 0.2s ease;
        }
        .setting-section:hover {
            border-color: #C7D2FE;
        }
        .payment-method-row:hover {
            background: #F8FAFC;
        }
        
        /* ===== TABS ===== */
        .tab-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .tab-btn.active {
            color: #4F46E5;
            border-color: #4F46E5;
        }
        .tab-content {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== MODALS ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: modalSlideIn 0.3s ease;
        }
        .modal-large {
            max-width: 800px;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0F172A;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94A3B8;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 4px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
            background: white;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .text-muted {
            font-size: 12px;
            color: #94A3B8;
            margin-top: 4px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
        }
        .btn-submit {
            padding: 10px 24px;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #4338CA;
        }
        .btn-cancel {
            padding: 10px 24px;
            background: #F1F5F9;
            color: #475569;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background: #E2E8F0;
        }
        .btn-danger {
            padding: 10px 24px;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: #DC2626;
        }
        
        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            font-size: 14px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 400px;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.success {
            background: #10B981;
        }
        .toast.error {
            background: #EF4444;
        }
        .toast.info {
            background: #3B82F6;
        }
        
        /* ===== SWITCH ===== */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #CBD5E1;
            transition: .3s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background: #4F46E5;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }
        
        /* ===== ACCESS CONTROL ===== */
        .role-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 16px;
            transition: all 0.2s;
        }
        .role-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .role-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: #EEF2FF;
            color: #4F46E5;
        }
        .role-actions .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        .role-actions .btn-icon:hover {
            transform: scale(1.05);
        }
        .btn-icon-edit {
            background: #EEF2FF;
            color: #4F46E5;
        }
        .btn-icon-edit:hover {
            background: #4F46E5;
            color: white;
        }
        .btn-icon-delete {
            background: #FEF2F2;
            color: #EF4444;
        }
        .btn-icon-delete:hover {
            background: #EF4444;
            color: white;
        }
        .btn-icon-permissions {
            background: #ECFDF5;
            color: #10B981;
        }
        .btn-icon-permissions:hover {
            background: #10B981;
            color: white;
        }
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #F8FAFC;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
            transition: all 0.2s;
        }
        .permission-item:hover {
            background: #F1F5F9;
        }
        .permission-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #4F46E5;
        }
        .permission-item label {
            margin: 0;
            cursor: pointer;
            font-size: 13px;
            color: #334155;
        }
        .permissions-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 8px;
        }
        .permissions-container::-webkit-scrollbar {
            width: 6px;
        }
        .permissions-container::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 3px;
        }
        .permissions-container::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        .permissions-container::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #94A3B8;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] flex h-screen text-slate-800 antialiased overflow-hidden">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-slate-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-slate-900 mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-10 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-slate-950"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-slate-950">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-400">Administrator</p>
                </div>
            </div>
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
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
                <button class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'general' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="general">
                    <i class="fa-solid fa-sliders mr-2"></i>General
                </button>
                <button class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'payment' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="payment">
                    <i class="fa-solid fa-credit-card mr-2"></i>Payment Methods
                </button>
                <button class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'access-control' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="access-control">
                    <i class="fa-solid fa-lock mr-2"></i>Access Control
                </button>
                <button class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'system' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" data-tab="system">
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
                    
                    <!-- General Settings -->
                    <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-sliders text-indigo-500"></i>
                            <span>General</span>
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Site Name</label>
                                <input type="text" name="setting_site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'FOODIE'); ?>" 
                                       class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
                                <input type="email" name="setting_site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'admin@foodie.com'); ?>" 
                                       class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Contact Phone</label>
                                <input type="text" name="setting_site_phone" value="<?php echo htmlspecialchars($settings['site_phone'] ?? '+1234567890'); ?>" 
                                       class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
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

                    <!-- Order Settings -->
                    <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-truck text-indigo-500"></i>
                            <span>Order</span>
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Default Preparation Time (minutes)</label>
                                <input type="number" name="setting_preparation_time" value="<?php echo htmlspecialchars($settings['preparation_time'] ?? 15); ?>" 
                                       class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Cancellation Time (minutes)</label>
                                <input type="number" name="setting_cancellation_time" value="<?php echo htmlspecialchars($settings['cancellation_time'] ?? 5); ?>" 
                                       class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
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

                <!-- Submit Button -->
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
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No payment methods added yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paymentMethods as $pm): ?>
                                    <tr class="payment-method-row">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($pm['method_name']); ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($pm['account_name'] ?? '-'); ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($pm['account_number'] ?? '-'); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $pm['is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $pm['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <a href="admin-settings.php?edit_payment=<?php echo $pm['id']; ?>&tab=payment" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this payment method?');">
                                                <input type="hidden" name="delete_payment_method" value="1">
                                                <input type="hidden" name="payment_id" value="<?php echo $pm['id']; ?>">
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
    <div class="space-y-6">
        <!-- Roles Section -->
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
                        <div class="role-card" data-role-id="<?php echo $role['id']; ?>">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="font-semibold text-slate-900"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></span>
                                    <span class="role-badge ml-2"><?php echo count($role['permissions']); ?> perms</span>
                                </div>
                                <div class="role-actions flex space-x-1">
                                    <button onclick="editRole(<?php echo $role['id']; ?>)" class="btn-icon btn-icon-edit" title="Edit Role">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <?php if (!in_array($role['id'], [1, 2, 3])): ?>
                                        <button onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')" class="btn-icon btn-icon-delete" title="Delete Role">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="managePermissions(<?php echo $role['id']; ?>)" class="btn-icon btn-icon-permissions" title="Manage Permissions">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1">
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- All Permissions Section -->
        <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-list text-indigo-500"></i>
                    <span>All Permissions by Module</span>
                </h2>
                <p class="text-sm text-slate-500">Available permissions grouped by module</p>
            </div>
            <div class="p-6">
                <?php foreach ($groupedPermissions as $module => $perms): ?>
                    <div class="mb-4 last:mb-0">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center">
                            <i class="fa-solid fa-folder text-indigo-400 mr-2"></i>
                            <?php echo htmlspecialchars(ucfirst($module)); ?>
                        </h3>
                        <div class="permission-grid">
                            <?php foreach ($perms as $perm): ?>
                                <div class="permission-item">
                                    <span class="text-sm text-slate-700"><?php echo htmlspecialchars($perm['display_name']); ?></span>
                                    <span class="text-xs text-slate-400 ml-auto"><?php echo htmlspecialchars($perm['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
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
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Maintenance Mode</label>
                            <select name="setting_maintenance_mode" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Off</option>
                                <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>On</option>
                            </select>
                            <p class="text-xs text-slate-400 mt-1">When enabled, only admins can access the site</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notification Email</label>
                            <input type="email" name="setting_notification_email" value="<?php echo htmlspecialchars($settings['notification_email'] ?? 'orders@foodie.com'); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
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
    </main>

    <!-- ============================================ -->
    <!-- MODALS -->
    <!-- ============================================ -->

    <!-- ADD PAYMENT METHOD MODAL -->
    <div id="addPaymentModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Add Payment Method</h2>
                <button onclick="closeAddPaymentModal()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="add_payment_method" value="1">
                <input type="hidden" name="tab" value="payment">
                
                <div class="form-group">
                    <label>Payment Method Name <span class="text-red-500">*</span></label>
                    <input type="text" name="payment_name" placeholder="e.g., K Pay, Wave Pay" required>
                </div>
                
                <div class="form-group">
                    <label>Account Name</label>
                    <input type="text" name="payment_account_name" placeholder="e.g., Foodie Restaurant">
                </div>
                
                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="payment_account_number" placeholder="e.g., 0987654321">
                </div>
                
                <button type="submit" class="btn-submit">Add Payment Method</button>
                <button type="button" onclick="closeAddPaymentModal()" class="btn-cancel">Cancel</button>
            </form>
        </div>
    </div>

    <!-- EDIT PAYMENT METHOD MODAL -->
    <?php if ($editPayment): ?>
    <div id="editPaymentModal" class="modal-overlay active">
        <div class="modal">
            <div class="modal-header">
                <h2>Edit Payment Method</h2>
                <a href="admin-settings.php?tab=payment" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="update_payment_method" value="1">
                <input type="hidden" name="payment_id" value="<?php echo $editPayment['id']; ?>">
                <input type="hidden" name="tab" value="payment">
                
                <div class="form-group">
                    <label>Payment Method Name <span class="text-red-500">*</span></label>
                    <input type="text" name="payment_name" value="<?php echo htmlspecialchars($editPayment['method_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Account Name</label>
                    <input type="text" name="payment_account_name" value="<?php echo htmlspecialchars($editPayment['account_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="payment_account_number" value="<?php echo htmlspecialchars($editPayment['account_number'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-slate-700">Active</span>
                        <label class="switch">
                            <input type="checkbox" name="payment_is_active" <?php echo $editPayment['is_active'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">Update Payment Method</button>
                <a href="admin-settings.php?tab=payment" class="btn-cancel block text-center">Cancel</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

<!-- CREATE ROLE MODAL -->
<div id="createRoleModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fa-solid fa-plus-circle text-indigo-500"></i> Create New Role</h2>
            <button onclick="closeModal('createRoleModal')" class="modal-close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/create-role">
            <div class="form-group">
                <label for="role_name">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="role_name" name="name" placeholder="e.g., manager, editor" required>
                <p class="text-muted">Use lowercase letters and underscores (e.g., content_manager)</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('createRoleModal')">Cancel</button>
                <button type="submit" class="btn-submit">Create Role</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ROLE MODAL -->
<div id="editRoleModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen text-indigo-500"></i> Edit Role</h2>
            <button onclick="closeModal('editRoleModal')" class="modal-close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/update-role">
            <input type="hidden" name="role_id" id="edit_role_id" value="">
            <div class="form-group">
                <label for="edit_role_name">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_role_name" name="name" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="submit" class="btn-submit">Update Role</button>
            </div>
        </form>
    </div>
</div>

<!-- MANAGE PERMISSIONS MODAL -->
<div id="managePermissionsModal" class="modal-overlay">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2><i class="fa-solid fa-key text-indigo-500"></i> Manage Permissions</h2>
            <button onclick="closeModal('managePermissionsModal')" class="modal-close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="/Campus-Food-Ordering-System/access-control/sync-permissions">
            <input type="hidden" name="role_id" id="perm_role_id" value="">
            <div id="permissionsContainer" class="permissions-container">
                <div class="loading">
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading permissions...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('managePermissionsModal')">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-save mr-2"></i> Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- ===== TOAST ===== -->
    <div id="toast" class="toast"></div>

    <script>
        // ============================================
        // TABS
        // ============================================
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
                    b.classList.add('border-transparent', 'text-slate-500');
                });
                
                // Add active class to clicked tab
                this.classList.add('active', 'border-indigo-600', 'text-indigo-600');
                this.classList.remove('border-transparent', 'text-slate-500');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show the target tab content
                const tabId = this.dataset.tab;
                const targetContent = document.getElementById('tab-' + tabId);
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                }
            });
        });

        // ============================================
        // MODALS
        // ============================================
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // ============================================
        // PAYMENT MODALS
        // ============================================
        function openAddPaymentModal() {
            openModal('addPaymentModal');
        }

        function closeAddPaymentModal() {
            closeModal('addPaymentModal');
        }

// ============================================
// ACCESS CONTROL FUNCTIONS
// ============================================
function openCreateRoleModal() {
    openModal('createRoleModal');
}

function editRole(roleId) {
    // Fetch role data
    fetch(`/Campus-Food-Ordering-System/access-control/get-role-permissions?role_id=${roleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const roles = <?php echo json_encode($roles); ?>;
                const role = roles.find(r => r.id == roleId);
                
                document.getElementById('edit_role_id').value = roleId;
                document.getElementById('edit_role_name').value = role ? role.name : '';
                openModal('editRoleModal');
            }
        })
        .catch(error => {
            showToast('Error loading role data', 'error');
            console.error(error);
        });
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
    document.getElementById('perm_role_id').value = roleId;
    const container = document.getElementById('permissionsContainer');
    container.innerHTML = '<div class="loading"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading permissions...</div>';
    openModal('managePermissionsModal');
    
    // Define supported permissions for each system role to match UI/UX design capabilities
    const allowedPermissionsByRole = {
        1: ['view_dashboard', 'manage_users', 'manage_menu', 'manage_orders', 'view_reports', 'view_orders', 'update_order_status', 'view_menu', 'add_to_cart', 'place_orders', 'update_profile'], // Admin
        2: ['view_dashboard', 'manage_menu', 'manage_orders', 'view_orders', 'update_order_status', 'view_menu', 'update_profile'], // Staff
        3: ['view_menu', 'add_to_cart', 'place_orders', 'view_orders', 'update_profile'] // Customer / User
    };
    
    // Fetch role permissions
    fetch(`/Campus-Food-Ordering-System/access-control/get-role-permissions?role_id=${roleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const allPermissions = <?php echo json_encode($permissions); ?>;
                
                // Group permissions by module
                const grouped = {};
                allPermissions.forEach(p => {
                    if (!grouped[p.module]) {
                        grouped[p.module] = [];
                    }
                    grouped[p.module].push(p);
                });
                
                let html = '';
                for (const [module, perms] of Object.entries(grouped)) {
                    // Filter permissions based on role capabilities/UI design limits
                    const filteredPerms = perms.filter(p => {
                        return !allowedPermissionsByRole[roleId] || allowedPermissionsByRole[roleId].includes(p.name);
                    });
                    
                    if (filteredPerms.length === 0) {
                        continue; // Hide module section if no permissions are allowed/supported
                    }
                    
                    html += `<div class="mb-4">
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">${module.charAt(0).toUpperCase() + module.slice(1)}</h4>
                        <div class="permission-grid">`;
                    
                    filteredPerms.forEach(p => {
                        const checked = data.permissions.some(rp => rp.id == p.id) ? 'checked' : '';
                        html += `<div class="permission-item">
                            <input type="checkbox" id="perm_${p.id}" name="permissions[]" value="${p.id}" ${checked}>
                            <label for="perm_${p.id}">${p.display_name}</label>
                        </div>`;
                    });
                    
                    html += `</div></div>`;
                }
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div class="text-red-500 text-center p-4">Error loading permissions: ${data.error}</div>`;
            }
        })
        .catch(error => {
            container.innerHTML = `<div class="text-red-500 text-center p-4">Error loading permissions</div>`;
            console.error(error);
        });
}
        // ============================================
        // TOAST
        // ============================================
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        <?php if ($success): ?>
            showToast('<?php echo htmlspecialchars($success); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($error): ?>
            showToast('<?php echo htmlspecialchars($error); ?>', 'error');
        <?php endif; ?>
    </script>

</body>
</html>