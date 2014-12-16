-- phpMyAdmin SQL Dump
-- version 4.0.9
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2014 at 02:54 AM
-- Server version: 5.5.34
-- PHP Version: 5.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `minichan_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE IF NOT EXISTS `activity` (
  `uid` varchar(23) NOT NULL,
  `time` int(10) NOT NULL,
  `action_name` varchar(60) NOT NULL,
  `action_id` int(10) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `android_tokens`
--

CREATE TABLE IF NOT EXISTS `android_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `uid` varchar(23) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `rand_token` char(10) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `request_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `rand_token` (`rand_token`),
  UNIQUE KEY `uid` (`uid`),
  KEY `request_time` (`request_time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=79 ;

-- --------------------------------------------------------

--
-- Table structure for table `bulletins`
--

CREATE TABLE IF NOT EXISTS `bulletins` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `poster` text NOT NULL,
  `time` int(11) NOT NULL,
  `date` text NOT NULL,
  `uid` text NOT NULL,
  `ip` text NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=164 ;

-- --------------------------------------------------------

--
-- Table structure for table `citations`
--

CREATE TABLE IF NOT EXISTS `citations` (
  `uid` varchar(23) NOT NULL,
  `topic` int(11) unsigned NOT NULL,
  `reply` int(11) unsigned NOT NULL,
  KEY `uid` (`uid`),
  KEY `topic` (`topic`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `address` text NOT NULL,
  `date` varchar(20) NOT NULL,
  `expires` int(11) NOT NULL,
  `uid` varchar(30) NOT NULL,
  `ip` text NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=81 ;

-- --------------------------------------------------------

--
-- Table structure for table `failed_postings`
--

CREATE TABLE IF NOT EXISTS `failed_postings` (
  `uid` varchar(23) NOT NULL,
  `time` int(10) NOT NULL,
  `reason` text NOT NULL,
  `headline` varchar(100) NOT NULL,
  `body` text NOT NULL,
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fingerprints`
--

CREATE TABLE IF NOT EXISTS `fingerprints` (
  `uid` varchar(23) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `fingerprint` bigint(20) NOT NULL,
  `time` int(10) NOT NULL,
  PRIMARY KEY (`uid`,`ip`,`fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `flood_control`
--

CREATE TABLE IF NOT EXISTS `flood_control` (
  `setting` varchar(50) NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY (`setting`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `flood_control`
--

INSERT INTO `flood_control` (`setting`, `value`) VALUES
('defcon', '5'),
('search_disabeld', '0');

-- --------------------------------------------------------

--
-- Table structure for table `gold_accounts`
--

CREATE TABLE IF NOT EXISTS `gold_accounts` (
  `UID` varchar(23) NOT NULL,
  `expires` int(10) NOT NULL,
  PRIMARY KEY (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ignore_lists`
--

CREATE TABLE IF NOT EXISTS `ignore_lists` (
  `uid` varchar(23) NOT NULL,
  `ignored_phrases` text NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `file_name` varchar(512) NOT NULL,
  `md5` varchar(32) NOT NULL,
  `topic_id` int(10) unsigned DEFAULT NULL,
  `reply_id` int(10) unsigned DEFAULT NULL,
  `thumb_width` int(3) NOT NULL,
  `thumb_height` int(3) NOT NULL,
  `img_external` varchar(512) NOT NULL DEFAULT '',
  `thumb_external` varchar(512) NOT NULL DEFAULT '',
  UNIQUE KEY `reply_id` (`reply_id`),
  UNIQUE KEY `topic_id` (`topic_id`),
  KEY `md5` (`md5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `internal_shorturls`
--

CREATE TABLE IF NOT EXISTS `internal_shorturls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2072352 ;

-- --------------------------------------------------------

--
-- Table structure for table `ip_bans`
--

CREATE TABLE IF NOT EXISTS `ip_bans` (
  `ip_address` varchar(100) NOT NULL,
  `filed` int(10) NOT NULL,
  `expiry` int(10) unsigned NOT NULL DEFAULT '0',
  `who` varchar(23) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `stealth` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip_address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `last_actions`
--

CREATE TABLE IF NOT EXISTS `last_actions` (
  `feature` varchar(30) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`feature`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mod_actions`
--

CREATE TABLE IF NOT EXISTS `mod_actions` (
  `action` varchar(255) NOT NULL,
  `target` varchar(23) NOT NULL,
  `mod_UID` varchar(23) NOT NULL,
  `mod_ip` varchar(100) NOT NULL,
  `time` int(10) NOT NULL,
  KEY `action` (`action`),
  KEY `time` (`time`),
  KEY `target` (`target`),
  KEY `target_2` (`target`,`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notepad`
--

CREATE TABLE IF NOT EXISTS `notepad` (
  `uid` varchar(23) NOT NULL,
  `notepad_content` text NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(100) NOT NULL,
  `page_title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE IF NOT EXISTS `permissions` (
  `uid` varchar(23) NOT NULL,
  `permission` varchar(25) NOT NULL,
  PRIMARY KEY (`uid`,`permission`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) NOT NULL,
  `content` varchar(255) NOT NULL,
  `votes` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9177 ;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE IF NOT EXISTS `poll_votes` (
  `uid` varchar(23) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `parent_id` int(11) unsigned NOT NULL,
  `option_id` int(11) unsigned NOT NULL,
  KEY `ip` (`ip`),
  KEY `parent_id` (`parent_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pre_bulletins`
--

CREATE TABLE IF NOT EXISTS `pre_bulletins` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `poster` text NOT NULL,
  `time` int(11) NOT NULL,
  `date` text NOT NULL,
  `uid` text NOT NULL,
  `ip` text NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=238 ;

-- --------------------------------------------------------

--
-- Table structure for table `pre_events`
--

CREATE TABLE IF NOT EXISTS `pre_events` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `address` text NOT NULL,
  `date` varchar(20) NOT NULL,
  `uid` text NOT NULL,
  `ip` text NOT NULL,
  `expires` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=77 ;

-- --------------------------------------------------------

--
-- Table structure for table `private_messages`
--

CREATE TABLE IF NOT EXISTS `private_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(23) NOT NULL,
  `destination` varchar(23) NOT NULL,
  `contents` text NOT NULL,
  `time` int(11) NOT NULL,
  `expiration` int(11) NOT NULL,
  `read` tinyint(1) NOT NULL,
  `can_reply` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `source` (`source`),
  KEY `destination` (`destination`),
  KEY `expiration` (`expiration`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20002 ;

-- --------------------------------------------------------

--
-- Table structure for table `read_topics`
--

CREATE TABLE IF NOT EXISTS `read_topics` (
  `uid` varchar(23) NOT NULL,
  `topic` int(11) NOT NULL,
  `replies` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`uid`,`topic`),
  KEY `uid` (`uid`),
  KEY `uid_2` (`uid`,`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE IF NOT EXISTS `replies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned NOT NULL,
  `namefag` varchar(60) DEFAULT NULL,
  `tripfag` varchar(12) DEFAULT NULL,
  `poster_number` int(10) unsigned NOT NULL,
  `author` varchar(23) NOT NULL,
  `author_ip` varchar(100) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `edit_time` int(10) unsigned DEFAULT NULL,
  `edit_mod` tinyint(1) unsigned DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `stealth_ban` tinyint(1) NOT NULL DEFAULT '0',
  `admin_hyperlink` tinyint(1) NOT NULL DEFAULT '1',
  `post_html` tinyint(1) NOT NULL DEFAULT '0',
  `tip` bigint(20) unsigned DEFAULT NULL,
  `flag` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `letter` (`poster_number`),
  KEY `author` (`author`),
  KEY `parent_id` (`parent_id`),
  KEY `author_ip` (`author_ip`),
  KEY `time` (`time`),
  KEY `deleted` (`deleted`),
  KEY `namefag` (`namefag`),
  KEY `tripfag` (`tripfag`),
  FULLTEXT KEY `repliesText` (`body`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=446933 ;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` int(11) NOT NULL,
  `reply` int(11) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `uid` varchar(23) NOT NULL,
  `reason` varchar(512) NOT NULL,
  `handled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `handled` (`handled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1156 ;

-- --------------------------------------------------------

--
-- Table structure for table `search_log`
--

CREATE TABLE IF NOT EXISTS `search_log` (
  `search_id` int(10) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(50) NOT NULL,
  `time` int(15) NOT NULL,
  `phrace` varchar(255) NOT NULL,
  PRIMARY KEY (`search_id`),
  KEY `phrace` (`phrace`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9392 ;

-- --------------------------------------------------------

--
-- Table structure for table `shorturls`
--

CREATE TABLE IF NOT EXISTS `shorturls` (
  `id` varchar(15) NOT NULL,
  `url` text NOT NULL,
  `ip` varchar(15) NOT NULL,
  `uid` varchar(23) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stored_notifications`
--

CREATE TABLE IF NOT EXISTS `stored_notifications` (
  `event` varchar(15) NOT NULL,
  `target` varchar(23) NOT NULL,
  `identifier` varchar(15) NOT NULL,
  `parent_id` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`event`,`target`,`identifier`,`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE IF NOT EXISTS `topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `author` varchar(23) NOT NULL,
  `namefag` varchar(60) DEFAULT NULL,
  `tripfag` varchar(12) DEFAULT NULL,
  `author_ip` varchar(100) NOT NULL,
  `replies` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post` int(10) unsigned NOT NULL,
  `visits` int(10) unsigned NOT NULL DEFAULT '0',
  `headline` varchar(100) NOT NULL,
  `body` text NOT NULL,
  `edit_time` int(10) unsigned DEFAULT NULL,
  `edit_mod` tinyint(1) unsigned DEFAULT NULL,
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `poll` tinyint(1) NOT NULL DEFAULT '0',
  `admin_hyperlink` tinyint(1) NOT NULL DEFAULT '1',
  `post_html` tinyint(1) NOT NULL DEFAULT '0',
  `secret_id` int(10) unsigned DEFAULT NULL,
  `flag` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author` (`author`),
  KEY `author_ip` (`author_ip`),
  KEY `last_post` (`last_post`),
  KEY `time` (`time`),
  KEY `sticky` (`sticky`),
  KEY `deleted` (`deleted`),
  KEY `namefag` (`namefag`),
  KEY `tripfag` (`tripfag`),
  FULLTEXT KEY `headline` (`headline`),
  FULLTEXT KEY `body` (`body`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37713 ;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `uid` varchar(23) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `address` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `uid_bans`
--

CREATE TABLE IF NOT EXISTS `uid_bans` (
  `uid` varchar(23) NOT NULL,
  `expiry` int(10) NOT NULL DEFAULT '0',
  `filed` int(10) NOT NULL,
  `who` varchar(23) NOT NULL,
  `reason` varchar(255) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `permanent` (`expiry`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `uid` varchar(23) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `password` varchar(32) NOT NULL,
  `first_seen` int(10) NOT NULL,
  `last_seen` int(10) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `last_ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `namefag` text NOT NULL,
  `report_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `mod_notes` text NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `first_seen` (`first_seen`),
  KEY `ip_address` (`ip_address`),
  KEY `last_ip` (`last_ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE IF NOT EXISTS `user_settings` (
  `uid` varchar(23) NOT NULL,
  `memorable_name` varchar(100) NOT NULL,
  `memorable_password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `spoiler_mode` tinyint(1) NOT NULL DEFAULT '0',
  `snippet_length` smallint(3) NOT NULL DEFAULT '80',
  `topics_mode` tinyint(1) NOT NULL,
  `ostrich_mode` tinyint(1) NOT NULL DEFAULT '0',
  `style` varchar(18) NOT NULL DEFAULT '',
  `custom_style` varchar(255) NOT NULL,
  `image_viewer` tinyint(1) NOT NULL DEFAULT '1',
  `disable_images` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `memorable_name` (`memorable_name`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vuln_plugins`
--

CREATE TABLE IF NOT EXISTS `vuln_plugins` (
  `uid` varchar(23) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `name` varchar(512) NOT NULL,
  `desc` varchar(512) NOT NULL,
  `version` varchar(128) NOT NULL,
  UNIQUE KEY `summary` (`uid`,`name`,`version`,`ip_address`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE IF NOT EXISTS `wallets` (
  `uid` varchar(23) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `watchlists`
--

CREATE TABLE IF NOT EXISTS `watchlists` (
  `uid` varchar(23) NOT NULL,
  `topic_id` int(10) NOT NULL,
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
