<?php
namespace App\Food\Application\Usecases;

use App\Food\Domain\Repositories\FoodRepositoryInterface;

class GetAllFoodsUseCase
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct(FoodRepositoryInterface $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    public function execute(): array
    {
        return $this->foodRepository->findAll();
    }
}