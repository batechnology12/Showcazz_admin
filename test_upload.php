<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

try {
    $tempPath = __DIR__ . '/public/test_image.jpg';
    file_put_contents($tempPath, 'fake image content');

    $fileName = 'test_image_' . time() . '.jpg';
    
    // Upload to DO
    $result = Storage::disk('do')->putFileAs('post_images', new File($tempPath), $fileName, 'public');
    
    if ($result) {
        $url = Storage::disk('do')->url('post_images/' . $fileName);
        echo "Upload Successful! URL: " . $url . "\n";
    } else {
        echo "Upload Failed.\n";
    }
    
    @unlink($tempPath);
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
