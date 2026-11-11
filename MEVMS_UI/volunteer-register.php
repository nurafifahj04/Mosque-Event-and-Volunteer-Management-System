<?php
// volunteer-register.php - Event Registration & Preferred Role Selection (FR-06, UI-05)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];
$eventId = intval($_GET["event_id"] ?? 0);

if ($eventId <= 0) {
    header("Location: volunteer-events.php");
    exit;
}

// 1. Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION["error_message"] = "Event not found.";
    header("Location: volunteer-events.php");
    exit;
}

// 2. Check if user already registered
$stmt = $conn->prepare("SELECT registration_id, registration_status FROM volunteer_registrations WHERE event_id = ? AND volunteer_id = ?");
$stmt->bind_param("ii", $eventId, $volunteerId);
$stmt->execute();
$existingReg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingReg) {
    $_SESSION["error_message"] = "You have already registered for this event (Status: " . $existingReg["registration_status"] . ").";
    header("Location: volunteer-status.php");
    exit;
}

// 3. Count total registered
$stmt = $conn->prepare("SELECT COUNT(*) FROM volunteer_registrations WHERE event_id = ? AND registration_status != 'Rejected'");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$totalRegistered = $stmt->get_result()->fetch_row()[0];
$stmt->close();

$quota = intval($event["volunteer_quota"]);
$availableSpaces = max(0, $quota - $totalRegistered);

if ($availableSpaces <= 0) {
    $_SESSION["error_message"] = "This event has reached its maximum volunteer quota.";
    header("Location: volunteer-events.php");
    exit;
}

// 4. Fetch available roles for this event with individual role quota counts
$rolesQuery = "
    SELECT 
        er.event_role_id,
        er.role_name,
        er.role_description,
        er.team_unit,
        er.required_skills,
        er.role_quota,
        COUNT(ra.assignment_id) AS assigned_count
    FROM event_roles er
    LEFT JOIN role_assignments ra ON er.event_role_id = ra.event_role_id
    WHERE er.event_id = ?
    GROUP BY er.event_role_id
