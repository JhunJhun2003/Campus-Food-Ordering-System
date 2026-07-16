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
use App\Food\Application\DTOs\CreateFoodSizeRequest;
use App\Food\Application\DTOs\UpdateFoodSizeRequest;
use App\Food\Application\Usecases\CreateFoodSizeUseCase;
use App\Food\Application\Usecases\UpdateFoodSizeUseCase;
use App\Food\Application\Usecases\DeleteFoodSizeUseCase;
use App\Food\Domain\Entities\FoodSize;
use App\Shared\Presentation\Http\Controllers\BaseController;

class FoodController extends BaseController
{
    private FoodRepositoryInterface $foodRepository;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        FoodRepositoryInterface $foodRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct();
        $this->foodRepository = $foodRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Handle image upload
     */
    private function uploadImage(array $file, ?string $existingImage = null): ?string
    {
        // If no file uploaded, return existing image
        if (!isset($file['image']) || $file['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return $existingImage;
        }

        // Check for upload errors
        if ($file['image']['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error: " . $file['image']['error']);
            return $existingImage;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['image']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            error_log("Invalid file type: " . $fileType);
            return $existingImage;
        }

        // Validate file size (max 2MB)
        if ($file['image']['size'] > 2 * 1024 * 1024) {
            error_log("File too large: " . $file['image']['size']);
            return $existingImage;
        }

        // Generate unique filename
        $extension = pathinfo($file['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/Campus-Food-Ordering-System/Public/uploads/foods/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Move uploaded file
        $destination = $uploadPath . $filename;
        if (move_uploaded_file($file['image']['tmp_name'], $destination)) {
            // Delete old image if exists
            if ($existingImage && file_exists($uploadPath . $existingImage)) {
                unlink($uploadPath . $existingImage);
            }
            return $filename;
        }

        error_log("Failed to move uploaded file");
        return $existingImage;
    }

    /**
     * Get all foods - No permission needed (public)
     */
    public function index(): array
    {
        $useCase = new GetAllFoodsUseCase($this->foodRepository);
        return $useCase->execute();
    }

    /**
     * Get all categories - No permission needed (public)
     */
    public function getCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    /**
     * Get food for editing - Staff/Admin only
     */
    public function getForEdit(int $id): ?array
    {
        $this->authorize('manage_menu');
        
        $useCase = new GetFoodForEditUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    /**
     * Create a new food item - Staff/Admin only
     */
    public function create(CreateFoodRequest $request): array
    {
        $this->authorize('manage_menu');
        
        $useCase = new CreateFoodUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Update a food item - Staff/Admin only
     */
    public function update(UpdateFoodRequest $request): array
    {
        $this->authorize('manage_menu');
        
        $useCase = new UpdateFoodUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Delete a food item - Admin only
     */
    public function delete(int $id): array
    {
        $this->authorize('delete_food');
        
        $useCase = new DeleteFoodUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    /**
     * Handle form submission with file uploads
     * This handles POST requests for add, edit, delete
     */
    public function handleRequest(): array
    {
        $message = null;
        $editFood = null;

        // ✅ Handle POST: Add - Staff/Admin only
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
            $this->authorize('manage_menu');
            
            // Upload image
            $imageName = $this->uploadImage($_FILES);
            
            $request = new CreateFoodRequest(
                (int) ($_POST['category_id'] ?? 0),
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                (float) ($_POST['price'] ?? 0),
                (int) ($_POST['stock'] ?? 0),
                (int) ($_POST['preparation_time'] ?? 15),
                $imageName
            );
            
            $message = $this->create($request);

            if (($message['success'] ?? false) && !empty($message['id'])) {
                $this->persistFoodSizes((int) $message['id'], $_POST['size_name'] ?? [], $_POST['size_price'] ?? [], $_POST['size_stock'] ?? [], $_POST['default_size'] ?? null);
            }

            return ['message' => $message];
        }

        // ✅ Handle POST: Edit - Staff/Admin only
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
            $this->authorize('manage_menu');
            
            $foodId = (int) ($_POST['food_id'] ?? 0);
            
            // Get existing food to get current image
            $existingFood = $this->getForEdit($foodId);
            $existingImage = $existingFood['image'] ?? null;
            
            // Upload new image if provided
            $imageName = $this->uploadImage($_FILES, $existingImage);
            
            $request = new UpdateFoodRequest(
                $foodId,
                (int) ($_POST['category_id'] ?? 0),
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                (float) ($_POST['price'] ?? 0),
                (int) ($_POST['stock'] ?? 0),
                (int) ($_POST['preparation_time'] ?? 15),
                $imageName
            );
            
            $message = $this->update($request);

            if (($message['success'] ?? false)) {
                $this->persistFoodSizes($foodId, $_POST['size_name'] ?? [], $_POST['size_price'] ?? [], $_POST['size_stock'] ?? [], $_POST['default_size'] ?? null);
            }

            return ['message' => $message];
        }

        // ✅ Handle POST: Delete - Admin only
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
            $this->authorize('delete_food');
            
            $foodId = (int) ($_POST['food_id'] ?? 0);
            if ($foodId > 0) {
                // Get food to delete image
                $existingFood = $this->getForEdit($foodId);
                if ($existingFood && isset($existingFood['image'])) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/Campus-Food-Ordering-System/Public/uploads/foods/' . $existingFood['image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                $message = $this->delete($foodId);
                return ['message' => $message];
            }
        }

        // ✅ Handle GET: Edit mode
        if (isset($_GET['edit'])) {
            $this->authorize('manage_menu');
            $editId = (int) $_GET['edit'];
            $editFood = $this->getForEdit($editId);
        }

        return [
            'message' => $message,
            'editFood' => $editFood
        ];
    }

        /**
     * Get sizes for a food item
     */
    public function getSizes(int $foodId): array
    {
        return $this->foodRepository->getSizes($foodId);
    }

    /**
     * Create a new food size
     */
    public function createSize(CreateFoodSizeRequest $request): array
    {
        $this->authorize('manage_menu');
        $useCase = new CreateFoodSizeUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Update a food size
     */
    public function updateSize(UpdateFoodSizeRequest $request): array
    {
        $this->authorize('manage_menu');
        $useCase = new UpdateFoodSizeUseCase($this->foodRepository);
        return $useCase->execute($request);
    }

    /**
     * Delete a food size
     */
    public function deleteSize(int $sizeId): array
    {
        $this->authorize('manage_menu');
        $useCase = new DeleteFoodSizeUseCase($this->foodRepository);
        return $useCase->execute($sizeId);
    }

    /**
     * Handle add size form submission
     */
    public function handleAddSize(): array
    {
        $this->authorize('manage_menu');
        
        $foodId = (int) ($_POST['food_id'] ?? 0);
        $sizeName = trim($_POST['size_name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $isDefault = isset($_POST['is_default']) ? true : false;

        $request = new CreateFoodSizeRequest($foodId, $sizeName, $price, $stock, $isDefault);
        return $this->createSize($request);
    }

    private function persistFoodSizes(int $foodId, array $sizeNames, array $sizePrices, array $sizeStocks, ?string $defaultSize = null): void
    {
        $names = array_values(array_filter(array_map('trim', $sizeNames)));
        $prices = array_values(array_map('floatval', $sizePrices));
        $stocks = array_values(array_map('intval', $sizeStocks));

        if (empty($names)) {
            return;
        }

        $existingSizes = $this->foodRepository->getSizes($foodId);
        $existingIds = [];

        foreach ($existingSizes as $size) {
            $existingIds[$size->getId()] = true;
        }

        $sizeCount = min(count($names), count($prices), count($stocks));
        $defaultId = null;

        if ($defaultSize !== null) {
            $defaultId = (int) $defaultSize;
        }

        $currentSizeIds = [];

        for ($i = 0; $i < $sizeCount; $i++) {
            $name = $names[$i];
            if ($name === '') {
                continue;
            }

            $price = $prices[$i] ?? 0.0;
            $stock = $stocks[$i] ?? 0;
            $isDefault = ($defaultId !== null) ? ($defaultId === ($i + 1)) : ($i === 0);

            if ($i < count($existingSizes)) {
                $existingSize = $existingSizes[$i] ?? null;
                if ($existingSize) {
                    $this->foodRepository->updateSize($existingSize->getId(), [
                        'size_name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'is_default' => $isDefault,
                    ]);
                    $currentSizeIds[] = $existingSize->getId();
                }
            } else {
                $newSize = new \App\Food\Domain\Entities\FoodSize(null, $foodId, $name, $price, $stock, $isDefault);
                $newSizeId = $this->foodRepository->createSize($newSize);
                $currentSizeIds[] = $newSizeId;
            }
        }

        foreach ($existingSizes as $existingSize) {
            if (!in_array($existingSize->getId(), $currentSizeIds, true)) {
                $this->foodRepository->deleteSize($existingSize->getId());
            }
        }
    }
}