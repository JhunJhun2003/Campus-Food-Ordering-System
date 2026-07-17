<?php
declare(strict_types=1);

/**
 * Notification Helper Functions
 */

use App\Notification\Application\Services\NotificationService;
use App\Notification\Application\Usecases\CreateNotificationUseCase;
use App\Notification\Infrastructure\Repositories\NotificationRepository;

function getNotificationService(): NotificationService
{
    static $instance = null;
    
    if ($instance === null) {
        $repository = new NotificationRepository();
        $createUseCase = new CreateNotificationUseCase($repository);
        $instance = new NotificationService($createUseCase);
    }
    
    return $instance;
}

function getNotificationController()
{
    static $instance = null;
    
    if ($instance === null) {
        $repository = new NotificationRepository();
        
        $createUseCase = new \App\Notification\Application\Usecases\CreateNotificationUseCase($repository);
        $getUseCase = new \App\Notification\Application\Usecases\GetUserNotificationsUseCase($repository);
        $unreadCountUseCase = new \App\Notification\Application\Usecases\GetUnreadCountUseCase($repository);
        $markReadUseCase = new \App\Notification\Application\Usecases\MarkAsReadUseCase($repository);
        $markAllReadUseCase = new \App\Notification\Application\Usecases\MarkAllAsReadUseCase($repository);
        $deleteUseCase = new \App\Notification\Application\Usecases\DeleteNotificationUseCase($repository);
        
        $instance = new \App\Notification\Presentation\Http\Controllers\NotificationController(
            $getUseCase,
            $unreadCountUseCase,
            $markReadUseCase,
            $markAllReadUseCase,
            $deleteUseCase
        );
    }
    
    return $instance;
}