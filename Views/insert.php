<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

// Connexion à la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'database_jeux';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer les données du formulaire
$titre = isset($_POST['titre']) ? $_POST['titre'] : '';
$date_parution_debut = isset($_POST['date_parution_debut']) ? $_POST['date_parution_debut'] : null;
$date_parution_fin = isset($_POST['date_parution_fin']) ? $_POST['date_parution_fin'] : null;
$editeur_nom= isset($_POST['editeur']) ? $_POST['editeur'] : '';
$auteur_nom= isset($_POST['auteur']) ? $_POST['auteur'] : '';
$categorie_nom = isset($_POST['mots_cles']) ? $_POST['mots_cles'] : '';
$version = isset($_POST['version']) ? $_POST['version'] : '';
$mecanisme_id = isset($_POST['mecanisme_id']) ? $_POST['mecanisme_id'] : null;
$information_date= isset($_POST['information_date']) ? $_POST['information_date'] : '';
$etat = isset($_POST['etat']) ? $_POST['etat'] : '';
$code_barre = isset($_POST['code_barre']) ? $_POST['code_barre'] : '';
$localisation_id = isset($_POST['localisation_id']) ? $_POST['localisation_id'] : '';
$collection_id = isset($_POST['collection_id']) ? $_POST['collection_id'] : '';
$age_indique = isset($_POST['age_indique']) ? $_POST['age_indique'] : 'NR';
$nombre_de_joueurs = isset($_POST['nombre_de_joueurs']) ? $_POST['nombre_de_joueurs'] : null;
$nombre_exemplaires= isset($_POST['nombre_exemplaires']) ? $_POST['nombre_exemplaires'] : null;



$stmt = $conn->prepare("INSERT INTO jeux (titre, date_parution_debut, date_parution_fin, information_date, version, nombre_de_joueurs, age_indique, mots_cles, mecanisme_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssiiss", $titre, $date_parution_debut, $date_parution_fin, $information_date, $version, $nombre_de_joueurs, $age_indique, $mots_cles, $mecanisme_id);
$stmt->execute();
$jeu_id = $stmt->insert_id;


// Lier le jeu avec l'éditeur

    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");


    $stmtEditeur = $conn->prepare("
            INSERT INTO editeur (nom)
            VALUES (?)");
    $stmtEditeur->bind_param("s",$editeur_nom);
    $stmtEditeur->execute();
    $editeurId = $stmtEditeur->insert_id;

    $stmt = $conn->prepare("INSERT INTO jeuediteur (jeu_id, editeur_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $jeu_id, $editeurId);
    $stmt->execute();


// Lier le jeu avec l'auteur

$stmtAuteur = $conn->prepare("
    INSERT INTO auteur (nom)
    VALUES (?)");
    $stmtAuteur->bind_param("s",$auteur_nom);
    $stmtAuteur->execute();
    $auteurId = $stmtAuteur->insert_id;

    $stmt = $conn->prepare("INSERT INTO jeuauteur(jeu_id, auteur_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $jeu_id, $auteurId);
    $stmt->execute();



    // Vérifier si la catégorie existe déjà
$stmtCheckCat = $conn->prepare("SELECT categorie_id FROM categorie WHERE nom = ?");
$stmtCheckCat->bind_param("s", $categorie_nom);
$stmtCheckCat->execute();
$result = $stmtCheckCat->get_result();

if ($result->num_rows > 0) {
    // Elle existe → récupérer son ID
    $row = $result->fetch_assoc();
    $categorieId = $row['categorie_id'];
} else {
    // Elle n'existe pas → l'insérer
    $stmtCategorie= $conn->prepare("INSERT INTO categorie (nom) VALUES (?)");
    $stmtCategorie->bind_param("s",$categorie_nom);
    $stmtCategorie->execute();
    $categorieId = $stmtCategorie->insert_id;
}


    $stmt = $conn->prepare("INSERT INTO jeucategorie (jeu_id, categorie_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $jeu_id, $categorieId);
    $stmt->execute();

    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");


// Insérer dans la table 'boite'
$stmt = $conn->prepare("INSERT INTO boite (jeu_id, etat, localisation_id, collection_id, code_barre, n_boite) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isiisi", $jeu_id, $etat, $localisation_id, $collection_id, $code_barre, $nombre_exemplaires);
$stmt->execute();


// Fermer la connexion à la base de données
$conn->close();


// Redirection selon l'origine du formulaire
$source = $_POST['source'] ?? '';

if ($source === 'admin') {
    header("Location: dashboard_admin.php");
} else {
    header("Location: dashboard_gestionnaire.php");
}
exit;
?>
