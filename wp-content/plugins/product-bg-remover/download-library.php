<?php
/**
 * One-time script to download the background removal library
 * Visit: yourdomain.com/wp-content/plugins/product-bg-remover/download-library.php
 */

$library_url = 'https://unpkg.com/@imgly/background-removal@1.4.5/dist/browser.umd.js';
$save_path = __DIR__ . '/js/background-removal.umd.js';

echo "Downloading library...<br>";

$content = file_get_contents($library_url);

if ($content) {
    file_put_contents($save_path, $content);
    echo "✓ Library downloaded successfully to: " . $save_path . "<br>";
    echo "File size: " . filesize($save_path) . " bytes<br>";
    echo "<br><strong>Done! You can delete this download-library.php file now.</strong>";
} else {
    echo "✗ Failed to download library<br>";
    echo "Please download manually from: " . $library_url . "<br>";
    echo "And save it as: js/background-removal.umd.js";
}