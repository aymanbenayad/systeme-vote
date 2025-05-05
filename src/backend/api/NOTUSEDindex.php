<?php
// La chaîne à hasher
require_once './header.php';
$password = "e7e0e631ae0f66d49674a83429ea620222c826d69d09a169bcb62e526bbf4103";

// Hashage bcrypt avec un coût par défaut (généralement 10)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Affichage du mot de passe haché
echo $hashedPassword;
?>
