<?php
/**
 * Script de récupération des résultats de votes
 *
 * Ce script récupère le nombre de votes pour chaque choix et les retourne
 * sous forme de données formatées pour affichage dans un graphique.
 *
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */
// Configuration des erreurs
require_once './header.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/results-errors.log');
error_reporting(E_ALL);
// Configuration du fuseau horaire
try {
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('Africa/Casablanca'));
} catch (Exception $e) {
    error_log("Erreur de configuration du fuseau horaire: " . $e->getMessage());
}
// Autoriser uniquement les requêtes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status'  => 'error',
        'message' => 'Méthode non autorisée. Utilisez GET.'
    ]);
    exit();
}
// Définir le type de contenu de la réponse
header('Content-Type: application/json');

$jsonFile = __DIR__ . '/admin/system_lock.json';
    $lockType = 'masquer_resultat';

    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $lockData = json_decode($jsonData, true);
        
        // Vérifier si le décodage a réussi et si l'option est activée
        if (json_last_error() === JSON_ERROR_NONE && isset($lockData[$lockType]) && $lockData[$lockType] === true) {

            // Pour une API JSON
            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                http_response_code(403); // Forbidden
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cette fonctionnalité est temporairement désactivée.'
                ]);
                exit();
            }
            
            // Pour une page HTML
            http_response_code(403); // Forbidden
            echo json_encode([
                'status' => 'error',
                'message' => 'Le résultat est masqué.',
                'labels' => null,
                'data'   => null
            ]);
            exit();
        }
    }

try {
    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $dbpassword = trim(file_get_contents(__DIR__ . '/../libs/sql.passwd'));
    $dbname = "ecovision";
   
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);
   
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion à la base de données: " . $conn->connect_error);
    }
   
    try {
        // Requête préparée pour récupérer les résultats
        $sql = "SELECT c.description, COUNT(v.vote_id) AS total_votes
                FROM choices c
                LEFT JOIN votes v ON c.choice_id = v.choice_id
                GROUP BY c.choice_id";
               
        $stmt = $conn->prepare($sql);
       
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête: " . $conn->error);
        }
       
        if (!$stmt->execute()) {
            throw new Exception("Erreur d'exécution de la requête: " . $stmt->error);
        }
       
        $result = $stmt->get_result();
       
        if (!$result) {
            throw new Exception("Erreur de récupération des résultats");
        }
       
        $labels = [];
        $data   = [];
       
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['description'];
            $data[]   = (int)$row['total_votes'];
        }
       
        $stmt->close();
       
        // Envoi du JSON avec format status:message uniforme
        http_response_code(200); // OK
        echo json_encode([
            'status'  => 'success',
            'message' => 'Données récupérées avec succès',
            'labels' => $labels,
            'data'   => $data
        ]);
       
    } catch (Exception $e) {
        throw new Exception("Erreur lors du traitement des données: " . $e->getMessage());
    } finally {
        // Fermeture de la connexion
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
   
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status'  => 'error',
        'message' => 'Une erreur est survenue lors de la récupération des résultats.'
    ]);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans results: " . $e->getMessage());
}
?>