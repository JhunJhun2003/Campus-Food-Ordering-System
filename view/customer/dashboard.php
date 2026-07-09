<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';

// Guest access is allowed on the dashboard. No requireCustomerAuth() check is needed here.

// ============================================
// 1. BUSINESS LOGIC
// ============================================

use App\User\Presentation\Http\Controllers\UserController;
use App\Food\Presentation\Http\Controllers\FoodControllerFactory;

$userController = getUserController();
$currentUser = $userController->getCurrentUser();
$permissions = getCustomerPermissions();

// ✅ Get food controller from factory - NO 'new' keyword!
$foodController = FoodControllerFactory::getInstance();
$foods = $foodController->index();
$categories = $foodController->getCategories();

// ✅ Get cart controller using helper
$cartController = getCartController();

// Get cart count
$itemCount = 0;
if ($currentUser && isset($currentUser['id'])) {
    try {
        $itemCount = $cartController->getItemCount($currentUser['id']);
    } catch (\Exception $e) {
        $itemCount = 0;
    }
}

// Prepare menu data for JavaScript
$categoryNames = array_map(fn($cat) => $cat['name'], $categories);
$categoryEmojis = ['Burgers' => '🍔', 'Pizza' => '🍕', 'Drinks' => '🥤', 'Sweets' => '🍰', 'Rice Meals' => '🍚'];
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

// ============================================
// 2. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Explore Our Menu';
$activePage = 'dashboard';
$customCss = 'css/dashboard.css';

include __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Explore Our Menu</h1>
    </div>

    <!-- Search Bar -->
    <div class="mb-8">
        <div class="relative w-full max-w-2xl">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="menu-search-input" oninput="handleSearch()" placeholder="Search food...." 
                   class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-sm text-slate-800 placeholder-slate-400">
        </div>
    </div>

    <!-- Categories Filter -->
    <div class="mb-10">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Categories</p>
        <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap pb-2">
            <button onclick="filterCategory('all')" id="cat-all" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition">All</button>
            <?php foreach ($categoryNames as $name): ?>
                <button onclick="filterCategory('<?php echo strtolower($name); ?>')" id="cat-<?php echo strtolower($name); ?>" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition">
                    <?php echo ($categoryEmojis[$name] ?? '🍽️') . ' ' . $name; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="menu-grid-container"></div>

    <!-- Empty State -->
    <div id="empty-state" class="hidden text-center py-16">
        <div class="w-20 h-20 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">🔍</div>
        <h3 class="text-base font-bold text-slate-800">No dishes matched your criteria</h3>
        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">Try altering your search text or switching the category tab to find your favorite treat!</p>
    </div>

</main>

<script>
// Menu Data
const menuDatabase = <?php echo json_encode($menuData); ?>;
let activeCategory = 'all';
let searchQuery = '';
let cartItemCount = <?php echo $itemCount; ?>;

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
        const isOutOfStock = (item.stock || 0) <= 0;
        const stockDisplay = item.stock || 0;
        
        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-150 rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all flex items-center justify-between';
        card.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-slate-50/50 rounded-xl flex items-center justify-center border border-slate-100 text-3.5xl select-none">${item.emoji}</div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">${item.name}</h3>
                    <p class="text-sm font-extrabold text-slate-900 mt-1">$ ${item.price}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-medium text-slate-500">Stock: <span class="font-bold ${stockDisplay <= 3 ? 'text-red-500' : 'text-emerald-600'}">${stockDisplay}</span></span>
                        ${stockDisplay <= 3 && stockDisplay > 0 ? '<span class="text-xs text-red-500 font-bold">⚠️ Low Stock</span>' : ''}
                    </div>
                </div>
            </div>
            ${isOutOfStock ? 
                `<span class="text-xs font-bold text-red-500 bg-red-50 px-3 py-1 rounded-full">Out of Stock</span>` :
                `<button class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/10 interactive-transition hover:scale-105 active:scale-95 add-to-cart-btn" data-id="${item.id}" data-name="${item.name}" data-stock="${item.stock}">
                    <i class="fa-solid fa-plus text-sm"></i>
                </button>`
            }
        `;
        container.appendChild(card);
    });

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.dataset.id;
            const foodName = this.dataset.name;
            const stock = parseInt(this.dataset.stock);
            if (stock <= 0) {
                showToast('Sorry, this item is out of stock!', false);
                return;
            }
            addToCart(foodName, foodId, stock);
        });
    });
}

function filterCategory(category) {
    activeCategory = category;
    document.querySelectorAll('.flex.items-center.gap-3 button').forEach(btn => {
        btn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition';
    });
    const activeBtn = document.getElementById(`cat-${category}`);
    if (activeBtn) {
        activeBtn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition';
    }
    renderMenuGrid();
}

function handleSearch() {
    searchQuery = document.getElementById('menu-search-input').value;
    renderMenuGrid();
}

function addToCart(dishName, foodId, availableStock) {
    const isLoggedIn = <?php echo (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        window.location.href = '/Campus-Food-Ordering-System/view/entrance/login.php';
        return;
    }

    const buttons = document.querySelectorAll('.add-to-cart-btn');
    buttons.forEach(btn => {
        if (btn.dataset.id == foodId) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i>';
            btn.disabled = true;
        }
    });

    fetch('/Campus-Food-Ordering-System/view/customer/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `food_id=${foodId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        buttons.forEach(btn => {
            if (btn.dataset.id == foodId) {
                btn.innerHTML = '<i class="fa-solid fa-plus text-sm"></i>';
                btn.disabled = false;
            }
        });
        if (data.success) {
            cartItemCount = data.item_count || cartItemCount + 1;
            updateCartBadge();
            const item = menuDatabase.find(i => i.id == foodId);
            if (item) {
                item.stock = (item.stock || 0) - 1;
                renderMenuGrid();
            }
            showToast(`"${dishName}" added to cart! 🛒`);
        } else {
            showToast(data.message || 'Failed to add item', false);
        }
    })
    .catch(() => {
        buttons.forEach(btn => {
            if (btn.dataset.id == foodId) {
                btn.innerHTML = '<i class="fa-solid fa-plus text-sm"></i>';
                btn.disabled = false;
            }
        });
        showToast('Failed to add item to cart', false);
    });
}

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

function showToast(message, isSuccess = true) {
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.remove('translate-y-24', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}

window.onload = function() {
    renderMenuGrid();
};
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>