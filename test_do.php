<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    config(['filesystems.disks.do.throw' => true]);
    $res = \Illuminate\Support\Facades\Storage::disk('do')->put('test.txt', 'test content');
    echo "Success: " . (int)$res . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
