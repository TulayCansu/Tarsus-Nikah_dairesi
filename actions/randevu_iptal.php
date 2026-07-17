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
    $stmt = $pdo->prepare("
        UPDATE randevular
        SET durum = 'iptal', guncelleme_tarihi = NOW()
        WHERE id = :id
    ");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Randevu bulunamadı.']);
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