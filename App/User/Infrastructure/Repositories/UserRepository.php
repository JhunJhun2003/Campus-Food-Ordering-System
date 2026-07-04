<?php
namespace App\User\Infrastructure\Repositories;

use App\User\Domain\Entities\User;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use Inc\Database;
use PDO;
use DateTime;

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

    public function save(User $user): int
    {
        if ($user->getId()->isEmpty()) {
            $sql = "INSERT INTO users (role_id, name, email, password, phone, address, is_verified, verification_code, verification_expires_at) 
                    VALUES (:role_id, :name, :email, :password, :phone, :address, :is_verified, :verification_code, :verification_expires_at)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':role_id' => $user->getRoleId(),
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':address' => $user->getAddress(),
                ':is_verified' => $user->isVerified() ? 1 : 0,
                ':verification_code' => $user->getVerificationCode(),
                ':verification_expires_at' => $user->getVerificationExpiresAt() ? $user->getVerificationExpiresAt()->format('Y-m-d H:i:s') : null
            ]);
            
            return (int) $this->db->lastInsertId();
        } else {
            $sql = "UPDATE users SET 
                    role_id = :role_id,
                    name = :name, 
                    email = :email, 
                    password = :password, 
                    phone = :phone, 
                    address = :address,
                    is_verified = :is_verified,
                    verification_code = :verification_code,
                    verification_expires_at = :verification_expires_at,
                    email_verified_at = :email_verified_at
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':role_id' => $user->getRoleId(),
                ':name' => $user->getName(),
                ':email' => $user->getEmail()->getValue(),
                ':password' => $user->getPassword()->getValue(),
                ':phone' => $user->getPhone(),
                ':address' => $user->getAddress(),
                ':is_verified' => $user->isVerified() ? 1 : 0,
                ':verification_code' => $user->getVerificationCode(),
                ':verification_expires_at' => $user->getVerificationExpiresAt() ? $user->getVerificationExpiresAt()->format('Y-m-d H:i:s') : null,
                ':email_verified_at' => $user->getEmailVerifiedAt() ? $user->getEmailVerifiedAt()->format('Y-m-d H:i:s') : null,
                ':id' => $user->getId()->getValue()
            ]);
            
            return $user->getId()->getValue();
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

    // ============================================
    // ROLE METHODS
    // ============================================

    public function getRoleId(string $roleName): int
    {
        $sql = "SELECT id FROM user_roles WHERE role_name = :role_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_name' => $roleName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'] ?? 3;
    }

    // ============================================
    // ADMIN USER MANAGEMENT METHODS
    // ============================================

    public function getAllRoles(): array
    {
        $sql = "SELECT * FROM user_roles ORDER BY id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function emailExists(string $email): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function createUser(string $name, string $email, string $password, string $phone, int $roleId): int
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (role_id, name, email, password, phone, is_verified) 
                VALUES (:role_id, :name, :email, :password, :phone, 0)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':role_id' => $roleId,
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':phone' => $phone
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteUser(int $userId): bool
    {
        if ($userId === 1) {
            return false;
        }
        
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }

    public function updateUser(int $userId, array $data): bool
    {
        $fields = [];
        $params = [':id' => $userId];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }
        if (isset($data['role_id'])) {
            $fields[] = "role_id = :role_id";
            $params[':role_id'] = $data['role_id'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (isset($data['is_verified'])) {
            $fields[] = "is_verified = :is_verified";
            $params[':is_verified'] = $data['is_verified'] ? 1 : 0;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getUserForEdit(int $userId): ?array
    {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function emailExistsExcluding(string $email, int $userId): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email, ':user_id' => $userId]);
        return $stmt->fetch() !== false;
    }

    // ============================================
    // ADMIN DASHBOARD METHODS
    // ============================================

    public function getTotalUsers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getTotalFoods(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM foods");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getTotalOrders(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getPendingOrders(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status_id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

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
    // REPORTS METHODS
    // ============================================

    public function getTotalRevenue(): float
    {
        $stmt = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE status_id = 5");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total'] ?? 0);
    }

    public function getCompletedOrders(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status_id = 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getMonthlyRevenue(int $months = 6): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    SUM(total_amount) as revenue,
                    COUNT(*) as order_count
                FROM orders 
                WHERE status_id = 5
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT :months";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        
        foreach (array_reverse($results) as $row) {
            $monthNum = (int) substr($row['month'], 5);
            $chartData[] = [
                'month' => $monthNames[$monthNum - 1] ?? $row['month'],
                'revenue' => (float) $row['revenue'],
                'orders' => (int) $row['order_count']
            ];
        }
        
        return $chartData;
    }

    public function getOrderStats(): array
    {
        $sql = "SELECT 
                    status_id,
                    COUNT(*) as count,
                    SUM(total_amount) as total
                FROM orders 
                GROUP BY status_id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============================================
    // SETTINGS METHODS
    // ============================================

    public function getAllSettings(): array
    {
        $sql = "SELECT setting_key, setting_value, setting_group FROM settings ORDER BY setting_group, setting_key";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    public function getSettingsByGroup(string $group): array
    {
        $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_group = :group ORDER BY setting_key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group' => $group]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    public function getSetting(string $key): ?string
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['setting_value'] ?? null;
    }

    public function updateSetting(string $key, string $value): bool
    {
        $sql = "UPDATE settings SET setting_value = :value WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':key' => $key,
            ':value' => $value
        ]);
    }

    public function updateSettings(array $settings): array
    {
        $success = [];
        $failed = [];
        
        foreach ($settings as $key => $value) {
            if ($this->updateSetting($key, $value)) {
                $success[] = $key;
            } else {
                $failed[] = $key;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed
        ];
    }

    // ============================================
    // HELPER METHODS
    // ============================================

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
            $data['address'] ?? null,
            (bool) ($data['is_verified'] ?? false),
            $data['verification_code'] ?? null,
            !empty($data['verification_expires_at']) ? new DateTime($data['verification_expires_at']) : null,
            !empty($data['email_verified_at']) ? new DateTime($data['email_verified_at']) : null
        );
    }
}