<?php
require_once './header.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration du rapport d'erreurs
ini_set('display_errors', 0);  // Désactive l'affichage des erreurs (sécurité)
ini_set('log_errors', 1);      // Active la journalisation des erreurs
ini_set('error_log', __DIR__ . '/../logs/errors/signup-errors.log');
error_reporting(E_ALL);

try {
    // Vérification de la méthode HTTP (doit être POST)
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ]);
        exit();
    }

    // Configuration du fuseau horaire
    date_default_timezone_set('Africa/Casablanca');
    $date = new DateTime();
    $sign_date = $date->format("Y-m-d H:i:s");

    // Récupération et nettoyage des données POST
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
    $prenom = isset($_POST['prenom']) ? trim(htmlspecialchars($_POST['prenom'], ENT_QUOTES, 'UTF-8')) : '';
    if (empty($prenom) || !preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/', $prenom)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $nom = isset($_POST['nom']) ? trim(htmlspecialchars($_POST['nom'], ENT_QUOTES, 'UTF-8')) : '';
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{0,50}$/', $nom)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $email = isset($_POST['email']) ? trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8')) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $fingerprint = isset($_POST['fingerprint']) ? trim(htmlspecialchars($_POST['fingerprint'], ENT_QUOTES, 'UTF-8')) : '';
    if (empty($fingerprint)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $sign_ip = $_SERVER['REMOTE_ADDR'];
    if (empty($sign_ip) || !filter_var($sign_ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $new_sign_id = bin2hex(random_bytes(32));

    $sign_id = isset($_COOKIE['sign_id']) ? $_COOKIE['sign_id'] : null;
    if ($sign_id !== null && !preg_match('/^[a-fA-F0-9]+$/', $sign_id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation des données requises
    if (empty($prenom) || empty($email) || empty($fingerprint)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Tous les champs sont obligatoires'
        ]);
        exit();
    }

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Format d\'email invalide'
        ]);
        exit();
    }
    $jsonFile = __DIR__ . '/admin/system_lock.json';
    $lockType = 'verrouillage_inscription';

    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $lockData = json_decode($jsonData, true);
        
        // Vérifier si le décodage a réussi et si l'option est activée
        if (json_last_error() === JSON_ERROR_NONE && isset($lockData[$lockType]) && $lockData[$lockType] === true) {

            // Pour une API JSON
            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                http_response_code(403); // Forbidden
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cette fonctionnalité est temporairement désactivée.'
                ]);
                exit();
            }
            
            // Pour une page HTML
            http_response_code(403); // Forbidden
            echo json_encode([
                'status' => 'error',
                'message' => 'L\'inscription est verrouillé.'
            ]);
            exit();
        }
    }

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";

    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'status' => 'error',
            'message' => 'Service temporairement indisponible'
        ]);
        exit();
    }
    // Vérification si l'email existe déjà
    $query_email = "SELECT * FROM users WHERE email = ?";
    $stmt_email = mysqli_prepare($conn, $query_email);
    if (!$stmt_email) {
        throw new Exception('Erreur de préparation de la requête email');
    }

    mysqli_stmt_bind_param($stmt_email, "s", $email);
    mysqli_stmt_execute($stmt_email);
    mysqli_stmt_store_result($stmt_email);

    if (mysqli_stmt_num_rows($stmt_email) > 0) {
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'error',
            'message' => 'Cet email est déjà enregistré'
        ]);
        mysqli_stmt_close($stmt_email);
        mysqli_close($conn);
        exit();
    }
    mysqli_stmt_close($stmt_email);

    // Vérification du fingerprint, IP ou sign_id
    $query_fingerprint_ip = "SELECT user_id, sign_date FROM users WHERE fingerprint = ? OR sign_id = ?";
    $stmt_fingerprint_ip = mysqli_prepare($conn, $query_fingerprint_ip);
    if (!$stmt_fingerprint_ip) {
        throw new Exception('Erreur de préparation de la requête fingerprint');
    }

    mysqli_stmt_bind_param($stmt_fingerprint_ip, "ss", $fingerprint, $sign_id);
    mysqli_stmt_execute($stmt_fingerprint_ip);
    mysqli_stmt_store_result($stmt_fingerprint_ip);

    if (mysqli_stmt_num_rows($stmt_fingerprint_ip) > 0) {
        mysqli_stmt_bind_result($stmt_fingerprint_ip, $user_id, $existing_sign_date);
        mysqli_stmt_fetch($stmt_fingerprint_ip);
        $existing_sign_date = strtotime($existing_sign_date);
        $current_time = strtotime($sign_date);
        $time_diff = $current_time - $existing_sign_date;

        if ($time_diff < 86400) { // 24 heures en secondes
            http_response_code(429); // Too Many Requests
            echo json_encode([
                'status' => 'error',
                'message' => 'Vous venez de vous inscrire, veuillez patienter'
            ]);
            mysqli_stmt_close($stmt_fingerprint_ip);
            mysqli_close($conn);
            exit();
        }
    }
    mysqli_stmt_close($stmt_fingerprint_ip);

    // Si toutes les validations passent
    http_response_code(200); // OK
    echo json_encode([
        'status' => 'success',
        'message' => 'VALID_FOR_NEXT_STEP'
    ]);

} catch (Exception $e) {
    error_log('Erreur inscription: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Une erreur interne est survenue'
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>