<?php
namespace App\Cart\Domain\Repositories;


use App\Cart\Domain\Entities\CartItem;
use App\Cart\Domain\Entities\Cart;

interface CartRepositoryInterface
{
    public function findByUserId(int $userId): ?Cart;
    public function save(Cart $cart): void;
    public function addItem(int $userId, CartItem $item): void;
    public function removeItem(int $userId, int $foodId): void;
    public function updateItemQuantity(int $userId, int $foodId, int $quantity): void;
    public function clear(int $userId): void;
    public function getTotal(int $userId): float;
    public function getItemCount(int $userId): int;
}