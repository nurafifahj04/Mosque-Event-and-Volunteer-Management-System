<?php
// head-report.php - Generate Volunteer Event Report (FR-17, UI-17, Lecturer Requirement)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Head of the Mosque Committee");

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

// 1. Available filters
$selectedEventId = intval($_GET["event_id"] ?? 0);
$selectedMonth = trim($_GET["month"] ?? ""); // e.g. "2026-08"

// Fetch list of months with events
$monthsResult = $conn->query("
    SELECT DISTINCT DATE_FORMAT(event_date, '%Y-%m') AS month_val, DATE_FORMAT(event_date, '%M %Y') AS month_label
    FROM events
    ORDER BY event_date DESC
");

// Fetch events list
$eventsResult = $conn->query("SELECT event_id, event_name, event_date FROM events ORDER BY event_date DESC");

// 2. Build Query based on filters
$whereClauses = [];
$params = [];
$types = "";

if ($selectedEventId > 0) {
    $whereClauses[] = "e.event_id = ?";
    $params[] = $selectedEventId;
    $types .= "i";
}

if ($selectedMonth !== "") {
    $whereClauses[] = "DATE_FORMAT(e.event_date, '%Y-%m') = ?";
    $params[] = $selectedMonth;
    $types .= "s";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Fetch Events for the report
$eventsQuery = "
    SELECT 
        e.event_id,
        e.event_name,
        e.event_description,
        e.event_date,
        e.location,
        e.volunteer_quota,
        e.planned_budget,
        e.funding_source,
        e.event_status
    FROM events e
    $whereSql
    ORDER BY e.event_date DESC
";

$stmt = $conn->prepare($eventsQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$eventsData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Volunteers list for the report
$volunteersQuery = "
    SELECT 
        e.event_name,
        e.event_date,
        u.full_name AS volunteer_name,
        u.phone_number,
        u.residential_area,
        COALESCE(er.role_name, pref_er.role_name, 'General Volunteer') AS assigned_role,
        COALESCE(er.team_unit, pref_er.team_unit, 'General') AS team_unit,
        COALESCE(ar.attendance_status, 'Not Checked In') AS attendance_status,
        ar.check_in_time,
        ar.verification_status AS attendance_verified,
        COALESCE(tr.token_status, 'Pending') AS token_status,
        COALESCE(tr.token_type, 'T-Shirt & Certificate') AS token_type
    FROM events e
    JOIN volunteer_registrations vr ON e.event_id = vr.event_id AND vr.registration_status = 'Approved'
    JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    LEFT JOIN token_records tr ON vr.registration_id = tr.registration_id
    $whereSql
    ORDER BY e.event_date DESC, u.full_name ASC
";

$stmt = $conn->prepare($volunteersQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$volunteersData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistics aggregation
$totalEventsCount = count($eventsData);
$totalVolunteersCount = count($volunteersData);
$totalPresentCount = 0;
$totalTokensDistributed = 0;
$totalBudget = 0.0;

foreach ($eventsData as $e) {
    $totalBudget += floatval($e["planned_budget"] ?? 0);
}

foreach ($volunteersData as $v) {
    if ($v["attendance_status"] === "Present") {
        $totalPresentCount++;
    }
    if ($v["token_status"] === "Distributed") {
        $totalTokensDistributed++;
    }
}

$attendanceRate = $totalVolunteersCount > 0 ? round(($totalPresentCount / $totalVolunteersCount) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer Event Report | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    @media print {
      .topbar, .sidebar, .report-filter-card, .dashboard-action, .report-actions, .no-print {
        display: none !important;
      }
      .layout {
        display: block !important;
      }
      .main {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
      }
      .card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        page-break-inside: avoid;
      }
      .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #000;
      }
    }
    .print-header {
      display: none;
    }
  </style>
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-head.php"; ?>

    <main class="main">

      <section class="page-heading no-print">
        <div>
          <p class="page-kicker">Mosque Executive Audit & Reporting</p>
          <h1>Volunteer Event Report</h1>
          <p class="page-description">
            Filter mosque programmes by month or event to evaluate volunteer participation, role deployment, attendance rates, and export official reports.
          </p>
        </div>
      </section>

      <!-- Printable Official Letterhead (Appears only on PDF Print) -->
      <div class="print-header">
        <h2 style="margin: 0; font-size: 20px; font-weight: 800;">MASJID KAMPUNG RINCHING HULU, SEMENYIH</h2>
        <p style="margin: 4px 0; font-size: 14px; font-weight: 700;">OFFICIAL VOLUNTEER & EVENT MANAGEMENT REPORT (MEVMS)</p>
        <small style="color: #444;">Generated on: <?php echo date("d F Y, g:i A"); ?> | Head of Mosque Committee (Nazir)</small>
      </div>

      <!-- Filter Card -->
      <section class="card report-filter-card no-print" style="margin-bottom: 22px;">
        <h2 class="form-section-title" style="margin-top: 0;">
          <i class="fa-solid fa-filter"></i> Filter Report Criteria
        </h2>
        <form method="get" action="head-report.php">
          <div class="form-grid">
            <div class="form-group">
              <label for="month-select"><i class="fa-solid fa-calendar"></i> Filter by Month</label>
              <select id="month-select" name="month">
                <option value="">-- All Months --</option>
                <?php while ($m = $monthsResult->fetch_assoc()): ?>
                  <option value="<?php echo $m['month_val']; ?>" <?php echo ($selectedMonth === $m['month_val']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($m['month_label']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="event-select"><i class="fa-solid fa-mosque"></i> Filter by Specific Event</label>
              <select id="event-select" name="event_id">
                <option value="0">-- All Events --</option>
                <?php while ($ev = $eventsResult->fetch_assoc()): ?>
                  <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selectedEventId === intval($ev['event_id'])) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ev['event_name']); ?> (<?php echo date("M Y", strtotime($ev['event_date'])); ?>)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 6px; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-magnifying-glass"></i> Apply Filter
            </button>
            <a href="head-report.php" class="btn btn-outline">
              <i class="fa-solid fa-rotate-left"></i> Reset Filter
            </a>
          </div>
        </form>
      </section>

      <!-- Summary Statistics Bar -->
      <section class="grid-3" style="margin-bottom: 22px;">
        <div class="stat">
          <div class="stat-icon green-icon"><i class="fa-solid fa-calendar-check"></i></div>
          <div>
            <h2><?php echo $totalEventsCount; ?></h2>
            <p>Events Evaluated</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon"><i class="fa-solid fa-users"></i></div>
          <div>
            <h2><?php echo $totalVolunteersCount; ?></h2>
            <p>Volunteers Registered</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon"><i class="fa-solid fa-clipboard-check"></i></div>
          <div>
            <h2><?php echo $attendanceRate; ?>%</h2>
            <p>Attendance Rate (<?php echo $totalPresentCount; ?> / <?php echo $totalVolunteersCount; ?>)</p>
          </div>
        </div>
      </section>

      <!-- Export Action Toolbar -->
      <section class="card no-print report-actions" style="margin-bottom: 22px; background: var(--bg); border-left: 5px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
          <div>
            <h3 style="color: var(--primary-dark); font-size: 16px; margin: 0 0 4px;">
              <i class="fa-solid fa-file-export" style="color: var(--primary);"></i> Export Official Report
            </h3>
            <span style="font-size: 13px; color: var(--text-muted);">
              Total Budget: <strong>RM <?php echo number_format($totalBudget, 2); ?></strong> | Tokens Distributed: <strong><?php echo $totalTokensDistributed; ?> items</strong>
            </span>
          </div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <!-- Print to PDF button -->
            <button type="button" onclick="window.print()" class="btn btn-primary">
              <i class="fa-solid fa-file-pdf"></i> Export / Print PDF
            </button>
            <!-- CSV Download -->
            <a href="process/export-report-csv.php?event_id=<?php echo $selectedEventId; ?>&month=<?php echo urlencode($selectedMonth); ?>" class="btn btn-outline">
              <i class="fa-solid fa-file-csv"></i> Download CSV / Excel
            </a>
          </div>
        </div>
      </section>

      <!-- Event Summaries -->
      <section class="card" style="margin-bottom: 22px;">
        <h2 class="form-section-title">Events Summary Overview</h2>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event Name</th>
                <th>Date</th>
                <th>Location</th>
                <th>Volunteer Quota</th>
                <th>Planned Budget</th>
                <th>Funding Source</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($eventsData)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">
                    No events match the selected filter.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($eventsData as $e): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($e["event_name"]); ?></strong></td>
                    <td><?php echo date("d F Y", strtotime($e["event_date"])); ?></td>
                    <td><?php echo htmlspecialchars($e["location"]); ?></td>
                    <td><?php echo htmlspecialchars($e["volunteer_quota"]); ?> pax</td>
                    <td><strong>RM <?php echo number_format($e["planned_budget"], 2); ?></strong></td>
                    <td><span class="table-subtext"><?php echo htmlspecialchars($e["funding_source"] ?? "Masjid General Fund"); ?></span></td>
                    <td>
                      <span class="badge <?php echo ($e['event_status'] === 'Completed') ? 'badge-approved' : 'badge-pending'; ?>">
                        <?php echo htmlspecialchars($e["event_status"]); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Volunteer Participant Roster -->
      <section class="card">
        <h2 class="form-section-title">Volunteer Participation & Token Endorsement Roster</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">
          Certified record of registered volunteers, assigned units, check-in timestamps, and appreciation items.
        </p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Volunteer Name</th>
                <th>Event</th>
                <th>Assigned Role & Unit</th>
                <th>Attendance</th>
                <th>Check-In Time</th>
                <th>Token Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($volunteersData)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No volunteers recorded for the selected event/month criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($volunteersData as $vd): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($vd["volunteer_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($vd["phone_number"]); ?> (<?php echo htmlspecialchars($vd["residential_area"]); ?>)</span>
                    </td>

                    <td>
                      <?php echo htmlspecialchars($vd["event_name"]); ?>
                      <span class="table-subtext"><i class="fa-solid fa-calendar"></i> <?php echo date("d M Y", strtotime($vd["event_date"])); ?></span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($vd["assigned_role"]); ?></strong>
                      <span class="table-subtext">Unit: <?php echo htmlspecialchars($vd["team_unit"]); ?></span>
                    </td>

                    <td>
                      <?php if ($vd["attendance_status"] === "Present"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Present</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Not Checked In</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php echo $vd["check_in_time"] ? date("g:i A", strtotime($vd["check_in_time"])) : "–"; ?>
                    </td>

                    <td>
                      <?php if ($vd["token_status"] === "Distributed"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-gift"></i> Distributed</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Pending</span>
                      <?php endif; ?>
                      <span class="table-subtext"><?php echo htmlspecialchars($vd["token_type"]); ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top: 24px; padding-top: 14px; border-top: 1px dashed var(--border); font-size: 12.5px; color: var(--text-muted); text-align: right;">
          Report officially verified and certified by: <strong><?php echo htmlspecialchars($fullName); ?> (Nazir / Head of Mosque Committee)</strong>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
