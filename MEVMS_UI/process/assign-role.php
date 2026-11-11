<?php
// process/assign-role.php - Assign / Reassign Volunteer Role (FR-09, Lecturer Requirement)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../committee-assign-roles.php");
    exit;
}

$registrationId = intval($_POST["registration_id"] ?? 0);
$eventRoleId = intval($_POST["event_role_id"] ?? 0);
$committeeId = $_SESSION["user_id"];
$eventId = intval($_POST["event_id"] ?? 0);

if ($registrationId <= 0 || $eventRoleId <= 0) {
    $_SESSION["error_message"] = "Please select a valid volunteer and role to assign.";
    header("Location: ../committee-assign-roles.php" . ($eventId ? "?event_id=$eventId" : ""));
    exit;
}

// 1. Verify role exists and get role quota
$stmt = $conn->prepare("SELECT role_name, role_quota, event_id FROM event_roles WHERE event_role_id = ?");
$stmt->bind_param("i", $eventRoleId);
$stmt->execute();
$role = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$role) {
    $_SESSION["error_message"] = "Role not found.";
    header("Location: ../committee-assign-roles.php" . ($eventId ? "?event_id=$eventId" : ""));
    exit;
}

// 2. Check current assignments for this role (excluding this registration if reassigning)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_assigned
    FROM role_assignments
    WHERE event_role_id = ? AND registration_id != ?
");
$stmt->bind_param("ii", $eventRoleId, $registrationId);
$stmt->execute();
$assignedRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($assignedRow["total_assigned"] >= $role["role_quota"]) {
    $_SESSION["error_message"] = "Role '" . htmlspecialchars($role["role_name"]) . "' is already full (" . $role["role_quota"] . " max). Cannot assign more volunteers.";
    header("Location: ../committee-assign-roles.php" . ($eventId ? "?event_id=$eventId" : ""));
    exit;
}

// 3. Check if an assignment already exists for this registration
$stmt = $conn->prepare("SELECT assignment_id FROM role_assignments WHERE registration_id = ?");
$stmt->bind_param("i", $registrationId);
$stmt->execute();
$existingAssignment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingAssignment) {
    // Re-assign role
    $stmt = $conn->prepare("
        UPDATE role_assignments
        SET event_role_id = ?, assigned_by = ?, assigned_at = NOW()
        WHERE registration_id = ?
    ");
    $stmt->bind_param("iii", $eventRoleId, $committeeId, $registrationId);
    $stmt->execute();
    $stmt->close();
    $_SESSION["success_message"] = "Volunteer role successfully re-assigned to '" . htmlspecialchars($role["role_name"]) . "'.";
} else {
    // New role assignment
    $stmt = $conn->prepare("
        INSERT INTO role_assignments (registration_id, event_role_id, assigned_by)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iii", $registrationId, $eventRoleId, $committeeId);
    $stmt->execute();
    $stmt->close();
    $_SESSION["success_message"] = "Volunteer role '" . htmlspecialchars($role["role_name"]) . "' assigned successfully.";
}

header("Location: ../committee-assign-roles.php" . ($eventId ? "?event_id=$eventId" : ""));
exit;
?>
