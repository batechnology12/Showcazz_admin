<?php
$files = [
    __DIR__ . '/app/Traits/CommonUserFunctions.php',
    __DIR__ . '/app/Traits/CompanyTrait.php',
    __DIR__ . '/app/Traits/CompanyApiTrait.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Replace: File::delete(ImgUploader::real_public_path() . 'user_images/' . $image);
    // With: File::delete(ImgUploader::real_public_path() . 'user_images/' . $image); if (env('DO_ACCESS_KEY_ID')) { \Illuminate\Support\Facades\Storage::disk('do')->delete('user_images/' . $image); }
    
    $modified = false;
    $lines = explode("\n", $content);
    $newLines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'File::delete(ImgUploader::real_public_path()') !== false) {
            // Only add if not already added
            if (strpos($line, 'DO_ACCESS_KEY_ID') === false) {
                // Extract the folder and filename variable
                // Example: File::delete(ImgUploader::real_public_path() . 'company_logos/' . $image);
                if (preg_match('/File::delete\(ImgUploader::real_public_path\(\)\s*\.\s*\'([a-zA-Z0-9_\/]+)\'\s*\.\s*(\$[a-zA-Z0-9_]+)\);/', $line, $matches)) {
                    $folderAndPrefix = $matches[1]; // e.g., 'company_logos/thumb/' or 'company_logos/'
                    $var = $matches[2]; // e.g., '$image'
                    $newLine = $line . " if (env('DO_ACCESS_KEY_ID')) { \Illuminate\Support\Facades\Storage::disk('do')->delete('" . $folderAndPrefix . "' . " . $var . "); }";
                    $newLines[] = $newLine;
                    $modified = true;
                    continue;
                }
            }
        }
        $newLines[] = $line;
    }
    
    if ($modified) {
        file_put_contents($file, implode("\n", $newLines));
        echo "Updated: $file\n";
    }
}
