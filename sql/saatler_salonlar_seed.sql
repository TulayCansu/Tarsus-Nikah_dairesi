-- =====================================================
-- saatler ve salonlar tabloları için GEÇİCİ/DENEME verisi
-- Gerçek veriler netleşince buradaki isim, kapasite ve
-- saatleri düzenlemen yeterli (Salonlar/Ayarlar sayfasından
-- ya da doğrudan phpMyAdmin > Düzenle üzerinden).
-- =====================================================

-- --- SAATLER (09:00 - 17:00 arası, saatte bir randevu slotu) ---
DELETE FROM `saatler`;
INSERT INTO `saatler` (`saat`) VALUES
('09:00:00'),
('10:00:00'),
('11:00:00'),
('12:00:00'),
('13:00:00'),
('14:00:00'),
('15:00:00'),
('16:00:00'),
('17:00:00');

-- --- SALONLAR ---
DELETE FROM `salonlar`;
INSERT INTO `salonlar` (`ad`, `kapasite`, `aktif`) VALUES
('1 Nolu Nikah Salonu', 100, 1),
('2 Nolu Nikah Salonu', 150, 1),
('VIP Salon', 50, 1);
