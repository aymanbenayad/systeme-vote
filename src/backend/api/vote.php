<?php
// Activer le rapport d'erreurs
require_once './header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/vote-errors.log');
error_reporting(E_ALL);

// Configuration de la timezone
$date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
$now_formatted = $date->format('Y-m-d H:i:s');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Méthode non autorisée.'
        ]);
        exit();
    }

    $timerFile = __DIR__ . '/admin/timer_data.json';

    // Vérifier si le cookie session_token existe
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Aucune session active. Veuillez vous connecter.'
        ]);
        exit();
    }

    // Vérifier si choice_id est fourni
    if (!isset($_POST['choice_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Choix non spécifié.'
        ]);
        exit();
    }
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
    // Récupération des données
    $session_token = isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : "";
    $choice_id = isset($_POST['choice_id']) ? intval($_POST['choice_id']) : 0;

    // Validation des données
    if (empty($session_token) || !preg_match('/^[a-fA-F0-9]+$/', $session_token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    if ($choice_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Sanitization des données
    $session_token = htmlspecialchars($session_token, ENT_QUOTES, 'UTF-8');

    $jsonFile = __DIR__ . '/admin/system_lock.json';
    $lockType = 'verrouillage_vote';

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
                'message' => 'Le vote est verrouillé.'
            ]);
            exit();
        }
    }


    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";

    // Connexion à la base de données
    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);

    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'status' => 'error',
            'message' => 'Serveur non disponible.'
        ]);
        exit();
    }

    // Vérifier si la session est valide et non expirée
    $query = "SELECT user_id, expired_at FROM sessions WHERE session_token = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $session_token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 0) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Session invalide.'
        ]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_bind_result($stmt, $user_id, $expired_at);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Vérifier si la session n'est pas expirée
    $expiry_timestamp = strtotime($expired_at);
    $current_timestamp = time();

    if ($expiry_timestamp < $current_timestamp) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Session expirée. Veuillez vous reconnecter.'
        ]);
        mysqli_close($conn);
        exit();
    }

    // Vérifier si l'utilisateur a déjà voté
    $check_vote = "SELECT vote_id FROM votes WHERE user_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_vote);
    mysqli_stmt_bind_param($stmt_check, "i", $user_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'status' => 'error',
            'message' => 'Vous avez déjà voté.'
        ]);
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt_check);

    if (file_exists($timerFile)) {
        // Charger les données du timer
        $timerData = json_decode(file_get_contents($timerFile), true);
        if (isset($timerData['targetDate'])) {
            $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));

            $targetDate = new DateTime($timerData['targetDate']);
            $targetDate->setTimezone(new DateTimeZone('Africa/Casablanca'));
            if ($date > $targetDate) {
                http_response_code(403); // Forbidden
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La période de vote est terminée.'
                ]);
                exit();
            }
        }
    }

    // Insérer le vote
    $insert_vote = "INSERT INTO votes (user_id, choice_id, timestamp) VALUES (?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $insert_vote);
    mysqli_stmt_bind_param($stmt_insert, "iis", $user_id, $choice_id, $now_formatted);

    if (mysqli_stmt_execute($stmt_insert)) {
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => 'Vote enregistré avec succès.'
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de l\'enregistrement du vote.'
        ]);
    }

    mysqli_stmt_close($stmt_insert);
    mysqli_close($conn);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Une erreur interne est survenue.'
    ]);
    error_log('Erreur dans vote.php: ' . $e->getMessage());
}
?>