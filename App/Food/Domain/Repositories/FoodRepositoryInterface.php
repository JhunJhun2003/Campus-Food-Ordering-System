<?php
namespace App\Food\Domain\Repositories;

use App\Food\Domain\Entities\Food;

interface FoodRepositoryInterface
{
    public function save(Food $food): void;
    public function findById(int $id): ?Food;
    public function findAll(): array;
    public function findByCategory(int $categoryId): array;
    public function findActive(): array;
    public function delete(int $id): void;
    public function updateStock(int $id, int $quantity): void;
}