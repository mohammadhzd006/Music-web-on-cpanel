<?php
// Playcloud Installation Script
// Run this file to set up the database and create necessary directories

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Playcloud Installation</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #121212; color: #ffffff; }
        .card { background-color: #1e1e1e; border: 1px solid #404040; }
        .btn-primary { background-color: #ff6b35; border-color: #ff6b35; }
        .btn-primary:hover { background-color: #e55a2b; border-color: #e55a2b; }
    </style>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header text-center'>
                        <h2><i class='fas fa-cloud-music-rain'></i> Playcloud Installation</h2>
                    </div>
                    <div class='card-body'>";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "<div class='alert alert-danger'>❌ PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "</div>";
    exit;
} else {
    echo "<div class='alert alert-success'>✅ PHP version " . PHP_VERSION . " is compatible</div>";
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "<div class='alert alert-danger'>❌ Missing required PHP extensions: " . implode(', ', $missing_extensions) . "</div>";
} else {
    echo "<div class='alert alert-success'>✅ All required PHP extensions are installed</div>";
}

// Check directory permissions
$directories = [
    'uploads/music' => 'Music uploads',
    'uploads/covers' => 'Cover image uploads',
    'admin/assets' => 'Admin assets',
    'database/backups' => 'Database backups'
];

foreach ($directories as $dir => $description) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div class='alert alert-success'>✅ Created directory: $description ($dir)</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to create directory: $description ($dir)</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ Directory exists: $description ($dir)</div>";
    }
    
    if (is_writable($dir)) {
        echo "<div class='alert alert-success'>✅ Directory is writable: $description ($dir)</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Directory is not writable: $description ($dir)</div>";
    }
}

// Database setup
if (isset($_POST['install'])) {
    $host = $_POST['db_host'];
    $name = $_POST['db_name'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");
        
        // Include database initialization
        require_once 'database/init.php';
        
        // Initialize database
        initializeDatabase();
        
        echo "<div class='alert alert-success'>✅ Database setup completed successfully!</div>";
        echo "<div class='alert alert-info'>
            <strong>Default Admin Account:</strong><br>
            Username: admin<br>
            Password: admin123<br>
            <strong>Please change the password after first login!</strong>
        </div>";
        
        echo "<div class='text-center mt-4'>
            <a href='index.php' class='btn btn-primary btn-lg'>Go to Playcloud</a>
        </div>";
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<form method='POST' class='mt-4'>
        <h4>Database Configuration</h4>
        <div class='mb-3'>
            <label for='db_host' class='form-label'>Database Host</label>
            <input type='text' class='form-control bg-dark text-light' id='db_host' name='db_host' value='localhost' required>
        </div>
        <div class='mb-3'>
            <label for='db_name' class='form-label'>Database Name</label>
            <input type='text' class='form-control bg-dark text-light' id='db_name' name='db_name' value='gbtechir_loov' required>
        </div>
        <div class='mb-3'>
            <label for='db_user' class='form-label'>Database User</label>
            <input type='text' class='form-control bg-dark text-light' id='db_user' name='db_user' value='gbtechir_mmd' required>
        </div>
        <div class='mb-3'>
            <label for='db_pass' class='form-label'>Database Password</label>
            <input type='password' class='form-control bg-dark text-light' id='db_pass' name='db_pass' value='H.m33343536' required>
        </div>
        <button type='submit' name='install' class='btn btn-primary'>Install Playcloud</button>
    </form>";
}

echo "
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>