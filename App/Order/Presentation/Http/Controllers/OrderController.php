<?php
namespace App\Order\Presentation\Http\Controllers;

use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Infrastructure\Repositories\OrderRepository;
use Inc\Database;

class OrderController
{
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
    }

    public function index(): array
    {
        $useCase = new GetAllOrdersUseCase($this->orderRepository);
        return $useCase->execute();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        $useCase = new GetAllOrdersUseCase($this->orderRepository);
        return $useCase->getRecentOrders($limit);
    }

    public function updateStatus(int $orderId, int $statusId): array
    {
        $useCase = new UpdateOrderStatusUseCase($this->orderRepository);
        return $useCase->execute($orderId, $statusId);
    }

    public function getStatuses(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM order_statuses ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}