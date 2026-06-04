<?php

$directories = [
    __DIR__ . '/app/Http/Controllers',
    __DIR__ . '/resources/views/admin'
];

$patterns = [
    // 1. asset('user_images/' . $variable) -> (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $variable) : asset('user_images/' . $variable))
    // We must be careful not to replace something that's already replaced.
    // So we look for asset('user_images/' and ensure it's not preceded by :
    // Actually, just looking for asset('user_images/' . $something) is fine, but we'll use a negative lookbehind if possible, or just be careful.
    '/asset\(\'user_images\/\'\s*\.\s*([^)]+)\)/',
    
    '/asset\(\'company_logos\/\'\s*\.\s*([^)]+)\)/'
];

$replacements = [
    '(env(\'DO_ACCESS_KEY_ID\') ? \Illuminate\Support\Facades\Storage::disk(\'do\')->url(\'user_images/\' . $1) : asset(\'user_images/\' . $1))',
    '(env(\'DO_ACCESS_KEY_ID\') ? \Illuminate\Support\Facades\Storage::disk(\'do\')->url(\'company_logos/\' . $1) : asset(\'company_logos/\' . $1))'
];

function processDirectory($dir, $patterns, $replacements) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $count = 0;
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade'])) { // Wait, blade files have .php extension usually (.blade.php)
            if ($file->getExtension() !== 'php') continue;
            
            $content = file_get_contents($file->getRealPath());
            $originalContent = $content;
            
            // We want to avoid replacing if it already has DO_ACCESS_KEY_ID
            // Let's do a trick: we replace it temporarily, and if it looks messy we can fix it.
            // Better: just run preg_replace, but only on lines that don't have DO_ACCESS_KEY_ID?
            
            // Let's iterate line by line to be safe
            $lines = explode("\n", $content);
            $newLines = [];
            $modified = false;
            
            foreach ($lines as $line) {
                if (strpos($line, 'DO_ACCESS_KEY_ID') !== false && strpos($line, 'user_images') !== false) {
                    $newLines[] = $line; // Already processed
                    continue;
                }
                if (strpos($line, 'DO_ACCESS_KEY_ID') !== false && strpos($line, 'company_logos') !== false) {
                    $newLines[] = $line; // Already processed
                    continue;
                }
                
                $newLine = preg_replace($patterns[0], $replacements[0], $line);
                $newLine = preg_replace($patterns[1], $replacements[1], $newLine);
                
                if ($newLine !== $line) {
                    $modified = true;
                }
                $newLines[] = $newLine;
            }
            
            if ($modified) {
                file_put_contents($file->getRealPath(), implode("\n", $newLines));
                echo "Updated: " . $file->getRealPath() . "\n";
                $count++;
            }
        }
    }
    return $count;
}

$total = 0;
foreach ($directories as $dir) {
    echo "Processing $dir...\n";
    $total += processDirectory($dir, $patterns, $replacements);
}

echo "Total files updated: $total\n";
