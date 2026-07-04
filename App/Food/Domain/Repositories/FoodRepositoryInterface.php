<?php
namespace App\Food\Domain\Repositories;

use App\Food\Domain\Entities\Food;

interface FoodRepositoryInterface
{
    // Basic CRUD
    public function save(Food $food): void;
    public function findById(int $id): ?Food;
    public function findAll(): array;
    public function findByCategory(int $categoryId): array;
    public function findActive(): array;
    public function delete(int $id): void;
    public function updateStock(int $id, int $quantity): void;
    
    // Admin methods
    public function getFoodForEdit(int $id): ?array;
    public function createFood(array $data): int;
    public function updateFood(int $id, array $data): bool;
    public function deleteFood(int $id): bool;
    
    // Stock management
    public function reduceStock(int $foodId, int $quantity): bool;
    public function reduceStockForItems(array $items): bool;
    public function getStock(int $foodId): int;
}