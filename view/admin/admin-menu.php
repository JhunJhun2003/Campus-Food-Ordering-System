<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('manage_menu');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use helpers - NO 'new' keyword!
$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$foodController = getFoodController();

// Handle all requests through controller
$result = $foodController->handleRequest();
$message = $result['message'] ?? null;
$editFood = $result['editFood'] ?? null;

// Get fresh data
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
    <link rel="stylesheet" href="admin-menu.css?v=1">
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
                <a href="admin-profile.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Profile</span>
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
            <button onclick="openAddFoodModal()" class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Item</span>
            </button>
        </div>
        
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
            
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

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3 px-6">Item</th>
                            <th class="py-3 px-6">Category</th>
                            <th class="py-3 px-6">Price</th>
                            <th class="py-3 px-6">Stock</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php if (empty($foods)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-400">
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
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                <?php echo $food->getStock(); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Out of Stock
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center justify-center space-x-3">
                                            <button onclick="openEditFoodModal(<?php echo $food->getId(); ?>)" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Edit">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event);">
                                                <input type="hidden" name="delete_food" value="1">
                                                <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
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

    <!-- ===== ADD FOOD MODAL ===== -->
    <div id="addFoodModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Add New Food Item</h2>
                <button onclick="closeAddFoodModal()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message['message']); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="addFoodForm">
                <input type="hidden" name="add_food" value="1">
                
                <div class="form-group">
                    <label>Food Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g., Cheese Burger" required>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Category <span class="text-red-500">*</span></label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price ($) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" placeholder="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Preparation Time (mins)</label>
                        <input type="number" name="preparation_time" placeholder="15" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe the food item..." rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Image URL (optional)</label>
                    <input type="text" name="image" placeholder="e.g., burger.png">
                </div>
                
                <button type="submit" class="btn-submit">Add Food Item</button>
                <button type="button" onclick="closeAddFoodModal()" class="btn-cancel">Cancel</button>
            </form>
        </div>
    </div>

    <!-- ===== EDIT FOOD MODAL ===== -->
    <div id="editFoodModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Edit Food Item</h2>
                <button onclick="closeEditFoodModal()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message['message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($editFood): ?>
                <form method="POST" action="" id="editFoodForm">
                    <input type="hidden" name="edit_food" value="1">
                    <input type="hidden" name="food_id" value="<?php echo $editFood['id']; ?>">
                    
                    <div class="form-group">
                        <label>Food Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editFood['name']); ?>" required>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Category <span class="text-red-500">*</span></label>
                            <select name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $editFood['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price ($) <span class="text-red-500">*</span></label>
                            <input type="number" name="price" value="<?php echo $editFood['price']; ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" value="<?php echo $editFood['stock']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Preparation Time (mins)</label>
                            <input type="number" name="preparation_time" value="<?php echo $editFood['preparation_time'] ?? 15; ?>" min="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($editFood['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image" value="<?php echo htmlspecialchars($editFood['image'] ?? ''); ?>" placeholder="e.g., burger.png">
                    </div>
                    
                    <button type="submit" class="btn-submit">Update Food Item</button>
                    <button type="button" onclick="closeEditFoodModal()" class="btn-cancel">Cancel</button>
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
            document.querySelectorAll('tbody tr').forEach(row => {
                const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
                const category = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
                row.style.display = (name.includes(searchTerm) || category.includes(searchTerm)) ? '' : 'none';
            });
        });

        // ============================================
        // CATEGORY FILTER
        // ============================================
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const categoryId = this.value;
            document.querySelectorAll('tbody tr').forEach(row => {
                const rowCategory = row.dataset.category || '';
                row.style.display = (categoryId === '' || rowCategory === categoryId) ? '' : 'none';
            });
        });

        // ============================================
        // ADD FOOD MODAL
        // ============================================
        function openAddFoodModal() {
            document.getElementById('addFoodModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAddFoodModal() {
            document.getElementById('addFoodModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('addFoodForm')?.reset();
        }

        document.getElementById('addFoodModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddFoodModal();
        });

        // ============================================
        // EDIT FOOD MODAL
        // ============================================
        function openEditFoodModal(foodId) {
            window.location.href = 'admin-menu.php?edit=' + foodId;
        }

        function closeEditFoodModal() {
            window.location.href = 'admin-menu.php';
        }

        <?php if ($editFood): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('editFoodModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        <?php endif; ?>

        document.getElementById('editFoodModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditFoodModal();
        });

        // ============================================
        // DELETE CONFIRM
        // ============================================
        function confirmDelete(event) {
            if (!confirm('Are you sure you want to delete this food item? This action cannot be undone.')) {
                event.preventDefault();
                return false;
            }
            return true;
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

        <?php if (isset($message)): ?>
            <?php if ($message['success']): ?>
                showToast('<?php echo htmlspecialchars($message['message']); ?>', 'success');
            <?php else: ?>
                showToast('<?php echo htmlspecialchars($message['message']); ?>', 'error');
            <?php endif; ?>
        <?php endif; ?>
    </script>

</body>
</html>