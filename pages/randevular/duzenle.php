<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM randevular WHERE id = :id");
$stmt->execute(['id' => $id]);
$randevu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$randevu) {
    $_SESSION['hata'] = 'Randevu bulunamadı.';
    header('Location: randevular.php');
    exit;
}

$salonlar = $pdo->query("SELECT id, ad FROM salonlar WHERE aktif = 1 OR id = " . (int) $randevu['salon_id'] . " ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$personeller = $pdo->query("SELECT id, ad, soyad FROM personeller WHERE aktif = 1 OR id = " . (int) $randevu['personel_id'] . " ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$saatler = $pdo->query("SELECT id, saat FROM saatler ORDER BY saat ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Randevu Düzenle | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css">
</head>
<body>

<div class="layout">
  <?php include '../../includes/sidebar.php'; ?>

  <main class="content">
    <a href="detay.php?id=<?php echo $randevu['id']; ?>" class="geri-link">← Randevu detayına dön</a>

    <section class="panel" style="max-width:760px;">
      <div class="panel-header">
        <h2>Randevu Düzenle — #<?php echo $randevu['id']; ?></h2>
      </div>
      <div class="panel-body dolgu">
        <form action="../../actions/randevu_guncelle.php" method="POST">
          <input type="hidden" name="id" value="<?php echo $randevu['id']; ?>">

          <div class="form-bolum">
            <div class="form-bolum-baslik">Gelin Bilgileri</div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="gelin_adi">Adı</label>
                <input type="text" id="gelin_adi" name="gelin_adi" required maxlength="50" value="<?php echo htmlspecialchars($randevu['gelin_adi']); ?>">
              </div>
              <div class="form-grup">
                <label for="gelin_soyad">Soyadı</label>
                <input type="text" id="gelin_soyad" name="gelin_soyad" required maxlength="50" value="<?php echo htmlspecialchars($randevu['gelin_soyad']); ?>">
              </div>
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="gelin_TC">TC Kimlik No</label>
                <input type="text" id="gelin_TC" name="gelin_TC" required maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($randevu['gelin_TC']); ?>">
              </div>
              <div class="form-grup">
                <label for="gelin_tel">Telefon</label>
                <input type="text" id="gelin_tel" name="gelin_tel" required maxlength="15" value="<?php echo htmlspecialchars($randevu['gelin_tel']); ?>">
              </div>
            </div>
          </div>

          <div class="form-bolum">
            <div class="form-bolum-baslik">Damat Bilgileri</div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="damat_adi">Adı</label>
                <input type="text" id="damat_adi" name="damat_adi" required maxlength="50" value="<?php echo htmlspecialchars($randevu['damat_adi']); ?>">
              </div>
              <div class="form-grup">
                <label for="damat_soyad">Soyadı</label>
                <input type="text" id="damat_soyad" name="damat_soyad" required maxlength="50" value="<?php echo htmlspecialchars($randevu['damat_soyad']); ?>">
              </div>
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="damat_TC">TC Kimlik No</label>
                <input type="text" id="damat_TC" name="damat_TC" required maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($randevu['damat_TC']); ?>">
              </div>
              <div class="form-grup">
                <label for="damat_tel">Telefon</label>
                <input type="text" id="damat_tel" name="damat_tel" required maxlength="15" value="<?php echo htmlspecialchars($randevu['damat_tel']); ?>">
              </div>
            </div>
          </div>

          <div class="form-bolum">
            <div class="form-bolum-baslik">Randevu Bilgileri</div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="salon_id">Salon</label>
                <select id="salon_id" name="salon_id" required>
                  <?php foreach ($salonlar as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $randevu['salon_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['ad']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-grup">
                <label for="personel_id">Memur</label>
                <select id="personel_id" name="personel_id" required>
                  <?php foreach ($personeller as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $randevu['personel_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ad'] . ' ' . $p['soyad']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="tarih">Tarih</label>
                <input type="date" id="tarih" name="tarih" required value="<?php echo htmlspecialchars($randevu['tarih']); ?>">
              </div>
              <div class="form-grup">
                <label for="saat_id">Saat</label>
                <select id="saat_id" name="saat_id" required>
                  <?php foreach ($saatler as $sa): ?>
                    <option value="<?php echo $sa['id']; ?>" <?php echo $sa['id'] == $randevu['saat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(substr($sa['saat'], 0, 5)); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="durum">Durum</label>
                <select id="durum" name="durum" required>
                  <?php
                  $durumlar = ['bekliyor' => 'Beklemede', 'onaylandi' => 'Onaylandı', 'tamamlandi' => 'Tamamlandı', 'iptal' => 'İptal Edildi'];
                  foreach ($durumlar as $key => $label):
                  ?>
                    <option value="<?php echo $key; ?>" <?php echo $key === $randevu['durum'] ? 'selected' : ''; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-grup"></div>
            </div>
          </div>

          <div class="form-bolum">
            <div class="form-bolum-baslik">Ödeme</div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="odeme_durumu">Ödeme Durumu</label>
                <select id="odeme_durumu" name="odeme_durumu" required>
                  <option value="ödenmedi" <?php echo $randevu['odeme_durumu'] === 'ödenmedi' ? 'selected' : ''; ?>>Ödenmedi</option>
                  <option value="ödendi" <?php echo $randevu['odeme_durumu'] === 'ödendi' ? 'selected' : ''; ?>>Ödendi</option>
                </select>
              </div>
              <div class="form-grup">
                <label for="odeme_tutari">Tutar (₺)</label>
                <input type="number" id="odeme_tutari" name="odeme_tutari" step="0.01" min="0" required value="<?php echo htmlspecialchars($randevu['odeme_tutari']); ?>">
              </div>
            </div>
          </div>

          <button type="submit" class="btn-kaydet">💾 Değişiklikleri Kaydet</button>
        </form>
      </div>
    </section>
  </main>
</div>

</body>
</html>