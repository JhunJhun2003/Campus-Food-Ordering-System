<?php

namespace App\AccessControl\Domain\Entities;

class Role
{
    private ?int $id;
    private string $name;
    private string $createdAt;
    private string $updatedAt;
    private array $permissions;

    public function __construct(
        ?int $id,
        string $name,
        string $createdAt = '',
        string $updatedAt = '',
        array $permissions = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->permissions = $permissions;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    public function getPermissions(): array { return $this->permissions; }

    // Business logic methods
    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission['name'] === $permissionName) {
                return true;
            }
        }
        return false;
    }

    public function addPermission(array $permission): void
    {
        // Check if permission already exists
        foreach ($this->permissions as $existing) {
            if ($existing['id'] === $permission['id']) {
                return;
            }
        }
        $this->permissions[] = $permission;
    }

    public function removePermission(int $permissionId): void
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p['id'] !== $permissionId
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'permissions' => $this->permissions,
            'permission_count' => count($this->permissions)
        ];
    }
}