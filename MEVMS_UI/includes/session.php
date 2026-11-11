<?php
// includes/session.php - Starts session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
