<?php
// process/review-registration.php
// Approve or Reject Volunteer Registration (FR-07, FR-08)

require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../committee-registrations.php");
    exit;
}

$registrationId = intval($_POST["registration_id"] ?? 0);
$decision       = trim($_POST["decision"] ?? "");
$decisionComment = trim($_POST["decision_comment"] ?? "");
$committeeId    = intval($_SESSION["user_id"] ?? 0);

if ($registrationId <= 0 || !in_array($decision, ["Approve", "Reject"], true)) {
    $_SESSION["error_message"] = "Invalid registration review action.";
    header("Location: ../committee-registrations.php");
    exit;
}

$status = ($decision === "Approve") ? "Approved" : "Rejected";

// 1. Get registration details
$stmt = $conn->prepare("
    SELECT 
        vr.registration_id,
        vr.event_id,
        vr.volunteer_id,
        vr.preferred_event_role_id,
        u.full_name,
        e.event_name
    FROM volunteer_registrations vr
    JOIN users u ON vr.volunteer_id = u.user_id
    JOIN events e ON vr.event_id = e.event_id
    WHERE vr.registration_id = ?
    LIMIT 1
");

if (!$stmt) {
    $_SESSION["error_message"] = "Database prepare failed: " . $conn->error;
    header("Location: ../committee-registrations.php");
    exit;
}

$stmt->bind_param("i", $registrationId);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg) {
    $_SESSION["error_message"] = "Registration record not found.";
    header("Location: ../committee-registrations.php");
    exit;
}

// 2. Update registration status
$stmt = $conn->prepare("
    UPDATE volunteer_registrations
    SET registration_status = ?,
        decision_by = ?,
        decision_at = NOW(),
        decision_comment = ?
    WHERE registration_id = ?
");

if (!$stmt) {
    $_SESSION["error_message"] = "Database prepare failed: " . $conn->error;
    header("Location: ../committee-registrations.php");
    exit;
}

$stmt->bind_param("sisi", $status, $committeeId, $decisionComment, $registrationId);
$updated = $stmt->execute();
$stmt->close();

if (!$updated) {
    $_SESSION["error_message"] = "Failed to update registration: " . $conn->error;
    header("Location: ../committee-registrations.php");
    exit;
}

if ($status === "Approved") {

    // 3. If the volunteer selected a preferred role, assign it only if quota is available
    if (!empty($reg["preferred_event_role_id"])) {

        $preferredRoleId = intval($reg["preferred_event_role_id"]);

        $quotaStmt = $conn->prepare("
            SELECT 
                er.role_quota,
                COUNT(ra.assignment_id) AS current_assigned
            FROM event_roles er
            LEFT JOIN role_assignments ra 
                ON er.event_role_id = ra.event_role_id
            WHERE er.event_role_id = ?
            GROUP BY er.event_role_id, er.role_quota
            LIMIT 1
        ");

        $quotaStmt->bind_param("i", $preferredRoleId);
        $quotaStmt->execute();
        $quotaRow = $quotaStmt->get_result()->fetch_assoc();
        $quotaStmt->close();

        $hasVacancy = $quotaRow && ($quotaRow["current_assigned"] < $quotaRow["role_quota"]);

        if ($hasVacancy) {
            $assignStmt = $conn->prepare("
                INSERT INTO role_assignments 
                    (registration_id, event_role_id, assigned_by, assigned_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    event_role_id = VALUES(event_role_id),
                    assigned_by = VALUES(assigned_by),
                    assigned_at = NOW()
            ");

            $assignStmt->bind_param("iii", $registrationId, $preferredRoleId, $committeeId);
            $assignStmt->execute();
            $assignStmt->close();
        }
    }

    // 4. Ensure attendance record exists
    $attendanceStmt = $conn->prepare("
        INSERT IGNORE INTO attendance_records 
            (registration_id, attendance_status, verification_status)
        VALUES (?, 'Not Checked In', 'Pending')
    ");

    $attendanceStmt->bind_param("i", $registrationId);
    $attendanceStmt->execute();
    $attendanceStmt->close();

    // 5. Ensure token record exists
    $tokenStmt = $conn->prepare("
        INSERT IGNORE INTO token_records 
            (registration_id, token_type, token_status, verification_status)
        VALUES (?, 'T-Shirt and Certificate', 'Pending', 'Pending')
    ");

    $tokenStmt->bind_param("i", $registrationId);
    $tokenStmt->execute();
    $tokenStmt->close();

    $_SESSION["success_message"] = "Registration for " . $reg["full_name"] . " approved successfully.";
} else {
    $_SESSION["success_message"] = "Registration for " . $reg["full_name"] . " has been rejected.";
}

header("Location: ../committee-registrations.php");
exit;
?>