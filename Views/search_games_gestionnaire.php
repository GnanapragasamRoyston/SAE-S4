<?php

session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'database_jeux';

$conn = new mysqli($host, $username, $password, $dbname);

// Vérifiez la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

$query = isset($_POST['query']) ? $_POST['query'] : '';
$games_per_page = 30; 
$req = $_GET['req'] ?? '';

// Récupérer le numéro de page depuis l'URL, ou 1 par défaut
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $games_per_page;





// Requête SQL pour rechercher les jeux avec pagination
$sql = "SELECT j.jeu_id, j.titre, a.nom AS auteur_nom, e.nom AS editeur_nom, j.version, j.nombre_de_joueurs
        FROM Jeux j
        LEFT JOIN JeuAuteur ja ON j.jeu_id = ja.jeu_id
        LEFT JOIN Auteur a ON ja.auteur_id = a.auteur_id
        LEFT JOIN JeuEditeur je ON j.jeu_id = je.jeu_id
        LEFT JOIN Editeur e ON je.editeur_id = e.editeur_id
        WHERE j.titre LIKE ?
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if ($query=='') {
$searchTerm = "%" . $req . "%";
} else {
    $searchTerm = "%" . $query ."%";
}
$stmt->bind_param("sii", $searchTerm, $offset, $games_per_page);
$stmt->execute();
$result = $stmt->get_result();

$games = [];
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}

// Récupérer le nombre total de jeux pour la pagination
$total_sql = "SELECT COUNT(*) AS total FROM Jeux j WHERE j.titre LIKE ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("s", $searchTerm);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_games = $total_row['total'];



// Calculer le nombre total de pages
$total_pages = ceil($total_games / $games_per_page);

// Calcul des plages de pages pour l'affichage
$pages_to_show = 5; // Nombre de pages visibles à la fois
$start_page = max(1, $page - floor($pages_to_show / 2));
$end_page = min($total_pages, $page + floor($pages_to_show / 2));

// Assurez-vous que la plage de pages reste valide
if ($start_page < 1) {
    $start_page = 1;
    $end_page = min($total_pages, $pages_to_show);
}

if ($end_page > $total_pages) {
    $end_page = $total_pages;
    $start_page = max(1, $end_page - $pages_to_show + 1);
}
// Vérifiez si une date est fournie
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['date_parution'])) {
    $date_parution = $_GET['date_parution'];

    if ($conn && !$conn->connect_error) {
        // Préparez la requête
        $stmta = $conn->prepare("SELECT * FROM jeux WHERE date_parution_debut = ?");
        $stmta->bind_param("s", $date_parution);
        $stmta->execute();
        $result = $stmta->get_result();

        // Affichez les résultats
        echo "<h2>Résultats :</h2>";
        while ($row = $result->fetch_assoc()) {
            echo "Titre : " . htmlspecialchars($row['titre']) . "<br>";
        }
        $stmta->close();
    } else {
        echo "Connexion à la base de données fermée ou invalide.";
    }
}

// Fermez la connexion à la fin
$conn->close();



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorbonne Paris Nord - Jeux de Société</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Content/CSS/jeux.css">
    <style>
    a {
            color: red; /* Les liens seront rouges tout le temps */
            text-decoration: none; /* Supprime le soulignement des liens */
        }

        /* Soulignement au survol */
        a:hover {
            text-decoration: none;
            color: #bb4025 /* Ajoute un soulignement lors du survol */
        }
        </style>
</head>
<body>

    <div class='logo'>
        <a href="../Views/accueil_gestionnaire.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil_gestionnaire.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil_gestionnaire.php" class="nav-item">Accueil</a>
    <a href="../Views/search_games_gestionnaire.php" class="nav-item active">Jeux</a>
    <a href="../Views/dashboard_gestionnaire.php" class="nav-item">Compte</a>
    </nav>

    <div class="conteneur">
        <div class="section-recherche">
            <form method="POST" action="search_games.php">
                <input type="search" name="query" placeholder="Rechercher un jeu">
                <button type="submit">Rechercher</button>
            </form>

        </div>

        <h1>Les Jeux de Société disponibles</h1>

        <div id="conteneur-jeux">
    <?php if (!empty($games)): ?>
        <?php foreach ($games as $game): ?>
            <div class="game-card">
                <h2>
                    <a href="game_details.php?jeu_id=<?php echo $game['jeu_id']; ?>">
            
                        <?php echo htmlspecialchars($game['titre'] ?? ''); ?>
                    </a>
                </h2>
                <p>Auteur : <?php echo htmlspecialchars($game['auteur_nom'] ?? 'Inconnu'); ?></p>
                </p>
                <p>Éditeur :
                <?php 
                $editeur = htmlspecialchars($game['editeur_nom'] ?? 'Non spécifié');
                echo rtrim($editeur, ' /'); 
                ?>
                </p>
                <p>Version : <?php echo htmlspecialchars($game['version']); ?></p>
                <p>Nombre de joueurs : <?php echo htmlspecialchars($game['nombre_de_joueurs']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php elseif (isset($query) && !empty($query)): ?>
        <p>Aucun jeu trouvé.</p>
    <?php endif; ?>
</div>

        <div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=1" class="pagination-link">Première</a>
        <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">Précédente</a>
    <?php endif; ?>

    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">Suivante</a>
        <a href="?page=<?php echo $total_pages; ?>" class="pagination-link">Dernière</a>
    <?php endif; ?>
    </div>
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
