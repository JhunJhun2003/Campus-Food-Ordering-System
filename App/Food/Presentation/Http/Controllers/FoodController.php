<?php
namespace App\Food\Presentation\Http\Controllers;

use App\Food\Application\Usecases\GetAllFoodsUseCase;
use App\Food\Application\Usecases\CreateFoodUseCase;
use App\Food\Application\Usecases\UpdateFoodUseCase;
use App\Food\Application\Usecases\DeleteFoodUseCase;
use App\Food\Application\Usecases\GetFoodForEditUseCase;
use App\Food\Infrastructure\Repositories\FoodRepository;
use App\Food\Domain\Repositories\FoodRepositoryInterface; 
use Inc\Database;

class FoodController
{
    private FoodRepositoryInterface $foodRepository;

    public function __construct()
    {
        $this->foodRepository = new FoodRepository();
    }

    public function index(): array
    {
        $useCase = new GetAllFoodsUseCase($this->foodRepository);
        return $useCase->execute();
    }

    public function getCategories(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getForEdit(int $id): ?array
    {
        $useCase = new GetFoodForEditUseCase($this->foodRepository);
        return $useCase->execute($id);
    }

    public function create(array $data): array
    {
        $useCase = new CreateFoodUseCase($this->foodRepository);
        return $useCase->execute($data);
    }

    public function update(int $id, array $data): array
    {
        $useCase = new UpdateFoodUseCase($this->foodRepository);
        return $useCase->execute($id, $data);
    }

    public function delete(int $id): array
    {
        $useCase = new DeleteFoodUseCase($this->foodRepository);
        return $useCase->execute($id);
    }
}