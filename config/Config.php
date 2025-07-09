<?php
namespace App\Config;

use Dotenv\Dotenv;

class Config
{
    /**Class Config manuelle, cette classe sert à cherger le fichier .env, à le lire et séparer et nettoyer les données
     * si on n'utilise pas de framework comme Symfony ou Laravel
    
    private static array $config = [];
    private static bool $loaded  = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../../.env';
        if (! file_exists($envFile)) {
            throw new \Exception("Fichier .env manquant");
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Ignore les commentaires
            }
            list($key, $value) = explode('=', $line, 2);
            $key               = trim($key);
            $value             = trim(trim($value), '"\''); // Enlever les guillemets

            self::$config[$key] = $value;
            $_ENV[$key]         = $value; // Mettre dans $_ENV pour compatibilité
            putenv("$key=$value");        // Mettre dans l'environnement
        }

        self::validateConfig();
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        if (! self::$loaded) {
            self::load();
        }
        return self::$config[$key] ?? $default;
    }

    private static function validateConfig(): void
    {
        $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_KEY'];
        $missing  = array_filter($required, fn($key) => empty(self::$config[$key]));

        if (! empty($missing)) {
            throw new \Exception("Variables d'environnement manquantes: " . implode(', ', $missing));
        }
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }*/

    /**
     * @param string $path le chemin vers le dossier contenant le fichier .env
     */

    public static function load($path = __DIR__ . '../'): void
    {
        //On vérifie si le fichier .env existe avant de tenter de le charger
        if (file_exists($path . '.env')) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        }
    }
    /**
     * @param string $key le nom de la variable d'environnement
     * @param mixed $default la valeur par défaut si la variable n'est pas définie
     * @return mixed la valeur de la variable d'environnement ou la valeur par défaut
     */

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

}
