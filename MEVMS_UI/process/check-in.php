<?php
// process/check-in.php - Volunteer Check-In Attendance (FR-12)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role("Volunteer");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../volunteer-checkin.php");
    exit;
}

$volunteerId = $_SESSION["user_id"];
$registrationId = intval($_POST["registration_id"] ?? 0);

if ($registrationId <= 0) {
    $_SESSION["error_message"] = "Invalid registration selected for check-in.";
    header("Location: ../volunteer-checkin.php");
    exit;
}

// 1. Verify this registration belongs to the logged-in volunteer and is approved
$stmt = $conn->prepare("
    SELECT vr.registration_id, vr.registration_status, e.event_name
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    WHERE vr.registration_id = ? AND vr.volunteer_id = ?
");
$stmt->bind_param("ii", $registrationId, $volunteerId);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg) {
    $_SESSION["error_message"] = "Registration record not found or does not belong to you.";
    header("Location: ../volunteer-checkin.php");
    exit;
}

if ($reg["registration_status"] !== "Approved") {
    $_SESSION["error_message"] = "Only approved volunteers can check in. Your status is currently: " . $reg["registration_status"];
    header("Location: ../volunteer-status.php");
    exit;
}

// 2. Check if already checked in
$stmt = $conn->prepare("SELECT attendance_id, attendance_status, check_in_time FROM attendance_records WHERE registration_id = ?");
$stmt->bind_param("i", $registrationId);
$stmt->execute();
$att = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($att && $att["attendance_status"] === "Present") {
    $time = date("g:i A", strtotime($att["check_in_time"]));
    $_SESSION["success_message"] = "You have already checked in at $time. Your attendance is recorded.";
    header("Location: ../volunteer-status.php");
    exit;
}

// 3. Record check-in attendance
if ($att) {
    $updateStmt = $conn->prepare("
        UPDATE attendance_records
        SET check_in_time = NOW(), attendance_status = 'Present', verification_status = 'Pending'
        WHERE registration_id = ?
    ");
    $updateStmt->bind_param("i", $registrationId);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    $insertStmt = $conn->prepare("
        INSERT INTO attendance_records (registration_id, check_in_time, attendance_status, verification_status)
        VALUES (?, NOW(), 'Present', 'Pending')
    ");
    $insertStmt->bind_param("i", $registrationId);
    $insertStmt->execute();
    $insertStmt->close();
}

$_SESSION["success_message"] = "Check-in successful for " . htmlspecialchars($reg["event_name"]) . "! Your arrival has been recorded.";
header("Location: ../volunteer-status.php");
exit;
?>
