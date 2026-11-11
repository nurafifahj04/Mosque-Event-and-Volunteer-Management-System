<?php
// head-token-verify.php - Verify Token Status (FR-15, UI-16)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Head of the Mosque Committee");

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

$selectedEventId = intval($_GET["event_id"] ?? 0);
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");

if ($selectedEventId <= 0) {
    $firstEv = $conn->query("SELECT event_id FROM events ORDER BY event_date DESC LIMIT 1")->fetch_assoc();
    $selectedEventId = $firstEv ? intval($firstEv["event_id"]) : 0;
}

// Fetch tokens for selected event
$query = "
    SELECT 
        tr.token_id,
        tr.token_type,
        tr.token_status,
        tr.distributed_at,
        tr.verification_status,
        u.full_name AS volunteer_name,
        u.phone_number,
        verifier.full_name AS verifier_name
    FROM token_records tr
    JOIN volunteer_registrations vr ON tr.registration_id = vr.registration_id
    JOIN users u ON vr.volunteer_id = u.user_id
    LEFT JOIN users verifier ON tr.verified_by = verifier.user_id
    WHERE vr.event_id = ?
    ORDER BY (tr.verification_status = 'Pending') DESC, tr.token_id ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selectedEventId);
$stmt->execute();
$tokens = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Token Status | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php require_once "includes/topbar.php"; ?>

  <div class="layout">

    <?php require_once "includes/sidebar-head.php"; ?>

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
          <p class="page-kicker">Volunteer Governance</p>
          <h1>Verify Token Status</h1>
          <p class="page-description">
            Audit and endorse volunteer appreciation token records (certificates and gift items) for mosque accountability.
          </p>
        </div>
      </section>

      <!-- Event Filter -->
      <section class="card" style="margin-bottom: 22px;">
        <form method="get" action="head-token-verify.php" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
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
            <i class="fa-solid fa-arrows-rotate"></i> Load Tokens
          </button>
        </form>
      </section>

      <!-- Token Records Table -->
      <section class="card">
        <h2 class="form-section-title">Token Distribution Records</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 18px;">
          Certify that tokens have been rightfully distributed to verified attendees.
        </p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Volunteer Name</th>
                <th>Token Item</th>
                <th>Distribution Status</th>
                <th>Date Distributed</th>
                <th>Nazir Verification</th>
                <th>Endorse Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($tokens->num_rows === 0): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No token records found for this event.
                  </td>
                </tr>
              <?php else: ?>
                <?php while ($t = $tokens->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($t["volunteer_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($t["phone_number"]); ?></span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($t["token_type"]); ?></strong>
                    </td>

                    <td>
                      <?php if ($t["token_status"] === "Distributed"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-gift"></i> Distributed</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Pending Distribution</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php echo $t["distributed_at"] ? date("d M Y", strtotime($t["distributed_at"])) : "–"; ?>
                    </td>

                    <td>
                      <?php if ($t["verification_status"] === "Verified"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Verified</span>
                        <span class="table-subtext">Certified by Nazir</span>
                      <?php else: ?>
                        <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($t["verification_status"] !== "Verified"): ?>
                        <form action="process/verify-token.php" method="post">
                          <input type="hidden" name="token_id" value="<?php echo $t['token_id']; ?>">
                          <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>">
                          <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px;">
                            <i class="fa-solid fa-award"></i> Verify & Endorse
                          </button>
                        </form>
                      <?php else: ?>
                        <span style="color: var(--success); font-size: 12px; font-weight: 600;">
                          <i class="fa-solid fa-check-double"></i> Certified
                        </span>
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
