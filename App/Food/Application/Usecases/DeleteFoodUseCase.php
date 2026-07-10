<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;
use Inc\Database;

class DeleteFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(int $id): array
    {
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Delete food and related records
            $db->beginTransaction();
            
            // Check if food exists
            $food = $this->foodRepository->findById($id);
            if (!$food) {
                throw new \Exception('Food item not found.');
            }

            $deleted = $this->foodRepository->deleteFood($id);
            
            if (!$deleted) {
                throw new \Exception('Failed to delete food item.');
            }
            
            // ✅ All operations succeeded
            $db->commit();
            
            return [
                'success' => true,
                'message' => 'Food item deleted successfully!'
            ];
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            
            return ['success' => false, 'message' => 'Failed to delete food item: ' . $e->getMessage()];
        }
    }
}