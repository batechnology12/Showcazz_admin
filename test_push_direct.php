<?php
require __DIR__.'/vendor/autoload.php';
use GuzzleHttp\Client;
use Firebase\JWT\JWT;

$serviceAccount = json_decode(file_get_contents(__DIR__.'/medical-app.json'), true);

$serviceAccount = json_decode(file_get_contents(__DIR__.'/medical-app.json'), true);

$headers = get_headers('https://google.com', 1);
$dateStr = is_array($headers['Date']) ? end($headers['Date']) : $headers['Date'];
$nowSeconds = isset($dateStr) ? strtotime($dateStr) : time();


$payload = [
    'iss' => $serviceAccount['client_email'],
    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $nowSeconds,
    'exp' => $nowSeconds + 3600,
];

// If json_decode already stripped newlines, this won't hurt. If not, it fixes it.
$privateKey = $serviceAccount['private_key'];

try {
    $jwt = JWT::encode($payload, $privateKey, 'RS256', $serviceAccount['private_key_id']);
    $client = new Client();
    $response = $client->post('https://oauth2.googleapis.com/token', [
        'form_params' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ],
    ]);
    
    $accessToken = json_decode($response->getBody()->getContents(), true)['access_token'];
    
    $deviceToken = "ez9aI-0URqayyIFlzI8PPH:APA91bHlPNGf4BpKzuFQbk71FMmN9RFG0B6ixkPpdMSjoUSZAyd2jHy-g9KCkmPawGQvxpirR5ls594alb0EXV1t3xW7O-Np6gsNKvwvAUuluNSVkSd0iSA";
    $projectId = $serviceAccount['project_id'];
    
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    $notificationPayload = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => 'Live Test from AI',
                'body'  => 'This is a test message generated via script',
            ],
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
        ],
    ];

    echo "Sending push to: {$deviceToken}\n";
    $pushResponse = $client->post($url, [
        'headers' => [
            'Authorization' => "Bearer $accessToken",
            'Content-Type'  => 'application/json',
        ],
        'json' => $notificationPayload,
        'http_errors' => false // To capture the error body
    ]);
    
    echo "Status Code: " . $pushResponse->getStatusCode() . "\n";
    echo "Response: " . $pushResponse->getBody() . "\n";
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
