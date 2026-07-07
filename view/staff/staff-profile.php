<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

// Check if user has staff or admin role
if (!in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use Inc\Database;

$userController = new UserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// PERMISSION CHECKS
// ============================================
$db = Database::getConnection();
$accessControlRepo = new AccessControlRepository($db);
$checkPermissionUseCase = new CheckPermissionUseCase($accessControlRepo);

// Check permissions for sidebar
$canViewOrders = $checkPermissionUseCase->execute($userId, 'view_orders_staff') || 
                 $checkPermissionUseCase->execute($userId, 'manage_orders');
$canViewMenu = $checkPermissionUseCase->execute($userId, 'manage_menu');
$canUpdateProfile = $checkPermissionUseCase->execute($userId, 'update_profile');

if (!$canUpdateProfile) {
    $_SESSION['error'] = "You do not have permission to access the staff profile.";
    header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
    exit();
}

// Get user profile
$profile = $userController->getProfile($userId);

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }

    $result = $userController->updateProfile($userId, $data);

    if ($result['success']) {
        $success = $result['message'];
        $profile = $userController->getProfile($userId);
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
    } else {
        $error = $result['message'];
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $result = $userController->changePassword($userId, $currentPassword, $newPassword);
        if ($result['success']) {
            $success = 'Password changed successfully!';
        } else {
            $error = $result['message'] ?? 'Failed to change password.';
        }
    }
}

$userRole = $_SESSION['user_role'] ?? 'staff';
$isAdmin = $userRole === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - Foodie</title>
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
        .profile-card {
            animation: slideIn 0.35s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }
        .alert-success {
            background-color: #D1FAE5;
            border: 1px solid #6EE7B7;
            color: #065F46;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
            background: white;
            color: #1E293B;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }
        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #8B5CF6, #6D28D9);
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #8B5CF6;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #7C3AED;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        .profile-tab-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .profile-tab-btn.active {
            color: #8B5CF6;
            border-color: #8B5CF6;
        }
        .profile-tab-content {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
                <span class="text-xs text-gray-400 font-medium mt-1">Staff Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="staff-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <?php if ($canViewOrders): ?>
                <a href="staff-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <?php endif; ?>
                <?php if ($canViewMenu): ?>
                <a href="staff-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <a href="../admin/admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="../admin/admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
                <?php if ($canUpdateProfile): ?>
                <a href="staff-profile.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Profile</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($currentUser['name'] ?? 'S', 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name'] ?? 'Staff'); ?></p>
                    <p class="text-xs text-gray-400"><?php echo ucfirst($userRole); ?></p>
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
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-950">My Profile</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your account information and preferences</p>
        </div>

        <!-- Profile Card -->
        <div class="profile-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Avatar & Basic Info -->
            <div class="bg-gradient-to-r from-purple-50 to-white p-6 border-b border-slate-100">
                <div class="flex items-center gap-6">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($profile['name'] ?? 'S', 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900"><?php echo htmlspecialchars($profile['name'] ?? 'Staff'); ?></h2>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo ucfirst($userRole); ?> • Member since <?php echo date('F j, Y', strtotime($profile['created_at'] ?? 'now')); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="alert-error px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-success px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="border-b border-slate-200 mb-4">
                    <div class="flex space-x-4">
                        <button class="profile-tab-btn active px-1 py-2 text-sm font-medium border-b-2 border-purple-600 text-purple-600" data-tab="edit">Edit Profile</button>
                        <button class="profile-tab-btn px-1 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 border-b-2 border-transparent" data-tab="password">Change Password</button>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div id="profile-edit" class="profile-tab-content">
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-group">
                            <label>Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="09123456789">
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="2" placeholder="Enter your address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div id="profile-password" class="profile-tab-content hidden">
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label>Current Password <span class="text-red-500">*</span></label>
                            <input type="password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label>New Password <span class="text-red-500">*</span></label>
                            <input type="password" name="new_password" required minlength="8">
                            <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters</p>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password <span class="text-red-500">*</span></label>
                            <input type="password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="staff-dashboard.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4">
                <i class="fa-solid fa-house text-2xl text-purple-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">Dashboard</p>
                    <p class="text-xs text-slate-400">Go to staff dashboard</p>
                </div>
            </a>
            <?php if ($canViewOrders): ?>
            <a href="staff-orders.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4">
                <i class="fa-solid fa-receipt text-2xl text-purple-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">Orders</p>
                    <p class="text-xs text-slate-400">Manage orders</p>
                </div>
            </a>
            <?php endif; ?>
            <a href="../entrance/logout.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4 hover:border-red-200">
                <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">Logout</p>
                    <p class="text-xs text-slate-400">Sign out of your account</p>
                </div>
            </a>
        </div>

    </main>

    <!-- ===== TOAST ===== -->
    <div id="toast" class="toast"></div>

    <script>
        // ============================================
        // PROFILE TABS
        // ============================================
        document.querySelectorAll('.profile-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.profile-tab-btn').forEach(b => {
                    b.classList.remove('active', 'border-purple-600', 'text-purple-600');
                    b.classList.add('border-transparent', 'text-slate-500');
                });
                
                this.classList.add('active', 'border-purple-600', 'text-purple-600');
                this.classList.remove('border-transparent', 'text-slate-500');
                
                document.querySelectorAll('.profile-tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                document.getElementById('profile-' + this.dataset.tab).classList.remove('hidden');
            });
        });

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