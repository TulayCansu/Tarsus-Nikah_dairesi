<?php

require_once '../../config/database.php'; 

// Eğer sayfaya gelen istek bir POST isteğiyse ve durum güncelleme amaçlıysa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    // Yanıtın JSON formatında olacağını belirtiyoruz
    header('Content-Type: application/json');
    
    $salonId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $yeniDurum = isset($_POST['durum']) ? intval($_POST['durum']) : 0; // 1 (aktif) veya 0 (pasif)

    if ($salonId > 0) {
        try {
            
            $sorgu = $pdo->prepare("UPDATE salonlar SET aktif = :aktif WHERE id = :id");
            $durumGuncellendi = $sorgu->execute([
                'aktif' => $yeniDurum,
                'id' => $salonId
            ]);

            if ($durumGuncellendi) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Salon durumu başarıyla güncellendi.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Güncelleme işlemi gerçekleştirilemedi.'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz salon ID.'
        ]);
    }
    // AJAX isteği bittiği için sayfanın geri kalanının yüklenmesini engelliyoruz
    exit; 
}

// 2. SAYFA YÜKLENİRKEN VERİLERİ ÇEKME
try {
    
    $sorgu = $pdo->query("SELECT id, ad, kapasite, aktif FROM salonlar ORDER BY id ASC");
    $salonlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Veritabanı bağlantı veya sorgu hatası: " . $e->getMessage());
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
                <p>Kayıtlı salonları listeleyebilir, durum kontrolünü anlık olarak tablodan değiştirebilirsiniz.</p>
            </header>

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
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 32px;">Kayıtlı salon bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salonlar as $salon): ?>
                                <tr id="salon-row-<?php echo $salon['id']; ?>">
                                    <td>#<?php echo $salon['id']; ?></td>
                                    <td class="salon-adi-td"><?php echo htmlspecialchars($salon['ad']); ?></td>
                                    <td>
                                        <span class="kapasite-badge"><?php echo $salon['kapasite']; ?> Kişi</span>
                                    </td>
                                    <td>
                                        <span class="durum-rozet <?php echo $salon['aktif'] == 1 ? 'durum-aktif' : 'durum-pasif'; ?>" id="rozet-<?php echo $salon['id']; ?>">
                                            <?php echo $salon['aktif'] == 1 ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 32px;">
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

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const statusToggles = document.querySelectorAll(".status-toggle");

        statusToggles.forEach(toggle => {
            toggle.addEventListener("change", function() {
                const salonId = this.getAttribute("data-id");
                const yeniDurum = this.checked ? 1 : 0;

                // İstek doğrudan bulunduğumuz 'salonlar.php' sayfasına atılıyor
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
                        this.checked = !this.checked; // Başarısızlıkta switch butonunu eski konumuna geri çek
                    }
                })
                .catch(error => {
                    console.error("Hata:", error);
                    alert("İşlem sırasında bir sistem hatası oluştu.");
                    this.checked = !this.checked; // Bağlantı hatasında switch butonunu eski konumuna geri çek
                });
            });
        });
    });
    </script>
</body>
</html>