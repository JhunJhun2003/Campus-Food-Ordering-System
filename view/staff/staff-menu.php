<?php
declare(strict_types=1);

session_start();

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/order_helpers.php'; // ✅ ADD THIS if exists, or create it

// ✅ Check maintenance mode - staff cannot access during maintenance
checkMaintenanceRedirect();
if (isAdmin()) {
    $_SESSION['error'] = 'Staff pages are for staff members only.';
    header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
    exit();
}
requireStaffAuth();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';

$permissions = getStaffPermissions($userId);

// Check if user can view menu
if (!$permissions['viewMenu']) {
    $_SESSION['error'] = "You do not have permission to view menu.";
    header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
    exit();
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use the helper function
$foodController = getFoodController();

// Block modifications if user doesn't have management permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$permissions['manageMenu']) {
    $_SESSION['error'] = "You do not have permission to manage menu items.";
    header('Location: /Campus-Food-Ordering-System/view/staff/staff-menu.php');
    exit();
}

$result = $foodController->handleRequest();
$message = $result['message'] ?? null;
$editFood = $result['editFood'] ?? null;

$foods = $foodController->index();
$categories = $foodController->getCategories();

// ... rest of your staff-menu.php code (unchanged) ...

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Staff Menu - Foodie';
$activePage = 'menu';
$customCss = 'css/staff-menu.css';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Manage Menu</h1>
            <p class="text-sm text-slate-500">
                Manage your food items and categories
                <?php if ($permissions['manageMenu']): ?>
                    <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold ml-2">
                        <i class="fa-solid fa-check mr-1"></i> Can Manage
                    </span>
                <?php else: ?>
                    <span class="inline-block px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-xs font-semibold ml-2">
                        <i class="fa-solid fa-lock mr-1"></i> View Only
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($permissions['manageMenu']): ?>
            <button onclick="openAddFoodModal()" class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Item</span>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <!-- Search & Filter -->
        <div class="p-5 flex items-center justify-between border-b border-slate-50">
            <div class="relative w-full max-w-xl">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                </span>
                <input type="text" placeholder="Search food items..." class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-slate-400" id="searchInput">
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6">Item</th>
                        <th class="py-3 px-6">Category</th>
                        <th class="py-3 px-6">Price</th>
                        <th class="py-3 px-6">Stock</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    <?php if (empty($foods)): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-utensils text-4xl block mb-3"></i>
                                <p class="text-sm font-medium">No food items found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($foods as $food): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors" data-category="<?php echo $food->getCategoryId(); ?>">
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-2xl">
                                            <?php 
                                                $emoji = match($food->getCategoryId()) {
                                                    1 => '🍔', 2 => '🍕', 3 => '🥤', 4 => '🍰', 5 => '🍚',
                                                    default => '🍽️'
                                                };
                                                echo $emoji;
                                            ?>
                                        </div>
                                        <span class="font-medium text-slate-900"><?php echo htmlspecialchars($food->getName()); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-600">
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
                                <td class="py-4 px-6 font-medium text-slate-900">$<?php echo number_format($food->getPrice(), 2); ?></td>
                                <td class="py-4 px-6">
                                    <?php if ($food->getStock() > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"><?php echo $food->getStock(); ?></span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center space-x-3">
                                        <?php if ($permissions['manageMenu']): ?>
                                            <button onclick="openEditFoodModal(<?php echo $food->getId(); ?>)" class="text-slate-400 hover:text-indigo-600 transition-colors" title="Edit">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event);">
                                                <input type="hidden" name="delete_food" value="1">
                                                <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                                <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400"><i class="fa-solid fa-lock mr-1"></i> View Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-white">
            <p class="text-sm text-slate-400">Showing <span class="font-medium text-slate-600"><?php echo count($foods); ?></span> items</p>
            <nav class="inline-flex -space-x-px rounded-md space-x-2">
                <button class="inline-flex items-center px-2 py-1.5 text-slate-400 border border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">1</button>
                <button class="inline-flex items-center px-2 py-1.5 text-slate-400 border border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </nav>
        </div>
    </div>
</main>

<!-- Add Food Modal -->
<div id="addFoodModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-slate-900">Add New Food Item</h2>
            <button onclick="closeAddFoodModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>
        <form method="POST" action="" id="addFoodForm">
            <input type="hidden" name="add_food" value="1">
            <div class="form-group mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" placeholder="e.g., Cheese Burger" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" placeholder="0.00" step="0.01" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Stock Quantity</label>
                    <input type="number" name="stock" placeholder="0" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Preparation Time (mins)</label>
                    <input type="number" name="preparation_time" placeholder="15" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Describe the food item..." class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors">Add Food Item</button>
            <button type="button" onclick="closeAddFoodModal()" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg font-semibold text-sm transition-colors mt-2">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Food Modal -->
<div id="editFoodModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-slate-900">Edit Food Item</h2>
            <button onclick="closeEditFoodModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>
        <?php if ($editFood): ?>
        <form method="POST" action="" id="editFoodForm">
            <input type="hidden" name="edit_food" value="1">
            <input type="hidden" name="food_id" value="<?php echo $editFood['id']; ?>">
            <!-- Same fields as add form but populated -->
            <div class="form-group mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editFood['name']); ?>" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $editFood['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="<?php echo $editFood['price']; ?>" step="0.01" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Stock Quantity</label>
                    <input type="number" name="stock" value="<?php echo $editFood['stock']; ?>" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Preparation Time (mins)</label>
                    <input type="number" name="preparation_time" value="<?php echo $editFood['preparation_time'] ?? 15; ?>" min="0" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm"><?php echo htmlspecialchars($editFood['description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors">Update Food Item</button>
            <button type="button" onclick="closeEditFoodModal()" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg font-semibold text-sm transition-colors mt-2">Cancel</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }
.modal {
    background: white;
    border-radius: 16px;
    max-width: 550px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    animation: modalSlideIn 0.3s ease;
}
@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<script>
function openAddFoodModal() {
    document.getElementById('addFoodModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeAddFoodModal() {
    document.getElementById('addFoodModal').classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('addFoodForm')?.reset();
}
function openEditFoodModal(foodId) {
    window.location.href = 'staff-menu.php?edit=' + foodId;
}
function closeEditFoodModal() {
    window.location.href = 'staff-menu.php';
}
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this food item? This action cannot be undone.')) {
        event.preventDefault();
        return false;
    }
    return true;
}

document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
        const category = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        row.style.display = (name.includes(searchTerm) || category.includes(searchTerm)) ? '' : 'none';
    });
});

document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    document.querySelectorAll('tbody tr').forEach(row => {
        const rowCategory = row.dataset.category || '';
        row.style.display = (categoryId === '' || rowCategory === categoryId) ? '' : 'none';
    });
});

document.getElementById('addFoodModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddFoodModal();
});
document.getElementById('editFoodModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditFoodModal();
});

<?php if ($editFood): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editFoodModal').classList.add('active');
    document.body.style.overflow = 'hidden';
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>