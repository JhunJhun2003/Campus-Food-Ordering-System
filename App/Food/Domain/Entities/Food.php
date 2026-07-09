<?php
namespace App\Food\Domain\Entities;

use DateTime;

class Food
{
    private ?int $id;
    private ?int $categoryId;
    private int $statusId;
    private string $name;
    private string $description;
    private float $price;
    private int $stock;
    private ?string $image;
    private int $preparationTime;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        ?int $categoryId,
        string $name,
        string $description,
        float $price,
        int $stock,
        ?string $image = null,
        int $preparationTime = 15,
        int $statusId = 1
    ) {
        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->statusId = $statusId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->image = $image;
        $this->preparationTime = $preparationTime;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // ============================================
    // GETTERS
    // ============================================

    public function getId(): ?int { return $this->id; }
    public function getCategoryId(): ?int { return $this->categoryId; }
    public function getStatusId(): int { return $this->statusId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getImage(): ?string { return $this->image; }
    public function getPreparationTime(): int { return $this->preparationTime; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // ============================================
    // SETTERS
    // ============================================

    public function setId(int $id): void { $this->id = $id; }
    public function setCategoryId(?int $categoryId): void { $this->categoryId = $categoryId; }
    public function setStatusId(int $statusId): void { $this->statusId = $statusId; }
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setPrice(float $price): void { $this->price = $price; }
    public function setStock(int $stock): void { $this->stock = $stock; }
    public function setImage(?string $image): void { $this->image = $image; }
    public function setPreparationTime(int $preparationTime): void { $this->preparationTime = $preparationTime; }
    
    // ✅ Add these methods
    public function setCreatedAt(DateTime $createdAt): void 
    { 
        $this->createdAt = $createdAt; 
    }
    
    public function setUpdatedAt(?DateTime $updatedAt): void 
    { 
        $this->updatedAt = $updatedAt; 
    }

    // ============================================
    // BUSINESS METHODS
    // ============================================

    public function isInStock(): bool { return $this->stock > 0; }
    
    public function isActive(): bool { return $this->statusId === 1; }
    
    public function isInactive(): bool { return $this->statusId === 2; }
    
    public function isOutOfStock(): bool { return $this->statusId === 3; }
    
    public function reduceStock(int $quantity): void 
    { 
        if ($this->stock < $quantity) {
            throw new \RuntimeException('Not enough stock available');
        }
        $this->stock -= $quantity; 
    }
    
    public function increaseStock(int $quantity): void 
    { 
        $this->stock += $quantity; 
    }

    // ============================================
    // CONVERSION
    // ============================================

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'status_id' => $this->statusId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'image' => $this->image,
            'preparation_time' => $this->preparationTime,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}