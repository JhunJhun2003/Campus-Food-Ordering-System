<?php
declare(strict_types=1);

namespace App\Food\Application\DTOs;

class UpdateFoodSizeRequest
{
    public function __construct(
        public readonly int $sizeId,
        public readonly ?string $sizeName = null,
        public readonly ?float $price = null,
        public readonly ?int $stock = null,
        public readonly ?bool $isDefault = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if ($this->sizeId <= 0) {
            $errors['size_id'] = 'Invalid size ID';
        }

        if ($this->sizeName !== null && strlen($this->sizeName) < 2) {
            $errors['size_name'] = 'Size name must be at least 2 characters';
        }

        if ($this->price !== null && $this->price <= 0) {
            $errors['price'] = 'Price must be greater than 0';
        }

        if ($this->stock !== null && $this->stock < 0) {
            $errors['stock'] = 'Stock cannot be negative';
        }

        return $errors;
    }
}