<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'database_jeux';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php'); // Rediriger l'utilisateur s'il n'est pas connecté
    exit;
}

$id_emprunteur = $_SESSION['user_id']; // Récupérer l'ID de l'emprunteur

$jeu_id = isset($_GET['jeu_id']) ? (int)$_GET['jeu_id'] : 0;

// Récupérer les informations du jeu
$sql = "SELECT j.jeu_id, j.titre, j.version, j.nombre_de_joueurs, a.nom AS auteur_nom, e.nom AS editeur_nom, j.date_parution_debut, j.date_parution_fin, j.information_date, j.age_indique, j.mots_cles, m.nom AS mecanisme_nom, b.boite_id, b.n_boite FROM Jeux j
LEFT JOIN JeuAuteur ja ON j.jeu_id = ja.jeu_id
LEFT JOIN Auteur a ON ja.auteur_id = a.auteur_id
LEFT JOIN JeuEditeur je ON j.jeu_id = je.jeu_id
LEFT JOIN Editeur e ON je.editeur_id = e.editeur_id
LEFT JOIN JeuMecanisme jm ON j.jeu_id = jm.jeu_id
LEFT JOIN Mecanisme m ON jm.mecanisme_id = m.mecanisme_id
LEFT JOIN Boite b ON j.jeu_id = b.jeu_id
WHERE j.jeu_id = ? 
GROUP BY j.jeu_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jeu_id);
$stmt->execute();
$result = $stmt->get_result();

$game = $result->fetch_assoc();
if ($game) {
    $imageUrl = getBoardGameImage($game['titre']);
    $gameName = $game['titre'];
} else {
    $imageUrl = null;
    $gameName = "Jeu non trouvé";
}
function getBoardGameImage($gameName) {
    $searchName = urlencode($gameName);
    $searchUrl = "https://boardgamegeek.com/xmlapi2/search?query={$searchName}";



    $searchXml = @simplexml_load_file($searchUrl);
    if ($searchXml === false) {
        return null;
    }

    if (!isset($searchXml->item[0]['id'])) {
        return null;
    }
    $gameId = (string)$searchXml->item[0]['id'];
    $detailsUrl = "https://boardgamegeek.com/xmlapi2/thing?id={$gameId}&stats=1";
    $detailsXml = @simplexml_load_file($detailsUrl);

    if ($detailsXml === false || !isset($detailsXml->item->image)) {
        return null;
    }
    return (string)$detailsXml->item->image;
}


$imageUrl = getBoardGameImage($game['titre']);

$gameName=$game['titre'];

// Variable pour afficher le message de succès ou d'erreur
$message = '';

