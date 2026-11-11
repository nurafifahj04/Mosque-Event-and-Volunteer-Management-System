<?php
// volunteer-status.php - My Registrations (FR-10, FR-11, UI-06)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// Fetch volunteer's active registrations
$query = "
    SELECT 
        vr.registration_id,
        vr.registration_date,
        vr.registration_status,
        vr.decision_comment,
        vr.preferred_event_role_id,
        e.event_id,
        e.event_name,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        assigned_role.role_name AS assigned_role_name,
        assigned_role.team_unit AS assigned_team_unit,
        pref_role.role_name AS preferred_role_name,
        pref_role.team_unit AS preferred_team_unit,
        ar.attendance_status,
        ar.check_in_time
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles assigned_role ON ra.event_role_id = assigned_role.event_role_id
    LEFT JOIN event_roles pref_role ON vr.preferred_event_role_id = pref_role.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    WHERE vr.volunteer_id = ?
    ORDER BY e.event_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$result = $stmt->get_result();

$registrations = [];
$approvedCount = 0;
$pendingCount = 0;
$assignedRoleCount = 0;

while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
    if ($row["registration_status"] === "Approved") {
        $approvedCount++;
        if (!empty($row["assigned_role_name"])) {
            $assignedRoleCount++;
        }
    } elseif ($row["registration_status"] === "Pending") {
        $pendingCount++;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Registrations | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-volunteer.php"; ?>

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
          <p class="page-kicker">Volunteer Registrations</p>
          <h1>My Registrations</h1>
          <p class="page-description">
            Track approval status, assigned roles, and access attendance check-in for your events.
          </p>
        </div>
        <a href="volunteer-events.php" class="btn btn-primary dashboard-action">
          <i class="fa-solid fa-calendar-plus"></i> Browse Events
        </a>
      </section>

      <section class="grid-3 registration-summary" style="margin-bottom: 24px;">
        <div class="stat">
          <div class="stat-icon green-icon"><i class="fa-solid fa-circle-check"></i></div>
          <div>
            <h2><?php echo $approvedCount; ?></h2>
            <p>Approved Events</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon"><i class="fa-solid fa-hourglass-half"></i></div>
          <div>
            <h2><?php echo $pendingCount; ?></h2>
            <p>Pending Review</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon"><i class="fa-solid fa-user-tag"></i></div>
          <div>
            <h2><?php echo $assignedRoleCount; ?></h2>
            <p>Confirmed Roles</p>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
          <div>
            <p class="page-kicker">Status Overview</p>
            <h2 style="color: var(--primary-dark); font-size: 19px;">Your Registered Events</h2>
          </div>
          <span class="history-count"><?php echo count($registrations); ?> Registrations</span>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Event Date</th>
                <th>Registration Status</th>
                <th>Assigned Role</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($registrations)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    You have not registered for any events yet. <a href="volunteer-events.php" style="color: var(--primary); font-weight: 600;">Browse events</a> to get started.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($reg["event_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($reg["location"]); ?></span>
                    </td>

                    <td>
                      <?php echo date("d M Y", strtotime($reg["event_date"])); ?>
                      <span class="table-subtext">
                        <i class="fa-solid fa-clock"></i> <?php echo date("g:i A", strtotime($reg["start_time"])); ?><?php echo $reg["end_time"] ? " – " . date("g:i A", strtotime($reg["end_time"])) : ""; ?>
                      </span>
                    </td>

                    <td>
                      <?php if ($reg["registration_status"] === "Approved"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Approved</span>
                      <?php elseif ($reg["registration_status"] === "Pending"): ?>
                        <span class="badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> Pending Review</span>
                      <?php else: ?>
                        <span class="badge badge-rejected"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                      <?php endif; ?>

                      <?php if (!empty($reg["decision_comment"])): ?>
                        <span class="table-subtext"><?php echo htmlspecialchars($reg["decision_comment"]); ?></span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if (!empty($reg["assigned_role_name"])): ?>
                        <strong><?php echo htmlspecialchars($reg["assigned_role_name"]); ?></strong>
                        <span class="table-subtext"><?php echo htmlspecialchars($reg["assigned_team_unit"]); ?></span>
                      <?php elseif (!empty($reg["preferred_role_name"])): ?>
                        <span><?php echo htmlspecialchars($reg["preferred_role_name"]); ?></span>
                        <span class="table-subtext" style="color: var(--accent);">(Preferred — awaiting committee confirmation)</span>
                      <?php else: ?>
                        <span class="badge badge-accent">Open / Committee Assigning</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($reg["registration_status"] === "Approved"): ?>
                        <?php if ($reg["attendance_status"] === "Present"): ?>
                          <span class="badge badge-approved">
                            <i class="fa-solid fa-check-double"></i> Checked In
                          </span>
                        <?php else: ?>
                          <a href="volunteer-checkin.php?registration_id=<?php echo $reg['registration_id']; ?>" class="btn btn-primary" style="padding: 7px 14px; font-size: 12px;">
                            <i class="fa-solid fa-clipboard-check"></i> Check In
                          </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;"><i class="fa-solid fa-clock"></i> In Review</span>
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
          <h3>What Happens Next?</h3>
          <p>
            The Mosque Committee reviews each registration. When approved, your confirmed role will appear on this page, and you will be eligible for attendance check-in on the event day.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
