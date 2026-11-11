<?php
// committee-dashboard.php - Mosque Committee Dashboard (FR-03, UI-10)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// 1. Live stats
$eventsCount = $conn->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$pendingCount = $conn->query("SELECT COUNT(*) FROM volunteer_registrations WHERE registration_status = 'Pending'")->fetch_row()[0];
$checkinsToday = $conn->query("SELECT COUNT(*) FROM attendance_records WHERE attendance_status = 'Present'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Committee Dashboard | MEVMS</title>
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
          <p class="page-kicker">Mosque Committee Management</p>
          <h1>Committee Dashboard</h1>
          <p class="page-description">
            Coordinate approved events, review volunteer registrations, assign team units, and verify on-site attendance.
          </p>
        </div>
        <a href="committee-record-event.php" class="btn btn-primary dashboard-action">
          <i class="fa-solid fa-calendar-plus"></i> Record Approved Event
        </a>
      </section>

      <section class="grid-3 committee-stats" style="margin-bottom: 24px;">
        <div class="stat">
          <div class="stat-icon green-icon">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <div>
            <h2><?php echo $eventsCount; ?></h2>
            <p>Approved Events</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>
          <div>
            <h2><?php echo $pendingCount; ?></h2>
            <p>Pending Applications</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon">
            <i class="fa-solid fa-clipboard-check"></i>
          </div>
          <div>
            <h2><?php echo $checkinsToday; ?></h2>
            <p>Volunteer Check-Ins</p>
          </div>
        </div>
      </section>

      <section class="grid-2 committee-quick-actions">

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-calendar-plus"></i>
          </div>
          <div>
            <h2>Record Approved Event</h2>
            <p>
              Enter details and team role quotas for mosque programmes approved through existing committee procedures.
            </p>
            <a href="committee-record-event.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Record Event
            </a>
          </div>
        </section>

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <div>
            <h2>Review Volunteer Registrations</h2>
            <p>
              Review pending volunteer applications, evaluate requirements, and approve or reject registrations.
            </p>
            <a href="committee-registrations.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Review Applications
            </a>
          </div>
        </section>

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-sitemap"></i>
          </div>
          <div>
            <h2>Assign Volunteer Roles</h2>
            <p>
              Assign team units and confirm duties for volunteers who selected "No Preference" or require rebalancing.
            </p>
            <a href="committee-assign-roles.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Assign Roles
            </a>
          </div>
        </section>

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-clipboard-user"></i>
          </div>
          <div>
            <h2>Attendance Verification</h2>
            <p>
              Inspect volunteer arrival timestamps and officially certify attendance records for token distribution.
            </p>
            <a href="committee-attendance.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Verify Attendance
            </a>
          </div>
        </section>

      </section>

      <section class="card committee-information-card" style="margin-top: 24px;">
        <div class="committee-information-icon">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <h3>Committee Operational Flow</h3>
          <p>
            Mosque Committee Members record approved events, review registrations, and verify volunteer check-ins. The Nazir (Head of Mosque Committee) certifies token distribution and exports the compiled reports.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
