<?php
declare(strict_types=1);

namespace App\Food\Application\DTOs;

/**
 * Create Food Request DTO
 * Encapsulates food creation data
 */
class CreateFoodRequest
{
    private int $categoryId;
    private string $name;
    private string $description;
    private float $price;
    private int $stock;
    private int $preparationTime;
    private string $image;

    public function __construct(
        int $categoryId,
        string $name,
        string $description,
        float $price,
        int $stock,
        int $preparationTime,
        string $image
    ) {
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->preparationTime = $preparationTime;
        $this->image = $image;
    }

    // Getters
    public function getCategoryId(): int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getPreparationTime(): int { return $this->preparationTime; }
    public function getImage(): string { return $this->image; }

    public function validate(): array
    {
        $errors = [];

        if ($this->categoryId <= 0) {
            $errors['category_id'] = 'Category is required.';
        }

        if (empty($this->name)) {
            $errors['name'] = 'Food name is required.';
        }

        if ($this->price <= 0) {
            $errors['price'] = 'Price must be greater than 0.';
        }

        if ($this->stock < 0) {
            $errors['stock'] = 'Stock cannot be negative.';
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'preparation_time' => $this->preparationTime,
            'image' => $this->image
        ];
    }
}