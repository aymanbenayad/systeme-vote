<?php
require_once './header.php';
/**
 * Script de suppression de compte utilisateur
 * 
 * Ce script gère la suppression définitive d'un compte utilisateur après vérification
 * du mot de passe et de la validité de la session.
 * 
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */

// Configuration des erreurs
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/delete-account-errors.log');
error_reporting(E_ALL);

// Vérification de la méthode HTTP
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit();
}

try {
    // Configuration du fuseau horaire
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('Africa/Casablanca'));
    $current_time = $date->format("Y-m-d H:i:s");
    
    // Récupération et validation des données
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }

    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    if (empty($password) || !preg_match('/(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9@\$#!%\*\?&\-_\+=\^\(\)]{8,128}/', $password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    $safePassword = sanitizeString($password);

    $session_token = isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : "";
    
    if (empty($password)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Veuillez saisir votre mot de passe.']);
        exit();
    }
    
    if (empty($session_token)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session non valide. Veuillez vous reconnecter.']);
        exit();
    }
    
    // Configuration de la connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";
    
    // Établissement de la connexion
    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Vérification de la validité de la session
    $query = "SELECT s.user_id, u.email, u.password FROM sessions s
              JOIN users u ON s.user_id = u.user_id
              WHERE s.session_token = ? AND s.expired_at > ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête");
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $session_token, $current_time);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Erreur d'exécution de la requête");
    }
    
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $user_id, $email, $hashed_password);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        // Vérification du mot de passe
        if (password_verify($password, $hashed_password)) {
            try {
                // Début des opérations de suppression
                
                // 1. Suppression des sessions de l'utilisateur
                $delete_sessions = "DELETE FROM sessions WHERE user_id = ?";
                $session_stmt = mysqli_prepare($conn, $delete_sessions);
                if (!$session_stmt) {
                    throw new Exception("Erreur lors de la préparation de suppression des sessions");
                }
                
                mysqli_stmt_bind_param($session_stmt, "i", $user_id);
                if (!mysqli_stmt_execute($session_stmt)) {
                    throw new Exception("Erreur lors de la suppression des sessions");
                }
                mysqli_stmt_close($session_stmt);
                
                // 2. Suppression des votes de l'utilisateur
                $delete_votes = "DELETE FROM votes WHERE user_id = ?";
                $votes_stmt = mysqli_prepare($conn, $delete_votes);
                if (!$votes_stmt) {
                    throw new Exception("Erreur lors de la préparation de suppression des votes");
                }
                
                mysqli_stmt_bind_param($votes_stmt, "i", $user_id);
                if (!mysqli_stmt_execute($votes_stmt)) {
                    throw new Exception("Erreur lors de la suppression des votes");
                }
                mysqli_stmt_close($votes_stmt);
                
                // 3. Suppression de l'utilisateur
                $delete_query = "DELETE FROM users WHERE user_id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_query);
                if (!$delete_stmt) {
                    throw new Exception("Erreur lors de la préparation de suppression de l'utilisateur");
                }
                
                mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                
                if (!mysqli_stmt_execute($delete_stmt)) {
                    throw new Exception("Erreur lors de la suppression de l'utilisateur");
                }
                
                mysqli_stmt_close($delete_stmt);
                
                // Suppression du cookie de session
                setcookie("session_token", "", [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                http_response_code(200); // OK
                echo json_encode(['status' => 'success', 'message' => 'Suppression du compte.']);
                
            } catch (Exception $e) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la suppression du compte.']);
                error_log("Erreur suppression compte: " . $e->getMessage());
            }
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['status' => 'error', 'message' => 'Mot de passe incorrect.']);
        }
    } else {
        mysqli_stmt_close($stmt);
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session expirée ou non valide. Veuillez vous reconnecter.']);
    }
    
    // Fermeture de la connexion à la base de données
    mysqli_close($conn);
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur interne est survenue.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans delete-account: " . $e->getMessage());
}
?>