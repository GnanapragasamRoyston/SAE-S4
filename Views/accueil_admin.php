<?php
require_once "../Models/Model.php"; 

// Tester la connexion à la base de données
try {
    $db = Model::getModel()->getConnection();
    $query = $db->query("SELECT 1");

    // Si la connexion est réussi afficher un message
    echo "<script>console.log('Connexion réussie à la base de données.');</script>";  // Affiche dans la console du navigateur
} catch (PDOException $e) {
    // Sinon afficher un message d'erreur
    echo "<script>console.log('Erreur de connexion : " . $e->getMessage() . "');</script>";  // Affiche dans la console du navigateur
}

?>

<?php
require '../Controllers/PHPMailer-master/src/Exception.php';
require '../Controllers/PHPMailer-master/src/PHPMailer.php';
require '../Controllers/PHPMailer-master/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$successMessage = '';
$errorMessage = '';

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $prenom = $_POST['prenom'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

  
    if (empty($prenom) || empty($nom) || empty($email) || empty($message)) {
        $errorMessage = "Erreur : Tous les champs sont obligatoires !";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Erreur : L'adresse email n'est pas valide !";
    } else {
        // Création de l'objet PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  
            $mail->SMTPAuth = true;
            $mail->Username = 'uspnjeuxsociete@gmail.com'; 
            $mail->Password = 'jfyuyfpmsfjdmogo';     
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;  // Port pour STARTTLS

            // Configuration de l'email
            $mail->setFrom('uspnjeuxsociete@gmail.com', 'Formulaire Contact');
            $mail->addAddress('uspnjeuxsociete@gmail.com');  
            $mail->addReplyTo($email, $prenom . ' ' . $nom);

            $mail->isHTML(true);
            $mail->Subject = 'Nouveau message depuis le formulaire';
            $mail->Body = "<h1>Nouveau message</h1>
                           <p><strong>Prénom :</strong> $prenom</p>
                           <p><strong>Nom :</strong> $nom</p>
                           <p><strong>Email :</strong> $email</p>
                           <p><strong>Message :</strong><br>$message</p>";
            $mail->AltBody = "Prénom: $prenom\nNom: $nom\nEmail: $email\nMessage:\n$message";

            $mail->send();
            $successMessage = "Le message a été envoyé avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur : Le message n'a pas pu être envoyé. {$mail->ErrorInfo}";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alkatra:wght@400..700&display=swap" rel="stylesheet">
    <title>Université Sorbonne Paris Nord</title>
    <link rel="stylesheet" href="../Content/CSS/style.css">    
</head>
<body>
    <div class='logo'>
        <br>
        <a href="../Views/accueil_admin.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil_admin.php"><h1 class='SPN'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="../Views/accueil_admin.php" class="nav-item active">accueil</a>
    <a href="../Views/search_games_admin.php" class="nav-item">Jeux</a>
    <a href="../Views/dashboard_admin.php" class="nav-item">Compte</a>
    </nav>


    <div class='Search'>
    <form action='search_games.php' method='GET'>
        <h1>Collection de<br> Sorbonne Paris Nord</h1>
        <input class="recherche" type='text' name='req' placeholder='Vous cherchez un jeu ?' required>
        <button class="recherche" type="submit" >Rechercher</button>
    </form>
    </div>

    <div class='Liste-jeux'>
        <h1 class='h-jeux'>Nos jeux du moment</h1>
        <p class='p-jeux'>Découvrez nos jeux les plus empruntés du moment</p>
        <br>
        <div class='jeux'>
            <div class='jeu'>
                <img src='../Content/IMG/monopoly.jpg' alt='Monopoly'>
                <p>Monopoly</p>
                <p>Hasbro - Français</p>
            </div>
            <div class='jeu'>
                <img src='../Content/IMG/uno.png' alt='UNO'>
                <p>UNO</p>
                <p>Mattel - Français</p>
            </div>
            <div class='jeu'>
                <img src='../Content/IMG/puissance-4-classique-.jpg' alt='Puissance 4'>
                <p>Puissance 4</p>
                <p>Hasbro - Français</p>
            </div>
            <div class='jeu'>
                <a href='../Views/search_games.php'><img src='../Content/IMG/yellow.jpeg' alt='Yellow'></a>
                <p id='p2'>Découvrez plus de jeux</p>
            </div>
            <div class='fleche'>
                <img src='../Content/IMG/fleche bas.png' alt='Fleche Bas'>
            </div>
        </div>
    </div>

    <div class='red'>
        <img src='../Content/IMG/chat.png'>
    </div>

    <div class='blue'>
    </div>

    <div class='form-contact'>
        <h2>Nous contacter</h2>
        <p>Veuillez remplir le formulaire ci-dessous pour nous envoyer un message. Nous vous répondrons dans les plus brefs délais.</p>

        <form action="../Views/accueil_admin.php" method="post">
            <input type="text" name="prenom" placeholder="Prénom *" required>
            <input type="text" name="nom" placeholder="Nom de famille *" required>
            <input type="email" name="email" placeholder="E-mail *" required>
            <textarea name="message" placeholder="Laissez-nous un message..." rows="6" required></textarea>
            <button type="submit">Envoyer</button>
            
            <?php if (!empty($successMessage)): ?>
                <div style="color: green; font-weight: bold;">
                    <?php echo $successMessage; ?>
                </div>
            <?php elseif (!empty($errorMessage)): ?>
                <div style="color: red; font-weight: bold;">
                    <?php echo "<br>" . $errorMessage; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class='fin-page'>
    <a href="../Views/accueil_admin.php"><img src='../Content/IMG/carre-rouge.jpg'></a>
    <h1>Sorbonne Paris <br>Nord</h1>
        <h2 id='t-h2'>Une expérience de jeu immersive</h2>
        <br>
        <hr class='ligne'>
        <div class='fin-contact'>
            <h3>Contact</h3>
            <br>
            <p>sorbonne-paris-nord@iutv-paris13.fr</p>
            <br>
            <p>Tél : 01 49 40 30 00</p>
            <br>
            <p>99 Av.Jean Baptiste Clément, 93430</p>
            <br>
            <p>Villetaneuse</p>
        </div>

        <div class='fin-navigation'>
            <br>
            <h3>Navigation</h3>
            <br>
            <p>Jeux</p>
            <br>
            <p>Carrières</p>
            <br>
            <p>A propos</p>
            <br>
            <p>Contact</p>
            <br>
            <p>Politique de confidentialité</p>
            <br>
            <p>Termes et conditions</p>
            <br>
            <p>Politique de cookies</p>
            <br>
            <p>Mention légales</p>
        </div>

        <div class='fin-retrouvez'>
            <h3>Retrouvez-nous sur :</h3>
            <br>
            <p><a id='lien' href='https://www.univ-spn.fr/' style='text-decoration: none;'>https://www.univ-spn.fr/</a></p>
        </div>
    </div>
</body>
</html>