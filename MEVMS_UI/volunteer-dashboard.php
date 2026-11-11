<?php
// volunteer-dashboard.php - Volunteer Dashboard (FR-03, UI-03)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// 1. Fetch counts for dashboard stats
$stmt = $conn->prepare("SELECT COUNT(*) FROM volunteer_registrations WHERE volunteer_id = ? AND registration_status = 'Approved'");
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$approvedCount = $stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM volunteer_registrations WHERE volunteer_id = ? AND registration_status = 'Pending'");
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM attendance_records ar
    JOIN volunteer_registrations vr ON ar.registration_id = vr.registration_id
    WHERE vr.volunteer_id = ? AND ar.verification_status = 'Verified'
");
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$verifiedAttendanceCount = $stmt->get_result()->fetch_row()[0];
$stmt->close();

// 2. Fetch the next upcoming approved event
$stmt = $conn->prepare("
    SELECT 
        vr.registration_id,
        vr.registration_status,
        e.event_id,
        e.event_name,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        COALESCE(er.role_name, pref_er.role_name, 'General Volunteer') AS assigned_role,
        COALESCE(er.team_unit, pref_er.team_unit, 'General') AS team_unit,
        COALESCE(ar.attendance_status, 'Not Checked In') AS attendance_status,
        ar.check_in_time
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    WHERE vr.volunteer_id = ? AND vr.registration_status = 'Approved' AND e.event_status != 'Completed'
    ORDER BY e.event_date ASC
    LIMIT 1
");
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$nextActivity = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer Dashboard | MEVMS</title>
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
          <p class="page-kicker">Volunteer Portal</p>
          <h1>Assalamualaikum, <?php echo htmlspecialchars($fullName); ?></h1>
          <p class="page-description">
            View your upcoming events, registration status, attendance, and volunteer records.
          </p>
        </div>
        <a href="volunteer-events.php" class="btn btn-primary dashboard-action">
          <i class="fa-solid fa-calendar-plus"></i> Browse Events
        </a>
      </section>

      <section class="grid-3 dashboard-stats">
        <div class="stat">
          <div class="stat-icon green-icon">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <div>
            <h2><?php echo $approvedCount; ?></h2>
            <p>Approved Events</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>
          <div>
            <h2><?php echo $pendingCount; ?></h2>
            <p>Pending Review</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div>
            <h2><?php echo $verifiedAttendanceCount; ?></h2>
            <p>Verified Attendance</p>
          </div>
        </div>
      </section>

      <!-- Next Activity Section -->
      <?php if ($nextActivity): ?>
        <section class="card" style="margin-bottom: 22px; border-top: 4px solid var(--primary);">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div>
              <p class="page-kicker">Your Next Activity</p>
              <h2 style="color: var(--primary-dark); font-size: 20px; margin-top: 2px;">
                <?php echo htmlspecialchars($nextActivity["event_name"]); ?>
              </h2>
            </div>
            <span class="badge badge-approved">Approved</span>
          </div>

          <div style="display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 18px; font-size: 13px; color: #475569;">
            <div>
              <i class="fa-solid fa-calendar" style="color: var(--primary);"></i>
              <strong>Date:</strong> <?php echo date("d F Y", strtotime($nextActivity["event_date"])); ?>
            </div>
            <div>
              <i class="fa-solid fa-clock" style="color: var(--primary);"></i>
              <strong>Time:</strong> <?php echo date("g:i A", strtotime($nextActivity["start_time"])); ?><?php echo $nextActivity["end_time"] ? " – " . date("g:i A", strtotime($nextActivity["end_time"])) : ""; ?>
            </div>
            <div>
              <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i>
              <strong>Location:</strong> <?php echo htmlspecialchars($nextActivity["location"]); ?>
            </div>
            <div>
              <i class="fa-solid fa-user-tag" style="color: var(--primary);"></i>
              <strong>Assigned Role:</strong> <?php echo htmlspecialchars($nextActivity["assigned_role"]); ?> (<?php echo htmlspecialchars($nextActivity["team_unit"]); ?>)
            </div>
          </div>

          <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <a href="volunteer-status.php" class="btn btn-outline">
              <i class="fa-solid fa-list-check"></i> View Registration
            </a>

            <?php if ($nextActivity["attendance_status"] === "Present"): ?>
              <span class="badge badge-approved" style="padding: 10px 16px; font-size: 13px;">
                <i class="fa-solid fa-check-double"></i> Checked In (<?php echo date("g:i A", strtotime($nextActivity["check_in_time"])); ?>)
              </span>
            <?php else: ?>
              <a href="volunteer-checkin.php?registration_id=<?php echo $nextActivity['registration_id']; ?>" class="btn btn-primary">
                <i class="fa-solid fa-clipboard-check"></i> Check In Now
              </a>
            <?php endif; ?>
          </div>
        </section>
      <?php else: ?>
        <section class="card" style="margin-bottom: 22px; text-align: center; padding: 36px 20px;">
          <div style="width: 54px; height: 54px; border-radius: 16px; background: var(--primary-light); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
            <i class="fa-solid fa-calendar-days"></i>
          </div>
          <h3 style="color: var(--primary-dark); margin-bottom: 6px;">No Active Event Registrations</h3>
          <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 18px; max-width: 480px; margin-left: auto; margin-right: auto;">
            Browse approved upcoming mosque events and register as a volunteer today!
          </p>
          <a href="volunteer-events.php" class="btn btn-primary">
            <i class="fa-solid fa-calendar-plus"></i> Browse Upcoming Events
          </a>
        </section>
      <?php endif; ?>

      <section class="card history-note-card">
        <div class="history-note-icon">
          <i class="fa-solid fa-bell"></i>
        </div>
        <div>
          <h3>Volunteer Reminder</h3>
          <p>
            Please check your assigned volunteer role before the event date and arrive at least 15 minutes before the event begins. Use the Check-In page to record your arrival at the mosque.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
