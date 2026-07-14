<?php
session_start();
// Zaten giriş yapmışsa doğrudan dashboard'a yönlendir
if (isset($_SESSION['personel_id'])) {
    header('Location: ../pages/dashboard/dashboard.php');
    exit;
}
$hata = $_SESSION['giris_hata'] ?? '';
unset($_SESSION['giris_hata']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nikah İşleri Müdürlüğü | Giriş</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

  <div class="bg-photo"></div>

  <div class="login-wrapper">
    <div class="card">

      <img class="logo" src="../assets/img/logo.png" alt="Tarsus Belediyesi Logosu">

      <div class="org-name">Nikah İşleri Müdürlüğü</div>
      <div class="org-sub">Randevu Yönetim Sistemi</div>

      <div class="welcome-block">
        <div class="welcome-title">Hoş geldiniz!</div>
        <div class="welcome-desc">Devam etmek için lütfen giriş yapın.</div>
      </div>

      <?php if ($hata): ?>
        <div class="hata-mesaji"><?php echo htmlspecialchars($hata); ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate>
        <div class="field">
          <input type="text" id="username" name="kullanici_adi" placeholder="Kullanıcı adı" autocomplete="username" required>
        </div>

        <div class="field">
          <input type="password" id="password" name="sifre" placeholder="Şifre" autocomplete="current-password" required>
        </div>

        <div class="row-between">
          <label class="remember">
            <input type="checkbox" name="beni_hatirla" checked>
            Beni hatırla
          </label>
        </div>

        <button type="submit" class="btn-primary">Giriş Yap</button>
      </form>

    </div>

    <div class="footer-note">© 2025 Tarsus Belediyesi - Tüm hakları saklıdır.</div>
  </div>

</body>
</html>