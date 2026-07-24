<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php'; //

$rol = $_SESSION['rol'] ?? '';

$DURUM_ETIKET = [
    'bekliyor'   => 'Beklemede',
    'onaylandi'  => 'Onaylandı',
    'tamamlandi' => 'Tamamlandı',
    'iptal'      => 'İptal Edildi',
];

if ($rol === 'nikah_memuru') {

    // --- PERSONEL: sadece kendi bugünkü programı ---
    $stmt = $pdo->prepare("
        SELECT r.gelin_adi, r.gelin_soyad, r.damat_adi, r.damat_soyad,
               s.saat, sal.ad AS salon_adi, r.adres, r.durum
        FROM randevular r
        JOIN saatler s ON r.saat_id = s.id
        LEFT JOIN salonlar sal ON r.salon_id = sal.id
        WHERE r.personel_id = :pid AND r.tarih = CURDATE()
        ORDER BY s.saat ASC
    ");
    $stmt->execute(['pid' => $_SESSION['personel_id']]);
    $bugunku_program = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bugunku_toplam = count($bugunku_program);
    $iptal_sayisi = 0;
    foreach ($bugunku_program as $p) {
        if ($p['durum'] === 'iptal') $iptal_sayisi++;
    }
    $planlanan_sayisi = $bugunku_toplam - $iptal_sayisi;

} else {

    // --- ADMIN: genel istatistikler ---
    $stmt = $pdo->query("SELECT COUNT(*) FROM randevular WHERE tarih = CURDATE()");
    $bugunku_randevu = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'tamamlandi' AND MONTH(tarih) = MONTH(CURDATE()) AND YEAR(tarih) = YEAR(CURDATE())");
    $bu_ay_nikah = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'bekliyor'");
    $bekleyen_randevu = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM personeller WHERE aktif = 1");
    $toplam_personel = $stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT r.gelin_adi, r.gelin_soyad, r.damat_adi, r.damat_soyad,
               r.tarih, s.saat, sal.ad AS salon_adi, r.durum, r.odeme_durumu
        FROM randevular r
        JOIN saatler s ON r.saat_id = s.id
        JOIN salonlar sal ON r.salon_id = sal.id
        ORDER BY r.tarih DESC, s.saat DESC
        LIMIT 5
    ");
    $son_randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Anasayfa | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>

<div class="layout">
  <?php include '../../includes/sidebar.php'; ?>

  <main class="content">

    <?php if ($rol === 'nikah_memuru'): ?>

      <div class="topbar">
        <h1>Bugünüm</h1>
        <div class="topbar-user"><?php echo htmlspecialchars(date('d.m.Y')); ?></div>
      </div>

      <section class="cards">
        <div class="card">
          <div class="card-icon">📅</div>
          <div class="card-value"><?php echo $bugunku_toplam; ?></div>
          <div class="card-label">Bugünkü Nikah</div>
        </div>
        <div class="card">
          <div class="card-icon">✅</div>
          <div class="card-value"><?php echo $planlanan_sayisi; ?></div>
          <div class="card-label">Planlanan</div>
        </div>
        <div class="card">
          <div class="card-icon">❌</div>
          <div class="card-value"><?php echo $iptal_sayisi; ?></div>
          <div class="card-label">İptal Edilen</div>
        </div>
      </section>

      <section class="table-section">
        <h2>Bugünkü Programım</h2>

        <?php if ($bugunku_toplam === 0): ?>
          <div class="program-bos">Bugün için atanmış bir nikah bulunmuyor.</div>
        <?php else: ?>
          <div class="gunluk-program">
            <?php foreach ($bugunku_program as $p): ?>
              <?php
                $iptal_mi = $p['durum'] === 'iptal';
                $yer = $p['salon_adi'] !== null ? $p['salon_adi'] : $p['adres'];
              ?>
              <div class="program-item <?php echo $iptal_mi ? 'iptal' : ''; ?>">
                <div class="program-saat"><?php echo htmlspecialchars(substr($p['saat'], 0, 5)); ?></div>
                <div class="program-bilgi">
                  <div class="program-isim">
                    <?php echo htmlspecialchars($p['gelin_adi'] . ' ' . $p['gelin_soyad'] . ' & ' . $p['damat_adi'] . ' ' . $p['damat_soyad']); ?>
                  </div>
                  <div class="program-yer">📍 <?php echo htmlspecialchars($yer); ?></div>
                </div>
                <span class="badge badge-<?php echo htmlspecialchars($p['durum']); ?>">
                  <?php echo htmlspecialchars($DURUM_ETIKET[$p['durum']] ?? $p['durum']); ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    <?php else: ?>

      <div class="topbar">
        <h1>Anasayfa</h1>
        <div class="topbar-user">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['ad']); ?></div>
      </div>

      <section class="cards">
        <div class="card">
          <div class="card-icon">📅</div>
          <div class="card-value"><?php echo $bugunku_randevu; ?></div>
          <div class="card-label">Bugünkü Randevu</div>
        </div>
        <div class="card">
          <div class="card-icon">💍</div>
          <div class="card-value"><?php echo $bu_ay_nikah; ?></div>
          <div class="card-label">Bu Ay Tamamlanan Nikah</div>
        </div>
        <div class="card">
          <div class="card-icon">⏳</div>
          <div class="card-value"><?php echo $bekleyen_randevu; ?></div>
          <div class="card-label">Bekleyen Randevu</div>
        </div>
        <div class="card">
          <div class="card-icon">👥</div>
          <div class="card-value"><?php echo $toplam_personel; ?></div>
          <div class="card-label">Aktif Personel</div>
        </div>
      </section>

      <section class="table-section">
        <h2>Son Randevular</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Gelin</th>
              <th>Damat</th>
              <th>Tarih</th>
              <th>Saat</th>
              <th>Salon</th>
              <th>Ödeme</th>
              <th>Durum</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($son_randevular) === 0): ?>
              <tr><td colspan="7">Henüz randevu kaydı yok.</td></tr>
            <?php else: ?>
              <?php foreach ($son_randevular as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['gelin_adi'] . ' ' . $r['gelin_soyad']); ?></td>
                  <td><?php echo htmlspecialchars($r['damat_adi'] . ' ' . $r['damat_soyad']); ?></td>
                  <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($r['tarih']))); ?></td>
                  <td><?php echo htmlspecialchars(substr($r['saat'], 0, 5)); ?></td>
                  <td><?php echo htmlspecialchars($r['salon_adi']); ?></td>
                  <td>
                    <span class="badge <?php echo $r['odeme_durumu'] === 'ödendi' ? 'badge-tamamlandi' : 'badge-bekliyor'; ?>">
                      <?php echo htmlspecialchars($r['odeme_durumu']); ?>
                    </span>
                  </td>
                  <td><span class="badge badge-<?php echo htmlspecialchars($r['durum']); ?>"><?php echo htmlspecialchars($DURUM_ETIKET[$r['durum']] ?? $r['durum']); ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

    <?php endif; ?>

  </main>
</div>

</body>
</html>