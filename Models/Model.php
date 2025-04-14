<?php


class Model {
    private $bd; // Attribut privé contenant l'objet PDO
    // Attribut qui contiendra l'unique instance du modèle
    private static $instance = null;
    
    private $host = 'localhost';       // Adresse du serveur de base de données
    private $username = 'root';        // Nom d'utilisateur
    private $password = '';            // Mot de passe (vide par défaut en local)
    private $dbname = 'database_jeux'; // Nom de la base de données

    /**
     * Constructeur créant l'objet PDO et l'affectant à $bd
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8";
        $this->bd = new PDO($dsn, $this->username, $this->password);
        $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Méthode permettant de récupérer l'instance de la classe Model
     */
    public static function getModel() {
        // Si la classe n'a pas encore été instanciée
        if (self::$instance === null) {
            self::$instance = new self(); // On l'instancie
        }
        return self::$instance; // On retourne l'instance
    }

    /**
     * Méthode permettant de récupérer l'objet PDO
     */
    public function getConnection() {
        return $this->bd;
    }
}
    
?>