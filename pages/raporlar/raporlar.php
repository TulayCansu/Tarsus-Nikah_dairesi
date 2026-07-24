<?php
require_once '../../includes/auth.php';
yetkiKontrol('admin');
require_once '../../config/database.php'; 

// Filtreleme Parametreleri (Tarih Aralığı)
$baslangic = $_GET['baslangic'] ?? '';
$bitis = $_GET['bitis'] ?? '';

// Tarih koşulu oluşturma
$tarih_kosulu = "";
$params = [];

if (!empty($baslangic) && !empty($bitis)) {
    $tarih_kosulu = " AND tarih BETWEEN :baslangic AND :bitis ";
    $params[':baslangic'] = $baslangic;
    $params[':bitis'] = $bitis;
}

// İstatistik Kartları Verisi
$kart_sorgu = "SELECT 
    SUM(CASE WHEN odeme_durumu = 'ödendi' THEN odeme_tutari ELSE 0 END) AS toplam_gelir,
    SUM(CASE WHEN durum = 'kıyıldı' THEN 1 ELSE 0 END) AS kiyilan_nikah,
    SUM(CASE WHEN odeme_durumu = 'ödenmedi' THEN odeme_tutari ELSE 0 END) AS beklenen_odeme,
    SUM(CASE WHEN durum = 'bekliyor' THEN 1 ELSE 0 END) AS bekleyen_nikah,
    SUM(CASE WHEN durum = 'iptal' THEN 1 ELSE 0 END) AS iptal_edilen_nikah
FROM randevular WHERE 1=1" . $tarih_kosulu;

$stmt = $pdo->prepare($kart_sorgu);
$stmt->execute($params);
$kartlar = $stmt->fetch(PDO::FETCH_ASSOC);

// Salon Dağılım Grafiği Verisi
$salon_sorgu = "SELECT s.ad AS salon_adi, COUNT(r.id) AS toplam_nikah
FROM randevular r
JOIN salonlar s ON r.salon_id = s.id
WHERE 1=1" . str_replace('tarih', 'r.tarih', $tarih_kosulu) . "
GROUP BY r.salon_id";

$stmt = $pdo->prepare($salon_sorgu);
$stmt->execute($params);
$salon_verileri = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Personel Dağılım Verisi
$personel_sorgu = "SELECT CONCAT(p.ad, ' ', p.soyad) AS personel, COUNT(r.id) AS nikah_sayisi
FROM randevular r
JOIN personeller p ON r.personel_id = p.id
WHERE 1=1" . str_replace('tarih', 'r.tarih', $tarih_kosulu) . "
GROUP BY r.personel_id";

$stmt = $pdo->prepare($personel_sorgu);
$stmt->execute($params);
$personel_verileri = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sorunlu / Tarihi Geçen Randevular
$gecmis_sorgu = "SELECT r.id, r.gelin_adi, r.gelin_soyad, r.damat_adi, r.damat_soyad, r.tarih, r.durum
FROM randevular r
WHERE r.tarih < CURDATE() AND r.durum NOT IN ('kıyıldı', 'iptal')" . str_replace('tarih', 'r.tarih', $tarih_kosulu);

