<?php

require_once '../../config/database.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AJAX DURUM GÜNCELLEME KONTROLÜ 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    header('Content-Type: application/json');
    
    $personelId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $yeniDurum = isset($_POST['durum']) ? intval($_POST['durum']) : 0;

    if ($personelId > 0) {
        try {
            
            $sorgu = $pdo->prepare("UPDATE personeller SET aktif = :aktif WHERE id = :id");
            $guncellendi = $sorgu->execute([
                'aktif' => $yeniDurum,
                'id' => $personelId
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
        echo json_encode(['success' => false, 'message' => 'Geçersiz personel ID.']);
    }
    exit; 
}

//FİLTRELEME VE SIRALAMA PARAMETRELERİNE GÖRE VERİ ÇEKME    
try {
    $arama = isset($_GET['ara']) ? trim($_GET['ara']) : '';
    $rol_filtre = isset($_GET['rol']) ? trim($_GET['rol']) : '';
    $sirala = isset($_GET['sirala']) ? trim($_GET['sirala']) : 'id_asc';

    // Tablonuzdaki sütun adlarına göre SQL sorgusu hazırlandı
    $sql = "SELECT id, ad, soyad, kullanici_adi, aktif, rol FROM personeller WHERE 1=1";
    $params = [];

    if ($arama !== '') {
        $sql .= " AND (ad LIKE :arama OR soyad LIKE :arama OR kullanici_adi LIKE :arama)";
        $params['arama'] = '%' . $arama . '%';
    }

    if ($rol_filtre !== '') {
        $sql .= " AND rol = :rol";
        $params['rol'] = $rol_filtre;
    }

    switch ($sirala) {
        case 'ad_asc':
            $sql .= " ORDER BY ad ASC";
            break;
        case 'ad_desc':
            $sql .= " ORDER BY ad DESC";
            break;
        case 'id_desc':
            $sql .= " ORDER BY id DESC";
            break;
        case 'id_asc':
        default:
            $sql .= " ORDER BY id ASC";
            break;
    }

    $sorgu = $pdo->prepare($sql);
    $sorgu->execute($params);
    $personeller = $sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Yönetimi - Tarsus Belediyesi</title>
    <link rel="stylesheet" href="../../assets/css/personeller.css"> 
</head>
<body>

    <div class="layout">
        
        <?php include '../../includes/sidebar.php'; ?>

        <!-- Ana İçerik Alanı -->
        <main class="content-container">
            <header class="content-header">
                <h2>Personel Yönetimi</h2>
                <p>Sistem personellerini listeleyebilir, arayabilir ve durumlarını güncelleyebilirsiniz.</p>
            </header>

            <!-- Filtreleme ve Arama Alanı -->
            <form method="GET" action="personeller.php" class="filter-card">
                <div class="filter-group search-group">
                    <label for="ara">Personel Ara</label>
                    <input type="text" id="ara" name="ara" placeholder="Ad, soyad veya kullanıcı adı..." value="<?php echo htmlspecialchars($arama); ?>">
                </div>

                <div class="filter-group">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol">
                        <option value="">Tümü</option>
                        <option value="admin" <?php echo $rol_filtre === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="personel" <?php echo $rol_filtre === 'personel' ? 'selected' : ''; ?>>Personel</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sirala">Sıralama</label>
                    <select id="sirala" name="sirala">
                        <option value="id_asc" <?php echo $sirala === 'id_asc' ? 'selected' : ''; ?>>Varsayılan (ID Artan)</option>
                        <option value="id_desc" <?php echo $sirala === 'id_desc' ? 'selected' : ''; ?>>ID Azalan</option>
                        <option value="ad_asc" <?php echo $sirala === 'ad_asc' ? 'selected' : ''; ?>>İsim (A-Z)</option>
                        <option value="ad_desc" <?php echo $sirala === 'ad_desc' ? 'selected' : ''; ?>>İsim (Z-A)</option>
                    </select>
                </div>

                <div class="filter-group" style="flex: 0 0 auto; justify-content: flex-end;">
                    <button type="submit" style="height: 40px; padding: 0 20px; background: #2a5aa8; color: #fff; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 13px;">Filtrele</button>
                </div>
            </form>

            <!-- Personel Tablo Kartı -->
            <div class="table-card">
                <table class="personel-tablosu">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>Kullanıcı Adı</th>
                            <th>Rol</th>
                            <th>Durum</th>
                            <th style="text-align: right; padding-right: 32px;">İşlem (Aktif/Pasif)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($personeller)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 32px;">Aranan kriterlere uygun personel bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($personeller as $personel): ?>
                                <tr id="personel-row-<?php echo $personel['id']; ?>">
                                    <td>#<?php echo $personel['id']; ?></td>
                                    <td class="personel-isim-td">
                                        <?php echo htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($personel['kullanici_adi']); ?></td>
                                    <td>
                                        <span class="table-rol-badge"><?php echo htmlspecialchars($personel['rol']); ?></span>
                                    </td>
                                    <td>
                                        <span class="durum-rozet <?php echo $personel['aktif'] == 1 ? 'durum-aktif' : 'durum-pasif'; ?>" id="rozet-<?php echo $personel['id']; ?>">
                                            <?php echo $personel['aktif'] == 1 ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 32px;">
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   class="status-toggle" 
                                                   data-id="<?php echo $personel['id']; ?>" 
                                                   <?php echo $personel['aktif'] == 1 ? 'checked' : ''; ?>>
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
                const personelId = this.getAttribute("data-id");
                const yeniDurum = this.checked ? 1 : 0;

                fetch("personeller.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `action=toggle&id=${personelId}&durum=${yeniDurum}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const rozet = document.getElementById(`rozet-${personelId}`);

                        if (yeniDurum === 1) {
                            rozet.textContent = "Aktif";
                            rozet.className = "durum-rozet durum-aktif";
                        } else {
                            rozet.textContent = "Pasif";
                            rozet.className = "durum-rozet durum-pasif";
                        }
                    } else {
                        alert("Hata: " + data.message);
                        this.checked = !this.checked; 
                    }
                })
                .catch(error => {
                    console.error("Hata:", error);
                    alert("İşlem sırasında bir hata oluştu.");
                    this.checked = !this.checked; 
                });
            });
        });
    });
    </script>
</body>
</html>