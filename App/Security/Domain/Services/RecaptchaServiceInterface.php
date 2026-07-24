<?php
declare(strict_types=1);

namespace App\Security\Domain\Services;

interface RecaptchaServiceInterface
{
    public function verify(string $token, ?string $remoteIp = null): bool;
    public function getSiteKey(): string;
    public function isEnabled(): bool;
}