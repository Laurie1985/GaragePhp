<?php
namespace App\Config;

use PDO;
use PDOException;

//La classe Database est responsable de la connexion à la base de données
//On lui implémente le design pattern Singleton pour s'assurer qu'il n'y a qu'une seule instance de la connexion à la base de données

class Database
{
    //Propriété statique pour stocker l'instance unique de PDO
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Le constructeur est privé pour empêcher l'instanciation de la classe via new Database()
    }
    private function __clone()
    {
        // La méthode de clonage est privée pour empêcher la clonage de l'instance
    }
    public static function getInstance(): PDO
    {
        //Si l'instance n'existe pas, on la crée
        if (self::$instance === null) {
            //On construit le DSN (Data Source Name) pour la connexion à la base de données avec les infos du fichier .env
            $dsn     = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", Config::get('DB_HOST'), Config::get('DB_PORT', '3306'), Config::get('DB_NAME'));
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Pour lancer une exception en cas d'erreur SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Pour récupérer les résultats sous forme de tableau associatif
            ];

            try {
                //On crée une nouvelle instance de PDO avec les informations de connexion
                self::$instance = new PDO($dsn, Config::get('DB_USER'), Config::get('DB_PASSWORD'), $options);
            } catch (PDOException $e) {
                //En cas d'erreur de connexion, on affiche un message d'erreur
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
