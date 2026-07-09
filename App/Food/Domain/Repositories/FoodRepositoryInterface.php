<?php
declare(strict_types=1);

namespace App\Food\Domain\Repositories;

use App\Food\Domain\Entities\Food;

interface FoodRepositoryInterface
{
    // ============================================
    // BASIC CRUD
    // ============================================

    public function save(Food $food): int;
    public function findById(int $id): ?Food;
    public function findAll(): array;
    public function findByCategory(int $categoryId): array;
    public function findActive(): array;
    public function delete(int $id): void;
    public function updateStock(int $id, int $quantity): void;
    
    // ============================================
    // ADMIN METHODS
    // ============================================

    public function getFoodForEdit(int $id): ?array;
    public function createFood(array $data): int;
    public function updateFood(int $id, array $data): bool;
    public function deleteFood(int $id): bool;
    
    // ============================================
    // STOCK MANAGEMENT
    // ============================================

    public function reduceStock(int $foodId, int $quantity): bool;
    public function reduceStockForItems(array $items): bool;
    public function getStock(int $foodId): int;
    
    // ============================================
    // STATISTICS - ADD THIS
    // ============================================

    /**
     * Get total number of food items
     */
    public function count(): int;  // ✅ ADD THIS

    /**
     * Get count by category
     */
    public function countByCategory(int $categoryId): int;  // ✅ ADD THIS (optional)

    /**
     * Get active food count
     */
    public function countActive(): int;  // ✅ ADD THIS (optional)
}