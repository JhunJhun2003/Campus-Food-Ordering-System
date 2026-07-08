<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('place_orders');

use App\User\Presentation\Http\Controllers\UserController;
use App\Cart\Presentation\Http\Controllers\CartController;
use App\Order\Presentation\Http\Controllers\OrderController;
use App\Payment\Presentation\Http\Controllers\PaymentController;

$userController = new UserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

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

// Get payment methods
$paymentController = new PaymentController();
$paymentMethods = $paymentController->getActiveMethods();

// Handle order submission
$error = '';
$success = '';
$orderId = null;
$selectedMethod = null;
$accountName = '';
$deliveryAddress = '';
$fullName = '';
$phone = '';
$selectedPaymentMethodId = isset($_POST['payment_method_id']) ? (int) $_POST['payment_method_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $paymentMethodId = $selectedPaymentMethodId;
    $accountName = trim($_POST['account_name'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $transactionImage = '';
    
    // Get selected payment method
    $selectedMethod = null;
    foreach ($paymentMethods as $pm) {
        if ($pm['id'] == $paymentMethodId) {
            $selectedMethod = $pm;
            break;
        }
    }
    
    $paymentMethodName = $selectedMethod['method_name'] ?? 'Cash on Delivery';
    $isDigital = $paymentMethodName !== 'Cash on Delivery';
    
    // Validation
    if (empty($fullName)) {
        $error = 'Please enter your full name.';
    } elseif (empty($phone)) {
        $error = 'Please enter your phone number.';
    } elseif (empty($paymentMethodId) || $paymentMethodId === 0) {
        $error = 'Please select a payment method.';
    } elseif (!$selectedMethod) {
        $error = 'Selected payment method is not available.';
    } elseif ($isDigital && empty($accountName)) {
        $error = 'Please enter your account name for digital payment.';
    } elseif (empty($deliveryAddress)) {
        $error = 'Please enter your delivery address.';
    } else {
        // Handle file upload
        if ($isDigital && isset($_FILES['transaction_image']) && $_FILES['transaction_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../Public/uploads/transactions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $file = $_FILES['transaction_image'];
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'transaction_' . time() . '_' . uniqid() . '.' . $fileExt;
            
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $transactionImage = 'uploads/transactions/' . $fileName;
            } else {
                $error = 'Failed to upload transaction image.';
            }
        } elseif ($isDigital) {
            $error = 'Please upload a transaction image.';
        }
        
        if (empty($error)) {
            $orderController = new OrderController();
            $result = $orderController->createOrder(
                $userId, 
                $items, 
                $total, 
                $deliveryAddress, 
                $paymentMethodName, 
                $fullName, 
                $phone, 
                $accountName, 
                $selectedMethod['account_number'] ?? '', 
                $transactionImage
            );
            
            if ($result['success']) {
                $orderId = $result['order_id'];
                $success = 'Order placed successfully!';
            } else {
                $error = $result['message'] ?? 'Failed to place order. Please try again.';
            }
        }
    }
}

// Pre-fill form fields on error
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)) {
    $fullName = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $deliveryAddress = $_POST['delivery_address'] ?? '';
    $accountName = $_POST['account_name'] ?? '';
}

