<?php
// volunteer-events.php - Approved Events List (FR-05, FR-06, UI-04)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// Fetch all open/upcoming events with correct quota counts
// registered_count = Pending + Approved only (not Rejected)
$eventsQuery = "
    SELECT
        e.event_id,
        e.event_name,
        e.event_description,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        e.volunteer_quota,
        e.event_status,
        COUNT(DISTINCT CASE WHEN vr.registration_status IN ('Pending','Approved') THEN vr.registration_id END) AS registered_count,
        MAX(CASE WHEN vr.volunteer_id = ? AND vr.registration_status IN ('Pending','Approved') THEN 1 ELSE 0 END) AS already_registered
    FROM events e
    LEFT JOIN volunteer_registrations vr ON e.event_id = vr.event_id
    WHERE e.event_status != 'Completed'
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
";

$stmt = $conn->prepare($eventsQuery);
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approved Events | MEVMS</title>
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

      <!-- Hero Banner -->
      <section class="mosque-hero-banner">
        <div class="hero-banner-content">
          <span class="hero-kicker">Masjid Kampung Rinching Hulu</span>
          <h1>Lend a Hand and Serve Your Community</h1>
          <p>
            Explore upcoming approved mosque programmes. Register your availability, choose your preferred role or let our committee match you.
          </p>
        </div>
        <div class="hero-banner-stat">
          <div class="hero-stat-badge">
            <i class="fa-solid fa-hand-holding-heart"></i>
            <div>
              <strong><?php echo count($events); ?></strong>
              <span>Active Programmes</span>
            </div>
          </div>
        </div>
      </section>

      <div class="filter-pills-bar">
        <button type="button" class="filter-pill active">
          <i class="fa-solid fa-border-all"></i> All Opportunities (<?php echo count($events); ?>)
        </button>
      </div>

      <section class="grid-2 event-grid">

        <?php foreach ($events as $ev): ?>
          <?php
            $quota = intval($ev["volunteer_quota"]);
            $filled = intval($ev["registered_count"]);
            $available = max(0, $quota - $filled);
            $isClosed = ($ev["event_status"] === "Closed") || ($available <= 0);
            $isAlreadyRegistered = !empty($ev["already_registered"]);

            $eventTimestamp = strtotime($ev["event_date"]);
            $day = date("d", $eventTimestamp);
            $month = strtoupper(date("M", $eventTimestamp));
            $pct = $quota > 0 ? min(100, round(($filled / $quota) * 100)) : 100;
          ?>
          <article class="event-card">

            <div class="event-card-top">
              <?php if ($isClosed): ?>
                <span class="badge badge-rejected">
                  <i class="fa-solid fa-circle-xmark"></i> Registration Closed
                </span>
              <?php else: ?>
                <span class="badge badge-approved">
                  <i class="fa-solid fa-circle-dot"></i> Registration Open
                </span>
              <?php endif; ?>

              <span class="event-date-badge">
                <strong><?php echo $day; ?></strong>
                <span><?php echo $month; ?></span>
              </span>
            </div>

            <div class="event-card-content">
              <p class="page-kicker">Masjid Community Event</p>
              <h2><?php echo htmlspecialchars($ev["event_name"]); ?></h2>

              <p class="event-description">
                <?php echo htmlspecialchars($ev["event_description"]); ?>
              </p>

              <div class="event-detail-list">
                <div>
                  <i class="fa-solid fa-clock"></i>
                  <span><?php echo date("g:i A", strtotime($ev["start_time"])); ?><?php echo $ev["end_time"] ? " – " . date("g:i A", strtotime($ev["end_time"])) : ""; ?></span>
                </div>
                <div>
                  <i class="fa-solid fa-location-dot"></i>
                  <span><?php echo htmlspecialchars($ev["location"]); ?></span>
                </div>
                <div>
                  <i class="fa-solid fa-people-group"></i>
                  <span><strong><?php echo $filled; ?> of <?php echo $quota; ?></strong> volunteer spaces filled</span>
                </div>
              </div>

              <!-- Quota Bar -->
              <div class="event-quota-progress">
                <div class="event-quota-labels">
                  <span>Volunteer Quota Usage</span>
                  <span><strong><?php echo $available; ?> Spaces Left</strong></span>
                </div>
                <div class="quota-bar-track">
                  <div class="quota-bar-fill <?php echo $isClosed ? 'is-full' : ''; ?>" style="width: <?php echo $pct; ?>%;"></div>
                </div>
              </div>

              <div class="event-card-footer">
                <?php if ($isAlreadyRegistered): ?>
                  <a href="volunteer-status.php" class="btn btn-outline btn-block">
                    <i class="fa-solid fa-circle-check"></i> Already Registered (View Status)
                  </a>
                <?php elseif ($isClosed): ?>
                  <button type="button" class="btn btn-outline btn-block" style="opacity: 0.6; cursor: not-allowed;" disabled>
                    <i class="fa-solid fa-ban"></i> Quota Full / Closed
                  </button>
                <?php else: ?>
                  <a href="volunteer-register.php?event_id=<?php echo $ev['event_id']; ?>" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-calendar-check"></i> View Event & Register
                  </a>
                <?php endif; ?>
              </div>

            </div>

          </article>
        <?php endforeach; ?>

      </section>

      <section class="card history-note-card" style="margin-top: 24px;">
        <div class="history-note-icon">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <h3>Registration Policy & Automated Quota</h3>
          <p>
            Volunteers may register once per event. When volunteer spaces are filled (quota limit reached), registration is automatically closed.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
