<?php
$aktif_sayfa = basename($_SERVER['PHP_SELF']);
$aktif_klasor = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../../assets/img/logo.png" alt="Logo">
    <span>Nikah İşleri</span>
  </div>

  <nav class="sidebar-menu">
    <a href="../dashboard/dashboard.php" class="<?php echo $aktif_klasor === 'dashboard' ? 'active' : ''; ?>">
      <span class="icon">🏠</span> Anasayfa
    </a>
    <a href="../randevular/randevular.php" class="<?php echo $aktif_klasor === 'randevular' ? 'active' : ''; ?>">
      <span class="icon">📅</span> Randevular
    </a>
    <a href="../salonlar/salonlar.php" class="<?php echo $aktif_klasor === 'salonlar' ? 'active' : ''; ?>">
      <span class="icon">🏛️</span> Salonlar
    </a>
    <a href="../personeller/personeller.php" class="<?php echo $aktif_klasor === 'personeller' ? 'active' : ''; ?>">
      <span class="icon">👥</span> Personel
    </a>
    <a href="../raporlar/raporlar.php" class="<?php echo $aktif_klasor === 'raporlar' ? 'active' : ''; ?>">
      <span class="icon">📊</span> Raporlar
    </a>
    <a href="../resmi_tatiller/resmi_tatiller.php" class="<?php echo $aktif_klasor === 'resmi_tatiller' ? 'active' : ''; ?>">
      <span class="icon">🎌</span> Resmi Tatiller
    </a>
    <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
    <a href="../loglar/loglar.php" class="<?php echo $aktif_klasor === 'loglar' ? 'active' : ''; ?>">
      <span class="icon">🗂️</span> Loglar
    </a>
    <a href="../ayarlar/ayarlar.php" class="<?php echo $aktif_klasor === 'ayarlar' ? 'active' : ''; ?>">
      <span class="icon">⚙️</span> Ayarlar
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-user">
    <?php echo htmlspecialchars(($_SESSION['ad'] ?? '') . ' ' . ($_SESSION['soyad'] ?? '')); ?>
    <span class="rol-badge"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></span>
  </div>

  <a href="../../auth/logout.php" class="sidebar-logout">
    <span class="icon">🚪</span> Çıkış Yap
  </a>
</aside>