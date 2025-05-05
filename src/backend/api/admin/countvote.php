<?php
// Configuration des rapports d'erreurs
require_once '../header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/countvote-errors.log');
error_reporting(E_ALL);

// Définir le header JSON dès le début
header('Content-Type: application/json');

try {
    // Vérifier la présence du cookie de session
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session non authentifiée']);
        exit();
    }

    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $dbname = "ecovision";

    // Connexion à la base de données
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);
    if ($conn->connect_error) {
        http_response_code(503); // Service Unavailable
        echo json_encode(['status' => 'error', 'message' => 'Connexion à la base de données échouée: ' . $conn->connect_error]);
        exit();
    }

    // Configuration de la timezone
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
    $session_token = $_COOKIE['session_token'];

    // Fonction de vérification de session admin
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
        echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé']);
        $conn->close();
        exit();
    }

    // Récupérer le nombre total de votes
    $totalVotes = 0;
    $sql = "SELECT COUNT(*) as total FROM votes";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $totalVotes = $row['total'];
    } else {
        throw new Exception("Erreur lors du comptage des votes: " . $conn->error);
    }

    // Récupérer le nombre d'utilisateurs
    $activeVoters = 0;
    $sql = "SELECT COUNT(*) as active FROM users";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $activeVoters = $row['active'];
    } else {
        throw new Exception("Erreur lors du comptage des utilisateurs: " . $conn->error);
    }

    // Récupérer le temps restant
    $remainingMinutes = 0;
    $timerFile = __DIR__ . '/timer_data.json';
    if (file_exists($timerFile)) {
        $timerData = json_decode(file_get_contents($timerFile), true);
        if (isset($timerData['targetDate'])) {
            $targetDate = new DateTime($timerData['targetDate'], new DateTimeZone('Africa/Casablanca'));
            $currentDate = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
            if ($targetDate > $currentDate) {
                $interval = $currentDate->diff($targetDate);
                $remainingMinutes = $interval->days * 24 * 60;
                $remainingMinutes += $interval->h * 60;
                $remainingMinutes += $interval->i;
            }
        }
    }

    // Préparer la réponse
    $response = [
        'status' => 'success',
        'data' => [
            'totalVotes' => $totalVotes,
            'activeVoters' => $activeVoters,
            'remainingMinutes' => $remainingMinutes
        ]
    ];

    // Envoyer la réponse
    http_response_code(200); // OK
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erreur interne du serveur: ' . $e->getMessage()
    ]);
    error_log('Erreur countvote: ' . $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>