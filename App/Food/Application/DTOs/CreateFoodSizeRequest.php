<?php
declare(strict_types=1);

namespace App\Food\Application\DTOs;

class CreateFoodSizeRequest
{
    public function __construct(
        public readonly int $foodId,
        public readonly string $sizeName,
        public readonly float $price,
        public readonly int $stock = 0,
        public readonly bool $isDefault = false
    ) {}

    public function validate(): array
    {
        $errors = [];

        if ($this->foodId <= 0) {
            $errors['food_id'] = 'Invalid food ID';
        }

        if (empty($this->sizeName) || strlen($this->sizeName) < 2) {
            $errors['size_name'] = 'Size name must be at least 2 characters';
        }

        if ($this->price <= 0) {
            $errors['price'] = 'Price must be greater than 0';
        }

        if ($this->stock < 0) {
            $errors['stock'] = 'Stock cannot be negative';
        }

        return $errors;
    }
}