<?php
// process/record-event.php - Record Approved Event (FR-04)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../committee-record-event.php");
    exit;
}

$eventName = trim($_POST["event_name"] ?? "");
$eventDescription = trim($_POST["event_description"] ?? "");
$eventDate = $_POST["event_date"] ?? "";
$startTime = $_POST["start_time"] ?? "";
$endTime = !empty($_POST["end_time"]) ? $_POST["end_time"] : null;
$location = trim($_POST["location"] ?? "");
$eventPicId = !empty($_POST["event_pic_id"]) ? intval($_POST["event_pic_id"]) : $_SESSION["user_id"];
$volunteerQuota = !empty($_POST["volunteer_quota"]) ? intval($_POST["volunteer_quota"]) : 3;
$plannedBudget = !empty($_POST["planned_budget"]) ? floatval($_POST["planned_budget"]) : 0.00;
$fundingSource = trim($_POST["funding_source"] ?? "");
$recordedBy = $_SESSION["user_id"];
$eventStatus = "Open";

if ($eventName === "" || $eventDate === "" || $startTime === "" || $location === "" || $volunteerQuota <= 0) {
    $_SESSION["error_message"] = "Please fill in all required event details (name, date, start time, location, volunteer quota).";
    header("Location: ../committee-record-event.php");
    exit;
}

// Insert into events table
$stmt = $conn->prepare("
    INSERT INTO events
    (event_name, event_description, event_date, start_time, end_time, location, event_pic_id, volunteer_quota, planned_budget, funding_source, event_status, recorded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "ssssssiidssi",
    $eventName,
    $eventDescription,
    $eventDate,
    $startTime,
    $endTime,
    $location,
    $eventPicId,
    $volunteerQuota,
    $plannedBudget,
    $fundingSource,
    $eventStatus,
    $recordedBy
);

if ($stmt->execute()) {
    $newEventId = $stmt->insert_id;
    $stmt->close();

    // Insert volunteer roles (role 1 required or primary, optional roles 2 & 3)
    $rolesToInsert = [];
    if (!empty($_POST["role_name"])) {
        $rolesToInsert[] = [
            'name' => trim($_POST["role_name"]),
            'quota' => !empty($_POST["role_quota"]) ? intval($_POST["role_quota"]) : 1,
            'unit' => trim($_POST["team_unit"] ?? "Unit A"),
            'skills' => trim($_POST["required_skills"] ?? ""),
            'desc' => trim($_POST["role_description"] ?? "Support event activities")
        ];
    }

    // Secondary roles if provided in form
    for ($i = 2; $i <= 3; $i++) {
        if (!empty($_POST["role_name_$i"])) {
            $rolesToInsert[] = [
                'name' => trim($_POST["role_name_$i"]),
                'quota' => !empty($_POST["role_quota_$i"]) ? intval($_POST["role_quota_$i"]) : 1,
                'unit' => trim($_POST["team_unit_$i"] ?? "Unit $i"),
                'skills' => trim($_POST["required_skills_$i"] ?? ""),
                'desc' => trim($_POST["role_description_$i"] ?? "Support event activities")
            ];
        }
    }

    // Default role if none specified
    if (empty($rolesToInsert)) {
        $rolesToInsert[] = [
            'name' => 'General Volunteer',
            'quota' => $volunteerQuota,
            'unit' => 'General Unit',
            'skills' => 'Willing to help',
            'desc' => 'General assistance during event'
        ];
    }

    $roleStmt = $conn->prepare("
        INSERT INTO event_roles
        (event_id, role_name, role_description, team_unit, required_skills, role_quota)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($rolesToInsert as $r) {
        $roleStmt->bind_param("issssi", $newEventId, $r['name'], $r['desc'], $r['unit'], $r['skills'], $r['quota']);
        $roleStmt->execute();
    }
    $roleStmt->close();

    $_SESSION["success_message"] = "Approved event '" . htmlspecialchars($eventName) . "' recorded successfully with volunteer quota of $volunteerQuota.";
    header("Location: ../committee-dashboard.php");
    exit;
} else {
    $_SESSION["error_message"] = "Error recording event: " . $conn->error;
    $stmt->close();
    header("Location: ../committee-record-event.php");
    exit;
}
?>
