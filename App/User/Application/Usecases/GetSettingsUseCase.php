<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;

class GetSettingsUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(): array
    {
        return $this->userRepository->getAllSettings();
    }

    public function getByGroup(string $group): array
    {
        return $this->userRepository->getSettingsByGroup($group);
    }

    public function getByKey(string $key): ?string
    {
        return $this->userRepository->getSetting($key);
    }
}