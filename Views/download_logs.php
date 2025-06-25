<?php
session_start();

// Vérifie si l'utilisateur est connecté et a le rôle d'admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403); // Interdit
    echo "Accès refusé.";
    exit;
}

$logFile = 'logs.txt';

if (file_exists($logFile)) {
    // Force le téléchargement du fichier
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . basename($logFile) . '"');
    header('Content-Length: ' . filesize($logFile));
    flush();
    readfile($logFile);
    exit;
} else {
    echo "Le fichier de log n'existe pas.";
}
?>

