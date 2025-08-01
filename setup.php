<?php
// Quick Setup File for Playcloud
// This file will set up the database and create admin user

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Playcloud Setup</title>
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
                        <h2><i class='fas fa-cloud-music-rain'></i> Playcloud Setup</h2>
                    </div>
                    <div class='card-body'>";

// Check if setup is already done
if (isset($_POST['setup'])) {
    try {
        // Include database initialization
        require_once 'database/init.php';
        
        // Initialize database
        initializeDatabase();
        
        echo "<div class='alert alert-success'>
            <h4>✅ Setup Completed Successfully!</h4>
            <p><strong>Admin Account Created:</strong></p>
            <ul>
                <li><strong>Username:</strong> admin</li>
                <li><strong>Password:</strong> admin123</li>
                <li><strong>Email:</strong> admin@gbtech.ir</li>
            </ul>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li><a href='login.php' class='btn btn-primary btn-sm'>Go to Login</a></li>
                <li>Login with admin/admin123</li>
                <li><a href='admin/' class='btn btn-outline-primary btn-sm'>Access Admin Panel</a></li>
            </ol>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
            <h4>❌ Setup Failed</h4>
            <p>Error: " . $e->getMessage() . "</p>
            <p>Please check your database configuration in <code>config/database.php</code></p>
        </div>";
    }
} else {
    echo "<div class='alert alert-info'>
        <h4>Welcome to Playcloud Setup</h4>
        <p>This will:</p>
        <ul>
            <li>Create all database tables</li>
            <li>Create admin user (admin/admin123)</li>
            <li>Add sample data</li>
        </ul>
        <p><strong>Database Configuration:</strong></p>
        <ul>
            <li><strong>Host:</strong> localhost</li>
            <li><strong>Database:</strong> gbtechir_loov</li>
            <li><strong>User:</strong> gbtechir_mmd</li>
            <li><strong>Password:</strong> H.m33343536</li>
        </ul>
    </div>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='setup' class='btn btn-primary btn-lg w-100'>Start Setup</button>";
    echo "</form>";
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