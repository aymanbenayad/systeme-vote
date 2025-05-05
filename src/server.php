<?php
// Get the requested URI
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// List of custom routes
$routes = [
    '/vote' => '/vote.html',
    '/index' => '/index.html',
    '/admin' => '/admin.html',
    '/' => '/index.html',
    '/contact' => '/contact.html',
    '/resultats' => '/resultats.html',
    '/profil' => '/profil.html',
    '/404' => '/404.html',
    '/error-page' => '/404.html',
    '/missing-page' => '/404.html',
];

// If route exists, serve corresponding file
if (array_key_exists($request, $routes)) {
    require __DIR__ . $routes[$request];
} else {
    // If file exists, serve it normally
    $file = __DIR__ . $request;
    if (file_exists($file) && !is_dir($file)) {
        return false; // Let the built-in server serve the file
    }

    // Otherwise, show 404
    require __DIR__ . '/404.html';
}
