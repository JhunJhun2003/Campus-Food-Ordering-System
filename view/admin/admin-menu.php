<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
requirePermission('manage_menu');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$foodController = getFoodController();

// Handle form submissions with redirect (PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectUrl = 'admin-menu.php';
    $successParam = '';
    
    // Add Food
    if (isset($_POST['add_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=added';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to add food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
    
    // Edit Food
    if (isset($_POST['edit_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=updated';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to update food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
    
    // Delete Food
    if (isset($_POST['delete_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=deleted';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to delete food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
}

// Check for success/error messages from session
$message = null;
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $message = ['success' => true, 'message' => 'Food item added successfully!'];
            break;
        case 'updated':
            $message = ['success' => true, 'message' => 'Food item updated successfully!'];
            break;
        case 'deleted':
            $message = ['success' => true, 'message' => 'Food item deleted successfully!'];
            break;
    }
} elseif (isset($_GET['error']) && isset($_SESSION['form_error'])) {
    $message = ['success' => false, 'message' => $_SESSION['form_error']];
    unset($_SESSION['form_error']);
}

// Get data for display
$foods = $foodController->index();
$categories = $foodController->getCategories();

// Handle edit mode
$editFood = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editFood = $foodController->getForEdit($editId);
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Manage Menu';
$activePage = 'menu';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manage Menu</h1>
        <p class="text-gray-400 text-sm mt-1">Manage your food items and categories</p>
    </div>
    <button onclick="openAddFoodModal()" class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors">
        <i class="fa-solid fa-plus"></i>
        <span>Add Item</span>
    </button>
</div>

<!-- Menu Table -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
    <div class="p-5 flex items-center justify-between border-b border-gray-50">
        <div class="relative w-full max-w-xl">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search food items..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
        </div>
        <div class="flex items-center space-x-3">
            <select id="categoryFilter" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6">Item</th>
                    <th class="py-3 px-6">Category</th>
                    <th class="py-3 px-6">Price</th>
                    <th class="py-3 px-6">Stock</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="menuTableBody" class="divide-y divide-gray-100 text-sm text-gray-700">
                <?php if (empty($foods)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fa-regular fa-utensils text-4xl block mb-3"></i>
                            <p class="text-sm font-medium">No food items found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($foods as $food): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors" data-category="<?php echo $food->getCategoryId(); ?>">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <?php if ($food->getImage()): ?>
                                        <?php 
                                            $imagePath = '/Campus-Food-Ordering-System/Public/uploads/foods/' . htmlspecialchars($food->getImage());
                                            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
                                            if (file_exists($fullPath)): 
                                        ?>
                                            <img src="<?php echo $imagePath; ?>" 
                                                 alt="<?php echo htmlspecialchars($food->getName()); ?>" 
                                                 class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                                <?php 
                                                    $emoji = match($food->getCategoryId()) {
                                                        1 => '🍔',
                                                        2 => '🍕',
                                                        3 => '🥤',
                                                        4 => '🍰',
                                                        5 => '🍚',
                                                        default => '🍽️'
                                                    };
                                                    echo $emoji;
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                            <?php 
                                                $emoji = match($food->getCategoryId()) {
                                                    1 => '🍔',
                                                    2 => '🍕',
                                                    3 => '🥤',
                                                    4 => '🍰',
                                                    5 => '🍚',
                                                    default => '🍽️'
                                                };
                                                echo $emoji;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($food->getName()); ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-gray-600">
                                <?php 
                                    $categoryName = '';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $food->getCategoryId()) {
                                            $categoryName = $cat['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($categoryName ?: 'Uncategorized');
                                ?>
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-900">$<?php echo number_format($food->getPrice(), 2); ?></td>
                            <td class="py-4 px-6">
                                <?php if ($food->getStock() > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        <?php echo $food->getStock(); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Out of Stock
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-3">
                                    <button onclick="openEditFoodModal(<?php echo $food->getId(); ?>)" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event);">
                                        <input type="hidden" name="delete_food" value="1">
                                        <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-100 flex items-center justify-between bg-white">
        <p class="text-sm text-gray-400">
            Showing <span class="font-medium text-gray-600"><?php echo count($foods); ?></span> items
        </p>
        <nav class="inline-flex -space-x-px rounded-md space-x-2" aria-label="Pagination">
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">
                1
            </button>
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
        </nav>
    </div>
</div>

<!-- ============================================ -->
<!-- ADD FOOD MODAL -->
<!-- ============================================ -->
<div id="addFoodModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Add New Food Item</h2>
            <button onclick="closeAddFoodModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message['message']); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="addFoodForm" enctype="multipart/form-data">
            <input type="hidden" name="add_food" value="1">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" placeholder="e.g., Cheese Burger" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Quantity</label>
                    <input type="number" name="stock" placeholder="0" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prep Time (mins)</label>
                    <input type="number" name="preparation_time" placeholder="15" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Describe the food item..." class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm"></textarea>
            </div>
            
            <!-- IMAGE UPLOAD -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Image</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-lg hover:border-indigo-500 transition-colors cursor-pointer" id="addImageDropZone">
                    <div class="space-y-1 text-center">
                        <i class="fa-regular fa-image text-3xl text-slate-400"></i>
                        <div class="flex text-sm text-slate-600">
                            <label for="addFoodImage" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload a file</span>
                                <input id="addFoodImage" name="image" type="file" class="sr-only" accept="image/*" onchange="previewImage(event, 'addImagePreview')">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">PNG, JPG, GIF up to 2MB</p>
                        <div id="addImagePreview" class="hidden mt-2">
                            <img id="addImagePreviewImg" src="#" alt="Preview" class="max-h-32 mx-auto rounded-lg border border-slate-200">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Add Food Item</button>
            <button type="button" onclick="closeAddFoodModal()" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- EDIT FOOD MODAL -->
<!-- ============================================ -->
<?php if ($editFood): ?>
<div id="editFoodModal" class="modal-overlay fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Edit Food Item</h2>
            <a href="admin-menu.php" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>

        <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message['message']); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="edit_food" value="1">
            <input type="hidden" name="food_id" value="<?php echo $editFood['id']; ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editFood['name']); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $editFood['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="<?php echo $editFood['price']; ?>" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Quantity</label>
                    <input type="number" name="stock" value="<?php echo $editFood['stock']; ?>" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prep Time (mins)</label>
                    <input type="number" name="preparation_time" value="<?php echo $editFood['preparation_time'] ?? 15; ?>" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm"><?php echo htmlspecialchars($editFood['description'] ?? ''); ?></textarea>
            </div>
            
            <!-- IMAGE UPLOAD WITH CURRENT IMAGE -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Image</label>
                <?php if (!empty($editFood['image'])): ?>
                    <div class="mb-2">
                        <img src="/Campus-Food-Ordering-System/Public/uploads/foods/<?php echo htmlspecialchars($editFood['image']); ?>" 
                             alt="Current Image" 
                             class="max-h-20 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-500 mt-1">Current image: <?php echo htmlspecialchars($editFood['image']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-lg hover:border-indigo-500 transition-colors cursor-pointer" id="editImageDropZone">
                    <div class="space-y-1 text-center">
                        <i class="fa-regular fa-image text-3xl text-slate-400"></i>
                        <div class="flex text-sm text-slate-600">
                            <label for="editFoodImage" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload new image</span>
                                <input id="editFoodImage" name="image" type="file" class="sr-only" accept="image/*" onchange="previewImage(event, 'editImagePreview')">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">PNG, JPG, GIF up to 2MB</p>
                        <div id="editImagePreview" class="hidden mt-2">
                            <img id="editImagePreviewImg" src="#" alt="Preview" class="max-h-32 mx-auto rounded-lg border border-slate-200">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editFood['image'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update Food Item</button>
            <a href="admin-menu.php" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm block text-center">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TOAST -->
<!-- ============================================ -->
<div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

<script>
// ============================================
// SEARCH
// ============================================
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#menuTableBody tr').forEach(row => {
        const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
        const category = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        row.style.display = (name.includes(searchTerm) || category.includes(searchTerm)) ? '' : 'none';
    });
});

// ============================================
// CATEGORY FILTER
// ============================================
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    document.querySelectorAll('#menuTableBody tr').forEach(row => {
        const rowCategory = row.dataset.category || '';
        row.style.display = (categoryId === '' || rowCategory === categoryId) ? '' : 'none';
    });
});

// ============================================
// ADD FOOD MODAL
// ============================================
function openAddFoodModal() {
    document.getElementById('addFoodModal').classList.remove('hidden');
    document.getElementById('addFoodModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddFoodModal() {
    document.getElementById('addFoodModal').classList.add('hidden');
    document.getElementById('addFoodModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('addFoodForm')?.reset();
    document.getElementById('addImagePreview')?.classList.add('hidden');
}

// ============================================
// EDIT FOOD MODAL
// ============================================
function openEditFoodModal(foodId) {
    window.location.href = 'admin-menu.php?edit=' + foodId;
}

function closeEditFoodModal() {
    window.location.href = 'admin-menu.php';
}

// ============================================
// DELETE CONFIRM
// ============================================
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this food item? This action cannot be undone.')) {
        event.preventDefault();
        return false;
    }
    return true;
}

// ============================================
// IMAGE PREVIEW
// ============================================
function previewImage(event, previewId) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewContainer = document.getElementById(previewId);
        const previewImg = document.getElementById(previewId + 'Img');
        previewImg.src = e.target.result;
        previewContainer.classList.remove('hidden');
        previewContainer.classList.add('block');
    }
    reader.readAsDataURL(file);
}

// ============================================
// DRAG AND DROP SUPPORT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Add drag and drop for add modal
    const addDropZone = document.getElementById('addImageDropZone');
    if (addDropZone) {
        addDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-500', 'bg-indigo-50');
        });
        addDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        addDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('addFoodImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        });
    }
    
    // Add drag and drop for edit modal
    const editDropZone = document.getElementById('editImageDropZone');
    if (editDropZone) {
        editDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-500', 'bg-indigo-50');
        });
        editDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        editDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('editFoodImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        });
    }
});

// ============================================
// PREVENT DOUBLE FORM SUBMISSION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';
            }
        });
    });
});

// ============================================
// TOAST
// ============================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg transform transition-all duration-300 z-50 max-w-md';
    const colors = {
        success: { bg: '#10B981', text: 'white' },
        error: { bg: '#EF4444', text: 'white' },
        info: { bg: '#3B82F6', text: 'white' }
    };
    const style = colors[type] || colors.success;
    toast.style.background = style.bg;
    toast.style.color = style.text;
    setTimeout(() => {
        toast.classList.remove('translate-y-24', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}

<?php if (isset($message)): ?>
    <?php if ($message['success']): ?>
        showToast('<?php echo htmlspecialchars($message['message']); ?>', 'success');
    <?php else: ?>
        showToast('<?php echo htmlspecialchars($message['message']); ?>', 'error');
    <?php endif; ?>
<?php endif; ?>
</script>

</main>
</body>
</html>