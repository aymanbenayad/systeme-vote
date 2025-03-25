<?php
// Exemple de condition pour une erreur 404
$pageNotFound = true; // Remplacez cette ligne par votre logique de vérification

if ($pageNotFound) {
    header("HTTP/1.0 404 Not Found"); // Envoyer un en-tête HTTP 404
    header("Location: /404.html");    // Rediriger vers la page 404.html
    exit(); // Arrêter l'exécution du script
}
?>