<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$zipFile = 'sns2a_deploy.zip';
$extractPath = __DIR__;

echo "Starting extraction of $zipFile to $extractPath...<br>";

if (!file_exists($zipFile)) {
    die("Error: Zip file '$zipFile' not found in " . __DIR__);
}

if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive class not found. PHP zip extension missing.");
}

$zip = new ZipArchive;
$res = $zip->open($zipFile);
if ($res === TRUE) {
    if ($zip->extractTo($extractPath)) {
        $zip->close();
        echo 'Extraction successful!<br>';
        echo 'You can now access <a href="./">the site</a>.<br>';

        // Try to set permissions
        @chmod('api/public/uploads', 0777);
        @chmod('api/logs', 0777);
        echo 'Permissions updated for uploads and logs.<br>';

        unlink(__FILE__); // Self-destruct
        echo 'This script has been deleted.';
    } else {
        echo 'Extraction failed during write.';
    }
} else {
    echo 'Extraction failed to open zip. Code: ' . $res;
}
?>