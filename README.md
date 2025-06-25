
# SAE 4.01 – Développement d'une application complexe

## Contexte du projet

L’Université Sorbonne Paris Nord possède une collection unique de plus de **17 000 jeux de société**, dont certains datent du XIXe siècle. Initialement gérée via un simple fichier Excel, cette base de données était difficilement exploitable.  

En **semestre 3**, un prototype de site web a été développé pour structurer les données et améliorer la gestion.  
En **semestre 4**, notre travail a consisté à **améliorer**, **corriger** et **finaliser** ce prototype pour produire un **site web fonctionnelle**, robuste, et extensible.

---

## Structure du projet

```plaintext
SAE-S4-main/
├── Content/              # Contient CSS et images
│   ├── CSS/
│   └── img/
├── Controllers/          # PHP et PHPMailer
│   └── PHPMailer-master/
├── Models/               # Modèles de données
├── Views/                # Vues HTML / PHP
├── database_jeux.sql     # Script SQL de création de la BDD
├── README.md             # Documentation
```

---

## Fonctionnalités principales

- **Recherche de jeux** avec filtres
- **Consultation détaillée** des jeux et de leurs exemplaires
- **Gestion des prêts** et du retour des jeux
- **Gestion des utilisateurs** avec rôles (Lecteur, Gestionnaire, Admin)
- **Connexion sécurisée à la base de données**
- **Support email** via PHPMailer
- Intégration d’**images** pour chaque jeu (prévu)

> **Améliorations par rapport à la SAE 3.01** :
> - Ajout de la **gestion des rôles** via `Personne`, `Rôle` et `Personne_Rôle`
> - Architecture MVC plus claire (Controllers, Models, Views)
> - Mise à niveau de la base vers la **BCNF**
> - Intégration de **PHPMailer** pour des notifications
> - Recherche filtrée multi-critères
> - Modifications/Suppression de jeux
> - Suivi des logs
---

## Modélisation de la base de données

Le modèle relationnel permet :
- La gestion des **jeux de société** (multi-auteurs, éditeurs, catégories, mécanismes)
- Le suivi des **exemplaires physiques** via la table `boite`
- La gestion des **prêts**, **emprunteurs** et **utilisateurs** avec **droits différenciés**

### Schéma relationnel simplifié :

```sql
JEUX(jeu_id, titre, ...), BOITE(boite_id, jeu_id, ...),
AUTEUR, EDITEUR, CATEGORIE, MECANISME, ...
PRET, EMPRUNTEUR, PERSONNE, ROLE, PERSONNEROLE
```

> Le schéma respecte les formes normales jusqu’à la **BCNF**.

---

## Installation locale avec WAMP

### Prérequis :
- [WAMP Server](https://www.wampserver.com/)
- [Python 3.x](https://www.python.org/) + `pip`
- Navigateur web moderne
- MySQL intégré avec WAMP (v8.0 recommandé)

---

### Étapes :

#### 1. Cloner le projet
```bash
git clone git clone https://github.com/votre_projet/SAE-S4-main.git

```

#### 2. Placer les fichiers
Copier le dossier `SAE-S4-main/` dans le répertoire suivant :
```
C:/wamp64/www/app/
```

#### 3. Créer et configurer la base de données

1. Lancer **WAMP Server** (icône verte)
2. Accéder à [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Créer une base de données, ex. `ludotheque`
4. Importer :
   - `sql/creation_tables.sql`
   - `sql/script_insertion.sql`

##### Si vous utilisez `LOAD DATA INFILE` :
- Déplacer `inventaire.csv` dans :
  ```
  C:/wamp64/tmp/inventaire.csv
  ```
- Modifier le chemin dans `creation_tables.sql` :
  ```sql
  LOAD DATA INFILE 'C:/wamp64/tmp/inventaire.csv' INTO TABLE ...
  ```
- Ajouter dans `my.ini` (section `[mysqld]`) :
  ```ini
  secure-file-priv=""
  ```
- Redémarrer MySQL depuis WAMP

#### 4. Configurer les identifiants MySQL
Dans `app/identifiants/identifiant.php` :
```php
<?php
$dsn = 'mysql:host=localhost;dbname=ludotheque';
$username = 'root';
$password = ''; // vide par défaut sous WAMP
?>
```

#### 5. Lancer le serveur
Accéder au site :
```
http://localhost/SAE-S4/
```
#### 6. Exécuter les scripts Python
```bash
cd scripts/nettoyage
pip install pandas
python main.py
```

## Équipe

Groupe Dyotech (BUT2 Informatique 2024/2025) :
- **ABDOUL Sajith**
- **MAHDJOUB Ciffedinne**
- **GNANAPRAGASAM Royston**
- **KESSAVANNE Dhanoush**

---

## Rapport & Ressources


- [Google Drive S301](https://drive.google.com/drive/folders/1o0HUy2CeCfMBVKCZ8CLMnOgTMujFHPqs?usp=drive_link)

---

## Technologies utilisées

- **Backend** : PHP
- **Base de données** : MySQL
- **Frontend** : HTML, CSS, JavaScript
- **Scripts** : Python (pandas)
- **Librairie email** : PHPMailer

---


