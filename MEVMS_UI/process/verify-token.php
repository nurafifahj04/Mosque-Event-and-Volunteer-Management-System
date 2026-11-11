<?php
// process/verify-token.php - Head of Committee Verify Token Status (FR-15)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role("Head of the Mosque Committee");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../head-token-verify.php");
    exit;
}

$tokenId = intval($_POST["token_id"] ?? 0);
$eventId = intval($_POST["event_id"] ?? 0);
$headId = $_SESSION["user_id"];

if ($tokenId <= 0) {
    $_SESSION["error_message"] = "Invalid token record.";
    header("Location: ../head-token-verify.php" . ($eventId ? "?event_id=$eventId" : ""));
    exit;
}

$stmt = $conn->prepare("
    UPDATE token_records
    SET verification_status = 'Verified', token_status = 'Distributed', verified_by = ?, verified_at = NOW()
    WHERE token_id = ?
");
$stmt->bind_param("ii", $headId, $tokenId);

if ($stmt->execute()) {
    $_SESSION["success_message"] = "Volunteer token distribution verified successfully.";
} else {
    $_SESSION["error_message"] = "Error verifying token: " . $conn->error;
}
$stmt->close();

header("Location: ../head-token-verify.php" . ($eventId ? "?event_id=$eventId" : ""));
exit;
?>
