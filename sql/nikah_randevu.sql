-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 13 Tem 2026, 09:58:58
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `nikah_randevu`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bakimgünleri`
--

CREATE TABLE `bakimgünleri` (
  `id` int(11) NOT NULL,
  `salon_id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `neden` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `loglar`
--

CREATE TABLE `loglar` (
  `id` int(11) NOT NULL,
  `personel_id` int(11) NOT NULL,
  `islem` varchar(100) NOT NULL,
  `tarih` datetime NOT NULL,
  `ip` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personeller`
--

CREATE TABLE `personeller` (
  `id` int(11) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `soyad` varchar(50) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `aktif` tinyint(1) NOT NULL,
  `rol` enum('admin','personel') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `randevular`
--

CREATE TABLE `randevular` (
  `id` int(11) NOT NULL,
  `gelin_adi` varchar(50) NOT NULL,
  `gelin_soyad` varchar(50) NOT NULL,
  `gelin_TC` char(11) NOT NULL,
  `gelin_tel` varchar(15) NOT NULL,
  `damat_adi` varchar(50) NOT NULL,
  `damat_soyad` varchar(50) NOT NULL,
  `damat_TC` char(11) NOT NULL,
  `damat_tel` varchar(15) NOT NULL,
  `tarih` date NOT NULL,
  `saat_id` int(11) NOT NULL,
  `salon_id` int(11) NOT NULL,
  `personel_id` int(11) NOT NULL,
  `durum` varchar(20) NOT NULL,
  `olusturma_tarihi` datetime NOT NULL,
  `guncelleme_tarihi` datetime NOT NULL,
  `iptal_nedeni` varchar(255) NOT NULL,
  `odeme_durumu` enum('ödendi','ödenmedi') NOT NULL,
  `odeme_tutari` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `resmitatiller`
--

CREATE TABLE `resmitatiller` (
  `id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `saatler`
--

CREATE TABLE `saatler` (
  `id` int(11) NOT NULL,
  `saat` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `salonlar`
--

CREATE TABLE `salonlar` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `kapasite` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bakimgünleri`
--
ALTER TABLE `bakimgünleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salon_id` (`salon_id`);

--
-- Tablo için indeksler `loglar`
--
ALTER TABLE `loglar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personel_id` (`personel_id`);

--
-- Tablo için indeksler `personeller`
--
ALTER TABLE `personeller`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `randevular`
--
ALTER TABLE `randevular`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tarih` (`tarih`,`saat_id`,`salon_id`),
  ADD KEY `salon_id` (`salon_id`),
  ADD KEY `personel_id` (`personel_id`),
  ADD KEY `saat_id` (`saat_id`);

--
-- Tablo için indeksler `resmitatiller`
--
ALTER TABLE `resmitatiller`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `saatler`
--
ALTER TABLE `saatler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `salonlar`
--
ALTER TABLE `salonlar`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bakimgünleri`
--
ALTER TABLE `bakimgünleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `loglar`
--
ALTER TABLE `loglar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `personeller`
--
ALTER TABLE `personeller`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `randevular`
--
ALTER TABLE `randevular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `resmitatiller`
--
ALTER TABLE `resmitatiller`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `saatler`
--
ALTER TABLE `saatler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `salonlar`
--
ALTER TABLE `salonlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `bakimgünleri`
--
ALTER TABLE `bakimgünleri`
  ADD CONSTRAINT `bakimgünleri_ibfk_1` FOREIGN KEY (`salon_id`) REFERENCES `salonlar` (`id`);

--
-- Tablo kısıtlamaları `loglar`
--
ALTER TABLE `loglar`
  ADD CONSTRAINT `loglar_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`);

--
-- Tablo kısıtlamaları `randevular`
--
ALTER TABLE `randevular`
  ADD CONSTRAINT `randevular_ibfk_1` FOREIGN KEY (`salon_id`) REFERENCES `salonlar` (`id`),
  ADD CONSTRAINT `randevular_ibfk_2` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`),
  ADD CONSTRAINT `randevular_ibfk_3` FOREIGN KEY (`saat_id`) REFERENCES `saatler` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
