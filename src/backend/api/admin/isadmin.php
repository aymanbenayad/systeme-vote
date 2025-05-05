<?php
// Configuration des rapports d'erreurs
require_once '../header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/isadmin-errors.log');
error_reporting(E_ALL);
// Définir le header JSON
header('Content-Type: application/json');
try {
    // Vérifier la présence du cookie de session
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Pas admin']);
        exit();
    }
    $session_token = $_COOKIE['session_token'];
    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $dbname = "ecovision";
    // Connexion à la base de données
    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode(['status' => 'error', 'message' => 'Serveur non disponible']);
        exit();
    }
    // Configuration de la timezone
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
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
        // Session non valide ou expirée
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Pas admin']);
        exit();
    }
    // Récupérer les données de l'utilisateur
    mysqli_stmt_bind_result($stmt, $user_id, $expired_at);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    // Prolonger la session
    $expiration = clone $date;
    $expiration->modify('+1 days');
    $new_expired_at = $expiration->format("Y-m-d H:i:s");
    $update_session = "UPDATE sessions SET expired_at = ? WHERE session_token = ?";
    $stmt_update = mysqli_prepare($conn, $update_session);
    mysqli_stmt_bind_param($stmt_update, "ss", $new_expired_at, $session_token);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    // Vérifier et renvoyer le fichier admin
    $htmlFile = __DIR__.'/../../admin.html';
    if (file_exists($htmlFile)) {
        // Changer le content-type pour HTML
        header('Content-Type: text/html');
        readfile($htmlFile);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Fichier admin introuvable']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Erreur interne du serveur']);
    error_log('Erreur isadmin: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>