<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('update_profile');

use App\User\Presentation\Http\Controllers\UserController;

$userController = new UserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$profile = $userController->getProfile($userId);
$error = '';
$success = '';

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
        } else {
            $error = $result['message'];
        }
    }
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - My Profile';
$activePage = 'profile';
$customCss = 'css/profile.css';

include __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">My Profile</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your personal information and preferences</p>
    </div>

    <!-- Profile Card -->
    <div class="profile-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <!-- Avatar & Basic Info -->
        <div class="bg-gradient-to-r from-emerald-50 to-white p-6 border-b border-slate-100">
            <div class="flex items-center gap-6">
                <div class="avatar-circle"><?php echo strtoupper(substr($profile['name'] ?? 'U', 0, 1)); ?></div>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900"><?php echo htmlspecialchars($profile['name'] ?? 'User'); ?></h2>
                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                    <p class="text-xs text-slate-400 mt-1">Member since <?php echo date('F j, Y', strtotime($profile['created_at'] ?? 'now')); ?></p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="p-6">
            <?php if ($error): ?>
                <div class="alert-error px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-success px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check"></i><span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

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
                    <label>Delivery Address</label>
                    <textarea name="address" rows="2" placeholder="Enter your delivery address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                    <div class="helper-text">Must be at least 8 characters with uppercase, lowercase, and a number</div>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="orders.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4">
            <i class="fa-regular fa-receipt text-2xl text-emerald-500"></i>
            <div><p class="text-sm font-bold text-slate-900">My Orders</p><p class="text-xs text-slate-400">View order history</p></div>
        </a>
        <a href="cart.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4">
            <i class="fa-solid fa-cart-shopping text-2xl text-emerald-500"></i>
            <div><p class="text-sm font-bold text-slate-900">My Cart</p><p class="text-xs text-slate-400">View your cart</p></div>
        </a>
        <a href="../entrance/logout.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4 hover:border-red-200">
            <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
            <div><p class="text-sm font-bold text-slate-900">Logout</p><p class="text-xs text-slate-400">Sign out of your account</p></div>
        </a>
    </div>
</main>

<style>
.avatar-circle {
    width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 700; color: white; background: linear-gradient(135deg, #10B981, #059669);
}
.alert-error { background-color: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
.alert-success { background-color: #D1FAE5; border: 1px solid #6EE7B7; color: #065F46; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.form-group input, .form-group textarea { width: 100%; padding: 12px 16px; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 14px; transition: all 0.2s; font-family: inherit; background: white; color: #1E293B; }
.form-group input:focus, .form-group textarea:focus { outline: none; border-color: #10B981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08); }
.form-group .helper-text { font-size: 12px; color: #94A3B8; margin-top: 4px; }
.btn-primary { width: 100%; padding: 14px; background: #10B981; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.interactive-transition { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.profile-card { animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes slideIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>