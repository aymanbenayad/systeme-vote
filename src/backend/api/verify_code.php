<?php
// Configuration des rapports d'erreurs
require_once './header.php';
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/verify-code-errors.log');
error_reporting(E_ALL);

// Définition de la timezone pour toutes les dates
$date = new DateTime();
$date->setTimezone(new DateTimeZone('Africa/Casablanca'));

try {
    // Vérification de la méthode HTTP dès le début
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
        exit();
    }

    // Récupération des données POST
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : "";
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $fingerprint = isset($_POST['fingerprint']) ? trim($_POST['fingerprint']) : "";
    $sign_ip = $_SERVER['REMOTE_ADDR'];
    $new_sign_id = bin2hex(random_bytes(32));
    $codeProvided = isset($_POST['code']) ? trim($_POST['code']) : "";


    // Sanitization des données
    $nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
    $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    $fingerprint = htmlspecialchars($fingerprint, ENT_QUOTES, 'UTF-8');
    $sign_ip = htmlspecialchars($sign_ip, ENT_QUOTES, 'UTF-8');
    $codeProvided = htmlspecialchars($codeProvided, ENT_QUOTES, 'UTF-8');

    // Hashage du mot de passe
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $sign_date = $date->format("Y-m-d H:i:s");

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";

    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode(['status' => 'error', 'message' => 'Serveur non disponible']);
        exit();
    }

    // Recherche du code de vérification
    $query = "SELECT code, attempts, last_date FROM codes WHERE fingerprint = ? OR email = ? ORDER BY code_id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $fingerprint, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $db_code, $attempts, $last_date);
        mysqli_stmt_fetch($stmt);
        
        $date_diff = time() - strtotime($last_date);
        if ($date_diff < 1800) { // 30 minutes
            if ($attempts > 3) {
                http_response_code(429); // Too Many Requests
                echo json_encode(['status' => 'error', 'message' => 'Trop de tentatives. Veuillez réessayer plus tard ou demander un nouveau code.']);
            } else if ($db_code == $codeProvided) {
                // Insertion de l'utilisateur
                $insert_user = "INSERT INTO users (email, password, sign_date, fingerprint, sign_ip, sign_id, nom, prenom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = mysqli_prepare($conn, $insert_user);
                mysqli_stmt_bind_param($stmt_insert, "ssssssss", $email, $hashed_password, $sign_date, $fingerprint, $sign_ip, $new_sign_id, $nom, $prenom);
                
                if (mysqli_stmt_execute($stmt_insert)) {
                    // Définition du cookie de session
                    setcookie("sign_id", $new_sign_id, [
                        'expires' => time() + 86400,
                        'path' => '/',
                        'secure' => false,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    http_response_code(201); // Created
                    echo json_encode(['status' => 'success', 'message' => 'Inscription réussie']);
                } else {
                    http_response_code(500); // Internal Server Error
                    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue']);
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                // Mise à jour des tentatives
                $new_attempts = $attempts + 1;
                $update_attempts = "UPDATE codes SET attempts = ? WHERE fingerprint = ? OR email = ?";
                $stmt_update = mysqli_prepare($conn, $update_attempts);
                mysqli_stmt_bind_param($stmt_update, "iss", $new_attempts, $fingerprint, $email);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
                
                http_response_code(401); // Unauthorized
                echo json_encode(['status' => 'error', 'message' => 'Code incorrect.']);
            }
        } else {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Code expiré. Veuillez en demander un nouveau.']);
        }
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Aucun code trouvé']);
    }

    // Nettoyage des ressources
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

} catch (Exception $e) {
    error_log('Erreur verify-code: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur interne est survenue']);
}
?>