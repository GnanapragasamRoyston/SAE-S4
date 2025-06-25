<?php
session_start(); // Ajouter session_start() pour accéder aux variables de session
require_once "../Models/Model.php";  

// Tester la connexion à la base de données
try {
    $db = Model::getModel()->getConnection();
    $query = $db->query("SELECT 1");
    echo "<script>console.log('Connexion réussie à la base de données.');</script>";
} catch (PDOException $e) {
    echo "<script>console.log('Erreur de connexion : " . addslashes($e->getMessage()) . "');</script>";
}

require '../Controllers/PHPMailer-master/src/Exception.php';
require '../Controllers/PHPMailer-master/src/PHPMailer.php';
require '../Controllers/PHPMailer-master/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$successMessage = '';
$errorMessage = '';

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
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'uspnjeuxsociete@gmail.com';
            $mail->Password = 'jfyuyfpmsfjdmogo';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>
    <div class='logo'>
        <a href="../Views/accueil.php"><img id='carre-rouge' src='../Content/IMG/carre-rouge.jpg' alt='rouge'></a>
        <a href="../Views/accueil.php" style="text-decoration:none;"><h1 class='SPN pt-1'>Sorbonne Paris Nord</h1></a>
    </div>

    <nav class="nav-bar">
    <a href="accueil.php" class="nav-item active">Accueil</a>
    <a href="search_games.php" class="nav-item">Jeux</a>
    <a href="connexion.php" class="nav-item">Compte</a>
    </nav>

    <div class='Search'>
        <form action='search_games.php' method='GET'>
            <h1>Collection de<br> Sorbonne Paris Nord</h1>
            <div class="search_games">
                <input class="recherche form-control" type='text' name='query' placeholder='Vous cherchez un jeu ?' required>
                <button class="recherche btn btn-danger" type="submit">Rechercher</button>
            </div>
        </form>
    </div>

    <div class='Liste-jeux'>
        <h1 class='h-jeux'>Nos jeux du moment</h1>
        <p class='p-jeux'>Découvrez nos jeux les plus empruntés du moment</p>
        <br>
        <div class='jeux'>
            <div class='jeux-img'>
                <a href="game_details.php?jeu_id=571">
                    <div class="jeu">
                        <img src='../Content/IMG/monopoly.jpg' class="jeu-img img-fluid" alt='Monopoly'>
                    </div>
                    <p class="p-jeux">Monopoly</p>
                    <p class="p-jeux">Hasbro - Français</p>
                </a>
            </div>
            <div class="jeux-img">
                <a href="game_details.php?jeu_id=6926">
                    <div class='jeu'>
                        <img src='../Content/IMG/uno.png' class="jeu-img img-fluid" alt='UNO'>
                    </div>
                    <p class="p-jeux">UNO</p>
                    <p class="p-jeux">JLB - Français</p>
                </a>
            </div>
            <div class="jeux-img">
                <a href="game_details.php?jeu_id=751">
                    <div class='jeu'>
                        <img src='../Content/IMG/puissance-4-classique-.jpg' class="jeu-img img-fluid" alt='Puissance 4'>
                    </div>
                    <p class="p-jeux">Puissance 4 Evolution</p>
                    <p class="p-jeux">Hasbro - Français</p>
                </a>
            </div>
            <div class="jeux-img">
                <a href="../Views/search_games.php">
                    <div class='jeu'>
                        <img src='../Content/IMG/yellow.png' class="fleche">
                    </div>
                    <p class="p-jeux mt-4">Découvrez plus de jeux</p>
                </a>
            </div>
        </div>
    </div>

    <div>
        <div class="red">
            <img src="../Content/IMG/chat.png" alt="image rouge" class="img-fluid">
        </div>
        <div class="blue">
        </div>

        <form action="" method="post" class="card card-body form-contact">
            <h1 class="text-center">Formulaire de contact</h1>
            <img class="img-fluid img-contact d-block mx-auto" src="../Content/img/contact.png">
            <p class="form-desc">Veuillez remplir le formulaire ci-dessous pour nous envoyer un message. Nous vous répondrons dans les plus brefs délais.</p>
            <div class="mb-3 menu">
                <input type="text" class="form-control" name="prenom" placeholder="Prénom" required>
            </div>

            <div class="mb-3 menu">
                <input type="text" class="form-control" name="nom" placeholder="Nom" required>
            </div>

            <div class="mb-3 menu">
                <input type="email" class="form-control" name="email" placeholder="Adresse email" required>
            </div>

            <div class="mb-3 menu">
                <textarea class="form-control" name="message" placeholder="Votre message" rows="5" style="resize: none;" required></textarea>
            </div>

            <button type="submit" class="btn btn-outline-info text-black w-50 mx-auto">Envoyer</button>

            <br>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            
            <div id="messageErreur" class="alert alert-danger text-center mt-3" style="display: none;"></div>
        </form>
    </div>

    <div class="fin">
        <div class="rouge">
            <h1 class="SPN2">Sorbonne Paris Nord</h1>
        </div>
        <h2 class="t-h2">Une expérience de jeu immersive</h2>
        <hr>
    </div>   
    <div class="fin d-flex justify-content-center">
        <div class='fin-contact'>
            <h3 class="text-center">Contact</h3>
        
            <p class="text-center">sorbonne-paris-nord@iutv-paris13.fr</p>
        
            <p class="text-center">Tél : 01 49 40 30 00</p>
        
            <p class="text-center">99 Av.Jean Baptiste Clément, 93430</p>
        
            <p class="text-center">Villetaneuse</p>
        </div>
        <div class='fin-navigation'>
            <h3 class="text-center">Navigation</h3>
            
            <p class="text-center">Jeux</p>
            
            <p class="text-center">Carrières</p>
            
            <p class="text-center">A propos</p>
            
            <p class="text-center">Contact</p>
            
            <p class="text-center">Politique de confidentialité</p>
            
            <p class="text-center">Mention légales</p>
        </div>
        <div class='fin-retrouvez'>
            <h3>Retrouvez-nous sur :</h3>
            
            <p class="text-center"><a id='lien' href='https://www.univ-spn.fr/' style='text-decoration: none;'>https://www.univ-spn.fr/</a></p>
        </div>
    </div>
</body>
</html>