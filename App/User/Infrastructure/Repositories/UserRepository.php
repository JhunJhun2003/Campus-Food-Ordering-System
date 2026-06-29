<?php
namespace App\User\Infrastructure\Repositories;

use App\User\Domain\Entities\User;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use Inc\Database;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // NEW: Get role ID by role name
    public function getRoleId(string $roleName): int
    {
        $sql = "SELECT id FROM user_roles WHERE role_name = :role_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_name' => $roleName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['id'] ?? 3; // Default to 'user' (id=3)
    }

    public function save(User $user): void
    {
        if ($user->getId()->isEmpty()) {
            $sql = "INSERT INTO users (role_id, name, email, password, phone, address) 
                    VALUES (:role_id, :name, :email, :password, :phone, :address)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':role_id' => $user->getRoleId(),
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':address' => $user->getAddress()
            ]);
        } else {
            $sql = "UPDATE users SET 
                    role_id = :role_id,
                    name = :name, 
                    email = :email, 
                    password = :password, 
                    phone = :phone, 
                    address = :address
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':role_id' => $user->getRoleId(),
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':address' => $user->getAddress(),
                ':id' => $user->getId()->getValue()
            ]);
        }
    }

    public function findById(UserId $id): ?User
    {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id->getValue()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                WHERE u.email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email->getValue()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                ORDER BY u.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function delete(UserId $id): void
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id->getValue()]);
    }

    public function exists(Email $email): bool
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email->getValue()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['count'] > 0;
    }

    private function hydrate(array $data): User
    {
        return new User(
            new UserId((int) $data['id']),
            (int) $data['role_id'],
            $data['role_name'],
            $data['name'],
            new Email($data['email']),
            new Password($data['password'], true),
            $data['phone'] ?? null,
            $data['address'] ?? null
        );
    }
}