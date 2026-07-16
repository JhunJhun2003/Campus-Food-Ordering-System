<?php
declare(strict_types=1);

namespace App\Food\Application\Usecases;

use App\Food\Domain\Entities\FoodSize;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Application\DTOs\CreateFoodSizeRequest;

class CreateFoodSizeUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(CreateFoodSizeRequest $request): array
    {
        try {
            // Validate
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            // Check if food exists
            $food = $this->foodRepository->findById($request->foodId);
            if (!$food) {
                return ['success' => false, 'message' => 'Food not found'];
            }

            // If this is the default size, unset other defaults
            if ($request->isDefault) {
                $existingSizes = $this->foodRepository->getSizes($request->foodId);
                foreach ($existingSizes as $size) {
                    if ($size->isDefault()) {
                        $this->foodRepository->updateSize($size->getId(), ['is_default' => false]);
                    }
                }
            }

            // Create size
            $size = new FoodSize(
                null,
                $request->foodId,
                $request->sizeName,
                $request->price,
                $request->stock,
                $request->isDefault
            );

            $sizeId = $this->foodRepository->createSize($size);

            return [
                'success' => true,
                'message' => 'Food size created successfully',
                'data' => ['size_id' => $sizeId]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create food size: ' . $e->getMessage()];
        }
    }
}