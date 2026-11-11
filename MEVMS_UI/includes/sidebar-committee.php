<?php
// includes/sidebar-committee.php - Unified Navigation for Mosque Committee Portal
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div>
    <div class="sidebar-kicker">
      <i class="fa-solid fa-users-gear"></i>
      <span>COMMITTEE PORTAL</span>
    </div>
    <nav class="sidebar-nav">
      <a href="committee-dashboard.php" class="<?php echo ($currentPage === 'committee-dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
      </a>
      <a href="committee-record-event.php" class="<?php echo ($currentPage === 'committee-record-event.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-plus"></i>
        <span>Record Approved Event</span>
      </a>
      <a href="committee-registrations.php" class="<?php echo ($currentPage === 'committee-registrations.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-check"></i>
        <span>Review Registrations</span>
      </a>
      <a href="committee-assign-roles.php" class="<?php echo ($currentPage === 'committee-assign-roles.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-sitemap"></i>
        <span>Assign Volunteer Roles</span>
      </a>
      <a href="committee-attendance.php" class="<?php echo ($currentPage === 'committee-attendance.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-clipboard-user"></i>
        <span>Attendance Verification</span>
      </a>
    </nav>
  </div>
  <div class="sidebar-footer">
    <a href="process/logout.php" class="logout-link">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Log Out</span>
    </a>
  </div>
</aside>
