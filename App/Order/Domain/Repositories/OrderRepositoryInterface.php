<?php
namespace App\Order\Domain\Repositories;

use App\Order\Domain\Entities\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(int $id): ?Order;
    public function findAll(): array;
    public function findByStatus(int $statusId): array;
    public function findByUser(int $userId): array;
    public function updateStatus(int $orderId, int $statusId): void;
    public function delete(int $id): void;
    public function getRecentOrders(int $limit = 10): array;
}