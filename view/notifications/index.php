<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../customer/includes/permissions.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/notification_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
requireEmailVerification();
checkMaintenanceRedirect();
redirectAdminStaffFromCustomer();

$userController = getUserController();
$currentUser = $userController->getCurrentUser();
$userId = (int) ($currentUser['id'] ?? 0);

$notificationController = getNotificationController();
$limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

$_GET['limit'] = (string) $limit;
$_GET['offset'] = (string) $offset;

$result = $notificationController->getNotifications();
$notifications = $result['success'] ? ($result['data'] ?? []) : [];
$unreadCount = $result['success'] ? (int) ($result['unread_count'] ?? 0) : 0;
$errorMessage = $result['success'] ? '' : ($result['message'] ?? 'Failed to load notifications');

$pageTitle = 'Foodie - Notifications';
$activePage = 'notifications';
$customCss = 'css/dashboard.css';

include __DIR__ . '/../customer/includes/header.php';
?>

<main class="flex-grow max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Notifications</h1>
            <p class="text-sm text-slate-500 mt-1">
                <?php if ($unreadCount > 0): ?>
                    You have <?php echo $unreadCount; ?> unread notification<?php echo $unreadCount === 1 ? '' : 's'; ?>.
                <?php else: ?>
                    You're all caught up.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($unreadCount > 0): ?>
            <button
                id="markAllReadBtn"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors"
            >
                Mark all as read
            </button>
        <?php endif; ?>
    </div>

    <?php if ($errorMessage): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-12 text-center">
                <div class="text-5xl text-slate-300 mb-4">
                    <i class="fa-regular fa-bell-slash"></i>
                </div>
                <p class="text-base font-medium text-slate-700">No notifications yet</p>
                <p class="text-sm text-slate-500 mt-1">Order updates and account alerts will appear here.</p>
            </div>
        <?php else: ?>
            <div id="notificationList" class="divide-y divide-slate-100">
                <?php foreach ($notifications as $notification): ?>
                    <div
                        class="notification-row p-4 sm:p-5 hover:bg-slate-50 transition-colors <?php echo $notification['is_read'] ? '' : 'bg-emerald-50/40'; ?>"
                        data-id="<?php echo (int) $notification['id']; ?>"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-11 h-11 rounded-full flex items-center justify-center text-white <?php echo htmlspecialchars($notification['bg_class']); ?>">
                                <i class="<?php echo htmlspecialchars($notification['icon']); ?>"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </p>
                                        <p class="text-sm text-slate-600 mt-1">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-2">
                                            <?php echo htmlspecialchars($notification['time_ago']); ?>
                                        </p>
                                    </div>

                                    <?php if (!$notification['is_read']): ?>
                                        <span class="unread-dot flex-shrink-0 w-2.5 h-2.5 mt-1 rounded-full bg-emerald-500"></span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 flex items-center gap-3">
                                    <?php if (!$notification['is_read']): ?>
                                        <button
                                            class="mark-read-btn text-xs font-semibold text-emerald-600 hover:text-emerald-700"
                                            data-id="<?php echo (int) $notification['id']; ?>"
                                        >
                                            Mark as read
                                        </button>
                                    <?php endif; ?>
                                    <button
                                        class="delete-btn text-xs font-semibold text-red-500 hover:text-red-600"
                                        data-id="<?php echo (int) $notification['id']; ?>"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
async function postNotificationAction(url, notificationId = null) {
    const body = notificationId ? `notification_id=${notificationId}` : '';
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body,
    });
    return response.json();
}

document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
    const data = await postNotificationAction('/Campus-Food-Ordering-System/Public/api/notifications/mark-all-read');
    if (data.success) {
        window.location.reload();
    }
});

document.querySelectorAll('.mark-read-btn').forEach((button) => {
    button.addEventListener('click', async (event) => {
        event.stopPropagation();
        const notificationId = button.dataset.id;
        const data = await postNotificationAction('/Campus-Food-Ordering-System/Public/api/notifications/mark-read', notificationId);
        if (data.success) {
            const row = document.querySelector(`.notification-row[data-id="${notificationId}"]`);
            row?.classList.remove('bg-emerald-50/40');
            row?.querySelector('.unread-dot')?.remove();
            button.remove();
        }
    });
});

document.querySelectorAll('.delete-btn').forEach((button) => {
    button.addEventListener('click', async (event) => {
        event.stopPropagation();
        if (!confirm('Delete this notification?')) {
            return;
        }

        const notificationId = button.dataset.id;
        const data = await postNotificationAction('/Campus-Food-Ordering-System/Public/api/notifications/delete', notificationId);
        if (data.success) {
            document.querySelector(`.notification-row[data-id="${notificationId}"]`)?.remove();
        }
    });
});
</script>

<?php include __DIR__ . '/../customer/includes/footer.php'; ?>
