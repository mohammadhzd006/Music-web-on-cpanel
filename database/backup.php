<?php
// Database Backup Script
// This file creates a backup of the database

require_once '../config/database.php';

function backupDatabase($filename = null) {
    try {
        $pdo = getDBConnection();
        
        if (!$filename) {
            $filename = 'playcloud_backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $backupPath = __DIR__ . '/backups/' . $filename;
        
        // Create backups directory if it doesn't exist
        if (!is_dir(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0755, true);
        }
        
        // Get all tables
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $backup = "-- Playcloud Database Backup\n";
        $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Database: " . DB_NAME . "\n\n";
        
        // Add database creation
        $backup .= "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $backup .= "USE `" . DB_NAME . "`;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $backup .= $row[1] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $backup .= "-- Data for table `$table`\n";
                $backup .= "INSERT INTO `$table` VALUES\n";
                
                $insertValues = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $insertValues[] = "(" . implode(', ', $values) . ")";
                }
                
                $backup .= implode(",\n", $insertValues) . ";\n\n";
            }
        }
        
        // Write backup to file
        file_put_contents($backupPath, $backup);
        
        return $backupPath;
        
    } catch (Exception $e) {
        throw new Exception("Backup failed: " . $e->getMessage());
    }
}

function restoreDatabase($backupFile) {
    try {
        $pdo = getDBConnection();
        
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found: $backupFile");
        }
        
        $sql = file_get_contents($backupFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception("Restore failed: " . $e->getMessage());
    }
}

function listBackups() {
    $backupDir = __DIR__ . '/backups';
    $backups = [];
    
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/*.sql');
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
    
    return $backups;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'backup':
                    $filename = backupDatabase();
                    $message = "✅ Backup created successfully: " . basename($filename);
                    break;
                    
                case 'restore':
                    if (isset($_POST['backup_file'])) {
                        $backupFile = __DIR__ . '/backups/' . $_POST['backup_file'];
                        restoreDatabase($backupFile);
                        $message = "✅ Database restored successfully from: " . $_POST['backup_file'];
                    } else {
                        $message = "❌ No backup file selected";
                    }
                    break;
            }
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

$backups = listBackups();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Playcloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; }
        .card { background-color: #1e1e1e; border: 1px solid #404040; }
        .btn-primary { background-color: #ff6b35; border-color: #ff6b35; }
        .btn-primary:hover { background-color: #e55a2b; border-color: #e55a2b; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2><i class="fas fa-database"></i> Database Management</h2>
                    </div>
                    <div class="card-body">
                        
                        <?php if (isset($message)): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Create Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="backup">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-download"></i> Create Backup
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Restore Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($backups)): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="restore">
                                                <div class="mb-3">
                                                    <label for="backup_file" class="form-label">Select Backup File</label>
                                                    <select class="form-select bg-dark text-light" id="backup_file" name="backup_file" required>
                                                        <option value="">Choose a backup file...</option>
                                                        <?php foreach ($backups as $backup): ?>
                                                            <option value="<?php echo $backup['filename']; ?>">
                                                                <?php echo $backup['filename']; ?> 
                                                                (<?php echo number_format($backup['size'] / 1024, 1); ?> KB)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-warning w-100" 
                                                        onclick="return confirm('Are you sure you want to restore the database? This will overwrite current data.')">
                                                    <i class="fas fa-upload"></i> Restore Backup
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <p class="text-muted">No backup files found.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($backups)): ?>
                            <div class="mt-4">
                                <h5>Available Backups</h5>
                                <div class="table-responsive">
                                    <table class="table table-dark">
                                        <thead>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Size</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backups as $backup): ?>
                                                <tr>
                                                    <td><?php echo $backup['filename']; ?></td>
                                                    <td><?php echo number_format($backup['size'] / 1024, 1); ?> KB</td>
                                                    <td><?php echo $backup['date']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="../admin/" class="btn btn-outline-primary">Back to Admin Panel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>