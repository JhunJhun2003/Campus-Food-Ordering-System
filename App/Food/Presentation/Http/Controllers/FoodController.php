<?php
declare(strict_types=1);

namespace App\Food\Presentation\Http\Controllers;

use App\Food\Application\Usecases\GetAllFoodsUseCase;
use App\Food\Application\Usecases\CreateFoodUseCase;
use App\Food\Application\Usecases\UpdateFoodUseCase;
use App\Food\Application\Usecases\DeleteFoodUseCase;
use App\Food\Application\Usecases\GetFoodForEditUseCase;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Domain\Repositories\CategoryRepositoryInterface;
use App\Food\Application\DTOs\CreateFoodRequest;
use App\Food\Application\DTOs\UpdateFoodRequest;

/**
 * Food Controller
 * Follows SOLID principles with Dependency Injection
 * No 'new' keyword - all dependencies are injected
 */
class FoodController
{
    private FoodRepositoryInterface $foodRepository;
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * Constructor with Dependency Injection
     * All dependencies are injected, not created inside
     */
    public function __construct(
        FoodRepositoryInterface $foodRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->foodRepository = $foodRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get all foods
     */
    public function index(): array
    {
        $useCase = new GetAllFoodsUseCase($this->foodRepository);
        return $useCase->execute();
    }

    /**
     * Get all categories
     * SQL moved to repository
     */
    public function getCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    /**
     * Get food for editing
     */
    public function getForEdit(int $id): ?array
    {
        $useCase = new GetFoodForEditUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    /**
     * Create a new food item
     */
    public function create(CreateFoodRequest $request): array
    {
        $useCase = new CreateFoodUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Update a food item
     */
    public function update(UpdateFoodRequest $request): array
    {
        $useCase = new UpdateFoodUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Delete a food item
     */
    public function delete(int $id): array
    {
        $useCase = new DeleteFoodUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    /**
     * Handle form submission
     * This is a helper method for the view layer
     */
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
            $request = new CreateFoodRequest(
                (int) ($_POST['category_id'] ?? 0),
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                (float) ($_POST['price'] ?? 0),
                (int) ($_POST['stock'] ?? 0),
                (int) ($_POST['preparation_time'] ?? 15),
                trim($_POST['image'] ?? '')
            );
            
            $message = $this->create($request);
        }

        // POST: Edit
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
            $foodId = (int) ($_POST['food_id'] ?? 0);
            $request = new UpdateFoodRequest(
                $foodId,
                (int) ($_POST['category_id'] ?? 0),
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                (float) ($_POST['price'] ?? 0),
                (int) ($_POST['stock'] ?? 0),
                (int) ($_POST['preparation_time'] ?? 15),
                trim($_POST['image'] ?? '')
            );
            
            $message = $this->update($request);
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