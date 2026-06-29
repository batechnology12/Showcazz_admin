<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // 1. Where does it store locally?
    $localDir = \App\Helpers\ImageUploadingHelper::real_public_path() . 'user_images';
    if (!is_dir($localDir)) {
        mkdir($localDir, 0777, true);
    }
    
    $fileName = 'final_test_' . time() . '.jpg';
    $localPath = $localDir . $fileName;
    
    // Simulate creating the file locally first (ImgUploader::UploadImage does this)
    file_put_contents($localPath, 'dummy image content');
    
    echo "--- LOCA STORAGE REPORT ---\n";
    echo "Status: SUCCESS\n";
    echo "Local File Location: " . $localPath . "\n";
    echo "Does file exist locally?: " . (file_exists($localPath) ? 'YES' : 'NO') . "\n\n";
    
    // 2. Where does it store in DigitalOcean Spaces?
    echo "--- DIGITALOCEAN SPACES REPORT ---\n";
    // Throw exceptions so we can catch any errors
    config(['filesystems.disks.do.throw' => true]);
    
    // Sync to storage
    \App\Helpers\ImageUploadingHelper::syncToStorage('user_images', $fileName, false);
    
    $url = \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $fileName);
    
    echo "Status: SUCCESS\n";
    echo "DigitalOcean Bucket: " . env('DO_BUCKET') . "\n";
    echo "DigitalOcean Folder: user_images\n";
    echo "DigitalOcean File URL: " . $url . "\n";
    
} catch (\Exception $e) {
    echo "--- DIGITALOCEAN ERROR ---\n";
    echo "Error: " . $e->getMessage() . "\n";
}
