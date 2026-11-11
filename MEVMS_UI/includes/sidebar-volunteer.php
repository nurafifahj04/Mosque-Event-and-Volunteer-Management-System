<?php
// includes/sidebar-volunteer.php - Unified Navigation for Volunteer Portal
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div>
    <div class="sidebar-kicker">
      <i class="fa-solid fa-hands-holding-child"></i>
      <span>VOLUNTEER PORTAL</span>
    </div>
    <nav class="sidebar-nav">
      <a href="volunteer-dashboard.php" class="<?php echo ($currentPage === 'volunteer-dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
      </a>
      <a href="volunteer-events.php" class="<?php echo ($currentPage === 'volunteer-events.php' || $currentPage === 'volunteer-register.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-days"></i>
        <span>Approved Events</span>
      </a>
      <a href="volunteer-status.php" class="<?php echo ($currentPage === 'volunteer-status.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-list-check"></i>
        <span>My Registrations</span>
      </a>
      <a href="volunteer-checkin.php" class="<?php echo ($currentPage === 'volunteer-checkin.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-clipboard-check"></i>
        <span>Check-In</span>
      </a>
      <a href="volunteer-token.php" class="<?php echo ($currentPage === 'volunteer-token.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-gift"></i>
        <span>Token Status</span>
      </a>
      <a href="volunteer-history.php" class="<?php echo ($currentPage === 'volunteer-history.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>Volunteer History</span>
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
