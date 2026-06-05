<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);

if (!$serviceAccount) {
    echo "Error: medical-app.json not found or invalid JSON\n";
    exit;
}

if (isset($serviceAccount['private_key'])) {
    $serviceAccount['private_key'] = str_replace('\n', "\n", $serviceAccount['private_key']);
}

try {
    $nowSeconds = time();
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $nowSeconds,
        'exp' => $nowSeconds + 3600,
    ];
    $jwt = \Firebase\JWT\JWT::encode($payload, $serviceAccount['private_key'], 'RS256');
    echo 'JWT Generated Successfully! Length: ' . strlen($jwt) . "\n";
    
    // Now test token generation
    $client = new \GuzzleHttp\Client();
    $response = $client->post('https://oauth2.googleapis.com/token', [
        'form_params' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ],
    ]);
    
    $result = json_decode($response->getBody()->getContents(), true);
    if (isset($result['access_token'])) {
        echo "Google OAuth Access Token fetched successfully!\n";
    } else {
        echo "Failed to fetch access token\n";
    }

} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
