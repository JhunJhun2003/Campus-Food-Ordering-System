<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

use App\User\Presentation\Http\Controllers\UserController;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$userController = getUserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

$profile = $userController->getProfile($userId);

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

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Admin Profile';
$activePage = 'profile';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-950">My Profile</h1>
    <p class="text-sm text-slate-500 mt-1">Manage your account information and preferences</p>
</div>

<!-- Profile Card -->
<div class="profile-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
    
    <!-- Avatar & Basic Info -->
    <div class="bg-gradient-to-r from-indigo-50 to-white p-6 border-b border-slate-100">
        <div class="flex items-center gap-6">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($profile['name'] ?? 'A', 0, 1)); ?>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-slate-900"><?php echo htmlspecialchars($profile['name'] ?? 'Admin'); ?></h2>
                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                <p class="text-xs text-slate-400 mt-1">Administrator • Member since <?php echo date('F j, Y', strtotime($profile['created_at'] ?? 'now')); ?></p>
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
                <button class="profile-tab-btn active px-1 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600" data-tab="edit">Edit Profile</button>
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
    <a href="../admin/admin-dashboard.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4">
        <i class="fa-solid fa-house text-2xl text-indigo-500"></i>
        <div>
            <p class="text-sm font-bold text-slate-900">Dashboard</p>
            <p class="text-xs text-slate-400">Go to admin dashboard</p>
        </div>
    </a>
    <a href="../admin/admin-settings.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4">
        <i class="fa-solid fa-gear text-2xl text-indigo-500"></i>
        <div>
            <p class="text-sm font-bold text-slate-900">Settings</p>
            <p class="text-xs text-slate-400">System configuration</p>
        </div>
    </a>
    <a href="../entrance/logout.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md transition-all flex items-center gap-4 hover:border-red-200">
        <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
        <div>
            <p class="text-sm font-bold text-slate-900">Logout</p>
            <p class="text-xs text-slate-400">Sign out of your account</p>
        </div>
    </a>
</div>

<!-- Toast -->
<div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

<style>
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
        border-radius: 12px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .alert-success {
        background-color: #D1FAE5;
        border: 1px solid #6EE7B7;
        color: #065F46;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
        background: linear-gradient(135deg, #4F46E5, #7C3AED);
    }
    .btn-primary {
        width: 100%;
        padding: 14px;
        background: #4F46E5;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-primary:hover {
        background: #4338CA;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .btn-secondary {
        width: 100%;
        padding: 14px;
        background: #F1F5F9;
        color: #475569;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 8px;
    }
    .btn-secondary:hover {
        background: #E2E8F0;
    }
    .profile-tab-btn {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .profile-tab-btn.active {
        color: #4F46E5;
        border-color: #4F46E5;
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
    // ============================================
    // PROFILE TABS
    // ============================================
    document.querySelectorAll('.profile-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.profile-tab-btn').forEach(b => {
                b.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            
            this.classList.add('active', 'border-indigo-600', 'text-indigo-600');
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

    <?php if ($success): ?>
        showToast('<?php echo htmlspecialchars($success); ?>', 'success');
    <?php endif; ?>
    
    <?php if ($error): ?>
        showToast('<?php echo htmlspecialchars($error); ?>', 'error');
    <?php endif; ?>
</script>

</main>
</body>
</html>