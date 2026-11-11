<?php
// process/signup.php - Volunteer Account Registration (FR-01)
require_once "../includes/db.php";
require_once "../includes/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../signup.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$fullName = trim($_POST["full_name"] ?? "");
$phoneNumber = trim($_POST["phone_number"] ?? "");
$residentialArea = trim($_POST["residential_area"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// 1. Validation: check empty fields
if (
    $username === "" ||
    $fullName === "" ||
    $phoneNumber === "" ||
    $residentialArea === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    $_SESSION["signup_error"] = "Please complete all required fields.";
    header("Location: ../signup.php");
    exit;
}

// 2. Validation: match passwords
if ($password !== $confirmPassword) {
    $_SESSION["signup_error"] = "Password and Confirm Password do not match.";
    header("Location: ../signup.php");
    exit;
}

// 3. Validation: password length
if (strlen($password) < 8) {
    $_SESSION["signup_error"] = "Password must be at least 8 characters.";
    header("Location: ../signup.php");
    exit;
}

// 4. Validation: check if username already exists
$check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION["signup_error"] = "This username is already in use. Please choose another one.";
    $check->close();
    header("Location: ../signup.php");
    exit;
}
$check->close();

// 5. Hash password securely
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$userRole = "Volunteer";
$accountStatus = "Active";

$stmt = $conn->prepare("
    INSERT INTO users
    (username, full_name, phone_number, residential_area, password_hash, user_role, account_status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssss",
    $username,
    $fullName,
    $phoneNumber,
    $residentialArea,
    $passwordHash,
    $userRole,
    $accountStatus
);

if ($stmt->execute()) {
    $_SESSION["signup_success"] = "Your volunteer account has been created successfully. Please log in.";
    $stmt->close();
    header("Location: ../login.php");
    exit;
} else {
    $_SESSION["signup_error"] = "Registration failed. Please try again. " . $conn->error;
    $stmt->close();
    header("Location: ../signup.php");
    exit;
}
?>
