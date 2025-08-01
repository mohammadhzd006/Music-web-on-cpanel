<?php
require_once '../includes/functions.php';
startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$query = $_GET['q'] ?? '';

if (empty($query)) {
    jsonResponse(['success' => false, 'error' => 'Search query is required'], 400);
}

try {
    $pdo = getDBConnection();
    $searchTerm = '%' . $query . '%';
    
    // Search tracks
    $stmt = $pdo->prepare("
        SELECT t.*, a.name as artist_name, al.title as album_title
        FROM tracks t
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        WHERE t.title LIKE ? OR a.name LIKE ? OR al.title LIKE ?
        ORDER BY t.title
        LIMIT 20
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $tracks = $stmt->fetchAll();
    
    // Search artists
    $stmt = $pdo->prepare("
        SELECT * FROM artists 
        WHERE name LIKE ? 
        ORDER BY name 
        LIMIT 10
    ");
    $stmt->execute([$searchTerm]);
    $artists = $stmt->fetchAll();
    
    // Search albums
    $stmt = $pdo->prepare("
        SELECT al.*, a.name as artist_name
        FROM albums al
        LEFT JOIN artists a ON al.artist_id = a.id
        WHERE al.title LIKE ? OR a.name LIKE ?
        ORDER BY al.title
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $albums = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'results' => [
            'tracks' => $tracks,
            'artists' => $artists,
            'albums' => $albums,
            'total' => count($tracks) + count($artists) + count($albums)
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Server error'], 500);
}
?>