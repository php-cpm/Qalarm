-- MySQL dump 10.13  Distrib 5.6.26-74.0, for Linux (x86_64)
--
-- Host: m3341.wdds.mysqldb.com    Database: qalarm
-- ------------------------------------------------------
-- Server version	5.6.26-74.0-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED='5d2d9e51-a7d3-11e5-93e3-a0369f7c3cd4:1-20983569874,
6ac7c4ce-b8dc-11e5-82f9-a0369f6b48b0:1-11036,
7eb1be1b-2eb5-11e5-be1a-549f35110040:1-567250,
af95e5b0-5785-11e5-883e-ecf4bbdec128:1-3989076';

--
-- Table structure for table `admin_app`
--

DROP TABLE IF EXISTS `admin_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_app` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) DEFAULT NULL COMMENT '用户id',
  `app_id` int(11) DEFAULT NULL COMMENT '应用id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=420 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_auth`
--

DROP TABLE IF EXISTS `admin_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mid` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `auth_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `auth_url` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `icon_class` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `badge_class` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `badge_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_menu_permit`
--

DROP TABLE IF EXISTS `admin_menu_permit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_menu_permit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) DEFAULT NULL COMMENT '页面ID',
  `sub_page_code` varchar(50) DEFAULT NULL COMMENT '页面tab Code',
  `sub_page_name` varchar(50) DEFAULT NULL COMMENT '页面tab 名字',
  `permit_code` varchar(50) DEFAULT NULL COMMENT '权限 Code',
  `permit_name` varchar(50) DEFAULT NULL COMMENT '权限 名字',
  `public_permit` int(11) DEFAULT '0' COMMENT '权限是否完全公开；1公开，0完全公开',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_role`
--

DROP TABLE IF EXISTS `admin_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `authid_set` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mid` int(11) DEFAULT NULL,
  `sid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_role_role_name_unique` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_role_permit`
--

DROP TABLE IF EXISTS `admin_role_permit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role_permit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL COMMENT '父角色ID',
  `permit_id` int(11) NOT NULL COMMENT '权限ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_ticket`
--

DROP TABLE IF EXISTS `admin_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11) NOT NULL,
  `ticket` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `admin_ticket_ticket_index` (`ticket`(255))
) ENGINE=InnoDB AUTO_INCREMENT=6057 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_user`
--

DROP TABLE IF EXISTS `admin_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `roleid_set` varchar(1024) NOT NULL DEFAULT '',
  `username` varchar(128) NOT NULL DEFAULT '',
  `password` varchar(256) NOT NULL DEFAULT '',
  `nickname` varchar(128) NOT NULL DEFAULT '',
  `mail` varchar(128) DEFAULT NULL,
  `mobile` varchar(16) NOT NULL DEFAULT '',
  `cityid` int(11) NOT NULL DEFAULT '131',
  `cityids` varchar(128) NOT NULL DEFAULT '',
  `head_img` varchar(128) DEFAULT NULL,
  `host_passwd_sha1` varchar(128) DEFAULT NULL,
  `host_passwd_shadow` varchar(128) DEFAULT NULL,
  `host_passwd_slice` varchar(12) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_user_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_user_role`
--

DROP TABLE IF EXISTS `admin_user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alarm`
--

DROP TABLE IF EXISTS `alarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alarm` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project` varchar(128) DEFAULT NULL,
  `time` timestamp NULL DEFAULT NULL COMMENT '报警时间',
  `message` varchar(1024) DEFAULT NULL COMMENT '报警内容',
  `receivor` varchar(1024) DEFAULT NULL COMMENT '接收人',
  PRIMARY KEY (`id`),
  KEY `index_project` (`project`)
) ENGINE=InnoDB AUTO_INCREMENT=120266 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gaea_migrations`
--

DROP TABLE IF EXISTS `gaea_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gaea_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL COMMENT '组名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project` varchar(128) DEFAULT NULL,
  `module` varchar(128) DEFAULT NULL,
  `code` varchar(128) DEFAULT NULL,
  `env` varchar(10) DEFAULT NULL,
  `time` timestamp NULL DEFAULT NULL,
  `server_ip` varchar(56) DEFAULT NULL,
  `client_ip` varchar(56) DEFAULT NULL,
  `script` varchar(256) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pro_time` (`project`,`time`),
  KEY `idx_pro_module_time` (`project`,`module`,`time`)
) ENGINE=InnoDB AUTO_INCREMENT=113985031 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned DEFAULT NULL COMMENT '项目id',
  `module` varchar(256) DEFAULT NULL COMMENT '模块名',
  `code` varchar(256) DEFAULT NULL COMMENT '错误码',
  `env` varchar(10) DEFAULT NULL COMMENT '环境 线上环境 or 测试环境',
  `monitors` varchar(256) DEFAULT NULL COMMENT '关注者',
  `strategy_id` int(10) unsigned DEFAULT NULL COMMENT '告警策略id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '开关状态',
  PRIMARY KEY (`id`),
  KEY `index_module_code_env` (`module`(255),`code`(255),`env`)
) ENGINE=InnoDB AUTO_INCREMENT=332 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `monitor`
--

DROP TABLE IF EXISTS `monitor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitor` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(128) DEFAULT NULL COMMENT '用户名',
  `real_name` varchar(128) DEFAULT NULL COMMENT '真实名字',
  `mail` varchar(128) DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `status` tinyint(4) unsigned DEFAULT NULL COMMENT '启用状态 0 禁用 1启用',
  PRIMARY KEY (`id`),
  KEY `index_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=923 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `monitor_group`
--

DROP TABLE IF EXISTS `monitor_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitor_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned DEFAULT NULL,
  `monitro_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL COMMENT '项目名称',
  `manager` varchar(56) DEFAULT NULL COMMENT '项目负责人',
  `monitor` varchar(1024) DEFAULT NULL COMMENT '关注者',
  `strategy_id` int(11) unsigned DEFAULT NULL COMMENT '告警策略id',
  `status` tinyint(4) unsigned DEFAULT '1' COMMENT '项目状态 0 关闭 1 启用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `test_graph_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '测试环境的图表展示',
  `test_alarm_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '测试环境的告警配置',
  PRIMARY KEY (`id`),
  KEY `index_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qalarm_failed_jobs`
--

DROP TABLE IF EXISTS `qalarm_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qalarm_failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8_unicode_ci NOT NULL,
  `queue` text COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=123269 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `strategy`
--

DROP TABLE IF EXISTS `strategy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `strategy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `param1` int(11) unsigned DEFAULT NULL COMMENT '连续多少次',
  `param2` int(11) unsigned DEFAULT NULL COMMENT '统计周期[分钟]',
  `param3` int(11) unsigned DEFAULT NULL COMMENT '统计周期内阈值',
  `valid_start` int(4) unsigned DEFAULT '0' COMMENT '报警时间段开始时间',
  `valid_end` int(4) unsigned DEFAULT '23' COMMENT '报警时间段结束时间',
  `is_sms` tinyint(2) unsigned DEFAULT NULL COMMENT '是否发短信 0 否 1是',
  `is_email` tinyint(2) unsigned DEFAULT NULL COMMENT '是否发邮件 0 否 1是',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-06-04 18:00:25
