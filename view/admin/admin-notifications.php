<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireAdminPageAccess();

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$notificationController = getNotificationController();
$_GET['limit'] = '50';
$_GET['offset'] = '0';

$result = $notificationController->getNotifications();
$notifications = $result['success'] ? ($result['data'] ?? []) : [];
$unreadCount = $result['success'] ? (int) ($result['unread_count'] ?? 0) : 0;
$errorMessage = $result['success'] ? '' : ($result['message'] ?? 'Failed to load notifications');

$pageTitle = 'Foodie - Notifications';
$activePage = 'notifications';

include __DIR__ . '/includes/sidebar.php';
?>

<?php include __DIR__ . '/../components/notifications-list-content.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
