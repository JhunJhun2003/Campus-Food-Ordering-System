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

    public function save(Food $food): int
    {
        if ($food->getId() === null) {
            $sql = "INSERT INTO foods (category_id, status_id, name, description, price, stock, image, preparation_time) 
                    VALUES (:category_id, :status_id, :name, :description, :price, :stock, :image, :preparation_time)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':category_id' => $food->getCategoryId(),
                ':status_id' => $food->getStatusId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
                ':price' => $food->getPrice(),
                ':stock' => $food->getStock(),
                ':image' => $food->getImage(),
                ':preparation_time' => $food->getPreparationTime()
            ]);
            
            return (int) $this->db->lastInsertId();
        } else {
            $sql = "UPDATE foods SET 
                    category_id = :category_id,
                    status_id = :status_id,
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
                ':status_id' => $food->getStatusId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
                ':price' => $food->getPrice(),
                ':stock' => $food->getStock(),
                ':image' => $food->getImage(),
                ':preparation_time' => $food->getPreparationTime(),
                ':id' => $food->getId()
            ]);
            
            return $food->getId();
        }
    }

    public function findById(int $id): ?Food
    {
        $sql = "SELECT f.*, fs.status_name 
                FROM foods f
                LEFT JOIN food_statuses fs ON f.status_id = fs.id
                WHERE f.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT f.*, fs.status_name, c.name as category_name
                FROM foods f
                LEFT JOIN food_statuses fs ON f.status_id = fs.id
                LEFT JOIN categories c ON f.category_id = c.id
                ORDER BY f.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findByCategory(int $categoryId): array
    {
        $sql = "SELECT f.*, fs.status_name
                FROM foods f
                LEFT JOIN food_statuses fs ON f.status_id = fs.id
                WHERE f.category_id = :category_id 
                ORDER BY f.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findActive(): array
    {
        $sql = "SELECT f.*, fs.status_name, c.name as category_name 
                FROM foods f 
                LEFT JOIN food_statuses fs ON f.status_id = fs.id
                LEFT JOIN categories c ON f.category_id = c.id 
                WHERE f.status_id = 1 AND f.stock > 0 
                ORDER BY f.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
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

    // ============================================
    // HYDRATE METHODS - FIXED
    // ============================================

    private function hydrate(array $data): Food
    {
        $food = new Food(
            (int) $data['id'],
            $data['category_id'] ? (int) $data['category_id'] : null,
            $data['name'],
            $data['description'] ?? '',
            (float) $data['price'],
            (int) $data['stock'],
            $data['image'] ?? null,
            (int) ($data['preparation_time'] ?? 15),
            (int) ($data['status_id'] ?? 1)  // ✅ Include status_id
        );

        if (isset($data['created_at'])) {
            $food->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $food->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        return $food;
    }

    // ============================================
    // ADMIN METHODS - FIXED
    // ============================================

    public function getFoodForEdit(int $id): ?array
    {
        $sql = "SELECT * FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function createFood(array $data): int
    {
        $sql = "INSERT INTO foods (category_id, status_id, name, description, price, stock, image, preparation_time) 
                VALUES (:category_id, :status_id, :name, :description, :price, :stock, :image, :preparation_time)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'],
            ':status_id' => $data['status_id'] ?? 1,  // ✅ Add status_id
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':stock' => $data['stock'],
            ':image' => $data['image'] ?? '',
            ':preparation_time' => $data['preparation_time'] ?? 15
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function updateFood(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['category_id'])) {
            $fields[] = "category_id = :category_id";
            $params[':category_id'] = $data['category_id'];
        }
        if (isset($data['status_id'])) {  // ✅ Add status_id
            $fields[] = "status_id = :status_id";
            $params[':status_id'] = $data['status_id'];
        }
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        if (isset($data['stock'])) {
            $fields[] = "stock = :stock";
            $params[':stock'] = $data['stock'];
        }
        if (isset($data['image'])) {
            $fields[] = "image = :image";
            $params[':image'] = $data['image'];
        }
        if (isset($data['preparation_time'])) {
            $fields[] = "preparation_time = :preparation_time";
            $params[':preparation_time'] = $data['preparation_time'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE foods SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteFood(int $id): bool
    {
        try {
            $sql = "DELETE FROM foods WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    // ============================================
    // STOCK MANAGEMENT
    // ============================================

    public function reduceStockForItems(array $items): bool
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($items as $item) {
                $foodId = $item['food_id'];
                $quantity = $item['quantity'];
                
                $sql = "UPDATE foods SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    ':quantity' => $quantity,
                    ':id' => $foodId
                ]);
                
                if (!$result || $stmt->rowCount() === 0) {
                    throw new \Exception("Not enough stock for food item ID: $foodId");
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function reduceStock(int $foodId, int $quantity): bool
    {
        $sql = "UPDATE foods SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $foodId
        ]);
        
        return $result && $stmt->rowCount() > 0;
    }

    public function getStock(int $foodId): int
    {
        $sql = "SELECT stock FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $foodId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['stock'] ?? 0);
    }
}