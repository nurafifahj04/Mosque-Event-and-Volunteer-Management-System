<?php
// committee-record-event.php - Record Approved Event (FR-04, UI-11)
require_once "includes/db.php";
require_once "includes/auth.php";

require_role(["Mosque Committee Member", "Head of the Mosque Committee"]);

$fullName = $_SESSION["full_name"];
$userRole = $_SESSION["user_role"];

$successMsg = $_SESSION["success_message"] ?? "";
$errorMsg = $_SESSION["error_message"] ?? "";
unset($_SESSION["success_message"], $_SESSION["error_message"]);

// Fetch committee members for PIC dropdown
$pics = $conn->query("SELECT user_id, full_name, committee_position FROM users WHERE user_role IN ('Mosque Committee Member', 'Head of the Mosque Committee') ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Record Approved Event | MEVMS</title>
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
          <p class="page-kicker">Event Administration</p>
          <h1>Record Approved Event</h1>
          <p class="page-description">
            Register programmes approved through the mosque's standard administration process into the MEVMS database.
          </p>
        </div>
        <a href="committee-dashboard.php" class="btn btn-outline dashboard-action">
          <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
      </section>

      <section class="card">
        <form action="process/record-event.php" method="post">

          <h2 class="form-section-title">Event Core Details</h2>

          <div class="form-group">
            <label for="event-name">Event Name *</label>
            <input type="text" id="event-name" name="event_name" placeholder="e.g. Gotong-Royong Perdana Masjid" required>
          </div>

          <div class="form-group">
            <label for="event-description">Event Description</label>
            <textarea id="event-description" name="event_description" placeholder="Briefly describe the programme purpose, target audience, and activities"></textarea>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label for="event-date">Event Date *</label>
              <input type="date" id="event-date" name="event_date" required>
            </div>

            <div class="form-group">
              <label for="location">Location / Venue *</label>
              <input type="text" id="location" name="location" placeholder="e.g. Dewan Solat Utama / Kawasan Masjid" required>
            </div>

            <div class="form-group">
              <label for="start-time">Start Time *</label>
              <input type="time" id="start-time" name="start_time" required>
            </div>

            <div class="form-group">
              <label for="end-time">End Time (Optional)</label>
              <input type="time" id="end-time" name="end_time">
            </div>

            <div class="form-group">
              <label for="event-pic">Person In Charge (PIC)</label>
              <select id="event-pic" name="event_pic_id">
                <?php while ($pic = $pics->fetch_assoc()): ?>
                  <option value="<?php echo $pic['user_id']; ?>">
                    <?php echo htmlspecialchars($pic['full_name']); ?><?php echo $pic['committee_position'] ? " (" . htmlspecialchars($pic['committee_position']) . ")" : ""; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="volunteer-quota">Total Volunteer Quota *</label>
              <input type="number" id="volunteer-quota" name="volunteer_quota" value="3" min="1" max="100" required>
              <small style="color: var(--text-muted); font-size: 11px;">Set to 3 for demonstration and testing of automated quota closure.</small>
            </div>

            <div class="form-group">
              <label for="planned-budget">Planned Budget (RM)</label>
              <input type="number" id="planned-budget" name="planned_budget" min="0" step="0.01" placeholder="e.g. 1500.00">
            </div>

            <div class="form-group">
              <label for="funding-source">Funding Source</label>
              <input type="text" id="funding-source" name="funding_source" placeholder="e.g. Tabung Kebajikan Masjid / Sumbangan Kariah">
            </div>
          </div>

          <h2 class="form-section-title" style="margin-top: 28px;">Volunteer Roles & Quotas Needed</h2>
          <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">
            Define specific team units and role limits for volunteers to choose from.
          </p>

          <!-- Role 1 -->
          <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 14px;">
            <strong style="color: var(--primary-dark); font-size: 14px; margin-bottom: 12px; display: block;">Role 1 (Required)</strong>
            <div class="form-grid">
              <div class="form-group">
                <label>Role Name *</label>
                <input type="text" name="role_name" placeholder="e.g. Kaunter Pendaftaran" required>
              </div>
              <div class="form-group">
                <label>Team / Unit</label>
                <input type="text" name="team_unit" placeholder="e.g. Unit Pendaftaran">
              </div>
              <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills" placeholder="e.g. Komunikasi mesra">
              </div>
              <div class="form-group">
                <label>Role Quota *</label>
                <input type="number" name="role_quota" value="1" min="1" required>
              </div>
            </div>
          </div>

          <!-- Role 2 -->
          <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 14px;">
            <strong style="color: var(--primary-dark); font-size: 14px; margin-bottom: 12px; display: block;">Role 2 (Optional)</strong>
            <div class="form-grid">
              <div class="form-group">
                <label>Role Name</label>
                <input type="text" name="role_name_2" placeholder="e.g. Jamuan & Makanan">
              </div>
              <div class="form-group">
                <label>Team / Unit</label>
                <input type="text" name="team_unit_2" placeholder="e.g. Unit Logistik">
              </div>
              <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills_2" placeholder="e.g. Penyediaan makanan">
              </div>
              <div class="form-group">
                <label>Role Quota</label>
                <input type="number" name="role_quota_2" value="1" min="1">
              </div>
            </div>
          </div>

          <!-- Role 3 -->
          <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 14px;">
            <strong style="color: var(--primary-dark); font-size: 14px; margin-bottom: 12px; display: block;">Role 3 (Optional)</strong>
            <div class="form-grid">
              <div class="form-group">
                <label>Role Name</label>
                <input type="text" name="role_name_3" placeholder="e.g. Kebersihan Kawasan">
              </div>
              <div class="form-group">
                <label>Team / Unit</label>
                <input type="text" name="team_unit_3" placeholder="e.g. Unit Kebersihan">
              </div>
              <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills_3" placeholder="e.g. Stamina fizikal">
              </div>
              <div class="form-group">
                <label>Role Quota</label>
                <input type="number" name="role_quota_3" value="1" min="1">
              </div>
            </div>
          </div>

          <div class="form-actions" style="margin-top: 22px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-floppy-disk"></i> Save & Publish Event
            </button>
            <a href="committee-dashboard.php" class="btn btn-outline">
              <i class="fa-solid fa-xmark"></i> Cancel
            </a>
          </div>

        </form>
      </section>

    </main>

  </div>

</body>
</html>
