<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;

class UpdateFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(int $id, array $data): array
    {
        if (empty($data['name']) || empty($data['category_id']) || empty($data['price'])) {
            return ['success' => false, 'message' => 'Name, Category, and Price are required.'];
        }

        try {
            $updated = $this->foodRepository->updateFood($id, $data);
            return [
                'success' => $updated,
                'message' => $updated ? 'Food item updated successfully!' : 'Failed to update food item.'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update food item: ' . $e->getMessage()];
        }
    }
}