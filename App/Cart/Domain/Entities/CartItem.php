<?php
namespace App\Cart\Domain\Entities;

class CartItem
{
    private ?int $id;
    private int $foodId;
    private string $foodName;
    private float $price;
    private int $quantity;
    private ?string $image;
    private ?int $foodSizeId;
    private ?string $sizeName;

    public function __construct(
        ?int $id,
        int $foodId,
        string $foodName,
        float $price,
        int $quantity = 1,
        ?string $image = null,
        ?int $foodSizeId = null,
        ?string $sizeName = null
    ) {
        $this->id = $id;
        $this->foodId = $foodId;
        $this->foodName = $foodName;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->image = $image;
        $this->foodSizeId = $foodSizeId;
        $this->sizeName = $sizeName;
    }

    public function getId(): ?int { return $this->id; }
    public function getFoodId(): int { return $this->foodId; }
    public function getFoodName(): string { return $this->foodName; }
    public function getPrice(): float { return $this->price; }
    public function getQuantity(): int { return $this->quantity; }
    public function getImage(): ?string { return $this->image; }
    public function getFoodSizeId(): ?int { return $this->foodSizeId; }
    public function getSizeName(): ?string { return $this->sizeName; }
    public function getSubtotal(): float { return $this->price * $this->quantity; }

    public function increaseQuantity(int $amount = 1): void
    {
        $this->quantity += $amount;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'food_id' => $this->foodId,
            'food_name' => $this->foodName,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'subtotal' => $this->getSubtotal(),
            'image' => $this->image,
            'food_size_id' => $this->foodSizeId,
            'size_name' => $this->sizeName
        ];
    }
}