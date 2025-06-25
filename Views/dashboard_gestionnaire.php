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

// Variables de recherche
$recherche_titre = '';
$recherche_id = '';
$jeux_trouves = [];

// Vérifier si un formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['recherche_titre']) && !empty($_POST['recherche_titre'])) {
        $recherche_titre = '%' . $_POST['recherche_titre'] . '%';
    }

    if (isset($_POST['recherche_id']) && is_numeric($_POST['recherche_id']) && $_POST['recherche_id'] > 0) {
        $recherche_id = $_POST['recherche_id'];  
    }

    if ($recherche_titre !== '' || $recherche_id !== '') {
        $sql_jeux = "SELECT j.jeu_id, j.titre, j.date_parution_debut, j.date_parution_fin, j.version, j.nombre_de_joueurs, j.age_indique, b.boite_id, b.etat, l.localisation_nom, c.nom AS collection_nom
            FROM Jeux j
            LEFT JOIN Boite b ON j.jeu_id = b.jeu_id
            LEFT JOIN Localisation l ON b.localisation_id = l.localisation_id
            LEFT JOIN Collection c ON b.collection_id = c.collection_id";

        $conditions = [];
        $params = [];

        if ($recherche_titre) {
            $conditions[] = "j.titre LIKE ?";
            $params[] = $recherche_titre;
        }

        if ($recherche_id) {
            $conditions[] = "j.jeu_id = ?";
            $params[] = $recherche_id;
        }

        if (count($conditions) > 0) {
            $sql_jeux .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql_jeux .= " ORDER BY j.titre";
        $stmt = $conn->prepare($sql_jeux);

        if (count($params) > 0) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $jeux_trouves = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Supprimer un jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $jeu_id = $_POST['jeu_id'];

    $sql_delete = "DELETE FROM Jeux WHERE jeu_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $jeu_id);

    if ($stmt_delete->execute()) {
        $_SESSION['message'] = "Jeu supprimé avec succès.";
    } else {
        $_SESSION['message'] = "Erreur lors de la suppression du jeu.";
    }
    $stmt_delete->close();
    header("Location: dashboard_gestionnaire.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="../Content/CSS/dashboard_style.css">
</head>
<body>

<div class='logo'>
        <a href="../Views/accueil.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
        <a href="../Views/accueil.php" class="nav-item">Accueil</a>
        <a href="../Views/search_games.php" class="nav-item">Jeux</a>
        <?php
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            $role = $_SESSION['role'];
            if ($role === 'Lecteur') {
                echo '<a href="../Views/dashboard_lecteur.php" class="nav-item">Compte</a>';
            } elseif ($role === 'Gestionnaire') {
                echo '<a href="../Views/dashboard_gestionnaire.php" class="nav-item">Compte</a>';
            } elseif ($role === 'Admin') {
                echo '<a href="../Views/dashboard_admin.php" class="nav-item">Compte</a>';
            }
        } else {
            echo '<a href="../Views/connexion.php" class="nav-item active">Compte</a>';
        }
        ?>
    </nav>

    <br>
    <br>
    <div class="container">
        <div class="header">
            <h1>Bienvenue, <?php echo htmlspecialchars($prenom) . " " . htmlspecialchars($nom); ?> !</h1>
            <a href='logout.php' class="btn-deconnexion">Déconnexion</a>
        </div>

        <br><br>


<?php

// Vérifier si le formulaire doit être affiché
if (isset($_POST['toggleForm'])) {
    // Inverser l'état de la variable (afficher ou masquer le formulaire)
    $_SESSION['showForm'] = !isset($_SESSION['showForm']) || $_SESSION['showForm'] == false;
}

// Vérifier si le formulaire doit être affiché
$showForm = isset($_SESSION['showForm']) && $_SESSION['showForm'] == true;
?>

<!-- Bouton pour afficher/masquer le formulaire -->
<form method="POST" action="">
    <button type="submit" name="toggleForm">
        <?php echo $showForm ? 'Masquer le formulaire' : 'Ajouter un jeu'; ?>
    </button>
</form>

<!-- Formulaire pour ajouter un nouveau jeu -->
<?php if ($showForm): ?>

    <form method="POST" action="../Views/insert.php" class="form-ajouter-jeu">
    <h2>Ajouter un nouveau jeu</h2>
    
    <!-- Titre -->
    <div class="form-group">
        <label for="titre">Titre :</label>
        <input type="text" name="titre" id="titre" placeholder="Titre du jeu" required>
    </div>

    <!-- Date de parution -->
    <div class="form-group">
        <label for="date_parution_debut">Date de parution début :</label>
        <input type="date" name="date_parution_debut" id="date_parution_debut">
    </div>

    <!-- Date de fin -->
    <div class="form-group">
        <label for="date_parution_fin">Date de parution fin :</label>
        <input type="date" name="date_parution_fin" id="date_parution_fin">
    </div>

    <!-- Editeur -->
    <div class="form-group">
        <label for="editeur">Éditeur :</label>
        <input type="text" name="editeur" id="editeur" placeholder="Éditeur" required>
    </div>

    <!-- Auteur -->
    <div class="form-group">
        <label for="auteur">Auteur :</label>
        <input type="text" name="auteur" id="auteur" placeholder="Auteur" required>
    </div>

    <!-- Version -->
    <div class="form-group">
        <label for="version">Version :</label>
        <input type="text" name="version" id="version" placeholder="Version" required>
    </div>

    <div class="form-group">
        <label for="nombre_de_joueurs">Nombre de joueurs :</label>
        <input type="number" name="nombre_de_joueurs" id="nombre_de_joueurs" placeholder="Nombre de joueurs" required>
    </div>

    <div class="form-group">
        <label for="age_indique">Âge indiqué :</label>
        <select name="age_indique" id="age_indique" required>
            <option value="1+">1+</option>
            <option value="2+">2+</option>
            <option value="3+">3+</option>
            <option value="4+">4+</option>
            <option value="5+">5+</option>
            <option value="6+">6+</option>
            <option value="7+">7+</option>
            <option value="8+">8+</option>
            <option value="9+">9+</option>
            <option value="10+">10+</option>
            <option value="11+">11+</option>
            <option value="12+">12+</option>
            <option value="13+">13+</option>
            <option value="14+">14+</option>
            <option value="15+">15+</option>
            <option value="16+">16+</option>
            <option value="17+">17+</option>
            <option value="18+">18+</option>
            <option value="NR">NR</option>
        </select>
    </div>

    <!-- Mots clés -->
    <div class="form-group">
        <label for="mots_cles">Mots clés :</label>
        <input type="text" name="mots_cles" id="mots_cles" placeholder="Mots clés">
    </div>

    <!-- Mécanisme -->
    <div class="form-group">
        <label for="mecanisme_id">Mécanisme :</label>
        <select name="mecanisme_id" id="mecanisme_id" required>
            <!-- Options récupérées dynamiquement depuis la base -->
            <?php

            // Récupérer tous les mécanismes disponibles
            $sql = "SELECT * FROM mecanisme";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['mecanisme_id'] . "'>" . $row['nom'] . "</option>";
                }
            }
            ?>
        </select>
    </div>


    <div class="form-group">
        <label for="information_date">Informations supplémentaires :</label>
        <input type="text" name="information_date" id="information_date" placeholder="Information supplémentaires">
    </div>

    <h3>Boîte</h3>
    
    <!-- État de la boîte -->
    <div class="form-group">
        <label for="etat">État de la boîte :</label>
        <select name="etat" id="etat" required>
            <option value="Neuf">Neuf</option>
            <option value="Bon état">Bon état</option>
            <option value="Usé">Usé</option>
        </select>
    </div>

    <!-- Ajouter un état personnalisé -->

    <!-- Code barre -->
    <div class="form-group">
        <label for="code_barre">Code barre :</label>
        <input type="text" name="code_barre" id="code_barre" placeholder="Code barre" required>
    </div>

    <!-- Collection -->
    <div class="form-group">
        <label for="collection_id">Collection :</label>
        <select name="collection_id" id="collection_id" required>
            <!-- Options récupérées dynamiquement depuis la base -->
            <?php


            // Récupérer toutes les collections disponibles
            $sql = "SELECT * FROM collection";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['collection_id'] . "'>" . $row['nom'] . "</option>";
                }
            }

            ?>
        </select>
    </div>

    <!-- Localisation -->
    <div class="form-group">
        <label for="localisation_id">Localisation :</label>
        <select name="localisation_id" id="localisation_id" required>
            <!-- Options récupérées dynamiquement depuis la base -->
            <?php

            // Récupérer toutes les localisations disponibles
            $sql = "SELECT * FROM localisation";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['localisation_id'] . "'>" . $row['localisation_nom'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
    </div>


    <div class="form-group">
        <label for="nombre_exemplaires">Nombre d'exemplaires :</label>
        <input type="number" name="nombre_exemplaires" id="nombre_exemplaires" value="1" min="1" required>
    </div>
    

    <button type="submit">Ajouter le jeu</button>
</form>


<?php endif; ?>

<form method="POST" action="../Views/dashboard_gestionnaire.php">
            <input type="text" name="recherche_titre" placeholder="Rechercher par titre" value="<?= isset($_POST['recherche_titre']) ? htmlspecialchars($_POST['recherche_titre']) : '' ?>">
            <input type="number" name="recherche_id" placeholder="Rechercher par ID" value="<?= isset($_POST['recherche_id']) ? htmlspecialchars($_POST['recherche_id']) : '' ?>">
            <button type="submit">Rechercher</button>
        </form>

<!-- Affichage des résultats -->
<h2>Liste des jeux :</h2>

<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($jeux_trouves)): ?>
    <table class="tableau-jeux">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Date de parution</th>
                <th>Âge indiqué</th>
                <th>Version</th>
                <th>État de la boîte</th>
                <th>Collection</th>
                <th>Localisation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jeux_trouves as $jeu): ?>
                <tr>
                    <td><?= htmlspecialchars($jeu['jeu_id']) ?></td>
                    <td><?= htmlspecialchars($jeu['titre']) ?></td>
                    <td><?= $jeu['date_parution_debut'] ? htmlspecialchars($jeu['date_parution_debut']) : 'Inconnue' ?></td>
                    <td><?= htmlspecialchars($jeu['age_indique']) ?></td>
                    <td><?= htmlspecialchars($jeu['version']) ?></td>
                    <td><?= htmlspecialchars($jeu['etat'] ?? '') ?></td>
                    <td><?= htmlspecialchars($jeu['collection_nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($jeu['localisation_nom'] ?? '') ?></td>
                    <td>
                        <form method="GET" action="../Views/modifier_jeu.php">
                            <input type="hidden" name="jeu_id" value="<?= $jeu['jeu_id'] ?>">
                            <button type="submit">Modifier</button>
                        </form>
                        <form method="POST" action="../Views/dashboard_gestionnaire.php" onsubmit="return confirm('Confirmer la suppression de ce jeu ?');">
                            <input type="hidden" name="delete_game" value="1">
                            <input type="hidden" name="jeu_id" value="<?= $jeu['jeu_id'] ?>">
                            <button type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($jeux_trouves)): ?>
    <p>Aucun jeu trouvé.</p>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <p>Aucune recherche effectuée. Utilisez le formulaire pour rechercher des jeux.</p>
<?php endif; ?>

    </div>
</body>
</html>
