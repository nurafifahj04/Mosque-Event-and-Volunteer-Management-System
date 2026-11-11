<?php
// volunteer-token.php - Token Status (FR-14, UI-08)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role("Volunteer");

$volunteerId = $_SESSION["user_id"];
$fullName = $_SESSION["full_name"];

$query = "
    SELECT 
        tr.token_id,
        tr.token_type,
        tr.token_status,
        tr.distributed_at,
        tr.verification_status,
        e.event_name,
        e.event_date
    FROM token_records tr
    JOIN volunteer_registrations vr ON tr.registration_id = vr.registration_id
    JOIN events e ON vr.event_id = e.event_id
    WHERE vr.volunteer_id = ?
    ORDER BY e.event_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $volunteerId);
$stmt->execute();
$result = $stmt->get_result();

$tokens = [];
$receivedCount = 0;
$pendingCount = 0;
$verifiedCount = 0;

while ($row = $result->fetch_assoc()) {
    $tokens[] = $row;
    if ($row["token_status"] === "Distributed") {
        $receivedCount++;
    } else {
        $pendingCount++;
    }
    if ($row["verification_status"] === "Verified") {
        $verifiedCount++;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Token Status | MEVMS</title>
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
          <p class="page-kicker">Volunteer Recognition</p>
          <h1>Token Status</h1>
          <p class="page-description">
            View appreciation items, certificates, and token distribution status verified by the Mosque Committee.
          </p>
        </div>
        <a href="volunteer-history.php" class="btn btn-outline dashboard-action">
          <i class="fa-solid fa-clock-rotate-left"></i> Volunteer History
        </a>
      </section>

      <section class="grid-3 registration-summary" style="margin-bottom: 24px;">
        <div class="stat">
          <div class="stat-icon green-icon"><i class="fa-solid fa-gift"></i></div>
          <div>
            <h2><?php echo $receivedCount; ?></h2>
            <p>Tokens Distributed</p>
          </div>
        </div>

        <div class="stat stat-accent">
          <div class="stat-icon gold-icon"><i class="fa-solid fa-hourglass-half"></i></div>
          <div>
            <h2><?php echo $pendingCount; ?></h2>
            <p>Pending Distribution</p>
          </div>
        </div>

        <div class="stat stat-blue">
          <div class="stat-icon blue-icon"><i class="fa-solid fa-circle-check"></i></div>
          <div>
            <h2><?php echo $verifiedCount; ?></h2>
            <p>Nazir Verified</p>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
          <div>
            <p class="page-kicker">Token Records</p>
            <h2 style="color: var(--primary-dark); font-size: 19px;">Volunteer Appreciation Items</h2>
          </div>
          <span class="history-count"><?php echo count($tokens); ?> Records</span>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Appreciation Item</th>
                <th>Distribution Status</th>
                <th>Nazir Verification</th>
                <th>Distribution Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tokens)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    No token records found. Complete event participation to receive appreciation items.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($tokens as $t): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($t["event_name"]); ?></strong>
                      <span class="table-subtext"><i class="fa-solid fa-calendar"></i> <?php echo date("d F Y", strtotime($t["event_date"])); ?></span>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($t["token_type"]); ?></strong>
                    </td>

                    <td>
                      <?php if ($t["token_status"] === "Distributed"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Distributed</span>
                        <?php if ($t["distributed_at"]): ?>
                          <span class="table-subtext" style="color: var(--success); font-weight: 600;">Collected on <?php echo date("d M Y", strtotime($t["distributed_at"])); ?></span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> Pending Collection</span>
                        <span class="table-subtext">Collect from committee counter</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($t["verification_status"] === "Verified"): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-award"></i> Verified</span>
                        <span class="table-subtext">Certified by Head of Committee</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Pending Verification</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php echo $t["distributed_at"] ? date("d M Y", strtotime($t["distributed_at"])) : "–"; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card history-note-card" style="margin-top: 22px;">
        <div class="history-note-icon">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <h3>Token Distribution and Verification</h3>
          <p>
            Appreciation items such as T-shirts and certificates are distributed after the event. The Head of the Mosque Committee verifies each token distribution record for mosque transparency and recognition.
          </p>
        </div>
      </section>

    </main>

  </div>

</body>
</html>
