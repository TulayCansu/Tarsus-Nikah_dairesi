<?php
// 1. GÜVENLİK AYARLARI

ini_set('session.cookie_httponly', 1); // JS erişimini engeller (XSS önlemi)
ini_set('session.use_only_cookies', 1); // URL'den session taşınmasını engeller
ini_set('session.cookie_secure', 1);   // Sadece HTTPS üzerinden gönder (SSL varsa)
ini_set('session.cookie_samesite', 'Lax'); // CSRF önlemi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. OTURUM KONTROL FONKSİYONLARI 
function checkLogin() {
    // Aktiflik kontrolü ve session varlığı kontrolü
    if (!isset($_SESSION['personel_id']) || (isset($_SESSION['is_active']) && $_SESSION['is_active'] != 1)) {
        $_SESSION['giris_hata'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
        
        // Oturumu güvenli bir şekilde sıfırla
        session_unset();
        session_destroy();
        
        header('Location: ../auth/login_view.php'); 
        exit;
    }
}

//Rol yetkisini kontrol eder. Çoklu rol desteği vardır. (1. kodun en iyi yönü)
//Örnek kullanım: yetkiKontrol('admin') veya yetkiKontrol(['admin', 'editor'])
function yetkiKontrol($izin_verilenler) {
    
    checkLogin();

    if (!isset($_SESSION['rol'])) {
        yetkisizErisimYonlendir();
    }

    // Tek bir string gelirse diziye çevirerek esnekliği sağla
    $roller = is_array($izin_verilenler) ? $izin_verilenler : [$izin_verilenler];

    if (!in_array($_SESSION['rol'], $roller)) {
        yetkisizErisimYonlendir();
    }
}

// Zaten giriş yapmış kullanıcıyı login sayfasından uzaklaştırır.
function redirectIfLoggedIn() {
    if (isset($_SESSION['personel_id']) && (isset($_SESSION['is_active']) && $_SESSION['is_active'] == 1)) {
        header("Location: ../pages/dashboard.php");
        exit();
    }
}

// Yetkisiz erişim durumunda 403 hatası verir ve yönlendirir.
function yetkisizErisimYonlendir() {
    http_response_code(403);
    header('Location: ../errors/hata-403.php');
    exit;
}