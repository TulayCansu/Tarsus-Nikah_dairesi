<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$salon_id = isset($_GET['salon_id']) ? (int)$_GET['salon_id'] : 0;
$tarih    = isset($_GET['tarih']) ? trim($_GET['tarih']) : '';

if (!$salon_id || !$tarih) {
    echo json_encode([]);
    exit;
}

// 1. Seçilen salonda ve tarihte REZERVE EDİLMİŞ (iptal edilmemiş) saat_id'leri çekiyoruz
$dolu_stmt = $pdo->prepare("
    SELECT saat_id 
    FROM randevular 
    WHERE salon_id = :salon_id AND tarih = :tarih AND durum != 'iptal'
");
$dolu_stmt->execute([
    ':salon_id' => $salon_id,
    ':tarih'    => $tarih
]);
$dolu_saatler = $dolu_stmt->fetchAll(PDO::FETCH_COLUMN);

// 2. SADECE SEÇİLEN SALONA AİT VE AKTİF SAATLERİ ÇEKİYORUZ
if (count($dolu_saatler) > 0) {
    // Dolu saatleri hariç tutan sorgu
    $in_clause = implode(',', array_fill(0, count($dolu_saatler), '?'));
    $params = array_merge([$salon_id], $dolu_saatler);
    
    $saat_stmt = $pdo->prepare("
        SELECT id, saat 
        FROM saatler 
        WHERE salon_id = ? AND aktif = 1 AND id NOT IN ($in_clause) 
        ORDER BY saat ASC
    ");
    $saat_stmt->execute($params);
} else {
    // Dolu saat yoksa seçilen salonun tüm aktif saatlerini getir
    $saat_stmt = $pdo->prepare("
        SELECT id, saat 
        FROM saatler 
        WHERE salon_id = :salon_id AND aktif = 1 
        ORDER BY saat ASC
    ");
    $saat_stmt->execute([':salon_id' => $salon_id]);
}

$uygun_saatler = $saat_stmt->fetchAll(PDO::FETCH_ASSOC);

// HH:MM:SS saat formatını HH:MM haline getiriyoruz
foreach ($uygun_saatler as &$s) {
    $s['saat'] = substr($s['saat'], 0, 5);
}

echo json_encode($uygun_saatler);