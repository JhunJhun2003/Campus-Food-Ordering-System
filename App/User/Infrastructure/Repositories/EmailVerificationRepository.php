<?php
namespace App\User\Infrastructure\Repositories;

use Inc\Database;
use PDO;

class EmailVerificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function saveVerification(int $userId, string $email, string $code, int $expiresInMinutes = 10): bool
    {
        // Delete old verifications for this user
        $this->deleteVerification($userId);
        
        $sql = "INSERT INTO email_verifications (user_id, email, verification_code, expires_at) 
                VALUES (:user_id, :email, :verification_code, DATE_ADD(NOW(), INTERVAL :expires MINUTE))";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':email' => $email,
            ':verification_code' => $code,
            ':expires' => $expiresInMinutes
        ]);
    }

    public function getVerification(string $email, string $code): ?array
    {
        $sql = "SELECT * FROM email_verifications 
                WHERE email = :email 
                AND verification_code = :code 
                AND is_verified = 0 
                AND expires_at > NOW()
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':code' => $code
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function markAsVerified(int $verificationId, int $userId): bool
    {
        $this->db->beginTransaction();
        
        try {
            // Mark verification as verified
            $sql1 = "UPDATE email_verifications SET is_verified = 1 WHERE id = :id";
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute([':id' => $verificationId]);
            
            // Update user as verified
            $sql2 = "UPDATE users SET is_verified = 1, email_verified_at = NOW() WHERE id = :user_id";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([':user_id' => $userId]);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteVerification(int $userId): bool
    {
        $sql = "DELETE FROM email_verifications WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function isEmailVerified(string $email): bool
    {
        $sql = "SELECT is_verified FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['is_verified'] == 1;
    }
}