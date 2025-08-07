<?php
namespace App\Models;

/*use InvalidArgumentException;*/
use PDO;

class User extends BaseModel
{

    protected string $table = 'users';

    private ?int $user_id = null;
    private string $username;
    private string $email;
    private string $password;
    private string $role;

    //Getters
    public function getId(): ?int
    {
        return $this->user_id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    //Setters avec validation
    public function setUsername(string $username): self
    {
        return $this;
    }
    public function setEmail(string $email): self
    {
        return $this;
    }
    public function setPassword(string $password): self
    {
        return $this;
    }
    public function setRole(string $role): self
    {
        return $this;
    }

    /*
    *Sauvegarde de l'utilisateur dans la base de données
    */
    public function save(): bool
    {
        if ($this->user_id === null) {
            $sql    = "INSERT INTO {$this->table} (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt   = $this->db->prepare($sql);
            $params = [
                ':username' => $this->username,
                ':email'    => $this->email,
                ':password' => $this->password,       //Attention : le mot de passe est déjà haché
                ':role'     => $this->role ?? 'user', // Valeur par défaut si le rôle n'est pas défini
            ];
        } else {
            $sql  = "UPDATE {$this->table} SET username = :username, email = :email, role = :role WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);

            //On lie les paramètres pour l'insertion ou la mise à jour
            $params = [
                ':username' => $this->username,
                ':email'    => $this->email,
                ':role'     => $this->role,
                ':user_id'  => $this->user_id, //ATTENTION la condition WHERE est obligatoire pour la mise à jour
            ];
        }
        $result = $stmt->execute($params);
        // Si l'utilisateur n'a pas encore été inséré, on récupère son ID
        // pour l'utiliser dans les futures opérations
        if ($this->user_id === null && $result) {
            $this->user_id = (int) $this->db->lastInsertId(); // Récupère l'ID de l'utilisateur inséré
        }
        return $result;
    }

    /**
     * Trouve un uitlisateur par son email.
     * @return static|null l'objet User trouvé ou null si aucun utilisateur n'est trouvé.
     */
    public function findByEmail(string $email): static
    {
        $stmt = $this->db->prepare("SELECT *FROM {$this->table} WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Vérifie les identifiants de l'utilisateur pour l'authentification.
     * @return static|null l'objet User si l'authentification réussit, sinon null.
     */
    public function authenticate(string $email, string $password): ?static
    {
        $user = $this->findByEmail($email);

        // Vérifie si l'utilisateur existe et si le mot de passe correspond au mot de passe haché
        // Utilise password_verify pour comparer le mot de passe en clair avec le mot de passe
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        return null; // Si l'authentification échoue, retourne null
    }

    /**
     * Cette méthode remplit les propriétés de l'objet User pour insérer dans la base de données.
     */
    private function hydrate(array $data): static
    {
        $this->user_id  = (int) $data['user_id'];
        $this->username = $data['username'];
        $this->email    = $data['email'];
        $this->password = $data['password'];
        $this->role     = $data['role'];
        return $this;
    }
}
