<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/auth_helper.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/notification_helpers.php';

checkMaintenanceRedirect();

if (isAdminLike()) {
    header('Location: /Campus-Food-Ordering-System/view/admin/admin-notifications.php');
    exit();
}

requireStaffAuth();
requireEmailVerification();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';
$permissions = getStaffPermissions($userId);

$notificationController = getNotificationController();
$_GET['limit'] = '50';
$_GET['offset'] = '0';

$result = $notificationController->getNotifications();
$notifications = $result['success'] ? ($result['data'] ?? []) : [];
$unreadCount = $result['success'] ? (int) ($result['unread_count'] ?? 0) : 0;
$errorMessage = $result['success'] ? '' : ($result['message'] ?? 'Failed to load notifications');

$pageTitle = 'Staff Notifications - Foodie';
$activePage = 'notifications';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <?php include __DIR__ . '/../components/notifications-list-content.php'; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
