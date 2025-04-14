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
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $mail = filter_input(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
    $mdp = $_POST['mdp'];
    $tel = htmlspecialchars(trim($_POST['tel']));
    $adresse = htmlspecialchars(trim($_POST['adresse']));

    if ($mail) {
        if (!$user->checkEmailExists($mail)) {
            if ($user->register($nom, $prenom, $mail, $mdp, $tel, $adresse)) {
                $personne_id = $user->getPersonneIdByEmail($mail);
                if ($user->assignRoleToUser($personne_id, 1)) {
                    $message = "<p style='color:green;'>Inscription réussie. Vous pouvez maintenant <a href='connexion.php'>vous connecter</a>.</p>";
                } else {
                    $message = "<p style='color:red;'>Erreur lors de l'assignation du rôle. Veuillez réessayer.</p>";
                }
            } else {
                $message = "<p style='color:red;'>Erreur lors de l'inscription. Veuillez réessayer.</p>";
            }
        } else {
            $message = "<p style='color:red;'>L'email est déjà utilisé.</p>";
        }
    } else {
        $message = "<p style='color:red;'>L'email saisi est invalide.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../Content/CSS/user.css">
</head>
<body>
    <div class='logo'>
        <br>
        <a href="../Views/accueil.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
        <a href="../Views/accueil.php" class="nav-item">Accueil</a>
        <a href="../Views/search_games.php" class="nav-item">Jeux</a>
        <a href="../Views/connexion.php" class="nav-item active">Compte</a>
    </nav>

    <div class="container">
        <h1>Inscription</h1>
        <form method="POST" action="">
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" required>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" required>

            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" required>

            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mdp" required>

            <label for="tel">Numéro de téléphone :</label>
            <input type="tel" id="tel" name="tel" pattern="^0[1-9](?:[ .-]?[0-9]{2}){4}$|^[0-9]{10}$" required>

            <label for="adresse">Adresse :</label>
            <input type="text" id="adresse" name="adresse" required>

            <button type="submit">Envoyer</button>
        </form>

        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <div class="login-prompt">
            <p>Vous avez déjà un compte ? <a href="connexion.php">Connectez-vous ici</a></p>
        </div>
    </div>
</body>
</html>
