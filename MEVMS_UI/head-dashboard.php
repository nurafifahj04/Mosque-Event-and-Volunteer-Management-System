<?php
// head-dashboard.php - Head of Mosque Committee Dashboard (FR-03, UI-15)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Head of the Mosque Committee");

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

// Fetch live statistics
$eventsCount = $conn->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$tokensPending = $conn->query("SELECT COUNT(*) FROM token_records WHERE verification_status = 'Pending'")->fetch_row()[0];
$reportsCount = $conn->query("SELECT COUNT(*) FROM event_reports")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Head Dashboard | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-head.php"; ?>

    <main class="main">

      <section class="page-heading">
        <div>
          <p class="page-kicker">Mosque Executive Governance</p>
          <h1>Head of Committee Dashboard</h1>
          <p class="page-description">
            Oversee event execution, certify token distributions, and audit volunteer reports for Masjid Kampung Rinching Hulu.
          </p>
        </div>
        <a href="head-report.php" class="btn btn-primary dashboard-action">
          <i class="fa-solid fa-file-export"></i> Generate Official Report
        </a>
      </section>

      <section class="grid-3 dashboard-stats" style="margin-bottom: 24px;">
        <div class="stat">
          <div class="stat-icon green-icon">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <div>
            <h2><?php echo $eventsCount; ?></h2>
            <p>Active Programmes</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon">
            <i class="fa-solid fa-gift"></i>
          </div>
          <div>
            <h2><?php echo $tokensPending; ?></h2>
            <p>Tokens Awaiting Verification</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div>
            <h2><?php echo $reportsCount; ?></h2>
            <p>Archived Reports</p>
          </div>
        </div>
      </section>

      <section class="grid-2 committee-quick-actions">

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-award"></i>
          </div>
          <div>
            <h2>Verify Token Status</h2>
            <p>
              Review volunteer appreciation distributions (T-shirts, certificates) and certify token claims.
            </p>
            <a href="head-token-verify.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Verify Token Records
            </a>
          </div>
        </section>

        <section class="card quick-action-card">
          <div class="quick-action-icon">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div>
            <h2>Generate Event Report</h2>
            <p>
              Aggregate post-event attendance, role deployments, and budget expenditures with PDF preview and CSV export.
            </p>
            <a href="head-report.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-right"></i> Access Report Generator
            </a>
          </div>
        </section>

      </section>

      <section class="card committee-information-card" style="margin-top: 24px;">
        <div class="committee-information-icon">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <h3>Executive Authority & Governance</h3>
          <p>
            The Head of the Mosque Committee (Nazir) holds final authority over appreciation token endorsement and generation of the mosque's annual community service audit reports.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
