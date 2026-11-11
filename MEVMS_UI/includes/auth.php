<?php
// includes/auth.php - Authentication and Role-based Access Control (FR-03)
require_once __DIR__ . '/session.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['login_error'] = "Please log in to access this page.";
        header("Location: login.php");
        exit;
    }
}

function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    $current_role = $_SESSION['user_role'] ?? '';
    if (!in_array($current_role, $allowed_roles, true)) {
        // Redirect to their respective dashboard
        if ($current_role === 'Head of the Mosque Committee') {
            header("Location: head-dashboard.php");
        } elseif ($current_role === 'Mosque Committee Member') {
            header("Location: committee-dashboard.php");
        } else {
            header("Location: volunteer-dashboard.php");
        }
        exit;
    }
}

function current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'role' => $_SESSION['user_role'] ?? 'Guest'
    ];
}
?>
