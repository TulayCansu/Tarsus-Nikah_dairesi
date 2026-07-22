-- =====================================================
-- resmitatiller tablosuna her_yil_tekrar sütununu ekler
-- (Yusuf'un veritabanında zaten mevcutsa hata vermez, atlar)
-- ve daha önce eklenen 2026 verilerini sabit/değişken
-- olarak işaretler.
--
-- her_yil_tekrar = 1  -> Sabit millî bayram, her yıl aynı
--                        ay-gün için otomatik gösterilir
--                        (1 Ocak, 23 Nisan, 1 Mayıs, 19 Mayıs,
--                        15 Temmuz, 30 Ağustos, 29 Ekim ve arifesi)
-- her_yil_tekrar = 0  -> Dini bayram (Ramazan/Kurban), yıldan
--                        yıla tarihi kaydığı için sadece o yıl
--                        için geçerli, her yıl yeniden girilmeli
-- =====================================================

ALTER TABLE `resmitatiller`
  ADD COLUMN IF NOT EXISTS `her_yil_tekrar` TINYINT(1) NOT NULL DEFAULT 0;

UPDATE `resmitatiller` SET `her_yil_tekrar` = 1
WHERE `tarih` IN (
  '2026-01-01', -- Yılbaşı
  '2026-04-23', -- Ulusal Egemenlik ve Çocuk Bayramı
  '2026-05-01', -- Emek ve Dayanışma Günü
  '2026-05-19', -- Atatürk'ü Anma, Gençlik ve Spor Bayramı
  '2026-07-15', -- Demokrasi ve Millî Birlik Günü
  '2026-08-30', -- Zafer Bayramı
  '2026-10-28', -- Cumhuriyet Bayramı Arifesi (yarım gün)
  '2026-10-29'  -- Cumhuriyet Bayramı
);

UPDATE `resmitatiller` SET `her_yil_tekrar` = 0
WHERE `tarih` IN (
  '2026-03-19','2026-03-20','2026-03-21','2026-03-22', -- Ramazan Bayramı (+ arife)
  '2026-05-26','2026-05-27','2026-05-28','2026-05-29','2026-05-30' -- Kurban Bayramı (+ arife)
);
