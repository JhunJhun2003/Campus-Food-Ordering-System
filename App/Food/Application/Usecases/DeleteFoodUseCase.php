<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;

class DeleteFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(int $id): array
    {
        try {
            // Check if food exists
            $food = $this->foodRepository->findById($id);
            if (!$food) {
                return ['success' => false, 'message' => 'Food item not found.'];
            }

            $deleted = $this->foodRepository->deleteFood($id);
            return [
                'success' => $deleted,
                'message' => $deleted ? 'Food item deleted successfully!' : 'Failed to delete food item.'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete food item: ' . $e->getMessage()];
        }
    }
}