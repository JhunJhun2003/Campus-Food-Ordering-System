<?php
namespace App\Food\Infrastructure\Repositories;

use App\Food\Domain\Entities\Food;
use App\Food\Domain\Entities\FoodSize;
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
            $sql = "INSERT INTO foods (category_id, status_id, name, description, image, preparation_time) 
                    VALUES (:category_id, :status_id, :name, :description, :image, :preparation_time)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':category_id' => $food->getCategoryId(),
                ':status_id' => $food->getStatusId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
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
                    image = :image, 
                    preparation_time = :preparation_time 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':category_id' => $food->getCategoryId(),
                ':status_id' => $food->getStatusId(),
                ':name' => $food->getName(),
                ':description' => $food->getDescription(),
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
                WHERE f.id = :id FOR UPDATE";
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
                ORDER BY RAND()";
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
                WHERE f.status_id = 1
                ORDER BY f.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $activeFoods = [];
        foreach ($data as $row) {
            $food = $this->hydrate($row);
            $sizes = $food->getSizes();
            if (empty($sizes)) {
                $activeFoods[] = $food;
                continue;
            }

            $hasInStockSize = false;
            foreach ($sizes as $size) {
                if ($size->getStock() > 0) {
                    $hasInStockSize = true;
                    break;
                }
            }

            if ($hasInStockSize) {
                $activeFoods[] = $food;
            }
        }

        return $activeFoods;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function updateStock(int $id, int $quantity): void
    {
        $size = $this->getDefaultSizeForFood($id);
        if (!$size) {
            return;
        }

        $this->updateSize($size->getId(), ['stock' => max(0, $size->getStock() + $quantity)]);
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
            0.0,
            0,
            $data['image'] ?? null,
            (int) ($data['preparation_time'] ?? 15),
            (int) ($data['status_id'] ?? 1)
        );

        if (isset($data['created_at'])) {
            $food->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $food->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        $sizes = $this->getSizes((int) $data['id']);
        $food->setSizes($sizes);

        if (!empty($sizes)) {
            $defaultSize = $food->getDefaultSize();
            if ($defaultSize) {
                $food->setPrice($defaultSize->getPrice());
                $food->setStock($defaultSize->getStock());
            }
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

        if (!$result) {
            return null;
        }

        $sizes = $this->getSizes($id);
        if (!empty($sizes)) {
            $defaultSize = null;
            foreach ($sizes as $size) {
                if ($size->isDefault()) {
                    $defaultSize = $size;
                    break;
                }
            }

            if ($defaultSize === null) {
                $defaultSize = $sizes[0];
            }

            if ($defaultSize) {
                $result['price'] = $defaultSize->getPrice();
                $result['stock'] = $defaultSize->getStock();
            }
        }

        return $result;
    }

    public function createFood(array $data): int
    {
        $sql = "INSERT INTO foods (category_id, status_id, name, description, image, preparation_time) 
                VALUES (:category_id, :status_id, :name, :description, :image, :preparation_time)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'],
            ':status_id' => $data['status_id'] ?? 1,
            ':name' => $data['name'],
            ':description' => $data['description'],
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
        if (isset($data['status_id'])) {
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
                
                $size = $this->getDefaultSizeForFood($foodId);
                if (!$size) {
                    throw new \Exception("No size found for food item ID: $foodId");
                }

                if ($size->getStock() < $quantity) {
                    throw new \Exception("Not enough stock for food item ID: $foodId");
                }

                $this->updateSize($size->getId(), ['stock' => $size->getStock() - $quantity]);
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
        $size = $this->getDefaultSizeForFood($foodId);
        if (!$size) {
            return false;
        }

        if ($size->getStock() < $quantity) {
            return false;
        }

        return $this->updateSize($size->getId(), ['stock' => $size->getStock() - $quantity]);
    }

    public function getStock(int $foodId): int
    {
        $sizes = $this->getSizes($foodId);
        $total = 0;
        foreach ($sizes as $size) {
            $total += $size->getStock();
        }
        return $total;
    }

    // ============================================
    // STATISTICS METHODS - ADD THESE
    // ============================================

    /**
     * Get total number of food items
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM foods");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get count by category
     */
    public function countByCategory(int $categoryId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM foods WHERE category_id = :category_id");
        $stmt->execute([':category_id' => $categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get active food count (status_id = 1 and stock > 0)
     */
    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(DISTINCT f.id) as count FROM foods f LEFT JOIN food_sizes fs ON fs.food_id = f.id WHERE f.status_id = 1 AND (fs.id IS NULL OR fs.stock > 0)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get count by status
     */
    public function countByStatus(int $statusId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM foods WHERE status_id = :status_id");
        $stmt->execute([':status_id' => $statusId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get count of out of stock items
     */
    public function countOutOfStock(): int
    {
        $stmt = $this->db->query("SELECT COUNT(DISTINCT f.id) as count FROM foods f LEFT JOIN food_sizes fs ON fs.food_id = f.id WHERE f.status_id = 1 AND NOT EXISTS (SELECT 1 FROM food_sizes fs2 WHERE fs2.food_id = f.id AND fs2.stock > 0)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

     // ============================================
    // STOCK MANAGEMENT - ADD THESE METHODS
    // ============================================

    /**
     * Restore stock with pessimistic locking
     * Must be called inside a transaction
     */
    public function restoreStockWithLock(int $foodId, int $quantity): bool
    {
        try {
            $size = $this->getDefaultSizeForFood($foodId);
            if (!$size) {
                throw new \Exception("Food item not found: $foodId");
            }

            return $this->updateSize($size->getId(), ['stock' => $size->getStock() + $quantity]);
            
        } catch (\PDOException $e) {
            error_log('Error restoring stock with lock: ' . $e->getMessage());
            throw $e;
        }
    }

    // ============================================
    // SIZE METHODS
    // ============================================

    public function getSizes(int $foodId): array
    {
        $sql = "SELECT * FROM food_sizes WHERE food_id = :food_id ORDER BY price ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':food_id' => $foodId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateSize'], $data);
    }

    public function findSizeById(int $sizeId): ?FoodSize
    {
        $sql = "SELECT * FROM food_sizes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $sizeId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrateSize($data) : null;
    }

    public function createSize(FoodSize $size): int
    {
        $sql = "INSERT INTO food_sizes (food_id, size_name, price, stock, is_default) 
                VALUES (:food_id, :size_name, :price, :stock, :is_default)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':food_id' => $size->getFoodId(),
            ':size_name' => $size->getSizeName(),
            ':price' => $size->getPrice(),
            ':stock' => $size->getStock(),
            ':is_default' => $size->isDefault() ? 1 : 0
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateSize(int $sizeId, array $data): bool
    {
        $fields = [];
        $params = [':id' => $sizeId];

        if (isset($data['size_name'])) {
            $fields[] = "size_name = :size_name";
            $params[':size_name'] = $data['size_name'];
        }
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        if (isset($data['stock'])) {
            $fields[] = "stock = :stock";
            $params[':stock'] = $data['stock'];
        }
        if (isset($data['is_default'])) {
            $fields[] = "is_default = :is_default";
            $params[':is_default'] = $data['is_default'] ? 1 : 0;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE food_sizes SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteSize(int $sizeId): bool
    {
        // Check if this size is used in cart_items or order_items
        $sql = "SELECT COUNT(*) FROM cart_items WHERE food_size_id = :size_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':size_id' => $sizeId]);
        $cartCount = (int) $stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM order_items WHERE food_size_id = :size_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':size_id' => $sizeId]);
        $orderCount = (int) $stmt->fetchColumn();

        if ($cartCount > 0 || $orderCount > 0) {
            // Soft delete - mark as inactive instead of deleting
            return $this->updateSize($sizeId, ['is_default' => false]);
        }

        $sql = "DELETE FROM food_sizes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $sizeId]);
    }

    public function reduceSizeStock(int $sizeId, int $quantity): bool
    {
        $sql = "UPDATE food_sizes SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $sizeId
        ]);
        return $result && $stmt->rowCount() > 0;
    }

    public function restoreSizeStock(int $sizeId, int $quantity): bool
    {
        $sql = "UPDATE food_sizes SET stock = stock + :quantity WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $sizeId
        ]);
    }

    private function hydrateSize(array $data): FoodSize
    {
        return new FoodSize(
            (int) $data['id'],
            (int) $data['food_id'],
            $data['size_name'],
            (float) $data['price'],
            (int) ($data['stock'] ?? 0),
            (bool) ($data['is_default'] ?? false)
        );
    }

    private function getDefaultSizeForFood(int $foodId): ?FoodSize
    {
        $sizes = $this->getSizes($foodId);
        if (empty($sizes)) {
            return null;
        }

        foreach ($sizes as $size) {
            if ($size->isDefault()) {
                return $size;
            }
        }

        return $sizes[0];
    }

     /**
     * Find food by ID with all sizes
     */
    public function findByIdWithSizes(int $id): ?Food
    {
        $food = $this->findById($id);
        if (!$food) {
            return null;
        }
        
        $sizes = $this->getSizes($id);
        $food->setSizes($sizes);
        
        return $food;
    }
}