<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class SettingsDTO
{
    public function __construct(
        public readonly string $siteName,
        public readonly string $siteEmail,
        public readonly string $sitePhone,
        public readonly int $preparationTime,
        public readonly string $currency,
        public readonly bool $maintenanceMode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            siteName: $data['site_name'] ?? 'FOODIE',
            siteEmail: $data['site_email'] ?? 'admin@foodie.com',
            sitePhone: $data['site_phone'] ?? '+1234567890',
            preparationTime: (int) ($data['preparation_time'] ?? 15),
            currency: $data['currency'] ?? 'USD',
            maintenanceMode: (bool) ($data['maintenance_mode'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'site_name' => $this->siteName,
            'site_email' => $this->siteEmail,
            'site_phone' => $this->sitePhone,
            'preparation_time' => $this->preparationTime,
            'currency' => $this->currency,
            'maintenance_mode' => $this->maintenanceMode ? 1 : 0
        ];
    }
}