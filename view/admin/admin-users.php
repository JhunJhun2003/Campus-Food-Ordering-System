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
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
requirePermission('manage_users');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$userRepository = new \App\User\Infrastructure\Repositories\UserRepository();
$users = $userRepository->findAll();
$roles = $userRepository->getAllRoles();

// Handle Add User
$addError = '';
$addSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int) ($_POST['role_id'] ?? 3);
    
    if (empty($name) || empty($email) || empty($password)) {
        $addError = 'Name, Email, and Password are required.';
    } else {
        try {
            if ($userRepository->emailExists($email)) {
                $addError = 'Email already registered.';
            } else {
                $userId = $userRepository->createUser($name, $email, $password, $phone, $roleId, true);
                if ($userId > 0) {
                    $addSuccess = 'User added successfully! (Auto-verified)';
                    $users = $userRepository->findAll();
                } else {
                    $addError = 'Failed to add user.';
                }
            }
        } catch (Exception $e) {
            $addError = 'Failed to add user: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            $deleted = $userRepository->deleteUser($userId);
            if ($deleted) {
                $users = $userRepository->findAll();
            }
        } catch (Exception $e) {
            // Handle error
        }
    }
}

// Handle Edit User
$editUser = null;
$editError = '';
$editSuccess = '';

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editUser = $userRepository->getUserForEdit($editId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int) ($_POST['role_id'] ?? 3);
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $editError = 'Name and Email are required.';
    } else {
        try {
            if ($userRepository->emailExistsExcluding($email, $userId)) {
                $editError = 'Email already registered to another user.';
            } else {
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role_id' => $roleId
                ];
                if (!empty($password)) {
                    $data['password'] = $password;
                }
                $updated = $userRepository->updateUser($userId, $data);
                if ($updated) {
                    $editSuccess = 'User updated successfully!';
                    $users = $userRepository->findAll();
                    $editUser = null;
                } else {
                    $editError = 'Failed to update user.';
                }
            }
        } catch (Exception $e) {
            $editError = 'Failed to update user: ' . $e->getMessage();
        }
    }
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Manage Users';
$activePage = 'users';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
        <p class="text-gray-400 text-sm mt-1">View and manage all registered users</p>
    </div>
    <button onclick="openAddUserModal()" class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
        <i class="fa-solid fa-plus"></i>
        <span>Add User</span>
    </button>
</div>

