<?php
namespace App\Cart\Infrastructure\Repositories;

use App\Cart\Domain\Entities\Cart;
use App\Cart\Domain\Entities\CartItem;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use Inc\Database;
use PDO;

class CartRepository implements CartRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUserId(int $userId): ?Cart
    {
        // Get cart
        $sql = "SELECT id FROM carts WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $cartData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cartData) {
            return null;
        }

        // Get cart items with food details
        $sql = "SELECT 
                    ci.id,
                    ci.food_id,
                    ci.quantity,
                    f.name as food_name,
                    f.price,
                    f.image
                FROM cart_items ci
                JOIN foods f ON ci.food_id = f.id
                WHERE ci.cart_id = :cart_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cart_id' => $cartData['id']]);
        $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemsData as $data) {
            $items[] = new CartItem(
                (int) $data['id'],
                (int) $data['food_id'],
                $data['food_name'],
                (float) $data['price'],
                (int) $data['quantity'],
                $data['image']
            );
        }

        return new Cart(
            (int) $cartData['id'],
            $userId,
            $items
        );
    }

    public function save(Cart $cart): void
    {
        $sql = "SELECT id FROM carts WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $cart->getUserId()]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $sql = "INSERT INTO carts (user_id) VALUES (:user_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $cart->getUserId()]);
        }
    }

    public function addItem(int $userId, CartItem $item): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) {
            $this->save(new Cart(null, $userId, []));
            $cart = $this->findByUserId($userId);
        }

        $sql = "SELECT id, quantity FROM cart_items 
                WHERE cart_id = :cart_id AND food_id = :food_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cart_id' => $cart->getId(),
            ':food_id' => $item->getFoodId()
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQuantity = $existing['quantity'] + $item->getQuantity();
            $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':quantity' => $newQuantity,
                ':id' => $existing['id']
            ]);
        } else {
            $sql = "INSERT INTO cart_items (cart_id, food_id, quantity) 
                    VALUES (:cart_id, :food_id, :quantity)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cart_id' => $cart->getId(),
                ':food_id' => $item->getFoodId(),
                ':quantity' => $item->getQuantity()
            ]);
        }
    }

    public function removeItem(int $userId, int $foodId): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) return;

        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND food_id = :food_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cart_id' => $cart->getId(),
            ':food_id' => $foodId
        ]);
    }

    public function updateItemQuantity(int $userId, int $foodId, int $quantity): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) return;

        if ($quantity <= 0) {
            $this->removeItem($userId, $foodId);
            return;
        }

        $sql = "UPDATE cart_items SET quantity = :quantity 
                WHERE cart_id = :cart_id AND food_id = :food_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cart->getId(),
            ':food_id' => $foodId
        ]);
    }

    public function clear(int $userId): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) return;

        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cart_id' => $cart->getId()]);
    }

    public function getTotal(int $userId): float
    {
        $cart = $this->findByUserId($userId);
        return $cart ? $cart->getTotal() : 0;
    }

    public function getItemCount(int $userId): int
    {
        $cart = $this->findByUserId($userId);
        return $cart ? $cart->getItemCount() : 0;
    }
}