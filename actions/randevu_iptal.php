<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['personel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz randevu numarası.']);
    exit;
}

try {
    // Randevunun mevcut durumunu kontrol et: bulunamadı / zaten iptal / iptal edilebilir
    $kontrol = $pdo->prepare("SELECT durum FROM randevular WHERE id = :id");
    $kontrol->execute(['id' => $id]);
    $mevcut_durum = $kontrol->fetchColumn();

    if ($mevcut_durum === false) {
        echo json_encode(['success' => false, 'message' => 'Randevu bulunamadı.']);
        exit;
    }

    if ($mevcut_durum === 'iptal') {
        echo json_encode(['success' => false, 'message' => 'Bu randevu zaten iptal edilmiş.']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE randevular
        SET durum = 'iptal', guncelleme_tarihi = NOW()
        WHERE id = :id AND durum != 'iptal'
    ");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Randevu bulunamadı ya da zaten iptal edilmiş.']);
        exit;
    }

    $log = $pdo->prepare('INSERT INTO loglar (personel_id, islem, tarih, ip) VALUES (:pid, :islem, NOW(), :ip)');
    $log->execute([
        'pid'   => $_SESSION['personel_id'],
        'islem' => "Randevu iptal etti: #$id",
        'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor',
    ]);

    echo json_encode(['success' => true, 'message' => 'Randevu iptal edildi.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}