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

    // ============================================
    // USER CRUD METHODS
    // ============================================

    /**
     * Save user (insert or update)
     */
    public function save(User $user): void
    {
        if ($user->getId()->isEmpty()) {
            // Insert new user
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
            // Update existing user
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

    /**
     * Find user by ID
     */
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

    /**
     * Find user by email
     */
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

    /**
     * Find all users
     */
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

    /**
     * Delete user by ID
     */
    public function delete(UserId $id): void
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id->getValue()]);
    }

    /**
     * Check if user exists by email
     */
    public function exists(Email $email): bool
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email->getValue()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['count'] > 0;
    }

    // ============================================
    // ROLE METHODS
    // ============================================

    /**
     * Get role ID by role name
     */
    public function getRoleId(string $roleName): int
    {
        $sql = "SELECT id FROM user_roles WHERE role_name = :role_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_name' => $roleName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['id'] ?? 3; // Default to 'user' (id=3)
    }

    // ============================================
    // ADMIN DASHBOARD METHODS
    // ============================================

    /**
     * Get total number of users
     */
    public function getTotalUsers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get total number of foods
     */
    public function getTotalFoods(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM foods");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get total number of orders
     */
    public function getTotalOrders(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get number of pending orders (status_id = 1)
     */
    public function getPendingOrders(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status_id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        $sql = "SELECT 
                    o.id, 
                    o.total_amount, 
                    o.order_date,
                    u.name as customer_name,
                    os.status_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN order_statuses os ON o.status_id = os.id
                ORDER BY o.order_date DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Hydrate database data to User entity
     */
    private function hydrate(array $data): User
    {
        return new User(
            new UserId((int) $data['id']),
            (int) $data['role_id'],
            $data['role_name'],
            $data['name'],
            new Email($data['email']),
            new Password($data['password'], true), // isHashed = true
            $data['phone'] ?? null,
            $data['address'] ?? null
        );
    }
}