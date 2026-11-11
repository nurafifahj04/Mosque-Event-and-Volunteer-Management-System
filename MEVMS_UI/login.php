<?php
// login.php - User Login (FR-02, UI-01)
require_once "includes/session.php";

$loginError   = $_SESSION["login_error"]    ?? "";
$signupSuccess= $_SESSION["signup_success"] ?? "";
unset($_SESSION["login_error"], $_SESSION["signup_success"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-page">

  <div class="auth-card">

    <div class="auth-header">
      <div class="auth-logo">
        <i class="fa-solid fa-mosque"></i>
      </div>
      <h1>MEVMS <span>Portal</span></h1>
      <p>Masjid Kampung Rinching Hulu</p>
      <small>Mosque Event &amp; Volunteer Management System</small>
    </div>

    <?php if ($loginError): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($loginError); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($signupSuccess): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo htmlspecialchars($signupSuccess); ?></span>
      </div>
    <?php endif; ?>

    <form action="process/login.php" method="post">

      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-icon">
          <i class="fa-solid fa-user"></i>
          <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px; padding:13px;">
        <i class="fa-solid fa-right-to-bracket"></i> Log In
      </button>

    </form>

    <div class="auth-divider">
      <span>New to MEVMS?</span>
    </div>

    <a href="signup.php" class="btn btn-outline btn-block">
      <i class="fa-solid fa-user-plus"></i> Create Volunteer Account
    </a>

  </div>

</body>
</html>
