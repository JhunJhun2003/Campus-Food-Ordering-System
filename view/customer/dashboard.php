<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Food\Presentation\Http\Controllers\FoodController;
use App\User\Presentation\Http\Controllers\UserController;

$userController = new UserController();

// Check if user is logged in
if (!$userController->isLoggedIn()) {
    header('Location: ../entrance/login.php');
    exit();
}

$currentUser = $userController->getCurrentUser();

// Get foods from FoodController
$foodController = new FoodController();
$foods = $foodController->index();
$categories = $foodController->getCategories();

// Get cart item count
$userId = $currentUser['id'] ?? null;
$itemCount = 0;
if ($userId) {
    try {
        $cartController = new \App\Cart\Presentation\Http\Controllers\CartController();
        $itemCount = $cartController->getItemCount($userId);
    } catch (\Exception $e) {
        $itemCount = 0;
    }
}

// Map categories for filter buttons
$categoryNames = array_map(fn($cat) => $cat['name'], $categories);
$categoryEmojis = [
    'Burgers' => '🍔',
    'Pizza' => '🍕',
    'Drinks' => '🥤',
    'Sweets' => '🍰',
    'Rice Meals' => '🍚',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Explore Our Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
<link rel="stylesheet" href="dashboard.css">
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- ===== HEADER (EXACTLY AS YOUR DESIGN) ===== -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40 shadow-sm shadow-slate-100/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Brand Logo -->
            <a href="dashboard.php" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-950">
                    <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 interactive-transition">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
            </a>

            <!-- Navigation Links (EXACTLY as your design) -->
            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-bold text-emerald-500 border-b-2 border-emerald-500 pb-1.5 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Order</a>
            </nav>

            <!-- Right Interfaces (EXACTLY as your design) -->
            <div class="flex items-center space-x-6">
                <button onclick="focusSearch()" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-magnifying-glass text-lg"></i>
                </button>
                <a href="cart.php" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                    <span id="header-cart-badge" class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center border-2 border-white shadow-sm transition-all scale-100 <?php echo $itemCount > 0 ? '' : 'hidden'; ?>">
                        <?php echo $itemCount; ?>
                    </span>
                </a>
                <div class="user-dropdown relative">
                    <button onclick="toggleDropdown()" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50 flex items-center gap-2">
                        <i class="fa-regular fa-user text-lg"></i>
                        <span class="text-sm font-medium text-slate-600 hidden sm:inline"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                        <a href="orders.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Orders</a>
                        <hr class="my-1 border-slate-100">
                        <a href="../entrance/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT (EXACTLY AS YOUR DESIGN) ===== -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Explore Our Menu Frame Title -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Explore Our Menu</h1>
        </div>

        <!-- Search Bar (EXACTLY as your design) -->
        <div class="mb-8">
            <div class="relative w-full max-w-2xl">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input 
                    type="text" 
                    id="menu-search-input"
                    oninput="handleSearch()"
                    placeholder="Search food...." 
                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-sm text-slate-800 placeholder-slate-400"
                >
            </div>
        </div>

        <!-- Filter Tabs / Categories (EXACTLY as your design) -->
        <div class="mb-10">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Categories</p>
            <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap pb-2">
                
                <button 
                    onclick="filterCategory('all')" 
                    id="cat-all" 
                    class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition"
                >
                    All
                </button>
                
                <?php foreach ($categoryNames as $name): ?>
                    <button 
                        onclick="filterCategory('<?php echo strtolower($name); ?>')" 
                        id="cat-<?php echo strtolower($name); ?>" 
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition"
                    >
                        <?php echo ($categoryEmojis[$name] ?? '🍽️') . ' ' . $name; ?>
                    </button>
                <?php endforeach; ?>
                
            </div>
        </div>

        <!-- MENU ITEMS GRID (EXACTLY as your design) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="menu-grid-container">
            <!-- Items injected by JavaScript -->
        </div>

        <!-- Fallback Empty State (EXACTLY as your design) -->
        <div id="empty-state" class="hidden text-center py-16">
            <div class="w-20 h-20 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                🔍
            </div>
            <h3 class="text-base font-bold text-slate-800">No dishes matched your criteria</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">Try altering your search text or switching the category tab to find your favorite treat!</p>
        </div>

    </main>

    <!-- TOAST POPUP (EXACTLY as your design) -->
    <div id="add-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800">
        <div class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-cart-arrow-down text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cart Updated</h4>
            <p id="toast-dish-label" class="text-sm font-semibold text-slate-100">Dishes re-added!</p>
        </div>
    </div>

    <!-- FOOTER (EXACTLY as your design) -->
    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; <?php echo date('Y'); ?> FOODIE INC. All rights reserved. Delicious Food, Delivered Fast.
        </div>
    </footer>

    <!-- ===== SCRIPTS ===== -->
    <script>
        // ============================================
        // DROPDOWN TOGGLE
        // ============================================
        function toggleDropdown() {
            const menu = document.getElementById('dropdownMenu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.getElementById('dropdownMenu').classList.add('hidden');
            }
        });

        // ============================================
        // MENU DATA FROM PHP
        // ============================================
        const menuDatabase = <?php 
            $menuData = [];
            foreach ($foods as $food) {
                $emojiMap = [1 => '🍔', 2 => '🍕', 3 => '🥤', 4 => '🍰', 5 => '🍚'];
                $categoryName = '';
                foreach ($categories as $cat) {
                    if ($cat['id'] == $food->getCategoryId()) {
                        $categoryName = $cat['name'];
                        break;
                    }
                }
                $menuData[] = [
                    'id' => $food->getId(),
                    'name' => $food->getName(),
                    'price' => $food->getPrice(),
                    'category' => $categoryName ?: 'Uncategorized',
                    'emoji' => $emojiMap[$food->getCategoryId()] ?? '🍽️',
                    'stock' => $food->getStock()
                ];
            }
            echo json_encode($menuData);
        ?>;

        // ============================================
        // STATE
        // ============================================
        let activeCategory = 'all';
        let searchQuery = '';
        let cartItemCount = <?php echo $itemCount; ?>;

        // ============================================
        // RENDER FUNCTION
        // ============================================
        function renderMenuGrid() {
            const container = document.getElementById('menu-grid-container');
            const emptyState = document.getElementById('empty-state');
            container.innerHTML = '';

            const filteredItems = menuDatabase.filter(item => {
                const matchesCategory = (activeCategory === 'all' || item.category.toLowerCase() === activeCategory);
                const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
                return matchesCategory && matchesSearch;
            });

            if (filteredItems.length === 0) {
                emptyState.classList.remove('hidden');
                container.classList.add('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            container.classList.remove('hidden');

            filteredItems.forEach(item => {
                const card = document.createElement('div');
                card.className = 'menu-card-anim bg-white border border-slate-150 rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all flex items-center justify-between';
                
                const isOutOfStock = (item.stock || 0) <= 0;
                
                card.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-slate-50/50 rounded-xl flex items-center justify-center border border-slate-100 text-3.5xl select-none">
                            ${item.emoji}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">${item.name}</h3>
                            <p class="text-sm font-extrabold text-slate-900 mt-1">$ ${item.price}</p>
                        </div>
                    </div>
                    ${isOutOfStock ? 
                        `<span class="text-xs font-bold text-red-500">Out of Stock</span>` :
                        `<button 
                            onclick="addToCart('${item.name}', ${item.id})" 
                            class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/10 interactive-transition hover:scale-105 active:scale-95"
                        >
                            <i class="fa-solid fa-plus text-sm"></i>
                        </button>`
                    }
                `;
                
                container.appendChild(card);
            });
        }

        // ============================================
        // CATEGORY FILTER
        // ============================================
        function filterCategory(category) {
            activeCategory = category;

            // Update button styles
            document.querySelectorAll('.categories button, .flex.items-center.gap-3 button').forEach(btn => {
                btn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition';
            });

            const activeBtn = document.getElementById(`cat-${category}`);
            if (activeBtn) {
                activeBtn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition';
            }

            renderMenuGrid();
        }

        // ============================================
        // SEARCH HANDLER
        // ============================================
        function handleSearch() {
            const input = document.getElementById('menu-search-input');
            searchQuery = input.value;
            renderMenuGrid();
        }

        // ============================================
        // FOCUS SEARCH
        // ============================================
        function focusSearch() {
            const input = document.getElementById('menu-search-input');
            input.focus();
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // ============================================
        // ADD TO CART
        // ============================================
        function addToCart(dishName, foodId) {
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `food_id=${foodId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cartItemCount = data.item_count || cartItemCount + 1;
                    updateCartBadge();
                    showToast(`"${dishName}" added to cart!`);
                } else {
                    showToast(data.message || 'Failed to add item', false);
                }
            })
            .catch(() => {
                showToast('Failed to add item', false);
            });
        }

        // ============================================
        // UPDATE CART BADGE
        // ============================================
        function updateCartBadge() {
            const badge = document.getElementById('header-cart-badge');
            if (badge) {
                if (cartItemCount > 0) {
                    badge.textContent = cartItemCount;
                    badge.classList.remove('hidden');
                    badge.classList.add('scale-125', 'bg-emerald-600');
                    setTimeout(() => {
                        badge.classList.remove('scale-125', 'bg-emerald-600');
                        badge.classList.add('scale-100');
                    }, 300);
                } else {
                    badge.classList.add('hidden');
                }
            }
        }

        // ============================================
        // TOAST NOTIFICATION
        // ============================================
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('add-toast');
            const label = document.getElementById('toast-dish-label');
            label.innerText = message;

            const icon = toast.querySelector('.text-emerald-400');
            if (isSuccess) {
                icon.innerHTML = '<i class="fa-solid fa-cart-arrow-down text-lg"></i>';
                icon.className = 'text-emerald-400 bg-emerald-500/10 p-2 rounded-xl';
            } else {
                icon.innerHTML = '<i class="fa-solid fa-circle-exclamation text-lg"></i>';
                icon.className = 'text-red-400 bg-red-500/10 p-2 rounded-xl';
            }

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 2500);
        }

        // ============================================
        // INITIALIZE
        // ============================================
        window.onload = function() {
            renderMenuGrid();
        }
    </script>

</body>
</html>