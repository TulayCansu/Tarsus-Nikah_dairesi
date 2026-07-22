<?php
session_start();
require_once '../../config/database.php'; // Veritabanı bağlantısı ($pdo)

// Oturum kontrolü
if (!isset($_SESSION['personel_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$personel_id = $_SESSION['personel_id'];
$mesaj = '';
$hata = '';

// --- FORM İŞLEMLERİ ---

// 1. ŞİFRE DEĞİŞTİRME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['islem']) && $_POST['islem'] === 'sifre_guncelle') {
    $mevcut_sifre = $_POST['mevcut_sifre'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    if (empty($mevcut_sifre) || empty($yeni_sifre) || empty($yeni_sifre_tekrar)) {
        $hata = 'Lütfen tüm alanları doldurun.';
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        $hata = 'Yeni şifreler birbiriyle uyuşmuyor.';
    } else {
        $stmt = $pdo->prepare("SELECT sifre FROM personeller WHERE id = ?");
        $stmt->execute([$personel_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mevcut_sifre, $user['sifre'])) {
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE personeller SET sifre = ? WHERE id = ?");
            $update->execute([$yeni_hash, $personel_id]);
            $mesaj = 'Şifreniz başarıyla güncellendi.';
        } else {
            $hata = 'Mevcut şifreniz hatalı.';
        }
    }
}

// 2. SALON MESAİ / RANDEVU SAATLERİ GÜNCELLEME (Sadece Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['islem']) && $_POST['islem'] === 'saat_guncelle') {
    if (($_SESSION['rol'] ?? '') === 'admin') {
        $secili_salon_id = intval($_POST['salon_id'] ?? 0);
        $secili_saat_idleri = $_POST['saatler'] ?? []; // İşaretlenen saat_id listesi

        if ($secili_salon_id > 0) {
            try {
                $pdo->beginTransaction();

                // 1. O salona ait TÜM saatleri önce pasif (0) yap
                $reset = $pdo->prepare("UPDATE saatler SET aktif = 0 WHERE salon_id = ?");
                $reset->execute([$secili_salon_id]);

                // 2. Seçilen saat_id'lerini aktif (1) yap
                if (!empty($secili_saat_idleri)) {
                    $in_clause = implode(',', array_map('intval', $secili_saat_idleri));
                    $update = $pdo->prepare("UPDATE saatler SET aktif = 1 WHERE salon_id = ? AND id IN ($in_clause)");
                    $update->execute([$secili_salon_id]);
                }

                $pdo->commit();
                $mesaj = 'Salon randevu saatleri başarıyla güncellendi.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $hata = 'Saatler güncellenirken hata oluştu: ' . $e->getMessage();
            }
        } else {
            $hata = 'Lütfen geçerli bir salon seçin.';
        }
    } else {
        $hata = 'Bu işlemi yapmaya yetkiniz yok.';
    }
}

// --- VERİLERİ ÇEKME ---
$salonlar = $pdo->query("SELECT * FROM salonlar WHERE aktif = 1 ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

// Tüm saatleri salon_id bazlı gruplayarak çekiyoruz
$tum_saatler = $pdo->query("SELECT id, salon_id, TIME_FORMAT(saat, '%H:%i') as saat_fmt, aktif FROM saatler ORDER BY saat ASC")->fetchAll(PDO::FETCH_ASSOC);

$salon_saatleri_json = [];
foreach ($tum_saatler as $st) {
    $salon_saatleri_json[$st['salon_id']][] = [
        'id' => $st['id'],
        'saat' => $st['saat_fmt'],
        'aktif' => intval($st['aktif'])
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Nikah Randevu Sistemi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/ayarlar.css">
</head>
<body>

<div class="layout">
    <!-- Sidebar Include -->
    <?php include '../../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="page-title">
                <h1>Sistem Ayarları</h1>
                <p>Güvenlik ayarlarınızı ve salonların randevu saat dilimlerini yönetin.</p>
            </div>
        </header>

        <?php if (!empty($mesaj)): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($mesaj); ?></div>
        <?php endif; ?>

        <?php if (!empty($hata)): ?>
            <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <!-- Tab Navigasyonu -->
        <div class="settings-tabs">
            <button class="tab-btn active" onclick="openTab(event, 'sifre-tab')">🔑 Şifre Değiştir</button>
            <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
                <button class="tab-btn" onclick="openTab(event, 'mesai-tab')">🏛️ Salon Randevu & Mesai Saatleri</button>
            <?php endif; ?>
        </div>

        <!-- TAB 1: Şifre Değiştirme -->
        <div id="sifre-tab" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h3>Şifre Güncelleme</h3>
                    <p>Hesap güvenliğiniz için şifrenizi buradan güncelleyebilirsiniz.</p>
                </div>
                <form action="" method="POST" class="settings-form">
                    <input type="hidden" name="islem" value="sifre_guncelle">
                    
                    <div class="form-group">
                        <label for="mevcut_sifre">Mevcut Şifre</label>
                        <input type="password" id="mevcut_sifre" name="mevcut_sifre" placeholder="••••••••" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="yeni_sifre">Yeni Şifre</label>
                            <input type="password" id="yeni_sifre" name="yeni_sifre" placeholder="••••••••" required>
                        </div>
                        <div class="form-group">
                            <label for="yeni_sifre_tekrar">Yeni Şifre (Tekrar)</label>
                            <input type="password" id="yeni_sifre_tekrar" name="yeni_sifre_tekrar" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 2: SALON MESAİ & RANDEVU SAATLERİ (Admin) -->
        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
        <div id="mesai-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>Salon Bazlı Randevu Saat Paneli</h3>
                    <p>Düzenlemek istediğiniz salonu seçin ve aktif/pasif olmasını istediğiniz saatleri işaretleyin.</p>
                </div>

                <form action="" method="POST" class="settings-form">
                    <input type="hidden" name="islem" value="saat_guncelle">

                    <!-- Salon Seçim Sekmeleri (Pills) -->
                    <div class="salon-selector-container">
                        <label class="section-label">1. Salon Seçimi Yapın</label>
                        <div class="salon-pills">
                            <?php foreach ($salonlar as $index => $salon): ?>
                                <label class="salon-pill-item">
                                    <input type="radio" name="salon_id" value="<?php echo $salon['id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?> onchange="renderSalonHours(this.value)">
                                    <div class="salon-pill-card">
                                        <span class="salon-icon">🏛️</span>
                                        <div class="salon-info">
                                            <span class="salon-name"><?php echo htmlspecialchars($salon['ad']); ?></span>
                                            <span class="salon-capacity"><?php echo $salon['kapasite']; ?> Kişilik</span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Saat Seçim Izgarası -->
                    <div class="time-slots-container">
                        <div class="time-slots-header">
                            <label class="section-label">2. Aktif Randevu Saatlerini Belirleyin</label>
                            <div class="quick-select-buttons">
                                <button type="button" class="btn-link" onclick="tumunuSec(true)">Tümünü Seç</button>
                                <span>•</span>
                                <button type="button" class="btn-link" onclick="tumunuSec(false)">Tümünü Kaldır</button>
                            </div>
                        </div>

                        <!-- JS Tarafından Dinamik Doldurulacak Alan -->
                        <div class="time-grid" id="time-grid-container">
                        </div>
                    </div>

                    <div class="form-actions border-top">
                        <button type="submit" class="btn btn-primary btn-lg">
                            💾 Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<script>
// PHP'den çekilen tüm salon-saat haritası
const salonSaatleriData = <?php echo json_encode($salon_saatleri_json); ?>;

// Sekme Değiştirme
function openTab(evt, tabName) {
    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}

// Seçilen Salonun Saatlerini HTML Olarak Oluşturma
function renderSalonHours(salonId) {
    const gridContainer = document.getElementById('time-grid-container');
    gridContainer.innerHTML = ''; // Temizle

    const hoursList = salonSaatleriData[salonId] || [];

    if (hoursList.length === 0) {
        gridContainer.innerHTML = '<p class="no-data">Bu salona ait tanımlı saat bulunamadı.</p>';
        return;
    }

    hoursList.forEach(item => {
        const isChecked = item.aktif === 1 ? 'checked' : '';
        
        const label = document.createElement('label');
        label.className = 'time-card';
        label.innerHTML = `
            <input type="checkbox" name="saatler[]" value="${item.id}" ${isChecked}>
            <div class="time-card-content">
                <span class="clock-icon">🕒</span>
                <span class="time-text">${item.saat}</span>
                <span class="status-badge"></span>
            </div>
        `;
        gridContainer.appendChild(label);
    });
}

// Tümünü Seç / Kaldır
function tumunuSec(durum) {
    const checkboxes = document.querySelectorAll('.time-grid input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = durum);
}

// Sayfa ilk yüklendiğinde varsayılan seçili salonun saatlerini ekrana bas
document.addEventListener("DOMContentLoaded", function() {
    const selectedSalon = document.querySelector('input[name="salon_id"]:checked');
    if (selectedSalon) {
        renderSalonHours(selectedSalon.value);
    }
});
</script>

</body>
</html>