<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    if (empty($argv[1])) {
        die("Usage: php generate_user_curl.php <email>\nExample: php generate_user_curl.php testing@gmail.com\n");
    }
    
    $email = $argv[1];

    // Get user by email
    $user = \App\User::where('email', $email)->first();
    
    if (!$user) {
        die("Error: User with email {$email} not found in the database.\n");
    }
    
    // Attempt to find device token column name
    $deviceToken = null;
    if (isset($user->firebase_token) && !empty($user->firebase_token)) {
        $deviceToken = $user->firebase_token;
    } else {
        die("Error: firebase_token is empty for {$email}. Please make sure they have a valid token saved.\n");
    }

    $fcm = new \App\Services\FCMService();
    $reflection = new \ReflectionClass($fcm);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);
    
    $serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);
    
    // Override from .env for security
    $serviceAccount['project_id'] = env('FIREBASE_PROJECT_ID', $serviceAccount['project_id'] ?? '');
    $serviceAccount['private_key_id'] = env('FIREBASE_PRIVATE_KEY_ID', $serviceAccount['private_key_id'] ?? '');
    $serviceAccount['private_key'] = env('FIREBASE_PRIVATE_KEY', $serviceAccount['private_key'] ?? '');
    $serviceAccount['client_email'] = env('FIREBASE_CLIENT_EMAIL', $serviceAccount['client_email'] ?? '');
    $serviceAccount['client_id'] = env('FIREBASE_CLIENT_ID', $serviceAccount['client_id'] ?? '');

    if (isset($serviceAccount['private_key'])) {
        $serviceAccount['private_key'] = str_replace(['\\n', '\n'], "\n", $serviceAccount['private_key']);
    }
    
    // Generate valid access token
    $token = $method->invoke($fcm, $serviceAccount);
    $projectId = $serviceAccount['project_id'];

    echo "\n\033[36m=== DEBUG INFO ===\033[0m\n";
    echo "Email ID: {$email}\n";
    echo "FCM Device Token: {$deviceToken}\n";
    echo "Project ID: {$projectId}\n";
    echo "Service Account Email: {$serviceAccount['client_email']}\n";
    echo "Generated Bearer Token: " . substr($token, 0, 15) . "...(truncated)\n";

    echo "\n\033[32m=== COPY PASTE AND RUN THE BELOW CURL COMMAND ===\033[0m\n\n";
    echo "curl --location 'https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send' \\\n";
    echo "--header 'Authorization: Bearer {$token}' \\\n";
    echo "--header 'Content-Type: application/json' \\\n";
    echo "--data '{\n";
    echo "   \"message\": {\n";
    echo "     \"token\": \"{$deviceToken}\",\n";
    echo "     \"notification\": {\n";
    echo "       \"title\": \"Live Test Notification for {$email}\",\n";
    echo "       \"body\": \"This is a test message to verify push on live\"\n";
    echo "     },\n";
    echo "     \"data\": {\n";
    echo "       \"click_action\": \"FLUTTER_NOTIFICATION_CLICK\"\n";
    echo "     }\n";
    echo "   }\n";
    echo " }'\n\n";
    
} catch (\Exception $e) {
    echo "\n\033[31mError generating token: " . $e->getMessage() . "\033[0m\n";
    echo "\n\033[36m=== DEBUG INFO ===\033[0m\n";
    echo "Email: " . ($email ?? 'N/A') . "\n";
    echo "FCM Device Token: " . ($deviceToken ?? 'N/A') . "\n";
    echo "Project ID: " . ($serviceAccount['project_id'] ?? 'N/A') . "\n";
    echo "Service Account Email: " . ($serviceAccount['client_email'] ?? 'N/A') . "\n";
}
