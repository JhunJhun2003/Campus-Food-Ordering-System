<?php
declare(strict_types=1);

namespace App\Notification\Domain\Enums;

enum NotificationType: string
{
    // Order Notifications
    case ORDER_CREATED = 'order_created';
    case ORDER_ACCEPTED = 'order_accepted';
    case ORDER_PREPARING = 'order_preparing';
    case ORDER_READY = 'order_ready';
    case ORDER_COMPLETED = 'order_completed';
    case ORDER_CANCELLED = 'order_cancelled';
    
    // Payment Notifications
    case PAYMENT_RECEIVED = 'payment_received';
    case PAYMENT_FAILED = 'payment_failed';
    
    // Refund Notifications
    case REFUND_REQUESTED = 'refund_requested';
    case REFUND_APPROVED = 'refund_approved';
    case REFUND_REJECTED = 'refund_rejected';
    
    // User Notifications
    case WELCOME = 'welcome';
    case EMAIL_VERIFIED = 'email_verified';
    case PASSWORD_CHANGED = 'password_changed';
    
    // System Notifications
    case SYSTEM = 'system';
    case MAINTENANCE = 'maintenance';
    
    public function getIcon(): string
    {
        return match($this) {
            self::ORDER_CREATED, self::ORDER_ACCEPTED, self::ORDER_PREPARING => 'fa-solid fa-clock',
            self::ORDER_READY => 'fa-solid fa-box-open',
            self::ORDER_COMPLETED => 'fa-solid fa-circle-check',
            self::ORDER_CANCELLED => 'fa-solid fa-circle-xmark',
            self::PAYMENT_RECEIVED => 'fa-solid fa-credit-card',
            self::PAYMENT_FAILED => 'fa-solid fa-exclamation-circle',
            self::REFUND_REQUESTED => 'fa-solid fa-rotate-left',
            self::REFUND_APPROVED => 'fa-solid fa-check-circle',
            self::REFUND_REJECTED => 'fa-solid fa-times-circle',
            self::WELCOME => 'fa-solid fa-hand-wave',
            self::EMAIL_VERIFIED => 'fa-solid fa-envelope-circle-check',
            self::PASSWORD_CHANGED => 'fa-solid fa-key',
            self::SYSTEM => 'fa-solid fa-bell',
            self::MAINTENANCE => 'fa-solid fa-tools',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::ORDER_CREATED, self::ORDER_ACCEPTED => 'blue',
            self::ORDER_PREPARING => 'purple',
            self::ORDER_READY => 'cyan',
            self::ORDER_COMPLETED => 'green',
            self::ORDER_CANCELLED => 'red',
            self::PAYMENT_RECEIVED => 'emerald',
            self::PAYMENT_FAILED => 'red',
            self::REFUND_REQUESTED => 'yellow',
            self::REFUND_APPROVED => 'green',
            self::REFUND_REJECTED => 'red',
            self::WELCOME => 'indigo',
            self::EMAIL_VERIFIED => 'green',
            self::PASSWORD_CHANGED => 'amber',
            self::SYSTEM => 'gray',
            self::MAINTENANCE => 'orange',
        };
    }

    public function getBgClass(): string
    {
        return match($this) {
            self::ORDER_CREATED, self::ORDER_ACCEPTED => 'bg-blue-500',
            self::ORDER_PREPARING => 'bg-purple-500',
            self::ORDER_READY => 'bg-cyan-500',
            self::ORDER_COMPLETED => 'bg-green-500',
            self::ORDER_CANCELLED => 'bg-red-500',
            self::PAYMENT_RECEIVED => 'bg-emerald-500',
            self::PAYMENT_FAILED => 'bg-red-500',
            self::REFUND_REQUESTED => 'bg-yellow-500',
            self::REFUND_APPROVED => 'bg-green-500',
            self::REFUND_REJECTED => 'bg-red-500',
            self::WELCOME => 'bg-indigo-500',
            self::EMAIL_VERIFIED => 'bg-green-500',
            self::PASSWORD_CHANGED => 'bg-amber-500',
            self::SYSTEM => 'bg-gray-500',
            self::MAINTENANCE => 'bg-orange-500',
        };
    }
}