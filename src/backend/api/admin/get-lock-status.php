<?php
require_once '../header.php';
/**
 * Script de récupération du statut de verrouillage du système
 *
 * Ce script récupère et retourne les données de verrouillage stockées dans un fichier JSON
 * pour afficher l'état actuel des options de verrouillage sur le frontend.
 *
 * @version 1.0
 * @environment Compatible avec environnement de production
 * @timezone Africa/Casablanca
 */

// Définition du type de contenu de la réponse
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/errors/getlockstatus-errors.log');
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
    $jsonFile = __DIR__ . '/./system_lock.json';
    
    // Vérifier si le fichier existe
    if (!file_exists($jsonFile)) {
        // Créer un fichier par défaut si non existant
        $defaultData = [
            'verrouillage_inscription' => false,
            'masquer_resultat' => false,
            'verrouillage_vote' => false,
            'derniere_modification' => $date->format('Y-m-d H:i:s')
        ];
        
        file_put_contents($jsonFile, json_encode($defaultData, JSON_PRETTY_PRINT));
        
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => 'Données par défaut créées',
            'data' => $defaultData
        ]);
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
        
        // Retourner les données au format attendu
        http_response_code(200); // OK
        echo json_encode([
            'status' => 'success',
            'message' => 'Données récupérées avec succès',
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Erreur de traitement du fichier JSON: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de la récupération des données de verrouillage.']);
    // Log de l'erreur sans divulgation de détails sensibles
    error_log("Erreur critique dans get-lock-status.php: " . $e->getMessage());
}
?>