<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    exit;
}

// Vérification et récupération des valeurs de nom et prénom
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

// Récupérer les détails de tous les utilisateurs
$sql = "SELECT p.*, pr.role_id, r.role_nom
        FROM personne p
        LEFT JOIN personne_role pr ON p.personne_id = pr.personne_id
        LEFT JOIN role r ON pr.role_id = r.role_id";
$result = $conn->query($sql);

// Récupérer tous les rôles
$sql_roles = "SELECT * FROM `role`";
$result_roles = $conn->query($sql_roles);
$roles = $result_roles->fetch_all(MYSQLI_ASSOC);

// Initialiser un tableau pour les rôles et utilisateurs
$users_by_role = [];

// Récupérer les utilisateurs et organiser par rôle
while ($user = $result->fetch_assoc()) {
    $role_name = strtolower($user['role_nom']); // On prend en compte le nom du rôle en minuscules
    if (!isset($users_by_role[$role_name])) {
        $users_by_role[$role_name] = []; // Créer une entrée vide pour chaque rôle
    }
    $users_by_role[$role_name][] = $user; // Ajouter l'utilisateur au rôle spécifique
}

// Récupérer les jeux empruntés
$sql_jeux = "SELECT p.pret_id, j.titre, j.date_parution_debut, j.age_indique, p.date_pret, p.date_retour, per.nom, per.prenom
            FROM Pret p
            JOIN Boite b ON p.boite_id = b.boite_id
            JOIN Jeux j ON b.jeu_id = j.jeu_id
            JOIN Personne per ON p.personne_id = per.personne_id";
$result_jeux = $conn->query($sql_jeux);
$jeux_empruntes = $result_jeux->fetch_all(MYSQLI_ASSOC);

// Fermer la connexion à la base de données
$conn->close();

// Traitement de la modification de rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role_id'])) {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    // Reconnexion à la base de données pour la mise à jour
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connexion échouée : " . $conn->connect_error);
    }

    // Mettre à jour le rôle de l'utilisateur dans la base de données
    $sql_update = "UPDATE personne_role SET role_id = ? WHERE personne_id = ?";
    $stmt = $conn->prepare($sql_update);

    if ($stmt === false) {
        die("Erreur lors de la préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param('ii', $role_id, $user_id);  // 'ii' signifie deux entiers
    $result = $stmt->execute();

    // Vérifier si la mise à jour a réussi
    if ($result) {
        $_SESSION['message'] = "Le rôle a été mis à jour avec succès.";
    } else {
        $_SESSION['message'] = "Une erreur s'est produite lors de la mise à jour du rôle.";
    }

    // Fermer la connexion et rediriger vers le tableau de bord
    $stmt->close();
    $conn->close();

    header("Location: dashboard_admin.php"); // Redirection vers le tableau de bord
    exit;
}

// Traitement de la suppression de jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pret_id'])) {
    $pret_id = $_POST['pret_id'];

    // Reconnexion à la base de données pour la suppression
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connexion échouée : " . $conn->connect_error);
    }

    // Supprimer le jeu emprunté
    $sql_delete = "DELETE FROM Pret WHERE pret_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $pret_id);

    if ($stmt_delete->execute()) {
        $_SESSION['message'] = "Le jeu a été supprimé avec succès.";
    } else {
        $_SESSION['message'] = "Une erreur s'est produite lors de la suppression du jeu.";
    }

    $stmt_delete->close();
    $conn->close();

    header("Location: dashboard_admin.php"); // Redirection vers le tableau de bord
    exit;
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
    header("Location: dashboard_admin.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Content/CSS/dashboard_style.css">
</head>
<body>

    <div class='logo'>
        <a href="../Views/accueil_admin.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil_admin.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil_admin.php" class="nav-item">accueil</a>
    <a href="../Views/search_games_admin.php" class="nav-item">Jeux</a>
    <a href="../Views/dashboard_admin.php" class="nav-item active">Compte</a>
    </nav>

    <br>
    <br>

    <div class="container">
        <h1>Bienvenue sur votre tableau de bord, <?php echo htmlspecialchars($prenom) . " " . htmlspecialchars($nom); ?> !</h1>
        <br>

        <!-- Affichage des messages (si présents) -->
        <?php if (isset($_SESSION['message'])): ?>
            <p class="maj_valide"><?= htmlspecialchars($_SESSION['message']); ?></p>
            <?php unset($_SESSION['message']); // Supprimer le message après l'affichage ?>
        <?php endif; ?>

        <!-- Tableau des rôles -->
        <h2>Gestion des Rôles</h2>
        <?php foreach ($roles as $role): ?>
            <?php 
                $role_name = strtolower($role['role_nom']);
                $users = isset($users_by_role[$role_name]) ? $users_by_role[$role_name] : [];
            ?>

            <h3><?= htmlspecialchars($role['role_nom']) ?>s</h3>
            
            <?php if (count($users) > 0): ?>
                <table class="tableau-roles">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Rôle actuel</th>
                            <th>Modifier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['nom']) ?></td>
                                <td><?= htmlspecialchars($user['prenom']) ?></td>
                                <td><?= htmlspecialchars($user['mail']) ?></td>
                                <td><?= htmlspecialchars($user['role_nom']) ?></td>
                                <td>
                                    <form method="POST" action="dashboard_admin.php">
                                        <input type="hidden" name="user_id" value="<?= $user['personne_id'] ?>">
                                        <select name="role_id">
                                            <?php foreach ($roles as $role_option): ?>
                                                <?php
                                                    $selected = $role_option['role_id'] == $user['role_id'] ? 'selected' : '';
                                                    echo "<option value='" . $role_option['role_id'] . "' $selected>" . $role_option['role_nom'] . "</option>";
                                                ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit">Modifier</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun utilisateur trouvé dans ce rôle.</p>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Tableau des jeux empruntés -->
         <br>
        <h2>Jeux Empruntés</h2>
        <?php if (count($jeux_empruntes) > 0): ?>
            <table class="tableau-jeux">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Date de Parution</th>
                        <th>Âge Indiqué</th>
                        <th>Date d'Emprunt</th>
                        <th>Date de Retour</th>
                        <th>Emprunté par</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jeux_empruntes as $jeu): ?>
                        <tr>
                            <td><?= htmlspecialchars($jeu['titre']) ?></td>
                            <td><?= $jeu['date_parution_debut'] !== '0000' && $jeu['date_parution_debut'] !== '' ? htmlspecialchars($jeu['date_parution_debut']) : 'Inconnu' ?></td>
                            <td><?= htmlspecialchars($jeu['age_indique']) ?></td>
                            <td><?= htmlspecialchars($jeu['date_pret']) ?></td>
                            <td><?= $jeu['date_retour'] ? htmlspecialchars($jeu['date_retour']) : "Non retourné" ?></td>
                            <td><?= htmlspecialchars($jeu['prenom']) . ' ' . htmlspecialchars($jeu['nom']) ?></td>
                            <td>
                            <form method="POST" action="dashboard_admin.php">
                                <input type="hidden" name="pret_id" value="<?= $jeu['pret_id'] ?>">
                                <button type="submit" name="action" value="rendu">Rendu</button>
                            </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>

            <a href="download_logs.php" class="btn-download">
            <button style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Télécharger les logs
            </button></a>

        <?php else: ?>
            <p>Aucun jeu emprunté pour l'instant.</p>
        <?php endif; ?>
        
        <br>
        <a href='../Views/logout.php'>Déconnexion</a>
    </div>
</body>
</html>



