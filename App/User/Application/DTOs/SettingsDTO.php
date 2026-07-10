<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class SettingsDTO
{
    public function __construct(
        public readonly string $siteName,
        public readonly string $siteEmail,
        public readonly string $sitePhone,
        public readonly string $timezone,
        public readonly int $preparationTime,
        public readonly int $cancellationTime,
        public readonly string $currency,
        public readonly bool $maintenanceMode,
        public readonly string $notificationEmail = 'orders@foodie.com'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            siteName: $data['site_name'] ?? 'FOODIE',
            siteEmail: $data['site_email'] ?? 'admin@foodie.com',
            sitePhone: $data['site_phone'] ?? '+1234567890',
            timezone: $data['timezone'] ?? 'Asia/Manila',
            preparationTime: (int) ($data['preparation_time'] ?? 15),
            cancellationTime: (int) ($data['cancellation_time'] ?? 5),
            currency: $data['currency'] ?? 'USD',
            maintenanceMode: (bool) ($data['maintenance_mode'] ?? 0),
            notificationEmail: $data['notification_email'] ?? 'orders@foodie.com'
        );
    }

    public function toArray(): array
    {
        return [
            'site_name' => $this->siteName,
            'site_email' => $this->siteEmail,
            'site_phone' => $this->sitePhone,
            'timezone' => $this->timezone,
            'preparation_time' => $this->preparationTime,
            'cancellation_time' => $this->cancellationTime,
            'currency' => $this->currency,
            'maintenance_mode' => $this->maintenanceMode ? 1 : 0,
            'notification_email' => $this->notificationEmail,
        ];
    }
}