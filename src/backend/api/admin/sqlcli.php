<?php
// Configuration des rapports d'erreurs
require_once '../header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/sqlcli-errors.log');
error_reporting(E_ALL);

// Définir le header JSON
header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
        exit();
    }

    // Configuration de la base de données
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $db_config = [
        'host'     => 'localhost',
        'username' => 'root',
        'database' => 'ecovision'
    ];

    // Vérifier la session admin
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session non authentifiée']);
        exit();
    }

    $session_token = $_COOKIE['session_token'];
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

    // Vérification de la session
    $conn1 = new mysqli($db_config['host'], $db_config['username'], $dbpassword, $db_config['database']);
    if (!verifyAdminSession($conn1, $session_token, $date)) {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé']);
        $conn1->close();
        exit();
    }
    $conn1->close();

    // Traitement de la requête SQL
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['query'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Requête SQL non spécifiée']);
        exit();
    }

    $query = trim($data['query']);
    $mode = isset($data['mode']) ? strtolower($data['mode']) : 'readonly';

    // Liste des commandes bloquées
    $blockedKeywords = [
        'DROP DATABASE', 'DROP SCHEMA', 'SHUTDOWN', 'TRUNCATE', 
        'GRANT', 'REVOKE', 'CREATE USER', 'ALTER USER', 'DROP USER'
    ];

    foreach ($blockedKeywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            http_response_code(403); // Forbidden
            echo json_encode([
                'status' => 'error',
                'message' => 'Requête non autorisée pour des raisons de sécurité'
            ]);
            exit();
        }
    }

    // Vérification du mode lecture seule
    if ($mode === 'readonly' && !preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|DESC)/i', $query)) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'status' => 'error',
            'message' => 'Mode lecture seule : les requêtes de modification sont désactivées'
        ]);
        exit();
    }

    // Exécution de la requête
    $conn = new mysqli($db_config['host'], $db_config['username'], $dbpassword, $db_config['database']);
    if ($conn->connect_error) {
        throw new Exception("Échec de la connexion à la base de données");
    }

    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|DESC)/i', $query)) {
        $result = $conn->query($query);
        if ($result === false) {
            throw new Exception("Erreur dans l'exécution de la requête: " . $conn->error);
        }

        $output = [];
        $fields = [];
        while ($fieldInfo = $result->fetch_field()) {
            $fields[] = $fieldInfo->name;
        }

        if (!empty($fields)) {
            $output[] = implode("\t|\t", $fields);
            $output[] = str_repeat("-", strlen(implode("\t|\t", $fields)));
        }

        while ($row = $result->fetch_assoc()) {
            $rowOutput = [];
            foreach ($row as $value) {
                $rowOutput[] = $value !== null ? $value : 'NULL';
            }
            $output[] = implode("\t|\t", $rowOutput);
        }

        $output[] = "Nombre de lignes: " . $result->num_rows;
        $result->free();
        
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => implode("\n", $output)
        ]);
    } else {
        $result = $conn->query($query);
        if ($result === false) {
            throw new Exception("Erreur dans l'exécution de la requête: " . $conn->error);
        }
        
        $message = "Requête exécutée avec succès\nLignes affectées: " . $conn->affected_rows;
        
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur interne du serveur: ' . $e->getMessage()
    ]);
    error_log('Erreur SQL CLI: ' . $e->getMessage());
}
?>