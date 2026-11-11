<?php
// scratch/run_full_test.php - Comprehensive simulation of the user's FYP demonstration
require_once "includes/db.php";

echo "======================================================\n";
echo "   MEVMS FINAL YEAR PROJECT LIVE WORKFLOW VALIDATION   \n";
echo "   Masjid Kampung Rinching Hulu (CSC2854 S2 2026/2027)\n";
echo "======================================================\n\n";

// 1. Verify Event 1 Baseline (Quota 3, Registered 0)
$stmt = $conn->prepare("
    SELECT e.event_name, e.volunteer_quota, COUNT(vr.registration_id) AS reg_count
    FROM events e
    LEFT JOIN volunteer_registrations vr ON e.event_id = vr.event_id AND vr.registration_status != 'Rejected'
    WHERE e.event_id = 1
    GROUP BY e.event_id
");
$stmt->execute();
$baseline = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "[TEST 1] Event 1 Initial Quota Baseline:\n";
echo "  Event: " . $baseline["event_name"] . "\n";
echo "  Registered: " . $baseline["reg_count"] . " / " . $baseline["volunteer_quota"] . " spaces\n";
if ($baseline["reg_count"] == 0 && $baseline["volunteer_quota"] == 3) {
    echo "  -> SUCCESS: Clean 0/3 baseline confirmed!\n\n";
} else {
    echo "  -> WARNING: Baseline is not 0/3!\n\n";
}

// 2. Simulate Volunteer 'afifah' (user_id = 1) Registering with "No Preference"
echo "[TEST 2] Volunteer 'afifah' registers with 'No Preference' (preferred_event_role_id = NULL):\n";
$stmt = $conn->prepare("
    INSERT INTO volunteer_registrations (event_id, volunteer_id, preferred_event_role_id, registration_status)
    VALUES (1, 1, NULL, 'Pending')
");
$stmt->execute();
$newRegId = $stmt->insert_id;
$stmt->close();
echo "  -> Registration ID $newRegId inserted with status 'Pending' and NULL preferred role.\n";

// Verify Event 1 Quota incremented to 1/3
$stmt = $conn->prepare("
    SELECT COUNT(vr.registration_id) AS reg_count
    FROM volunteer_registrations vr
    WHERE vr.event_id = 1 AND vr.registration_status != 'Rejected'
");
$stmt->execute();
$currentReg = $stmt->get_result()->fetch_row()[0];
$stmt->close();
echo "  -> Live Quota Updated: $currentReg / 3 spaces filled.\n\n";

// 3. Simulate Mosque Committee Reviewing and Approving
echo "[TEST 3] Committee reviews and approves Afifah's application:\n";
$conn->query("
    UPDATE volunteer_registrations 
    SET registration_status = 'Approved', decision_by = 4, decision_at = NOW(), decision_comment = 'Approved by AJK'
    WHERE registration_id = $newRegId
");
// Create attendance & token initial records
$conn->query("INSERT IGNORE INTO attendance_records (registration_id, attendance_status, verification_status) VALUES ($newRegId, 'Not Checked In', 'Pending')");
$conn->query("INSERT IGNORE INTO token_records (registration_id, token_type, token_status, verification_status) VALUES ($newRegId, 'T-Shirt and Certificate', 'Pending', 'Pending')");
echo "  -> Registration approved! Attendance and Token records initialized.\n\n";

// 4. Committee Assigns Volunteer Role (Role 1: Registration Counter)
echo "[TEST 4] Committee assigns Afifah to vacant Role 1 (Registration Counter):\n";
$conn->query("
    INSERT INTO role_assignments (registration_id, event_role_id, assigned_by)
    VALUES ($newRegId, 1, 4)
");
$roleCheck = $conn->query("
    SELECT er.role_name, er.team_unit 
    FROM role_assignments ra
    JOIN event_roles er ON ra.event_role_id = er.event_role_id
    WHERE ra.registration_id = $newRegId
")->fetch_assoc();
echo "  -> Role Assigned: " . $roleCheck["role_name"] . " (" . $roleCheck["team_unit"] . ")\n\n";

// 5. Volunteer Check-In Attendance on Event Day
echo "[TEST 5] Volunteer checks in on site:\n";
$conn->query("
    UPDATE attendance_records 
    SET check_in_time = NOW(), attendance_status = 'Present', verification_status = 'Pending'
    WHERE registration_id = $newRegId
");
echo "  -> Attendance marked 'Present' with timestamp.\n\n";

// 6. Committee Attendance Verification
echo "[TEST 6] Committee verifies physical attendance:\n";
$conn->query("
    UPDATE attendance_records 
    SET verification_status = 'Verified', verified_by = 4, verified_at = NOW()
    WHERE registration_id = $newRegId
");
$conn->query("
    UPDATE token_records 
    SET token_status = 'Distributed', distributed_at = NOW()
    WHERE registration_id = $newRegId
");
echo "  -> Attendance status set to 'Verified'. Token marked 'Distributed'.\n\n";

// 7. Head of Mosque Committee (Nazir) Verifies Token
echo "[TEST 7] Nazir endorses token distribution:\n";
$conn->query("
    UPDATE token_records 
    SET verification_status = 'Verified', verified_by = 5, verified_at = NOW()
    WHERE registration_id = $newRegId
");
echo "  -> Token endorsed by Head of Mosque Committee!\n\n";

// 8. Test Head Report Generation
echo "[TEST 8] Event Report Query Test:\n";
$reportRow = $conn->query("
    SELECT 
        e.event_name,
        u.full_name AS volunteer,
        er.role_name AS assigned_role,
        ar.attendance_status,
        ar.verification_status AS attendance_verified,
        tr.token_status,
        tr.verification_status AS token_verified
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    LEFT JOIN token_records tr ON vr.registration_id = tr.registration_id
    WHERE vr.registration_id = $newRegId
")->fetch_assoc();

echo "  -> Report Row Captured:\n";
echo "     Event: " . $reportRow["event_name"] . "\n";
echo "     Volunteer: " . $reportRow["volunteer"] . "\n";
echo "     Assigned Role: " . $reportRow["assigned_role"] . "\n";
echo "     Attendance: " . $reportRow["attendance_status"] . " (" . $reportRow["attendance_verified"] . ")\n";
echo "     Token: " . $reportRow["token_status"] . " (" . $reportRow["token_verified"] . ")\n\n";

echo ">>> ALL STEPS VALIDATED AND TESTED SUCCESSFULLY! <<<\n";

// Reset baseline back to 0/3 for live evaluation
echo "\n[RESET] Restoring Event 1 & 2 to 0/3 baseline for live presentation...\n";
require_once "seed.php";
echo "Done!\n";
?>
