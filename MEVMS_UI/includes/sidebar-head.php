<?php
// includes/sidebar-head.php - Unified Navigation for Head of Mosque Committee Portal
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div>
    <div class="sidebar-kicker">
      <i class="fa-solid fa-crown"></i>
      <span>HEAD OF COMMITTEE</span>
    </div>
    <nav class="sidebar-nav">
      <a href="head-dashboard.php" class="<?php echo ($currentPage === 'head-dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span>Executive Dashboard</span>
      </a>
      <a href="head-token-verify.php" class="<?php echo ($currentPage === 'head-token-verify.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-award"></i>
        <span>Verify Token Status</span>
      </a>
      <a href="head-report.php" class="<?php echo ($currentPage === 'head-report.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-file-lines"></i>
        <span>Volunteer Event Report</span>
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
