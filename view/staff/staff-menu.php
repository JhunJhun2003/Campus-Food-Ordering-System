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

use App\Food\Presentation\Http\Controllers\FoodController;

$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';
$isAdmin = $userRole === 'admin';

// Load food data
$foodController = new FoodController();
$foods = $foodController->index();
$categories = $foodController->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Menu - Foodie</title>
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
        .food-card {
            transition: all 0.2s ease;
        }
        .food-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-in-stock { background: #D1FAE5; color: #065F46; }
        .status-low-stock { background: #FEF3C7; color: #92400E; }
        .status-out-of-stock { background: #FEE2E2; color: #991B1B; }
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
                <a href="staff-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="staff-menu.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
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
            </nav>
        </div>

        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">Menu Management</h1>
                <p class="text-sm text-slate-500">View all food items available</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                    </span>
                    <input type="text" id="searchInput" placeholder="Search menu..." 
                           class="pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 w-48">
                </div>
                <select id="categoryFilter" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($foods)): ?>
                <div class="col-span-full text-center py-12 text-slate-400">
                    <i class="fa-solid fa-utensils text-4xl block mb-3"></i>
                    <p class="text-sm font-medium">No menu items available</p>
                </div>
            <?php else: ?>
                <?php foreach ($foods as $food): ?>
                    <div class="food-card bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden" 
                         data-category="<?php echo $food->getCategoryId(); ?>"
                         data-name="<?php echo strtolower($food->getName()); ?>">
                        
                        <!-- Image Placeholder -->
                        <div class="h-40 bg-gradient-to-br from-indigo-50 to-slate-100 flex items-center justify-center">
                            <?php 
                                $emoji = match($food->getCategoryId()) {
                                    1 => '🍔',
                                    2 => '🍕',
                                    3 => '🥤',
                                    4 => '🍰',
                                    5 => '🍚',
                                    default => '🍽️'
                                };
                            ?>
                            <span class="text-6xl"><?php echo $emoji; ?></span>
                        </div>
                        
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-slate-900 text-lg">
                                    <?php echo htmlspecialchars($food->getName()); ?>
                                </h3>
                                <span class="font-bold text-indigo-600 text-lg">
                                    $<?php echo number_format($food->getPrice(), 2); ?>
                                </span>
                            </div>
                            
                            <?php if ($food->getDescription()): ?>
                                <p class="text-sm text-slate-500 mb-3 line-clamp-2">
                                    <?php echo htmlspecialchars($food->getDescription()); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <!-- Stock Status -->
                                    <?php 
                                        $stock = $food->getStock();
                                        if ($stock > 10): 
                                    ?>
                                        <span class="status-badge status-in-stock">
                                            <i class="fa-solid fa-check-circle mr-1"></i> In Stock
                                        </span>
                                    <?php elseif ($stock > 0): ?>
                                        <span class="status-badge status-low-stock">
                                            <i class="fa-solid fa-exclamation-triangle mr-1"></i> Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-out-of-stock">
                                            <i class="fa-solid fa-circle-xmark mr-1"></i> Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-slate-400">
                                    <i class="fa-regular fa-clock mr-1"></i>
                                    <?php echo $food->getPreparationTime() ?? 15; ?> min
                                </span>
                            </div>
                            
                            <!-- Admin Only Actions -->
                            <?php if ($isAdmin): ?>
                                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                                    <a href="../admin/admin-menu.php?edit=<?php echo $food->getId(); ?>" 
                                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                                        <i class="fa-regular fa-pen-to-square mr-1"></i> Edit
                                    </a>
                                    <form method="POST" action="../admin/admin-menu.php" style="display:inline;" 
                                          onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="delete_food" value="1">
                                        <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors">
                                            <i class="fa-regular fa-trash-can mr-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Stats Footer -->
        <div class="mt-6 bg-white border border-slate-100 rounded-xl shadow-sm p-4 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                Showing <span class="font-medium text-slate-700"><?php echo count($foods); ?></span> items
            </p>
            <div class="flex items-center space-x-4 text-sm text-slate-500">
                <span><span class="font-medium text-emerald-600"><?php 
                    $inStock = array_filter($foods, fn($f) => $f->getStock() > 10);
                    echo count($inStock);
                ?></span> In Stock</span>
                <span><span class="font-medium text-amber-600"><?php 
                    $lowStock = array_filter($foods, fn($f) => $f->getStock() > 0 && $f->getStock() <= 10);
                    echo count($lowStock);
                ?></span> Low Stock</span>
                <span><span class="font-medium text-red-600"><?php 
                    $outOfStock = array_filter($foods, fn($f) => $f->getStock() == 0);
                    echo count($outOfStock);
                ?></span> Out of Stock</span>
            </div>
        </div>
    </main>

    <script>
        // ============================================
        // SEARCH FUNCTIONALITY
        // ============================================
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.food-card').forEach(card => {
                const name = card.dataset.name || '';
                card.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });

        // ============================================
        // CATEGORY FILTER
        // ============================================
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const categoryId = this.value;
            document.querySelectorAll('.food-card').forEach(card => {
                const cardCategory = card.dataset.category || '';
                card.style.display = (categoryId === '' || cardCategory === categoryId) ? '' : 'none';
            });
        });
    </script>

</body>
</html>