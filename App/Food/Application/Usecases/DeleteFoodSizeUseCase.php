<?php
declare(strict_types=1);

namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;

class DeleteFoodSizeUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(int $sizeId): array
    {
        try {
            // Check if size exists
            $size = $this->foodRepository->findSizeById($sizeId);
            if (!$size) {
                return ['success' => false, 'message' => 'Food size not found'];
            }

            // Prevent deleting the only size
            $sizes = $this->foodRepository->getSizes($size->getFoodId());
            if (count($sizes) <= 1) {
                return ['success' => false, 'message' => 'Cannot delete the only size for this food item'];
            }

            $result = $this->foodRepository->deleteSize($sizeId);

            if ($result) {
                return ['success' => true, 'message' => 'Food size deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete food size'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete food size: ' . $e->getMessage()];
        }
    }
}