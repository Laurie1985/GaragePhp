<?php

// Affiche les erreurs directement dans la page
init_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

//Import des classes
use App\Config\Config;
use App\Utils\Response;

// Démarrer la session ou reprendre la session existante
session_start();

//Charger nos variables d'environnement
Config::load();

//Définir des routes avec la bibliothèque FastRoute
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    $r->addRoute('GET', '/', ['App\Controllers\HomeController::class', 'index']);
    $r->addRoute('GET', '/login', ['App\Controllers\AuthController::class', 'showLogin']);
    $r->addRoute('POST', '/login', ['App\Controllers\AuthController::class', 'login']);
    $r->addRoute('POST', '/logout', ['App\Controllers\AuthController::class', 'logout']);
    $r->addRoute('GET', '/cars', ['App\Controllers\CarController::class', 'index']);

});

//Traitement de la requête

//Récupérer la méthode HTTP (GET, POST, PUT, PATCH, DELETE) et l'URI de la requête (/cars, /login, etc.)
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri        = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

//Dispatcher FastRoute
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
$response  = new Response();

//Analyser le résultat du dispatch
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // La route n'a pas été trouvée
        $response->error("404 - Page non trouvée", 404);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // La méthode HTTP n'est pas autorisée pour cette route
        $response->error("405 - Méthode non autorisée", 405);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$controllerClass, $method] = $routeInfo[1];
        $vars                       = $routeInfo[2];
        try {
            // Instancier le contrôleur et appeler la méthode correspondante
            $controller = new $controllerClass();
            call_user_func_array([$controller, $method], $vars);
        } catch (\Exception $e) {
            // Gérer les exceptions
            if (Config::get('APP_DEBUG') === true) {
                // En mode debug, afficher l'erreur
                $response->error("Erreur 500 : " . $e->getMessage() . " dans " . $e->getFile() . ":" . $e->getLine(), 500);
            } else {
                (new \App\Utils\Logger())->log('ERROR', 'Erreur Serveur :' . $e->getMessage());
                $response->error("Une erreur interne est survenue.", 500);
            }
        }
        break;
}
