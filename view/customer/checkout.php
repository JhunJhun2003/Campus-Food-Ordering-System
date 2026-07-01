<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;
use App\Cart\Presentation\Http\Controllers\CartController;
use App\Order\Presentation\Http\Controllers\OrderController;

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
$items = $cart['items'] ?? [];
$total = $cart['total'] ?? 0;
$itemCount = $cart['item_count'] ?? 0;

// Redirect if cart is empty
if (empty($items)) {
    header('Location: cart.php?empty=1');
    exit();
}

// Handle order submission
$error = '';
$success = '';
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $accountName = $_POST['account_name'] ?? '';
    $accountNumber = $_POST['account_number'] ?? '';
    $deliveryAddress = $_POST['delivery_address'] ?? '';
    
    // Validate
    if ($paymentMethod !== 'cod' && (empty($accountName) || empty($accountNumber))) {
        $error = 'Please fill in account details for digital payment.';
    } elseif (empty($deliveryAddress)) {
        $error = 'Please enter your delivery address.';
    } else {
        // Create order
        $orderController = new OrderController();
        $result = $orderController->createOrder($userId, $items, $total, $deliveryAddress, $paymentMethod);
        
        if ($result['success']) {
            $orderId = $result['order_id'];
            // Clear cart
            $cartController->clear($userId);
            $success = 'Order placed successfully!';
        } else {
            $error = $result['message'] ?? 'Failed to place order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Checkout</title>
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
        .step-view {
            animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .custom-radio:checked + div {
            border-color: #10B981;
            background-color: #ECFDF5;
        }
        .alert-error {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }
        .alert-success {
            background-color: #D1FAE5;
            border: 1px solid #6EE7B7;
            color: #065F46;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
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

            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-bold text-emerald-500 border-b-2 border-emerald-500 pb-1.5 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Orders</a>
            </nav>

            <div class="flex items-center space-x-6">
                <button class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-magnifying-glass text-lg"></i>
                </button>
                <a href="cart.php" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                    <span class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center border-2 border-white shadow-sm">
                        <?php echo $itemCount; ?>
                    </span>
                </a>
                <a href="profile.php" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-regular fa-user text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-10 text-center sm:text-left">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-8">Checkout</h1>
            
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="alert-error px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success && $orderId): ?>
                <div class="alert-success px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($success); ?> Order #<?php echo $orderId; ?></span>
                </div>
            <?php endif; ?>

            <!-- Step Progress -->
            <div class="relative max-w-2xl mx-auto sm:mx-0">
                <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-100 -translate-y-1/2 z-0"></div>
                <div id="step-progress-line" class="absolute top-1/2 left-0 w-[0%] h-0.5 bg-emerald-500 -translate-y-1/2 z-0 transition-all duration-500 ease-in-out"></div>
                
                <div class="relative flex justify-between z-10">
                    <div class="flex flex-col items-center">
                        <div id="circle-step-1" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-emerald-500 text-white ring-4 ring-emerald-100 transition-all duration-300">1</div>
                        <span id="label-step-1" class="text-xs font-bold text-slate-900 mt-2 transition-all duration-300">Payment</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div id="circle-step-2" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-slate-100 text-slate-400 transition-all duration-300">2</div>
                        <span id="label-step-2" class="text-xs font-medium text-slate-400 mt-2 transition-all duration-300">Review</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div id="circle-step-3" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-slate-100 text-slate-400 transition-all duration-300">3</div>
                        <span id="label-step-3" class="text-xs font-medium text-slate-400 mt-2 transition-all duration-300">Confirm</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 1: Payment -->
        <div id="panel-step-1" class="step-view grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Payment Methods -->
            <div class="lg:col-span-7 space-y-4">
                <h2 class="text-lg font-bold text-slate-950 tracking-wide mb-2">Select Payment Method</h2>
                
                <div class="space-y-3" id="payment-methods-list">
                    <label class="block relative cursor-pointer group">
                        <input type="radio" name="payment_method" value="cod" checked onchange="handlePaymentMethodChange(this)" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white peer-checked:border-emerald-500 peer-checked:ring-2 peer-checked:ring-emerald-50 hover:bg-slate-50/50 interactive-transition shadow-sm">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center border-2 border-slate-300 peer-checked:group-hover:border-emerald-500 bg-white peer-checked:bg-emerald-500 flex-shrink-0 transition-all duration-200">
                                <i class="fa-solid fa-check text-xs text-white opacity-0"></i>
                            </div>
                            <span class="font-bold text-slate-800 text-sm sm:text-base">Cash on Delivery</span>
                        </div>
                    </label>

                    <label class="block relative cursor-pointer group">
                        <input type="radio" name="payment_method" value="card" onchange="handlePaymentMethodChange(this)" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white peer-checked:border-emerald-500 peer-checked:ring-2 peer-checked:ring-emerald-50 hover:bg-slate-50/50 interactive-transition shadow-sm">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center border-2 border-slate-300 bg-white flex-shrink-0 transition-all duration-200">
                                <i class="fa-solid fa-check text-xs text-white opacity-0"></i>
                            </div>
                            <span class="font-bold text-slate-800 text-sm sm:text-base">Credit / Debit Card</span>
                        </div>
                    </label>

                    <label class="block relative cursor-pointer group">
                        <input type="radio" name="payment_method" value="kpay" onchange="handlePaymentMethodChange(this)" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white peer-checked:border-emerald-500 peer-checked:ring-2 peer-checked:ring-emerald-50 hover:bg-slate-50/50 interactive-transition shadow-sm">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center border-2 border-slate-300 bg-white flex-shrink-0 transition-all duration-200">
                                <i class="fa-solid fa-check text-xs text-white opacity-0"></i>
                            </div>
                            <span class="font-bold text-slate-800 text-sm sm:text-base">K Pay</span>
                        </div>
                    </label>

                    <label class="block relative cursor-pointer group">
                        <input type="radio" name="payment_method" value="wave" onchange="handlePaymentMethodChange(this)" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white peer-checked:border-emerald-500 peer-checked:ring-2 peer-checked:ring-emerald-50 hover:bg-slate-50/50 interactive-transition shadow-sm">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center border-2 border-slate-300 bg-white flex-shrink-0 transition-all duration-200">
                                <i class="fa-solid fa-check text-xs text-white opacity-0"></i>
                            </div>
                            <span class="font-bold text-slate-800 text-sm sm:text-base">Wave Pay</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Right Column: Account Details -->
            <div class="lg:col-span-5">
                <form method="POST" action="" id="checkout-form">
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-md shadow-slate-100/40 space-y-6">
                        
                        <div class="border-b border-slate-50 pb-4">
                            <h3 class="text-base font-bold text-slate-900">Verification & Accounts</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Please fill your account credentials below.</p>
                        </div>

                        <!-- Delivery Address -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Delivery Address</label>
                            <textarea name="delivery_address" rows="2" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/5 transition-all" placeholder="Enter your delivery address..." required>123 Culinary Boulevard, Foodie Town</textarea>
                        </div>

                        <!-- COD Message -->
                        <div id="cod-friendly-message" class="hidden text-center py-6 px-4 bg-emerald-50/40 rounded-xl border border-dashed border-emerald-100">
                            <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mx-auto mb-3">
                                <i class="fa-solid fa-truck-fast text-lg"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900">Cash on Delivery Active</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Pay upon delivery. No digital details needed!</p>
                        </div>

                        <!-- Digital Transfer Fields -->
                        <div id="transfer-input-fields" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Account Name</label>
                                <input type="text" name="account_name" id="acc-name-input" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 font-semibold text-sm focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/5 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Account Number</label>
                                <input type="text" name="account_number" id="acc-num-input" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 font-semibold text-sm focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/5 transition-all">
                            </div>
                        </div>

                        <button type="submit" name="place_order" value="1" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/25 interactive-transition tracking-wide flex items-center justify-center space-x-2">
                            <span>Proceed to Checkout</span>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- STEP 2: Review (Hidden by default) -->
        <div id="panel-step-2" class="step-view hidden max-w-2xl mx-auto bg-white border border-slate-100 rounded-2xl p-6 sm:p-8 shadow-md">
            <div class="border-b border-slate-100 pb-5 mb-6 text-center sm:text-left">
                <h3 class="text-xl font-bold text-slate-900">Review Your Order</h3>
                <p class="text-xs text-slate-400 mt-1">Please double check your order details before placing.</p>
            </div>

            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Billing Details</span>
                        <p id="review-billing-name" class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></p>
                        <p id="review-billing-account" class="text-xs text-slate-500 mt-1">Acc No: <?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?></p>
                    </div>
                    <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Method Chosen</span>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <p id="review-payment-method-label" class="text-sm font-bold text-slate-800">Cash on Delivery</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-b border-slate-100 py-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Your Cart Items</span>
                    <?php foreach ($items as $item): ?>
                        <div class="flex justify-between items-center text-sm py-2">
                            <div class="flex items-center space-x-3">
                                <span class="text-xl p-1.5 bg-slate-50 rounded">
                                    <?php 
                                        $emojiMap = [1 => '🍔', 2 => '🍕', 3 => '🥤', 4 => '🍰', 5 => '🍚'];
                                        echo $emojiMap[$item['food_id']] ?? '🍽️';
                                    ?>
                                </span>
                                <div>
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($item['food_name']); ?></p>
                                    <p class="text-[10px] text-slate-400">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                            </div>
                            <span class="font-bold text-slate-900">$ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between items-center bg-slate-900 text-white p-4 rounded-xl">
                    <span class="text-sm font-bold">Total Bill amount due</span>
                    <span class="text-xl font-extrabold">$ <?php echo number_format($total, 2); ?></span>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button onclick="goToStep1()" class="w-full sm:w-1/3 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold py-3.5 rounded-xl text-sm transition-colors">Go Back</button>
                    <button onclick="submitOrder()" class="w-full sm:w-2/3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-md shadow-emerald-500/10 transition-all">Submit & Confirm Delivery</button>
                </div>
            </div>
        </div>

        <!-- STEP 3: Success -->
        <div id="panel-step-3" class="step-view hidden max-w-md mx-auto text-center bg-white border border-slate-100 rounded-3xl p-8 shadow-xl">
            <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-500 mx-auto mb-6 ring-8 ring-emerald-500/5">
                <svg class="w-10 h-10 stroke-current" fill="none" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Order Placed!</h3>
            <p class="text-slate-500 text-sm mt-2 leading-relaxed max-w-xs mx-auto">Thank you for ordering. The kitchen has begun preparing your food.</p>

            <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-4 my-6 text-left">
                <div class="flex justify-between items-center text-xs text-slate-500 mb-2">
                    <span>Order ID</span>
                    <span class="font-bold text-slate-800">#<?php echo $orderId ?? 'PENDING'; ?></span>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-500">
                    <span>Delivery Time</span>
                    <span class="font-bold text-slate-800">Est. 25-35 mins</span>
                </div>
            </div>

            <div class="space-y-3">
                <a href="orders.php" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl text-sm transition-all shadow-md text-center">View My Orders</a>
                <a href="dashboard.php" class="block text-xs font-bold text-slate-400 hover:text-emerald-500 uppercase tracking-wider py-1">Back to Menu</a>
            </div>
        </div>

    </main>

    <!-- TOAST -->
    <div id="checkout-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800">
        <div class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Checkout Status</h4>
            <p id="toast-message" class="text-sm font-semibold text-slate-100">Step updated successfully!</p>
        </div>
    </div>

    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; 2026 FOODIE INC. All rights reserved. Delicious Food, Delivered Fast.
        </div>
    </footer>

    <script>
        let selectedPaymentMethod = 'cod';

        window.addEventListener('DOMContentLoaded', () => {
            const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
            if (selectedRadio) {
                handlePaymentMethodChange(selectedRadio);
            }
        });

        function handlePaymentMethodChange(element) {
            selectedPaymentMethod = element.value;
            
            const radios = document.querySelectorAll('input[name="payment_method"]');
            radios.forEach(radio => {
                const parentCard = radio.nextElementSibling;
                parentCard.className = "flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50/50 interactive-transition shadow-sm";
            });

            const activeParent = element.nextElementSibling;
            activeParent.className = "flex items-center space-x-4 p-4 rounded-xl border-2 border-emerald-500 bg-emerald-50/35 ring-4 ring-emerald-50/20 hover:bg-emerald-50/40 interactive-transition shadow-sm";

            const codMessage = document.getElementById('cod-friendly-message');
            const transferFields = document.getElementById('transfer-input-fields');

            if (selectedPaymentMethod === 'cod') {
                codMessage.classList.remove('hidden');
                transferFields.classList.add('hidden');
            } else {
                codMessage.classList.add('hidden');
                transferFields.classList.remove('hidden');
            }
        }

        function triggerToast(message, isSuccess = true) {
            const toast = document.getElementById('checkout-toast');
            const text = document.getElementById('toast-message');
            text.innerText = message;

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }

        function goToStep2() {
            // Validate form
            const form = document.getElementById('checkout-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Update review panel
            const name = document.getElementById('acc-name-input')?.value || 'Doorstep Customer';
            const account = document.getElementById('acc-num-input')?.value || 'Cash on delivery';
            
            document.getElementById('review-billing-name').innerText = name;
            document.getElementById('review-billing-account').innerText = selectedPaymentMethod === 'cod' ? 'Cash collected on spot' : `Account: ${account}`;
            
            const methodLabels = {
                'cod': 'Cash on Delivery',
                'card': 'Credit / Debit Card',
                'kpay': 'K Pay E-Wallet',
                'wave': 'Wave Pay E-Wallet'
            };
            document.getElementById('review-payment-method-label').innerText = methodLabels[selectedPaymentMethod] || 'Cash on Delivery';

            document.getElementById('step-progress-line').style.width = '50%';
            document.getElementById('circle-step-2').className = "w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-emerald-500 text-white ring-4 ring-emerald-100 transition-all duration-300";
            document.getElementById('label-step-2').className = "text-xs font-bold text-slate-900 mt-2 transition-all duration-300";

            document.getElementById('panel-step-1').classList.add('hidden');
            document.getElementById('panel-step-2').classList.remove('hidden');
            triggerToast('Review your order');
        }

        function goToStep1() {
            document.getElementById('step-progress-line').style.width = '0%';
            document.getElementById('circle-step-2').className = "w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-slate-100 text-slate-400 transition-all duration-300";
            document.getElementById('label-step-2').className = "text-xs font-medium text-slate-400 mt-2 transition-all duration-300";

            document.getElementById('panel-step-2').classList.add('hidden');
            document.getElementById('panel-step-1').classList.remove('hidden');
        }

        function submitOrder() {
            document.getElementById('step-progress-line').style.width = '100%';
            document.getElementById('circle-step-3').className = "w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-emerald-500 text-white ring-4 ring-emerald-100 transition-all duration-300";
            document.getElementById('label-step-3').className = "text-xs font-bold text-slate-900 mt-2 transition-all duration-300";

            document.getElementById('panel-step-2').classList.add('hidden');
            document.getElementById('panel-step-3').classList.remove('hidden');
            triggerToast('Order placed successfully! 🎉');
        }
    </script>

</body>
</html>