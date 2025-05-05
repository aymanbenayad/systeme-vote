<?php
// Content Security Policy
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; script-src 'self' https://cdnjs.cloudflare.com 'nonce-...'; style-src 'self' https://fonts.googleapis.com 'nonce-...'; img-src 'self' imageexterneici; connect-src 'self' https://api.example.com; object-src 'none'; base-uri 'self'; form-action 'self'; report-uri /csp-violation-report-endpoint;");

// Autres en-têtes de sécurité
header("X-Content-Type-Options: nosniff");

// CORS
$allowed_origin = 'https://ecovision-project.vercel.app';

// Vérifie si l'en-tête 'Origin' est présent dans la requête
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] == $allowed_origin) {
    header("Access-Control-Allow-Origin: " . $allowed_origin);
} else {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès interdit");
}
// Gérer les pré-requêtes CORS (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Répondre à la pré-requête sans faire autre chose
}
