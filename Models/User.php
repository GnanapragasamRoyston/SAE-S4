<?php

class User {
 private $conn;
 private $table = "personne"; // Table des personnes

 public $id;
 public $nom;
 public $prenom;
 public $email;
 public $password;
 public $role;

 public function __construct($db) {
     $this->conn = $db;
 }

 // Méthode pour se connecter
 public function login($email, $password) {
     $query = "SELECT * FROM " . $this->table . " WHERE mail = :email";
     $stmt = $this->conn->prepare($query);
     $stmt->bindParam(':email', $email);
     $stmt->execute();
     
     $user = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if (!$user) {
         writeLog("Échec de connexion : Aucun utilisateur trouvé pour l'email $email");
         return false;
     }
     
     if (!password_verify($password, $user['mdp'])) {
         writeLog("Échec de connexion : Mot de passe incorrect pour l'email $email");
         return false;
     }
     
     $this->id = $user['personne_id'];
     $this->email = $user['mail'];
     $this->nom = $user['nom'];
     $this->prenom = $user['prenom'];
     $this->role = $this->getUserRole($user['personne_id']);
     
     if (!$this->role) {
         writeLog("Échec de connexion : Aucun rôle trouvé pour l'utilisateur ID {$user['personne_id']}");
         return false;
     }
     
     $_SESSION['user_id'] = $this->id;
     $_SESSION['email'] = $this->email;
     $_SESSION['nom'] = $this->nom;
     $_SESSION['prenom'] = $this->prenom;
     $_SESSION['role'] = $this->role;
     writeLog("L'utilisateur ID: {$_SESSION['user_id']} s'est connecté.");
     return true; 
 }

 // Vérifie si l'email existe déjà dans la base de données
 public function checkEmailExists($email) {
     $query = "SELECT * FROM " . $this->table . " WHERE mail = :email";
     $stmt = $this->conn->prepare($query);
     $stmt->bindParam(':email', $email);
     $stmt->execute();
     return $stmt->rowCount() > 0;
 }

 // Méthode pour inscrire un utilisateur
 public function register($nom, $prenom, $email, $password, $tel, $adresse) {
     // Hachage du mot de passe
     $hashed_password = password_hash($password, PASSWORD_DEFAULT);

     // Préparer la requête d'insertion dans la table personne
     $query = "INSERT INTO " . $this->table . " (nom, prenom, mail, mdp, telephone, adresse) 
     VALUES (:nom, :prenom, :mail, :mdp, :telephone, :adresse)";
     $stmt = $this->conn->prepare($query);

     // Lier les paramètres
     $stmt->bindParam(':nom', $nom);
     $stmt->bindParam(':prenom', $prenom);
     $stmt->bindParam(':mail', $email);
     $stmt->bindParam(':mdp', $hashed_password);
     $stmt->bindParam(':telephone', $tel);
     $stmt->bindParam(':adresse', $adresse);

     // Exécuter la requête
     if ($stmt->execute()) {
         writeLog("Utilisateur inscrit avec l'email: $email");
         return true;
     }
     writeLog("Échec de l'inscription pour l'email: $email");
     return false;
 }

 // Méthode pour assigner un rôle à un utilisateur
 public function assignRoleToUser($personne_id, $role_id) {
     $query = "INSERT INTO personne_role (personne_id, role_id) VALUES (:personne_id, :role_id)";
     $stmt = $this->conn->prepare($query);
     $stmt->bindParam(':personne_id', $personne_id);
     $stmt->bindParam(':role_id', $role_id);
     if ($stmt->execute()) {
         writeLog("Rôle $role_id assigné à l'utilisateur ID: $personne_id");
         return true;
     }
     writeLog("Échec de l'assignation du rôle $role_id à l'utilisateur ID: $personne_id");
     return false;
 }

 // Méthode pour obtenir le rôle de l'utilisateur
 public function getUserRole($personne_id) {
     $query = "SELECT role_nom FROM role
     JOIN personne_role ON role.role_id = personne_role.role_id
     WHERE personne_role.personne_id = :personne_id";
     $stmt = $this->conn->prepare($query);
     $stmt->bindParam(':personne_id', $personne_id);
     $stmt->execute();
     $role = $stmt->fetch(PDO::FETCH_ASSOC);
     if (!$role) {
         writeLog("Aucun rôle trouvé pour personne_id: $personne_id");
     }
     return $role ? $role['role_nom'] : null;
 }

 // Méthode pour récupérer l'ID d'un utilisateur par son email
 public function getPersonneIdByEmail($email) {
     $query = "SELECT personne_id FROM " . $this->table . " WHERE mail = :email";
     $stmt = $this->conn->prepare($query);
     $stmt->bindParam(':email', $email);
     $stmt->execute();
     $result = $stmt->fetch(PDO::FETCH_ASSOC);
     return $result ? $result['personne_id'] : null;
 }
}
?>