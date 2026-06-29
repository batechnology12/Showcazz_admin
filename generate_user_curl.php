<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Get user by email
    $user = \App\User::where('email', 'testing@gmail.com')->first();
    
    if (!$user) {
        die("Error: User with email testing@gmail.com not found in the database.\n");
    }
    
    // Attempt to find device token column name
    $deviceToken = null;
    if (isset($user->firebase_token) && !empty($user->firebase_token)) {
        $deviceToken = $user->firebase_token;
    } else {
        die("Error: firebase_token is empty for testing@gmail.com. Please make sure they have a valid token saved.\n");
    }

    $fcm = new \App\Services\FCMService();
    $reflection = new \ReflectionClass($fcm);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);
    
    $serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);
    if (isset($serviceAccount['private_key'])) {
        $serviceAccount['private_key'] = str_replace('\n', "\n", $serviceAccount['private_key']);
    }
    
    // Generate valid access token
    $token = $method->invoke($fcm, $serviceAccount);
    $projectId = $serviceAccount['project_id'];

    echo "\n\033[32m=== COPY PASTE AND RUN THE BELOW CURL COMMAND ===\033[0m\n\n";
    echo "curl --location 'https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send' \\\n";
    echo "--header 'Authorization: Bearer {$token}' \\\n";
    echo "--header 'Content-Type: application/json' \\\n";
    echo "--data '{\n";
    echo "   \"message\": {\n";
    echo "     \"token\": \"{$deviceToken}\",\n";
    echo "     \"notification\": {\n";
    echo "       \"title\": \"Live Test Notification for testing@gmail.com\",\n";
    echo "       \"body\": \"This is a test message to verify push on live\"\n";
    echo "     },\n";
    echo "     \"data\": {\n";
    echo "       \"click_action\": \"FLUTTER_NOTIFICATION_CLICK\"\n";
    echo "     }\n";
    echo "   }\n";
    echo " }'\n\n";
    
} catch (\Exception $e) {
    echo "Error generating token: " . $e->getMessage() . "\n";
}