$orderPlaced = !empty($success) && !empty($orderId);
if ($orderPlaced) {
    $itemCount = 0;
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Checkout';
$activePage = 'checkout';
$customCss = 'css/checkout.css';

include __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-8">Checkout</h1>
        
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
<!-- Step Progress - Polished Design -->
<div class="max-w-2xl mx-auto sm:mx-0 mb-10">
    <div class="relative flex items-center justify-between px-4">
        <!-- Background Line -->
        <div class="absolute left-8 right-8 top-1/2 h-0.5 bg-slate-200 -translate-y-1/2"></div>
        
        <!-- Progress Line -->
        <div id="step-progress-line" class="absolute left-8 top-1/2 h-0.5 bg-emerald-500 -translate-y-1/2 transition-all duration-700 ease-in-out <?php echo $orderPlaced ? 'w-[calc(100%-64px)]' : 'w-0'; ?>"></div>
        
        <!-- Step 1 -->
        <div class="flex flex-col items-center z-10">
            <div id="circle-step-1" class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold <?php echo $orderPlaced ? 'bg-emerald-500 text-white ring-4 ring-emerald-100' : 'bg-emerald-500 text-white ring-4 ring-emerald-100'; ?> transition-all duration-300 shadow-sm">
                1
            </div>
            <span id="label-step-1" class="text-xs font-semibold text-slate-700 mt-2.5">Payment</span>
        </div>
        
        <!-- Step 2 -->
        <div class="flex flex-col items-center z-10">
            <div id="circle-step-2" class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold <?php echo $orderPlaced ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 shadow-sm' : 'bg-white text-slate-400 ring-4 ring-slate-100 border-2 border-slate-300'; ?> transition-all duration-300">
                2
            </div>
            <span id="label-step-2" class="text-xs font-semibold <?php echo $orderPlaced ? 'text-slate-700' : 'text-slate-400'; ?> mt-2.5 transition-all duration-300">Review</span>
        </div>
        
        <!-- Step 3 -->
        <div class="flex flex-col items-center z-10">
            <div id="circle-step-3" class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold <?php echo $orderPlaced ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 shadow-sm' : 'bg-white text-slate-400 ring-4 ring-slate-100 border-2 border-slate-300'; ?> transition-all duration-300">
                3
            </div>
            <span id="label-step-3" class="text-xs font-semibold <?php echo $orderPlaced ? 'text-slate-700' : 'text-slate-400'; ?> mt-2.5 transition-all duration-300">Confirm</span>
        </div>
    </div>
</div>
    </div>

    <!-- STEP 1: Payment -->
    <div id="panel-step-1" class="step-view <?php echo $orderPlaced ? 'hidden' : 'grid'; ?> grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Payment Methods -->
        <div class="lg:col-span-7 space-y-4">
            <h2 class="text-lg font-bold text-slate-950 tracking-wide mb-2">Select Payment Method</h2>
            
            <div class="space-y-3" id="payment-methods-list">
                <?php if (empty($paymentMethods)): ?>
                    <p class="text-sm text-slate-500">No payment methods available. Please contact admin.</p>
                <?php else: ?>
                    <?php $first = true; ?>
                    <?php foreach ($paymentMethods as $pm): ?>
                        <?php
                            $isSelected = $selectedPaymentMethodId > 0
                                ? ((int) $pm['id'] === $selectedPaymentMethodId)
                                : $first;
                        ?>
                        <label class="block relative cursor-pointer group">
                            <input type="radio" 
                                   name="payment_method_id" 
                                   id="payment_method_<?php echo $pm['id']; ?>" 
                                   value="<?php echo $pm['id']; ?>" 
                                   form="checkout-form"
                                   class="sr-only peer payment-radio"
                                   onchange="selectPaymentMethod(<?php echo $pm['id']; ?>)"
                                   <?php echo $isSelected ? 'checked' : ''; ?>>
                            <div class="payment-option flex items-center space-x-4 p-4 rounded-xl border border-slate-200 bg-white hover:border-emerald-500 hover:bg-emerald-50/30 shadow-sm <?php echo $isSelected ? 'selected' : ''; ?>">
                                <div class="w-6 h-6 rounded-md flex items-center justify-center border-2 border-slate-300 bg-white flex-shrink-0 transition-all duration-200">
                                    <i class="fa-solid fa-check text-xs text-white opacity-0"></i>
                                </div>
                                <span class="font-bold text-slate-800 text-sm sm:text-base">
                                    <?php echo htmlspecialchars($pm['method_name']); ?>
                                </span>
                            </div>
                        </label>
                        
                        <?php if (!empty($pm['account_name']) || !empty($pm['account_number'])): ?>
                            <div id="payment-details-<?php echo $pm['id']; ?>" class="payment-method-details <?php echo $isSelected ? 'active' : ''; ?>">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php if (!empty($pm['account_name'])): ?>
                                        <div>
                                            <div class="detail-label">Account Name</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($pm['account_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($pm['account_number'])): ?>
                                        <div>
                                            <div class="detail-label">Account Number</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($pm['account_number']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Account Details -->
        <div class="lg:col-span-5">
            <form method="POST" action="" id="checkout-form" enctype="multipart/form-data">
                <input type="hidden" name="place_order" value="1">
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-md shadow-slate-100/40 space-y-6">
                    
                    <div class="border-b border-slate-50 pb-4">
                        <h3 class="text-base font-bold text-slate-900">Contact & Delivery Details</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Please fill in your contact information and delivery address.</p>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label>Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($fullName ?: $currentUser['name'] ?? ''); ?>" required>
                    </div>

                    <!-- Phone Number -->
                    <div class="form-group">
                        <label>Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone ?: $currentUser['phone'] ?? ''); ?>" required>
                    </div>

                    <!-- Delivery Address -->
                    <div class="form-group">
                        <label>Delivery Address <span class="text-red-500">*</span></label>
                        <textarea name="delivery_address" rows="2" placeholder="Enter your delivery address..." required><?php echo htmlspecialchars($deliveryAddress ?: '123 Culinary Boulevard, Foodie Town'); ?></textarea>
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
                        <div class="form-group">
                            <label>Account Name <span class="text-red-500">*</span></label>
                            <input type="text" name="account_name" id="acc-name-input" placeholder="Enter account name" value="<?php echo htmlspecialchars($accountName ?: $currentUser['name'] ?? ''); ?>" required>
                        </div>

                        <!-- Transaction Image Upload -->
                        <div class="form-group">
                            <label>Transaction Image <span class="text-red-500">*</span></label>
                            <div class="file-upload-wrapper" id="fileUploadWrapper">
                                <input type="file" name="transaction_image" id="transactionImage" accept="image/*,.pdf" onchange="handleFileUpload(event)">
                                <i class="fa-regular fa-image file-icon"></i>
                                <div class="file-name">Click to upload or drag & drop</div>
                                <div class="file-hint">JPG, PNG, GIF, WEBP, PDF (Max 5MB)</div>
                            </div>
                            <div class="file-preview" id="filePreview">
                                <div class="file-info">
                                    <i class="fa-regular fa-file-image"></i>
                                    <span id="fileNameDisplay">No file selected</span>
                                </div>
                                <span class="remove-file" onclick="removeFile()">
                                    <i class="fa-solid fa-xmark"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="goToStep2()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/25 interactive-transition tracking-wide flex items-center justify-center space-x-2">
                        <span>Review Order</span>
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- STEP 2: Review -->
    <div id="panel-step-2" class="step-view hidden max-w-2xl mx-auto bg-white border border-slate-100 rounded-2xl p-6 sm:p-8 shadow-md">
        <div class="border-b border-slate-100 pb-5 mb-6 text-center sm:text-left">
            <h3 class="text-xl font-bold text-slate-900">Review Your Order</h3>
            <p class="text-xs text-slate-400 mt-1">Please double check your order details before placing.</p>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Customer Details</span>
                    <p id="review-customer-name" class="text-sm font-bold text-slate-800"></p>
                    <p id="review-customer-phone" class="text-xs text-slate-500 mt-1">Phone: </p>
                    <p id="review-customer-address" class="text-xs text-slate-500 mt-1">Address: </p>
                </div>
                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Method Chosen</span>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <p id="review-payment-method-label" class="text-sm font-bold text-slate-800"></p>
                    </div>
                    <p id="review-file-status" class="text-xs text-slate-500 mt-1">File: No file attached</p>
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
                <button type="button" onclick="goToStep1()" class="w-full sm:w-1/3 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold py-3.5 rounded-xl text-sm transition-colors">Go Back</button>
                <button type="button" onclick="submitOrder(this)" class="w-full sm:w-2/3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-md shadow-emerald-500/10 transition-all">Submit & Confirm Delivery</button>
            </div>
        </div>
    </div>

    <!-- STEP 3: Success -->
    <div id="panel-step-3" class="step-view <?php echo $orderPlaced ? '' : 'hidden'; ?> max-w-md mx-auto text-center bg-white border border-slate-100 rounded-3xl p-8 shadow-xl">
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

<script>
// ============================================
// STATE VARIABLES
// ============================================
let selectedPaymentMethodId = 0;
let selectedPaymentMethodName = '';
let orderSubmitInProgress = false;

// ============================================
// INITIALIZE ON PAGE LOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const selectedRadio = document.querySelector('input[name="payment_method_id"]:checked');
    if (selectedRadio) {
        selectPaymentMethod(parseInt(selectedRadio.value));
    } else {
        const firstRadio = document.querySelector('input[name="payment_method_id"]');
        if (firstRadio) {
            firstRadio.checked = true;
            selectPaymentMethod(parseInt(firstRadio.value));
        }
    }
    
    document.querySelectorAll('input[name="payment_method_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                selectPaymentMethod(parseInt(this.value));
            }
        });
    });
});