";
$stmt = $conn->prepare($rolesQuery);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$rolesResult = $stmt->get_result();
$roles = [];
while ($r = $rolesResult->fetch_assoc()) {
    $roles[] = $r;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register for Event | MEVMS</title>
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
          <p class="page-kicker">Volunteer Opportunity</p>
          <h1>Register for Event</h1>
          <p class="page-description">
            Choose your preferred role or select "No Preference" to allow the mosque committee to assign you based on need.
          </p>
        </div>
        <a href="volunteer-events.php" class="btn btn-outline dashboard-action">
          <i class="fa-solid fa-arrow-left"></i> Back to Events
        </a>
      </section>

      <!-- Event Summary Card -->
      <section class="card reg-event-info-card" style="margin-bottom: 24px;">
        <div class="reg-event-header">
          <div>
            <span class="badge badge-approved"><i class="fa-solid fa-circle-dot"></i> Registration Open</span>
            <h2 class="reg-event-title"><?php echo htmlspecialchars($event["event_name"]); ?></h2>
            <p class="reg-event-desc"><?php echo htmlspecialchars($event["event_description"]); ?></p>
          </div>
          <div class="reg-date-box">
            <strong><?php echo date("d", strtotime($event["event_date"])); ?></strong>
            <span><?php echo strtoupper(date("M", strtotime($event["event_date"]))); ?></span>
            <small><?php echo date("Y", strtotime($event["event_date"])); ?></small>
          </div>
        </div>

        <div class="reg-event-meta">
          <span>
            <i class="fa-solid fa-clock"></i>
            <?php echo date("g:i A", strtotime($event["start_time"]));
            echo $event["end_time"] ? " – " . date("g:i A", strtotime($event["end_time"])) : ""; ?>
          </span>
          <span>
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($event["location"]); ?>
          </span>
          <span>
            <i class="fa-solid fa-people-group"></i>
            <strong><?php echo $availableSpaces; ?></strong> of <?php echo $quota; ?> spaces available
          </span>
        </div>
      </section>

      <!-- Registration Form -->
      <form action="process/register-event.php" method="post">
        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">

        <!-- Role Selection -->
        <section class="card" style="margin-bottom: 24px;">
          <p class="page-kicker">Volunteer Role Preference</p>
          <h2 class="reg-section-title" style="margin-bottom: 8px;">Select a Preferred Role</h2>
          <p class="reg-role-note" style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 20px;">
            You may select a specific task unit, or pick <strong>No Preference</strong> if you are open to any role the committee assigns.
          </p>

          <div class="role-options">

            <!-- "No Preference / Let Committee Assign" Option -->
            <label class="role-option role-option-no-pref is-selected" id="label_role_0" onclick="handleRoleSelect(0)">
              <input type="radio" id="role_0" name="volunteer_role" value="" checked>
              <div class="role-option-content">
                <div class="role-radio-circle">
                  <i class="fa-solid fa-check"></i>
                </div>
                <div class="role-option-icon">
                  <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
                <div class="role-option-details">
                  <strong>⭐ No Preference — Let Mosque Committee Assign</strong>
                  <small>Flexible participation. The mosque committee will assign you to the team unit with the highest need.</small>
                  <em>Open to All Tasks</em>
                </div>
                <div class="role-space">
                  <span class="badge badge-accent">Recommended</span>
                </div>
              </div>
            </label>

            <!-- Specific Roles defined for this event -->
            <?php foreach ($roles as $r):
              $rQuota  = intval($r["role_quota"]);
              $rLeft   = max(0, $rQuota - intval($r["assigned_count"]));
              $isFull  = ($rLeft <= 0);
            ?>
            <label class="role-option <?php echo $isFull ? 'role-unavailable' : ''; ?>" id="label_role_<?php echo $r['event_role_id']; ?>" onclick="handleRoleSelect(<?php echo $r['event_role_id']; ?>)">
              <input
                type="radio"
                id="role_<?php echo $r['event_role_id']; ?>"
                name="volunteer_role"
                value="<?php echo $r['event_role_id']; ?>"
                <?php echo $isFull ? 'disabled' : ''; ?>
              >
              <div class="role-option-content">
                <div class="role-radio-circle">
                  <i class="fa-solid fa-check"></i>
                </div>
                <div class="role-option-icon">
                  <i class="fa-solid fa-user-gear"></i>
                </div>
                <div class="role-option-details">
                  <strong><?php echo htmlspecialchars($r["role_name"]); ?></strong>
                  <small><?php echo htmlspecialchars($r["role_description"]); ?></small>
                  <?php if (!empty($r["team_unit"])): ?>
                    <em>Unit: <?php echo htmlspecialchars($r["team_unit"]); ?></em>
                  <?php endif; ?>
                </div>
                <div class="role-space">
                  <?php if ($isFull): ?>
                    <span class="badge badge-rejected">Full</span>
                  <?php else: ?>
                    <b><?php echo $rLeft; ?></b>
                    <small>spaces left</small>
                  <?php endif; ?>
                </div>
              </div>
            </label>
            <?php endforeach; ?>

          </div>
        </section>

        <!-- Confirmation Card -->
        <section class="card reg-confirm-row">
          <div class="reg-confirm-text">
            <i class="fa-solid fa-circle-info" style="color: var(--primary); font-size: 24px;"></i>
            <div>
              <strong>Ready to submit your registration?</strong>
              <p>Your registration will be submitted to the Mosque Committee for review and role confirmation.</p>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane"></i> Confirm & Register
          </button>
        </section>

      </form>

    </main>

  </div>

  <script>
    function handleRoleSelect(roleId) {
      const radio = document.getElementById('role_' + roleId);
      if (radio && !radio.disabled) {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('is-selected'));
        radio.checked = true;
        const parent = document.getElementById('label_role_' + roleId);
        if (parent) parent.classList.add('is-selected');
      }
    }

    document.querySelectorAll('input[name="volunteer_role"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('is-selected'));
        const parent = this.closest('.role-option');
        if (parent) parent.classList.add('is-selected');
      });
    });
  </script>

</body>
</html>
