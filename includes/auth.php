<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmış mı kontrolü
if (!isset($_SESSION['personel_id'])) {
    $_SESSION['giris_hata'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
    header('Location: ../auth/login_view.php'); 
    exit;
}

// Rol kontrol fonksiyonu

function yetkiKontrol(array| string $izin_verilenler) {  //Sadece adminin erişebileceği sayfalar için
    if (!isset($_SESSION['rol'])) {
        yetkisizErisimYonlendir();
    }

    $roller = is_array($izin_verilenler) ? $izin_verilenler : [$izin_verilenler];

    if (!in_array($_SESSION['rol'], $roller)) {
        yetkisizErisimYonlendir();
    }
}

//Yetkisiz erişim durumunda yapılacak işlem
function yetkisizErisimYonlendir() {
    
    http_response_code(403);
    
    // Kullanıcıyı 403 hata sayfasına yönlendir
    header('Location: ../pages/errors/hata-403.php');
    exit;
}