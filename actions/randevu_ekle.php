<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function geriDon(string $mesaj, bool $basarili): void
{
    $_SESSION[$basarili ? 'basari' : 'hata'] = $mesaj;
    header('Location: ../pages/randevular/randevular.php');
    exit;
}

if (!isset($_SESSION['personel_id'])) {
    geriDon('Bu işlem için giriş yapmalısınız.', false);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    geriDon('Geçersiz istek.', false);
}

// --- Girdileri topla ve temizle ---
$gelin_adi          = trim($_POST['gelin_adi'] ?? '');
$gelin_soyad        = trim($_POST['gelin_soyad'] ?? '');
$gelin_TC           = trim($_POST['gelin_TC'] ?? '');
$gelin_tel          = trim($_POST['gelin_tel'] ?? '');
$gelin_dogum_tarihi = trim($_POST['gelin_dogum_tarihi'] ?? '');

$damat_adi          = trim($_POST['damat_adi'] ?? '');
$damat_soyad        = trim($_POST['damat_soyad'] ?? '');
$damat_TC           = trim($_POST['damat_TC'] ?? '');
$damat_tel          = trim($_POST['damat_tel'] ?? '');
$damat_dogum_tarihi = trim($_POST['damat_dogum_tarihi'] ?? '');

$tarih              = trim($_POST['tarih'] ?? '');
$saat_id            = (int) ($_POST['saat_id'] ?? 0);
$salon_id           = (int) ($_POST['salon_id'] ?? 0);
$personel_id        = (int) ($_POST['personel_id'] ?? 0);
$durum              = trim($_POST['durum'] ?? 'bekliyor');
$odeme_durumu       = trim($_POST['odeme_durumu'] ?? 'ödenmedi');
$odeme_tutari       = trim($_POST['odeme_tutari'] ?? '0');

// --- Yardımcı: doğum tarihine göre 18 yaşını doldurmuş mu? ---
function resitMi(string $dogumTarihi): bool
{
    $dogum = DateTime::createFromFormat('Y-m-d', $dogumTarihi);
    if (!$dogum) return false;
    $on_sekiz_yil_once = new DateTime('-18 years');
    return $dogum <= $on_sekiz_yil_once;
}

// --- Doğrulama ---
$hatalar = [];

if ($gelin_adi === '' || $gelin_soyad === '') $hatalar[] = 'Gelin adı ve soyadı zorunludur.';
if ($damat_adi === '' || $damat_soyad === '') $hatalar[] = 'Damat adı ve soyadı zorunludur.';

if (!preg_match('/^\d{11}$/', $gelin_TC)) $hatalar[] = 'Gelin TC kimlik numarası 11 haneli olmalıdır.';
if (!preg_match('/^\d{11}$/', $damat_TC)) $hatalar[] = 'Damat TC kimlik numarası 11 haneli olmalıdır.';

if ($gelin_tel === '' || $damat_tel === '') $hatalar[] = 'Telefon numaraları zorunludur.';

if (!DateTime::createFromFormat('Y-m-d', $gelin_dogum_tarihi)) {
    $hatalar[] = 'Gelin doğum tarihi geçerli değil.';
} elseif (!resitMi($gelin_dogum_tarihi)) {
    $hatalar[] = 'Gelin 18 yaşından küçük olduğu için nikah randevusu oluşturulamaz.';
}

if (!DateTime::createFromFormat('Y-m-d', $damat_dogum_tarihi)) {
    $hatalar[] = 'Damat doğum tarihi geçerli değil.';
} elseif (!resitMi($damat_dogum_tarihi)) {
    $hatalar[] = 'Damat 18 yaşından küçük olduğu için nikah randevusu oluşturulamaz.';
}

if (!DateTime::createFromFormat('Y-m-d', $tarih)) $hatalar[] = 'Geçerli bir tarih seçilmelidir.';

if ($saat_id <= 0)     $hatalar[] = 'Saat seçilmelidir.';
if ($salon_id <= 0)    $hatalar[] = 'Salon seçilmelidir.';
if ($personel_id <= 0) $hatalar[] = 'Memur seçilmelidir.';

