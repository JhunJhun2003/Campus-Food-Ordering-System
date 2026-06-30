<?php
namespace App\User\Presentation\Http\Controllers;

use App\User\Application\Usecases\GetDashboardStatsUseCase;
use App\User\Application\Usecases\GetReportsUseCase;
use App\User\Application\Usecases\GetSettingsUseCase;
use App\User\Application\Usecases\UpdateSettingsUseCase;
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

    // Dashboard
    public function dashboard(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetDashboardStatsUseCase($this->userRepository);
        return $useCase->execute();
    }

    // Reports
    public function reports(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetReportsUseCase($this->userRepository);
        return $useCase->execute();
    }

    // ============================================
    // SETTINGS METHODS (ADD THESE)
    // ============================================

    /**
     * Get all settings
     */
    public function getSettings(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetSettingsUseCase($this->userRepository);
        return $useCase->execute();
    }

    /**
     * Update settings from POST request
     */
    public function updateSettingsFromRequest(): array
    {
        $this->userController->requireAdmin();
        
        // Filter only setting fields
        $postData = array_filter($_POST, function($key) {
            return strpos($key, 'setting_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        // Convert to key-value pairs
        $settingsToUpdate = [];
        foreach ($postData as $key => $value) {
            $cleanKey = str_replace('setting_', '', $key);
            $settingsToUpdate[$cleanKey] = trim($value);
        }
        
        $useCase = new UpdateSettingsUseCase($this->userRepository);
        return $useCase->execute($settingsToUpdate);
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        return $this->userController->getCurrentUser();
    }
}