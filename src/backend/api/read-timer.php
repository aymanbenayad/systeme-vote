<?php
require_once './header.php';
/**
 * Script de récupération des données de timer pour le vote
 *
 * Ce script récupère et retourne les données de timer stockées dans un fichier JSON
 * pour afficher le temps restant avant la fin du vote sur le frontend.
 *
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */
// Définition du type de contenu de la réponse
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors/readtimer-errors.log');
error_reporting(E_ALL);
// Vérification de la méthode HTTP
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit();
}
try {
    // Configuration du fuseau horaire
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('Africa/Casablanca'));
   
    // Chemin vers le fichier JSON
    $jsonFile = __DIR__ . '/admin/timer_data.json';
   
    // Vérifier si le fichier existe
    if (!file_exists($jsonFile)) {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Fichier timer_data.json introuvable']);
        exit();
    }
   
    try {
        // Lire et décoder le JSON
        $jsonData = file_get_contents($jsonFile);
        if ($jsonData === false) {
            throw new Exception("Impossible de lire le fichier JSON");
        }
       
        // Décodage du JSON
        $data = json_decode($jsonData, true);
       
        // Vérifier si le décodage a réussi
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erreur de décodage JSON: " . json_last_error_msg());
        }
       
        // Vérifier la présence des champs requis
        if (!isset($data['adminStartTime']) || !isset($data['targetDate'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Données JSON incomplètes']);
            exit();
        }
       
        // Retourner les données au format attendu
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => 'Données récupérées avec succès',
            'data' => [
                'adminStartTime' => $data['adminStartTime'],
                'targetDate' => $data['targetDate']
            ]
        ]);
       
    } catch (Exception $e) {
        throw new Exception("Erreur de traitement du fichier JSON: " . $e->getMessage());
    }
   
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la récupération des données du timer.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans get-timer: " . $e->getMessage());
}
?>