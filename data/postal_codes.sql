/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table postal_codes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `postal_codes`;

CREATE TABLE `postal_codes` (
  `country_code` char(2) NOT NULL DEFAULT '' COMMENT 'iso country code',
  `postal_code` varchar(20) NOT NULL DEFAULT '',
  `place_name` varchar(180) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `admin_name1` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '1. order subdivision (state)',
  `admin_code1` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '1. order subdivision (state)',
  `admin_name2` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '2. order subdivision (county/province)',
  `admin_code2` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '2. order subdivision (county/province)',
  `admin_name3` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '3. order subdivision (community)',
  `admin_code3` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '3. order subdivision (community)',
  `latitude` float NOT NULL COMMENT 'estimated latitude (wgs84)',
  `longitude` float NOT NULL COMMENT 'estimated longitude (wgs84)',
  `accuracy` tinyint(1) NOT NULL COMMENT 'accuracy of lat/lng from 1=estimated to 6=centroid',
  KEY `postal_code` (`postal_code`),
  KEY `place_name` (`place_name`,`admin_code1`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Download and import the file `US.zip` from <http://download.geonames.org/export/zip/>\nThe GeoNames.org database is licensed under a Creative Commons Attribution 3.0 License.';




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
