<?php
// Activer le rapport d'erreurs
require_once './header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/session-errors.log');
error_reporting(E_ALL);

// Vérifier que la méthode HTTP est GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Méthode non autorisée'
    ]);
    exit();
}

try {
    // Configuration de la timezone
    date_default_timezone_set('Africa/Casablanca');
    $date = new DateTime('now');
    $now_formatted = $date->format('Y-m-d H:i:s');

    // Récupérer le paramètre texte de manière sécurisée
    $texte = isset($_GET['texte']) ? htmlspecialchars($_GET['texte'], ENT_QUOTES, 'UTF-8') : '';

    // Gestion de la déconnexion
    if ($texte == 'LogOut') {
        if (isset($_COOKIE['session_token'])) {
            $session_token = $_COOKIE['session_token'];
            
            // Configuration de la base de données
            $servername = "localhost";
            $username = "root";
            $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
            $dbname = "ecovision";
            
            try {
                // Connexion à la base de données
                $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
                if (!$conn) {
                    throw new Exception('Erreur de connexion à la base de données');
                }
                
                // Supprimer la session de la base de données
                $delete_query = "DELETE FROM sessions WHERE session_token = ?";
                $stmt = mysqli_prepare($conn, $delete_query);
                if (!$stmt) {
                    throw new Exception('Erreur de préparation de la requête');
                }
                
                mysqli_stmt_bind_param($stmt, "s", $session_token);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                
            } catch (Exception $e) {
                error_log('Erreur lors de la déconnexion: ' . $e->getMessage());
                // On continue quand même pour supprimer le cookie même si la DB a échoué
            }
            
            // Supprimer le cookie
            setcookie('session_token', '', [
                'expires' => time() - 3600, // expiration dans le passé = suppression
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
        }
        
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => 'Déconnexion réussie'
        ]);
        exit();
    }

    // Vérifier si le cookie session_token existe
    if (!isset($_COOKIE['session_token'])) {
        if ($texte == 'IsConnected') {
            http_response_code(200); // OK
            echo json_encode([
                'status' => 'error',
                'message' => false
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Aucune session active.'
            ]);
        }
        exit();
    }

    $session_token = $_COOKIE['session_token'];

    // Configuration de la base de données
    $servername = "localhost";
        $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";

    try {
        // Connexion à la base de données
        $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
        if (!$conn) {
            throw new Exception('Erreur de connexion à la base de données');
        }

        // Vérifier si la session est valide et non expirée
        $query = "SELECT user_id, expired_at FROM sessions WHERE session_token = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Erreur de préparation de la requête');
        }
        
        mysqli_stmt_bind_param($stmt, "s", $session_token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 0) {
            if ($texte == 'IsConnected') {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'error',
                    'message' => false
                ]);
            } else {
                http_response_code(401); // Unauthorized
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Session invalide.'
                ]);
            }
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
            if ($texte == 'IsConnected') {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'error',
                    'message' => false
                ]);
            } else {
                http_response_code(401); // Unauthorized
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Session expirée.'
                ]);
            }
            mysqli_close($conn);
            exit();
        }

        // À ce stade, la session est valide
        if ($texte == 'IsConnected') {
            http_response_code(200); // OK
            echo json_encode([
                'status' => 'success',
                'message' => true
            ]);
            mysqli_close($conn);
            exit();
        }

        // Récupérer les informations de l'utilisateur
        $user_query = "SELECT user_id, email, sign_date, fingerprint, sign_ip, sign_id, nom, prenom
                       FROM users WHERE user_id = ?";
        $stmt_user = mysqli_prepare($conn, $user_query);
        if (!$stmt_user) {
            throw new Exception('Erreur de préparation de la requête utilisateur');
        }
        
        mysqli_stmt_bind_param($stmt_user, "i", $user_id);
        mysqli_stmt_execute($stmt_user);
        $result = mysqli_stmt_get_result($stmt_user);

        if ($row = mysqli_fetch_assoc($result)) {
            // Ne jamais renvoyer des données sensibles
            unset($row['password']);
            unset($row['fingerprint']);
            unset($row['sign_ip']);
            
            // Gérer les différentes demandes basées sur le texte
            switch ($texte) {
                case 'UserId':
                    http_response_code(200); // OK
                    echo json_encode($row['user_id']);
                    break;
                case 'Email':
                    http_response_code(200); // OK
                    echo json_encode($row['email']);
                    break;
                case 'Nom':
                    http_response_code(200); // OK
                    echo json_encode($row['nom']);
                    break;
                case 'Prenom':
                    http_response_code(200); // OK
                    echo json_encode($row['prenom']);
                    break;
                case 'UserInfo':
                    http_response_code(200); // OK
                    echo json_encode([
                        'status' => "success",
                        'nom' => $row['nom'],
                        'prenom' => $row['prenom'],
                        'email' => $row['email']
                    ]);
                    break;
                case 'HasVoted':
                    $vote_query = "SELECT COUNT(*) as vote_count FROM votes WHERE user_id = ?";
                    $stmt_vote = mysqli_prepare($conn, $vote_query);
                    if (!$stmt_vote) {
                        throw new Exception('Erreur de préparation de la requête de vote');
                    }
                    
                    mysqli_stmt_bind_param($stmt_vote, "i", $user_id);
                    mysqli_stmt_execute($stmt_vote);
                    $result_vote = mysqli_stmt_get_result($stmt_vote);
                    $row_vote = mysqli_fetch_assoc($result_vote);
                    
                    http_response_code(200); // OK
                    echo json_encode([
                        'status' => 'success',
                        'message' => ($row_vote['vote_count'] == 1)
                    ]);
                    mysqli_stmt_close($stmt_vote);
                    break;
                default:
                    http_response_code(200); // OK
                    echo json_encode([
                        'status' => 'success',
                    ]);
                    break;
            }
        } else {
            if ($texte == 'IsConnected') {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'error',
                    'message' => false
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Utilisateur introuvable.'
                ]);
            }
        }

        mysqli_stmt_close($stmt_user);
        mysqli_close($conn);

    } catch (Exception $e) {
        error_log('Erreur dans le traitement de session: ' . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur interne du serveur'
        ]);
        exit();
    }

} catch (Exception $e) {
    error_log('Erreur globale: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur interne du serveur'
    ]);
    exit();
}
?>