<?php
// signup.php - Volunteer Sign-Up Page (FR-01, UI-02)
require_once "includes/session.php";

$signupError = $_SESSION["signup_error"] ?? "";
unset($_SESSION["signup_error"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Volunteer Account | MEVMS</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-page">

  <div class="auth-card auth-card-wide">

    <div class="auth-header">
      <div class="auth-logo">
        <i class="fa-solid fa-mosque"></i>
      </div>
      <h1>MEVMS <span>Portal</span></h1>
      <p>Masjid Kampung Rinching Hulu</p>
      <small>Volunteer Account Registration</small>
    </div>

    <div style="margin-bottom: 20px;">
      <h2 style="font-size: 18px; color: var(--primary-dark); font-weight: 700; margin-bottom: 4px;">
        <i class="fa-solid fa-user-plus" style="color: var(--primary); margin-right: 6px;"></i> Register as a Volunteer
      </h2>
      <p style="font-size: 13px; color: var(--text-muted);">
        Join our mosque volunteer community to support upcoming programmes and events.
      </p>
    </div>

    <?php if ($signupError): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($signupError); ?></span>
      </div>
    <?php endif; ?>

    <form action="process/signup.php" method="post">

      <div class="form-group">
        <label for="username">Desired Username</label>
        <div class="input-icon">
          <i class="fa-solid fa-at"></i>
          <input type="text" id="username" name="username" placeholder="Choose a username" required>
        </div>
      </div>

      <div class="form-group">
        <label for="full_name">Full Name (As per IC)</label>
        <div class="input-icon">
          <i class="fa-solid fa-id-card"></i>
          <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label for="phone_number">Phone Number</label>
          <div class="input-icon">
            <i class="fa-solid fa-phone"></i>
            <input type="text" id="phone_number" name="phone_number" placeholder="e.g. 012-345 6789" required>
          </div>
        </div>

        <div class="form-group">
          <label for="residential_area">Residential Area</label>
          <div class="input-icon">
            <i class="fa-solid fa-location-dot"></i>
            <input type="text" id="residential_area" name="residential_area" placeholder="e.g. Kg Rinching Hulu" required>
          </div>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-icon">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" required>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-icon">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" minlength="8" required>
          </div>
        </div>
      </div>

      <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="fa-solid fa-circle-info"></i>
        <span>Newly created accounts are automatically enrolled in the <strong>Volunteer</strong> role.</span>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="padding: 13px;">
        <i class="fa-solid fa-user-check"></i> Register Account
      </button>

      <a href="login.php" class="btn btn-outline btn-block" style="margin-top: 10px;">
        <i class="fa-solid fa-arrow-left"></i> Back to Login
      </a>

    </form>

  </div>

</body>
</html>
