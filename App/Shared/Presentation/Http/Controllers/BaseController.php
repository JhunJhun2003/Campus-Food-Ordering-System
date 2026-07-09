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
     * Require authentication - throws exception if not authenticated
     */
    protected function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {
            throw new \RuntimeException('Authentication required', 401);
        }
    }

    /**
     * Require admin access - throws exception if not admin
     */
    protected function requireAdmin(): void
    {
        $this->requireAuthentication();
        if (!$this->isAdmin()) {
            throw new \RuntimeException('Admin access required', 403);
        }
    }

    /**
     * Require staff access - throws exception if not staff or admin
     */
    protected function requireStaff(): void
    {
        $this->requireAuthentication();
        if (!$this->isStaff() && !$this->isAdmin()) {
            throw new \RuntimeException('Staff access required', 403);
        }
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
    protected function jsonError(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['success' => false, 'error' => $message], $statusCode);
    }

    /**
     * JSON success response
     */
    protected function jsonSuccess(array $data = [], string $message = 'Success'): void
    {
        $this->jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
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
}