<?php
/**
 * Script de modification de mot de passe utilisateur
 * 
 * Ce script gère la modification du mot de passe d'un utilisateur connecté
 * après vérification de l'ancien mot de passe et de la validité de la session.
 * 
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */

// Configuration des erreurs
require_once './header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/modify-password-errors.log');
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
    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : "";
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : "";
    $session_token = isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : "";

        // Validation ancien mot de passe
    if (empty($old_password) || !preg_match('/(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9@\$#!%\*\?&\-_\+=\^\(\)]{8,128}/', $old_password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation nouveau mot de passe
    if (empty($new_password) || !preg_match('/(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9@\$#!%\*\?&\-_\+=\^\(\)]{8,128}/', $new_password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation session token 
    if (empty($session_token) || !preg_match('/^[a-fA-F0-9]+$/', $session_token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Sanitize toutes les variables
    $old_password = sanitizeString($old_password);
    $new_password = sanitizeString($new_password);
    $session_token = sanitizeString($session_token);
        
    // Validation des entrées
    if (empty($old_password) || empty($new_password)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont obligatoires.']);
        exit();
    }
    
    if (empty($session_token)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Session non valide. Veuillez vous reconnecter.']);
        exit();
    }
    
    if ($old_password === $new_password) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.']);
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
    
    try {
        // Vérification de la validité de la session
        $query = "SELECT s.user_id, u.email, u.password FROM sessions s
                  JOIN users u ON s.user_id = u.user_id
                  WHERE s.session_token = ? AND s.expired_at > ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête de vérification de session");
        }
        
        mysqli_stmt_bind_param($stmt, "ss", $session_token, $current_time);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Erreur d'exécution de la requête de vérification de session");
        }
        
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $user_id, $email, $hashed_password);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            // Vérification de l'ancien mot de passe
            if (password_verify($old_password, $hashed_password)) {
                // Hachage du nouveau mot de passe
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Mise à jour du mot de passe
                $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                if (!$update_stmt) {
                    throw new Exception("Erreur de préparation de la requête de mise à jour du mot de passe");
                }
                
                mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $user_id);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Erreur d'exécution de la requête de mise à jour du mot de passe");
                }
                
                mysqli_stmt_close($update_stmt);
                
                http_response_code(200); // OK
                echo json_encode(['status' => 'success', 'message' => 'Mot de passe modifié avec succès.']);
            } else {
                http_response_code(401); // Unauthorized
                echo json_encode(['status' => 'error', 'message' => 'Ancien mot de passe incorrect.']);
            }
        } else {
            mysqli_stmt_close($stmt);
            http_response_code(401); // Unauthorized
            echo json_encode(['status' => 'error', 'message' => 'Session expirée ou non valide. Veuillez vous reconnecter.']);
        }
    } catch (Exception $e) {
        throw new Exception("Erreur pendant le processus de modification du mot de passe: " . $e->getMessage());
    }
    
    // Fermeture de la connexion à la base de données
    mysqli_close($conn);
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la modification du mot de passe.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans modify-password: " . $e->getMessage());
}
?>