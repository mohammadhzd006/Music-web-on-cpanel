<?php
require_once '../includes/functions.php';
startSession();

// Check if user is admin
if (!isAdmin()) {
    redirect('../login.php');
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$trackId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $title = sanitizeInput($_POST['title']);
        $artistId = (int)$_POST['artist_id'];
        $albumId = !empty($_POST['album_id']) ? (int)$_POST['album_id'] : null;
        $duration = (int)$_POST['duration'];
        $genre = sanitizeInput($_POST['genre']);
        
        // Handle file upload
        $filePath = '';
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = uploadFile($_FILES['audio_file'], '../uploads/music', ['mp3', 'wav', 'ogg']);
        }
        
        // Handle cover image upload
        $coverImage = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverImage = uploadFile($_FILES['cover_image'], '../uploads/covers', ['jpg', 'jpeg', 'png', 'gif']);
        }
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO tracks (title, artist_id, album_id, duration, file_path, cover_image, genre) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $artistId, $albumId, $duration, $filePath, $coverImage, $genre]);
            handleSuccess('Track added successfully!', 'tracks.php');
        } else {
            $updateFields = ['title' => $title, 'artist_id' => $artistId, 'duration' => $duration, 'genre' => $genre];
            if ($albumId) $updateFields['album_id'] = $albumId;
            if ($filePath) $updateFields['file_path'] = $filePath;
            if ($coverImage) $updateFields['cover_image'] = $coverImage;
            
            $sql = "UPDATE tracks SET " . implode(', ', array_map(fn($k) => "$k = ?", array_keys($updateFields))) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($updateFields), $trackId]);
            handleSuccess('Track updated successfully!', 'tracks.php');
        }
    } elseif ($action === 'delete' && $trackId) {
        $stmt = $pdo->prepare("DELETE FROM tracks WHERE id = ?");
        $stmt->execute([$trackId]);
        handleSuccess('Track deleted successfully!', 'tracks.php');
    }
}

// Get track data for editing
$track = null;
if ($action === 'edit' && $trackId) {
    $stmt = $pdo->prepare("SELECT * FROM tracks WHERE id = ?");
    $stmt->execute([$trackId]);
    $track = $stmt->fetch();
}

// Get artists and albums for dropdowns
$artists = $pdo->query("SELECT id, name FROM artists ORDER BY name")->fetchAll();
$albums = $pdo->query("SELECT id, title FROM albums ORDER BY title")->fetchAll();

// Get tracks for listing
$tracks = [];
if ($action === 'list') {
    $stmt = $pdo->prepare("
        SELECT t.*, a.name as artist_name, al.title as album_title
        FROM tracks t
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    $tracks = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracks Management - Playcloud Admin</title>
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
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link active" href="tracks.php">Tracks</a>
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
                            <a class="nav-link active" href="tracks.php">
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
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $action === 'add' ? 'Add New Track' : ($action === 'edit' ? 'Edit Track' : 'Tracks Management'); ?></h2>
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Track
                        </a>
                    <?php else: ?>
                        <a href="tracks.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                    <?php endif; ?>
                </div>

                <?php echo displayMessages(); ?>

                <?php if ($action === 'list'): ?>
                    <!-- Tracks List -->
                    <div class="card bg-dark border">
                        <div class="card-header">
                            <h5 class="mb-0">All Tracks</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tracks)): ?>
                                <p class="text-muted">No tracks found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Cover</th>
                                                <th>Title</th>
                                                <th>Artist</th>
                                                <th>Album</th>
                                                <th>Duration</th>
                                                <th>Genre</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tracks as $track): ?>
                                            <tr>
                                                <td>
                                                    <img src="../uploads/covers/<?php echo $track['cover_image'] ?: 'default.jpg'; ?>" 
                                                         class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                </td>
                                                <td><?php echo htmlspecialchars($track['title']); ?></td>
                                                <td><?php echo htmlspecialchars($track['artist_name']); ?></td>
                                                <td><?php echo htmlspecialchars($track['album_title'] ?? '-'); ?></td>
                                                <td><?php echo formatDuration($track['duration']); ?></td>
                                                <td><?php echo htmlspecialchars($track['genre']); ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?php echo $track['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $track['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this track?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Add/Edit Form -->
                    <div class="card bg-dark border">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo $action === 'add' ? 'Add New Track' : 'Edit Track'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="title" class="form-label">Track Title *</label>
                                        <input type="text" class="form-control bg-dark text-light" id="title" name="title" 
                                               value="<?php echo $track ? htmlspecialchars($track['title']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="artist_id" class="form-label">Artist *</label>
                                        <select class="form-select bg-dark text-light" id="artist_id" name="artist_id" required>
                                            <option value="">Select Artist</option>
                                            <?php foreach ($artists as $artist): ?>
                                                <option value="<?php echo $artist['id']; ?>" 
                                                        <?php echo ($track && $track['artist_id'] == $artist['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($artist['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="album_id" class="form-label">Album</label>
                                        <select class="form-select bg-dark text-light" id="album_id" name="album_id">
                                            <option value="">Select Album</option>
                                            <?php foreach ($albums as $album): ?>
                                                <option value="<?php echo $album['id']; ?>" 
                                                        <?php echo ($track && $track['album_id'] == $album['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($album['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <input type="text" class="form-control bg-dark text-light" id="genre" name="genre" 
                                               value="<?php echo $track ? htmlspecialchars($track['genre']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="duration" class="form-label">Duration (seconds) *</label>
                                        <input type="number" class="form-control bg-dark text-light" id="duration" name="duration" 
                                               value="<?php echo $track ? $track['duration'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="audio_file" class="form-label">Audio File <?php echo $action === 'add' ? '*' : ''; ?></label>
                                        <input type="file" class="form-control bg-dark text-light" id="audio_file" name="audio_file" 
                                               accept=".mp3,.wav,.ogg" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                        <?php if ($track && $track['file_path']): ?>
                                            <small class="text-muted">Current: <?php echo $track['file_path']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cover_image" class="form-label">Cover Image</label>
                                    <input type="file" class="form-control bg-dark text-light" id="cover_image" name="cover_image" 
                                           accept=".jpg,.jpeg,.png,.gif">
                                    <?php if ($track && $track['cover_image']): ?>
                                        <small class="text-muted">Current: <?php echo $track['cover_image']; ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="tracks.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        <?php echo $action === 'add' ? 'Add Track' : 'Update Track'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>