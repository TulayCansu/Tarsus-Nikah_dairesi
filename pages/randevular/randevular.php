<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php'; 

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

// --- Filtreleme / Arama parametreleri ---
$f_ara       = trim($_GET['ara'] ?? '');            // ad, soyad veya TC kimlik no içinde arama
$f_durum     = trim($_GET['durum'] ?? '');           // bekliyor | onaylandi | tamamlandi | iptal
$f_salon_id  = (int) ($_GET['salon_id'] ?? 0);
$f_tarih_bas = trim($_GET['tarih_bas'] ?? '');        // YYYY-MM-DD
$f_tarih_bit = trim($_GET['tarih_bit'] ?? '');        // YYYY-MM-DD

$gecerli_durumlar = array_keys($DURUM_ETIKET);
if ($f_durum !== '' && !in_array($f_durum, $gecerli_durumlar, true)) {
    $f_durum = '';
}
if ($f_tarih_bas !== '' && !DateTime::createFromFormat('Y-m-d', $f_tarih_bas)) {
    $f_tarih_bas = '';
}
if ($f_tarih_bit !== '' && !DateTime::createFromFormat('Y-m-d', $f_tarih_bit)) {
    $f_tarih_bit = '';
}

$filtre_aktif = $f_ara !== '' || $f_durum !== '' || $f_salon_id > 0 || $f_tarih_bas !== '' || $f_tarih_bit !== '';

// Sayfalama linklerinde ve formda tekrar kullanmak için filtre parametreleri
$filtre_query = [
    'ara'       => $f_ara,
    'durum'     => $f_durum,
    'salon_id'  => $f_salon_id > 0 ? $f_salon_id : '',
    'tarih_bas' => $f_tarih_bas,
    'tarih_bit' => $f_tarih_bit,
];
$filtre_query_string = http_build_query(array_filter($filtre_query, fn($v) => $v !== ''));

$kosullar = [];
$parametreler = [];

if ($f_ara !== '') {
    $kosullar[] = "(r.gelin_adi LIKE :ara1 OR r.gelin_soyad LIKE :ara2 OR r.damat_adi LIKE :ara3 OR r.damat_soyad LIKE :ara4 OR r.gelin_TC LIKE :ara5 OR r.damat_TC LIKE :ara6)";
    $ara_deger = '%' . $f_ara . '%';
    $parametreler['ara1'] = $ara_deger;
    $parametreler['ara2'] = $ara_deger;
    $parametreler['ara3'] = $ara_deger;
    $parametreler['ara4'] = $ara_deger;
    $parametreler['ara5'] = $ara_deger;
    $parametreler['ara6'] = $ara_deger;
}
if ($f_durum !== '') {
    $kosullar[] = "r.durum = :durum";
    $parametreler['durum'] = $f_durum;
}
if ($f_salon_id > 0) {
    $kosullar[] = "r.salon_id = :salon_id";
    $parametreler['salon_id'] = $f_salon_id;
}
if ($f_tarih_bas !== '') {
    $kosullar[] = "r.tarih >= :tarih_bas";
    $parametreler['tarih_bas'] = $f_tarih_bas;
}
if ($f_tarih_bit !== '') {
    $kosullar[] = "r.tarih <= :tarih_bit";
    $parametreler['tarih_bit'] = $f_tarih_bit;
}

$where_sql = count($kosullar) > 0 ? ('WHERE ' . implode(' AND ', $kosullar)) : '';

// --- İstatistik kartları (filtreden bağımsız, genel durum) ---
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

// --- Filtreye uyan toplam kayıt sayısı (sayfalama bu sayıya göre hesaplanır) ---
$sayimStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM randevular r
    JOIN salonlar sal ON r.salon_id = sal.id
    JOIN saatler sa ON r.saat_id = sa.id
    $where_sql
");
$sayimStmt->execute($parametreler);
$filtreli_toplam = (int) $sayimStmt->fetchColumn();