$stmt = $pdo->prepare($gecmis_sorgu);
$stmt->execute($params);
$gecmis_randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Raporlar - Nikah Randevu Sistemi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/raporlar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="layout">
    
    <?php include '../../includes/sidebar.php'; ?>

    <main class="main-content">
        
        <header class="content-header">
            <div class="header-title">
                <h1>Sistem Raporları</h1>
                <p>Nikah istatistikleri ve finansal durum özeti</p>
            </div>
            
            <form method="GET" class="filter-form">
                <div class="input-group">
                    <label>Başlangıç:</label>
                    <input type="date" name="baslangic" value="<?php echo htmlspecialchars($baslangic); ?>">
                </div>
                <div class="input-group">
                    <label>Bitiş:</label>
                    <input type="date" name="bitis" value="<?php echo htmlspecialchars($bitis); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrele</button>
                <?php if(!empty($baslangic) || !empty($bitis)): ?>
                    <a href="raporlar.php" class="btn btn-secondary">Temizle</a>
                <?php endif; ?>
                <button type="button" onclick="pencereyiYazdir()" class="btn btn-export">🖨️ Dışa Aktar / Yazdır</button>
            </form>
        </header>

        <!-- İstatistik Kartları -->
        <section class="cards-grid">
            <div class="report-card card-gelir">
                <div class="card-icon">💰</div>
                <div class="card-info">
                    <h3>Toplam Gelir</h3>
                    <p class="card-value"><?php echo number_format($kartlar['toplam_gelir'] ?? 0, 2, ',', '.'); ?> TL</p>
                </div>
            </div>
            <div class="report-card card-kiyilan">
                <div class="card-icon">💍</div>
                <div class="card-info">
                    <h3>Kıyılan Nikah</h3>
                    <p class="card-value"><?php echo $kartlar['kiyilan_nikah'] ?? 0; ?></p>
                </div>
            </div>
            <div class="report-card card-bekleyen-odeme">
                <div class="card-icon">⏳</div>
                <div class="card-info">
                    <h3>Beklenen Ödemeler</h3>
                    <p class="card-value"><?php echo number_format($kartlar['beklenen_odeme'] ?? 0, 2, ',', '.'); ?> TL</p>
                </div>
            </div>
            <div class="report-card card-bekleyen-nikah">
                <div class="card-icon">📅</div>
                <div class="card-info">
                    <h3>Bekleyen Nikah</h3>
                    <p class="card-value"><?php echo $kartlar['bekleyen_nikah'] ?? 0; ?></p>
                </div>
            </div>
            <div class="report-card card-iptal">
                <div class="card-icon">❌</div>
                <div class="card-info">
                    <h3>İptal Edilenler</h3>
                    <p class="card-value"><?php echo $kartlar['iptal_edilen_nikah'] ?? 0; ?></p>
                </div>
            </div>
        </section>

        <!-- Grafikler ve Sayısal Dağılımlar -->
        <section class="charts-section">
            <div class="chart-container">
                <h3>🏛️ Salonlara Göre Dağılım</h3>
                <canvas id="salonChart"></canvas>
            </div>
            
            <div class="table-container-mini">
                <h3>👥 Personel Görev Dağılımı</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Personel Adı Soyadı</th>
                            <th>Kıydığı/Kıyacağı Nikah Sayısı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($personel_verileri)): ?>
                            <tr><td colspan="2" class="text-center">Veri bulunamadı.</td></tr>
                        <?php else: ?>
                            <?php foreach($personel_verileri as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['personel']); ?></strong></td>
                                    <td><?php echo $p['nikah_sayisi']; ?> Nikah</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tarihi Geçen Geçmiş Randevular Tablosu -->
        <section class="table-section">
            <div class="table-header-title">
                <h3>⚠️ Tarihi Geçen / Durumu Güncellenmemiş Randevular</h3>
                <p>Tarihi bugünden eski olan ancak sistemsel olarak 'Kıyıldı' ya da 'İptal' olarak kapatılmamış işlemler.</p>
            </div>
            <div class="table-container-full">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gelin</th>
                            <th>Damat</th>
                            <th>Nikah Tarihi</th>
                            <th>Mevcut Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($gecmis_randevular)): ?>
                            <tr><td colspan="5" class="text-center-success">🎉 İşlemi sarkan geçmiş randevu bulunmamaktadır.</td></tr>
                        <?php else: ?>
                            <?php foreach($gecmis_randevular as $g): ?>
                                <tr>
                                    <td>#<?php echo $g['id']; ?></td>
                                    <td><?php echo htmlspecialchars($g['gelin_adi'] . ' ' . $g['gelin_soyad']); ?></td>
                                    <td><?php echo htmlspecialchars($g['damat_adi'] . ' ' . $g['damat_soyad']); ?></td>
                                    <td><span class="date-badge"><?php echo date('d.m.Y', strtotime($g['tarih'])); ?></span></td>
                                    <td><span class="status-badge alert"><?php echo htmlspecialchars($g['durum']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<script>
// Dışa Aktar / Yazdır Fonksiyonu
function pencereyiYazdir() {
    window.print();
}

// Chart.js Yapılandırması (Salon Dağılımı Grafiği)
const salonData = <?php echo json_encode($salon_verileri); ?>;
const labels = salonData.map(item => item.salon_adi);
const counts = salonData.map(item => item.toplam_nikah);

const ctx = document.getElementById('salonChart').getContext('2d');
new Chart(ctx, {
    type: 'bar', // Grafik türü (Bar grafiği)
    data: {
        labels: labels,
        datasets: [{
            label: 'Nikah Sayısı',
            data: counts,
            backgroundColor: 'rgba(42, 90, 168, 0.8)',
            borderColor: '#2a5aa8',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>
</body>
</html>