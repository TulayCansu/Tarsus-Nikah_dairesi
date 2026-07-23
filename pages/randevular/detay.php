<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$DURUM_ETIKET = [
    'bekliyor'   => 'Beklemede',
    'onaylandi'  => 'Onaylandı',
    'tamamlandi' => 'Tamamlandı',
    'iptal'      => 'İptal Edildi',
];

// --- Flash mesajları (iptal/güncelleme işlemlerinden sonra gösterilir) ---
$basari_mesaji = $_SESSION['basari'] ?? null;
$hata_mesaji    = $_SESSION['hata'] ?? null;
unset($_SESSION['basari'], $_SESSION['hata']);

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
<link rel="stylesheet" href="../../assets/css/randevular.css?v=7">

<!-- Ekran sıkışıklığını çözen ve Yazdırma Düzenini sağlayan Stiller -->
<style>
  .detay-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 24px !important;
    padding: 24px !important;
  }

  .detay-blok {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }

  .detay-blok-baslik {
    font-size: 13px !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #14325f !important;
    margin-bottom: 14px !important;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
  }

  .detay-satir {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 16px !important;
    padding: 9px 0 !important;
    border-bottom: 1px dashed #e2e8f0 !important;
    font-size: 14px !important;
  }

  .detay-satir:last-child {
    border-bottom: none !important;
  }

  .detay-satir span:first-child {
    color: #64748b !important;
    font-weight: 500;
    white-space: nowrap !important;
    flex-shrink: 0;
  }

  .detay-satir span:last-child {
    font-weight: 600 !important;
    color: #0e264a !important;
    text-align: right !important;
    word-break: break-word !important;
  }

  @media (max-width: 800px) {
    .detay-grid {
      grid-template-columns: 1fr !important;
    }
  }

  /* =========================================================
     YAZDIRMA (PRINT) CSS KURALLARI
     Yazıcıdan çıktı alınırken gereksiz butonları ve menüyü gizler.
     ========================================================= */
  @media print {
    body {
      background: #fff !important;
      color: #000 !important;
    }

    .sidebar, 
    .geri-link, 
    .detay-aksiyonlar,
    .flash {
      display: none !important; /* Sol menü, geri linki ve butonlar kağıtta çıkmaz */
    }

    .content {
      margin-left: 0 !important;
      padding: 0 !important;
    }

    .panel {
      box-shadow: none !important;
      border: 1px solid #ccc !important;
    }

    .detay-grid {
      grid-template-columns: 1fr 1fr !important;
      padding: 15px !important;
      gap: 15px !important;
    }

    .detay-blok {
      border: 1px solid #ddd !important;
      background: #fff !important;
    }
  }
</style>
</head>
<body>

<div class="layout">
  <?php include '../../includes/sidebar.php'; ?>

  <main class="content">
    <a href="randevular.php" class="geri-link">← Randevular listesine dön</a>

    <?php if ($basari_mesaji): ?>
      <div class="flash flash-basari"><?php echo htmlspecialchars($basari_mesaji); ?></div>
    <?php endif; ?>
    <?php if ($hata_mesaji): ?>
      <div class="flash flash-hata"><?php echo htmlspecialchars($hata_mesaji); ?></div>
    <?php endif; ?>

    <section class="panel">
      <div class="detay-ust">
        <div>
          <h2><?php echo htmlspecialchars($randevu['gelin_adi'] . ' ' . $randevu['gelin_soyad']); ?> &nbsp;&amp;&nbsp; <?php echo htmlspecialchars($randevu['damat_adi'] . ' ' . $randevu['damat_soyad']); ?></h2>
          <p>Randevu No: #<?php echo $randevu['id']; ?> &nbsp;·&nbsp; <span class="badge badge-<?php echo htmlspecialchars($durum_key); ?>"><?php echo htmlspecialchars($etiket); ?></span></p>
        </div>
        <div class="detay-aksiyonlar">
          <!-- Yazdır Butonu -->
          <button type="button" class="btn-ikincil" onclick="window.print()">🖨️ Yazdır</button>

          <?php if ($durum_key !== 'iptal'): ?>
            <a href="duzenle.php?id=<?php echo $randevu['id']; ?>" class="btn-ikincil">✏️ Düzenle</a>
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