// Vérifier si l'utilisateur a déjà emprunté ce jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jeu_id'])) {
    $jeu_id = (int)$_POST['jeu_id']; // Récupérer l'ID du jeu à emprunter
    $boite_id = $game['boite_id']; // Récupérer l'ID de la boîte associée au jeu
    $n_boite = $game['n_boite']; // Nombre de boîtes disponibles pour ce jeu

    // Vérifier si le jeu a déjà été emprunté par l'utilisateur
    $sql_verification = "SELECT * FROM Pret WHERE personne_id = ? AND boite_id = ?";
    $stmt_verification = $conn->prepare($sql_verification);
    $stmt_verification->bind_param("ii", $id_emprunteur, $boite_id);
    $stmt_verification->execute();
    $result_verification = $stmt_verification->get_result();

    if ($result_verification->num_rows > 0) {
        // Le jeu a déjà été emprunté
        $message = "<div class='message-error alert alert-secondary text-center'>Vous avez déjà emprunté ce jeu.</div>";
    } else {
        // Vérifier s'il reste des boîtes disponibles
        if ($n_boite > 0) {
            // Calculer la date de retour (14 jours après la date d'emprunt)
            $date_pret = date('Y/m/d');
            $date_retour = date('Y/m/d', strtotime('+14 days'));

            // Insertion dans la table Pret (avec boite_id au lieu de jeu_id et ajout de la date_retour)
            $sql_emprunt = "INSERT INTO Pret (personne_id, boite_id, date_pret, date_retour) VALUES (?, ?, NOW(), ?)";
            $stmt_emprunt = $conn->prepare($sql_emprunt);

            $stmt_emprunt->bind_param("iis", $id_emprunteur, $boite_id, $date_retour);
            
            if ($stmt_emprunt->execute()) {
                // Réduire le nombre de boîtes disponibles de 1
                $sql_update_boite = "UPDATE Boite SET n_boite = n_boite - 1 WHERE boite_id = ?";
                $stmt_update_boite = $conn->prepare($sql_update_boite);
                $stmt_update_boite->bind_param("i", $boite_id);
                $stmt_update_boite->execute();

                $message = "<div class='message-success alert alert-success text-center'>Le jeu a été emprunté avec succès ! La date de retour est <strong> le $date_retour.</strong></div>";
            } else {
                $message = "<div class='message-error alert alert-danger text-center'>Erreur lors de l'emprunt du jeu.</div>";
            }
        } else {
            $message = "<div class='message-error alert alert-info text-black text-center'>Aucune boîte disponible pour ce jeu.</div>";
        }
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Jeu</title>
    <link href="../Content/CSS/game_details.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>
    <div class='logo'>
        <a href="../Views/accueil.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil.php" class="nav-item">Accueil</a>
    <a href="../Views/search_games.php" class="nav-item active">Jeux</a>
    <a href="../Views/dashboard_lecteur.php" class="nav-item">Compte</a>
    </nav>


    <br>
    <br>

    <div class="jeux_conteneur">
        <!-- Affichage du message de succès ou d'erreur -->
        <?php if (!empty($message)) { echo $message; } ?>

        <h1><?php echo htmlspecialchars($game['titre']); ?></h1>
        <?php if ($imageUrl): ?>
        <div style="text-align:center;">
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Image du jeu <?php echo htmlspecialchars($game['titre']); ?>" style="max-height: 300px; border: 3px solid #333; border-radius: 8px;">
        </div>
        <?php endif; ?>
        <br>
        <p><strong>Auteur :</strong> 
        <?php $auteur = htmlspecialchars($game['auteur_nom'] ?? 'Inconnu'); 
        echo rtrim($auteur, ' /'); // Supprime le '/' et les espaces en fin de chaîne
        ?>
        </p>
        <p><strong>Éditeur :</strong> 
        <?php 
        $editeur = htmlspecialchars($game['editeur_nom'] ?? 'Non spécifié');
        echo rtrim($editeur, ' /'); // Supprime le '/' et les espaces en fin de chaîne
        ?>
    </p>
        <p><strong>Version :</strong> <?php echo htmlspecialchars($game['version']); ?></p>
        <p><strong>Nombre de joueurs :</strong> <?php echo htmlspecialchars($game['nombre_de_joueurs']); ?></p>
        <p><strong>Date de parution :</strong> 
        <?php 
        if ($game['date_parution_debut'] !== '0000') {
        echo htmlspecialchars($game['date_parution_debut']);
        } else {
            echo "Inconnu";
        }

        if ($game['date_parution_fin'] !== '0000') {
            echo ' - ' . htmlspecialchars($game['date_parution_fin']);
        }
        ?>
        </p>
        <p><strong>Informations supplémentaires :</strong> <?php echo htmlspecialchars($game['information_date']); ?></p>
        <p><strong>Âge indiqué :</strong> <?php echo htmlspecialchars($game['age_indique']); ?></p>
        <p><strong>Mots clés :</strong> <?php echo htmlspecialchars($game['mots_cles']); ?></p>
        <p><strong>Mécanisme :</strong> <?php echo htmlspecialchars($game['mecanisme_nom']); ?></p>

        <form method="POST" action="">
            <input type="hidden" name="jeu_id" value="<?php echo htmlspecialchars($game['jeu_id']); ?>">
            <button type="submit" class="bouton_emprunt">Emprunter ce jeu</button>
        </form>
    </div>

    <footer>
        <div class="fin-page">
            <div class="fin-contact">
                <h3>Contact</h3>
                <p>sorbonne-paris-nord@iutv-paris13.fr</p>
                <p>Tél : 01 49 40 30 00</p>
                <p>99 Av. Jean Baptiste Clément, 93430</p>
                <p>Villetaneuse</p>
            </div>

            <div class="fin-navigation">
                <h3>Navigation</h3>
                <p>Jeux</p>
                <p>Carrières</p>
                <p>A propos</p>
                <p>Contact</p>
                <p>Politique de confidentialité</p>
                <p>Termes et conditions</p>
                <p>Politique de cookies</p>
                <p>Mentions légales</p>
            </div>

            <div class="fin-retrouvez">
                <h3>Retrouvez-nous sur :</h3>
                <p>https://www.univ-spn.fr/</p>
            </div>
        </div>
    </footer>
</body>
</html>