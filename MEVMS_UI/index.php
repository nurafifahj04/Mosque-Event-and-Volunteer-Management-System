<?php
// index.php - Main entry point
require_once "includes/session.php";
require_once "includes/auth.php";

if (is_logged_in()) {
    $role = $_SESSION["user_role"] ?? "";
    if ($role === "Head of the Mosque Committee") {
        header("Location: head-dashboard.php");
        exit;
    } elseif ($role === "Mosque Committee Member") {
        header("Location: committee-dashboard.php");
        exit;
    } else {
        header("Location: volunteer-dashboard.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>
