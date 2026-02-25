<?php
$file = 'database.sql';
$content = file_get_contents($file);
// Detect UTF-16LE (BOM: FF FE)
if (substr($content, 0, 2) === "\xFF\xFE") {
    echo "UTF-16LE detected, converting to UTF-8...\n";
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
    // Remove BOM if present after conversion
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    file_put_contents($file, $content);
    echo "Conversion complete.\n";
} else {
    echo "UTF-16LE not detected.\n";
}
