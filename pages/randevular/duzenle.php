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

// --- Resmi tatil tarihleri (formda anlık uyarı için) ---
$tatil_sabit = $pdo->query(
    "SELECT DATE_FORMAT(tarih, '%m-%d') AS ay_gun, aciklama FROM resmitatiller WHERE her_yil_tekrar = 1"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$tatil_degisken = $pdo->query(
    "SELECT tarih, aciklama FROM resmitatiller WHERE her_yil_tekrar = 0 OR her_yil_tekrar IS NULL"
)->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Randevu Düzenle | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css?v=6">
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
            <div class="form-satir">
              <div class="form-grup">
                <label for="gelin_dogum_tarihi">Doğum Tarihi</label>
                <input type="date" id="gelin_dogum_tarihi" name="gelin_dogum_tarihi" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                <span class="yas-uyari" id="gelinYasUyari"></span>
              </div>
              <div class="form-grup"></div>
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
            <div class="form-satir">
              <div class="form-grup">
                <label for="damat_dogum_tarihi">Doğum Tarihi</label>
                <input type="date" id="damat_dogum_tarihi" name="damat_dogum_tarihi" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                <span class="yas-uyari" id="damatYasUyari"></span>
              </div>
              <div class="form-grup"></div>
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
                <label for="tarih_goster">Tarih</label>
                <div class="tarih-secici" id="tarihSecici">
                  <input type="text" id="tarih_goster" class="tarih-goster" placeholder="Tarih seçin" autocomplete="off" readonly required>
                  <input type="hidden" id="tarih" name="tarih">
                  <div class="takvim-kutu" id="takvimKutu" style="display:none;">
                    <div class="takvim-header">
                      <button type="button" class="takvim-nav" id="takvimOnceki">‹</button>
                      <span id="takvimBaslik"></span>
                      <button type="button" class="takvim-nav" id="takvimSonraki">›</button>
                    </div>
                    <div class="takvim-gunler-satiri">
                      <span>Pt</span><span>Sa</span><span>Ça</span><span>Pe</span><span>Cu</span><span>Ct</span><span>Pz</span>
                    </div>
                    <div class="takvim-grid" id="takvimGrid"></div>
                    <div class="takvim-lejant">
                      <span><i class="nokta tatil-nokta"></i> Resmi tatil</span>
                      <span><i class="nokta bugun-nokta"></i> Bugün</span>
                    </div>
                  </div>
                </div>
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

<script>
const TATIL_DEGISKEN = <?php echo json_encode($tatil_degisken, JSON_UNESCAPED_UNICODE); ?>; // yıla özel (ör. Ramazan/Kurban Bayramı)
const TATIL_SABIT = <?php echo json_encode($tatil_sabit, JSON_UNESCAPED_UNICODE); ?>; // ay-gün eşleşir, her yıl tekrarlanır
const AY_ADLARI = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

function takvimSeciciBaslat(opts) {
  const gosterInput = document.getElementById(opts.gosterId);
  const gizliInput = document.getElementById(opts.hiddenId);
  const kutu = document.getElementById(opts.kutuId);
  const grid = document.getElementById(opts.gridId);
  const baslik = document.getElementById(opts.baslikId);
  const oncekiBtn = document.getElementById(opts.oncekiId);
  const sonrakiBtn = document.getElementById(opts.sonrakiId);
  if (!gosterInput) return;

  const bugun = new Date();
  const bugunStr = bugun.toISOString().slice(0, 10);

  let baslangic = opts.baslangicTarih ? new Date(opts.baslangicTarih + 'T00:00:00') : bugun;
  let gYil = baslangic.getFullYear();
  let gAy = baslangic.getMonth();

  if (opts.baslangicTarih) {
    gizliInput.value = opts.baslangicTarih;
    gosterInput.value = formatliGoster(opts.baslangicTarih);
  }

  function formatliGoster(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return `${d}.${m}.${y}`;
  }

  function ciz() {
    baslik.textContent = AY_ADLARI[gAy] + ' ' + gYil;
    grid.innerHTML = '';

    const ilkGunIndex = (new Date(gYil, gAy, 1).getDay() + 6) % 7;
    const ayGunSayisi = new Date(gYil, gAy + 1, 0).getDate();

    for (let i = 0; i < ilkGunIndex; i++) {
      const bos = document.createElement('button');
      bos.type = 'button';
      bos.className = 'takvim-gun bos';
      grid.appendChild(bos);
    }

    for (let gun = 1; gun <= ayGunSayisi; gun++) {
      const dateStr = `${gYil}-${String(gAy + 1).padStart(2, '0')}-${String(gun).padStart(2, '0')}`;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'takvim-gun';

      const gunNoSpan = document.createElement('span');
      gunNoSpan.className = 'gun-no';
      gunNoSpan.textContent = gun;
      btn.appendChild(gunNoSpan);

      const ayGun = `${String(gAy + 1).padStart(2, '0')}-${String(gun).padStart(2, '0')}`;
      const tatilAciklama = TATIL_DEGISKEN[dateStr] || TATIL_SABIT[ayGun];
      const haftaGunu = new Date(gYil, gAy, gun).getDay(); // 0=Pazar, 6=Cumartesi
      const haftaSonuMu = haftaGunu === 0 || haftaGunu === 6;
      const gecmisMi = opts.gecmisiEngelle && dateStr < bugunStr;

      if (tatilAciklama) {
        btn.classList.add('tatil');
        btn.title = tatilAciklama;

        const etiket = document.createElement('span');
        etiket.className = 'tatil-etiket';
        etiket.textContent = tatilAciklama;
        btn.appendChild(etiket);
      } else if (haftaSonuMu) {
        btn.classList.add('pasif');
        btn.title = 'Hafta sonu';
      } else if (gecmisMi) {
        btn.classList.add('pasif');
      } else {
        btn.addEventListener('click', () => {
          gizliInput.value = dateStr;
          gosterInput.value = formatliGoster(dateStr);
          kutu.style.display = 'none';
          ciz();
        });
      }

      if (dateStr === bugunStr) btn.classList.add('bugun');
      if (dateStr === gizliInput.value) btn.classList.add('secili');

      grid.appendChild(btn);
    }
  }

  oncekiBtn.addEventListener('click', () => { gAy--; if (gAy < 0) { gAy = 11; gYil--; } ciz(); });
  sonrakiBtn.addEventListener('click', () => { gAy++; if (gAy > 11) { gAy = 0; gYil++; } ciz(); });

  gosterInput.addEventListener('click', () => {
    kutu.style.display = kutu.style.display === 'none' ? 'block' : 'none';
  });

  document.addEventListener('click', (e) => {
    if (!kutu.contains(e.target) && e.target !== gosterInput) {
      kutu.style.display = 'none';
    }
  });

  ciz();
}

takvimSeciciBaslat({
  gosterId: 'tarih_goster', hiddenId: 'tarih', kutuId: 'takvimKutu',
  gridId: 'takvimGrid', baslikId: 'takvimBaslik', oncekiId: 'takvimOnceki', sonrakiId: 'takvimSonraki',
  baslangicTarih: <?php echo json_encode($randevu['tarih']); ?>,
  gecmisiEngelle: false
});

// --- 18 yaş kontrolü (Gelin/Damat) ---
function yasHesapla(dogumTarihiStr) {
  const dogum = new Date(dogumTarihiStr + 'T00:00:00');
  const bugun = new Date();
  let yas = bugun.getFullYear() - dogum.getFullYear();
  const ayFarki = bugun.getMonth() - dogum.getMonth();
  if (ayFarki < 0 || (ayFarki === 0 && bugun.getDate() < dogum.getDate())) yas--;
  return yas;
}

function yasKontroluBaglat(inputId, uyariId) {
  const input = document.getElementById(inputId);
  const uyari = document.getElementById(uyariId);
  if (!input) return;
  input.addEventListener('change', () => {
    if (!input.value) { uyari.textContent = ''; input.setCustomValidity(''); return; }
    if (yasHesapla(input.value) < 18) {
      uyari.textContent = '18 yaşından küçükler için nikah randevusu oluşturulamaz.';
      input.setCustomValidity('18 yaşından küçükler için nikah randevusu oluşturulamaz.');
    } else {
      uyari.textContent = '';
      input.setCustomValidity('');
    }
  });
}

yasKontroluBaglat('gelin_dogum_tarihi', 'gelinYasUyari');
yasKontroluBaglat('damat_dogum_tarihi', 'damatYasUyari');

document.querySelector('form[action*="randevu_guncelle"]').addEventListener('submit', function (e) {
  const gelinTarih = document.getElementById('gelin_dogum_tarihi').value;
  const damatTarih = document.getElementById('damat_dogum_tarihi').value;
  if ((gelinTarih && yasHesapla(gelinTarih) < 18) || (damatTarih && yasHesapla(damatTarih) < 18)) {
    e.preventDefault();
    alert('18 yaşından küçükler için nikah randevusu oluşturulamaz.');
  }
});
</script>

</body>
</html>