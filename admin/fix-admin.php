<?php
// Fix Admin User Script
// This script will make a user admin or create admin user

require_once '../includes/functions.php';
startSession();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Admin User - Playcloud</title>
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
                    <div class='card-header'>
                        <h2>Fix Admin User</h2>
                    </div>
                    <div class='card-body'>";

try {
    $pdo = getDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_admin':
                // Create new admin user
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, is_admin) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute(['admin', 'admin@gbtech.ir', $adminPassword, 'Administrator', 1]);
                echo "<div class='alert alert-success'>✅ Admin user created successfully!</div>";
                break;
                
            case 'make_admin':
                // Make existing user admin
                $username = $_POST['username'];
                $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    echo "<div class='alert alert-success'>✅ User '$username' is now admin!</div>";
                } else {
                    echo "<div class='alert alert-warning'>⚠️ User '$username' not found</div>";
                }
                break;
                
            case 'list_users':
                // List all users
                $stmt = $pdo->query("SELECT id, username, email, is_admin FROM users");
                $users = $stmt->fetchAll();
                
                echo "<h4>All Users:</h4>";
                echo "<div class='table-responsive'>";
                echo "<table class='table table-dark'>";
                echo "<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr></thead>";
                echo "<tbody>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . $user['id'] . "</td>";
                    echo "<td>" . $user['username'] . "</td>";
                    echo "<td>" . $user['email'] . "</td>";
                    echo "<td>" . ($user['is_admin'] ? '✅ Yes' : '❌ No') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
                break;
        }
    }
    
    // Show current admin users
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE is_admin = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    echo "<h4>Current Admin Users:</h4>";
    if (empty($admins)) {
        echo "<div class='alert alert-warning'>⚠️ No admin users found</div>";
    } else {
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>" . $admin['username'] . "</strong> (" . $admin['email'] . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    
    // Create admin user form
    echo "<h4>Create New Admin User</h4>";
    echo "<form method='POST' class='mb-4'>";
    echo "<input type='hidden' name='action' value='create_admin'>";
    echo "<button type='submit' class='btn btn-primary'>Create Admin User (admin/admin123)</button>";
    echo "</form>";
    
    // Make user admin form
    echo "<h4>Make User Admin</h4>";
    echo "<form method='POST' class='mb-4'>";
    echo "<input type='hidden' name='action' value='make_admin'>";
    echo "<div class='mb-3'>";
    echo "<label for='username' class='form-label'>Username</label>";
    echo "<input type='text' class='form-control bg-dark text-light' id='username' name='username' required>";
    echo "</div>";
    echo "<button type='submit' class='btn btn-warning'>Make User Admin</button>";
    echo "</form>";
    
    // List all users
    echo "<h4>List All Users</h4>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='list_users'>";
    echo "<button type='submit' class='btn btn-info'>Show All Users</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
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