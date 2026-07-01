<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;

class CreateFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(array $data): array
    {
        // Validate
        if (empty($data['name']) || empty($data['category_id']) || empty($data['price'])) {
            return ['success' => false, 'message' => 'Name, Category, and Price are required.'];
        }

        try {
            $foodId = $this->foodRepository->createFood($data);
            return [
                'success' => true,
                'message' => 'Food item added successfully!',
                'id' => $foodId
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to add food item: ' . $e->getMessage()];
        }
    }
}