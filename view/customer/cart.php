<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Cart\Presentation\Http\Controllers\CartController;
use App\User\Presentation\Http\Controllers\UserController;

$userController = new UserController();

// Check if user is logged in
if (!$userController->isLoggedIn()) {
    header('Location: ../entrance/login.php');
    exit();
}

$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'];

// Get cart data
$cartController = new CartController();
$cart = $cartController->index($userId);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $foodId = (int) ($_POST['food_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'add':
            $response = $cartController->add($userId, $foodId, $quantity);
            break;
        case 'update':
            $response = $cartController->update($userId, $foodId, $quantity);
            break;
        case 'remove':
            $response = $cartController->remove($userId, $foodId);
            break;
        case 'clear':
            $response = $cartController->clear($userId);
            break;
    }
    
    echo json_encode($response);
    exit();
}

// Check if cart is empty to disable checkout
$items = $cart['items'] ?? [];
$total = $cart['total'] ?? 0;
$itemCount = $cart['item_count'] ?? 0;
$isCartEmpty = empty($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #FCFDFE;
        }
        .interactive-transition {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .fade-out-item {
            animation: fadeOut 0.3s ease forwards;
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(12px); height: 0; padding: 0; margin: 0; overflow: hidden; }
        }
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-900">
                    <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 interactive-transition">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
            </a>

            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-bold text-emerald-500 border-b-2 border-emerald-500 pb-1.5 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Orders</a>
            </nav>

            <div class="flex items-center space-x-6">
                <!-- <button class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-magnifying-glass text-lg"></i>
                </button> -->
                <button onclick="scrollToCart()" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                    <span id="header-cart-badge" class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center border-2 border-white shadow-sm <?php echo $itemCount > 0 ? '' : 'hidden'; ?>">
                        <?php echo $itemCount; ?>
                    </span>
                </button>
                <a href="profile.php" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-regular fa-user text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Your Cart (<span id="cart-items-count-header"><?php echo $itemCount; ?> items</span>)
            </h1>
        </div>

        <!-- Desktop Table Headers -->
        <div class="hidden md:grid grid-cols-12 bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6 text-sm font-semibold text-slate-500 tracking-wide">
            <div class="col-span-5 pl-4">Item</div>
            <div class="col-span-2 text-center">Price</div>
            <div class="col-span-2 text-center">Quantity</div>
            <div class="col-span-2 text-center">Total</div>
            <div class="col-span-1 text-center">Action</div>
        </div>

        <!-- Cart Items -->
        <div id="cart-items-wrapper" class="space-y-4 mb-8">
            <?php if (empty($items)): ?>
                <div class="flex flex-col items-center justify-center py-20 text-center bg-white border border-dashed border-slate-200 rounded-2xl p-8">
                    <div class="text-slate-300 text-5xl mb-4">
                        <i class="fa-solid fa-cart-flatbed"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Your cart is empty</h3>
                    <p class="text-slate-400 text-sm mt-1 mb-6 max-w-xs">Looks like you haven't added any delicious food items to your cart yet.</p>
                    <a href="dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-lg text-sm interactive-transition">
                        <span>Browse Menu</span>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div id="cart-item-row-<?php echo $item['food_id']; ?>" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-white border border-slate-100 rounded-2xl p-4 md:py-6 md:px-4 shadow-sm hover:shadow-md/50 interactive-transition">
                        <div class="col-span-1 md:col-span-5 flex items-center">
                            <div class="w-full max-w-[280px] sm:max-w-full flex items-center space-x-4 border border-slate-100 rounded-xl p-3 bg-white shadow-sm/50">
                                <span class="text-3xl p-2 bg-slate-50 rounded-lg select-none">
                                    <?php 
                                        $emojiMap = [1 => '🍔', 2 => '🍕', 3 => '🥤', 4 => '🍰', 5 => '🍚'];
                                        echo $emojiMap[$item['food_id']] ?? '🍽️';
                                    ?>
                                </span>
                                <span class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($item['food_name']); ?></span>
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Price</span>
                            <span class="font-bold text-slate-800 text-base">$ <?php echo number_format($item['price'], 2); ?></span>
                        </div>

                        <div class="col-span-1 md:col-span-2 flex md:justify-center items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Quantity</span>
                            <div class="flex items-center space-x-3.5 bg-slate-50/50 border border-slate-200/80 rounded-xl px-3.5 py-1.5 shadow-inner">
                                <button onclick="updateCartItem(<?php echo $item['food_id']; ?>, 1)" class="text-slate-400 hover:text-emerald-500 interactive-transition text-xs p-1 focus:outline-none">
                                    <i class="fa-solid fa-circle-plus text-base sm:text-lg"></i>
                                </button>
                                <span class="font-black text-slate-800 text-sm select-none w-6 text-center" id="qty-<?php echo $item['food_id']; ?>"><?php echo $item['quantity']; ?></span>
                                <button onclick="updateCartItem(<?php echo $item['food_id']; ?>, -1)" class="text-slate-400 hover:text-rose-500 interactive-transition text-xs p-1 focus:outline-none">
                                    <i class="fa-solid fa-circle-minus text-base sm:text-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Total</span>
                            <span class="font-black text-slate-900 text-base" id="item-total-<?php echo $item['food_id']; ?>">
                                $ <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                        </div>

                        <div class="col-span-1 md:col-span-1 flex md:justify-center items-center justify-end px-2">
                            <button onclick="removeCartItem(<?php echo $item['food_id']; ?>)" class="text-slate-400 hover:text-rose-600 hover:bg-rose-50 p-3 rounded-xl interactive-transition focus:outline-none" title="Remove Item">
                                <i class="fa-solid fa-trash-can text-lg"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Subtotal -->
        <div class="border-t border-slate-100 pt-6 mt-8 flex flex-col items-end px-4 sm:px-6">
            <div class="flex items-center space-x-12 mb-8">
                <span class="text-base font-bold text-slate-600">Subtotal</span>
                <span id="cart-subtotal-price" class="text-2xl font-black text-slate-950">$ <?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 sm:px-6">
            <a href="dashboard.php" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 border border-emerald-500 text-emerald-500 hover:bg-emerald-50/50 font-bold px-6 py-3.5 rounded-xl text-sm interactive-transition">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Continue Shopping</span>
            </a>
            
            <?php if ($isCartEmpty): ?>
                <button disabled class="w-full sm:w-auto bg-slate-400 text-white font-bold px-10 py-3.5 rounded-xl text-sm cursor-not-allowed btn-disabled">
                    Proceed to Checkout
                </button>
            <?php else: ?>
                <a href="checkout.php" class="w-full sm:w-auto inline-flex items-center justify-center bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-10 py-3.5 rounded-xl text-sm shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 interactive-transition tracking-wide">
                    Proceed to Checkout
                </a>
            <?php endif; ?>
        </div>
    </main>

    <!-- TOAST NOTIFICATION -->
    <div id="notification-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800">
        <div id="toast-icon-box" class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Notification</h4>
            <p id="toast-msg-text" class="text-sm font-semibold text-slate-100">Item status changed successfully!</p>
        </div>
    </div>

    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; <?php echo date('Y'); ?> FOODIE INC. All rights reserved. Delicious Food, Delivered Fast.
        </div>
    </footer>

    <script>
        let cartItems = <?php echo json_encode($items); ?>;
        let cartTotal = <?php echo $total; ?>;
        let cartItemCount = <?php echo $itemCount; ?>;

        function renderCartUI() {
            const wrapper = document.getElementById('cart-items-wrapper');
            const totalHeader = document.getElementById('cart-items-count-header');
            const subtotalLabel = document.getElementById('cart-subtotal-price');
            const badgeCount = document.getElementById('header-cart-badge');

            if (cartItems.length === 0) {
                wrapper.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 text-center bg-white border border-dashed border-slate-200 rounded-2xl p-8">
                        <div class="text-slate-300 text-5xl mb-4">
                            <i class="fa-solid fa-cart-flatbed"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Your cart is empty</h3>
                        <p class="text-slate-400 text-sm mt-1 mb-6 max-w-xs">Looks like you haven't added any delicious food items to your cart yet.</p>
                        <a href="dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-lg text-sm interactive-transition">
                            <span>Browse Menu</span>
                        </a>
                    </div>
                `;
                totalHeader.innerText = "0 items";
                subtotalLabel.innerText = "$ 0";
                badgeCount.innerText = "0";
                badgeCount.classList.add('hidden');
                
                // Disable checkout button
                const checkoutBtn = document.querySelector('a[href="checkout.php"]');
                if (checkoutBtn) {
                    checkoutBtn.removeAttribute('href');
                    checkoutBtn.className = 'w-full sm:w-auto bg-slate-400 text-white font-bold px-10 py-3.5 rounded-xl text-sm cursor-not-allowed btn-disabled';
                    checkoutBtn.textContent = 'Proceed to Checkout';
                }
                return;
            }

            badgeCount.classList.remove('hidden');
            let rowsHtml = '';
            let currentSubtotal = 0;

            cartItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                currentSubtotal += itemTotal;

                rowsHtml += `
                    <div id="cart-item-row-${item.food_id}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-white border border-slate-100 rounded-2xl p-4 md:py-6 md:px-4 shadow-sm hover:shadow-md/50 interactive-transition">
                        <div class="col-span-1 md:col-span-5 flex items-center">
                            <div class="w-full max-w-[280px] sm:max-w-full flex items-center space-x-4 border border-slate-100 rounded-xl p-3 bg-white shadow-sm/50">
                                <span class="text-3xl p-2 bg-slate-50 rounded-lg select-none">🍔</span>
                                <span class="font-bold text-slate-900 text-base">${item.food_name}</span>
                            </div>
                        </div>
                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Price</span>
                            <span class="font-bold text-slate-800 text-base">$ ${item.price}</span>
                        </div>
                        <div class="col-span-1 md:col-span-2 flex md:justify-center items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Quantity</span>
                            <div class="flex items-center space-x-3.5 bg-slate-50/50 border border-slate-200/80 rounded-xl px-3.5 py-1.5 shadow-inner">
                                <button onclick="updateCartItem(${item.food_id}, 1)" class="text-slate-400 hover:text-emerald-500 interactive-transition text-xs p-1 focus:outline-none">
                                    <i class="fa-solid fa-circle-plus text-base sm:text-lg"></i>
                                </button>
                                <span class="font-black text-slate-800 text-sm select-none w-6 text-center">${item.quantity}</span>
                                <button onclick="updateCartItem(${item.food_id}, -1)" class="text-slate-400 hover:text-rose-500 interactive-transition text-xs p-1 focus:outline-none">
                                    <i class="fa-solid fa-circle-minus text-base sm:text-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block items-center justify-between px-2">
                            <span class="md:hidden text-xs font-bold text-slate-400 uppercase tracking-wider">Total</span>
                            <span class="font-black text-slate-900 text-base">$ ${itemTotal}</span>
                        </div>
                        <div class="col-span-1 md:col-span-1 flex md:justify-center items-center justify-end px-2">
                            <button onclick="removeCartItem(${item.food_id})" class="text-slate-400 hover:text-rose-600 hover:bg-rose-50 p-3 rounded-xl interactive-transition focus:outline-none" title="Remove Item">
                                <i class="fa-solid fa-trash-can text-lg"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            wrapper.innerHTML = rowsHtml;
            totalHeader.innerText = `${cartItems.length} items`;
            badgeCount.innerText = cartItems.length;
            subtotalLabel.innerText = `$ ${currentSubtotal}`;
            cartTotal = currentSubtotal;

            // Enable checkout button
            const checkoutBtn = document.querySelector('a[href="checkout.php"]');
            if (checkoutBtn) {
                checkoutBtn.href = 'checkout.php';
                checkoutBtn.className = 'w-full sm:w-auto inline-flex items-center justify-center bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-10 py-3.5 rounded-xl text-sm shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 interactive-transition tracking-wide';
                checkoutBtn.textContent = 'Proceed to Checkout';
            }
        }

        function updateCartItem(foodId, change) {
            const item = cartItems.find(i => i.food_id === foodId);
            if (item) {
                const newQuantity = Math.max(1, item.quantity + change);
                if (newQuantity !== item.quantity) {
                    fetch('cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=update&food_id=${foodId}&quantity=${newQuantity}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            item.quantity = newQuantity;
                            renderCartUI();
                            triggerNotification(`Updated quantity to ${newQuantity}`);
                        }
                    });
                }
            }
        }

        function removeCartItem(foodId) {
            const targetRow = document.getElementById(`cart-item-row-${foodId}`);
            if (targetRow) {
                targetRow.classList.add('fade-out-item');
                setTimeout(() => {
                    fetch('cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=remove&food_id=${foodId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cartItems = cartItems.filter(i => i.food_id !== foodId);
                            renderCartUI();
                            triggerNotification('Item removed from cart');
                        }
                    });
                }, 300);
            }
        }

        function triggerNotification(message, isSuccess = true) {
            const toast = document.getElementById('notification-toast');
            const text = document.getElementById('toast-msg-text');
            const iconBox = document.getElementById('toast-icon-box');

            text.innerText = message;
            iconBox.innerHTML = isSuccess 
                ? '<i class="fa-solid fa-circle-check text-lg"></i>'
                : '<i class="fa-solid fa-circle-exclamation text-lg"></i>';
            iconBox.className = isSuccess 
                ? 'text-emerald-400 bg-emerald-500/10 p-2 rounded-xl'
                : 'text-rose-400 bg-rose-500/10 p-2 rounded-xl';

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }

        function scrollToCart() {
            document.getElementById('cart-items-wrapper').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

</body>
</html>