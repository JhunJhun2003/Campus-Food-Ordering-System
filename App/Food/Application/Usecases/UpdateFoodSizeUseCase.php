<?php
declare(strict_types=1);

namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Food\Application\DTOs\UpdateFoodSizeRequest;

class UpdateFoodSizeUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(UpdateFoodSizeRequest $request): array
    {
        try {
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            $data = [];
            if ($request->sizeName !== null) {
                $data['size_name'] = $request->sizeName;
            }
            if ($request->price !== null) {
                $data['price'] = $request->price;
            }
            if ($request->stock !== null) {
                $data['stock'] = $request->stock;
            }
            if ($request->isDefault !== null) {
                $data['is_default'] = $request->isDefault;
            }

            if (empty($data)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            // If making this default, unset other defaults
            if (isset($data['is_default']) && $data['is_default']) {
                $size = $this->foodRepository->findSizeById($request->sizeId);
                if ($size) {
                    $existingSizes = $this->foodRepository->getSizes($size->getFoodId());
                    foreach ($existingSizes as $s) {
                        if ($s->getId() !== $request->sizeId && $s->isDefault()) {
                            $this->foodRepository->updateSize($s->getId(), ['is_default' => false]);
                        }
                    }
                }
            }

            $result = $this->foodRepository->updateSize($request->sizeId, $data);

            if ($result) {
                return ['success' => true, 'message' => 'Food size updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update food size'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update food size: ' . $e->getMessage()];
        }
    }
}