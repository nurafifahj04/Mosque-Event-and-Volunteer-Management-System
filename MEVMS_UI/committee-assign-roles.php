<?php
// committee-assign-roles.php - Assign and Reassign Volunteer Roles (FR-09, Lecturer Requirement, UI-13)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// 1. Fetch events
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");

// Default to first event or selected event
$selectedEventId = intval($_GET["event_id"] ?? 0);
if ($selectedEventId <= 0) {
    $firstEv = $conn->query("SELECT event_id FROM events ORDER BY event_date DESC LIMIT 1")->fetch_assoc();
    $selectedEventId = $firstEv ? intval($firstEv["event_id"]) : 0;
}

// 2. Fetch roles for this event with assignment counts
$rolesQuery = "
    SELECT 
        er.event_role_id,
        er.role_name,
        er.team_unit,
        er.role_quota,
        COUNT(ra.assignment_id) AS current_assigned
    FROM event_roles er
    LEFT JOIN role_assignments ra ON er.event_role_id = ra.event_role_id
    WHERE er.event_id = ?
    GROUP BY er.event_role_id
";
$stmt = $conn->prepare($rolesQuery);
$stmt->bind_param("i", $selectedEventId);
$stmt->execute();
$rolesResult = $stmt->get_result();
$eventRoles = [];
while ($r = $rolesResult->fetch_assoc()) {
    $eventRoles[] = $r;
}
$stmt->close();

// 3. Fetch approved volunteers for this event
$volunteersQuery = "
    SELECT 
        vr.registration_id,
        u.full_name,
        u.residential_area,
        u.phone_number,
        pref_er.role_name AS preferred_role_name,
        pref_er.team_unit AS preferred_unit,
        curr_er.event_role_id AS current_role_id,
        curr_er.role_name AS current_role_name,
        curr_er.team_unit AS current_unit
    FROM volunteer_registrations vr
    JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN role_assignments ra ON vr.registration_id = ra.registration_id
    LEFT JOIN event_roles curr_er ON ra.event_role_id = curr_er.event_role_id
    WHERE vr.event_id = ? AND vr.registration_status = 'Approved'
    ORDER BY u.full_name ASC
";
$stmt = $conn->prepare($volunteersQuery);
$stmt->bind_param("i", $selectedEventId);
$stmt->execute();
$volunteers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Volunteer Roles | MEVMS</title>
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
          <p class="page-kicker">Role Management & Allocation</p>
          <h1>Assign & Balance Volunteer Roles</h1>
          <p class="page-description">
            Assign team duties for volunteers who registered with "No Preference" or balance allocations across units.
          </p>
        </div>
      </section>

      <!-- Event Filter Card -->
      <section class="card" style="margin-bottom: 22px;">
        <form method="get" action="committee-assign-roles.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
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
            <i class="fa-solid fa-arrows-rotate"></i> Load Event Roles
          </button>
        </form>
      </section>

      <!-- Role Quota Balance Cards -->
      <section class="grid-3" style="margin-bottom: 24px;">
        <?php foreach ($eventRoles as $er): ?>
          <?php
            $isFull = ($er["current_assigned"] >= $er["role_quota"]);
          ?>
          <div class="stat <?php echo $isFull ? 'stat-blue' : 'stat-accent'; ?>">
            <div class="stat-icon <?php echo $isFull ? 'blue-icon' : 'gold-icon'; ?>">
              <i class="fa-solid fa-users"></i>
            </div>
            <div>
              <h2><?php echo $er["current_assigned"]; ?> / <?php echo $er["role_quota"]; ?></h2>
              <p>
                <strong><?php echo htmlspecialchars($er["role_name"]); ?></strong>
                <?php if ($isFull): ?>
                  <span style="color: var(--danger); font-size: 11.5px; font-weight: 700; display: block;">Quota Full</span>
                <?php else: ?>
                  <span style="color: var(--primary); font-size: 11.5px; font-weight: 600; display: block;"><?php echo ($er["role_quota"] - $er["current_assigned"]); ?> Vacancy Available</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <!-- Approved Volunteers Table -->
      <section class="card">
        <h2 class="form-section-title">Approved Volunteers & Role Balancing</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 18px;">
          Review the volunteer's preference. For volunteers with <strong>Open / No Preference</strong>, assign them to vacancies.
        </p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Volunteer Name</th>
                <th>Residential Area</th>
                <th>Volunteer's Choice</th>
                <th>Currently Assigned Role</th>
                <th>Assign / Reassign Role</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($volunteers->num_rows === 0): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No approved volunteers found for this event yet. Approve registrations first in <a href="committee-registrations.php" style="color: var(--primary); font-weight: 600;">Review Registrations</a>.
                  </td>
                </tr>
              <?php else: ?>
                <?php while ($v = $volunteers->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($v["full_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($v["phone_number"]); ?></span>
                    </td>

                    <td>
                      <?php echo htmlspecialchars($v["residential_area"]); ?>
                    </td>

                    <td>
                      <?php if (!empty($v["preferred_role_name"])): ?>
                        <span class="badge" style="background: var(--info-bg); color: var(--info); border: 1px solid var(--info-border);">
                          <i class="fa-solid fa-user-tag"></i> <?php echo htmlspecialchars($v["preferred_role_name"]); ?>
                        </span>
                        <span class="table-subtext"><?php echo htmlspecialchars($v["preferred_unit"] ?? "-"); ?></span>
                      <?php else: ?>
                        <span class="badge badge-accent">
                          <i class="fa-solid fa-hand-holding-heart"></i> Open / No Preference
                        </span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($v["current_role_name"]): ?>
                        <span class="badge badge-approved">
                          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($v["current_role_name"]); ?>
                        </span>
                        <span class="table-subtext"><?php echo htmlspecialchars($v["current_unit"] ?? "-"); ?></span>
                      <?php else: ?>
                        <span class="badge badge-pending">
                          <i class="fa-solid fa-clock"></i> Unassigned
                        </span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <!-- Role Assignment Form (FR-09) -->
                      <form action="process/assign-role.php" method="post" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="registration_id" value="<?php echo $v['registration_id']; ?>">
                        <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>">

                        <select name="event_role_id" style="padding: 6px 10px; font-size: 12px; border-radius: 6px;" required>
                          <option value="">Select target role...</option>
                          <?php foreach ($eventRoles as $er): ?>
                            <?php
                              $isCurrent = ($v["current_role_id"] == $er["event_role_id"]);
                              $isFull = (!$isCurrent && $er["current_assigned"] >= $er["role_quota"]);
                            ?>
                            <option value="<?php echo $er['event_role_id']; ?>" 
                              <?php echo $isCurrent ? 'selected' : ''; ?>
                              <?php echo $isFull ? 'disabled' : ''; ?>>
                              <?php echo htmlspecialchars($er['role_name']); ?> (<?php echo $er['current_assigned']; ?>/<?php echo $er['role_quota']; ?><?php echo $isFull ? ' - Full' : ''; ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                          <i class="fa-solid fa-arrows-rotate"></i> <?php echo $v["current_role_name"] ? "Reassign" : "Assign"; ?>
                        </button>
                      </form>
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
