<?php
require_once './header.php';
header('Content-Type: application/json');
date_default_timezone_set('Africa/Casablanca'); // Assure-toi du bon fuseau horaire

$response = [
    "datetime" => date("c") // Format ISO 8601
];

echo json_encode($response);
?>
