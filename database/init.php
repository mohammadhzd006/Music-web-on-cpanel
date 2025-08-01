<?php
// Database Initialization Script
// This file creates all necessary tables and initial data

require_once '../config/database.php';

function initializeDatabase() {
    try {
        $pdo = getDBConnection();
        
        // Read and execute the schema file
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
        } else {
            // Fallback: Create tables directly if schema file doesn't exist
            createTablesDirectly($pdo);
        }
        
        // Create default admin user
        createDefaultAdmin($pdo);
        
        // Insert sample data
        insertSampleData($pdo);
        
        return true;
        
    } catch (PDOException $e) {
        throw new Exception("Database initialization failed: " . $e->getMessage());
    }
}

function createTablesDirectly($pdo) {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        avatar VARCHAR(255),
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Artists table
    $pdo->exec("CREATE TABLE IF NOT EXISTS artists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        bio TEXT,
        avatar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Albums table
    $pdo->exec("CREATE TABLE IF NOT EXISTS albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        artist_id INT,
        cover_image VARCHAR(255),
        release_date DATE,
        genre VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL
    )");
    
    // Tracks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tracks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        artist_id INT,
        album_id INT,
        duration INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        cover_image VARCHAR(255),
        genre VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL,
        FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL
    )");
    
    // Playlists table
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        cover_image VARCHAR(255),
        user_id INT,
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Playlist tracks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_tracks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        playlist_id INT,
        track_id INT,
        position INT,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
        FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
    )");
    
    // User likes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        track_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_track (user_id, track_id)
    )");
    
    // User listening history
    $pdo->exec("CREATE TABLE IF NOT EXISTS listening_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        track_id INT,
        listened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
    )");
    
    // Pages table for admin
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        content TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Menu items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        url VARCHAR(255),
        parent_id INT DEFAULT NULL,
        position INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE SET NULL
    )");
    
    // AI recommendations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        track_id INT,
        score DECIMAL(5,4),
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
    )");
}

function createDefaultAdmin($pdo) {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, is_admin) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@gbtech.ir', $adminPassword, 'Administrator', true]);
    }
}

function insertSampleData($pdo) {
    // Insert sample artists
    $artists = [
        ['name' => 'The Weeknd', 'bio' => 'Canadian singer and songwriter'],
        ['name' => 'Dua Lipa', 'bio' => 'British singer and songwriter'],
        ['name' => 'Drake', 'bio' => 'Canadian rapper and singer'],
        ['name' => 'Ariana Grande', 'bio' => 'American singer and actress'],
        ['name' => 'Post Malone', 'bio' => 'American rapper and singer']
    ];
    
    foreach ($artists as $artist) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO artists (name, bio) VALUES (?, ?)
        ");
        $stmt->execute([$artist['name'], $artist['bio']]);
    }
    
    // Insert sample albums
    $albums = [
        ['title' => 'After Hours', 'artist_id' => 1, 'genre' => 'R&B'],
        ['title' => 'Future Nostalgia', 'artist_id' => 2, 'genre' => 'Pop'],
        ['title' => 'Scorpion', 'artist_id' => 3, 'genre' => 'Hip Hop'],
        ['title' => 'Thank U, Next', 'artist_id' => 4, 'genre' => 'Pop'],
        ['title' => 'Hollywood\'s Bleeding', 'artist_id' => 5, 'genre' => 'Hip Hop']
    ];
    
    foreach ($albums as $album) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO albums (title, artist_id, genre) VALUES (?, ?, ?)
        ");
        $stmt->execute([$album['title'], $album['artist_id'], $album['genre']]);
    }
    
    // Insert sample tracks
    $tracks = [
        ['title' => 'Blinding Lights', 'artist_id' => 1, 'album_id' => 1, 'duration' => 200, 'genre' => 'R&B'],
        ['title' => 'Don\'t Start Now', 'artist_id' => 2, 'album_id' => 2, 'duration' => 183, 'genre' => 'Pop'],
        ['title' => 'God\'s Plan', 'artist_id' => 3, 'album_id' => 3, 'duration' => 198, 'genre' => 'Hip Hop'],
        ['title' => '7 rings', 'artist_id' => 4, 'album_id' => 4, 'duration' => 178, 'genre' => 'Pop'],
        ['title' => 'Circles', 'artist_id' => 5, 'album_id' => 5, 'duration' => 215, 'genre' => 'Hip Hop']
    ];
    
    foreach ($tracks as $track) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO tracks (title, artist_id, album_id, duration, genre, file_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $track['title'], 
            $track['artist_id'], 
            $track['album_id'], 
            $track['duration'], 
            $track['genre'],
            'sample_' . strtolower(str_replace(' ', '_', $track['title'])) . '.mp3'
        ]);
    }
    
    // Insert sample playlists
    $playlists = [
        ['name' => 'Top Hits 2024', 'description' => 'Best songs of 2024', 'user_id' => 1],
        ['name' => 'Chill Vibes', 'description' => 'Relaxing music collection', 'user_id' => 1],
        ['name' => 'Workout Mix', 'description' => 'High energy workout songs', 'user_id' => 1]
    ];
    
    foreach ($playlists as $playlist) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO playlists (name, description, user_id, is_public) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$playlist['name'], $playlist['description'], $playlist['user_id']]);
    }
}

// Run initialization if this file is called directly
if (basename($_SERVER['PHP_SELF']) == 'init.php') {
    try {
        initializeDatabase();
        echo "✅ Database initialized successfully!<br>";
        echo "✅ Default admin user created (admin/admin123)<br>";
        echo "✅ Sample data inserted<br>";
        echo "<br><a href='../index.php'>Go to Playcloud</a>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}
?>