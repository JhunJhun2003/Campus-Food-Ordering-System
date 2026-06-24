<?php
namespace App\User\Infrastructure\repositories;

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

    public function save(User $user): void
    {
        if ($user->getId()->isEmpty()) {
            // Insert new user
            $sql = "INSERT INTO users (name, email, password, phone, role) 
                    VALUES (:name, :email, :password, :phone, :role)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':role' => $user->getRole()
            ]);
        } else {
            // Update existing user
            $sql = "UPDATE users SET 
                    name = :name, 
                    email = :email, 
                    password = :password, 
                    phone = :phone, 
                    role = :role 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':role' => $user->getRole(),
                ':id' => $user->getId()->getValue()
            ]);
        }
    }

    public function findById(UserId $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id->getValue()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email->getValue()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
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
            $data['name'],
            new Email($data['email']),
            new Password($data['password'], true), // isHashed = true
            $data['phone'],
            $data['role']
        );
    }
}