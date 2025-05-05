<?php
/**
 * Send Verification Code Script
 * 
 * This script handles verification code generation and sending via email.
 * It includes rate limiting, fingerprint verification, and email validation.
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
ini_set('error_log', __DIR__ . '/../logs/errors/send-code-errors.log');
error_reporting(E_ALL);

// Set timezone for all date operations
date_default_timezone_set('Africa/Casablanca');
$date = new DateTime();
$date->setTimezone(new DateTimeZone('Africa/Casablanca'));

// Verify request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Validation of email
    function sanitizeString($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Email invalide']);
        exit();
    }
    
    $fingerprint = isset($_POST['fingerprint']) ? trim($_POST['fingerprint']) : '';
    if (empty($fingerprint) || !preg_match('/^[a-zA-Z0-9]+$/', $fingerprint)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Fingerprint requis']);
        exit();
    }

    // Sanitize
    $email = sanitizeString($email);
    $fingerprint = sanitizeString($fingerprint);

    // Database connection
    $servername = "localhost";
    $username = "root";
    
    try {
        $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
        $dbname = "ecovision";

        $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Check for previous attempts
        $query_check_attempts = "SELECT first_date, sign_attempts FROM codes WHERE fingerprint = ? OR email = ? ORDER BY code_id DESC LIMIT 1";
        $stmt_check_attempts = mysqli_prepare($conn, $query_check_attempts);
        
        if (!$stmt_check_attempts) {
            throw new Exception("Prepare statement failed");
        }
        
        mysqli_stmt_bind_param($stmt_check_attempts, "ss", $fingerprint, $email);
        mysqli_stmt_execute($stmt_check_attempts);
        mysqli_stmt_store_result($stmt_check_attempts);

        $current_time = time();
        $current_date = $date->format("Y-m-d H:i:s");
        
        try {
            $code = random_int(100000, 999999);
        } catch (Exception $e) {
            // Fallback if random_int fails
            $code = mt_rand(100000, 999999);
        }

        if (mysqli_stmt_num_rows($stmt_check_attempts) > 0) {
            // Fingerprint or email already exists
            mysqli_stmt_bind_result($stmt_check_attempts, $attempt_date, $attempts);
            mysqli_stmt_fetch($stmt_check_attempts);

            $attempt_timestamp = strtotime($attempt_date);
            $time_diff = $current_time - $attempt_timestamp;

            if ($time_diff < 3600) { // Less than 1 hour
                $new_attempts = $attempts + 1;

                if ($new_attempts > 3) {
                    mysqli_stmt_close($stmt_check_attempts);
                    mysqli_close($conn);
                    http_response_code(429); // Too Many Requests
                    echo json_encode(['status' => 'error', 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
                    exit();
                } else {
                    $query_update = "UPDATE codes SET sign_attempts = ?, code = ?, last_date = ?, attempts = 0 WHERE fingerprint = ? OR email = ?";
                    $stmt_update = mysqli_prepare($conn, $query_update);
                    
                    if (!$stmt_update) {
                        throw new Exception("Update prepare statement failed");
                    }
                    
                    mysqli_stmt_bind_param($stmt_update, "iisss", $new_attempts, $code, $current_date, $fingerprint, $email);
                    if (!mysqli_stmt_execute($stmt_update)) {
                        throw new Exception("Update execute failed");
                    }
                    mysqli_stmt_close($stmt_update);
                }
            } else {
                $query_update = "UPDATE codes SET sign_attempts = 1, code = ?, last_date = ?, first_date = ?, attempts = 0 WHERE fingerprint = ? OR email = ?";
                $stmt_update = mysqli_prepare($conn, $query_update);
                
                if (!$stmt_update) {
                    throw new Exception("Update prepare statement failed");
                }
                
                $current_date = $date->format("Y-m-d H:i:s");
                mysqli_stmt_bind_param($stmt_update, "issss", $code, $current_date, $current_date, $fingerprint, $email);
                
                if (!mysqli_stmt_execute($stmt_update)) {
                    throw new Exception("Update execute failed");
                }
                mysqli_stmt_close($stmt_update);
            }
        } else {
            // First attempt for this fingerprint/email
            $query_insert = "INSERT INTO codes (fingerprint, email, first_date, sign_attempts, last_date, code, attempts) VALUES (?, ?, ?, 1, ?, ?, 0)";
            $stmt_insert = mysqli_prepare($conn, $query_insert);
            
            if (!$stmt_insert) {
                throw new Exception("Insert prepare statement failed");
            }
            
            $current_date = $date->format("Y-m-d H:i:s");
            mysqli_stmt_bind_param($stmt_insert, "ssssi", $fingerprint, $email, $current_date, $current_date, $code);
            
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Insert execute failed");
            }
            mysqli_stmt_close($stmt_insert);
        }

        mysqli_stmt_close($stmt_check_attempts);
        mysqli_close($conn);

        // Send email with verification code
        try {
            // Load Composer's autoloader
            require '../libs/vendor/autoload.php';

            // Create a PHPMailer instance
            $mail = new PHPMailer(true);
            
            // SMTP server settings
            $mail->isSMTP();                                           
            $mail->Host       = 'smtp.gmail.com';                        
            $mail->SMTPAuth   = true;                                    
            $mail->Username   = 'noreply.ecovision@gmail.com';           
            $mail->Password   = trim(file_get_contents(__DIR__ . '/../libs/mail.passwd'));
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;          
            $mail->Port       = 587;                                     

            // Recipients
            $mail->setFrom('noreply.ecovision@gmail.com', 'Ecovision');
            $mail->addAddress($email);

            $body = "
            <html>
            <head>
                <meta charset=\"UTF-8\">
                <title>Code de confirmation</title>
                <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #ffffff;
                    color: #000000;
                    padding: 20px;
                    margin: 0;
                }
                .container {
                    max-width: 600px;
                    margin: auto;
                    border: 1px solid #dddddd;
                    padding: 20px;
                    border-radius: 6px;
                    background-color: #f9f9f9;
                }
                h2 {
                    font-size: 20px;
                    margin-top: 0;
                }
                .code {
                    font-size: 24px;
                    font-weight: bold;
                    background-color: #e0e0e0;
                    padding: 10px 20px;
                    display: inline-block;
                    border-radius: 4px;
                    margin: 20px 0;
                    letter-spacing: 2px;
                }
                p {
                    font-size: 14px;
                    margin: 15px 0;
                }
                .footer {
                    font-size: 12px;
                    color: #555;
                    border-top: 1px solid #ccc;
                    margin-top: 30px;
                    padding-top: 10px;
                }
                </style>
            </head>
            <body>
                <div class=\"container\">
                <h2>Bonjour,</h2>
                <p>Merci de vous être inscrit. Voici votre code de confirmation :</p>

                <div class=\"code\">$code</div>

                <p>Ce code est valide pendant 30 minutes.</p>

                <div class=\"footer\">
                    <p>Si vous n'avez pas demandé ce code, vous pouvez ignorer ce message.</p>
                    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
                </div>
                </div>
            </body>
            </html>
            ";

            // Email content
            $mail->isHTML(true);                                        
            $mail->Subject = 'Votre code de confirmation Ecovision';
            $mail->Body    = $body;

            // Send email
            $mail->send();
            
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Code envoyé avec succès']);
            
        } catch (Exception $e) {
            // Log error and return generic error message
            error_log("Erreur d'envoi d'email: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'Échec de l\'envoi du code. Veuillez réessayer ultérieurement.']);
        }
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Serveur non disponible.']);
        
        // Close any open resources
        if (isset($stmt_check_attempts) && $stmt_check_attempts) {
            mysqli_stmt_close($stmt_check_attempts);
        }
        if (isset($conn) && $conn) {
            mysqli_close($conn);
        }
    }
    
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue.']);
}
?>