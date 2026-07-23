<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sadece admin görebilir
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../dashboard/dashboard.php');
    exit;
}

$aylar_tr = [1=>'Oca',2=>'Şub',3=>'Mar',4=>'Nis',5=>'May',6=>'Haz',7=>'Tem',8=>'Ağu',9=>'Eyl',10=>'Eki',11=>'Kas',12=>'Ara'];

// -------------------------------------------------------------
// Bir log satırının serbest metnini (islem) anlamlı bir
// İşlem Türü / Detay / Durum kombinasyonuna çevirir.
// -------------------------------------------------------------
function log_ayristir($islem) {
    if (preg_match('/^Randevu ekledi:\s*(.+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'ekle', 'tur' => 'Randevu Oluşturdu', 'ikon' => '+',
            'renk' => 'yesil', 'detay' => $m[1], 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (preg_match('/^Randevu güncelledi:\s*#?(\d+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'guncelle', 'tur' => 'Randevu Güncelledi', 'ikon' => '✎',
            'renk' => 'turuncu', 'detay' => 'Randevu No: #' . $m[1], 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (preg_match('/^Randevu iptal etti:\s*#?(\d+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'iptal', 'tur' => 'Randevu İptal Etti', 'ikon' => '✕',
            'renk' => 'kirmizi', 'detay' => 'Randevu No: #' . $m[1], 'durum' => 'İptal', 'durum_sinif' => 'iptal',
        ];
    }
    if (preg_match('/^Randevu görüntüledi:\s*#?(\d+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'goruntule', 'tur' => 'Randevu Görüntüledi', 'ikon' => '👁',
            'renk' => 'mavi', 'detay' => 'Randevu No: #' . $m[1], 'durum' => 'Bilgi', 'durum_sinif' => 'bilgi',
        ];
    }
    if (preg_match('/^Personel ekledi:\s*(.+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'personel_ekle', 'tur' => 'Personel Ekledi', 'ikon' => '👤',
            'renk' => 'mor', 'detay' => 'Yeni Personel: ' . $m[1], 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (preg_match('/^Personel güncelledi:\s*(.+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'personel_guncelle', 'tur' => 'Personel Güncelledi', 'ikon' => '✎',
            'renk' => 'turuncu', 'detay' => $m[1], 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (preg_match('/^Personel sildi:\s*(.+)$/u', $islem, $m)) {
        return [
            'tur_kod' => 'personel_sil', 'tur' => 'Personel Sildi', 'ikon' => '✕',
            'renk' => 'kirmizi', 'detay' => $m[1], 'durum' => 'Bilgi', 'durum_sinif' => 'bilgi',
        ];
    }
    if (preg_match('/^Salon.*güncelledi:\s*(.+)$/ui', $islem, $m)) {
        return [
            'tur_kod' => 'salon_guncelle', 'tur' => 'Salon Bilgisi Güncelledi', 'ikon' => '🏛',
            'renk' => 'teal', 'detay' => $m[1], 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (trim($islem) === 'Giriş yaptı') {
        return [
            'tur_kod' => 'giris', 'tur' => 'Sisteme Giriş Yaptı', 'ikon' => '🔒',
            'renk' => 'mavi', 'detay' => 'Oturum başlatıldı', 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    if (trim($islem) === 'Çıkış yaptı') {
        return [
            'tur_kod' => 'cikis', 'tur' => 'Sistemden Çıkış Yaptı', 'ikon' => '🔓',
            'renk' => 'mavi', 'detay' => 'Oturum sonlandırıldı', 'durum' => 'Başarılı', 'durum_sinif' => 'basarili',
        ];
    }
    // Bilinmeyen / tanımlanmamış işlem türleri için genel görünüm
    return [
        'tur_kod' => 'diger', 'tur' => 'Diğer İşlem', 'ikon' => '•',
        'renk' => 'gri', 'detay' => $islem, 'durum' => 'Bilgi', 'durum_sinif' => 'bilgi',
    ];
}

$tur_secenekleri = [
    ''                => 'Tüm İşlem Türleri',
    'ekle'            => 'Randevu Oluşturdu',
    'guncelle'        => 'Randevu Güncelledi',
    'iptal'           => 'Randevu İptal Etti',
    'goruntule'       => 'Randevu Görüntüledi',
    'personel_ekle'   => 'Personel Ekledi',
    'personel_guncelle' => 'Personel Güncelledi',
    'personel_sil'    => 'Personel Sildi',
    'salon_guncelle'  => 'Salon Bilgisi Güncelledi',
    'giris'           => 'Sisteme Giriş Yaptı',
    'cikis'           => 'Sistemden Çıkış Yaptı',
    'diger'           => 'Diğer',
];
$tur_sql_desenleri = [
    'ekle'              => "l.islem LIKE 'Randevu ekledi:%'",
    'guncelle'          => "l.islem LIKE 'Randevu güncelledi:%'",
    'iptal'             => "l.islem LIKE 'Randevu iptal etti:%'",
    'goruntule'         => "l.islem LIKE 'Randevu görüntüledi:%'",
    'personel_ekle'     => "l.islem LIKE 'Personel ekledi:%'",
    'personel_guncelle' => "l.islem LIKE 'Personel güncelledi:%'",
    'personel_sil'      => "l.islem LIKE 'Personel sildi:%'",
    'salon_guncelle'    => "l.islem LIKE '%alon%güncelledi:%'",
    'giris'             => "l.islem = 'Giriş yaptı'",
    'cikis'             => "l.islem = 'Çıkış yaptı'",
    'diger'             => "l.islem NOT LIKE 'Randevu ekledi:%' AND l.islem NOT LIKE 'Randevu güncelledi:%' AND l.islem NOT LIKE 'Randevu iptal etti:%' AND l.islem NOT LIKE 'Randevu görüntüledi:%' AND l.islem NOT LIKE 'Personel ekledi:%' AND l.islem NOT LIKE 'Personel güncelledi:%' AND l.islem NOT LIKE 'Personel sildi:%' AND l.islem NOT LIKE '%alon%güncelledi:%' AND l.islem NOT IN ('Giriş yaptı','Çıkış yaptı')",
];

// -------------------------------------------------------------
// CSV DIŞA AKTARMA
// -------------------------------------------------------------
$disa_aktar = isset($_GET['aktar']) && $_GET['aktar'] === 'csv';

// -------------------------------------------------------------
// FİLTRELER
// -------------------------------------------------------------
$f_kullanici = isset($_GET['kullanici']) ? intval($_GET['kullanici']) : 0;
$f_tur       = isset($_GET['tur']) ? trim($_GET['tur']) : '';
$f_baslangic = isset($_GET['baslangic']) ? trim($_GET['baslangic']) : '';
$f_bitis     = isset($_GET['bitis']) ? trim($_GET['bitis']) : '';
$f_ara       = isset($_GET['ara']) ? trim($_GET['ara']) : '';
$sirala      = isset($_GET['sirala']) && $_GET['sirala'] === 'eski' ? 'ASC' : 'DESC';
$sayfa_boyutu = isset($_GET['boyut']) ? intval($_GET['boyut']) : 10;
if (!in_array($sayfa_boyutu, [10, 25, 50], true)) $sayfa_boyutu = 10;
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;

$kosullar = [];
$params = [];

if ($f_kullanici > 0) {
    $kosullar[] = 'l.personel_id = :kullanici';
    $params['kullanici'] = $f_kullanici;
}
if ($f_tur !== '' && isset($tur_sql_desenleri[$f_tur])) {
    $kosullar[] = $tur_sql_desenleri[$f_tur];
}
if ($f_baslangic !== '') {
    $kosullar[] = 'DATE(l.tarih) >= :baslangic';
    $params['baslangic'] = $f_baslangic;
}
if ($f_bitis !== '') {
    $kosullar[] = 'DATE(l.tarih) <= :bitis';
    $params['bitis'] = $f_bitis;
}
if ($f_ara !== '') {
    $kosullar[] = "(l.islem LIKE :ara OR CONCAT(p.ad,' ',p.soyad) LIKE :ara OR l.ip LIKE :ara)";
    $params['ara'] = '%' . $f_ara . '%';
}

$where_sql = empty($kosullar) ? '' : ('WHERE ' . implode(' AND ', $kosullar));

try {
    // Kullanıcı listesi (filtre dropdown için)
    $kullanicilar = $pdo->query("SELECT id, ad, soyad FROM personeller ORDER BY ad, soyad")->fetchAll(PDO::FETCH_ASSOC);

    // İstatistik kartları
    $toplam_islem = (int) $pdo->query("SELECT COUNT(*) FROM loglar")->fetchColumn();
    $bugunku_islem = (int) $pdo->query("SELECT COUNT(*) FROM loglar WHERE DATE(tarih) = CURDATE()")->fetchColumn();
    $giris_bugun = (int) $pdo->query("SELECT COUNT(*) FROM loglar WHERE DATE(tarih) = CURDATE() AND islem = 'Giriş yaptı'")->fetchColumn();
    $guncelleme_bugun = (int) $pdo->query("SELECT COUNT(*) FROM loglar WHERE DATE(tarih) = CURDATE() AND islem LIKE 'Randevu güncelledi:%'")->fetchColumn();
    $iptal_bugun = (int) $pdo->query("SELECT COUNT(*) FROM loglar WHERE DATE(tarih) = CURDATE() AND islem LIKE 'Randevu iptal etti:%'")->fetchColumn();

    // Toplam filtrelenmiş kayıt sayısı
    $sayim_sql = "SELECT COUNT(*) FROM loglar l JOIN personeller p ON l.personel_id = p.id $where_sql";
    $sayim_stmt = $pdo->prepare($sayim_sql);
    $sayim_stmt->execute($params);
    $filtreli_toplam = (int) $sayim_stmt->fetchColumn();

    if ($disa_aktar) {
        // --- CSV dışa aktarma: mevcut filtrelerle TÜM kayıtları döker ---
        $csv_sql = "SELECT l.tarih, p.ad, p.soyad, p.rol, l.islem, l.ip
                     FROM loglar l JOIN personeller p ON l.personel_id = p.id
                     $where_sql ORDER BY l.tarih $sirala";
        $csv_stmt = $pdo->prepare($csv_sql);
        $csv_stmt->execute($params);
        $tum_kayitlar = $csv_stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sistem_loglari_' . date('Y-m-d_His') . '.csv"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM, Excel'de Türkçe karakterler için
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Tarih/Saat', 'Kullanıcı', 'Rol', 'İşlem Türü', 'Detay', 'Durum', 'IP Adresi'], ';');
        foreach ($tum_kayitlar as $k) {
            $ay = log_ayristir($k['islem']);
            fputcsv($out, [
                date('d.m.Y H:i:s', strtotime($k['tarih'])),
                $k['ad'] . ' ' . $k['soyad'],
                $k['rol'],
                $ay['tur'],
                $ay['detay'],
                $ay['durum'],
                $k['ip'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    $toplam_sayfa = max(1, (int) ceil($filtreli_toplam / $sayfa_boyutu));
    if ($sayfa > $toplam_sayfa) $sayfa = $toplam_sayfa;
    $offset = ($sayfa - 1) * $sayfa_boyutu;

    $liste_sql = "SELECT l.id, l.tarih, l.islem, l.ip, p.ad, p.soyad, p.rol
                  FROM loglar l JOIN personeller p ON l.personel_id = p.id
                  $where_sql ORDER BY l.tarih $sirala LIMIT :limit OFFSET :offset";
    $liste_stmt = $pdo->prepare($liste_sql);
    foreach ($params as $k => $v) $liste_stmt->bindValue(':' . $k, $v);
    $liste_stmt->bindValue(':limit', $sayfa_boyutu, PDO::PARAM_INT);
    $liste_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $liste_stmt->execute();
    $kayitlar = $liste_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Aktif filtreleri URL için koru (pagination/sıralama linklerinde)
function filtre_url($ek = []) {
    $mevcut = $_GET;
    unset($mevcut['aktar']);
    foreach ($ek as $k => $v) $mevcut[$k] = $v;
    return '?' . http_build_query($mevcut);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Logları - Nikah İşleri Müdürlüğü</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/loglar.css">
</head>
<body>

<div class="layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="topbar">
            <h1>Sistem Logları</h1>
            <div class="topbar-user">
                Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['ad'] ?? ''); ?>
                <span class="avatar-circle">👤</span>
            </div>
        </div>

        <div class="baslik-satiri">
            <div class="baslik-ikon">🗂️</div>
            <div>
                <h2>Sistem Logları</h2>
                <p>Sistemde gerçekleştirilen tüm işlemleri görüntüleyin.</p>
            </div>
            <div class="baslik-butonlar">
                <a href="loglar.php" class="btn-beyaz">↻ Yenile</a>
                <a href="<?php echo filtre_url(['aktar' => 'csv']); ?>" class="btn-beyaz">⭳ Dışa Aktar</a>
            </div>
        </div>

        <section class="cards">
            <div class="card">
                <div class="card-icon mavi">🗒️</div>
                <div>
                    <div class="card-value"><?php echo number_format($toplam_islem, 0, ',', '.'); ?></div>
                    <div class="card-label">Toplam İşlem</div>
                    <div class="card-alt">Tüm zamanlar</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon yesil">📅</div>
                <div>
                    <div class="card-value"><?php echo $bugunku_islem; ?></div>
                    <div class="card-label">Bugünkü İşlem</div>
                    <div class="card-alt"><?php echo date('d.m.Y'); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon mor">🔒</div>
                <div>
                    <div class="card-value"><?php echo $giris_bugun; ?></div>
                    <div class="card-label">Giriş İşlemleri</div>
                    <div class="card-alt">Bugün</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon turuncu">✎</div>
                <div>
                    <div class="card-value"><?php echo $guncelleme_bugun; ?></div>
                    <div class="card-label">Güncelleme</div>
                    <div class="card-alt">Bugün</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon kirmizi">✕</div>
                <div>
                    <div class="card-value"><?php echo $iptal_bugun; ?></div>
                    <div class="card-label">İptal İşlemleri</div>
                    <div class="card-alt">Bugün</div>
                </div>
            </div>
        </section>

        <form method="GET" class="filtre-bar">
            <select name="kullanici">
                <option value="0">Tüm Kullanıcılar</option>
                <?php foreach ($kullanicilar as $k): ?>
                    <option value="<?php echo $k['id']; ?>" <?php echo $f_kullanici == $k['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="tur">
                <?php foreach ($tur_secenekleri as $kod => $etiket): ?>
                    <option value="<?php echo $kod; ?>" <?php echo $f_tur === $kod ? 'selected' : ''; ?>><?php echo $etiket; ?></option>
                <?php endforeach; ?>
            </select>

            <div class="filtre-tarih-grup">
                <label>Başlangıç Tarihi</label>
                <input type="date" name="baslangic" value="<?php echo htmlspecialchars($f_baslangic); ?>">
            </div>
            <div class="filtre-tarih-grup">
                <label>Bitiş Tarihi</label>
                <input type="date" name="bitis" value="<?php echo htmlspecialchars($f_bitis); ?>">
            </div>

            <button type="submit" class="btn-filtrele">🔍 Filtrele</button>

            <div class="ara-kutusu">
                <input type="text" name="ara" value="<?php echo htmlspecialchars($f_ara); ?>" placeholder="Ara...">
                <span>🔍</span>
            </div>
        </form>

        <div class="panel">
            <div class="panel-ust">
                <span>Toplam <?php echo number_format($filtreli_toplam, 0, ',', '.'); ?> kayıt bulundu.</span>
                <div class="sirala-secici">
                    <a href="<?php echo filtre_url(['sirala' => 'yeni']); ?>" class="<?php echo $sirala === 'DESC' ? 'aktif' : ''; ?>">En Yeni</a>
                    <a href="<?php echo filtre_url(['sirala' => 'eski']); ?>" class="<?php echo $sirala === 'ASC' ? 'aktif' : ''; ?>">En Eski</a>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>TARİH / SAAT</th>
                        <th>KULLANICI</th>
                        <th>İŞLEM TÜRÜ</th>
                        <th>DETAY</th>
                        <th>DURUM</th>
                        <th>IP ADRESİ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kayitlar)): ?>
                        <tr><td colspan="6" class="bos-durum">Filtrelerinize uygun log kaydı bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kayitlar as $k):
                            $ay = log_ayristir($k['islem']);
                            $ts = strtotime($k['tarih']);
                            $tarih_metin = date('d.m.Y H:i:s', $ts);
                        ?>
                        <tr>
                            <td class="tarih-td"><?php echo $tarih_metin; ?></td>
                            <td>
                                <div class="kullanici-hucre">
                                    <span class="mini-avatar <?php echo $k['rol'] === 'admin' ? 'admin' : 'personel'; ?>">
                                        <?php echo strtoupper(mb_substr($k['ad'], 0, 1)); ?>
                                    </span>
                                    <div>
                                        <div class="kullanici-ad"><?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?></div>
                                        <div class="kullanici-rol"><?php echo htmlspecialchars(ucfirst($k['rol'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="islem-turu <?php echo $ay['renk']; ?>">
                                    <span class="islem-ikon"><?php echo $ay['ikon']; ?></span> <?php echo htmlspecialchars($ay['tur']); ?>
                                </span>
                            </td>
                            <td class="detay-td"><?php echo htmlspecialchars($ay['detay']); ?></td>
                            <td><span class="durum-rozet <?php echo $ay['durum_sinif']; ?>"><?php echo htmlspecialchars($ay['durum']); ?></span></td>
                            <td class="ip-td"><?php echo htmlspecialchars($k['ip']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="table-footer">
                <div class="pagination">
                    <a href="<?php echo filtre_url(['sayfa' => max(1, $sayfa - 1)]); ?>" class="page-btn <?php echo $sayfa <= 1 ? 'disabled' : ''; ?>">‹</a>
                    <?php
                        $bas = max(1, $sayfa - 2);
                        $bit = min($toplam_sayfa, $sayfa + 2);
                        if ($bas > 1) echo '<a href="' . filtre_url(['sayfa' => 1]) . '" class="page-btn">1</a>' . ($bas > 2 ? '<span class="page-nokta">...</span>' : '');
                        for ($p = $bas; $p <= $bit; $p++):
                    ?>
                        <a href="<?php echo filtre_url(['sayfa' => $p]); ?>" class="page-btn <?php echo $p == $sayfa ? 'active' : ''; ?>"><?php echo $p; ?></a>
                    <?php endfor;
                        if ($bit < $toplam_sayfa) echo ($bit < $toplam_sayfa - 1 ? '<span class="page-nokta">...</span>' : '') . '<a href="' . filtre_url(['sayfa' => $toplam_sayfa]) . '" class="page-btn">' . $toplam_sayfa . '</a>';
                    ?>
                    <a href="<?php echo filtre_url(['sayfa' => min($toplam_sayfa, $sayfa + 1)]); ?>" class="page-btn <?php echo $sayfa >= $toplam_sayfa ? 'disabled' : ''; ?>">›</a>
                </div>

                <form method="GET" class="boyut-secici">
                    <?php foreach ($_GET as $gk => $gv): if ($gk !== 'boyut' && $gk !== 'sayfa'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($gk); ?>" value="<?php echo htmlspecialchars($gv); ?>">
                    <?php endif; endforeach; ?>
                    <select name="boyut" onchange="this.form.submit()">
                        <option value="10" <?php echo $sayfa_boyutu == 10 ? 'selected' : ''; ?>>10 / sayfa</option>
                        <option value="25" <?php echo $sayfa_boyutu == 25 ? 'selected' : ''; ?>>25 / sayfa</option>
                        <option value="50" <?php echo $sayfa_boyutu == 50 ? 'selected' : ''; ?>>50 / sayfa</option>
                    </select>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>