if (!in_array($durum, ['bekliyor', 'onaylandi', 'tamamlandi', 'iptal'], true)) $hatalar[] = 'Geçersiz durum.';
if (!in_array($odeme_durumu, ['ödendi', 'ödenmedi'], true)) $hatalar[] = 'Geçersiz ödeme durumu.';
if (!is_numeric($odeme_tutari) || (float) $odeme_tutari < 0) $hatalar[] = 'Geçersiz ödeme tutarı.';

if (!empty($hatalar)) {
    geriDon(implode(' ', $hatalar), false);
}

// --- Kayıt ---
try {
    // 1. Seçilen saat_id'nin saat string karşılığını bulalım (Örn: '09:00:00')
    $stmt_saat = $pdo->prepare("SELECT saat FROM saatler WHERE id = ?");
    $stmt_saat->execute([$saat_id]);
    $secilen_saat = $stmt_saat->fetchColumn();

    if (!$secilen_saat) {
        geriDon('Geçersiz saat seçimi.', false);
    }

    // 2. Memurun aynı gün ve saatte (başka salonda dahi olsa) randevusu var mı kontrol edelim
    $sql_caksima = "
        SELECT COUNT(*) 
        FROM randevular r
        JOIN saatler s ON r.saat_id = s.id
        WHERE r.personel_id = :personel_id 
          AND r.tarih = :tarih 
          AND s.saat = :secilen_saat 
          AND r.durum != 'iptal'
    ";
    $stmt_check = $pdo->prepare($sql_caksima);
    $stmt_check->execute([
        'personel_id'  => $personel_id,
        'tarih'        => $tarih,
        'secilen_saat'  => $secilen_saat
    ]);

    if ($stmt_check->fetchColumn() > 0) {
        geriDon('Seçilen nikah memurunun belirtilen tarih ve saatte başka bir randevusu bulunmaktadır.', false);
    }

    // 3. Veritabanı Kaydı (gelin_dogum_tarihi ve damat_dogum_tarihi eklendi)
    $stmt = $pdo->prepare("
        INSERT INTO randevular
            (gelin_adi, gelin_soyad, gelin_TC, gelin_tel, gelin_dogum_tarihi,
             damat_adi, damat_soyad, damat_TC, damat_tel, damat_dogum_tarihi,
             tarih, saat_id, salon_id, personel_id,
             durum, olusturma_tarihi, guncelleme_tarihi,
             iptal_nedeni, odeme_durumu, odeme_tutari)
        VALUES
            (:gelin_adi, :gelin_soyad, :gelin_TC, :gelin_tel, :gelin_dogum_tarihi,
             :damat_adi, :damat_soyad, :damat_TC, :damat_tel, :damat_dogum_tarihi,
             :tarih, :saat_id, :salon_id, :personel_id,
             :durum, NOW(), NOW(),
             '', :odeme_durumu, :odeme_tutari)
    ");

    $stmt->execute([
        'gelin_adi'          => $gelin_adi,
        'gelin_soyad'        => $gelin_soyad,
        'gelin_TC'           => $gelin_TC,
        'gelin_tel'          => $gelin_tel,
        'gelin_dogum_tarihi' => $gelin_dogum_tarihi,
        'damat_adi'          => $damat_adi,
        'damat_soyad'        => $damat_soyad,
        'damat_TC'           => $damat_TC,
        'damat_tel'          => $damat_tel,
        'damat_dogum_tarihi' => $damat_dogum_tarihi,
        'tarih'              => $tarih,
        'saat_id'            => $saat_id,
        'salon_id'           => $salon_id,
        'personel_id'        => $personel_id,
        'durum'              => $durum,
        'odeme_durumu'       => $odeme_durumu,
        'odeme_tutari'       => $odeme_tutari,
    ]);

    // Log kaydı
    $log = $pdo->prepare('INSERT INTO loglar (personel_id, islem, tarih, ip) VALUES (:pid, :islem, NOW(), :ip)');
    $log->execute([
        'pid'   => $_SESSION['personel_id'],
        'islem' => "Randevu ekledi: $gelin_adi $gelin_soyad & $damat_adi $damat_soyad",
        'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor',
    ]);

    geriDon('Randevu başarıyla eklendi.', true);

} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
        geriDon('Bu tarih, saat ve salon için zaten bir randevu var. Lütfen farklı bir saat veya salon seçin.', false);
    }
    geriDon('Veritabanı hatası: ' . $e->getMessage(), false);
}