<?php
declare(strict_types=1);

namespace App\Notification\Presentation\Http\Controllers;

use App\Notification\Application\DTOs\GetNotificationsRequest;
use App\Notification\Application\Usecases\GetUserNotificationsUseCase;
use App\Notification\Application\Usecases\GetUnreadCountUseCase;
use App\Notification\Application\Usecases\MarkAsReadUseCase;
use App\Notification\Application\Usecases\MarkAllAsReadUseCase;
use App\Notification\Application\Usecases\DeleteNotificationUseCase;
use App\Shared\Presentation\Http\Controllers\BaseController;

class NotificationController extends BaseController
{
    private GetUserNotificationsUseCase $getUserNotificationsUseCase;
    private GetUnreadCountUseCase $getUnreadCountUseCase;
    private MarkAsReadUseCase $markAsReadUseCase;
    private MarkAllAsReadUseCase $markAllAsReadUseCase;
    private DeleteNotificationUseCase $deleteNotificationUseCase;

    public function __construct(
        GetUserNotificationsUseCase $getUserNotificationsUseCase,
        GetUnreadCountUseCase $getUnreadCountUseCase,
        MarkAsReadUseCase $markAsReadUseCase,
        MarkAllAsReadUseCase $markAllAsReadUseCase,
        DeleteNotificationUseCase $deleteNotificationUseCase
    ) {
        parent::__construct();
        $this->getUserNotificationsUseCase = $getUserNotificationsUseCase;
        $this->getUnreadCountUseCase = $getUnreadCountUseCase;
        $this->markAsReadUseCase = $markAsReadUseCase;
        $this->markAllAsReadUseCase = $markAllAsReadUseCase;
        $this->deleteNotificationUseCase = $deleteNotificationUseCase;
    }

    /**
     * Get user notifications (API)
     */
    public function getNotifications(): array
    {
        try {
            $this->requireAuthentication();
            $userId = $this->getCurrentUserId();

            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
            $unreadOnly = null;
            if (isset($_GET['unread_only'])) {
                $unreadOnly = filter_var($_GET['unread_only'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            $request = new GetNotificationsRequest($userId, $limit, $offset, $unreadOnly);
            return $this->getUserNotificationsUseCase->execute($request);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get unread count (API)
     */
    public function getUnreadCount(): array
    {
        try {
            $this->requireAuthentication();
            $userId = $this->getCurrentUserId();

            return $this->getUnreadCountUseCase->execute($userId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mark notification as read (API)
     */
    public function markAsRead(): array
    {
        try {
            $this->requireAuthentication();
            $userId = $this->getCurrentUserId();

            $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

            if ($notificationId <= 0) {
                return ['success' => false, 'message' => 'Invalid notification ID'];
            }

            return $this->markAsReadUseCase->execute($notificationId, $userId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mark all notifications as read (API)
     */
    public function markAllAsRead(): array
    {
        try {
            $this->requireAuthentication();
            $userId = $this->getCurrentUserId();

            return $this->markAllAsReadUseCase->execute($userId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete notification (API)
     */
    public function delete(): array
    {
        try {
            $this->requireAuthentication();
            $userId = $this->getCurrentUserId();

            $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

            if ($notificationId <= 0) {
                return ['success' => false, 'message' => 'Invalid notification ID'];
            }

            return $this->deleteNotificationUseCase->execute($notificationId, $userId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Show notification bell widget (View)
     */
    public function widget(): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $userId = $this->getCurrentUserId();
        
        // Get unread count
        $result = $this->getUnreadCountUseCase->execute($userId);
        $unreadCount = $result['success'] ? ($result['data']['count'] ?? 0) : 0;

        // Get recent notifications
        $request = new GetNotificationsRequest($userId, 10, 0);
        $result = $this->getUserNotificationsUseCase->execute($request);
        $notifications = $result['success'] ? $result['data'] : [];

        $notificationsPageUrl = '/Campus-Food-Ordering-System/notifications';
        if ($this->isAdmin()) {
            $notificationsPageUrl = '/Campus-Food-Ordering-System/view/admin/admin-notifications.php';
        } elseif ($this->isStaff()) {
            $notificationsPageUrl = '/Campus-Food-Ordering-System/view/staff/staff-notifications.php';
        }

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 5);
        include $basePath . '/view/components/notification-widget.php';
    }
}