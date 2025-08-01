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

if (!$input || !isset($input['track_id'])) {
    jsonResponse(['success' => false, 'error' => 'Track ID is required'], 400);
}

try {
    $trackId = (int)$input['track_id'];
    
    // Log the playback activity
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO listening_history (user_id, track_id, listened_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $trackId]);
    
    jsonResponse(['success' => true, 'message' => 'Playback logged successfully']);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Server error'], 500);
}
?>