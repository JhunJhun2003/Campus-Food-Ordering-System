<?php
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
    public function addItem(int $orderId, int $foodId, int $quantity, float $unitPrice): void;

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
    // STATS METHODS
    // ============================================

    /**
     * Get total number of orders
     */
    public function getTotalOrders(): int;

    /**
     * Get number of pending orders
     */
    public function getPendingOrders(): int;

    /**
     * Count orders by status name
     */
    public function countByStatusName(string $statusName): int;

    /**
     * Get order statistics
     */
    public function getOrderStatistics(): array;
}