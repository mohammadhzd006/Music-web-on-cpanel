<?php
require_once '../includes/functions.php';
startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$trackId = $_GET['id'] ?? null;

if (!$trackId) {
    jsonResponse(['success' => false, 'error' => 'Track ID is required'], 400);
}

try {
    $track = getTrackInfo($trackId);
    
    if (!$track) {
        jsonResponse(['success' => false, 'error' => 'Track not found'], 404);
    }
    
    // Log playback if user is logged in
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], $trackId);
    }
    
    jsonResponse(['success' => true, 'track' => $track]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Server error'], 500);
}
?>