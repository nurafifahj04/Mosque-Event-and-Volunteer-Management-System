<?php
// volunteer-history.php - Volunteer History Page (FR-16, UI-09)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];

// Fetch past events and attendance records for this volunteer
$query = "
    SELECT 
        vr.registration_id,
        e.event_id,
        e.event_name,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        COALESCE(er.role_name, pref_er.role_name, 'General Volunteer') AS volunteer_role,
        COALESCE(er.team_unit, pref_er.team_unit, 'General') AS team_unit,
        ar.attendance_status,
        ar.check_in_time,
        ar.verification_status AS attendance_verification,
        tr.token_type,
        tr.token_status,
        tr.verification_status AS token_verification
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    LEFT JOIN token_records tr ON vr.registration_id = tr.registration_id
    WHERE vr.volunteer_id = ? AND vr.registration_status = 'Approved'
    ORDER BY e.event_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
$eventsJoined = 0;
$attendanceVerified = 0;
$rolesCompleted = 0;

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
    $eventsJoined++;
    if ($row["attendance_verification"] === "Verified") {
        $attendanceVerified++;
    }
    if ($row["attendance_status"] === "Present") {
        $rolesCompleted++;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer History | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-volunteer.php"; ?>

    <main class="main">

      <section class="page-heading">
        <div>
          <p class="page-kicker">Volunteer Record</p>
          <h1>Volunteer History</h1>
          <p class="page-description">
            Review your historical event service, verified attendances, and appreciation records.
          </p>
        </div>
        <a href="volunteer-events.php" class="btn btn-primary dashboard-action">
          <i class="fa-solid fa-calendar-plus"></i> Browse Events
        </a>
      </section>

      <section class="grid-3 history-summary" style="margin-bottom: 24px;">
        <div class="stat">
          <div class="stat-icon green-icon">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <div>
            <h2><?php echo $eventsJoined; ?></h2>
            <p>Events Joined</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div>
            <h2><?php echo $attendanceVerified; ?></h2>
            <p>Attendance Verified</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon">
            <i class="fa-solid fa-star"></i>
          </div>
          <div>
            <h2><?php echo $rolesCompleted; ?></h2>
            <p>Roles Completed</p>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header history-card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
          <div>
            <p class="page-kicker">Service Timeline</p>
            <h2 style="color: var(--primary-dark); font-size: 19px;">Past Volunteer Engagements</h2>
          </div>
          <span class="history-count">
            <?php echo count($history); ?> Events
          </span>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Event Date</th>
                <th>Volunteer Role</th>
                <th>Attendance</th>
                <th>Token Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($history)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No past volunteer records found yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($history as $h): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($h["event_name"]); ?></strong>
                      <span class="table-subtext">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($h["location"]); ?>
                      </span>
                    </td>

                    <td>
                      <?php echo date("d F Y", strtotime($h["event_date"])); ?>
                      <span class="table-subtext">
                        <i class="fa-solid fa-clock"></i> <?php echo date("g:i A", strtotime($h["start_time"])); ?><?php echo $h["end_time"] ? " – " . date("g:i A", strtotime($h["end_time"])) : ""; ?>
                      </span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($h["volunteer_role"]); ?></strong>
                      <span class="table-subtext">
                        <?php echo htmlspecialchars($h["team_unit"]); ?>
                      </span>
                    </td>

                    <td>
                      <?php if ($h["attendance_verification"] === "Verified"): ?>
                        <span class="badge badge-approved">
                          <i class="fa-solid fa-circle-check"></i> Present · Verified
                        </span>
                        <span class="table-subtext" style="color: var(--success); font-weight: 600;">
                          Checked in at <?php echo $h["check_in_time"] ? date("g:i A", strtotime($h["check_in_time"])) : "Recorded"; ?>
                        </span>
                      <?php elseif ($h["attendance_status"] === "Present"): ?>
                        <span class="badge badge-pending">
                          <i class="fa-solid fa-hourglass-half"></i> Present · Pending
                        </span>
                        <span class="table-subtext">
                          Checked in at <?php echo date("g:i A", strtotime($h["check_in_time"])); ?>
                        </span>
                      <?php else: ?>
                        <span class="badge badge-pending">
                          Not Checked In
                        </span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($h["token_status"] === "Distributed"): ?>
                        <span class="badge badge-approved">
                          <i class="fa-solid fa-gift"></i> Distributed
                        </span>
                        <span class="table-subtext" style="color: var(--success); font-weight: 600;">
                          <?php echo htmlspecialchars($h["token_type"] ?? "T-Shirt & Certificate"); ?>
                        </span>
                      <?php else: ?>
                        <span class="badge badge-pending">
                          Pending Distribution
                        </span>
                        <span class="table-subtext">
                          <?php echo htmlspecialchars($h["token_type"] ?? "T-Shirt & Certificate"); ?>
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card history-note-card" style="margin-top: 22px;">
        <div class="history-note-icon">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <h3>Your Volunteer Record</h3>
          <p>
            This page shows your past event registrations, assigned volunteer roles, attendance records and token distribution status. The information is used to generate volunteer event reports for the mosque committee.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
