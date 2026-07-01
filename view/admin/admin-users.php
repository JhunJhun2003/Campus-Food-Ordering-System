<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;
use App\User\Infrastructure\Repositories\UserRepository;

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

// Get repository instance
$userRepository = new UserRepository();

// Get all users
$users = $userRepository->findAll();

// Get all roles from repository
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
                $userId = $userRepository->createUser($name, $email, $password, $phone, $roleId);
                if ($userId > 0) {
                    $addSuccess = 'User added successfully!';
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

// Get user data for edit modal
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editUser = $userRepository->getUserForEdit($editId);
}

// Handle Edit User POST
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
            // Check if email exists excluding current user
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
                    $editUser = null; // Close modal
                } else {
                    $editError = 'Failed to update user.';
                }
            }
        } catch (Exception $e) {
            $editError = 'Failed to update user: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Manage Users</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-users.css?v=1">

</head>
<body class="bg-gray-50 flex h-screen text-gray-800 antialiased">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-gray-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-black mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-20 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-black"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-black">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
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
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-red-50 hover:text-red-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
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
                            <option value="<?php echo strtolower($role['role_name']); ?>">
                                <?php echo ucfirst($role['role_name']); ?>
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
                            <th class="py-3 px-6">Joined</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-400">
                                    <i class="fa-regular fa-user text-4xl block mb-3"></i>
                                    <p class="text-sm font-medium">No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
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
    </main>

    <!-- ===== ADD USER MODAL ===== -->
    <div id="addUserModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button onclick="closeAddUserModal()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
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
                
                <div class="form-group">
                    <label>Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" placeholder="john@example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" placeholder="Min 8 characters" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="09123456789">
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo ucfirst($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Add User</button>
            </form>
        </div>
    </div>

    <!-- ===== EDIT USER MODAL ===== -->
    <div id="editUserModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button onclick="closeEditUserModal()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
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

            <?php if ($editUser): ?>
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                    
                    <div class="form-group">
                        <label>Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editUser['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password <span class="text-gray-400 text-xs">(Leave blank to keep current)</span></label>
                        <input type="password" name="password" placeholder="Enter new password to change">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo $role['id'] == $editUser['role_id'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">Update User</button>
                    <button type="button" onclick="closeEditUserModal()" class="btn-cancel">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TOAST ===== -->
    <div id="toast" class="toast"></div>

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
            document.getElementById('addUserModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('active');
            document.body.style.overflow = '';
            // Clear any error/success messages
            const errorDiv = document.querySelector('#addUserModal .bg-red-50');
            const successDiv = document.querySelector('#addUserModal .bg-green-50');
            if (errorDiv) errorDiv.remove();
            if (successDiv) successDiv.remove();
        }

        document.getElementById('addUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddUserModal();
        });

        // ============================================
        // EDIT USER MODAL
        // ============================================
        function openEditUserModal(userId) {
            // Fetch user data via GET parameter
            window.location.href = 'admin-users.php?edit=' + userId;
        }

        function closeEditUserModal() {
            // Redirect to remove edit parameter
            window.location.href = 'admin-users.php';
        }

        // Check if edit modal should be open
        <?php if ($editUser): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('editUserModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        <?php endif; ?>

        document.getElementById('editUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditUserModal();
        });

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
            toast.className = 'toast ' + type;
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        <?php if ($addSuccess): ?>
            showToast('<?php echo htmlspecialchars($addSuccess); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($addError): ?>
            showToast('<?php echo htmlspecialchars($addError); ?>', 'error');
        <?php endif; ?>
        
        <?php if ($editSuccess): ?>
            showToast('<?php echo htmlspecialchars($editSuccess); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($editError): ?>
            showToast('<?php echo htmlspecialchars($editError); ?>', 'error');
        <?php endif; ?>
    </script>

</body>
</html>