// ============================================
// SELECT PAYMENT METHOD
// ============================================
function selectPaymentMethod(methodId) {
    selectedPaymentMethodId = methodId;
    
    const radio = document.getElementById('payment_method_' + methodId);
    if (radio) {
        const label = radio.closest('label');
        if (label) {
            const nameSpan = label.querySelector('.font-bold.text-slate-800');
            if (nameSpan) {
                selectedPaymentMethodName = nameSpan.textContent.trim();
            }
        }
    }
    
    document.querySelectorAll('.payment-option').forEach(el => {
        el.classList.remove('selected');
    });
    
    const selectedOption = document.querySelector(`#payment_method_${methodId}`)?.closest('label')?.querySelector('.payment-option');
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
    
    document.querySelectorAll('.payment-option .fa-check').forEach(icon => {
        icon.classList.add('opacity-0');
    });
    
    const checkIcon = document.querySelector(`#payment_method_${methodId}`)?.closest('label')?.querySelector('.payment-option .fa-check');
    if (checkIcon) {
        checkIcon.classList.remove('opacity-0');
    }
    
    document.querySelectorAll('.payment-method-details').forEach(el => {
        el.classList.remove('active');
    });
    const details = document.getElementById('payment-details-' + methodId);
    if (details) {
        details.classList.add('active');
    }
    
    const isCOD = selectedPaymentMethodName === 'Cash on Delivery';
    const codMessage = document.getElementById('cod-friendly-message');
    const transferFields = document.getElementById('transfer-input-fields');
    const accountNameInput = document.getElementById('acc-name-input');
    const transactionImageInput = document.getElementById('transactionImage');
    
    if (isCOD) {
        codMessage.classList.remove('hidden');
        transferFields.classList.add('hidden');
        accountNameInput.required = false;
        accountNameInput.disabled = true;
        transactionImageInput.required = false;
        transactionImageInput.disabled = true;
    } else {
        codMessage.classList.add('hidden');
        transferFields.classList.remove('hidden');
        accountNameInput.disabled = false;
        accountNameInput.required = true;
        transactionImageInput.disabled = false;
        transactionImageInput.required = true;
    }
}

