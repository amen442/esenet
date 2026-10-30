<?php
// config/database.php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Connexion MySQL via PDO
        $dsn = 'mysql:host=localhost;dbname=esen_connect;charset=utf8mb4';
        $username = 'root';
        $password = '';
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }

    public static function getInstance(): PDO {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}

// Démarrer la session
session_start();

// Créer la connexion PDO
$conn = Database::getInstance();

// Fonction pour sécuriser les entrées
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
