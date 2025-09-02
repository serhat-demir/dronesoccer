-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 31 Tem 2025, 13:06:09
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
-- Veritabanı: `dronesoccer`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gruplar`
--

CREATE TABLE `gruplar` (
  `id` int(11) NOT NULL,
  `grup_adi` varchar(255) NOT NULL,
  `kullanici_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `gruplar`
--

INSERT INTO `gruplar` (`id`, `grup_adi`, `kullanici_id`) VALUES
(6, 'Grup A', 1),
(7, 'Grup B', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `kullanici_adi` varchar(30) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `is_admin` int(1) NOT NULL DEFAULT 0,
  `kurum` varchar(255) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `kullanici_adi`, `sifre`, `is_admin`, `kurum`, `telefon`, `is_approved`, `created_at`) VALUES
(1, 'admin', '$2y$10$7MhfCU6QypkgCvLlOTP6GO1bpAscYdKvz/f5TEgQ.AOSl2akNGxr6', 2, NULL, NULL, 1, '2025-07-29 11:34:19');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `scoreboard`
--

CREATE TABLE `scoreboard` (
  `id` int(11) NOT NULL,
  `takim1_id` int(11) NOT NULL,
  `takim1_gol` int(3) NOT NULL,
  `takim1_pen` int(3) NOT NULL,
  `takim1_puan` int(1) NOT NULL,
  `takim2_id` int(11) NOT NULL,
  `takim2_gol` int(3) NOT NULL,
  `takim2_pen` int(3) NOT NULL,
  `takim2_puan` int(1) NOT NULL,
  `kazanan_takim_id` int(11) NOT NULL,
  `asama` enum('Ön Eleme','Çeyrek Final','Yarı Final','3-4 Maçı','Final') NOT NULL DEFAULT 'Ön Eleme',
  `kullanici_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `scoreboard`
--

INSERT INTO `scoreboard` (`id`, `takim1_id`, `takim1_gol`, `takim1_pen`, `takim1_puan`, `takim2_id`, `takim2_gol`, `takim2_pen`, `takim2_puan`, `kazanan_takim_id`, `asama`, `kullanici_id`) VALUES
(290, 34, 9, 2, 1, 33, 9, 2, 1, 0, 'Ön Eleme', 1),
(291, 45, 12, 1, 1, 40, 11, 2, 1, 0, 'Ön Eleme', 1),
(292, 39, 5, 0, 0, 37, 11, 1, 3, 37, 'Ön Eleme', 1),
(293, 43, 4, 2, 3, 44, 5, 0, 0, 43, 'Ön Eleme', 1),
(294, 36, 1, 0, 0, 38, 9, 0, 3, 38, 'Ön Eleme', 1),
(295, 41, 12, 1, 3, 42, 9, 3, 0, 41, 'Ön Eleme', 1),
(296, 35, 9, 2, 3, 34, 7, 2, 0, 35, 'Ön Eleme', 1),
(297, 44, 18, 1, 3, 40, 0, 1, 0, 44, 'Ön Eleme', 1),
(298, 37, 11, 0, 3, 36, 4, 3, 0, 37, 'Ön Eleme', 1),
(299, 42, 8, 1, 0, 45, 13, 0, 3, 45, 'Ön Eleme', 1),
(300, 38, 10, 3, 3, 33, 1, 1, 0, 38, 'Ön Eleme', 1),
(301, 41, 15, 2, 3, 43, 4, 0, 0, 41, 'Ön Eleme', 1),
(302, 39, 9, 3, 3, 35, 3, 1, 0, 39, 'Ön Eleme', 1),
(303, 40, 5, 0, 1, 42, 5, 0, 1, 0, 'Ön Eleme', 1),
(304, 37, 14, 0, 3, 34, 7, 1, 0, 37, 'Ön Eleme', 1),
(305, 44, 3, 3, 0, 41, 15, 1, 3, 41, 'Ön Eleme', 1),
(306, 33, 8, 1, 3, 36, 1, 3, 0, 33, 'Ön Eleme', 1),
(307, 42, 3, 0, 0, 44, 7, 4, 3, 44, 'Ön Eleme', 1),
(308, 35, 9, 2, 0, 37, 13, 3, 3, 37, 'Ön Eleme', 1),
(309, 43, 5, 2, 0, 45, 8, 1, 3, 45, 'Ön Eleme', 1),
(310, 34, 2, 0, 0, 38, 11, 1, 3, 38, 'Ön Eleme', 1),
(311, 40, 6, 0, 0, 43, 7, 0, 3, 43, 'Ön Eleme', 1),
(312, 39, 12, 0, 3, 36, 3, 2, 0, 39, 'Ön Eleme', 1),
(313, 45, 6, 0, 1, 41, 6, 0, 1, 0, 'Ön Eleme', 1),
(314, 33, 4, 2, 0, 35, 18, 3, 3, 35, 'Ön Eleme', 1),
(315, 43, 5, 1, 0, 42, 10, 0, 3, 42, 'Ön Eleme', 1),
(316, 38, 13, 3, 3, 37, 8, 3, 0, 38, 'Ön Eleme', 1),
(317, 45, 6, 0, 3, 44, 2, 1, 0, 45, 'Ön Eleme', 1),
(318, 33, 8, 0, 0, 39, 14, 2, 3, 39, 'Ön Eleme', 1),
(319, 41, 7, 0, 0, 40, 8, 0, 3, 40, 'Ön Eleme', 1),
(320, 35, 13, 1, 3, 38, 7, 2, 0, 35, 'Ön Eleme', 1),
(321, 36, 4, 1, 0, 34, 10, 1, 3, 34, 'Ön Eleme', 1),
(322, 38, 9, 2, 3, 39, 8, 0, 0, 38, 'Ön Eleme', 1),
(323, 36, 6, 1, 0, 35, 13, 6, 3, 35, 'Ön Eleme', 1),
(324, 34, 7, 0, 0, 39, 11, 0, 3, 39, 'Ön Eleme', 1),
(325, 37, 3, 4, 3, 33, 6, 0, 0, 37, 'Ön Eleme', 1),
(326, 38, 8, 3, 3, 43, 8, 1, 0, 38, 'Çeyrek Final', 1),
(327, 37, 9, 1, 0, 44, 15, 2, 3, 44, 'Çeyrek Final', 1),
(328, 35, 11, 6, 3, 41, 5, 3, 0, 35, 'Çeyrek Final', 1),
(329, 39, 15, 2, 3, 45, 7, 3, 0, 39, 'Çeyrek Final', 1),
(330, 38, 9, 3, 0, 35, 13, 3, 3, 35, 'Yarı Final', 1),
(331, 44, 11, 1, 3, 39, 3, 2, 0, 44, 'Yarı Final', 1),
(332, 38, 12, 1, 3, 39, 7, 0, 0, 38, '3-4 Maçı', 1),
(333, 35, 7, 2, 0, 44, 10, 1, 3, 44, 'Final', 1),
(334, 35, 10, 4, 3, 44, 9, 2, 0, 35, 'Final', 1),
(335, 35, 10, 0, 3, 44, 4, 2, 0, 35, 'Final', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `takimlar`
--

CREATE TABLE `takimlar` (
  `id` int(11) NOT NULL,
  `takim_adi` text NOT NULL,
  `takim_grup` int(11) NOT NULL DEFAULT 0,
  `kullanici_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `takimlar`
--

INSERT INTO `takimlar` (`id`, `takim_adi`, `takim_grup`, `kullanici_id`) VALUES
(33, 'SKYRANGERS', 6, 1),
(34, 'ROBISTIM DRONE TEAM A', 6, 1),
(35, 'EZZWIN', 6, 1),
(36, 'GÖKYÜZÜ GÖZCÜLERİ', 6, 1),
(37, 'SKYKICK DRONE', 6, 1),
(38, 'SWIFT (EBABİL)', 6, 1),
(39, 'RED DRAGONS', 6, 1),
(40, 'SOHIL DRONE TEAM', 7, 1),
(41, 'GOLDEN DRONES', 7, 1),
(42, 'ROBISTIM DRONE TEAM B', 7, 1),
(43, 'HÜRGENÇ B', 7, 1),
(44, 'ERAWINGS', 7, 1),
(45, 'AEROERA', 7, 1);

--
-- Tetikleyiciler `takimlar`
--
DELIMITER $$
CREATE TRIGGER `takim_kayit_sil` AFTER DELETE ON `takimlar` FOR EACH ROW delete from scoreboard where takim1_id = old.id or takim2_id = old.id
$$
DELIMITER ;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `gruplar`
--
ALTER TABLE `gruplar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gruplar_ibfk_1` (`kullanici_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kAdi` (`kullanici_adi`);

--
-- Tablo için indeksler `scoreboard`
--
ALTER TABLE `scoreboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scoreboard_ibfk_1` (`kullanici_id`);

--
-- Tablo için indeksler `takimlar`
--
ALTER TABLE `takimlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `takimlar_ibfk_1` (`kullanici_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `gruplar`
--
ALTER TABLE `gruplar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `scoreboard`
--
ALTER TABLE `scoreboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- Tablo için AUTO_INCREMENT değeri `takimlar`
--
ALTER TABLE `takimlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `gruplar`
--
ALTER TABLE `gruplar`
  ADD CONSTRAINT `gruplar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `scoreboard`
--
ALTER TABLE `scoreboard`
  ADD CONSTRAINT `scoreboard_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `takimlar`
--
ALTER TABLE `takimlar`
  ADD CONSTRAINT `takimlar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
