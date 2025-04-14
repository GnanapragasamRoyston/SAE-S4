<?php
// Initialiser la session
session_start();

// Détruire toutes les données de la session
session_unset(); // Supprimer toutes les variables de session
session_destroy(); // Détruire la session

// Rediriger vers la page de connexion ou d'accueil
header("Location: connexion.php"); // Remplacez 'index.php' par la page vers laquelle vous voulez rediriger après la déconnexion
exit;
?>