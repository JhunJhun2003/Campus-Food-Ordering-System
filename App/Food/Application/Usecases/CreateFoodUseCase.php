<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Application\DTOs\CreateFoodRequest;

class CreateFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(CreateFoodRequest $request): array  // ✅ Use DTO
    {
        // Validate
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        try {
            $data = $request->toArray();
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