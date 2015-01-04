<?php if(!$upgrade) die();
// Will add rounded_corners column to user_settings table
$link->db_exec("ALTER TABLE `user_settings` ADD `rounded_corners` TINYINT(1) NOT NULL DEFAULT '0' AFTER `custom_style`;");