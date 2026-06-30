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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <header class="navbar">
        <div class="container nav-wrapper">
            <a href="dashboard.php" class="logo-group">
                <iconify-icon icon="lucide:utensils-crosses"></iconify-icon>
                <span>FOODIE</span>
            </a>
            <nav class="nav-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="cart.php">Cart</a>
                <a href="orders.php">Orders</a>
            </nav>
            <div class="nav-actions">
                <a href="cart.php"><iconify-icon icon="lucide:shopping-cart"></iconify-icon></a>
                <div class="user-dropdown" style="position: relative; display: inline-block;">
                    <span style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                        <iconify-icon icon="lucide:user"></iconify-icon>
                        <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">
                            <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>
                        </span>
                    </span>
                    <div class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid var(--border); border-radius: 8px; padding: 8px 0; min-width: 150px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100;">
                        <a href="profile.php" style="display: block; padding: 8px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px;">Profile</a>
                        <a href="orders.php" style="display: block; padding: 8px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px;">My Orders</a>
                        <hr style="margin: 4px 0; border: none; border-top: 1px solid var(--border);">
                        <a href="../entrance/logout.php" style="display: block; padding: 8px 16px; text-decoration: none; color: #EF4444; font-size: 14px;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Search -->
        <div class="search-container">
            <iconify-icon icon="lucide:search"></iconify-icon>
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
                <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                    <iconify-icon icon="lucide:utensils" style="font-size: 48px; display: block; margin-bottom: 8px;"></iconify-icon>
                    No food items available at the moment.
                </p>
            <?php else: ?>
                <?php foreach ($foods as $food): ?>
                    <div class="card" data-category="<?php echo $food->getCategoryId(); ?>">
                        <img src="<?php echo htmlspecialchars($food->getImage() ?? 'https://fonts.gstatic.com/s/e/notoemoji/latest/1f354/512.webp'); ?>" 
                             class="card-img" 
                             alt="<?php echo htmlspecialchars($food->getName()); ?>">
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($food->getName()); ?></h3>
                            <p><?php echo htmlspecialchars($food->getDescription()); ?></p>
                            <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                                ⏱️ <?php echo $food->getPreparationTime(); ?> mins
                            </small>
                        </div>
                        <div class="card-footer">
                            <span class="price">$ <?php echo number_format($food->getPrice(), 2); ?></span>
                            <?php if ($food->getStock() > 0): ?>
                                <form action="add-to-cart.php" method="POST">
                                    <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                    <button type="submit" class="add-btn">
                                        <iconify-icon icon="lucide:plus"></iconify-icon>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #EF4444; font-size: 12px; font-weight: 600;">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="bottom-nav-item active">
            <iconify-icon icon="lucide:home"></iconify-icon>
            <span>Dashboard</span>
        </a>
        <a href="dashboard.php" class="bottom-nav-item">
            <iconify-icon icon="lucide:utensils-crosses"></iconify-icon>
            <span>Menu</span>
        </a>
        <a href="cart.php" class="bottom-nav-item">
            <iconify-icon icon="lucide:shopping-cart"></iconify-icon>
            <span>Cart</span>
        </a>
        <a href="profile.php" class="bottom-nav-item">
            <iconify-icon icon="lucide:user"></iconify-icon>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        // User dropdown toggle
        document.querySelector('.user-dropdown')?.addEventListener('click', function(e) {
            const dropdown = this.querySelector('.dropdown-menu');
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(el => {
                    el.style.display = 'none';
                });
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