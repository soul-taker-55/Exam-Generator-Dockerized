<?php
session_start();

// Clear all session variables
$_SESSION = [];

// If there's a session cookie, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear remember_me cookie if set
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/");
}

// Redirect to login page
header("Location: index.php");
exit();
