<?php
namespace App\User\Application\Usecases;

use App\User\Infrastructure\Repositories\UserRepository;

class GetReportsUseCase
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

// App/User/Application/Usecases/GetReportsUseCase.php

public function execute(): array
{
    return [
        'total_orders' => $this->userRepository->getTotalOrders(),
        'total_revenue' => $this->userRepository->getTotalRevenue(),
        'completed_orders' => $this->userRepository->getCompletedOrders(), // ✅ NEW
        'pending_orders' => $this->userRepository->getPendingOrders(),
        'monthly_revenue' => $this->userRepository->getMonthlyRevenue(6),
        'order_stats' => $this->userRepository->getOrderStats()
    ];
}
}