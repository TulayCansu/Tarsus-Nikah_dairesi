<?php
require_once '../includes/auth.php';
// Oturum kapatma işlemini loglara kaydet (session yok edilmeden önce)
if (isset($_SESSION['personel_id'])) {
    require_once __DIR__ . '/../config/database.php';
    try {
        $log = $pdo->prepare('INSERT INTO loglar (personel_id, islem, tarih, ip) VALUES (:pid, :islem, NOW(), :ip)');
        $log->execute([
            'pid'   => $_SESSION['personel_id'],
            'islem' => 'Çıkış yaptı',
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor',
        ]);
    } catch (PDOException $e) {
        // Log kaydı başarısız olsa bile çıkış işlemini engelleme
    }
}

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session çerezini de temizle (varsa)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı tamamen yok et
session_destroy();

// Login sayfasına yönlendir
header("Location: login_view.php");
exit();