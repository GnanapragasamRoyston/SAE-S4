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

// Paramètres GET
$query = $_GET['query'] ?? '';
$mecanisme = isset($_GET['mecanisme']) ? (int)$_GET['mecanisme'] : null;
$joueurs = (isset($_GET['joueurs']) && $_GET['joueurs'] !== '') ? (int)$_GET['joueurs'] : null;
$date_parution_debut = isset($_GET['date_parution_debut']) ? (int)$_GET['date_parution_debut'] : null;
if ($date_parution_debut !== null && ($date_parution_debut < 1901 || $date_parution_debut > 2022)) {
    $date_parution_debut = null;
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$games_per_page = 30;
$offset = ($page - 1) * $games_per_page;
$searchTerm = "%" . $query . "%";

// Requête principale avec filtres dynamiques
$sql = "SELECT j.jeu_id, j.titre, a.nom AS auteur_nom, e.nom AS editeur_nom, j.version, j.nombre_de_joueurs, j.date_parution_debut
        FROM Jeux j
        LEFT JOIN JeuAuteur ja ON j.jeu_id = ja.jeu_id
        LEFT JOIN Auteur a ON ja.auteur_id = a.auteur_id
        LEFT JOIN JeuEditeur je ON j.jeu_id = je.jeu_id
        LEFT JOIN Editeur e ON je.editeur_id = e.editeur_id
        WHERE j.titre LIKE ?";

$params = ["s", $searchTerm];
if ($mecanisme !== null && $mecanisme > 0) {
    $sql .= " AND j.mecanisme_id = ?";
    $params[0] .= "i";
    $params[] = $mecanisme;
}
if ($joueurs !== null && $joueurs > 0) {
    $sql .= " AND j.nombre_de_joueurs = ?";
    $params[0] .= "i";
    $params[] = $joueurs;
}
if ($date_parution_debut !== null) {
    $sql .= " AND j.date_parution_debut = ?";
    $params[0] .= "i";
    $params[] = $date_parution_debut;
}

$sql .= " LIMIT ?, ?";
$params[0] .= "ii";
$params[] = $offset;
$params[] = $games_per_page;

$stmt = $conn->prepare($sql);
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();

$games = [];
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}

// Total pour pagination
$total_sql = "SELECT COUNT(*) AS total FROM Jeux j WHERE j.titre LIKE ?";
$total_params = ["s", $searchTerm];
if ($mecanisme !== null && $mecanisme > 0) {
    $total_sql .= " AND j.mecanisme_id = ?";
    $total_params[0] .= "i";
    $total_params[] = $mecanisme;
}
if ($joueurs !== null && $joueurs > 0) {
    $total_sql .= " AND j.nombre_de_joueurs = ?";
    $total_params[0] .= "i";
    $total_params[] = $joueurs;
}
if ($date_parution_debut !== null) {
    $total_sql .= " AND j.date_parution_debut = ?";
    $total_params[0] .= "i";
    $total_params[] = $date_parution_debut;
}

$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param(...$total_params);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_games = $total_row['total'];

$total_pages = ceil($total_games / $games_per_page);
$pages_to_show = 5;
$start_page = max(1, $page - floor($pages_to_show / 2));
$end_page = min($total_pages, $page + floor($pages_to_show / 2));
$pagination_base_url = '?' . http_build_query(array_merge($_GET, ['page' => '']));

// Récupération des mécanismes
$mecanismes = [];
$meca_result = $conn->query("SELECT mecanisme_id, nom FROM Mecanisme ORDER BY nom ASC");
while ($row = $meca_result->fetch_assoc()) {
    $mecanismes[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sorbonne Paris Nord - Jeux de Société</title>
    <link rel="stylesheet" href="../Content/CSS/jeux.css">
    <style>
        a { color: red; text-decoration: none; }
        a:hover { text-decoration: none; color: #bb4025; }
    </style>
</head>
<body>
    <div class='logo'>
        <a href="../Views/accueil.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg'></a>
        <a href="../Views/accueil.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
        <a href="../Views/accueil.php" class="nav-item">Accueil</a>
        <a href="search_games.php" class="nav-item active">Jeux</a>
        <a href="../Views/connexion.php" class="nav-item">Compte</a>
    </nav>

    <div class="conteneur">
        <div class="section-recherche">
        <form method="GET" action="search_games.php">
    <input type="search" name="query" placeholder="Rechercher un jeu" value="<?= htmlspecialchars($query) ?>">
    <label>Mécanisme :</label>
    <select name="mecanisme">
        <option value="">-- Tous --</option>
        <?php foreach ($mecanismes as $meca): ?>
            <option value="<?= $meca['mecanisme_id'] ?>" <?= ($mecanisme == $meca['mecanisme_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($meca['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>Nombre de joueurs :</label>
    <input type="number" name="joueurs" min="1" value="<?= htmlspecialchars($joueurs ?? '') ?>">
    <label>Date de parution (début) :</label>
    <input type="number" name="date_parution_debut" min="1901" max="2022" value="<?= htmlspecialchars($date_parution_debut ?? '') ?>">
    <button type="submit">Rechercher</button>
</form>

        </div>

        <h1>Les Jeux de Société disponibles</h1>
        <div id="conteneur-jeux">
            <?php if (!empty($games)): ?>
                <?php foreach ($games as $game): ?>
                    <div class="game-card">
                        <h2><a href="game_details.php?jeu_id=<?= $game['jeu_id'] ?>"><?= htmlspecialchars($game['titre']) ?></a></h2>
                        <p>Auteur : <?= htmlspecialchars($game['auteur_nom'] ?? 'Inconnu') ?></p>
                        <p>Éditeur : <?= htmlspecialchars($game['editeur_nom'] ?? 'Non spécifié') ?></p>
                        <p>Version : <?= htmlspecialchars($game['version']) ?></p>
                        <p>Nombre de joueurs : <?= htmlspecialchars($game['nombre_de_joueurs']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun jeu trouvé.</p>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= $pagination_base_url ?>1">Première</a>
                <a href="<?= $pagination_base_url . ($page - 1) ?>">Précédente</a>
            <?php endif; ?>
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="<?= $pagination_base_url . $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="<?= $pagination_base_url . ($page + 1) ?>">Suivante</a>
                <a href="<?= $pagination_base_url . $total_pages ?>">Dernière</a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="fin-page">
            <div class="fin-contact">
                <h3>Contact</h3>
                <p>sorbonne-paris-nord@iutv-paris13.fr</p>
                <p>Tél : 01 49 40 30 00</p>
                <p>99 Av. Jean Baptiste Clément, 93430 Villetaneuse</p>
            </div>
            <div class="fin-navigation">
                <h3>Navigation</h3>
                <p>Jeux</p><p>Carrières</p><p>A propos</p><p>Contact</p>
                <p>Politique de confidentialité</p><p>Mentions légales</p>
            </div>
            <div class="fin-retrouvez">
                <h3>Retrouvez-nous sur :</h3>
                <p>https://www.univ-spn.fr/</p>
            </div>
        </div>
    </footer>
</body>
</html>
