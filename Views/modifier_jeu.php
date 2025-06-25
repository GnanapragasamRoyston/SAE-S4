<?php
session_start();

// Connexion à la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'database_jeux';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Vérifier si l'identifiant du jeu est passé en GET
if (!isset($_GET['jeu_id'])) {
    die("ID du jeu manquant.");
}

$jeu_id = (int) $_GET['jeu_id'];
$source = $_GET['source'] ?? 'gestionnaire';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $version = $_POST['version'];
    $nombre_de_joueurs = $_POST['nombre_de_joueurs'];
    $age_indique = $_POST['age_indique'];
    $date_parution_debut = $_POST['date_parution_debut'] ?? null;
    $date_parution_fin = $_POST['date_parution_fin'] ?? null;
    $etat = $_POST['etat'];
    $collection_id = $_POST['collection_id'];
    $localisation_id = $_POST['localisation_id'];

    // Mise à jour dans la table Jeux
    $sql_jeu = "UPDATE Jeux SET titre=?, version=?, nombre_de_joueurs=?, age_indique=?, date_parution_debut=?, date_parution_fin=? WHERE jeu_id=?";
    $stmt_jeu = $conn->prepare($sql_jeu);
    $stmt_jeu->bind_param("ssisssi", $titre, $version, $nombre_de_joueurs, $age_indique, $date_parution_debut, $date_parution_fin, $jeu_id);
    $stmt_jeu->execute();

    // Mise à jour dans la table Boite (si existante)
    $sql_boite = "UPDATE Boite SET etat=?, collection_id=?, localisation_id=? WHERE jeu_id=?";
    $stmt_boite = $conn->prepare($sql_boite);
    $stmt_boite->bind_param("siii", $etat, $collection_id, $localisation_id, $jeu_id);
    $stmt_boite->execute();


    $source = $_POST['source'] ?? 'gestionnaire';

    if ($source === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_gestionnaire.php");
    }
    exit;

}

// Récupération des données actuelles du jeu
$sql = "SELECT j.*, b.etat, b.collection_id, b.localisation_id FROM Jeux j LEFT JOIN Boite b ON j.jeu_id = b.jeu_id WHERE j.jeu_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jeu_id);
$stmt->execute();
$result = $stmt->get_result();
$jeu = $result->fetch_assoc();

if (!$jeu) {
    die("Jeu introuvable.");
}

// Récupération des collections
$collections = $conn->query("SELECT * FROM Collection");
$localisations = $conn->query("SELECT * FROM Localisation");

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un jeu</title>
    <link rel="stylesheet" href="../Content/CSS/modifier_jeu.css">  
</head>
<body>
<div class="wrapper">
    <div class="container">
        <h1>Modifier le jeu : <?= htmlspecialchars($jeu['titre']) ?></h1>
        <form method="POST">
            <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">
            <div class="form-group">
                <label for="titre">Titre :</label>
                <input type="text" name="titre" value="<?= htmlspecialchars($jeu['titre']) ?>" required>
            </div>

            <div class="form-group">
                <label for="version">Version :</label>
                <input type="text" name="version" value="<?= htmlspecialchars($jeu['version']) ?>" required>
            </div>

            <div class="form-group">
                <label for="nombre_de_joueurs">Nombre de joueurs :</label>
                <input type="number" name="nombre_de_joueurs" value="<?= htmlspecialchars($jeu['nombre_de_joueurs']) ?>" required>
            </div>

            <div class="form-group">
                <label for="age_indique">Âge indiqué :</label>
                <input type="text" name="age_indique" value="<?= htmlspecialchars($jeu['age_indique']) ?>" required>
            </div>

            <div class="form-group">
                <label for="date_parution_debut">Date de parution début :</label>
                <input type="date" name="date_parution_debut" value="<?= date('Y-m-d', strtotime($jeu['date_parution_debut'])) ?>">
            </div>

            <div class="form-group">
                <label for="date_parution_fin">Date de parution fin :</label>
                <input type="date" name="date_parution_fin" value="<?= !empty($jeu['date_parution_fin']) && $jeu['date_parution_fin'] !== '0000-00-00' ? date('Y-m-d', strtotime($jeu['date_parution_fin'])) : '' ?>">
            </div>

            <div class="form-group">
                <label for="etat">État :</label>
                <select name="etat" required>
                    <option value="Neuf" <?= $jeu['etat'] == 'Neuf' ? 'selected' : '' ?>>Neuf</option>
                    <option value="Bon état" <?= $jeu['etat'] == 'Bon état' ? 'selected' : '' ?>>Bon état</option>
                    <option value="Usé" <?= $jeu['etat'] == 'Usé' ? 'selected' : '' ?>>Usé</option>
                </select>
            </div>

            <div class="form-group">
                <label for="collection_id">Collection :</label>
                <select name="collection_id">
                    <?php while ($c = $collections->fetch_assoc()): ?>
                        <option value="<?= $c['collection_id'] ?>" <?= $jeu['collection_id'] == $c['collection_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="localisation_id">Localisation :</label>
                <select name="localisation_id">
                    <?php while ($l = $localisations->fetch_assoc()): ?>
                        <option value="<?= $l['localisation_id'] ?>" <?= $jeu['localisation_id'] == $l['localisation_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['localisation_nom']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit">Enregistrer les modifications</button>
        </form>
        <div class="back-link">
        <a href="<?= $source === 'admin' ? 'dashboard_admin.php' : 'dashboard_gestionnaire.php' ?>" style="color: #d32f2f; font-weight: bold;">← Retour au tableau de bord</a>
        </div>
    </div>
</div>
</body>
</html>
