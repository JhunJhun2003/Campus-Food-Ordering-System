<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;

class GetDashboardStatsUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(): array
    {
        return [
            'total_users' => $this->userRepository->getTotalUsers(),
            'total_foods' => $this->userRepository->getTotalFoods(),
            'total_orders' => $this->userRepository->getTotalOrders(),
            'pending_orders' => $this->userRepository->getPendingOrders(),
            'recent_orders' => $this->userRepository->getRecentOrders(5)
        ];
    }
}