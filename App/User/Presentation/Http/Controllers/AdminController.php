<?php
namespace App\User\Presentation\Http\Controllers;

use App\User\Application\Usecases\GetDashboardStatsUseCase;
use App\User\Infrastructure\Repositories\UserRepository;

class AdminController
{
    private UserRepository $userRepository;
    private UserController $userController;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->userController = new UserController();
    }

    public function dashboard(): array
    {
        $this->userController->requireAdmin();
        
        $useCase = new GetDashboardStatsUseCase($this->userRepository);
        return $useCase->execute();
    }

    public function getCurrentUser(): ?array
    {
        return $this->userController->getCurrentUser();
    }
}