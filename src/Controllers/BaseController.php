<?php
namespace App\Controllers;

use App\Security\Validator;
use App\Utils\Response;

/**
 * Controller de base pour les autres contrôleurs
 * Toutes les autres classes de contrôleur doivent hériter de cette classe
 */
abstract class BaseController
{
    protected Response $response;
    protected Validator $validator;

    public function __construct()
    {
        $this->response  = new Response();
        $this->validator = new Validator();
    }

    /**
     * Affiche une vue en l'injectant dans le layout principal
     * @param string $view Le nom de la vue à afficher (sans l'extension .php)
     * @param array $data Les données à passer à la vue
     */

    protected function render(string $view, array $data = []): void
    {
        //On construit le chemin complet vers le fichier de vue
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        //On vérifie si le fichier de vue existe
        if (! file_exists($viewPath)) {
            $this->response->error("Vue non trouvée : $viewPath", 500);
            return;
        }

                        //Extract transforme les clés d'un tableau en variables
                        //Par exemple, $data = ['title' => 'Accueil'] devient $title = 'Accueil'
        extract($data); // Extrait les variables du tableau $data pour les rendre disponibles dans la vue

                           //On utilise la mise en tampon de sortie (output buffering) pour capturer le HTML de la vue
        ob_start();        // Démarre la mise en tampon de sortie
        include $viewPath; // Inclut la vue

                                   //On vide le cache, la variable $content contient la vue
        $content = ob_get_clean(); // Récupère le contenu mis en tampon et nettoie le tampon

                                                  //Finalement, on inclut le layout principal, qui peut maintenant utiliser la variable $content
        include __DIR__ . '/../views/layout.php'; // Inclut le layout principal
    }

    /**
     * Récupère et nettoie les donneés envoyées via une requête POST
     */
    protected function getPostData(): array
    {
        return $this->validator->sanitize($_POST);
    }

    /**
     * Vérifie si l'utilisateur est authentifié sinon redirige vers la page de connexion
     */
    protected function requireAuth(): void
    {
        if (! isset($_SESSION['user_id'])) {
            $this->response->redirect('/login');
        }
    }
}
