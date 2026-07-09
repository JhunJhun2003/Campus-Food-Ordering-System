<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Application\DTOs\UpdateFoodRequest;

class UpdateFoodUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(UpdateFoodRequest $request): array  // ✅ Use DTO
    {
        // Validate
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        try {
            $data = $request->toArray();
            $id = $request->getId();
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