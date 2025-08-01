<?php
require_once '../includes/functions.php';
startSession();

// Check if user is admin
if (!isAdmin()) {
    // Redirect to login with error message
    $_SESSION['error'] = 'You must be logged in as an admin to access this page.';
    redirect('../login.php');
}

// Check if database connection works
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get statistics with error handling
$stats = [];
try {
    $stats = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'tracks' => $pdo->query("SELECT COUNT(*) FROM tracks")->fetchColumn(),
        'albums' => $pdo->query("SELECT COUNT(*) FROM albums")->fetchColumn(),
        'playlists' => $pdo->query("SELECT COUNT(*) FROM playlists")->fetchColumn(),
        'artists' => $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn(),
        'pages' => $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = [
        'users' => 0,
        'tracks' => 0,
        'albums' => 0,
        'playlists' => 0,
        'artists' => 0,
        'pages' => 0
    ];
}

// Get recent activities with error handling
$recentTracks = [];
$recentUsers = [];

try {
    $recentTracks = $pdo->query("
        SELECT t.*, a.name as artist_name 
        FROM tracks t 
        LEFT JOIN artists a ON t.artist_id = a.id 
        ORDER BY t.created_at DESC 
        LIMIT 5
    ")->fetchAll();

    $recentUsers = $pdo->query("
        SELECT * FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    // Tables might not exist yet, continue with empty arrays
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Playcloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/admin.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-cloud-music-rain me-2"></i>Playcloud Admin
            </a>
            
            <div class="navbar-nav me-auto">
                <a class="nav-link active" href="index.php">Dashboard</a>
                <a class="nav-link" href="tracks.php">Tracks</a>
                <a class="nav-link" href="albums.php">Albums</a>
                <a class="nav-link" href="artists.php">Artists</a>
                <a class="nav-link" href="playlists.php">Playlists</a>
                <a class="nav-link" href="users.php">Users</a>
                <a class="nav-link" href="pages.php">Pages</a>
                <a class="nav-link" href="menu.php">Menu</a>
            </div>
            
            <div class="d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-light me-3">
                    <i class="fas fa-external-link-alt me-1"></i>View Site
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php echo getCurrentUser()['full_name'] ?? getCurrentUser()['username']; ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-section">
                    <h6 class="text-muted mb-3">Content Management</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="tracks.php">
                                <i class="fas fa-music me-2"></i>Tracks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="albums.php">
                                <i class="fas fa-folder me-2"></i>Albums
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="artists.php">
                                <i class="fas fa-user me-2"></i>Artists
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="playlists.php">
                                <i class="fas fa-list me-2"></i>Playlists
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h6 class="text-muted mb-3">User Management</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="roles.php">
                                <i class="fas fa-user-shield me-2"></i>Roles
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h6 class="text-muted mb-3">Site Management</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="pages.php">
                                <i class="fas fa-file-alt me-2"></i>Pages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="menu.php">
                                <i class="fas fa-bars me-2"></i>Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div>
                        <a href="tracks.php?action=add" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-1"></i>Add Track
                        </a>
                        <a href="albums.php?action=add" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Add Album
                        </a>
                    </div>
                </div>

                <?php echo displayMessages(); ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['users']; ?></h4>
                                        <small>Users</small>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['tracks']; ?></h4>
                                        <small>Tracks</small>
                                    </div>
                                    <i class="fas fa-music fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['albums']; ?></h4>
                                        <small>Albums</small>
                                    </div>
                                    <i class="fas fa-folder fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['playlists']; ?></h4>
                                        <small>Playlists</small>
                                    </div>
                                    <i class="fas fa-list fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['artists']; ?></h4>
                                        <small>Artists</small>
                                    </div>
                                    <i class="fas fa-user fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 col-sm-6 mb-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['pages']; ?></h4>
                                        <small>Pages</small>
                                    </div>
                                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Tracks -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark border">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Tracks</h5>
                                <a href="tracks.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentTracks)): ?>
                                    <p class="text-muted">No tracks added yet.</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recentTracks as $track): ?>
                                        <div class="list-group-item bg-dark border-0 d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($track['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($track['artist_name']); ?></small>
                                            </div>
                                            <div>
                                                <a href="tracks.php?action=edit&id=<?php echo $track['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark border">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Users</h5>
                                <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentUsers)): ?>
                                    <p class="text-muted">No users registered yet.</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recentUsers as $user): ?>
                                        <div class="list-group-item bg-dark border-0 d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $user['is_admin'] ? 'danger' : 'secondary'; ?>">
                                                    <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-dark border">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="tracks.php?action=add" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Track
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="albums.php?action=add" class="btn btn-outline-success w-100">
                                            <i class="fas fa-plus me-2"></i>Add Album
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="artists.php?action=add" class="btn btn-outline-info w-100">
                                            <i class="fas fa-plus me-2"></i>Add Artist
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="pages.php?action=add" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-plus me-2"></i>Add Page
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>