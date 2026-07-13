<?php
declare(strict_types=1);

session_start();

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/user_helpers.php'; // ✅ ADD THIS

// ✅ Check maintenance mode - staff cannot access during maintenance
checkMaintenanceRedirect();
if (isAdmin()) {
    $_SESSION['error'] = 'Staff pages are for staff members only.';
    header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
    exit();
}
requireStaffAuth();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';

$permissions = getStaffPermissions($userId);

// Check if user can update profile
if (!$permissions['updateProfile']) {
    $_SESSION['error'] = "You do not have permission to access profile.";
    header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
    exit();
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use the helper function
$userController = getUserController();
$profile = $userController->getProfile($userId);

// ... rest of your staff-profile.php code (unchanged) ...

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
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
    if (isset($_POST['change_password'])) {
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
}

$isAdmin = $userRole === 'admin';

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Staff Profile - Foodie';
$activePage = 'profile';
$customCss = 'css/staff-profile.css';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-950">My Profile</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your account information and preferences</p>
    </div>

    <!-- Profile Card -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <!-- Avatar & Basic Info -->
        <div class="bg-gradient-to-r from-purple-50 to-white p-6 border-b border-slate-100">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold text-white bg-gradient-to-br from-purple-500 to-purple-700">
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
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm flex items-center gap-2">
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

            <!-- Edit Profile -->
            <div id="profile-edit" class="profile-tab-content">
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm" required>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm" required>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="09123456789" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm">
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <textarea name="address" rows="2" placeholder="Enter your address" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold text-sm transition-colors">Save Changes</button>
                </form>
            </div>

            <!-- Change Password -->
            <div id="profile-password" class="profile-tab-content hidden">
                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Current Password <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm" required>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">New Password <span class="text-red-500">*</span></label>
                        <input type="password" name="new_password" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm" required minlength="8">
                        <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters</p>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm New Password <span class="text-red-500">*</span></label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm" required>
                    </div>
                    <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold text-sm transition-colors">Change Password</button>
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
        <?php if ($permissions['viewOrders']): ?>
        <a href="staff-orders.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4">
            <i class="fa-solid fa-receipt text-2xl text-purple-500"></i>
            <div>
                <p class="text-sm font-bold text-slate-900">Orders</p>
                <p class="text-xs text-slate-400">Manage orders</p>
            </div>
        </a>
        <?php endif; ?>
        <a href="../entrance/logout.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4 hover:border-rose-200">
            <i class="fa-solid fa-right-from-bracket text-2xl text-rose-500"></i>
            <div>
                <p class="text-sm font-bold text-slate-900">Logout</p>
                <p class="text-xs text-slate-400">Sign out of your account</p>
            </div>
        </a>
    </div>
</main>

<style>
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
</style>

<script>
document.querySelectorAll('.profile-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.profile-tab-btn').forEach(b => {
            b.classList.remove('active', 'border-purple-600', 'text-purple-600');
            b.classList.add('border-transparent', 'text-slate-500');
        });
        this.classList.add('active', 'border-purple-600', 'text-purple-600');
        this.classList.remove('border-transparent', 'text-slate-500');
        document.querySelectorAll('.profile-tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById('profile-' + this.dataset.tab).classList.remove('hidden');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>