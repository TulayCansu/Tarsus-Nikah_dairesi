<?php
require_once '../../includes/auth.php';
yetkiKontrol('admin');
require_once '../../config/database.php'; 

// 2. AJAX DURUM GÜNCELLEME KONTROLÜ (POST İsteği - $pdo kullanıldı)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    header('Content-Type: application/json');
    
    $salonId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $yeniDurum = isset($_POST['durum']) ? intval($_POST['durum']) : 0; // 1 veya 0

    if ($salonId > 0) {
        try {
            // Sütun adı 'aktif' ve bağlantı değişkeni $pdo olarak güncellendi
            $sorgu = $pdo->prepare("UPDATE salonlar SET aktif = :aktif WHERE id = :id");
            $guncellendi = $sorgu->execute([
                'aktif' => $yeniDurum,
                'id' => $salonId
            ]);

            if ($guncellendi) {
                echo json_encode(['success' => true, 'message' => 'Durum güncellendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Güncelleme yapılamadı.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz salon ID.']);
    }
    exit; 
}

// 3. SALON VERİLERİNİ ÇEKME
try {
    // Tüm salonları id sırasına göre düz bir şekilde çekiyoruz
    $sorgu = $pdo->query("SELECT id, ad, kapasite, aktif FROM salonlar ORDER BY id ASC");
    $salonlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Yönetimi - Tarsus Belediyesi</title>
    <link rel="stylesheet" href="../../assets/css/salonlar.css"> 
</head>
<body>

    <div class="layout">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="content-container">
            <header class="content-header">
                <h2>Salon Yönetimi</h2>
                <p>Kayıtlı nikah salonlarını listeleyebilir ve durum kontrolünü anlık olarak değiştirebilirsiniz.</p>
            </header>

            <!-- Salon Tablo Kartı -->
            <div class="table-card">
                <table class="salon-tablosu">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Salon Adı</th>
                            <th>Kapasite</th>
                            <th>Durum</th>
                            <th style="text-align: right; padding-right: 32px;">İşlem (Aktif/Pasif)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salonlar)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 32px;">Kayıtlı nikah salonu bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salonlar as $salon): ?>
                                <tr id="salon-row-<?php echo $salon['id']; ?>">
                                    <td>#<?php echo $salon['id']; ?></td>
                                    <!-- Veritabanındaki 'ad' sütunu (XSS Korumalı) -->
                                    <td class="salon-adi-td"><?php echo htmlspecialchars($salon['ad']); ?></td>
                                    <td>
                                        <!-- Personel sayfasındaki şık rozet tasarım dili uygulandı -->
                                        <span class="kapasite-badge" style="font-size: 12px; font-weight: 500; color: #0e264a; background: #e2e8f0; padding: 4px 10px; border-radius: 6px; display: inline-block;"><?php echo $salon['kapasite']; ?> Kişi</span>
                                    </td>
                                    <td>
                                        <!-- Veritabanındaki 'aktif' sütununa göre rozet -->
                                        <span class="durum-rozet <?php echo $salon['aktif'] == 1 ? 'durum-aktif' : 'durum-pasif'; ?>" id="rozet-<?php echo $salon['id']; ?>">
                                            <?php echo $salon['aktif'] == 1 ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 32px;">
                                        <!-- Switch Toggle -->
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   class="status-toggle" 
                                                   data-id="<?php echo $salon['id']; ?>" 
                                                   <?php echo $salon['aktif'] == 1 ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- AJAX İşlemleri İçin JavaScript Kodları -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const statusToggles = document.querySelectorAll(".status-toggle");

        statusToggles.forEach(toggle => {
            toggle.addEventListener("change", function() {
                const salonId = this.getAttribute("data-id");
                const yeniDurum = this.checked ? 1 : 0;

                // İstek doğrudan bulunduğumuz 'salonlar.php' sayfasına iletiliyor
                fetch("salonlar.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `action=toggle&id=${salonId}&durum=${yeniDurum}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const rozet = document.getElementById(`rozet-${salonId}`);

                        if (yeniDurum === 1) {
                            rozet.textContent = "Aktif";
                            rozet.className = "durum-rozet durum-aktif";
                        } else {
                            rozet.textContent = "Pasif";
                            rozet.className = "durum-rozet durum-pasif";
                        }
                    } else {
                        alert("Hata: " + data.message);
                        this.checked = !this.checked; // Başarısızlıkta switch'i eski konumuna çek
                    }
                })
                .catch(error => {
                    console.error("Hata:", error);
                    alert("İşlem sırasında bir hata oluştu.");
                    this.checked = !this.checked; // Hata durumunda switch'i eski konumuna çek
                });
            });
        });
    });
    </script>
</body>
</html>