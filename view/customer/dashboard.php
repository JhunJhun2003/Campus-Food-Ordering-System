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
            <a href="#" class="logo-group"><iconify-icon icon="lucide:utensils-crosses"></iconify-icon><span>FOODIE</span></a>
            <nav class="nav-links"><a href="#" class="active">Home</a><a href="#">Menu</a><a href="#">Cart</a><a href="#">Orders</a></nav>
            <div class="nav-actions"><iconify-icon icon="lucide:shopping-cart"></iconify-icon><iconify-icon icon="lucide:user"></iconify-icon></div>
        </div>
    </header>
    <main class="container">
        <div class="search-container">
            <iconify-icon icon="lucide:search"></iconify-icon>
            <input type="text" placeholder="What food are you looking for?">
        </div>
        <div class="categories">
            <span class="category-chip active">All Items</span>
            <span class="category-chip">🍔 Burgers</span>
            <span class="category-chip">🍕 Pizza</span>
            <span class="category-chip">🥤 Drinks</span>
            <span class="category-chip">🍰 Sweets</span>
        </div>
        <div class="menu-grid">
            <script>
                for(let i=0; i<8; i++) {
                    document.write(`
                        <div class="card">
                            <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f354/512.webp" class="card-img" alt="Burger">
                            <div class="card-body">
                                <h3>Cheese Burger Deluxe</h3>
                                <p>Juicy beef patty stacked with double cheddar cheese and secret sauce.</p>
                            </div>
                            <div class="card-footer">
                                <span class="price">$ 100</span>
                                <button class="add-btn"><iconify-icon icon="lucide:plus"></iconify-icon></button>
                            </div>
                        </div>
                    `);
                }
            </script>
        </div>
    </main>
    <nav class="bottom-nav">
        <a href="#" class="bottom-nav-item active"><iconify-icon icon="lucide:home"></iconify-icon><span>Home</span></a>
        <a href="#" class="bottom-nav-item"><iconify-icon icon="lucide:utensils-crosses"></iconify-icon><span>Menu</span></a>
        <a href="#" class="bottom-nav-item"><iconify-icon icon="lucide:shopping-cart"></iconify-icon><span>Cart</span></a>
        <a href="#" class="bottom-nav-item"><iconify-icon icon="lucide:user"></iconify-icon><span>Profile</span></a>
    </nav>
</body>
</html>