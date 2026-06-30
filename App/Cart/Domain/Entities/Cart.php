<?php
namespace App\Cart\Domain\Entities;

use DateTime;

class Cart
{
    private ?int $id;
    private int $userId;
    private DateTime $createdAt;
    private array $items;

    public function __construct(?int $id, int $userId, array $items = [])
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->items = $items;
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getItems(): array { return $this->items; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    public function addItem(CartItem $item): void
    {
        foreach ($this->items as $existingItem) {
            if ($existingItem->getFoodId() === $item->getFoodId()) {
                $existingItem->increaseQuantity($item->getQuantity());
                return;
            }
        }
        $this->items[] = $item;
    }

    public function removeItem(int $foodId): void
    {
        $this->items = array_filter($this->items, function($item) use ($foodId) {
            return $item->getFoodId() !== $foodId;
        });
    }

    public function updateQuantity(int $foodId, int $quantity): void
    {
        foreach ($this->items as $item) {
            if ($item->getFoodId() === $foodId) {
                if ($quantity <= 0) {
                    $this->removeItem($foodId);
                } else {
                    $item->setQuantity($quantity);
                }
                return;
            }
        }
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }
        return $total;
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'total' => $this->getTotal(),
            'item_count' => $this->getItemCount(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}