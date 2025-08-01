<?php
require_once '../includes/functions.php';
startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}

try {
    $recommendations = generateAIRecommendations($_SESSION['user_id'], 10);
    
    jsonResponse(['success' => true, 'recommendations' => $recommendations]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Server error'], 500);
}
?>