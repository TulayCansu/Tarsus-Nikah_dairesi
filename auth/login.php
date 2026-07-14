<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_view.php');
    exit;
}

$kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
$sifre = $_POST['sifre'] ?? '';

if ($kullanici_adi === '' || $sifre === '') {
    $_SESSION['giris_hata'] = 'Kullanıcı adı ve şifre zorunludur.';
    header('Location: login_view.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM personeller WHERE kullanici_adi = :kadi LIMIT 1');
$stmt->execute(['kadi' => $kullanici_adi]);
$personel = $stmt->fetch();

if (!$personel || !password_verify($sifre, $personel['sifre'])) {
    $_SESSION['giris_hata'] = 'Kullanıcı adı veya şifre hatalı.';
    header('Location: login_view.php');
    exit;
}

if ((int)$personel['aktif'] !== 1) {
    $_SESSION['giris_hata'] = 'Hesabınız pasif durumda. Yöneticinizle iletişime geçin.';
    header('Location: login_view.php');
    exit;
}

// Giriş başarılı - oturum başlat
session_regenerate_id(true);
$_SESSION['personel_id'] = $personel['id'];
$_SESSION['ad'] = $personel['ad'];
$_SESSION['soyad'] = $personel['soyad'];
$_SESSION['rol'] = $personel['rol'];

// Log kaydı
$log = $pdo->prepare('INSERT INTO loglar (personel_id, islem, tarih, ip) VALUES (:pid, :islem, NOW(), :ip)');
$log->execute([
    'pid' => $personel['id'],
    'islem' => 'Giriş yaptı',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor',
]);

header('Location: ../pages/dashboard/dashboard.php');
exit;