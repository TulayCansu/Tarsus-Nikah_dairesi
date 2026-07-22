-- =====================================================
-- resmitatiller tablosu için 2026 yılı resmi tatil verileri
-- Tablo yapısı (sql/nikah_randevu.sql içinde tanımlı):
--   id INT AUTO_INCREMENT, tarih DATE, aciklama VARCHAR(100)
--
-- Kullanım: phpMyAdmin > nikah_randevu veritabanı > SQL sekmesi
-- üzerinden bu dosyanın içeriğini çalıştırman yeterli.
-- Zaten kayıt varsa çakışmayı önlemek için önce mevcut
-- 2026 kayıtlarını temizliyoruz.
-- =====================================================

DELETE FROM `resmitatiller` WHERE YEAR(`tarih`) = 2026;

INSERT INTO `resmitatiller` (`tarih`, `aciklama`) VALUES
('2026-01-01', 'Yılbaşı'),
('2026-03-19', 'Ramazan Bayramı Arifesi (yarım gün)'),
('2026-03-20', 'Ramazan Bayramı 1. Gün'),
('2026-03-21', 'Ramazan Bayramı 2. Gün'),
('2026-03-22', 'Ramazan Bayramı 3. Gün'),
('2026-04-23', 'Ulusal Egemenlik ve Çocuk Bayramı'),
('2026-05-01', 'Emek ve Dayanışma Günü'),
('2026-05-19', 'Atatürk\'ü Anma, Gençlik ve Spor Bayramı'),
('2026-05-26', 'Kurban Bayramı Arifesi (yarım gün)'),
('2026-05-27', 'Kurban Bayramı 1. Gün'),
('2026-05-28', 'Kurban Bayramı 2. Gün'),
('2026-05-29', 'Kurban Bayramı 3. Gün'),
('2026-05-30', 'Kurban Bayramı 4. Gün'),
('2026-07-15', 'Demokrasi ve Millî Birlik Günü'),
('2026-08-30', 'Zafer Bayramı'),
('2026-10-28', 'Cumhuriyet Bayramı Arifesi (yarım gün)'),
('2026-10-29', 'Cumhuriyet Bayramı');
