<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$posts = \Illuminate\Support\Facades\DB::table('posts')->whereNotNull('images')->get();

foreach($posts as $post) {
    if (!empty($post->images)) {
        $images = json_decode($post->images, true);
        if (is_string($images)) $images = json_decode($images, true);
        if (is_array($images)) {
            foreach($images as $img) {
                $localPath = public_path('post_images/' . $img);
                if (!file_exists($localPath)) {
                    echo "MISSING: " . $localPath . "\n";
                } else {
                    echo "FOUND: " . $localPath . "\n";
                }
            }
        }
    }
}
