<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Application\DTOs\CreateFoodRequest;
use Inc\Database;

class CreateFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(CreateFoodRequest $request): array
    {
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Food creation and related operations
            $db->beginTransaction();
            
            // Validate
            $errors = $request->validate();
            if (!empty($errors)) {
                $db->rollBack();
                return ['success' => false, 'message' => implode('<br>', $errors)];
            }

            $data = $request->toArray();
            $foodId = $this->foodRepository->createFood($data);
            
            if (!$foodId) {
                throw new \Exception('Failed to create food item.');
            }
            
            // ✅ All operations succeeded
            $db->commit();
            
            return [
                'success' => true,
                'message' => 'Food item added successfully!',
                'id' => $foodId
            ];
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            
            return ['success' => false, 'message' => 'Failed to add food item: ' . $e->getMessage()];
        }
    }
}