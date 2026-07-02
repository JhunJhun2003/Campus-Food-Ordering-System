<?php
namespace App\Food\Presentation\Http\Controllers;

use App\Food\Application\Usecases\GetAllFoodsUseCase;
use App\Food\Application\Usecases\CreateFoodUseCase;
use App\Food\Application\Usecases\UpdateFoodUseCase;
use App\Food\Application\Usecases\DeleteFoodUseCase;
use App\Food\Application\Usecases\GetFoodForEditUseCase;
use App\Food\Infrastructure\Repositories\FoodRepository;
use Inc\Database;

class FoodController
{
    private FoodRepository $foodRepository;

    public function __construct()
    {
        $this->foodRepository = new FoodRepository();
    }

    public function index(): array
    {
        $useCase = new GetAllFoodsUseCase($this->foodRepository);
        return $useCase->execute();
    }

    public function getCategories(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getForEdit(int $id): ?array
    {
        $useCase = new GetFoodForEditUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    public function create(array $data): array
    {
        $useCase = new CreateFoodUseCase($this->foodRepository);
        return $useCase->execute($data);
    }

    public function update(int $id, array $data): array
    {
        $useCase = new UpdateFoodUseCase($this->foodRepository);
        return $useCase->execute($id, $data);
    }

    public function delete(int $id): array
    {
        $useCase = new DeleteFoodUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    // New helper method to handle form submissions
    public function handleRequest(): array
    {
        $message = null;
        $editFood = null;

        // GET: Edit
        if (isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            $editFood = $this->getForEdit($editId);
        }

        // POST: Add
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
            $data = [
                'category_id' => (int) ($_POST['category_id'] ?? 0),
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'stock' => (int) ($_POST['stock'] ?? 0),
                'preparation_time' => (int) ($_POST['preparation_time'] ?? 15),
                'image' => trim($_POST['image'] ?? '')
            ];
            
            $message = $this->create($data);
        }

        // POST: Edit
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
            $foodId = (int) ($_POST['food_id'] ?? 0);
            $data = [
                'category_id' => (int) ($_POST['category_id'] ?? 0),
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'stock' => (int) ($_POST['stock'] ?? 0),
                'preparation_time' => (int) ($_POST['preparation_time'] ?? 15),
                'image' => trim($_POST['image'] ?? '')
            ];
            
            $message = $this->update($foodId, $data);
            if ($message['success']) {
                $editFood = null;
            }
        }

        // POST: Delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
            $foodId = (int) ($_POST['food_id'] ?? 0);
            if ($foodId > 0) {
                $message = $this->delete($foodId);
            }
        }

        return [
            'message' => $message,
            'editFood' => $editFood
        ];
    }
}