<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php'; 

// --- Durum etiketleri ve renk sınıfları ---
$DURUM_ETIKET = [
    'bekliyor'   => 'Beklemede',
    'onaylandi'  => 'Onaylandı',
    'tamamlandi' => 'Tamamlandı',
    'iptal'      => 'İptal Edildi',
];

// --- Flash mesajları ---
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
    "SELECT COUNT(*) FROM personeller WHERE aktif = 1 AND rol = 'personel'"
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
$personeller_sayisi = (int) $pdo->query("SELECT COUNT(*) FROM personeller WHERE aktif = 1 AND rol = 'personel'")->fetchColumn();

$form_hazir = count($salonlar) > 0 && $personeller_sayisi > 0;

// --- Resmi tatil tarihleri ---
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
<title>Randevular | Nikah İşleri Müdürlüğü</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/randevular.css?v=6">
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
        <?php if ($personeller_sayisi === 0): ?> en az bir aktif personel,<?php endif; ?>
        
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
                    <option value="">Seçiniz</option>
                    <?php foreach ($salonlar as $s): ?>
                      <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['ad']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
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
              </div>
              <div class="form-satir">
                <div class="form-grup">
                  <label for="saat_id">Saat</label>
                  <select id="saat_id" name="saat_id" required disabled>
                    <option value="">Önce Salon ve Tarih Seçiniz</option>
                  </select>
                </div>
                <div class="form-grup">
                  <label for="personel_id">Memur</label>
                  <select id="personel_id" name="personel_id" required disabled>
                    <option value="">Önce Tarih ve Saat Seçiniz</option>
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
const TATIL_DEGISKEN = <?php echo json_encode($tatil_degisken, JSON_UNESCAPED_UNICODE); ?>;
const TATIL_SABIT = <?php echo json_encode($tatil_sabit, JSON_UNESCAPED_UNICODE); ?>;
const AY_ADLARI = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

// --- Dinamik Saat Sıfırlama Yardımcısı ---
function memurSelectSifirla(mesaj = 'Önce Tarih ve Saat Seçiniz') {
  const personelSelect = document.getElementById('personel_id');
  personelSelect.innerHTML = `<option value="">${mesaj}</option>`;
  personelSelect.disabled = true;
}

// --- Müsait Memurları Getirme Fonksiyonu ---
function uygunMemurlariYukle() {
  const tarih = document.getElementById('tarih').value;
  const saatId = document.getElementById('saat_id').value;
  const personelSelect = document.getElementById('personel_id');

  if (!tarih || !saatId) {
    memurSelectSifirla();
    return;
  }

  personelSelect.innerHTML = '<option value="">Yükleniyor...</option>';
  personelSelect.disabled = true;

  fetch(`../../actions/uygun_memurlari_getir.php?tarih=${tarih}&saat_id=${saatId}`)
    .then(res => res.json())
    .then(memurlar => {
      personelSelect.innerHTML = '<option value="">Seçiniz</option>';

      if (!memurlar || memurlar.length === 0) {
        personelSelect.innerHTML = '<option value="">Bu saatte müsait memur bulunamadı</option>';
        personelSelect.disabled = true;
      } else {
        memurlar.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = `${p.ad} ${p.soyad}`;
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

// --- Uygun Saatleri Getirme Fonksiyonu ---
function uygunSaatleriYukle() {
  const salonId = document.getElementById('salon_id').value;
  const tarih = document.getElementById('tarih').value;
  const saatSelect = document.getElementById('saat_id');

  // Salon veya tarih değiştiğinde memur seçimini sıfırla
  memurSelectSifirla();

  if (!salonId || !tarih) {
    saatSelect.innerHTML = '<option value="">Önce Salon ve Tarih Seçiniz</option>';
    saatSelect.disabled = true;
    return;
  }

  saatSelect.innerHTML = '<option value="">Yükleniyor...</option>';
  saatSelect.disabled = true;

  fetch(`../../actions/uygun_saatleri_getir.php?salon_id=${salonId}&tarih=${tarih}`)
    .then(res => res.json())
    .then(saatler => {
      saatSelect.innerHTML = '<option value="">Saat Seçiniz</option>';

      if (!saatler || saatler.length === 0) {
        saatSelect.innerHTML = '<option value="">Bu tarihte uygun saat bulunamadı</option>';
        saatSelect.disabled = true;
      } else {
        saatler.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.saat;
          saatSelect.appendChild(opt);
        });
        saatSelect.disabled = false;
      }
    })
    .catch(() => {
      saatSelect.innerHTML = '<option value="">Saatler yüklenirken hata oluştu</option>';
      saatSelect.disabled = true;
    });
}

// Change Event Dinleyicileri
document.getElementById('salon_id').addEventListener('change', uygunSaatleriYukle);
document.getElementById('saat_id').addEventListener('change', uygunMemurlariYukle);

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
  gecmisiEngelle: true
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

document.querySelector('#yeni-randevu form').addEventListener('submit', function (e) {
  const gelinTarih = document.getElementById('gelin_dogum_tarihi').value;
  const damatTarih = document.getElementById('damat_dogum_tarihi').value;
  if ((gelinTarih && yasHesapla(gelinTarih) < 18) || (damatTarih && yasHesapla(damatTarih) < 18)) {
    e.preventDefault();
    alert('18 yaşından küçükler için nikah randevusu oluşturulamaz.');
  }
});

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