<?php
// committee-registrations.php - Review Volunteer Registrations (FR-07, FR-08, UI-12)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

$selectedEventId = intval($_GET["event_id"] ?? 0);

// Fetch all events for filter
$eventsResult = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");

// Fetch registrations
$whereSql = "";
if ($selectedEventId > 0) {
    $whereSql = "WHERE vr.event_id = " . $selectedEventId;
}

$query = "
    SELECT 
        vr.registration_id,
        vr.registration_date,
        vr.registration_status,
        vr.decision_comment,
        vr.decision_at,
        vr.preferred_event_role_id,
        u.full_name AS volunteer_name,
        u.phone_number,
        u.residential_area,
        e.event_id,
        e.event_name,
        e.event_date,
        COALESCE(pref_er.role_name, 'No Preference') AS preferred_role,
        pref_er.team_unit AS preferred_unit,
        decider.full_name AS decider_name
    FROM volunteer_registrations vr
    JOIN users u ON vr.volunteer_id = u.user_id
    JOIN events e ON vr.event_id = e.event_id
    LEFT JOIN event_roles pref_er ON vr.preferred_event_role_id = pref_er.event_role_id
    LEFT JOIN users decider ON vr.decision_by = decider.user_id
    $whereSql
    ORDER BY (vr.registration_status = 'Pending') DESC, vr.registration_date DESC
";

$registrations = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Registrations | MEVMS</title>
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
          <p class="page-kicker">Volunteer Applications</p>
          <h1>Review Volunteer Registrations</h1>
          <p class="page-description">
            Evaluate incoming volunteer applications, check preferred roles or "No Preference" requests, and approve or decline participants.
          </p>
        </div>
        <a href="committee-assign-roles.php" class="btn btn-outline dashboard-action">
          <i class="fa-solid fa-sitemap"></i> Assign Roles
        </a>
      </section>

      <!-- Event Filter -->
      <section class="card" style="margin-bottom: 22px;">
        <form method="get" action="committee-registrations.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
          <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 250px;">
            <label for="event_id"><i class="fa-solid fa-filter"></i> Filter by Event</label>
            <select name="event_id" id="event_id">
              <option value="0">All Mosque Events</option>
              <?php while ($ev = $eventsResult->fetch_assoc()): ?>
                <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selectedEventId === intval($ev['event_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ev['event_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-magnifying-glass"></i> Filter
          </button>
          <?php if ($selectedEventId > 0): ?>
            <a href="committee-registrations.php" class="btn btn-outline">
              <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
          <?php endif; ?>
        </form>
      </section>

      <!-- Registrations Table -->
      <section class="card">
        <div class="card-header" style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
          <h2 class="form-section-title" style="margin: 0;">Volunteer Applications Roster</h2>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Volunteer Name</th>
                <th>Target Event</th>
                <th>Contact & Residence</th>
                <th>Role Preference</th>
                <th>Status</th>
                <th>Review & Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($registrations->num_rows === 0): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No volunteer registrations found for this filter.
                  </td>
                </tr>
              <?php else: ?>
                <?php while ($r = $registrations->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($r["volunteer_name"]); ?></strong>
                      <span class="table-subtext">Applied: <?php echo date("d M Y, g:i A", strtotime($r["registration_date"])); ?></span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($r["event_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-calendar"></i> <?php echo date("d M Y", strtotime($r["event_date"])); ?></span>
                    </td>

                    <td>
                      <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($r["phone_number"]); ?></span>
                      <span class="table-subtext"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($r["residential_area"]); ?></span>
                    </td>

                    <td>
                      <?php if ($r["preferred_event_role_id"] === null || $r["preferred_role"] === "No Preference"): ?>
                        <span class="badge badge-accent">
                          <i class="fa-solid fa-hand-holding-heart"></i> Open (Let Committee Assign)
                        </span>
                      <?php else: ?>
                        <span class="badge" style="background: var(--info-bg); color: var(--info); border: 1px solid var(--info-border);">
                          <i class="fa-solid fa-user-tag"></i> <?php echo htmlspecialchars($r["preferred_role"]); ?>
                        </span>
                        <?php if ($r["preferred_unit"]): ?>
                          <span class="table-subtext">Unit: <?php echo htmlspecialchars($r["preferred_unit"]); ?></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($r["registration_status"] === "Approved"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Approved</span>
                        <?php if ($r["decision_comment"]): ?>
                          <span class="table-subtext"><?php echo htmlspecialchars($r["decision_comment"]); ?></span>
                        <?php endif; ?>
                      <?php elseif ($r["registration_status"] === "Pending"): ?>
                        <span class="badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> Pending Review</span>
                      <?php else: ?>
                        <span class="badge badge-rejected"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                        <?php if ($r["decision_comment"]): ?>
                          <span class="table-subtext"><?php echo htmlspecialchars($r["decision_comment"]); ?></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($r["registration_status"] === "Pending"): ?>
                        <form action="process/review-registration.php" method="post" style="display: flex; flex-direction: column; gap: 6px;">
                          <input type="hidden" name="registration_id" value="<?php echo $r['registration_id']; ?>">
                          <input type="text" name="decision_comment" placeholder="Optional remark" style="font-size: 11.5px; padding: 5px 8px; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                          <div style="display: flex; gap: 6px;">
                            <button type="submit" name="decision" value="Approve" class="btn btn-primary" style="padding: 5px 12px; font-size: 11.5px;">
                              <i class="fa-solid fa-check"></i> Approve
                            </button>
                            <button type="submit" name="decision" value="Reject" class="btn btn-danger" style="padding: 5px 12px; font-size: 11.5px;">
                              <i class="fa-solid fa-xmark"></i> Reject
                            </button>
                          </div>
                        </form>
                      <?php else: ?>
                        <div style="font-size: 12px; color: var(--text-muted);">
                          Decided by <strong><?php echo htmlspecialchars($r["decider_name"] ?? "Committee"); ?></strong><br>
                          <small><?php echo $r["decision_at"] ? date("d M Y, g:i A", strtotime($r["decision_at"])) : ""; ?></small>
                        </div>
                      <?php endif; ?>
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
