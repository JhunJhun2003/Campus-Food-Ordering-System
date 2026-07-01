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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Explore Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="navbar">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="dashboard.php" class="logo-group">
                <svg viewBox="0 0 100 100" class="fill-current text-slate-950">
                    <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                    <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                    <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                </svg>
                <span>FOODIE</span>
            </a>

            <!-- ✅ Navigation - Home, Cart, Orders (No Menu) -->
            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-bold text-emerald-500 border-b-2 border-emerald-500 pb-1.5 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Orders</a>
            </nav>

            <div class="flex items-center space-x-4">
                <a href="cart.php" class="text-slate-600 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50 relative">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                </a>
                <div class="user-dropdown">
                    <div class="user-trigger" onclick="toggleDropdown()">
                        <i class="fa-regular fa-user text-slate-600"></i>
                        <span class="user-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </div>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="profile.php"><i class="fa-regular fa-user mr-2"></i> Profile</a>
                        <a href="orders.php"><i class="fa-regular fa-receipt mr-2"></i> My Orders</a>
                        <div class="divider"></div>
                        <a href="../entrance/logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket mr-2"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-6 sm:p-8 mb-8 border border-emerald-100/50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                        🍔 Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'Foodie'); ?>!
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">Discover delicious food, order fast, and enjoy every bite.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                        <span class="px-3 py-1.5 bg-white border border-slate-200 rounded-full text-xs font-semibold text-slate-600">
                            <?php 
                                $emoji = match($category['name']) {
                                    'Burgers' => '🍔',
                                    'Pizza' => '🍕',
                                    'Drinks' => '🥤',
                                    'Sweets' => '🍰',
                                    'Rice Meals' => '🍚',
                                    default => '🍽️'
                                };
                                echo $emoji . ' ' . htmlspecialchars($category['name']); 
                            ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" placeholder="What food are you looking for?" id="searchInput">
        </div>

        <!-- Categories -->
        <div class="categories">
            <span class="category-chip active" data-category="all">All Items</span>
            <?php foreach ($categories as $category): ?>
                <span class="category-chip" data-category="<?php echo $category['id']; ?>">
                    <?php 
                        $emoji = match($category['name']) {
                            'Burgers' => '🍔',
                            'Pizza' => '🍕',
                            'Drinks' => '🥤',
                            'Sweets' => '🍰',
                            'Rice Meals' => '🍚',
                            default => '🍽️'
                        };
                        echo $emoji . ' ' . htmlspecialchars($category['name']); 
                    ?>
                </span>
            <?php endforeach; ?>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid" id="menuGrid">
            <?php if (empty($foods)): ?>
                <div class="col-span-full text-center py-16 text-slate-400">
                    <i class="fa-regular fa-utensils text-5xl block mb-4"></i>
                    <p class="text-sm font-medium">No food items available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($foods as $food): ?>
                    <div class="card" data-category="<?php echo $food->getCategoryId(); ?>">
                        <div class="card-img" style="display: flex; align-items: center; justify-content: center; background: #F8FAFC; border-radius: 10px; height: 140px; font-size: 56px;">
                            <?php 
                                $emojiMap = [
                                    1 => '🍔',
                                    2 => '🍕',
                                    3 => '🥤',
                                    4 => '🍰',
                                    5 => '🍚',
                                ];
                                echo $emojiMap[$food->getCategoryId()] ?? '🍽️';
                            ?>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($food->getName()); ?></h3>
                            <p><?php echo htmlspecialchars($food->getDescription()); ?></p>
                            <small>⏱️ <?php echo $food->getPreparationTime(); ?> mins</small>
                        </div>
                        <div class="card-footer">
                            <span class="price">$ <?php echo number_format($food->getPrice(), 2); ?></span>
                            <?php if ($food->getStock() > 0): ?>
                                <form action="add-to-cart.php" method="POST" class="m-0">
                                    <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                    <button type="submit" class="add-btn">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #EF4444; font-size: 11px; font-weight: 700;">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- ===== BOTTOM NAV (Mobile) ===== -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="bottom-nav-item active">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="cart.php" class="bottom-nav-item">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Cart</span>
        </a>
        <a href="orders.php" class="bottom-nav-item">
            <i class="fa-solid fa-receipt"></i>
            <span>Orders</span>
        </a>
        <a href="profile.php" class="bottom-nav-item">
            <i class="fa-regular fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <!-- ===== SCRIPTS ===== -->
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.getElementById('userDropdown').style.display = 'none';
            }
        });

        // Category filter
        document.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                
                const categoryId = this.dataset.category;
                const cards = document.querySelectorAll('.card');
                
                cards.forEach(card => {
                    if (categoryId === 'all' || card.dataset.category === categoryId) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Search filter
        document.getElementById('searchInput').addEventListener('input', function() {
            const search = this.value.toLowerCase();
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                const name = card.querySelector('h3')?.textContent?.toLowerCase() || '';
                const desc = card.querySelector('p')?.textContent?.toLowerCase() || '';
                
                if (name.includes(search) || desc.includes(search)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>