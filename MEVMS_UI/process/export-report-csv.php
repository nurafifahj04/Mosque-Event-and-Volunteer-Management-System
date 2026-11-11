<?php
// process/export-report-csv.php - Export Report to CSV / Excel (FR-17)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role("Head of the Mosque Committee");

$eventId = intval($_GET["event_id"] ?? 0);
$month = trim($_GET["month"] ?? "");

$whereClauses = [];
$params = [];
$types = "";

if ($eventId > 0) {
    $whereClauses[] = "e.event_id = ?";
    $params[] = $eventId;
    $types .= "i";
}

if ($month !== "") {
    $whereClauses[] = "DATE_FORMAT(e.event_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT 
        e.event_name,
        e.event_date,
        e.location,
        e.volunteer_quota,
        e.planned_budget,
        u.full_name AS volunteer_name,
        u.phone_number,
        u.residential_area,
        COALESCE(er.role_name, 'Unassigned') AS assigned_role,
        COALESCE(ar.attendance_status, 'Not Checked In') AS attendance_status,
        COALESCE(ar.check_in_time, 'N/A') AS check_in_time,
        COALESCE(ar.verification_status, 'Pending') AS attendance_verified,
        COALESCE(tr.token_type, 'N/A') AS token_type,
        COALESCE(tr.token_status, 'Pending') AS token_status,
        COALESCE(tr.verification_status, 'Pending') AS token_verified
    FROM events e
    LEFT JOIN volunteer_registrations vr ON e.event_id = vr.event_id AND vr.registration_status = 'Approved'
    LEFT JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    LEFT JOIN token_records tr ON vr.registration_id = tr.registration_id
    $whereSql
    ORDER BY e.event_date DESC, u.full_name ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = "MEVMS_Report_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");
// UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Header row
fputcsv($output, [
    "Event Name",
    "Event Date",
    "Location",
    "Volunteer Quota",
    "Planned Budget (RM)",
    "Volunteer Name",
    "Phone Number",
    "Residential Area",
    "Assigned Role",
    "Attendance Status",
    "Check-In Time",
    "Attendance Verified",
    "Token Type",
    "Token Status",
    "Token Verified"
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row["event_name"],
        $row["event_date"],
        $row["location"],
        $row["volunteer_quota"],
        $row["planned_budget"],
        $row["volunteer_name"] ?? "No volunteers registered",
        $row["phone_number"] ?? "-",
        $row["residential_area"] ?? "-",
        $row["assigned_role"],
        $row["attendance_status"],
        $row["check_in_time"],
        $row["attendance_verified"],
        $row["token_type"],
        $row["token_status"],
        $row["token_verified"]
    ]);
}

fclose($output);
exit;
?>
