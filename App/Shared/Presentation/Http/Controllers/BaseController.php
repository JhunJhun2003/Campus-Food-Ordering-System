<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Controllers;

use App\Shared\Presentation\Http\Traits\AuthorizesRequests;

abstract class BaseController
{
    use AuthorizesRequests;

    protected ?int $currentUserId = null;
    protected ?string $currentUserRole = null;

    public function __construct()
    {
        $this->currentUserId = $this->getCurrentUserId() ?: null;
        $this->currentUserRole = $this->getUserRole();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return $this->currentUserId !== null && $this->currentUserId > 0;
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool
    {
        return $this->currentUserRole === 'admin';
    }

    /**
     * Check if user is staff
     */
    protected function isStaff(): bool
    {
        return $this->currentUserRole === 'staff';
    }

    /**
     * Check if user is customer
     */
    protected function isCustomer(): bool
    {
        return $this->currentUserRole === 'customer' || $this->currentUserRole === 'user';
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * Get current user role
     */
    protected function getUserRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get current user data
     */
    protected function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        try {
            $db = \Inc\Database::getConnection();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = :user_id
            ");
            $stmt->execute([':user_id' => $this->currentUserId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                $user['id'] = (int) $user['id'];
                $user['role_id'] = isset($user['role_id']) ? (int) $user['role_id'] : null;
                return $user;
            }
        } catch (\Exception $e) {
            error_log('Error getting current user: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Require authentication - throws exception if not authenticated
     */
    protected function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {
            throw new \RuntimeException('Authentication required', 401);
        }
    }

    /**
     * Require admin access - returns user data if authorized
     */
    protected function requireAdmin(): ?array
    {
        $this->requireAuthentication();
        
        if (!$this->isAdmin()) {
            throw new \RuntimeException('Admin access required', 403);
        }

        return $this->getCurrentUser();
    }

    /**
     * Require staff access - returns user data if authorized
     */
    protected function requireStaff(): ?array
    {
        $this->requireAuthentication();
        
        if (!$this->isStaff() && !$this->isAdmin()) {
            throw new \RuntimeException('Staff access required', 403);
        }

        return $this->getCurrentUser();
    }

    /**
     * Require customer access - returns user data if authorized
     */
    protected function requireCustomer(): ?array
    {
        $this->requireAuthentication();
        
        if (!$this->isCustomer()) {
            throw new \RuntimeException('Customer access required', 403);
        }

        return $this->getCurrentUser();
    }

    /**
     * Require staff or customer access - returns user data if authorized
     */
    protected function requireStaffOrCustomer(): ?array
    {
        $this->requireAuthentication();
        
        if (!$this->isStaff() && !$this->isAdmin() && !$this->isCustomer()) {
            throw new \RuntimeException('Access denied', 403);
        }

        return $this->getCurrentUser();
    }

    /**
     * JSON response helper
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * JSON error response
     */
    protected function jsonError(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = ['success' => false, 'error' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        $this->jsonResponse($response, $statusCode);
    }

    /**
     * JSON success response
     */
    protected function jsonSuccess(array $data = [], string $message = 'Success', ?array $extra = null): void
    {
        $response = ['success' => true, 'message' => $message, 'data' => $data];
        if ($extra !== null) {
            $response = array_merge($response, $extra);
        }
        $this->jsonResponse($response);
    }

    /**
     * Redirect with error message
     */
    protected function redirectWithError(string $url, string $errorMessage): void
    {
        $_SESSION['error'] = $errorMessage;
        if (headers_sent()) {
            echo '<script>window.location.href="' . $url . '";</script>';
            exit();
        }
        header('Location: ' . $url);
        exit();
    }

    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess(string $url, string $successMessage): void
    {
        $_SESSION['success'] = $successMessage;
        if (headers_sent()) {
            echo '<script>window.location.href="' . $url . '";</script>';
            exit();
        }
        header('Location: ' . $url);
        exit();
    }

    /**
     * Redirect to previous page or default
     */
    protected function redirectBack(string $default = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        if (headers_sent()) {
            echo '<script>window.location.href="' . $referer . '";</script>';
            exit();
        }
        header('Location: ' . $referer);
        exit();
    }

    /**
     * Get POST data as array
     */
    protected function getPostData(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return $_POST;
        }
        
        $data = json_decode($input, true);
        return is_array($data) ? $data : $_POST;
    }

    /**
     * Get query parameters
     */
    protected function getQueryParams(): array
    {
        return $_GET;
    }

    /**
     * Validate required fields
     */
    protected function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = "The {$field} field is required";
            }
        }
        return $errors;
    }
}