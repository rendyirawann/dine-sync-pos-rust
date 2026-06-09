<?php

$dir = __DIR__ . '/resources/views/backend';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$filesChanged = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        $newContent = $content;
        
        // Single quotes
        $newContent = str_replace("'/admin/", "'{{ url('admin') }}/", $newContent);
        $newContent = str_replace("'admin/", "'{{ url('admin') }}/", $newContent); // just in case 'admin/
        
        // Double quotes
        $newContent = str_replace('"/admin/', '"{{ url(\'admin\') }}/', $newContent);
        // Do not blanket replace "admin/ as it might be used in HTML tags like src="admin/
        
        // Backticks
        $newContent = str_replace('`/admin/', '`{{ url(\'admin\') }}/', $newContent);
        
        // Some places might use url("users/" + id), but we saw "users/
        // Let's also look for that specific pattern from index.blade.php
        $newContent = preg_replace('/url:\s*"users\//', 'url: "{{ url(\'admin/users\') }}/', $newContent);
        $newContent = preg_replace('/url:\s*`users\//', 'url: `{{ url(\'admin\') }}/users/', $newContent);
        
        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            echo "Updated: $path\n";
            $filesChanged++;
        }
    }
}

echo "Total files updated: $filesChanged\n";
