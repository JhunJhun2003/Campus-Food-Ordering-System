<?php
namespace App\Food\Infrastructure\Repositories;

use App\Food\Domain\Entities\Food;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use Inc\Database;
use PDO;

class FoodRepository implements FoodRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(Food $food): void
    {
        if ($food->getId() === null) {
            // Insert new food
            $sql = "INSERT INTO foods (category_id, name, description, price, stock, image, preparation_time) 
                    VALUES (:category_id, :name, :description, :price, :stock, :image, :preparation_time)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':category_id' => $food->getCategoryId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
                ':price' => $food->getPrice(),
                ':stock' => $food->getStock(),
                ':image' => $food->getImage(),
                ':preparation_time' => $food->getPreparationTime()
            ]);
        } else {
            // Update existing food
            $sql = "UPDATE foods SET 
                    category_id = :category_id,
                    name = :name, 
                    description = :description, 
                    price = :price, 
                    stock = :stock, 
                    image = :image, 
                    preparation_time = :preparation_time 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':category_id' => $food->getCategoryId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
                ':price' => $food->getPrice(),
                ':stock' => $food->getStock(),
                ':image' => $food->getImage(),
                ':preparation_time' => $food->getPreparationTime(),
                ':id' => $food->getId()
            ]);
        }
    }

    public function findById(int $id): ?Food
    {
        $sql = "SELECT * FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM foods ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findByCategory(int $categoryId): array
    {
        $sql = "SELECT * FROM foods WHERE category_id = :category_id ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findActive(): array
    {
        $sql = "SELECT f.*, c.name as category_name 
                FROM foods f 
                LEFT JOIN categories c ON f.category_id = c.id 
                WHERE f.stock > 0 
                ORDER BY f.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrateWithCategory'], $data);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function updateStock(int $id, int $quantity): void
    {
        $sql = "UPDATE foods SET stock = stock + :quantity WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':quantity' => $quantity, ':id' => $id]);
    }

    private function hydrate(array $data): Food
    {
        return new Food(
            (int) $data['id'],
            $data['category_id'] ? (int) $data['category_id'] : null,
            $data['name'],
            $data['description'],
            (float) $data['price'],
            (int) $data['stock'],
            $data['image'] ?? null,
            (int) $data['preparation_time']
        );
    }

    private function hydrateWithCategory(array $data): array
    {
        return [
            'id' => (int) $data['id'],
            'category_id' => $data['category_id'] ? (int) $data['category_id'] : null,
            'category_name' => $data['category_name'] ?? 'Uncategorized',
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => (float) $data['price'],
            'stock' => (int) $data['stock'],
            'image' => $data['image'] ?? null,
            'preparation_time' => (int) $data['preparation_time'],
            'created_at' => $data['created_at']
        ];
    }
}