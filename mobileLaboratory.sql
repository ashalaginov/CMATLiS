-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 03, 2013 at 01:03 PM
-- Server version: 5.5.31
-- PHP Version: 5.3.10-1ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mobileLaboratory`
--

-- --------------------------------------------------------

--
-- Table structure for table `androidAVDs`
--

CREATE TABLE IF NOT EXISTS `androidAVDs` (
  `avd_name` varchar(200) NOT NULL,
  `path` varchar(200) NOT NULL,
  `target` varchar(100) NOT NULL,
  `api_level` tinyint(3) unsigned NOT NULL,
  `ABI` varchar(200) NOT NULL,
  `skins` varchar(200) NOT NULL,
  PRIMARY KEY (`avd_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `androidAVDs`
--

INSERT INTO `androidAVDs` (`avd_name`, `path`, `target`, `api_level`, `ABI`, `skins`) VALUES
('Test1', '/home/andymir/.android/avd/Test1.avd', 'Android 4.2.2 ', 17, 'x86', '480x800');

-- --------------------------------------------------------

--
-- Table structure for table `androidVMtargets`
--

CREATE TABLE IF NOT EXISTS `androidVMtargets` (
  `id_target` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pseudonim` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `api_level` tinyint(3) unsigned NOT NULL,
  `revision` tinyint(3) unsigned NOT NULL,
  `skins` varchar(200) NOT NULL,
  `ABIs` varchar(200) NOT NULL,
  PRIMARY KEY (`id_target`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `androidVMtargets`
--

INSERT INTO `androidVMtargets` (`id_target`, `pseudonim`, `name`, `type`, `api_level`, `revision`, `skins`, `ABIs`) VALUES
(1, 'android-17', 'Android 4.2.2', 'Platform', 17, 2, 'WSVGA, WQVGA432, WVGA854, HVGA, WXGA800-7in, WQVGA400, WXGA800, WVGA800 (default), WXGA720, QVGA', 'x86');

-- --------------------------------------------------------

--
-- Table structure for table `appsFeatures`
--

CREATE TABLE IF NOT EXISTS `appsFeatures` (
  `id_featureSet` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_app` int(10) unsigned NOT NULL,
  `id_test` int(10) unsigned NOT NULL,
  `sdkVersion` tinyint(3) unsigned NOT NULL,
  `targetSdkVersion` tinyint(3) unsigned NOT NULL,
  `app_label_length` mediumint(8) unsigned NOT NULL,
  `package_name_length` mediumint(8) unsigned NOT NULL,
  `filesize` int(10) unsigned NOT NULL,
  `permissions_highest` int(10) unsigned NOT NULL,
  `permissions_avg` float NOT NULL,
  `permissions_number` mediumint(8) unsigned NOT NULL,
  `pull_data_size` int(10) unsigned NOT NULL,
  `log_launch_size` int(10) unsigned NOT NULL,
  `cpu_usage_peak` float NOT NULL,
  `cpu_usage_avg` float NOT NULL,
  `cpu_usage_stdev` float NOT NULL,
  `thr_usage_peak` float NOT NULL,
  `thr_usage_avg` float NOT NULL,
  `thr_usage_stdev` int(11) NOT NULL,
  `vss_usage_peak` float NOT NULL,
  `vss_usage_avg` float NOT NULL,
  `vss_usage_stdev` int(11) NOT NULL,
  `rss_usage_peak` float NOT NULL,
  `rss_usage_avg` float NOT NULL,
  `rss_usage_stdev` int(11) NOT NULL,
  `shared_prefs` tinyint(1) NOT NULL,
  `shared_prefs_size` int(10) unsigned NOT NULL,
  `databases` tinyint(1) NOT NULL,
  `databases_size` int(10) unsigned NOT NULL,
  `package_entropy` float unsigned NOT NULL,
  `package_number_files` mediumint(8) unsigned NOT NULL,
  `manifest_size` mediumint(8) unsigned NOT NULL,
  `res_folder_size` mediumint(8) unsigned NOT NULL,
  `assets_folder_size` mediumint(8) unsigned NOT NULL,
  `classes_dex_size` int(10) unsigned NOT NULL,
  `classes_dex_entropy` float unsigned NOT NULL,
  `execution_time` float NOT NULL,
  PRIMARY KEY (`id_featureSet`),
  KEY `id_app` (`id_app`,`id_test`),
  KEY `id_test` (`id_test`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `item` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`item`, `value`) VALUES
('launchedAVD', 'Test2'),
('launchedAVD_API', '8'),
('testConfiguration', 'a:9:{s:8:"testConf";s:8:"testConf";s:13:"disable_audio";s:2:"on";s:7:"traffic";s:2:"on";s:17:"disable_boot_anim";s:2:"on";s:10:"log_launch";s:2:"on";s:5:"trace";s:2:"on";s:10:"screenshot";s:2:"on";s:14:"read_cpu_usage";s:2:"on";s:9:"pull_data";s:2:"on";}');

-- --------------------------------------------------------

--
-- Table structure for table `performedAnalysis`
--

CREATE TABLE IF NOT EXISTS `performedAnalysis` (
  `id_analysis` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_test` int(11) NOT NULL,
  `id_app` int(11) NOT NULL,
  `app_label` varchar(200) NOT NULL,
  `folder_name` varchar(200) NOT NULL,
  `screenshot` tinyint(1) NOT NULL DEFAULT '0',
  `resources_usage` text NOT NULL,
  `data_structure_analysis` text NOT NULL,
  `databases_analysis` text NOT NULL,
  `ml_threats_analysis` text NOT NULL,
  `shared_prefs_analysis` text NOT NULL,
  `analysis_finished` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_analysis`),
  KEY `id_test` (`id_test`),
  KEY `id_app` (`id_app`),
  KEY `app_label` (`app_label`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=336 ;

-- --------------------------------------------------------

--
-- Table structure for table `performedTests`
--

CREATE TABLE IF NOT EXISTS `performedTests` (
  `id_test` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `avd_name` varchar(200) NOT NULL,
  `id_app` int(10) unsigned NOT NULL,
  `test_configuration` text NOT NULL,
  `log_install` text NOT NULL,
  `log_launch` text NOT NULL,
  `log_test` text NOT NULL,
  `log_uninstall` text NOT NULL,
  `screenshot` tinyint(1) NOT NULL DEFAULT '0',
  `pull_data` tinyint(1) NOT NULL,
  `read_cpu_usage` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` float unsigned NOT NULL,
  `test_app_process_echo` text NOT NULL,
  `bugreport` tinyint(1) NOT NULL,
  `folder_name` varchar(200) NOT NULL,
  `errors` smallint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_test`),
  KEY `id_app` (`id_app`),
  KEY `avd_name` (`avd_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=194 ;

-- --------------------------------------------------------

--
-- Table structure for table `processedApps`
--

CREATE TABLE IF NOT EXISTS `processedApps` (
  `id_app` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_label` varchar(100) CHARACTER SET utf8 NOT NULL,
  `md5_name` varchar(32) CHARACTER SET utf8 NOT NULL,
  `version` varchar(100) CHARACTER SET utf8 NOT NULL,
  `versionCode` mediumint(8) unsigned NOT NULL,
  `sdkVersion` tinyint(4) unsigned NOT NULL,
  `targetSdkVersion` tinyint(4) unsigned NOT NULL,
  `package_name` varchar(200) CHARACTER SET utf8 NOT NULL,
  `package_structure` mediumtext CHARACTER SET utf8 NOT NULL,
  `launchable_activity` varchar(200) CHARACTER SET utf8 NOT NULL,
  `permissions` mediumtext CHARACTER SET utf8 NOT NULL,
  `filesize` int(10) unsigned NOT NULL,
  `uploaded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `native_code` varchar(200) NOT NULL,
  `locales` text NOT NULL,
  `supports_screens` text NOT NULL,
  `densities` text NOT NULL,
  PRIMARY KEY (`id_app`),
  UNIQUE KEY `md5_name` (`md5_name`),
  KEY `app_label` (`app_label`),
  KEY `sdkVersion` (`sdkVersion`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=131 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
