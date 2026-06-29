<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = new \App\User;
    $user->email = 'testcompany_'.time().'@example.com';
    $user->password = 'password';
    $user->usertype = 'company';
    $user->visibility_control = 'public';
    $user->post_visibility_control = 'public';
    $user->message_visibility_control = 'public';
    $user->name = 'TestCompany';
    $user->is_active = 1;
    $user->save();
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
