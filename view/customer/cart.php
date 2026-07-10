<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

requireLogin();
requireEmailVerification();
// ✅ Check maintenance mode
checkMaintenanceRedirect();
// ✅ Redirect admin/staff away from customer dashboard
redirectAdminStaffFromCustomer();
requirePermission('add_to_cart');

use App\User\Presentation\Http\Controllers\UserController;

$userController = getUserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Get cart controller using helper - NO 'new' keyword!
$cartController = getCartController();

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

$cart = $cartController->index($userId);
$items = $cart['items'] ?? [];
$total = $cart['total'] ?? 0;
$itemCount = $cart['item_count'] ?? 0;
$isCartEmpty = empty($items);

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Your Cart';
$activePage = 'cart';
$customCss = 'css/cart.css';

include __DIR__ . '/includes/header.php';
?>

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
                <div class="text-slate-300 text-5xl mb-4"><i class="fa-solid fa-cart-flatbed"></i></div>
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
                            <span class="text-3xl p-2 bg-slate-50 rounded-lg select-none"><?php 
                                $emojiMap = [1 => '🍔', 2 => '🍕', 3 => '🥤', 4 => '🍰', 5 => '🍚'];
                                echo $emojiMap[$item['food_id']] ?? '🍽️';
                            ?></span>
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
                        <span class="font-black text-slate-900 text-base" id="item-total-<?php echo $item['food_id']; ?>">$ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
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
            <button disabled class="w-full sm:w-auto bg-slate-400 text-white font-bold px-10 py-3.5 rounded-xl text-sm cursor-not-allowed btn-disabled">Proceed to Checkout</button>
        <?php else: ?>
            <a href="checkout.php" class="w-full sm:w-auto inline-flex items-center justify-center bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-10 py-3.5 rounded-xl text-sm shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 interactive-transition tracking-wide">Proceed to Checkout</a>
        <?php endif; ?>
    </div>
</main>

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
                <div class="text-slate-300 text-5xl mb-4"><i class="fa-solid fa-cart-flatbed"></i></div>
                <h3 class="text-lg font-bold text-slate-800">Your cart is empty</h3>
                <p class="text-slate-400 text-sm mt-1 mb-6 max-w-xs">Looks like you haven't added any delicious food items to your cart yet.</p>
                <a href="dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-lg text-sm interactive-transition"><span>Browse Menu</span></a>
            </div>
        `;
        totalHeader.innerText = "0 items";
        subtotalLabel.innerText = "$ 0";
        if (badgeCount) {
            badgeCount.innerText = "0";
            badgeCount.classList.add('hidden');
        }
        const checkoutBtn = document.querySelector('a[href="checkout.php"]');
        if (checkoutBtn) {
            checkoutBtn.removeAttribute('href');
            checkoutBtn.className = 'w-full sm:w-auto bg-slate-400 text-white font-bold px-10 py-3.5 rounded-xl text-sm cursor-not-allowed btn-disabled';
            checkoutBtn.textContent = 'Proceed to Checkout';
        }
        return;
    }

    if (badgeCount) {
        badgeCount.classList.remove('hidden');
    }
    let rowsHtml = '';
    let currentSubtotal = 0;

    cartItems.forEach(item => {
        const itemTotal = item.price * item.quantity;
        currentSubtotal += itemTotal;
        rowsHtml += `
            <div id="cart-item-row-${item.food_id}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-white border border-slate-100 rounded-2xl p-4 md:py-6 md:px-4 shadow-sm hover:shadow-md/50 interactive-transition">
                <div class="col-span-1 md:col-span-5 flex items-center">
                    <div class="w-full max-w-[280px] sm:max-w-full flex items-center space-x-4 border border-slate-100 rounded-xl p-3 bg-white shadow-sm/50">
                        <span class="text-3xl p-2 bg-slate-50 rounded-lg select-none">${item.emoji || '🍽️'}</span>
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
    if (badgeCount) {
        badgeCount.innerText = cartItems.length;
    }
    subtotalLabel.innerText = `$ ${currentSubtotal}`;
    cartTotal = currentSubtotal;

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
                    showToast(`Updated quantity to ${newQuantity}`);
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
                    showToast('Item removed from cart');
                }
            });
        }, 300);
    }
}

function showToast(message) {
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>