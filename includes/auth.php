<?php
// 1. GÜVENLİK AYARLARI

ini_set('session.cookie_httponly', 1); // JS erişimini engeller (XSS önlemi)
ini_set('session.use_only_cookies', 1); // URL'den session taşınmasını engeller
// "Secure" bayrağı yalnızca gerçek HTTPS bağlantısında aktif edilir.
// Sabit olarak 1 verilmesi, XAMPP/http://localhost gibi HTTPS olmayan
// geliştirme ortamlarında oturum çerezinin tarayıcı tarafından tamamen
// reddedilmesine (ve girişin sessizce çalışmamasına) yol açabiliyordu.
$https_aktif = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $https_aktif ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax'); // CSRF önlemi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    $proje_kok = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $belge_kok = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    define('BASE_URL', rtrim(str_replace($belge_kok, '', $proje_kok), '/'));
}

// 2. OTURUM KONTROL FONKSİYONLARI 
function checkLogin() {
    // Aktiflik kontrolü ve session varlığı kontrolü
    if (!isset($_SESSION['personel_id']) || (isset($_SESSION['is_active']) && $_SESSION['is_active'] != 1)) {
        $_SESSION['giris_hata'] = 'Bu sayfayı görüntülemek için giriş yapmalısınız.';
        
        // Oturumu güvenli bir şekilde sıfırla
        session_unset();
        session_destroy();
        
        header('Location: ' . BASE_URL . '/auth/login_view.php');
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

// Yetkisiz erişim durumunda 403 hatası verir ve yönlendirir.
function yetkisizErisimYonlendir() {
    http_response_code(403);
    header('Location: ../errors/hata-403.php');
    exit;
}

// 3. CSRF (Cross-Site Request Forgery) KORUMASI
// Oturum başına bir kez üretilir; formlara gizli alan olarak eklenir ve
// state değiştiren (POST) her istekte doğrulanır.
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Formların içine basılacak hazır gizli input alanı.
function csrf_alani(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// POST isteklerinin başında çağrılır; token eşleşmezse isteği reddeder.
function csrf_dogrula(): void {
    $gelen = $_POST['csrf_token'] ?? '';
    if ($gelen === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $gelen)) {
        http_response_code(403);

        // Bu uç zaten JSON döndürüyorsa (ör. randevu_iptal.php, personel/salon
        // toggle uçları) hatayı da aynı formatta döndür ki fetch() tarafındaki
        // res.json() çağrısı patlamasın.
        $json_yaniti = false;
        foreach (headers_list() as $h) {
            if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'application/json') !== false) {
                $json_yaniti = true;
                break;
            }
        }
        if (!$json_yaniti && (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || !empty($_SERVER['HTTP_X_REQUESTED_WITH']))) {
            $json_yaniti = true;
        }

        if ($json_yaniti) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.']);
        } else {
            echo 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen sayfayı yenileyip formu tekrar gönderin.';
        }
        exit;
    }
}