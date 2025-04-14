<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    exit;
}

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : 'Invité';
$nom = isset($_SESSION['nom']) ? $_SESSION['nom'] : '';

// Connexion à la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'database_jeux';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer les jeux empruntés par l'utilisateur
$sql = "SELECT b.code_barre, j.titre, b.etat, l.localisation_nom ,p.date_pret, p.date_retour
        FROM Pret p
        JOIN Boite b ON p.boite_id = b.boite_id
        JOIN Localisation l ON b.localisation_id = l.localisation_id
        JOIN Jeux j ON b.jeu_id = j.jeu_id
        WHERE p.personne_id= ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Stocker les jeux dans un tableau
$jeux_empruntes = $result->fetch_all(MYSQLI_ASSOC);

// Fermer la connexion à la base de donnéessajith95@gmail.com
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Jeux Empruntés</title>
    <link rel="stylesheet" href="../Content/CSS/dashboard_style.css"> 
</head>
<body>

<div class='logo'>
        <a href="../Views/accueil_lecteur.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil_lecteur.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil_lecteur.php" class="nav-item">accueil</a>
    <a href="../Views/search_games_lecteur.php" class="nav-item">Jeux</a>
    <a href="../Views/dashboard_lecteur.php" class="nav-item active">Compte</a>
    </nav>

    <br>
    <br>

    <div class="container">
        <h1>Bienvenue, <?php echo htmlspecialchars($prenom) . " " . htmlspecialchars($nom); ?> !</h1>
        <h2>Vos jeux empruntés :</h2>

        <?php if (count($jeux_empruntes) > 0): ?>
            <table class="tableau-jeux">
                <thead>
                    <tr>
                        <th>Code Barre</th>
                        <th>Titre</th>
                        <th>Etat</th>
                        <th>Où le trouver ?</th>
                        <th>Date d'emprunt</th>
                        <th>Date de retour</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jeux_empruntes as $jeu): ?>
                        <tr>
                            <td><?= htmlspecialchars($jeu['code_barre']) ?></td>
                            <td><?= htmlspecialchars($jeu['titre']) ?></td>
                            <td><?= htmlspecialchars($jeu['etat']) ?></td>
                            <td><?= htmlspecialchars($jeu['localisation_nom']) ?></td>
                            <td><?= htmlspecialchars($jeu['date_pret']) ?></td>
                            <td><?= htmlspecialchars($jeu['date_retour']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Vous n'avez emprunté aucun jeu pour l'instant.</p>
        <?php endif; ?>

        <a href='../Views/logout.php'>Déconnexion</a>
    </div>
</body>
</html>