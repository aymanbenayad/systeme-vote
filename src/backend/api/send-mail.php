<?php
/**
 * Contact Form Email Sending Script
 * 
 * This script handles contact form submissions, implements rate limiting,
 * validates input data, and sends emails via PHPMailer.
 * 
 * Server Requirements:
 * - PHP 7.0+
 * - MySQLi extension
 * - PHPMailer library installed via Composer
 * - Write access to log directory
 */
require_once './header.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Error handling configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/send-email-errors.log');
error_reporting(E_ALL);

// Verify request method at the beginning
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit();
}

try {
    // Load Composer's autoloader
    require '../libs/vendor/autoload.php';
    
    // Set timezone for all date operations
    date_default_timezone_set('Africa/Casablanca');
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
    $now_formatted = $date->format('Y-m-d H:i:s');
    
    // Parse incoming JSON data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Données invalides.']);
        exit();
    }
    
    // Sanitize and validate input data
    $nom = isset($data['nom']) ? htmlspecialchars(trim($data['nom'] ?? '')) : '';
    $prenom = isset($data['prenom']) ? htmlspecialchars(trim($data['prenom'] ?? '')) : '';
    $email = isset($data['email']) ? htmlspecialchars(trim($data['email'] ?? '')) : '';
    $objet = isset($data['objet']) ? htmlspecialchars(trim($data['objet'] ?? '')) : '';
    $message = isset($data['message']) ? htmlspecialchars(trim($data['message'] ?? '')) : '';
    $fingerprint = isset($data['fingerprint']) ? htmlspecialchars(trim($data['fingerprint'] ?? '')) : '';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Validate required fields
    if (empty($prenom) || empty($email) || empty($objet) || empty($message)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Veuillez remplir tous les champs obligatoires.']);
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Format d\'email invalide.']);
        exit();
    }
    
    try {
        // Database configuration
        $servername = "localhost";
        $username = "root";
        
        try {
            $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
        } catch (Exception $e) {
            error_log("Failed to read database password: " . $e->getMessage());
            exit();
        }
        
        $dbname = "ecovision";
        
        // Database connection
        $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
        
        if (!$conn) {
            throw new Exception("Database connection failed: " . mysqli_connect_error());
        }
        
        // Check for previous attempts
        $query = "SELECT attempt_id, mail_date, mail_attempts FROM attempts WHERE fingerprint = ? OR ip = ? ORDER BY attempt_id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ss", $fingerprint, $ip);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Statement execution failed: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $attempt_id, $mail_date_str, $mail_attempts);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            // Check if attempts exceed limit
            if ($mail_attempts >= 3) {
                // Check if an hour has passed since last attempt
                $mail_date = strtotime($mail_date_str);
                $now_unix = strtotime($now_formatted);
                $diff_hours = ($now_unix - $mail_date) / 3600; // Convert to hours
                
                if ($diff_hours < 1) {
                    mysqli_close($conn);
                    http_response_code(429); // Too Many Requests
                    echo json_encode(['status' => 'error', 'message' => 'Vous avez dépassé le nombre de tentatives autorisées. Veuillez réessayer plus tard.']);
                    exit();
                } else {
                    // Reset counter after 1 hour
                    $reset = "UPDATE attempts SET mail_date = ?, mail_attempts = 1 WHERE attempt_id = ?";
                    $stmt_reset = mysqli_prepare($conn, $reset);
                    
                    if (!$stmt_reset) {
                        throw new Exception("Prepare reset statement failed: " . mysqli_error($conn));
                    }
                    
                    mysqli_stmt_bind_param($stmt_reset, "si", $now_formatted, $attempt_id);
                    
                    if (!mysqli_stmt_execute($stmt_reset)) {
                        throw new Exception("Reset execution failed: " . mysqli_stmt_error($stmt_reset));
                    }
                    
                    mysqli_stmt_close($stmt_reset);
                }
            } else {
                // Increment attempts counter
                $new_attempts = $mail_attempts + 1;
                $update = "UPDATE attempts SET mail_date = ?, mail_attempts = ? WHERE attempt_id = ?";
                $stmt_update = mysqli_prepare($conn, $update);
                
                if (!$stmt_update) {
                    throw new Exception("Prepare update statement failed: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($stmt_update, "sii", $now_formatted, $new_attempts, $attempt_id);
                
                if (!mysqli_stmt_execute($stmt_update)) {
                    throw new Exception("Update execution failed: " . mysqli_stmt_error($stmt_update));
                }
                
                mysqli_stmt_close($stmt_update);
            }
        } else {
            // Create a new entry in the table
            $insert = "INSERT INTO attempts (fingerprint, ip, mail_date, mail_attempts, password_date, password_attempts) VALUES (?, ?, ?, 1, NULL, 0)";
            $stmt_insert = mysqli_prepare($conn, $insert);
            
            if (!$stmt_insert) {
                throw new Exception("Prepare insert statement failed: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt_insert, "sss", $fingerprint, $ip, $now_formatted);
            
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Insert execution failed: " . mysqli_stmt_error($stmt_insert));
            }
            
            mysqli_stmt_close($stmt_insert);
        }
        
        // Send email
        try {
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();                                           
            $mail->Host       = 'smtp.gmail.com';                       
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'noreply.ecovision@gmail.com';          
            
            try {
                $mail->Password = trim(file_get_contents(__DIR__ . '/../libs/mail.passwd'));
            } catch (Exception $e) {
                throw new Exception("Failed to read email password");
            }
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
            $mail->Port       = 587;                                    
            
            $mail->setFrom('noreply.ecovision@gmail.com', 'Ecovision');
            $mail->addAddress('ecovision.vote@gmail.com');
            
            $mail->isHTML(true);
            $mail->Subject = "Contact - $objet";
            $mail->Body = "
                <h2>Nouvelle prise de contact Ecovision</h2>
                <p><strong>Nom :</strong> {$nom}</p>
                <p><strong>Prénom :</strong> {$prenom}</p>
                <p><strong>Email :</strong> {$email}</p>
                <p><strong>Objet :</strong> {$objet}</p>
                <p><strong>Message :</strong><br>" . nl2br($message) . "</p>
            ";
            $mail->AltBody = "Nom : $nom\nPrénom : $prenom\nEmail : $email\nObjet : $objet\nMessage : $message";
            
            $mail->send();
            mysqli_close($conn);
            
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Votre message a bien été envoyé. Merci !']);
            
        } catch (Exception $e) {
            mysqli_close($conn);
            error_log("Erreur envoi contact : " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'Échec de l\'envoi du message. Veuillez réessayer.']);
        }
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        
        // Close database connection if it exists
        if (isset($conn) && $conn) {
            mysqli_close($conn);
        }
        
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.']);
    }
    
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue.']);
}
?>