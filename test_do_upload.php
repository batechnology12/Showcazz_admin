<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$filename = 'test_upload_' . time() . '.txt';
$localPath = public_path('user_images/' . $filename);

file_put_contents($localPath, 'This is a test upload for DO Spaces permissions.');

// Simulate what ImageUploadingHelper does
\Illuminate\Support\Facades\Storage::disk('do')->putFileAs('user_images', new \Illuminate\Http\File($localPath), $filename, ['visibility' => 'public']);
\Illuminate\Support\Facades\Storage::disk('do')->setVisibility('user_images/' . $filename, 'public');

$url = \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $filename);

echo "Uploaded to DO Spaces: $url\n";

// Now test fetching it via curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    echo "Success! The file is publicly readable.\n";
} else {
    echo "Failed! The file is NOT publicly readable. Response:\n$response\n";
}

// Cleanup local file
@unlink($localPath);
