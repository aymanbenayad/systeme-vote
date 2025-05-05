<?php
// Configuration des rapports d'erreurs
require_once '../header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/cli-errors.log');
error_reporting(E_ALL);

// Définir le header JSON
header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ]);
        exit();
    }

    // Vérifier la présence du cookie de session
    if (!isset($_COOKIE['session_token'])) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Session non authentifiée'
        ]);
        exit();
    }

    $session_token = $_COOKIE['session_token'];

    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../../libs/sql.passwd'));
    $dbname = "ecovision";

    // Récupération des données JSON
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Données JSON invalides'
        ]);
        exit();
    }

    // Connexion à la base de données
    $conn = mysqli_connect($servername, $username, $dbpassword, $dbname);
    if (!$conn) {
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'status' => 'error',
            'message' => 'Serveur non disponible'
        ]);
        exit();
    }

    // Configuration de la timezone
    $date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));

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
        echo json_encode([
            'status' => 'error',
            'message' => 'Accès non autorisé'
        ]);
        mysqli_close($conn);
        exit();
    }
   
    // Traitement des commandes
    if (isset($data['command'])) {
        $command = trim($data['command']);
        $commandParts = preg_split('/\s+/', $command, 2);
        $commandBase = strtolower($commandParts[0]);
        $args = isset($commandParts[1]) ? $commandParts[1] : '';
        
        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $currentDir = dirname(__DIR__);
        
        // Commande help
        if ($commandBase === 'help') {
            echo json_encode([
                'status' => 'success',
                'message' => $isWindows ? [
                    "Commandes Windows disponibles:",
                    "- dir [chemin] : Liste le contenu d'un répertoire",
                    "- cd [chemin] : Change de répertoire",
                    "- echo [texte] : Affiche un texte",
                    "- type [fichier] : Affiche le contenu d'un fichier",
                    "- systeminfo : Affiche les informations système",
                    "- tasklist : Liste les processus en cours d'exécution",
                    "- date : Affiche la date actuelle",
                    "- time : Affiche l'heure actuelle",
                    "- ipconfig : Affiche la configuration réseau",
                    "- hostname : Affiche le nom de l'ordinateur",
                    "- whoami : Affiche l'utilisateur actuel",
                    "- ver : Affiche la version de Windows",
                    "- set : Affiche les variables d'environnement",
                    "- path : Affiche le chemin de recherche",
                    "- findstr [motif] [fichier] : Recherche un motif dans un fichier",
                    "- ping [hôte] : Vérifie la connectivité avec un hôte",
                    "- netstat : Affiche les connexions réseau actives",
                    "- cls : Efface l'écran (simulé)",
                    "- help : Affiche cette aide"
                ] : ["Commande 'help' non disponible sur ce système"]
            ]);
            exit;
        } else if ($commandBase === 'cls') {
            echo json_encode([
                'status' => 'success',
                'message' => ["[Écran effacé]"],
                'data' => ['clear' => true]
            ]);
            exit;
        }
        
        // Liste des commandes autorisées
        $allowedCommands = $isWindows ? [
            'dir', 'cd', 'echo', 'type', 'systeminfo', 'tasklist', 
            'date', 'time', 'ipconfig', 'hostname', 'whoami', 
            'ver', 'set', 'path', 'findstr', 'ping', 'netstat'
        ] : [];
        
        if (in_array($commandBase, $allowedCommands)) {
            $fullCommand = "cd /d " . escapeshellarg($currentDir) . " && " . $command . " 2>&1";
            exec($fullCommand, $output, $returnCode);
            
            echo json_encode([
                'status' => $returnCode === 0 ? 'success' : 'error',
                'message' => empty($output) ? ["Commande exécutée sans sortie (code de retour: $returnCode)"] : $output,
                'data' => ['returnCode' => $returnCode]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => [
                    "Commande non autorisée: {$commandBase}",
                    "Utilisez 'help' pour voir la liste des commandes disponibles"
                ]
            ]);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Commande non spécifiée'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur interne du serveur'
    ]);
    error_log('Erreur CLI: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>