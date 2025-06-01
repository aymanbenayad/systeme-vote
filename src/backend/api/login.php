<?php
/**
 * Script de connexion utilisateur avec gestion de sessions sécurisées
 * 
 * Ce script gère le processus de connexion des utilisateurs, les tentatives de connexion,
 * la création et la gestion des sessions, avec une logique différente pour l'administrateur.
 * 
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */

// Configuration des erreurs
require_once './header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/login-errors.log');
error_reporting(E_ALL);


// Vérification de la méthode HTTP
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit();
}

try {
    // Configuration du fuseau horaire
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
    $now_formatted = $date->format('Y-m-d H:i:s');
    
    // Récupération et validation des données
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $fingerprint = isset($_POST['fingerprint']) ? trim($_POST['fingerprint']) : "";
    $ip = $_SERVER['REMOTE_ADDR'];

        // Validation email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation mot de passe
    if (empty($password) || !preg_match('/(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9@\$#!%\*\?&\-_\+=\^\(\)]{8,128}/', $password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation fingerprint (hexadécimal, 32 caractères)
    if (empty($fingerprint) || !preg_match('/^[a-zA-Z0-9]+$/', $fingerprint)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Validation IP
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
        exit();
    }

    // Sanitize toutes les variables
    $email = sanitizeString($email);
    $password = sanitizeString($password);
    $fingerprint = sanitizeString($fingerprint);
    $ip = sanitizeString($ip);
    
    if (empty($email) || empty($password) || empty($fingerprint)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Informations de connexion incomplètes.']);
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
        // Vérifier s'il existe une tentative précédente
        $query = "SELECT attempt_id, password_date, password_attempts FROM attempts WHERE fingerprint = ? OR ip = ? ORDER BY attempt_id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête de vérification des tentatives");
        }
        
        mysqli_stmt_bind_param($stmt, "ss", $fingerprint, $ip);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Erreur lors de l'exécution de la requête de vérification des tentatives");
        }
        
        mysqli_stmt_store_result($stmt);
        
        // Gestion des tentatives de connexion
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $attempt_id, $password_date_str, $attempts);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            $password_date = strtotime($password_date_str);
            $now_unix = strtotime($now_formatted);
            $diff = $now_unix - $password_date;
            
            if ($diff < 600) { // 10 minutes
                $new_attempts = $attempts + 1;
                
                if ($new_attempts > 5) {
                    http_response_code(429); // Too Many Requests
                    echo json_encode(['status' => 'error', 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
                    mysqli_close($conn);
                    exit();
                }
                
                // Mise à jour du nombre de tentatives
                $update = "UPDATE attempts SET password_attempts = ? WHERE attempt_id = ?";
                $stmt_update = mysqli_prepare($conn, $update);
                if (!$stmt_update) {
                    throw new Exception("Erreur de préparation de la mise à jour des tentatives");
                }
                
                mysqli_stmt_bind_param($stmt_update, "ii", $new_attempts, $attempt_id);
                
                if (!mysqli_stmt_execute($stmt_update)) {
                    throw new Exception("Erreur lors de la mise à jour des tentatives");
                }
                
                mysqli_stmt_close($stmt_update);
            } else {
                // Réinitialisation de la tentative après 10 minutes
                $reset = "UPDATE attempts SET password_date = ?, password_attempts = 1 WHERE attempt_id = ?";
                $stmt_reset = mysqli_prepare($conn, $reset);
                if (!$stmt_reset) {
                    throw new Exception("Erreur de préparation de la réinitialisation des tentatives");
                }
                
                mysqli_stmt_bind_param($stmt_reset, "si", $now_formatted, $attempt_id);
                
                if (!mysqli_stmt_execute($stmt_reset)) {
                    throw new Exception("Erreur lors de la réinitialisation des tentatives");
                }
                
                mysqli_stmt_close($stmt_reset);
            }
        } else {
            // Création d'une nouvelle entrée de tentative
            $insert = "INSERT INTO attempts (fingerprint, ip, password_date, password_attempts) VALUES (?, ?, ?, 1)";
            $stmt_insert = mysqli_prepare($conn, $insert);
            if (!$stmt_insert) {
                throw new Exception("Erreur de préparation de l'insertion des tentatives");
            }
            
            mysqli_stmt_bind_param($stmt_insert, "sss", $fingerprint, $ip, $now_formatted);
            
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Erreur lors de l'insertion des tentatives");
            }
            
            mysqli_stmt_close($stmt_insert);
        }
        
        // Processus de connexion
        $isAdmin = ($email === 'ecovision.vote@gmail.com');
        
        // Vérification des informations de connexion
        $sql = "SELECT user_id, password FROM users WHERE email = ?";
        $stmt_login = mysqli_prepare($conn, $sql);
        if (!$stmt_login) {
            throw new Exception("Erreur de préparation de la requête de connexion");
        }
        
        mysqli_stmt_bind_param($stmt_login, "s", $email);
        
        if (!mysqli_stmt_execute($stmt_login)) {
            throw new Exception("Erreur lors de l'exécution de la requête de connexion");
        }
        
        mysqli_stmt_store_result($stmt_login);
        
        if (mysqli_stmt_num_rows($stmt_login) == 1) {
            mysqli_stmt_bind_result($stmt_login, $user_id, $hashed_password);
            mysqli_stmt_fetch($stmt_login);
            mysqli_stmt_close($stmt_login);
            
            if (password_verify($password, $hashed_password)) {
                // Réinitialisation du compteur de tentatives après connexion réussie
                $reset_attempts = "UPDATE attempts SET password_attempts = 0 WHERE fingerprint = ? OR ip = ?";
                $stmt_reset_attempts = mysqli_prepare($conn, $reset_attempts);
                if (!$stmt_reset_attempts) {
                    throw new Exception("Erreur de préparation de la réinitialisation des compteurs");
                }
                
                mysqli_stmt_bind_param($stmt_reset_attempts, "ss", $fingerprint, $ip);
                
                if (!mysqli_stmt_execute($stmt_reset_attempts)) {
                    throw new Exception("Erreur lors de la réinitialisation des compteurs");
                }
                
                mysqli_stmt_close($stmt_reset_attempts);
                
                // Vérification des sessions existantes
                $check_session = "SELECT session_token, expired_at FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
                $stmt_check = mysqli_prepare($conn, $check_session);
                if (!$stmt_check) {
                    throw new Exception("Erreur de préparation de la vérification des sessions");
                }
                
                mysqli_stmt_bind_param($stmt_check, "i", $user_id);
                
                if (!mysqli_stmt_execute($stmt_check)) {
                    throw new Exception("Erreur lors de la vérification des sessions");
                }
                
                mysqli_stmt_store_result($stmt_check);
                
                $create_new_session = true;
                $session_token = "";
                
                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    mysqli_stmt_bind_result($stmt_check, $existing_token, $expiry_date);
                    mysqli_stmt_fetch($stmt_check);
                    
                    $expiry_timestamp = strtotime($expiry_date);
                    $current_timestamp = time();
                    
                    // Réutilisation de la session si elle est encore valide
                    if ($expiry_timestamp > $current_timestamp) {
                        $session_token = $existing_token;
                        $create_new_session = false;
                        
                        // Mise à jour de la date d'expiration
                        $expiration = clone $date;
                        $expiration->modify('+' . ($isAdmin ? '1' : '7') . ' days');
                        $expired_at = $expiration->format("Y-m-d H:i:s");
                        
                        $update_session = "UPDATE sessions SET expired_at = ? WHERE session_token = ?";
                        $stmt_update_session = mysqli_prepare($conn, $update_session);
                        if (!$stmt_update_session) {
                            throw new Exception("Erreur de préparation de la mise à jour de session");
                        }
                        
                        mysqli_stmt_bind_param($stmt_update_session, "ss", $expired_at, $session_token);
                        
                        if (!mysqli_stmt_execute($stmt_update_session)) {
                            throw new Exception("Erreur lors de la mise à jour de session");
                        }
                        
                        mysqli_stmt_close($stmt_update_session);
                    }
                }
                
                mysqli_stmt_close($stmt_check);
                
                // Création d'une nouvelle session si nécessaire
                if ($create_new_session) {
                    $session_token = bin2hex(random_bytes(64));
                    $created_at = $now_formatted;
                    $expiration = clone $date;
                    $expiration->modify('+' . ($isAdmin ? '1' : '7') . ' days');
                    $expired_at = $expiration->format("Y-m-d H:i:s");
                    
                    $insert_session = "INSERT INTO sessions (user_id, session_token, created_at, expired_at) VALUES (?, ?, ?, ?)";
                    $stmt_session = mysqli_prepare($conn, $insert_session);
                    if (!$stmt_session) {
                        throw new Exception("Erreur de préparation de l'insertion de session");
                    }
                    
                    mysqli_stmt_bind_param($stmt_session, "isss", $user_id, $session_token, $created_at, $expired_at);
                    
                    if (!mysqli_stmt_execute($stmt_session)) {
                        throw new Exception("Erreur lors de la création de session");
                    }
                    
                    mysqli_stmt_close($stmt_session);
                }
                
                // Configuration du cookie de session
                $cookie_expiry = time() + ($isAdmin ? 86400 : 86400 * 7); // 1 ou 7 jours
                setcookie("session_token", $session_token, [
                    'expires' => $cookie_expiry,
                    'path' => '/',
                    'secure' => false, // À passer à true en production avec HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Connexion réussie' . ($isAdmin ? ' en admin' : '') . '!'
                ]);
            } else {
                http_response_code(401); // Unauthorized
                echo json_encode(['status' => 'error', 'message' => 'Adresse mail ou mot de passe incorrect.']);
            }
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['status' => 'error', 'message' => 'Adresse mail ou mot de passe incorrect.']);
        }
    } catch (Exception $e) {
        throw new Exception("Erreur pendant le processus de connexion: " . $e->getMessage());
    }
    
    // Fermeture de la connexion à la base de données
    mysqli_close($conn);
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la connexion.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans login: " . $e->getMessage());
}
?>