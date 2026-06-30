<?php
namespace App\Food\Presentation\Http\Controllers;

use App\Food\Application\Usecases\GetAllFoodsUseCase;
use App\Food\Infrastructure\Repositories\FoodRepository;
use Inc\Database;

class FoodController
{
    private FoodRepository $foodRepository;

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
}