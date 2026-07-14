<?php
declare(strict_types=1);

namespace App\Refund\Domain\Repositories;

use App\Refund\Domain\Entities\Refund;

interface RefundRepositoryInterface
{
    public function save(Refund $refund): int;
    public function findById(int $id): ?Refund;
    public function findByOrderId(int $orderId): ?Refund;
    public function findByUser(int $userId): array;
    public function findAllPending(): array;
    public function findAll(?int $statusId = null, ?int $userId = null, int $limit = 20, int $offset = 0): array;
    public function count(?int $statusId = null, ?int $userId = null): int;
    public function updateStatus(int $refundId, int $statusId): bool;
    public function getRefundStatuses(): array;
}