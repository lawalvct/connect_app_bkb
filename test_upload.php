<?php
// Test script to debug file upload in step 5
// Place this in your public directory and access via browser

require_once __DIR__ . '/bootstrap/app.php';

$app = app();

echo "<h1>Profile Upload Test</h1>";

// Check if profiles directory exists and is writable
$profilesDir = public_path('uploads/profiles');
echo "<h2>Directory Check:</h2>";
echo "Profiles directory: " . $profilesDir . "<br>";
echo "Exists: " . (is_dir($profilesDir) ? 'YES' : 'NO') . "<br>";
echo "Writable: " . (is_writable($profilesDir) ? 'YES' : 'NO') . "<br>";

// List existing files
echo "<h2>Existing Files:</h2>";
if (is_dir($profilesDir)) {
    $files = scandir($profilesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo $file . "<br>";
        }
    }
} else {
    echo "Directory does not exist<br>";
}

// Test form
echo "<h2>Test Upload Form:</h2>";
echo '<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" accept="image/*"><br><br>
    <input type="submit" value="Test Upload">
</form>';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Upload Test Result:</h2>";

    $file = $_FILES['test_file'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . "<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "Temp name: " . $file['tmp_name'] . "<br>";
    echo "Error: " . $file['error'] . "<br>";

    if ($file['error'] === 0) {
        $filename = time() . '_' . $file['name'];
        $destination = $profilesDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "<strong>SUCCESS:</strong> File uploaded to " . $destination . "<br>";
            echo "File URL: " . url('uploads/profiles/' . $filename) . "<br>";
        } else {
            echo "<strong>FAILED:</strong> Could not move uploaded file<br>";
        }
    } else {
        echo "<strong>ERROR:</strong> Upload error code " . $file['error'] . "<br>";
    }
}

echo "<h2>PHP Configuration:</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";

?>
