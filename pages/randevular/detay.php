<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$DURUM_ETIKET = [
    'bekliyor'   => 'Beklemede',
    'onaylandi'  => 'Onaylandı',
    'tamamlandi' => 'Tamamlandı',
    'iptal'      => 'İptal Edildi',
];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT r.*, sal.ad AS salon_adi, sa.saat, p.ad AS personel_adi, p.soyad AS personel_soyad
    FROM randevular r
    JOIN salonlar sal ON r.salon_id = sal.id
    JOIN saatler sa ON r.saat_id = sa.id
    JOIN personeller p ON r.personel_id = p.id
    WHERE r.id = :id
");
$stmt->execute(['id' => $id]);
$randevu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$randevu) {
    $_SESSION['hata'] = 'Randevu bulunamadı.';
    header('Location: randevular.php');
    exit;
}

$durum_key = $randevu['durum'];
$etiket = $DURUM_ETIKET[$durum_key] ?? ucfirst($durum_key);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Randevu Detayı | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css">
</head>
<body>

<div class="layout">
  <?php include '../../includes/sidebar.php'; ?>

  <main class="content">
    <a href="randevular.php" class="geri-link">← Randevular listesine dön</a>

    <section class="panel">
      <div class="detay-ust">
        <div>
          <h2><?php echo htmlspecialchars($randevu['gelin_adi'] . ' ' . $randevu['gelin_soyad']); ?> &nbsp;&amp;&nbsp; <?php echo htmlspecialchars($randevu['damat_adi'] . ' ' . $randevu['damat_soyad']); ?></h2>
          <p>Randevu No: #<?php echo $randevu['id']; ?> &nbsp;·&nbsp; <span class="badge badge-<?php echo htmlspecialchars($durum_key); ?>"><?php echo htmlspecialchars($etiket); ?></span></p>
        </div>
        <div class="detay-aksiyonlar">
          <a href="duzenle.php?id=<?php echo $randevu['id']; ?>" class="btn-ikincil">✏️ Düzenle</a>
          <?php if ($durum_key !== 'iptal'): ?>
            <button type="button" class="btn-tehlike" onclick="randevuIptalEt(<?php echo $randevu['id']; ?>, this)">🗑️ İptal Et</button>
          <?php endif; ?>
        </div>
      </div>

      <div class="detay-grid">
        <div class="detay-blok">
          <div class="detay-blok-baslik">Gelin Bilgileri</div>
          <div class="detay-satir"><span>Ad Soyad</span><span><?php echo htmlspecialchars($randevu['gelin_adi'] . ' ' . $randevu['gelin_soyad']); ?></span></div>
          <div class="detay-satir"><span>TC Kimlik No</span><span><?php echo htmlspecialchars($randevu['gelin_TC']); ?></span></div>
          <div class="detay-satir"><span>Telefon</span><span><?php echo htmlspecialchars($randevu['gelin_tel']); ?></span></div>
        </div>
        <div class="detay-blok">
          <div class="detay-blok-baslik">Damat Bilgileri</div>
          <div class="detay-satir"><span>Ad Soyad</span><span><?php echo htmlspecialchars($randevu['damat_adi'] . ' ' . $randevu['damat_soyad']); ?></span></div>
          <div class="detay-satir"><span>TC Kimlik No</span><span><?php echo htmlspecialchars($randevu['damat_TC']); ?></span></div>
          <div class="detay-satir"><span>Telefon</span><span><?php echo htmlspecialchars($randevu['damat_tel']); ?></span></div>
        </div>
        <div class="detay-blok">
          <div class="detay-blok-baslik">Randevu Bilgileri</div>
          <div class="detay-satir"><span>Salon</span><span><?php echo htmlspecialchars($randevu['salon_adi']); ?></span></div>
          <div class="detay-satir"><span>Tarih</span><span><?php echo htmlspecialchars(date('d.m.Y', strtotime($randevu['tarih']))); ?></span></div>
          <div class="detay-satir"><span>Saat</span><span><?php echo htmlspecialchars(substr($randevu['saat'], 0, 5)); ?></span></div>
          <div class="detay-satir"><span>İlgili Memur</span><span><?php echo htmlspecialchars($randevu['personel_adi'] . ' ' . $randevu['personel_soyad']); ?></span></div>
        </div>
        <div class="detay-blok">
          <div class="detay-blok-baslik">Ödeme &amp; Kayıt</div>
          <div class="detay-satir"><span>Ödeme Durumu</span><span><?php echo htmlspecialchars(ucfirst($randevu['odeme_durumu'])); ?></span></div>
          <div class="detay-satir"><span>Tutar</span><span><?php echo number_format((float) $randevu['odeme_tutari'], 2, ',', '.'); ?> ₺</span></div>
          <?php if ($durum_key === 'iptal' && $randevu['iptal_nedeni'] !== ''): ?>
            <div class="detay-satir"><span>İptal Nedeni</span><span><?php echo htmlspecialchars($randevu['iptal_nedeni']); ?></span></div>
          <?php endif; ?>
          <div class="detay-satir"><span>Oluşturma</span><span><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($randevu['olusturma_tarihi']))); ?></span></div>
          <div class="detay-satir"><span>Son Güncelleme</span><span><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($randevu['guncelleme_tarihi']))); ?></span></div>
        </div>
      </div>
    </section>
  </main>
</div>

<script>
function randevuIptalEt(id, btn) {
  if (!confirm('Bu randevuyu iptal etmek istediğine emin misin? Bu işlem geri alınamaz.')) return;

  btn.disabled = true;
  fetch('../../actions/randevu_iptal.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(id)
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Hata: ' + data.message);
        btn.disabled = false;
      }
    })
    .catch(() => {
      alert('İşlem sırasında bir bağlantı hatası oluştu.');
      btn.disabled = false;
    });
}
</script>

</body>
</html>