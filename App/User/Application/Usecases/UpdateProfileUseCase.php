<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;

class UpdateProfileUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(int $userId, array $data): array
    {
        try {
            $user = $this->userRepository->findById(new UserId($userId));
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Update fields
            if (isset($data['name']) && !empty($data['name'])) {
                $user->changeName($data['name']);
            }

            if (isset($data['email']) && !empty($data['email'])) {
                // Check if email already exists for another user
                $email = new Email($data['email']);
                $existingUser = $this->userRepository->findByEmail($email);
                if ($existingUser && $existingUser->getId()->getValue() != $userId) {
                    return ['success' => false, 'message' => 'Email already registered to another account.'];
                }
                $user->changeEmail($email);
            }

            if (isset($data['phone'])) {
                $user->changePhone($data['phone']);
            }

            if (isset($data['address'])) {
                $user->changeAddress($data['address']);
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $password = new Password($data['password']);
                $user->changePassword($password);
            }

            // Save changes
            $this->userRepository->save($user);

            return [
                'success' => true,
                'message' => 'Profile updated successfully!'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}