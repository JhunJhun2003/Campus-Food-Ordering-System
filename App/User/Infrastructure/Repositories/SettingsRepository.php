<?php
declare(strict_types=1);

namespace App\User\Infrastructure\Repositories;

use Inc\Database;
use PDO;

class SettingsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get a single setting by key
     */
    public function getSetting(string $key): ?string
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        return $stmt->fetchColumn() ?: null;
    }

    /**
     * Get all settings as key-value array
     */
    public function getAllSettings(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Get multiple settings by group
     */
    public function getSettingsByGroup(string $group): array
    {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = :group");
        $stmt->execute([':group' => $group]);
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Update a setting
     */
    public function updateSetting(string $key, string $value): bool
    {
        $stmt = $this->db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        return $stmt->execute([':key' => $key, ':value' => $value]);
    }

    /**
     * Update multiple settings
     */
    public function updateSettings(array $settings): bool
    {
        $this->db->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
                $stmt->execute([':key' => $key, ':value' => $value]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}