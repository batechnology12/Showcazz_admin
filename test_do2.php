<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create a dummy image file locally
    $localDir = \App\Helpers\ImageUploadingHelper::real_public_path() . 'user_images';
    if (!is_dir($localDir)) {
        mkdir($localDir, 0777, true);
    }
    
    $fileName = 'test_munees_' . time() . '.jpg';
    $localPath = $localDir . '/' . $fileName;
    file_put_contents($localPath, 'dummy image content');
    
    // Simulate what RegisterController does
    config(['filesystems.disks.do.throw' => true]);
    \App\Helpers\ImageUploadingHelper::syncToStorage('user_images', $fileName, false);
    
    echo "Success! File synced. Check DO Spaces for user_images/" . $fileName . "\n";
    $url = \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $fileName);
    echo "URL: " . $url . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