// --- Randevu listesi (filtreli + sayfalı) ---
$stmt = $pdo->prepare("
    SELECT r.id, r.gelin_adi, r.gelin_soyad, r.damat_adi, r.damat_soyad,
           r.tarih, r.durum, sal.ad AS salon_adi, sa.saat
    FROM randevular r
    JOIN salonlar sal ON r.salon_id = sal.id
    JOIN saatler sa ON r.saat_id = sa.id
    $where_sql
    ORDER BY r.tarih DESC, sa.saat DESC
    LIMIT :limit OFFSET :offset
");
foreach ($parametreler as $anahtar => $deger) {
    $stmt->bindValue(':' . $anahtar, $deger);
}
$stmt->bindValue(':limit', $sayfa_basi, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toplam_sayfa = max(1, (int) ceil($filtreli_toplam / $sayfa_basi));

// --- Form için gerekli seçenekler ---
$salonlar = $pdo->query("SELECT id, ad FROM salonlar WHERE aktif = 1 ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
// Filtre kutusunda geçmiş randevuların ait olduğu pasif salonlar da görünsün diye ayrı bir liste
$salonlar_filtre = $pdo->query("SELECT id, ad FROM salonlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

// Sadece rolü 'personel' olanlar randevu memuru olarak seçilebilir
$personeller = $pdo->query("SELECT id, ad, soyad FROM personeller WHERE aktif = 1 AND rol = 'personel' ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

// Saat artık $saatler dizisiyle değil, salon + tarih seçilince AJAX ile anlık getiriliyor.
// Formun hazır olması için artık sadece salon ve personel bulunması yeterli.
$form_hazir = count($salonlar) > 0 && count($personeller) > 0;

// --- Resmi tatil tarihleri (formda anlık uyarı için) ---
// Sabit millî bayramlar (1 Ocak, 23 Nisan, 1 Mayıs, 19 Mayıs, 15 Temmuz, 30 Ağustos, 29 Ekim vb.)
// her_yil_tekrar = 1 ile işaretlenir ve ay-gün eşleşmesiyle HER YIL gösterilir.
// Ramazan/Kurban Bayramı gibi dini bayramlar yıldan yıla kaydığı için her_yil_tekrar = 0'dır,
// yalnızca girildikleri yıl için gösterilir.
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
<link rel="stylesheet" href="../../assets/css/randevular.css?v=7">
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
        <div class="filtre-cubugu">
          <form method="GET" class="filtre-form" id="filtreForm">
            <div class="filtre-grup filtre-ara">
              <input type="text" name="ara" placeholder="İsim, soyisim veya TC kimlik no ara..." value="<?php echo htmlspecialchars($f_ara); ?>">
            </div>
            <div class="filtre-grup">
              <select name="durum">
                <option value="">Tüm Durumlar</option>
                <?php foreach ($DURUM_ETIKET as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo $f_durum === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtre-grup">
              <select name="salon_id">
                <option value="">Tüm Salonlar</option>
                <?php foreach ($salonlar_filtre as $s): ?>
                  <option value="<?php echo $s['id']; ?>" <?php echo $f_salon_id === (int) $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['ad']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtre-grup">
              <input type="date" name="tarih_bas" value="<?php echo htmlspecialchars($f_tarih_bas); ?>" title="Başlangıç tarihi">
            </div>
            <div class="filtre-grup">
              <input type="date" name="tarih_bit" value="<?php echo htmlspecialchars($f_tarih_bit); ?>" title="Bitiş tarihi">
            </div>
            <div class="filtre-grup filtre-aksiyon">
              <button type="submit" class="btn-filtrele">🔍 Filtrele</button>
              <?php if ($filtre_aktif): ?>
                <a href="randevular.php" class="btn-filtre-temizle">Temizle ✕</a>
              <?php endif; ?>
            </div>
          </form>
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
              <?php if (count($randevular) === 0 && $filtre_aktif): ?>
                <tr><td colspan="6" class="bos-durum">Filtrelere uyan randevu bulunamadı. <a href="randevular.php">Filtreleri temizle</a></td></tr>
              <?php elseif (count($randevular) === 0): ?>
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
            <span>
              <?php if ($filtre_aktif): ?>Filtreye uyan <?php endif; ?>Toplam <?php echo $filtreli_toplam; ?> kayıttan
              <?php echo $filtreli_toplam === 0 ? 0 : $offset + 1; ?>–<?php echo min($offset + $sayfa_basi, $filtreli_toplam); ?> arası gösteriliyor.</span>
            <div class="sayfalama">
              <?php
                $sayfaLink = function ($n) use ($filtre_query_string) {
                    $q = 'sayfa=' . $n;
                    if ($filtre_query_string !== '') $q .= '&' . $filtre_query_string;
                    return '?' . $q;
                };
              ?>
              <a class="<?php echo $sayfa <= 1 ? 'devre-disi' : ''; ?>" href="<?php echo $sayfaLink(max(1, $sayfa - 1)); ?>">‹</a>
              <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <a class="<?php echo $i === $sayfa ? 'aktif' : ''; ?>" href="<?php echo $sayfaLink($i); ?>"><?php echo $i; ?></a>
              <?php endfor; ?>
              <a class="<?php echo $sayfa >= $toplam_sayfa ? 'devre-disi' : ''; ?>" href="<?php echo $sayfaLink(min($toplam_sayfa, $sayfa + 1)); ?>">›</a>
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
                  <select id="saat_id" name="saat_id" required disabled>
                    <option value="">Önce Salon ve Tarih Seçiniz</option>
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

  // --- Uygun Saatleri Getirme Fonksiyonu ---
function uygunSaatleriYukle() {
  const salonId = document.getElementById('salon_id').value;
  const tarih = document.getElementById('tarih').value; // Gizli input olan 'tarih'
  const saatSelect = document.getElementById('saat_id');

  // Salon veya tarih seçilmediyse saat kutusunu sıfırla ve kilitle
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

      if (saatler.length === 0) {
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

    // Salon değiştiğinde saatleri tekrar sorgula
    document.getElementById('salon_id').addEventListener('change', uygunSaatleriYukle);

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
          uygunSaatleriYukle(); // Takvimden gün seçildiğinde saatleri otomatik getirir
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