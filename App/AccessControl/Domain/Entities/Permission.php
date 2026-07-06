<?php

namespace App\AccessControl\Domain\Entities;

class Permission
{
    private ?int $id;
    private string $name;
    private string $displayName;
    private string $module;
    private string $createdAt;

    public function __construct(
        ?int $id,
        string $name,
        string $displayName,
        string $module,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->displayName = $displayName;
        $this->module = $module;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getModule(): string { return $this->module; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // Business logic
    public function belongsToModule(string $module): bool
    {
        return $this->module === $module;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->displayName,
            'module' => $this->module,
            'created_at' => $this->createdAt
        ];
    }
}