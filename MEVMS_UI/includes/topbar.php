<?php
// includes/topbar.php - Reusable Topbar with Accessibility Font-Size Controller
$currentUser = current_user();
?>
<header class="topbar">
  <a href="index.php" class="topbar-brand">
    <div class="topbar-brand-icon">
      <i class="fa-solid fa-mosque"></i>
    </div>
    <div class="topbar-brand-text">
      <h1>MEVMS <span>Portal</span></h1>
      <p>Masjid Kampung Rinching Hulu</p>
    </div>
  </a>

  <div class="topbar-actions">
    <!-- Accessibility Font-Size Controller (Tuan Haji Hambali / Senior A11y Requirement) -->
    <div class="a11y-control" title="Kawalan Saiz Fon Kandungan">
      <span class="a11y-label"><i class="fa-solid fa-text-height"></i> Saiz:</span>
      <button type="button" class="font-btn active" id="font-btn-normal" onclick="setFontSize('normal')" title="Saiz Biasa">A</button>
      <button type="button" class="font-btn" id="font-btn-large" onclick="setFontSize('large')" title="Saiz Besar (+15%)">A+</button>
      <button type="button" class="font-btn" id="font-btn-senior" onclick="setFontSize('senior')" title="Mod Warga Emas (+30%)">A++</button>
    </div>

    <div class="topbar-user">
      <div class="topbar-user-avatar">
        <i class="fa-solid fa-user"></i>
      </div>
      <div>
        <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
        <small style="display:block;font-size:10.5px;color:var(--text-muted);font-weight:600;line-height:1;">
          <?php echo htmlspecialchars($currentUser['role']); ?>
        </small>
      </div>
    </div>
  </div>
</header>

<script>
  // Accessibility Font Size Persistence Engine
  function setFontSize(mode) {
    document.documentElement.classList.remove('font-normal', 'font-large', 'font-senior');
    document.querySelectorAll('.font-btn').forEach(btn => btn.classList.remove('active'));

    let size = '16px';

    if (mode === 'large') {
        document.documentElement.classList.add('font-large');
        size = '19px';
        const b = document.getElementById('font-btn-large');
        if (b) b.classList.add('active');
    } else if (mode === 'senior') {
        document.documentElement.classList.add('font-senior');
        size = '23px';
        const b = document.getElementById('font-btn-senior');
        if (b) b.classList.add('active');
    } else {
        document.documentElement.classList.add('font-normal');
        const b = document.getElementById('font-btn-normal');
        if (b) b.classList.add('active');
    }

    document.body.style.fontSize = size;

    try {
        localStorage.setItem('mevmsFontSize', mode);
    } catch (e) {}
  }
  // Restore preferred font size on page load
  (function() {
    try {
      const saved = localStorage.getItem('mevms_font_size') || 'normal';
      setFontSize(saved);
    } catch(e) {}
  })();
</script>
