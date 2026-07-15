<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

// ✅ Check maintenance mode
checkMaintenanceRedirect();
// Guest access is allowed on the dashboard. No requireCustomerAuth() check is needed here.

// ✅ Redirect admin/staff away from customer dashboard
redirectAdminStaffFromCustomer();

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
    $imagePath = null;
    if (!empty($food->getImage())) {
        $imagePath = '/Campus-Food-Ordering-System/Public/uploads/foods/' . rawurlencode($food->getImage());
    }

    $menuData[] = [
        'id' => $food->getId(),
        'name' => $food->getName(),
        'price' => $food->getPrice(),
        'category' => $categoryName ?: 'Uncategorized',
        'emoji' => $emojiMap[$food->getCategoryId()] ?? '🍽️',
        'stock' => $food->getStock(),
        'image' => $imagePath
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

<main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5">
    
    <?php include __DIR__ . '/component/dashboard/header.php'; ?>
    
    <?php include __DIR__ . '/component/dashboard/search-bar.php'; ?>
    
    <?php include __DIR__ . '/component/dashboard/categories-filter.php'; ?>
    
    <?php include __DIR__ . '/component/dashboard/menu-grid.php'; ?>
    
    <?php include __DIR__ . '/component/dashboard/empty-state.php'; ?>

</main>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ✅ PHP variables passed to JavaScript
const menuDatabase = <?php echo json_encode($menuData); ?>;
const isUserLoggedIn = <?php echo (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) ? 'true' : 'false'; ?>;
let activeCategory = 'all';
let searchQuery = '';
let cartItemCount = <?php echo $itemCount; ?>;
</script>

<script src="/Campus-Food-Ordering-System/view/customer/component/dashboard/scripts/dashboard.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>