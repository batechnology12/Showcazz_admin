<?php
// server.php - For PHP built-in server to serve static files

if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . '/public' . $url['path'];

    // If requested file exists in public/, serve it directly
    if (is_file($file)) {
        return false;
    }
}

// Otherwise, route everything to Laravel index.php
require __DIR__ . '/public/index.php';
