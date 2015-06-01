<?php if(!$upgrade) die();
multi_query(<<<SQL
ALTER TABLE `uid_bans` ADD `stealth` TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE `topics` ADD `stealth_ban` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
SQL
);