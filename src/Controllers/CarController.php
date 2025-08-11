<?php
namespace App\Controllers;

use App\Models\Car;

class CarController extends BaseController
{

    public function index(): void
    {
        $this->requireAuth();
        $this->render('cars/index', [
            'title' => 'Tableau de bord voitures',
            'cars'  => (new Car())->all(), // Récupère toutes les voitures depuis le modèle Car
        ]);
    }
}
