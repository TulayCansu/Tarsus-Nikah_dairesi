<?php
// GÜVENLİK: Bu script veritabanına doğrudan personel hesabı ekler.
// Önceden hiçbir kimlik doğrulaması yoktu; URL'yi bilen HERKES (giriş
// yapmadan) tarayıcıdan açarak sisteme sabit kullanıcı adı/şifre ile bir
// personel hesabı ekleyebiliyordu. Artık yalnızca:
//   1) Komut satırından (php admin_olustur.php) çalıştırılabilir, ya da
//   2) Sisteme zaten "admin" olarak giriş yapmış biri tarayıcıdan açarsa
//      çalışır.
// Bunların dışındaki tüm istekler 403 ile reddedilir.
$cli_modunda = (php_sapi_name() === 'cli');

if (!$cli_modunda) {
    require_once __DIR__ . '/includes/auth.php';
    yetkiKontrol('admin');
}

require_once __DIR__ . '/config/database.php';

$ad = 'memur';
$soyad = 'memur';
$kullanici_adi = 'memur';
$sifre_duz = 'memur123!';
// Geçerli roller: 'admin' | 'personel' (randevu girer/yönetir) | 'nikah_memuru' (sadece kendi günlük programını görür)
$rol = 'nikah_memuru';
$aktif = 1;

$sifre_hash = password_hash($sifre_duz, PASSWORD_DEFAULT);

try {
    $kontrol = $pdo->prepare('SELECT id FROM personeller WHERE kullanici_adi = :kadi');
    $kontrol->execute(['kadi' => $kullanici_adi]);
    if ($kontrol->fetch()) {
        echo "Bu kullanıcı adı ('$kullanici_adi') zaten kayıtlı. Yeni hesap oluşturulmadı.";
        exit;
    }

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
} catch (PDOException $e) {
    // Ham hata mesajı (veritabanı yapısını ifşa edebilir) artık ekrana basılmıyor.
    error_log('admin_olustur.php hata: ' . $e->getMessage());
    echo 'Hesap oluşturulurken bir hata oluştu. Ayrıntılar için sunucu log kaydına bakın.';
}