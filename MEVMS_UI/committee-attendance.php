<?php
// committee-attendance.php - Verify Volunteer Attendance (FR-13, UI-14)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

$selectedEventId = intval($_GET["event_id"] ?? 0);
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");

if ($selectedEventId <= 0) {
    $firstEv = $conn->query("SELECT event_id FROM events ORDER BY event_date DESC LIMIT 1")->fetch_assoc();
    $selectedEventId = $firstEv ? intval($firstEv["event_id"]) : 0;
}

// Fetch attendance records for selected event
$query = "
    SELECT 
        ar.attendance_id,
        ar.check_in_time,
        ar.attendance_status,
        ar.verification_status,
        ar.verified_at,
        u.full_name AS volunteer_name,
        u.phone_number,
        COALESCE(er.role_name, pref_er.role_name, 'General Volunteer') AS assigned_role,
        COALESCE(er.team_unit, pref_er.team_unit, '-') AS team_unit,
        verifier.full_name AS verifier_name
    FROM attendance_records ar
    JOIN volunteer_registrations vr ON ar.registration_id = vr.registration_id
    JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN users verifier ON ar.verified_by = verifier.user_id
    WHERE vr.event_id = ? AND vr.registration_status = 'Approved'
    ORDER BY (ar.verification_status = 'Pending') DESC, ar.check_in_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selectedEventId);
$stmt->execute();
$attendanceRecords = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Attendance | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-committee.php"; ?>

    <main class="main">

      <?php if ($successMsg): ?>
        <div class="alert alert-success">
          <i class="fa-solid fa-circle-check"></i>
          <span><?php echo htmlspecialchars($successMsg); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="alert alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>
      <?php endif; ?>

      <section class="page-heading">
        <div>
          <p class="page-kicker">On-Site Operations</p>
          <h1>Verify Volunteer Attendance</h1>
          <p class="page-description">
            Inspect check-in timestamps and certify physical attendance for volunteer appreciation token eligibility.
          </p>
        </div>
      </section>

      <!-- Event Filter -->
      <section class="card" style="margin-bottom: 22px;">
        <form method="get" action="committee-attendance.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
          <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <label for="event-select"><i class="fa-solid fa-calendar"></i> Select Mosque Event</label>
            <select id="event-select" name="event_id" onchange="this.form.submit()">
              <?php while ($ev = $events->fetch_assoc()): ?>
                <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selectedEventId === intval($ev['event_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ev['event_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-arrows-rotate"></i> Load Attendance
          </button>
        </form>
      </section>

      <!-- Attendance Table -->
      <section class="card">
        <h2 class="form-section-title">Volunteer Check-In Records</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 18px;">
          Volunteers who checked in on the portal are shown below. Click Verify to confirm their physical attendance.
        </p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Volunteer Name</th>
                <th>Assigned Role</th>
                <th>Check-In Time</th>
                <th>Status</th>
                <th>Verification</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($attendanceRecords->num_rows === 0): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No attendance records found for this event yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php while ($a = $attendanceRecords->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($a["volunteer_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($a["phone_number"]); ?></span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($a["assigned_role"]); ?></strong>
                      <span class="table-subtext"><?php echo htmlspecialchars($a["team_unit"]); ?></span>
                    </td>

                    <td>
                      <?php if ($a["check_in_time"]): ?>
                        <strong style="color: var(--primary);"><i class="fa-solid fa-clock"></i> <?php echo date("g:i A", strtotime($a["check_in_time"])); ?></strong>
                        <span class="table-subtext"><?php echo date("d M Y", strtotime($a["check_in_time"])); ?></span>
                      <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;">Not checked in</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($a["attendance_status"] === "Present"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-user-check"></i> Present</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Not Checked In</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($a["verification_status"] === "Verified"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Verified</span>
                        <span class="table-subtext">by <?php echo htmlspecialchars($a["verifier_name"] ?? "Committee"); ?></span>
                      <?php else: ?>
                        <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($a["verification_status"] !== "Verified" && $a["attendance_status"] === "Present"): ?>
                        <form action="process/verify-attendance.php" method="post">
                          <input type="hidden" name="attendance_id" value="<?php echo $a['attendance_id']; ?>">
                          <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>">
                          <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px;">
                            <i class="fa-solid fa-clipboard-check"></i> Verify Attendance
                          </button>
                        </form>
                      <?php elseif ($a["verification_status"] === "Verified"): ?>
                        <span style="color: var(--success); font-size: 12px; font-weight: 600;">
                          <i class="fa-solid fa-circle-check"></i> Verified
                        </span>
                      <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;">Awaiting arrival</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
