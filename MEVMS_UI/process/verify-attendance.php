<?php
// process/verify-attendance.php - Committee Attendance Verification (FR-13)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../committee-attendance.php");
    exit;
}

$attendanceId = intval($_POST["attendance_id"] ?? 0);
$eventId = intval($_POST["event_id"] ?? 0);
$committeeId = $_SESSION["user_id"];

if ($attendanceId <= 0) {
    $_SESSION["error_message"] = "Invalid attendance record.";
    header("Location: ../committee-attendance.php" . ($eventId ? "?event_id=$eventId" : ""));
    exit;
}

// Update attendance record to Verified
$stmt = $conn->prepare("
    UPDATE attendance_records
    SET verification_status = 'Verified', verified_by = ?, verified_at = NOW()
    WHERE attendance_id = ?
");
$stmt->bind_param("ii", $committeeId, $attendanceId);

if ($stmt->execute()) {
    $stmt->close();
    
    // Also mark token record as distributed if present
    $conn->query("
        UPDATE token_records tr
        JOIN attendance_records ar ON tr.registration_id = ar.registration_id
        SET tr.token_status = 'Distributed', tr.distributed_at = NOW()
        WHERE ar.attendance_id = $attendanceId AND tr.token_status = 'Pending'
    ");

    $_SESSION["success_message"] = "Volunteer attendance verified successfully.";
} else {
    $_SESSION["error_message"] = "Failed to verify attendance: " . $conn->error;
    $stmt->close();
}

header("Location: ../committee-attendance.php" . ($eventId ? "?event_id=$eventId" : ""));
exit;
?>
