<?php
namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel
{
    /**
     * @var PDO l'instance de la connexion à la base de données
     */
    protected PDO $db;

    /**
     * @var string le nom de la table associée au modèle
     */
    protected string $table;

    public function __construct(?PDO $db = null)
    {
        // On initialise la connexion à la base de données
        $this->db = $db ?? Database::getInstance();

    }
}
