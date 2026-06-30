<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;
use App\Food\Presentation\Http\Controllers\FoodController;

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

// Get foods from FoodController
$foodController = new FoodController();
$foods = $foodController->index();
$categories = $foodController->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Manage Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-link.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }
        .sidebar-link:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
    </style>
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
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>

                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>

                <a href="admin-menu.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>

                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>

                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>

                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors">
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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Menu</h1>
                <p class="text-gray-400 text-sm mt-1">Manage your food items and categories</p>
            </div>
            <button class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Item</span>
            </button>
        </div>
        
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
            
            <!-- Search & Filter Bar -->
            <div class="p-5 flex items-center justify-between border-b border-gray-50">
                <div class="relative w-full max-w-xl">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </span>
                    <input type="text" placeholder="Search food items..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400" id="searchInput">
                </div>
                <div class="flex items-center space-x-3">
                    <select class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
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
                            <th class="py-3 px-6">Item</th>
                            <th class="py-3 px-6">Category</th>
                            <th class="py-3 px-6">Price</th>
                            <th class="py-3 px-6">Stock</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php if (empty($foods)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <i class="fa-regular fa-utensils text-4xl block mb-3"></i>
                                    <p class="text-sm font-medium">No food items found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($foods as $food): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors" data-category="<?php echo $food->getCategoryId(); ?>">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                                <?php 
                                                    $emoji = match($food->getCategoryId()) {
                                                        1 => '🍔',
                                                        2 => '🍕',
                                                        3 => '🥤',
                                                        4 => '🍰',
                                                        5 => '🍚',
                                                        default => '🍽️'
                                                    };
                                                    echo $emoji;
                                                ?>
                                            </div>
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($food->getName()); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-600">
                                        <?php 
                                            $categoryName = '';
                                            foreach ($categories as $cat) {
                                                if ($cat['id'] == $food->getCategoryId()) {
                                                    $categoryName = $cat['name'];
                                                    break;
                                                }
                                            }
                                            echo htmlspecialchars($categoryName ?: 'Uncategorized');
                                        ?>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-gray-900">$<?php echo number_format($food->getPrice(), 2); ?></td>
                                    <td class="py-4 px-6">
                                        <?php if ($food->getStock() > 0): ?>
                                            <span class="text-emerald-600 font-medium"><?php echo $food->getStock(); ?></span>
                                        <?php else: ?>
                                            <span class="text-red-500 font-medium">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $food->getStock() > 0 
                                                ? 'bg-emerald-100 text-emerald-800' 
                                                : 'bg-red-100 text-red-800'; 
                                            ?>
                                        ">
                                            <?php echo $food->getStock() > 0 ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center justify-center space-x-3">
                                            <button class="text-gray-400 hover:text-indigo-600 transition-colors edit-btn" title="Edit" data-id="<?php echo $food->getId(); ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <button class="text-gray-400 hover:text-red-600 transition-colors delete-btn" title="Delete" data-id="<?php echo $food->getId(); ?>">
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
                    Showing <span class="font-medium text-gray-600"><?php echo count($foods); ?></span> items
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
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
                const category = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
                
                if (name.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const categoryId = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (categoryId === '' || row.dataset.category === categoryId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const foodId = this.dataset.id;
                if (confirm('Are you sure you want to delete this food item?')) {
                    // Add delete logic here
                    alert('Food item ' + foodId + ' deleted!');
                }
            });
        });

        // Edit
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const foodId = this.dataset.id;
                alert('Edit food item ' + foodId + ' coming soon!');
            });
        });

        // Add item
        document.querySelector('.bg-indigo-600')?.addEventListener('click', function() {
            alert('Add food item functionality coming soon!');
        });
    </script>

</body>
</html>