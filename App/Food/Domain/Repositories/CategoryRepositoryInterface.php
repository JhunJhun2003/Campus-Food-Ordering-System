<?php
declare(strict_types=1);

namespace App\Food\Domain\Repositories;

interface CategoryRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?array;
    public function findByName(string $name): ?array;
}