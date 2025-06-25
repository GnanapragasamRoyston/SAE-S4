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

    // Variables de recherche
    $recherche_titre = '';
    $recherche_id = '';
    $jeux_trouves = [];

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
            <br>
            <br>            
            <?php
            // Gestion affichage / masquage formulaire
            if (isset($_POST['toggleForm'])) {
                $_SESSION['showForm'] = !isset($_SESSION['showForm']) || $_SESSION['showForm'] == false;
            }
            $showForm = isset($_SESSION['showForm']) && $_SESSION['showForm'] == true;
            ?>

            <!-- Bouton d'affichage/masquage -->
            <form method="POST" action="">
                <button type="submit" name="toggleForm">
                    <?= $showForm ? 'Masquer le formulaire' : 'Ajouter un jeu' ?>
                </button>
            </form>

            <?php if ($showForm): ?>
                <form method="POST" action="../Views/insert.php" class="form-ajouter-jeu">
                    <h2>Ajouter un nouveau jeu</h2>

                    <div class="form-group">
                        <label for="titre">Titre :</label>
                        <input type="text" name="titre" id="titre" required>
                    </div>

                    <div class="form-group">
                        <label for="date_parution_debut">Date de parution début :</label>
                        <input type="date" name="date_parution_debut" id="date_parution_debut">
                    </div>

                    <div class="form-group">
                        <label for="date_parution_fin">Date de parution fin :</label>
                        <input type="date" name="date_parution_fin" id="date_parution_fin">
                    </div>

                    <div class="form-group">
                        <label for="editeur">Éditeur :</label>
                        <input type="text" name="editeur" id="editeur" required>
                    </div>

                    <div class="form-group">
                        <label for="auteur">Auteur :</label>
                        <input type="text" name="auteur" id="auteur" required>
                    </div>

                    <div class="form-group">
                        <label for="version">Version :</label>
                        <input type="text" name="version" id="version" required>
                    </div>

                    <div class="form-group">
                        <label for="nombre_de_joueurs">Nombre de joueurs :</label>
                        <input type="number" name="nombre_de_joueurs" id="nombre_de_joueurs" required>
                    </div>

                    <div class="form-group">
                        <label for="age_indique">Âge indiqué :</label>
                        <select name="age_indique" id="age_indique" required>
                            <?php
                            for ($i = 1; $i <= 18; $i++) {
                                echo "<option value='{$i}+'>{$i}+</option>";
                            }
                            ?>
                            <option value="NR">NR</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mots_cles">Mots clés :</label>
                        <input type="text" name="mots_cles" id="mots_cles">
                    </div>

                    <div class="form-group">
                        <label for="mecanisme_id">Mécanisme :</label>
                        <select name="mecanisme_id" id="mecanisme_id" required>
                            <?php
                            $conn = new mysqli($host, $username, $password, $dbname);
                            $res = $conn->query("SELECT * FROM mecanisme");
                            while ($row = $res->fetch_assoc()) {
                                echo "<option value='{$row['mecanisme_id']}'>{$row['nom']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="information_date">Informations supplémentaires :</label>
                        <input type="text" name="information_date" id="information_date">
                    </div>

                    <h3>Boîte</h3>

                    <div class="form-group">
                        <label for="etat">État de la boîte :</label>
                        <select name="etat" id="etat" required>
                            <option value="Neuf">Neuf</option>
                            <option value="Bon état">Bon état</option>
                            <option value="Usé">Usé</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="code_barre">Code barre :</label>
                        <input type="text" name="code_barre" id="code_barre" required>
                    </div>

                    <div class="form-group">
                        <label for="collection_id">Collection :</label>
                        <select name="collection_id" id="collection_id" required>
                            <?php
                            $res = $conn->query("SELECT * FROM collection");
                            while ($row = $res->fetch_assoc()) {
                                echo "<option value='{$row['collection_id']}'>{$row['nom']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="localisation_id">Localisation :</label>
                        <select name="localisation_id" id="localisation_id" required>
                            <?php
                            $res = $conn->query("SELECT * FROM localisation");
                            while ($row = $res->fetch_assoc()) {
                                echo "<option value='{$row['localisation_id']}'>{$row['localisation_nom']}</option>";
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nombre_exemplaires">Nombre d'exemplaires :</label>
                        <input type="number" name="nombre_exemplaires" id="nombre_exemplaires" value="1" min="1" required>
                    </div>
                        
                    <input type="hidden" name="source" value="admin">
                    <button type="submit">Ajouter le jeu</button>
                </form>
            <?php endif; ?>
            <br>
                <form method="POST" action="dashboard_admin.php" style="margin-bottom: 20px;">
            <input type="text" name="recherche_titre" placeholder="Rechercher par titre" value="<?= isset($_POST['recherche_titre']) ? htmlspecialchars($_POST['recherche_titre']) : '' ?>">
            <input type="number" name="recherche_id" placeholder="Rechercher par ID" value="<?= isset($_POST['recherche_id']) ? htmlspecialchars($_POST['recherche_id']) : '' ?>">
            <button type="submit">Rechercher</button>
                </form>

            <?php if (!empty($jeux_trouves)): ?>
            <h2>Résultats de la recherche :</h2>
            <table class="tableau-jeux">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Date de parution</th>
                        <th>Version</th>
                        <th>Âge</th>
                        <th>État</th>
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
                            <td><?= htmlspecialchars($jeu['date_parution_debut']) ?></td>
                            <td><?= htmlspecialchars($jeu['version']) ?></td>
                            <td><?= htmlspecialchars($jeu['age_indique']) ?></td>
                            <td><?= htmlspecialchars($jeu['etat']) ?></td>
                            <td><?= htmlspecialchars($jeu['collection_nom']) ?></td>
                            <td><?= htmlspecialchars($jeu['localisation_nom']) ?></td>
                            <td>
                            <a href="../Views/modifier_jeu.php?jeu_id=<?= $jeu['jeu_id'] ?>&source=admin">
                                <button type="button">Modifier</button>
                            </a>
                            <form method="POST" action="../Views/dashboard_admin.php" onsubmit="return confirm('Confirmer la suppression de ce jeu ?');">
                                <input type="hidden" name="delete_game" value="1">
                                <input type="hidden" name="jeu_id" value="<?= $jeu['jeu_id'] ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p>Aucun jeu trouvé.</p>
        <?php endif; ?>

                

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
            <br>
            <a href='../Views/logout.php'>Déconnexion</a>
        </div>
    </body>
    </html>



