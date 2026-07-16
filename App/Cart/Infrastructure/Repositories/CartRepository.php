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
                    ci.food_size_id,
                    COALESCE(fs.price, fs_default.price) as price,
                    COALESCE(fs.size_name, fs_default.size_name, 'Regular') as size_name,
                    f.name as food_name,
                    f.image
                FROM cart_items ci
                JOIN foods f ON ci.food_id = f.id
                LEFT JOIN food_sizes fs ON ci.food_size_id = fs.id
                LEFT JOIN food_sizes fs_default ON fs_default.food_id = f.id AND fs_default.is_default = 1
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
                (float) ($data['price'] ?? 0), 
                (int) $data['quantity'], 
                $data['image'], 
                isset($data['food_size_id']) ? (int) $data['food_size_id'] : null,
                $data['size_name'] ?? null
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

        $sql = "SELECT id, quantity, food_size_id FROM cart_items 
                WHERE cart_id = :cart_id AND food_id = :food_id AND food_size_id <=> :food_size_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cart_id' => $cart->getId(),
            ':food_id' => $item->getFoodId(),
            ':food_size_id' => $item->getFoodSizeId()
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
            $sql = "INSERT INTO cart_items (cart_id, food_id, quantity, food_size_id) 
                    VALUES (:cart_id, :food_id, :quantity, :food_size_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cart_id' => $cart->getId(),
                ':food_id' => $item->getFoodId(),
                ':quantity' => $item->getQuantity(),
                ':food_size_id' => $item->getFoodSizeId()
            ]);
        }
    }

    public function removeItem(int $userId, int $cartItemId): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) return;

        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cart_id' => $cart->getId(),
            ':item_id' => $cartItemId
        ]);
    }

    public function updateItemQuantity(int $userId, int $cartItemId, int $quantity): void
    {
        $cart = $this->findByUserId($userId);
        if (!$cart) return;

        if ($quantity <= 0) {
            $this->removeItem($userId, $cartItemId);
            return;
        }

        $sql = "UPDATE cart_items SET quantity = :quantity 
                WHERE cart_id = :cart_id AND id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cart->getId(),
            ':item_id' => $cartItemId
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