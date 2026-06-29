<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;
use App\User\Infrastructure\Repositories\UserRepository;

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

// Get all users from repository
$userRepository = new UserRepository();
$users = $userRepository->findAll();
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
<link rel="stylesheet" href="admin-users.css">
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
        <!-- Page Header -->
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
                <p class="text-gray-400 text-sm mt-1">View and manage all registered users</p>
            </div>
            <button class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                <i class="fa-solid fa-plus"></i>
                <span>Add User</span>
            </button>
        </div>
        
        <!-- Users Table -->
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
            
            <!-- Search & Filter Bar -->
            <div class="p-5 flex items-center justify-between border-b border-gray-50">
                <div class="relative w-full max-w-xl">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </span>
                    <input type="text" placeholder="Search users by name or email..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
                </div>
                <div class="flex items-center space-x-3">
                    <select class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="user">User</option>
                    </select>
                    <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Table -->
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
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <i class="fa-regular fa-user text-4xl block mb-3"></i>
                                    <p class="text-sm font-medium">No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
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
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-emerald-700 bg-emerald-100 rounded-md status-badge">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                            Active
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-400 text-xs">
                                        <?php echo $user->getCreatedAt()->format('M d, Y'); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center justify-center space-x-3">
                                            <button class="text-gray-400 hover:text-indigo-600 transition-colors edit-btn" title="Edit">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <button class="text-gray-400 hover:text-red-600 transition-colors delete-btn" title="Delete">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
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

    <script>
        // Search functionality
        document.querySelector('input[placeholder="Search users by name or email..."]').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
                const email = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Role filter
        document.querySelector('select').addEventListener('change', function() {
            const role = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const roleCell = row.querySelector('td:nth-child(3) span');
                if (!roleCell) return;
                
                const rowRole = roleCell.textContent?.toLowerCase() || '';
                
                if (role === '' || rowRole.includes(role)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this user?')) {
                    // Add delete logic here
                    alert('User deleted!');
                }
            });
        });

        // Edit
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                alert('Edit user functionality coming soon!');
            });
        });

        // Add user
        document.querySelector('.bg-indigo-600')?.addEventListener('click', function() {
            alert('Add user functionality coming soon!');
        });
    </script>

</body>
</html>