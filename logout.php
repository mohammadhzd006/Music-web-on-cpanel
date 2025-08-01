<?php
require_once 'includes/functions.php';
startSession();

// Clear all session data
session_destroy();

// Redirect to login page
redirect('login.php');
?>