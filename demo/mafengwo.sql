# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 203.195.132.34 (MySQL 5.6.33-0ubuntu0.14.04.1)
# Database: phpspider
# Generation Time: 2016-10-11 08:48:31 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table mafengwo_content3
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mafengwo_content`;

CREATE TABLE `mafengwo_content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `city` varchar(20) DEFAULT NULL COMMENT '城市',
  `name` varchar(50) DEFAULT NULL COMMENT '标题',
  `date` date DEFAULT NULL COMMENT '出发日期',
  `up` int(11) DEFAULT NULL COMMENT '顶',
  `pv` int(11) DEFAULT NULL COMMENT '浏览次数',
  `fav` int(11) DEFAULT NULL COMMENT '收藏',
  `share` int(11) DEFAULT NULL COMMENT '分享',
  `pic` int(11) DEFAULT NULL COMMENT '图片数目',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
