<?php
declare(strict_types=1);

namespace App\Order\Domain\Repositories;

use App\Order\Domain\Entities\Order;

interface OrderRepositoryInterface
{
    // ============================================
    // CRUD METHODS
    // ============================================

    /**
     * Save a new order and return its ID
     */
    public function save(Order $order): int;

    /**
     * Update an existing order
     */
    public function updateOrder(Order $order): void;

    /**
     * Find order by ID
     */
    public function findById(int $id): ?Order;

    /**
     * Find all orders
     */
    public function findAll(): array;

    /**
     * Find orders by status
     */
    public function findByStatus(int $statusId): array;

    /**
     * Find orders by user ID
     */
    public function findByUser(int $userId): array;

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, int $statusId): void;

    /**
     * Delete order by ID
     */
    public function delete(int $id): void;

    /**
     * Get recent orders with limit
     */
    public function getRecentOrders(int $limit = 10): array;

    // ============================================
    // ORDER ITEMS METHODS
    // ============================================

    /**
     * Get items for a specific order
     */
    public function getOrderItems(int $orderId): array;

    /**
     * Add item to order
     */
    public function addItem(int $orderId, int $foodId, int $quantity, float $unitPrice, ?int $foodSizeId = null): void;

    /**
     * Save multiple order items
     */
    public function saveItems(int $orderId, array $items): void;

    // ============================================
    // STATUS METHODS
    // ============================================

    /**
     * Get all order statuses
     */
    public function getOrderStatuses(): array;

    /**
     * Get order status by ID
     */
    public function getOrderStatusById(int $statusId): ?array;

    // ============================================
    // STATS METHODS (For Admin Dashboard)
    // ============================================

    /**
     * Get total number of orders
     */
    public function getTotalOrders(): int;

    /**
     * Get number of pending orders
     */
    public function getPreparingOrders(): int;
    public function getPendingOrders(): int;

    /**
     * Get number of completed orders
     */
    public function getCompletedOrders(): int;

    /**
     * Count orders by status name
     */
    public function countByStatusName(string $statusName): int;

    /**
     * Get total revenue from completed orders
     */
    public function getTotalRevenue(): float;

    /**
     * Get total revenue between a date range
     */
    public function getRevenueBetween(string $startDate, string $endDate): float;

    /**
     * Get total order count between a date range
     */
    public function getOrdersBetween(string $startDate, string $endDate): int;

    /**
     * Get completed order count between a date range
     */
    public function getCompletedOrdersBetween(string $startDate, string $endDate): int;

    /**
     * Get pending order count between a date range
     */
    public function getPendingOrdersBetween(string $startDate, string $endDate): int;

    /**
     * Get daily revenue between a date range
     */
    public function getDailyRevenueBetween(string $startDate, string $endDate): array;

    /**
     * Get daily revenue for chart (from 1st day to end of current month)
     */
    public function getMonthlyRevenue(int $months = 6): array;

    /**
     * Get order statistics grouped by status
     */
    public function getOrderStats(): array;

    /**
     * Get order statistics summary
     */
    public function getOrderStatistics(): array;

    /**
     * Count orders placed today
     */
    public function countToday(): int;

    /**
     * Count orders placed this month
     */
    public function countMonthly(): int;

      public function lockOrder(int $orderId): ?array;
}