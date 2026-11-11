<?php
// volunteer-checkin.php - Event Check-In (FR-12, UI-07)
require_once "includes/db.php";
require_once "includes/auth.php";
require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName    = $_SESSION["full_name"];
$regId       = intval($_GET["registration_id"] ?? 0);

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg   = $_SESSION["error_message"]   ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

$baseQuery = "
    SELECT
        vr.registration_id,
        e.event_name, e.event_date, e.start_time, e.end_time, e.location,
        COALESCE(er.role_name, pref_er.role_name, 'General Volunteer') AS assigned_role,
        COALESCE(er.team_unit, pref_er.team_unit, 'General') AS team_unit,
        ar.attendance_status, ar.check_in_time, ar.verification_status
    FROM volunteer_registrations vr
    JOIN events e ON vr.event_id = e.event_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles er ON ra.event_role_id = er.event_role_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN attendance_records ar ON vr.registration_id = ar.registration_id
    WHERE vr.volunteer_id = ? AND vr.registration_status = 'Approved' AND e.event_status != 'Completed'
";

if ($regId > 0) {
    $stmt = $conn->prepare($baseQuery . " AND vr.registration_id = ?");
    $stmt->bind_param("ii", $volunteerId, $regId);
    $stmt->execute();
    $reg     = $stmt->get_result()->fetch_assoc();
    $allRegs = [];
    $stmt->close();
} else {
    $stmt = $conn->prepare($baseQuery . " AND (ar.attendance_status IS NULL OR ar.attendance_status != 'Present') ORDER BY e.event_date ASC");
    $stmt->bind_param("i", $volunteerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $allRegs = [];
    while ($row = $result->fetch_assoc()) $allRegs[] = $row;
    $stmt->close();
    $reg = count($allRegs) === 1 ? $allRegs[0] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Check-In | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php require_once "includes/topbar.php"; ?>
<div class="layout">
<?php require_once "includes/sidebar-volunteer.php"; ?>
<main class="main">

  <?php if ($successMsg): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><span><?php echo htmlspecialchars($successMsg); ?></span></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?php echo htmlspecialchars($errorMsg); ?></span></div>
  <?php endif; ?>

  <section class="page-heading">
    <div>
      <p class="page-kicker">Volunteer Attendance</p>
      <h1>Event Check-In</h1>
      <p class="page-description">Confirm your on-site arrival for approved mosque events.</p>
    </div>
    <a href="volunteer-status.php" class="btn btn-outline dashboard-action">
      <i class="fa-solid fa-list-check"></i> My Registrations
    </a>
  </section>

  <?php if ($reg): ?>
    <section class="checkin-layout">
      <section class="card">
        <div class="checkin-event-header">
          <div class="checkin-event-date-badge">
            <strong><?php echo date("d", strtotime($reg["event_date"])); ?></strong>
            <span><?php echo strtoupper(date("M", strtotime($reg["event_date"]))); ?></span>
            <small><?php echo date("Y", strtotime($reg["event_date"])); ?></small>
          </div>
          <div class="checkin-event-info">
            <p class="page-kicker">Approved Event</p>
            <h2><?php echo htmlspecialchars($reg["event_name"]); ?></h2>
            <div class="checkin-meta-row">
              <span><i class="fa-solid fa-clock"></i><?php echo date("g:i A", strtotime($reg["start_time"])); ?><?php echo $reg["end_time"] ? " &ndash; ".date("g:i A",strtotime($reg["end_time"])) : ""; ?></span>
              <span><i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($reg["location"]); ?></span>
            </div>
          </div>
        </div>

        <div class="checkin-role-box">
          <div class="checkin-role-icon"><i class="fa-solid fa-user-tag"></i></div>
          <div>
            <p class="page-kicker" style="margin-bottom:2px;">Your Volunteer Role</p>
            <strong style="font-size:15px;color:var(--terra-deeper);"><?php echo htmlspecialchars($reg["assigned_role"]); ?></strong>
            <span class="table-subtext"><?php echo htmlspecialchars($reg["team_unit"]); ?></span>
          </div>
        </div>

        <?php if (!empty($reg["attendance_status"]) && $reg["attendance_status"] === "Present"): ?>
          <div class="alert alert-success" style="margin-top:18px;">
            <i class="fa-solid fa-circle-check"></i>
            <span><strong>Check-In Recorded!</strong> You checked in at <?php echo date("g:i A, d M Y", strtotime($reg["check_in_time"])); ?>. Status: <?php echo htmlspecialchars($reg["verification_status"] ?? "Pending Verification"); ?></span>
          </div>
        <?php else: ?>
          <form action="process/check-in.php" method="post">
            <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:22px;padding:14px;font-size:15px;">
              <i class="fa-solid fa-fingerprint"></i> Confirm Event Check-In
            </button>
          </form>
        <?php endif; ?>

        <?php if (count($allRegs) > 1): ?>
          <a href="volunteer-checkin.php" class="btn btn-outline btn-block" style="margin-top:12px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Event List
          </a>
        <?php endif; ?>
      </section>

      <aside class="card checkin-help-card">
        <div class="checkin-help-icon"><i class="fa-solid fa-circle-question"></i></div>
        <h3>Check-In Guidelines</h3>
        <ul class="checkin-help-list">
          <li>Arrive at least 15 minutes before the event begins.</li>
          <li>Ensure your registration has been approved by the committee.</li>
          <li>Check in only once when you arrive at the venue.</li>
          <li>Report to your assigned Team Unit PIC upon arrival.</li>
        </ul>
        <div class="checkin-support-box">
          <i class="fa-solid fa-mosque"></i>
          <div>
            <strong>Need help?</strong>
            <span>Contact Mosque Committee at the registration counter.</span>
          </div>
        </div>
      </aside>
    </section>

  <?php elseif (count($allRegs) > 1): ?>
    <section class="card">
      <p class="page-kicker">Select Event</p>
      <h2 style="font-size:19px;color:var(--terra-deeper);margin:4px 0 6px;">Your Approved Events</h2>
      <p style="color:var(--text-muted);font-size:13.5px;margin-bottom:20px;">You have <?php echo count($allRegs); ?> upcoming approved events. Select the one you are attending today.</p>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($allRegs as $r): ?>
          <a href="volunteer-checkin.php?registration_id=<?php echo $r['registration_id']; ?>" class="checkin-event-pick-row">
            <div class="checkin-pick-date">
              <strong><?php echo date("d", strtotime($r["event_date"])); ?></strong>
              <span><?php echo strtoupper(date("M", strtotime($r["event_date"]))); ?></span>
            </div>
            <div class="checkin-pick-info">
              <strong><?php echo htmlspecialchars($r["event_name"]); ?></strong>
              <small>
                <i class="fa-solid fa-clock"></i> <?php echo date("g:i A", strtotime($r["start_time"])); ?>
                &bull;
                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r["location"]); ?>
                &bull; <?php echo htmlspecialchars($r["assigned_role"]); ?>
              </small>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:var(--text-light);"></i>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

  <?php else: ?>
    <section class="card" style="text-align:center;padding:44px 20px;">
      <div style="width:60px;height:60px;border-radius:18px;background:var(--terra-light);color:var(--terra);display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:14px;">
        <i class="fa-solid fa-clipboard-question"></i>
      </div>
      <h2 style="font-size:19px;margin-bottom:8px;">No Events Ready for Check-In</h2>
      <p style="color:var(--text-muted);font-size:13.5px;max-width:460px;margin:0 auto 22px;">
        You do not have any approved upcoming events awaiting check-in. Register for an event and wait for committee approval.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="volunteer-events.php" class="btn btn-primary"><i class="fa-solid fa-calendar-days"></i> Browse Events</a>
        <a href="volunteer-status.php" class="btn btn-outline"><i class="fa-solid fa-list-check"></i> My Registrations</a>
      </div>
    </section>
  <?php endif; ?>

</main>
</div>
</body>
</html>
