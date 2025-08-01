<?php
require_once 'includes/functions.php';
startSession();

// Get featured playlists
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as creator_name, COUNT(pt.track_id) as track_count
    FROM playlists p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN playlist_tracks pt ON p.id = pt.playlist_id
    WHERE p.is_public = 1
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 1
");
$stmt->execute();
$featuredPlaylist = $stmt->fetch();

// Get recent albums
$stmt = $pdo->prepare("
    SELECT al.*, a.name as artist_name
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    ORDER BY al.created_at DESC
    LIMIT 6
");
$stmt->execute();
$recentAlbums = $stmt->fetchAll();

// Get AI recommendations if user is logged in
$aiRecommendations = [];
if (isLoggedIn()) {
    $aiRecommendations = generateAIRecommendations($_SESSION['user_id'], 4);
}

// Get menu items
$menuItems = getMenuItems();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playcloud - پخش موسیقی آنلاین</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-cloud-music-rain me-2"></i>Playcloud
            </a>
            
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="new-releases.php">New Releases</a>
                <a class="nav-link" href="news-feed.php">News Feed</a>
                <a class="nav-link" href="shuffle.php">Shuffle Play</a>
            </div>
            
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light me-3">
                    <i class="fas fa-microphone"></i>
                </button>
                
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php echo getCurrentUser()['full_name'] ?? getCurrentUser()['username']; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="my-playlists.php">My Playlists</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-section">
                    <h6 class="text-muted mb-3">Browse Music</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="albums.php">
                                <i class="fas fa-folder me-2"></i>Albums
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tracks.php">
                                <i class="fas fa-music me-2"></i>Tracks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="genres.php">
                                <i class="fas fa-tags me-2"></i>Genres
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h6 class="text-muted mb-3">Library</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="recently-played.php">
                                <i class="fas fa-clock me-2"></i>Recently Played
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="favorites.php">
                                <i class="fas fa-heart me-2"></i>Favorite Tracks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="charts.php">
                                <i class="fas fa-chart-line me-2"></i>Charts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="radio.php">
                                <i class="fas fa-broadcast-tower me-2"></i>Radio
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-6 col-lg-8 main-content">
                <?php echo displayMessages(); ?>
                
                <!-- Featured Playlist -->
                <?php if ($featuredPlaylist): ?>
                <div class="featured-playlist mb-4">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="text-uppercase text-warning mb-2">Curated Playlist</h6>
                                    <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($featuredPlaylist['name']); ?></h3>
                                    <p class="mb-3">
                                        <?php echo htmlspecialchars($featuredPlaylist['description']); ?>
                                    </p>
                                    <div class="d-flex align-items-center text-muted">
                                        <span class="me-3"><i class="fas fa-heart me-1"></i><?php echo number_format($featuredPlaylist['track_count']); ?> Likes</span>
                                        <span class="me-3"><i class="fas fa-music me-1"></i><?php echo $featuredPlaylist['track_count']; ?> Songs</span>
                                        <span><i class="fas fa-clock me-1"></i>13 hr 7 min</span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <img src="uploads/covers/<?php echo $featuredPlaylist['cover_image'] ?: 'default.jpg'; ?>" 
                                         class="img-fluid rounded" alt="Playlist Cover">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- AI Recommendations -->
                <?php if (!empty($aiRecommendations)): ?>
                <div class="mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-robot me-2 text-primary"></i>AI Recommendations for You
                    </h5>
                    <div class="row">
                        <?php foreach ($aiRecommendations as $track): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-dark border-0 track-card">
                                <img src="uploads/covers/<?php echo $track['cover_image'] ?: 'default.jpg'; ?>" 
                                     class="card-img-top" alt="Track Cover">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($track['title']); ?></h6>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($track['artist_name']); ?></p>
                                    <button class="btn btn-sm btn-primary play-btn" data-track-id="<?php echo $track['id']; ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Albums -->
                <div class="mb-4">
                    <h5 class="mb-3">Recent Albums</h5>
                    <div class="row">
                        <?php foreach ($recentAlbums as $album): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-dark border-0 album-card">
                                <img src="uploads/covers/<?php echo $album['cover_image'] ?: 'default.jpg'; ?>" 
                                     class="card-img-top" alt="Album Cover">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($album['title']); ?></h6>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($album['artist_name']); ?></p>
                                    <button class="btn btn-sm btn-primary play-btn" data-album-id="<?php echo $album['id']; ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar-section mb-4">
                    <h6 class="text-muted mb-3">Recent Activity</h6>
                    <div class="activity-item mb-3">
                        <small class="text-muted">Posted by Dungen • 5m</small>
                        <div class="d-flex align-items-center mt-2">
                            <i class="fas fa-chevron-left me-2"></i>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section mb-4">
                    <h6 class="text-muted mb-3">Merchandise</h6>
                    <div class="merchandise-card p-3 bg-dark border rounded">
                        <i class="fas fa-tshirt text-primary mb-2"></i>
                        <h6>Artist Merchandise</h6>
                        <small class="text-muted">Support your favorite artists</small>
                    </div>
                </div>

                <div class="sidebar-section mb-4">
                    <h6 class="text-muted mb-3">Live Performances</h6>
                    <div class="live-card p-3 bg-dark border rounded">
                        <h6>Dungen Live LP-2020</h6>
                        <div class="waveform mb-2">
                            <div class="wave-bar"></div>
                            <div class="wave-bar"></div>
                            <div class="wave-bar"></div>
                            <div class="wave-bar"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i>
                            </button>
                            <div>
                                <button class="btn btn-sm btn-outline-light me-1">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#playlists">Playlists</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#podcasts">Podcasts</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#albums">Albums</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="playlists">
                            <div class="playlist-item d-flex align-items-center mb-2">
                                <i class="fas fa-folder me-2 text-warning"></i>
                                <div>
                                    <small class="d-block">Indie Sadie</small>
                                    <small class="text-muted">2d ago</small>
                                </div>
                            </div>
                            <div class="playlist-item d-flex align-items-center mb-2">
                                <i class="fas fa-folder me-2 text-warning"></i>
                                <div>
                                    <small class="d-block">Boards of Canada (Full)</small>
                                    <small class="text-muted">4 Oct</small>
                                </div>
                            </div>
                            <div class="playlist-item d-flex align-items-center mb-2">
                                <i class="fas fa-folder me-2 text-warning"></i>
                                <div>
                                    <small class="d-block">IC122 at Prince Bar</small>
                                    <small class="text-muted">22 Sep</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Now Playing Bar -->
    <div class="now-playing-bar">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <img src="uploads/covers/default.jpg" class="now-playing-cover me-3" alt="Now Playing">
                        <div>
                            <div class="fw-bold">Lady Magnolia</div>
                            <div class="text-muted">Piero Umiliani</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-center align-items-center">
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button class="btn btn-link text-light me-3 play-pause-btn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-step-forward"></i>
                        </button>
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-random"></i>
                        </button>
                        <button class="btn btn-link text-light">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-primary" style="width: 45%"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-end align-items-center">
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="btn btn-link text-light me-3">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <div class="volume-slider me-3">
                            <input type="range" class="form-range" min="0" max="100" value="70">
                        </div>
                        <button class="btn btn-link text-light">
                            <i class="fas fa-cast"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/player.js"></script>
</body>
</html>