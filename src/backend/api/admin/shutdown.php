<?php
require_once '../header.php';
/**
 * Script de gestion du verrouillage du système
 *
 * Ce script permet de contrôler les options de verrouillage du système de vote
 * et stocke les paramètres dans un fichier JSON.
 *
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */

// Définition du type de contenu de la réponse
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/shutdown-errors.log');
error_reporting(E_ALL);
// Vérification de la méthode HTTP
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit();
}
if (!isset($_COOKIE['session_token'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'status' => 'error',
        'message' => 'Session non authentifiée'
    ]);
    exit();
}

$session_token = $_COOKIE['session_token'];
// Configuration de la base de données
$servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $dbname = "ecovision";
$conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'status' => 'error',
            'message' => 'Serveur non disponible'
        ]);
        exit();
    }

// Configuration de la timezone
$date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
function verifyAdminSession($conn, $session_token, $date) {
    $now_formatted = $date->format('Y-m-d H:i:s');
    
    // Vérification de la session admin
    $query = "SELECT s.user_id, s.expired_at FROM sessions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.session_token = ? AND s.expired_at > ? AND s.user_id=1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $session_token, $now_formatted);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        // Nettoyage du cookie si session invalide
        setcookie("session_token", "", [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    mysqli_stmt_bind_result($stmt, $user_id, $expired_at);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    // Prolongation de la session
    $expiration = clone $date;
    $expiration->modify('+1 days');
    $new_expired_at = $expiration->format("Y-m-d H:i:s");
    
    $update_session = "UPDATE sessions SET expired_at = ? WHERE session_token = ?";
    $stmt_update = mysqli_prepare($conn, $update_session);
    mysqli_stmt_bind_param($stmt_update, "ss", $new_expired_at, $session_token);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    return true;
}

// Vérification de la session admin
if (!verifyAdminSession($conn, $session_token, $date)) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'status' => 'error',
        'message' => 'Accès non autorisé'
    ]);
    mysqli_close($conn);
    exit();
}

try {
    // Configuration du fuseau horaire
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('Africa/Casablanca'));
    
    // Chemin vers le fichier JSON
    $jsonFile = __DIR__ . '/./system_lock.json';
    
    // Récupération des données POST
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur de décodage des données POST: " . json_last_error_msg());
    }
    
    // Vérifier la présence des champs requis
    if (!isset($postData['lockRegistration']) || !isset($postData['hideResults'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Données POST incomplètes']);
        exit();
    }
    
    // Préparer les données à enregistrer
    $lockData = [
        'verrouillage_inscription' => (bool) $postData['lockRegistration'],
        'masquer_resultat' => (bool) $postData['hideResults'],
        'verrouillage_vote' => (bool) $postData['lockVoting'], // Cette option sera implémentée par vous
        'derniere_modification' => $date->format('Y-m-d H:i:s')
    ];
    
    // Enregistrer les données dans le fichier JSON
    if (file_put_contents($jsonFile, json_encode($lockData, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Impossible d'écrire dans le fichier JSON");
    }
    
    // Retourner une réponse de succès
    http_response_code(200); // OK
    echo json_encode([
        'status' => 'success',
        'message' => 'Options de verrouillage mises à jour avec succès',
        'data' => $lockData
    ]);
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la mise à jour des options de verrouillage.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans shutdown.php: " . $e->getMessage());
}
?>