<?php
function replaceInDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            replaceInDir($path);
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            if (strpos($content, 'registrations') !== false) {
                $content = str_replace('registrations', 'registrations', $content); // prevent double replace if any
                $content = str_replace('registrations', 'registrations', $content);
                file_put_contents($path, $content);
                echo "Updated: $path\n";
            }
        }
    }
}

replaceInDir(__DIR__);
echo "Replacement complete.\n";
?>
