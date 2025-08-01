<?php
require_once 'config/database.php';

// Session management
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Authentication functions
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    startSession();
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function getCurrentUser() {
    startSession();
    if (!isLoggedIn()) return null;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Security functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// File upload functions
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return false;
    }
    
    $fileName = uniqid() . '.' . $extension;
    $filePath = $destination . '/' . $fileName;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $fileName;
    }
    
    return false;
}

// Music functions
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}

function getTrackInfo($trackId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, a.name as artist_name, al.title as album_title, al.cover_image as album_cover
        FROM tracks t
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        WHERE t.id = ?
    ");
    $stmt->execute([$trackId]);
    return $stmt->fetch();
}

function getPlaylistTracks($playlistId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, a.name as artist_name, al.title as album_title, pt.position
        FROM playlist_tracks pt
        JOIN tracks t ON pt.track_id = t.id
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        WHERE pt.playlist_id = ?
        ORDER BY pt.position
    ");
    $stmt->execute([$playlistId]);
    return $stmt->fetchAll();
}

// AI Recommendation functions
function generateAIRecommendations($userId, $limit = 10) {
    $pdo = getDBConnection();
    
    // Get user's listening history and likes
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.genre, t.artist_id
        FROM listening_history lh
        JOIN tracks t ON lh.track_id = t.id
        WHERE lh.user_id = ?
        ORDER BY lh.listened_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $userPreferences = $stmt->fetchAll();
    
    if (empty($userPreferences)) {
        // Return popular tracks if no history
        $stmt = $pdo->prepare("
            SELECT t.*, a.name as artist_name, al.title as album_title
            FROM tracks t
            LEFT JOIN artists a ON t.artist_id = a.id
            LEFT JOIN albums al ON t.album_id = al.id
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // Generate recommendations based on user preferences
    $genres = array_column($userPreferences, 'genre');
    $artistIds = array_column($userPreferences, 'artist_id');
    
    $placeholders = str_repeat('?,', count($genres) - 1) . '?';
    $artistPlaceholders = str_repeat('?,', count($artistIds) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT t.*, a.name as artist_name, al.title as album_title
        FROM tracks t
        LEFT JOIN artists a ON t.artist_id = a.id
        LEFT JOIN albums al ON t.album_id = al.id
        WHERE (t.genre IN ($placeholders) OR t.artist_id IN ($artistPlaceholders))
        AND t.id NOT IN (
            SELECT track_id FROM listening_history WHERE user_id = ?
        )
        ORDER BY RAND()
        LIMIT ?
    ");
    
    $params = array_merge($genres, $artistIds, [$userId, $limit]);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Menu functions
function getMenuItems() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM menu_items 
        WHERE is_active = 1 
        ORDER BY position, id
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Page functions
function getPageBySlug($slug) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Utility functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function logActivity($userId, $action, $details = '') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO listening_history (user_id, track_id, listened_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$userId, $action]);
}

// Error handling
function handleError($error, $redirect = null) {
    $_SESSION['error'] = $error;
    if ($redirect) {
        redirect($redirect);
    }
}

function handleSuccess($message, $redirect = null) {
    $_SESSION['success'] = $message;
    if ($redirect) {
        redirect($redirect);
    }
}

// Display messages
function displayMessages() {
    startSession();
    $output = '';
    
    if (isset($_SESSION['error'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    
    return $output;
}
?>