// ============================================
// FILE UPLOAD
// ============================================
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const wrapper = document.getElementById('fileUploadWrapper');
        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileNameDisplay');
        
        wrapper.classList.add('has-file');
        fileName.textContent = file.name;
        preview.classList.add('active');
    }
}

function removeFile() {
    const input = document.getElementById('transactionImage');
    const wrapper = document.getElementById('fileUploadWrapper');
    const preview = document.getElementById('filePreview');
    
    input.value = '';
    wrapper.classList.remove('has-file');
    preview.classList.remove('active');
}

// ============================================
// TOAST NOTIFICATION
// ============================================
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

// ============================================
// STEP NAVIGATION
// ============================================
function goToStep2() {
    const form = document.getElementById('checkout-form');
    
    const selectedRadio = document.querySelector('input[name="payment_method_id"]:checked');
    if (!selectedRadio) {
        triggerToast('Please select a payment method.', false);
        return;
    }
    
    const selectedValue = selectedRadio ? parseInt(selectedRadio.value) : 0;
    if (selectedValue === 0) {
        triggerToast('Please select a payment method.', false);
        return;
    }
    
    if (selectedPaymentMethodName && selectedPaymentMethodName !== 'Cash on Delivery') {
        const fileInput = document.getElementById('transactionImage');
        if (!fileInput.files || fileInput.files.length === 0) {
            triggerToast('Please upload a transaction image.', false);
            return;
        }
    }
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const fullName = document.querySelector('input[name="full_name"]')?.value || '';
    const phone = document.querySelector('input[name="phone"]')?.value || '';
    const address = document.querySelector('textarea[name="delivery_address"]')?.value || '';
    const fileInput = document.getElementById('transactionImage');
    const fileName = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0].name : 'No file attached';
    
    document.getElementById('review-customer-name').innerText = fullName;
    document.getElementById('review-customer-phone').innerText = 'Phone: ' + phone;
    document.getElementById('review-customer-address').innerText = 'Address: ' + address;
    document.getElementById('review-payment-method-label').innerText = selectedPaymentMethodName || 'Cash on Delivery';
    document.getElementById('review-file-status').innerText = 'File: ' + fileName;

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

function submitOrder(button) {
    if (orderSubmitInProgress) {
        return;
    }

    const form = document.getElementById('checkout-form');
    orderSubmitInProgress = true;

    if (button) {
        button.disabled = true;
        button.classList.add('opacity-70', 'cursor-not-allowed');
    }

    document.getElementById('step-progress-line').style.width = '100%';
    document.getElementById('circle-step-3').className = "w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-emerald-500 text-white ring-4 ring-emerald-100 transition-all duration-300";
    document.getElementById('label-step-3').className = "text-xs font-bold text-slate-900 mt-2 transition-all duration-300";

    triggerToast('Submitting your order...');
    if (form.requestSubmit) {
        form.requestSubmit();
        return;
    } else {
        form.submit();
        return;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>