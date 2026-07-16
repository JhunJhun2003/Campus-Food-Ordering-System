<?php
namespace App\Food\Domain\Entities;

use DateTime;

class FoodSize
{
    private ?int $id;
    private int $foodId;
    private string $sizeName;
    private float $price;
    private int $stock;
    private bool $isDefault;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        int $foodId,
        string $sizeName,
        float $price,
        int $stock = 0,
        bool $isDefault = false
    ) {
        $this->id = $id;
        $this->foodId = $foodId;
        $this->sizeName = $sizeName;
        $this->price = $price;
        $this->stock = $stock;
        $this->isDefault = $isDefault;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // ============================================
    // GETTERS
    // ============================================

    public function getId(): ?int { return $this->id; }
    public function getFoodId(): int { return $this->foodId; }
    public function getSizeName(): string { return $this->sizeName; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function isDefault(): bool { return $this->isDefault; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // ============================================
    // SETTERS
    // ============================================

    public function setStock(int $stock): void 
    { 
        $this->stock = $stock; 
        $this->updatedAt = new DateTime();
    }
    
    public function setPrice(float $price): void 
    { 
        $this->price = $price; 
        $this->updatedAt = new DateTime();
    }
    
    public function setSizeName(string $sizeName): void 
    { 
        $this->sizeName = $sizeName; 
        $this->updatedAt = new DateTime();
    }
    
    public function setIsDefault(bool $isDefault): void 
    { 
        $this->isDefault = $isDefault; 
        $this->updatedAt = new DateTime();
    }

    // ============================================
    // BUSINESS METHODS
    // ============================================

    public function isInStock(): bool { return $this->stock > 0; }
    
    public function reduceStock(int $quantity): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }
        $this->stock -= $quantity;
        $this->updatedAt = new DateTime();
        return true;
    }

    public function increaseStock(int $quantity): void
    {
        $this->stock += $quantity;
        $this->updatedAt = new DateTime();
    }

    // ============================================
    // CONVERSION
    // ============================================

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'food_id' => $this->foodId,
            'size_name' => $this->sizeName,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}