<?php
session_start();
require_once '../Models/Model.php';
require_once '../Models/User.php';

function writeLog($message) {
    $file = __DIR__ . '/logs.txt';
    $date = date('Y-m-d H:i:s');
    file_put_contents($file, "[$date] $message\n", FILE_APPEND);
}

$m = Model::getModel();
$db = $m->getConnection();
$user = new User($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mail']);
    $password = $_POST['mdp'];

    if ($user->login($email, $password)) {
        $role = $_SESSION['role'];
        // Vérifier si un paramètre 'redirect' est présent dans l'URL
        if (isset($_GET['redirect'])) {
            $redirect_url = urldecode($_GET['redirect']);
            writeLog("Redirection après connexion vers : $redirect_url");
            header("Location: $redirect_url");
            exit;
        } else {
            // Redirection par défaut selon le rôle
            if ($role === 'Lecteur') {
                header("Location: dashboard_lecteur.php");
                exit;
            } elseif ($role === 'Gestionnaire') {
                header("Location: dashboard_gestionnaire.php");
                exit;
            } elseif ($role === 'Admin') {
                header("Location: dashboard_admin.php");
                exit;
            } else {
                $message = "<p style='color:red;'>Rôle inconnu.</p>";
            }
        }
    } else {
        $message = "<p style='color:red;'>Identifiants incorrects.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte</title>
    <link rel="stylesheet" href="../Content/CSS/user.css">
</head>
<body>
    <div class='logo'>
        <a href="../Views/accueil.php" class="w-25"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil.php" style="text-decoration:none;" class="w-25"><h1 class='SPN pt-1'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil.php" class="nav-item">Accueil</a>
    <a href="../Views/search_games.php" class="nav-item">Jeux</a>
    <?php
    // Vérifier si l'utilisateur est connecté
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        if ($role === 'Lecteur') {
            echo '<a href="../Views/dashboard_lecteur.php" class="nav-item active">Compte</a>';
        } elseif ($role === 'Gestionnaire') {
            echo '<a href="../Views/dashboard_gestionnaire.php" class="nav-item active">Compte</a>';
        } elseif ($role === 'Admin') {
            echo '<a href="../Views/dashboard_admin.php" class="nav-item active">Compte</a>';
        }
    } else {
        // Utilisateur non connecté : lien vers connexion.php
        echo '<a href="../Views/connexion.php" class="nav-item active">Compte</a>';
    }
    ?>
    </nav>

    <div class="container">
        <h1>Connectez-vous !</h1>
        <form class="login" method="POST" action="">
            <label>E-mail : </label>
            <input type="email" id="mail" name="mail" required>
            <br>
            <label>Mot de passe : </label>
            <input type="password" id="mdp" name="mdp" required>
            <br>
            <button type="submit">Se connecter</button>
        </form>
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        <p>Pas encore de compte ? <a href="inscription.php">Inscrivez-vous</a></p>
    </div>

    <div class="red-rectangle"></div>
</body>
</html>