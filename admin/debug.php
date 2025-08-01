<?php
// Admin Panel Debug File
// This file helps diagnose admin panel issues

require_once '../includes/functions.php';
startSession();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Debug - Playcloud</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #121212; color: #ffffff; }
        .card { background-color: #1e1e1e; border: 1px solid #404040; }
    </style>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h2>Admin Panel Debug</h2>
                    </div>
                    <div class='card-body'>";

// Check session
echo "<h4>Session Information</h4>";
echo "<div class='alert alert-info'>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Data:</strong><br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

// Check if user is logged in
echo "<h4>Login Status</h4>";
if (isLoggedIn()) {
    echo "<div class='alert alert-success'>✅ User is logged in</div>";
    echo "<strong>User ID:</strong> " . $_SESSION['user_id'] . "<br>";
    echo "<strong>Username:</strong> " . ($_SESSION['username'] ?? 'Not set') . "<br>";
    echo "<strong>Is Admin:</strong> " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'Yes' : 'No') : 'Not set') . "<br>";
} else {
    echo "<div class='alert alert-danger'>❌ User is not logged in</div>";
}

// Check if user is admin
echo "<h4>Admin Status</h4>";
if (isAdmin()) {
    echo "<div class='alert alert-success'>✅ User is admin</div>";
} else {
    echo "<div class='alert alert-danger'>❌ User is not admin</div>";
}

// Check database connection
echo "<h4>Database Connection</h4>";
try {
    $pdo = getDBConnection();
    echo "<div class='alert alert-success'>✅ Database connection successful</div>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='alert alert-success'>✅ Users table exists</div>";
        
        // Check admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<div class='alert alert-success'>✅ Admin user exists</div>";
            echo "<strong>Admin ID:</strong> " . $admin['id'] . "<br>";
            echo "<strong>Admin Username:</strong> " . $admin['username'] . "<br>";
            echo "<strong>Admin Email:</strong> " . $admin['email'] . "<br>";
            echo "<strong>Is Admin:</strong> " . ($admin['is_admin'] ? 'Yes' : 'No') . "<br>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ Admin user does not exist</div>";
        }
        
        // List all users
        $stmt = $pdo->query("SELECT id, username, email, is_admin FROM users");
        $users = $stmt->fetchAll();
        
        echo "<h5>All Users:</h5>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-dark'>";
        echo "<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr></thead>";
        echo "<tbody>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        
    } else {
        echo "<div class='alert alert-danger'>❌ Users table does not exist</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}

// Check current user
echo "<h4>Current User Information</h4>";
$currentUser = getCurrentUser();
if ($currentUser) {
    echo "<div class='alert alert-success'>✅ Current user data retrieved</div>";
    echo "<pre>" . print_r($currentUser, true) . "</pre>";
} else {
    echo "<div class='alert alert-warning'>⚠️ Could not retrieve current user data</div>";
}

// Create admin user if needed
echo "<h4>Create Admin User</h4>";
if (isset($_POST['create_admin'])) {
    try {
        $pdo = getDBConnection();
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, is_admin) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@gbtech.ir', $adminPassword, 'Administrator', true]);
        
        echo "<div class='alert alert-success'>✅ Admin user created successfully!</div>";
        echo "<strong>Username:</strong> admin<br>";
        echo "<strong>Password:</strong> admin123<br>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error creating admin user: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='create_admin' class='btn btn-primary'>Create Admin User</button>";
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