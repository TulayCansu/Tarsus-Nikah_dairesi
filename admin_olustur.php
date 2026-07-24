<?php
require_once 'config/database.php';

$ad = 'memur';
$soyad = 'memur';
$kullanici_adi = 'memur';
$sifre_duz = 'memur123!';
// Geçerli roller: 'admin' | 'personel' (randevu girer/yönetir) | 'nikah_memuru' (sadece kendi günlük programını görür)
$rol = 'nikah_memuru';
$aktif = 1;

$sifre_hash = password_hash($sifre_duz, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO personeller (ad, soyad, kullanici_adi, sifre, aktif, rol) VALUES (:ad, :soyad, :kadi, :sifre, :aktif, :rol)');
$stmt->execute([
    'ad' => $ad,
    'soyad' => $soyad,
    'kadi' => $kullanici_adi,
    'sifre' => $sifre_hash,
    'aktif' => $aktif,
    'rol' => $rol,
]);

echo "Hesap oluşturuldu!<br>";
echo "Kullanıcı adı: $kullanici_adi<br>";
echo "Şifre: $sifre_duz<br>";
echo "Hash: $sifre_hash";