<?php
// process/login.php - User Login & Role Routing (FR-02, FR-03)
require_once "../includes/db.php";
require_once "../includes/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    $_SESSION["login_error"] = "Please enter both username and password.";
    header("Location: ../login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT user_id, username, full_name, password_hash, user_role, account_status
    FROM users
    WHERE username = ?
");

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
    $_SESSION["login_error"] = "Invalid username or password.";
    header("Location: ../login.php");
    exit;
}

if ($user["account_status"] !== "Active") {
    $_SESSION["login_error"] = "Your account is inactive. Please contact the mosque committee.";
    header("Location: ../login.php");
    exit;
}

// Set session
$_SESSION["user_id"] = $user["user_id"];
$_SESSION["username"] = $user["username"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["user_role"] = $user["user_role"];

// Route to correct portal dashboard based on role (FR-03)
if ($user["user_role"] === "Head of the Mosque Committee") {
    header("Location: ../head-dashboard.php");
    exit;
}

if ($user["user_role"] === "Mosque Committee Member") {
    header("Location: ../committee-dashboard.php");
    exit;
}

header("Location: ../volunteer-dashboard.php");
exit;
?>
