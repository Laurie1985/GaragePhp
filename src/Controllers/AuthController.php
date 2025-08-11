<?php
namespace App\Controllers;

use App\Models\User;

//use App\Security\Validator;
use App\Security\TokenManager;
use App\Utils\Logger;

/**
 * Cette classe gère les actions liées à l'authentification des utilisateurs.
 * Elle permet de s'inscrire, de se connecter et de se déconnecter.
 */

class AuthController extends BaseController
{
    //Attributs
    private User $userModel;
    private TokenManager $tokenManager;
    private Logger $logger;

    //Constructeur est appellé à chaque création d'un objet AuthController
    //Il initialise les attributs avec des instances des classes User, TokenManager et Logger
    public function __construct()
    {
        parent::__construct();
        $this->userModel    = new User(); //j'instancie le modèle User
        $this->tokenManager = new TokenManager();
        $this->logger       = new Logger();
    }

    /**
     * Méthode qui affiche la page avec le formulaire de connexion.
     * Elle vérifie si l'utilisateur est déjà connecté et le redirige vers la page
     */
    public function showLogin(): void
    {
        // Affiche la vue de connexion
        $this->render('auth/login', [
            'title'      => 'Connexion',
            'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
        ]);
    }

    public function login(): void
    {

        //On s'asssure que la requête est de type POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->response->redirect('/login');
        }

        $data = $this->getPostData();

        //Validation du jeton CSRF
        if (! $this->tokenManager->ValidateCsrfToken($data['crsf_token'] ?? '')) {
            $this->response->error('Token de sécurité invalide.', 403); // Redirige vers la page de connexion avec un message d'erreur

        }

                                                                                   //Le modele User s'occupe de la logique d'authentification
        $user = $this->userModel->authenticate($data['email'], $data['password']); // Authentifie l'utilisateur avec l'email et le mot de passe

        if ($user) {

            //Si l'authentification réussit, on stocke les informations en session
            $_SESSION['user_id']  = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['role']     = $user->getRole();

            //Redirection vers le tableau de bord
            $this->response->redirect('/cars');
        } else {
            //Si l'authentification échoue, on affiche le formulaire de connexion avec un message d'erreur
            $this->render('auth/login', [
                'title'      => 'Connexion',
                'error'      => 'Email ou mot de passe incorrectes.',
                'old'        => ['email' => $data['email']],              // Pré-remplit le champ email avec la valeur saisie
                'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
            ]);
        }
    }

    /**
     * Affichage du formulaire d'inscription.
     */

    public function showRegister(): void
    {
        // Affiche la vue d'inscription
        $this->render('auth/register', [
            'title'      => 'Inscription',
            'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
        ]);
    }

    /**
     * Traitement des données soummises par le formulaire d'inscription.
     */

    public function register(): void
    {
        //On s'assure que la requête est de type POST sinon on redirige vers la page d'inscription
        //pour éviter les attaques CSRF et les requêtes GET accidentelles
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->response->redirect('/register');
        }

        $data = $this->getPostData();

        //Validation du jeton CSRF
        if (! $this->tokenManager->ValidateCsrfToken($data['crsf_token'] ?? '')) {
            $this->response->error('Token de sécurité invalide.', 403); // Redirige vers la page d'inscription avec un message d'erreur
        }

        //Validation des données du formulaire d'inscription
        $errors = $this->validator->validate($data, [
            'username'          => 'required|min:3|max:50',
            'email'             => 'required|email',
            'password'          => 'required|min:9',
            'passeword_confirm' => 'required|same:password',
        ]);

        if (! empty($errors)) {
            //Si des erreurs de validation sont présentes, on affiche le formulaire d'inscription avec les erreurs
            $this->render('auth/register', [
                'title'      => 'Inscription',
                'errors'     => $errors,
                'old'        => $data,                                    // Pré-remplit les champs avec les valeurs saisies
                'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
            ]);
            return;
        }

        //Vérification de l'email
        if ($this->userModel->findByEmail($data['email'])) {
            //Si l'email existe déjà, on affiche le formulaire d'inscription avec un message d'erreur
            $this->render('auth/register', [
                'title'      => 'Inscription',
                'errors'     => ['email' => ['Cette adress email est déjà utilisée.']],
                'old'        => $data,                                    // Pré-remplit les champs avec les valeurs saisies
                'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
            ]);
            return;
        }

        /**
         * Si tout est correct, on crée un nouvel utilisateur
         * et on le sauvegarde dans la base de données.
         */
        try {

            //On instancie un nouvel utilisateur
            //On utilise les setters pour remplir les propriétés de l'objet User(inclut la validation et hachage du mot de passe)
            $newUser = new User();
            $newUser->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setPassword($data['password'])
                ->setRole($data['user']); //role par défaut

            //On sauvegarde l'utilisateur dans la base de données
            //La méthode save() insère ou met à jour l'utilisateur dans la base de données
            if ($newUser->save()) {

                //Si la création réussie, on connecte automatiquement l'utilisateur
                $_SESSION['user_id']  = $newUser->getId();
                $_SESSION['role']     = $newUser->getRole();
                $_SESSION['username'] = $newUser->getUsername();
                $this->response->redirect('/cars'); // Redirige vers le tableau de bord
            } else {

                //Si la sauveagarde échoue
                throw new \Exception("La création du compte a échouée.");
            }

        } catch (\Exception $e) {
            // En cas d'erreur lors de la création de l'utilisateur, on affiche le formulaire d'inscription avec un message d'erreur
            $this->render('auth/register', [
                'title'      => 'Inscription',
                'errors'     => "Erreur : " . $e->getMessage(),           // Affiche l'erreur générale
                'old'        => $data,                                    // Pré-remplit les champs avec les valeurs saisies
                'csrf_token' => $this->tokenManager->generateCsrfToken(), //Méthode interne pour générer un jeton CSRF
            ]);

        }
    }

/**
 * Méthode déconnexion avec destruction de la session
 */
    public function logout(): void
    {

        //On s'assure que la requête est de type POST sinon on redirige vers la page d'inscription
        //pour éviter les attaques CSRF et les requêtes GET accidentelles
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->response->redirect('/');
        }

        //On détruit la session pour déconnecter l'utilisateur
        session_destroy();

        //Redirige vers la page de connexion
        $this->response->redirect('/login');

    }
}
