<?php
declare(strict_types=1);

namespace App\User\Infrastructure\Repositories;

use App\User\Domain\Entities\PasswordReset;
use App\User\Domain\Repositories\PasswordResetRepositoryInterface;
use Inc\Database;
use PDO;
use DateTime;

class PasswordResetRepository implements PasswordResetRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(PasswordReset $passwordReset): int
    {
        // Delete any existing reset records for this user
        $this->deleteByUserId($passwordReset->getUserId());

        $sql = "INSERT INTO password_resets (user_id, email, code, expires_at, created_at) 
                VALUES (:user_id, :email, :code, :expires_at, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $passwordReset->getUserId(),
            ':email' => $passwordReset->getEmail(),
            ':code' => $passwordReset->getCode(),
            ':expires_at' => $passwordReset->getExpiresAt()->format('Y-m-d H:i:s')
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function findByCode(string $code): ?PasswordReset
    {
        $sql = "SELECT * FROM password_resets WHERE code = :code ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    public function findByUserId(int $userId): ?PasswordReset
    {
        $sql = "SELECT * FROM password_resets WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    public function deleteByUserId(int $userId): bool
    {
        $sql = "DELETE FROM password_resets WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function deleteExpired(): int
    {
        $sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR is_used = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function markAsUsed(int $id): bool
    {
        $sql = "UPDATE password_resets SET is_used = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    private function hydrate(array $data): PasswordReset
    {
        return new PasswordReset(
            (int) $data['id'],
            (int) $data['user_id'],
            $data['email'],
            $data['code'],
            (bool) ($data['is_used'] ?? false),
            new DateTime($data['expires_at']),
            new DateTime($data['created_at']),
            $data['updated_at'] ? new DateTime($data['updated_at']) : null
        );
    }
}