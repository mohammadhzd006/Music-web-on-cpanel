<?php
require_once '../includes/functions.php';
startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
}

$preferences = $input['preferences'] ?? [];
$playlistName = $input['name'] ?? 'AI Generated Playlist';
$description = $input['description'] ?? 'Generated based on your preferences';

try {
    $pdo = getDBConnection();
    
    // Create playlist
    $stmt = $pdo->prepare("
        INSERT INTO playlists (name, description, user_id, is_public) 
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$playlistName, $description, $_SESSION['user_id']]);
    $playlistId = $pdo->lastInsertId();
    
    // Generate tracks based on preferences
    $genres = $preferences['genres'] ?? [];
    $artists = $preferences['artists'] ?? [];
    $mood = $preferences['mood'] ?? '';
    $duration = $preferences['duration'] ?? 60; // minutes
    
    $whereConditions = [];
    $params = [];
    
    if (!empty($genres)) {
        $placeholders = str_repeat('?,', count($genres) - 1) . '?';
        $whereConditions[] = "t.genre IN ($placeholders)";
        $params = array_merge($params, $genres);
    }
    
    if (!empty($artists)) {
        $placeholders = str_repeat('?,', count($artists) - 1) . '?';
        $whereConditions[] = "a.name IN ($placeholders)";
        $params = array_merge($params, $artists);
    }
    
    // Add mood-based filtering
    if ($mood) {
        $moodKeywords = [
            'happy' => ['upbeat', 'energetic', 'pop', 'dance'],
            'sad' => ['slow', 'melancholic', 'ballad', 'acoustic'],
            'relaxed' => ['ambient', 'chill', 'jazz', 'classical'],
            'energetic' => ['rock', 'electronic', 'fast', 'dance']
        ];
        
        if (isset($moodKeywords[$mood])) {
            $keywords = $moodKeywords[$mood];
            $keywordPlaceholders = str_repeat('?,', count($keywords) - 1) . '?';
            $whereConditions[] = "(t.genre IN ($keywordPlaceholders) OR t.title LIKE '%$mood%')";
            $params = array_merge($params, $keywords);
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' OR ', $whereConditions) : '';
    
    // Calculate target track count based on duration
    $targetTracks = max(10, min(50, $duration * 2)); // 2 tracks per minute, between 10-50 tracks
    
    $sql = "
        SELECT t.*, a.name as artist_name, al.title as album_title
        FROM tracks t
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        $whereClause
        ORDER BY RAND()
        LIMIT ?
    ";
    
    $params[] = $targetTracks;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tracks = $stmt->fetchAll();
    
    // Add tracks to playlist
    if (!empty($tracks)) {
        $stmt = $pdo->prepare("
            INSERT INTO playlist_tracks (playlist_id, track_id, position) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($tracks as $index => $track) {
            $stmt->execute([$playlistId, $track['id'], $index + 1]);
        }
    }
    
    // Get the complete playlist
    $playlistTracks = getPlaylistTracks($playlistId);
    
    jsonResponse([
        'success' => true, 
        'playlist' => [
            'id' => $playlistId,
            'name' => $playlistName,
            'description' => $description,
            'tracks' => $playlistTracks,
            'track_count' => count($playlistTracks)
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}
?>