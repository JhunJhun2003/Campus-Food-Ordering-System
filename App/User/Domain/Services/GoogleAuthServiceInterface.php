<?php
declare(strict_types=1);

namespace App\User\Domain\Services;

interface GoogleAuthServiceInterface
{
    /**
     * Get Google OAuth URL for redirect
     */
    public function getAuthUrl(): string;

    /**
     * Authenticate with Google and get user profile
     */
    public function authenticate(string $code): ?array;

    /**
     * Get Google client
     */
    public function getClient(): \Google\Client;
}