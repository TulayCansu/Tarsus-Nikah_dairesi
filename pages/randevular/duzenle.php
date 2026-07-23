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

// İptal edilmiş randevular artık düzenlenemez; direkt detay sayfasına yönlendir.
if ($randevu['durum'] === 'iptal') {
    $_SESSION['hata'] = 'İptal edilmiş bir randevu düzenlenemez.';
    header('Location: detay.php?id=' . $randevu['id']);
    exit;
}

$salonlar = $pdo->query("SELECT id, ad FROM salonlar WHERE aktif = 1 OR id = " . (int) $randevu['salon_id'] . " ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Resmi tatil tarihleri ---
$tatil_sabit = $pdo->query(
    "SELECT DATE_FORMAT(tarih, '%m-%d') AS ay_gun, aciklama FROM resmitatiller WHERE her_yil_tekrar = 1"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$tatil_degisken = $pdo->query(
    "SELECT tarih, aciklama FROM resmitatiller WHERE her_yil_tekrar = 0 OR her_yil_tekrar IS NULL"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Doğum tarihlerinin form açılışında doldurulması
$gelin_dogum_tarihi = !empty($randevu['gelin_dogum_tarihi']) ? $randevu['gelin_dogum_tarihi'] : '';
$damat_dogum_tarihi = !empty($randevu['damat_dogum_tarihi']) ? $randevu['damat_dogum_tarihi'] : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Randevu Düzenle | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css?v=7">
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
          <input type="hidden" name="id" id="randevu_id" value="<?php echo $randevu['id']; ?>">

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
                <input type="date" id="gelin_dogum_tarihi" name="gelin_dogum_tarihi" required 
                       value="<?php echo htmlspecialchars($gelin_dogum_tarihi); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
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
                <input type="date" id="damat_dogum_tarihi" name="damat_dogum_tarihi" required 
                       value="<?php echo htmlspecialchars($damat_dogum_tarihi); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
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
                <label for="tarih_goster">Tarih</label>
                <div class="tarih-secici" id="tarihSecici">
                  <input type="text" id="tarih_goster" class="tarih-goster" placeholder="Tarih seçin" autocomplete="off" readonly required>
                  <input type="hidden" id="tarih" name="tarih" value="<?php echo $randevu['tarih']; ?>">
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
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="saat_id">Saat</label>
                <select id="saat_id" name="saat_id" required>
                  <option value="">Yükleniyor...</option>
                </select>
              </div>
              <div class="form-grup">
                <label for="personel_id">Memur</label>
                <select id="personel_id" name="personel_id" required>
                  <option value="">Yükleniyor...</option>
                </select>
              </div>
            </div>
            <div class="form-satir">
              <div class="form-grup">
                <label for="durum">Durum</label>
                <select id="durum" name="durum" required>
                  <?php
                  $durumlar = ['bekliyor' => 'Beklemede', 'onaylandi' => 'Onaylandı', 'tamamlandi' => 'Tamamlandı'];
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
const MEVCUT_SAAT_ID = <?php echo (int) $randevu['saat_id']; ?>;
const MEVCUT_PERSONEL_ID = <?php echo (int) $randevu['personel_id']; ?>;
const RANDEVU_ID = <?php echo (int) $randevu['id']; ?>;

const TATIL_DEGISKEN = <?php echo json_encode($tatil_degisken, JSON_UNESCAPED_UNICODE); ?>;
const TATIL_SABIT = <?php echo json_encode($tatil_sabit, JSON_UNESCAPED_UNICODE); ?>;
const AY_ADLARI = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

function memurSelectSifirla(mesaj = 'Önce Tarih ve Saat Seçiniz') {
  const personelSelect = document.getElementById('personel_id');
  personelSelect.innerHTML = `<option value="">${mesaj}</option>`;
  personelSelect.disabled = true;
}

// --- Dinamik Müsait Memurları Yükleme ---
function uygunMemurlariYukle(varsayilanPersonelId = null) {
  const tarih = document.getElementById('tarih').value;
  const saatId = document.getElementById('saat_id').value;
  const personelSelect = document.getElementById('personel_id');

  if (!tarih || !saatId) {
    memurSelectSifirla();
    return;
  }

  personelSelect.innerHTML = '<option value="">Yükleniyor...</option>';
  personelSelect.disabled = true;

  fetch(`../../actions/uygun_memurlari_getir.php?tarih=${tarih}&saat_id=${saatId}&randevu_id=${RANDEVU_ID}`)
    .then(res => res.json())
    .then(memurlar => {
      personelSelect.innerHTML = '<option value="">Seçiniz</option>';

      if (!memurlar || memurlar.length === 0) {
        personelSelect.innerHTML = '<option value="">Bu saatte müsait memur bulunamadı</option>';
        personelSelect.disabled = true;
      } else {
        const secilecekId = varsayilanPersonelId || MEVCUT_PERSONEL_ID;
        memurlar.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = `${p.ad} ${p.soyad}`;
          if (p.id == secilecekId) {
            opt.selected = true;
          }
          personelSelect.appendChild(opt);
        });
        personelSelect.disabled = false;
      }
    })
    .catch(() => {
      personelSelect.innerHTML = '<option value="">Memurlar yüklenirken hata oluştu</option>';
      personelSelect.disabled = true;
    });
}

// --- Dinamik Uygun Saatleri Yükleme ---
function uygunSaatleriYukle(varsayilanSaatId = null) {
  const salonId = document.getElementById('salon_id').value;
  const tarih = document.getElementById('tarih').value;
  const saatSelect = document.getElementById('saat_id');

  if (!salonId || !tarih) {
    saatSelect.innerHTML = '<option value="">Önce Salon ve Tarih Seçiniz</option>';
    saatSelect.disabled = true;
    memurSelectSifirla();
    return;
  }

  saatSelect.innerHTML = '<option value="">Yükleniyor...</option>';
  saatSelect.disabled = true;

  fetch(`../../actions/uygun_saatleri_getir.php?salon_id=${salonId}&tarih=${tarih}&randevu_id=${RANDEVU_ID}`)
    .then(res => res.json())
    .then(saatler => {
      saatSelect.innerHTML = '<option value="">Saat Seçiniz</option>';

      if (!saatler || saatler.length === 0) {
        saatSelect.innerHTML = '<option value="">Bu tarihte uygun saat bulunamadı</option>';
        saatSelect.disabled = true;
        memurSelectSifirla();
      } else {
        const secilecekSaatId = varsayilanSaatId || MEVCUT_SAAT_ID;
        saatler.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.saat;
          if (s.id == secilecekSaatId) {
            opt.selected = true;
          }
          saatSelect.appendChild(opt);
        });
        saatSelect.disabled = false;

        // Saatler yüklendikten sonra o saate uygun memurları getir
        uygunMemurlariYukle(MEVCUT_PERSONEL_ID);
      }
    })
    .catch(() => {
      saatSelect.innerHTML = '<option value="">Saatler yüklenirken hata oluştu</option>';
      saatSelect.disabled = true;
      memurSelectSifirla();
    });
}

// Salon veya Saat değiştiğinde tetikleyiciler
document.getElementById('salon_id').addEventListener('change', () => {
  uygunSaatleriYukle();
});

document.getElementById('saat_id').addEventListener('change', () => {
  uygunMemurlariYukle();
});

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
      const haftaGunu = new Date(gYil, gAy, gun).getDay();
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
          uygunSaatleriYukle();
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

// Sayfa ilk yüklendiğinde mevcut salon ve tarihe göre saat ile memurları getir
document.addEventListener('DOMContentLoaded', () => {
  uygunSaatleriYukle(MEVCUT_SAAT_ID);
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
  
  const kontrolEt = () => {
    if (!input.value) { uyari.textContent = ''; input.setCustomValidity(''); return; }
    if (yasHesapla(input.value) < 18) {
      uyari.textContent = '18 yaşından küçükler için nikah randevusu oluşturulamaz.';
      input.setCustomValidity('18 yaşından küçükler için nikah randevusu oluşturulamaz.');
    } else {
      uyari.textContent = '';
      input.setCustomValidity('');
    }
  };

  input.addEventListener('change', kontrolEt);
  // Sayfa yüklendiğinde var olan değer için kontrolü çalıştır
  if (input.value) kontrolEt();
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