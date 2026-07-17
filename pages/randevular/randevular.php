<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php'; // $pdo burada tanımlı olmalı

// --- Durum etiketleri ve renk sınıfları (tek yerden yönetiliyor) ---
$DURUM_ETIKET = [
    'bekliyor'   => 'Beklemede',
    'onaylandi'  => 'Onaylandı',
    'tamamlandi' => 'Tamamlandı',
    'iptal'      => 'İptal Edildi',
];

// --- Flash mesajları (ekleme/silme işleminden sonra actions/ dosyaları set ediyor) ---
$basari_mesaji = $_SESSION['basari'] ?? null;
$hata_mesaji    = $_SESSION['hata'] ?? null;
unset($_SESSION['basari'], $_SESSION['hata']);

// --- Sayfalama ---
$sayfa_basi = 8;
$sayfa = isset($_GET['sayfa']) ? max(1, (int) $_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_basi;

// --- İstatistik kartları ---
$toplam_randevu = (int) $pdo->query("SELECT COUNT(*) FROM randevular")->fetchColumn();

$bu_ayki_randevu = (int) $pdo->query(
    "SELECT COUNT(*) FROM randevular WHERE MONTH(tarih) = MONTH(CURDATE()) AND YEAR(tarih) = YEAR(CURDATE())"
)->fetchColumn();

$bugunku_randevu = (int) $pdo->query(
    "SELECT COUNT(*) FROM randevular WHERE tarih = CURDATE()"
)->fetchColumn();

$aktif_personel = (int) $pdo->query(
    "SELECT COUNT(*) FROM personeller WHERE aktif = 1"
)->fetchColumn();

// --- Randevu listesi (sayfalı) ---
$stmt = $pdo->prepare("
    SELECT r.id, r.gelin_adi, r.gelin_soyad, r.damat_adi, r.damat_soyad,
           r.tarih, r.durum, sal.ad AS salon_adi, sa.saat
    FROM randevular r
    JOIN salonlar sal ON r.salon_id = sal.id
    JOIN saatler sa ON r.saat_id = sa.id
    ORDER BY r.tarih DESC, sa.saat DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $sayfa_basi, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toplam_sayfa = max(1, (int) ceil($toplam_randevu / $sayfa_basi));

// --- Form için gerekli seçenekler ---
$salonlar = $pdo->query("SELECT id, ad FROM salonlar WHERE aktif = 1 ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$personeller = $pdo->query("SELECT id, ad, soyad FROM personeller WHERE aktif = 1 ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$saatler = $pdo->query("SELECT id, saat FROM saatler ORDER BY saat ASC")->fetchAll(PDO::FETCH_ASSOC);

$form_hazir = count($salonlar) > 0 && count($personeller) > 0 && count($saatler) > 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Randevular | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css">
</head>
<body>

<div class="layout">
  <?php include '../../includes/sidebar.php'; ?>

  <main class="content">
    <div class="topbar">
      <h1>Randevular</h1>
      <div class="topbar-user">
        Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['ad'] ?? ''); ?>
        <span class="avatar-circle">👤</span>
      </div>
    </div>

    <?php if ($basari_mesaji): ?>
      <div class="flash flash-basari"><?php echo htmlspecialchars($basari_mesaji); ?></div>
    <?php endif; ?>
    <?php if ($hata_mesaji): ?>
      <div class="flash flash-hata"><?php echo htmlspecialchars($hata_mesaji); ?></div>
    <?php endif; ?>

    <?php if (!$form_hazir): ?>
      <div class="uyari-kutu">
        Yeni randevu ekleyebilmek için önce şunların hazır olması gerekiyor:
        <?php if (count($salonlar) === 0): ?> en az bir <a href="../salonlar/salonlar.php">aktif salon</a>,<?php endif; ?>
        <?php if (count($personeller) === 0): ?> en az bir aktif personel,<?php endif; ?>
        <?php if (count($saatler) === 0): ?> <code>saatler</code> tablosunda en az bir saat kaydı<?php endif; ?>.
        Bunlar tamamlanana kadar formdaki "Kaydet" butonu pasif olacak.
      </div>
    <?php endif; ?>

    <section class="cards">
      <div class="card">
        <div class="card-icon mor">📅</div>
        <div>
          <div class="card-value"><?php echo $toplam_randevu; ?></div>
          <div class="card-label">Toplam Randevu</div>
        </div>
      </div>
      <div class="card">
        <div class="card-icon yesil">📆</div>
        <div>
          <div class="card-value"><?php echo $bu_ayki_randevu; ?></div>
          <div class="card-label">Bu Ayki Randevu</div>
        </div>
      </div>
      <div class="card">
        <div class="card-icon mavi">⏰</div>
        <div>
          <div class="card-value"><?php echo $bugunku_randevu; ?></div>
          <div class="card-label">Bugünkü Randevu</div>
        </div>
      </div>
      <div class="card">
        <div class="card-icon pembe">👥</div>
        <div>
          <div class="card-value"><?php echo $aktif_personel; ?></div>
          <div class="card-label">Aktif Personel</div>
        </div>
      </div>
    </section>

    <div class="randevu-grid">

      <!-- SOL: RANDEVU LİSTESİ -->
      <section class="panel">
        <div class="panel-header">
          <h2>Randevu Listesi</h2>
          <a href="#yeni-randevu" class="btn-yeni" id="yeniRandevuBtn">+ Yeni Randevu</a>
        </div>
        <div class="panel-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Gelin</th>
                <th>Damat</th>
                <th>Salon</th>
                <th>Tarih</th>
                <th>Durum</th>
                <th>İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($randevular) === 0): ?>
                <tr><td colspan="6" class="bos-durum">Henüz randevu kaydı yok. Sağdaki formdan ilk randevuyu ekleyebilirsin.</td></tr>
              <?php else: ?>
                <?php foreach ($randevular as $r): ?>
                  <?php
                    $durum_key = $r['durum'];
                    $etiket = $DURUM_ETIKET[$durum_key] ?? ucfirst($durum_key);
                  ?>
                  <tr>
                    <td class="ad-soyad"><?php echo htmlspecialchars($r['gelin_adi'] . ' ' . $r['gelin_soyad']); ?></td>
                    <td class="ad-soyad"><?php echo htmlspecialchars($r['damat_adi'] . ' ' . $r['damat_soyad']); ?></td>
                    <td><?php echo htmlspecialchars($r['salon_adi']); ?></td>
                    <td>
                      <?php echo htmlspecialchars(date('d.m.Y', strtotime($r['tarih']))); ?>
                      <span class="saat-alt"><?php echo htmlspecialchars(substr($r['saat'], 0, 5)); ?></span>
                    </td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($durum_key); ?>"><?php echo htmlspecialchars($etiket); ?></span></td>
                    <td>
                      <a class="islem-btn islem-goruntule" href="detay.php?id=<?php echo $r['id']; ?>" title="Detay">👁️</a>
                      <?php if ($durum_key !== 'iptal'): ?>
                        <button type="button" class="islem-btn islem-sil" title="İptal Et"
                                onclick="randevuIptalEt(<?php echo $r['id']; ?>, this)">🗑️</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <div class="sayfalama-satiri">
            <span>Toplam <?php echo $toplam_randevu; ?> kayıttan
              <?php echo $toplam_randevu === 0 ? 0 : $offset + 1; ?>–<?php echo min($offset + $sayfa_basi, $toplam_randevu); ?> arası gösteriliyor.</span>
            <div class="sayfalama">
              <a class="<?php echo $sayfa <= 1 ? 'devre-disi' : ''; ?>" href="?sayfa=<?php echo max(1, $sayfa - 1); ?>">‹</a>
              <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <a class="<?php echo $i === $sayfa ? 'aktif' : ''; ?>" href="?sayfa=<?php echo $i; ?>"><?php echo $i; ?></a>
              <?php endfor; ?>
              <a class="<?php echo $sayfa >= $toplam_sayfa ? 'devre-disi' : ''; ?>" href="?sayfa=<?php echo min($toplam_sayfa, $sayfa + 1); ?>">›</a>
            </div>
          </div>
        </div>
      </section>

      <!-- SAĞ: YENİ RANDEVU EKLE -->
      <section class="panel" id="yeni-randevu">
        <div class="panel-header">
          <h2>Yeni Randevu Ekle</h2>
        </div>
        <div class="panel-body dolgu">
          <form action="../../actions/randevu_ekle.php" method="POST">

            <div class="form-bolum">
              <div class="form-bolum-baslik">Gelin Bilgileri</div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="gelin_adi">Adı</label>
                  <input type="text" id="gelin_adi" name="gelin_adi" required maxlength="50">
                </div>
                <div class="form-grup">
                  <label for="gelin_soyad">Soyadı</label>
                  <input type="text" id="gelin_soyad" name="gelin_soyad" required maxlength="50">
                </div>
              </div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="gelin_TC">TC Kimlik No</label>
                  <input type="text" id="gelin_TC" name="gelin_TC" required maxlength="11" pattern="\d{11}" title="11 haneli TC kimlik numarası">
                </div>
                <div class="form-grup">
                  <label for="gelin_tel">Telefon</label>
                  <input type="text" id="gelin_tel" name="gelin_tel" required maxlength="15" placeholder="05xx xxx xx xx">
                </div>
              </div>
            </div>

            <div class="form-bolum">
              <div class="form-bolum-baslik">Damat Bilgileri</div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="damat_adi">Adı</label>
                  <input type="text" id="damat_adi" name="damat_adi" required maxlength="50">
                </div>
                <div class="form-grup">
                  <label for="damat_soyad">Soyadı</label>
                  <input type="text" id="damat_soyad" name="damat_soyad" required maxlength="50">
                </div>
              </div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="damat_TC">TC Kimlik No</label>
                  <input type="text" id="damat_TC" name="damat_TC" required maxlength="11" pattern="\d{11}" title="11 haneli TC kimlik numarası">
                </div>
                <div class="form-grup">
                  <label for="damat_tel">Telefon</label>
                  <input type="text" id="damat_tel" name="damat_tel" required maxlength="15" placeholder="05xx xxx xx xx">
                </div>
              </div>
            </div>

            <div class="form-bolum">
              <div class="form-bolum-baslik">Randevu Bilgileri</div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="salon_id">Salon</label>
                  <select id="salon_id" name="salon_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($salonlar as $s): ?>
                      <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['ad']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-grup">
                  <label for="personel_id">Memur</label>
                  <select id="personel_id" name="personel_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($personeller as $p): ?>
                      <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['ad'] . ' ' . $p['soyad']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="tarih">Tarih</label>
                  <input type="date" id="tarih" name="tarih" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-grup">
                  <label for="saat_id">Saat</label>
                  <select id="saat_id" name="saat_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($saatler as $sa): ?>
                      <option value="<?php echo $sa['id']; ?>"><?php echo htmlspecialchars(substr($sa['saat'], 0, 5)); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="durum">Durum</label>
                  <select id="durum" name="durum" required>
                    <option value="onaylandi">Onaylandı</option>
                    <option value="bekliyor">Beklemede</option>
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
                    <option value="ödenmedi">Ödenmedi</option>
                    <option value="ödendi">Ödendi</option>
                  </select>
                </div>
                <div class="form-grup">
                  <label for="odeme_tutari">Tutar (₺)</label>
                  <input type="number" id="odeme_tutari" name="odeme_tutari" step="0.01" min="0" value="0" required>
                </div>
              </div>
            </div>

            <button type="submit" class="btn-kaydet" <?php echo $form_hazir ? '' : 'disabled'; ?>>💾 Kaydet</button>
          </form>
        </div>
      </section>

    </div>
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

document.getElementById('yeniRandevuBtn').addEventListener('click', function (e) {
  e.preventDefault();
  document.getElementById('yeni-randevu').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>

</body>
</html>