<?php
// Désactiver la mise en cache
require_once '../header.php';
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Configuration des rapports d'erreurs
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/timer-errors.log');
error_reporting(E_ALL);

try {
    // Vérifier la session admin
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session non authentifiée']);
        exit();
    }

    $session_token = $_COOKIE['session_token'];

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $dbname = "ecovision";

    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode(['status' => 'error', 'message' => 'Serveur non disponible']);
        exit();
    }

    // Configuration de la timezone
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));

    // Fonction de vérification de session admin
    function verifyAdminSession($conn, $session_token, $date) {
        $now_formatted = $date->format('Y-m-d H:i:s');
        
        $query = "SELECT s.user_id, s.expired_at FROM sessions s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.session_token = ? AND s.expired_at > ? AND s.user_id=1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $session_token, $now_formatted);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 0) {
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
        mysqli_close($conn);
        exit();
    }

    mysqli_close($conn);

    // Chemin vers le fichier de stockage
    $timerFile = __DIR__ . '/timer_data.json';

    // Vérification de la méthode HTTP
    $method = $_SERVER['REQUEST_METHOD'];

    // Fonction pour lire les données actuelles
    function getTimerData($timerFile) {
        if (file_exists($timerFile)) {
            $data = file_get_contents($timerFile);
            return json_decode($data, true);
        }
        
        // Valeur par défaut si le fichier n'existe pas
        $defaultData = [
            'adminStartTime' => (new DateTime('now', new DateTimeZone('Africa/Casablanca')))->format('Y-m-d\TH:i:s'),
            'targetDate' => '2025-04-01T11:30:00'
        ];
        
        // Créer le fichier avec les valeurs par défaut
        file_put_contents($timerFile, json_encode($defaultData));
        return $defaultData;
    }

    // Gestion des requêtes GET - récupérer les données du timer
    if ($method === 'GET') {
        $timerData = getTimerData($timerFile);
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'data' => $timerData
        ]);
        exit;
    }

    // Gestion des requêtes POST - mettre à jour les données du timer
    if ($method === 'POST') {
        $inputData = json_decode(file_get_contents('php://input'), true);
        if ($inputData === null) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Données JSON invalides']);
            exit();
        }

        $timerData = getTimerData($timerFile);
        
        // Mise à jour des données
        if (isset($inputData['adminStartTime'])) {
            $timerData['adminStartTime'] = $inputData['adminStartTime'];
        }
        
        if (isset($inputData['targetDate'])) {
            $timerData['targetDate'] = $inputData['targetDate'];
        }
        
        if (isset($inputData['reset']) && $inputData['reset']) {
            $timerData['adminStartTime'] = (new DateTime('now', new DateTimeZone('Africa/Casablanca')))->format('Y-m-d\TH:i:s');
            $timerData['targetDate'] = '2025-04-01T11:30:00';
        }
        
        // Sauvegarde des données
        file_put_contents($timerFile, json_encode($timerData));
        
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'data' => $timerData
        ]);
        exit;
    }

    // Méthode non autorisée
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Erreur interne du serveur']);
    error_log('Erreur timer: ' . $e->getMessage());
}
?>