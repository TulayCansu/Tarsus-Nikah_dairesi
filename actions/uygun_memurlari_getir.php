<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$tarih   = $_GET['tarih'] ?? '';
$saat_id = (int) ($_GET['saat_id'] ?? 0);

if (empty($tarih) || $saat_id <= 0) {
    echo json_encode([]);
    exit;
}

// 1. Seçilen saat_id'nin saat string karşılığını bul (örn: '09:00:00')
$stmt_saat = $pdo->prepare("SELECT saat FROM saatler WHERE id = ?");
$stmt_saat->execute([$saat_id]);
$secilen_saat = $stmt_saat->fetchColumn();

if (!$secilen_saat) {
    echo json_encode([]);
    exit;
}

// 2. O gün ve o saatte randevusu (iptal edilmemiş) OLMAYAN memurları çek
$sql = "
    SELECT p.id, p.ad, p.soyad 
    FROM personeller p
    WHERE p.aktif = 1 
      AND p.rol = 'personel'
      AND p.id NOT IN (
          SELECT r.personel_id 
          FROM randevular r
          JOIN saatler s ON r.saat_id = s.id
          WHERE r.tarih = :tarih 
            AND s.saat = :secilen_saat
            AND r.durum != 'iptal'
      )
    ORDER BY p.ad ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':tarih'        => $tarih,
    ':secilen_saat' => $secilen_saat
]);

$memurlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($memurlar);