<!-- Users Table -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
    <div class="p-5 flex items-center justify-between border-b border-gray-50">
        <div class="relative w-full max-w-xl">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search users by name or email..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
        </div>
        <div class="flex items-center space-x-3">
            <select id="roleFilter" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo strtolower($role['name']); ?>">
                        <?php echo ucfirst($role['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6">User</th>
                    <th class="py-3 px-6">Email</th>
                    <th class="py-3 px-6">Role</th>
                    <th class="py-3 px-6">Status</th>
                    <th class="py-3 px-6">Joined</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody" class="divide-y divide-gray-100 text-sm text-gray-700">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fa-regular fa-user text-4xl block mb-3"></i>
                            <p class="text-sm font-medium">No users found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            $isVerified = $user->isVerified();
                            $emailVerifiedAt = $user->getEmailVerifiedAt();
                        ?>
                        <tr class="hover:bg-gray-50/50 transition-colors" data-role="<?php echo $user->getRoleName(); ?>">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-medium text-xs
                                        <?php 
                                            echo match($user->getRoleName()) {
                                                'admin' => 'bg-indigo-500',
                                                'staff' => 'bg-purple-500',
                                                default => 'bg-gray-500'
                                            };
                                        ?>
                                    ">
                                        <?php echo strtoupper(substr($user->getName(), 0, 1)); ?>
                                    </div>
                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($user->getName()); ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($user->getEmail()->getValue()); ?></td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                        echo match($user->getRoleName()) {
                                            'admin' => 'bg-indigo-100 text-indigo-800',
                                            'staff' => 'bg-purple-100 text-purple-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>
                                ">
                                    <?php echo ucfirst($user->getRoleName()); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <?php if ($isVerified): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check-circle mr-1"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fa-solid fa-clock mr-1"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-gray-400 text-xs">
                                <?php echo $user->getCreatedAt()->format('M d, Y'); ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-3">
                                    <button onclick="openEditUserModal(<?php echo $user->getId()->getValue(); ?>)" class="text-gray-400 hover:text-indigo-600 edit-btn transition-colors" title="Edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event, <?php echo $user->getId()->getValue(); ?>);">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $user->getId()->getValue(); ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-600 delete-btn transition-colors" title="Delete">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-100 flex items-center justify-between bg-white">
        <p class="text-sm text-gray-400">
            Showing <span class="font-medium text-gray-600"><?php echo count($users); ?></span> users
        </p>
        <nav class="inline-flex -space-x-px rounded-md space-x-2" aria-label="Pagination">
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">
                1
            </button>
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
        </nav>
    </div>
</div>

<!-- ============================================ -->
<!-- ADD USER MODAL -->
<!-- ============================================ -->
<div id="addUserModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Add New User</h2>
            <button onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <?php if ($addError): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($addError); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($addSuccess): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($addSuccess); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="addUserForm">
            <input type="hidden" name="add_user" value="1">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" placeholder="John Doe" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" placeholder="john@example.com" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" placeholder="Min 8 characters" required minlength="8" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" placeholder="09123456789" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select name="role_id" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>">
                            <?php echo ucfirst($role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700">
                <i class="fa-solid fa-info-circle mr-2"></i>
                User will be <strong>auto-verified</strong> and can login immediately.
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Add User</button>
            <button type="button" onclick="closeAddUserModal()" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- EDIT USER MODAL -->
<!-- ============================================ -->
<?php if ($editUser): ?>
<div id="editUserModal" class="modal-overlay fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Edit User</h2>
            <a href="admin-users.php" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>

        <?php if ($editError): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($editError); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($editSuccess): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($editSuccess); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="editUserForm">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editUser['name']); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-gray-400 text-xs">(Leave blank to keep current)</span></label>
                <input type="password" name="password" placeholder="Enter new password to change" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select name="role_id" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo $role['id'] == $editUser['role_id'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update User</button>
            <a href="admin-users.php" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm block text-center">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TOAST -->
<!-- ============================================ -->
<div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

<script>
// ============================================
// SEARCH
// ============================================
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#usersTableBody tr').forEach(row => {
        const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
        const email = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        row.style.display = (name.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none';
    });
});

// ============================================
// ROLE FILTER
// ============================================
document.getElementById('roleFilter').addEventListener('change', function() {
    const role = this.value.toLowerCase();
    document.querySelectorAll('#usersTableBody tr').forEach(row => {
        const rowRole = row.dataset.role?.toLowerCase() || '';
        row.style.display = (role === '' || rowRole === role) ? '' : 'none';
    });
});

// ============================================
// ADD USER MODAL
// ============================================
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
    document.getElementById('addUserModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('addUserForm')?.reset();
}

// ============================================
// EDIT USER MODAL
// ============================================
function openEditUserModal(userId) {
    window.location.href = 'admin-users.php?edit=' + userId;
}

function closeEditUserModal() {
    window.location.href = 'admin-users.php';
}

// ============================================
// CONFIRM DELETE
// ============================================
function confirmDelete(event, userId) {
    event.preventDefault();
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        if (userId === 1) {
            alert('Cannot delete the main admin user.');
            return false;
        }
        event.target.submit();
    }
    return false;
}

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

<?php if ($addSuccess): ?> showToast('<?php echo htmlspecialchars($addSuccess); ?>', 'success'); <?php endif; ?>
<?php if ($addError): ?> showToast('<?php echo htmlspecialchars($addError); ?>', 'error'); <?php endif; ?>
<?php if ($editSuccess): ?> showToast('<?php echo htmlspecialchars($editSuccess); ?>', 'success'); <?php endif; ?>
<?php if ($editError): ?> showToast('<?php echo htmlspecialchars($editError); ?>', 'error'); <?php endif; ?>
</script>

</main>
</body>
</html>