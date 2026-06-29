<?php
namespace App\Food\Domain\Entities;

use DateTime;

class Food
{
    private ?int $id;
    private ?int $categoryId;
    private string $name;
    private string $description;
    private float $price;
    private int $stock;
    private ?string $image;
    private int $preparationTime;
    private DateTime $createdAt;

    public function __construct(
        ?int $id,
        ?int $categoryId,
        string $name,
        string $description,
        float $price,
        int $stock,
        ?string $image = null,
        int $preparationTime = 15
    ) {
        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->image = $image;
        $this->preparationTime = $preparationTime;
        $this->createdAt = new DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCategoryId(): ?int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getImage(): ?string { return $this->image; }
    public function getPreparationTime(): int { return $this->preparationTime; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    // Business Methods
    public function isInStock(): bool { return $this->stock > 0; }
    
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'image' => $this->image,
            'preparation_time' => $this->preparationTime,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}