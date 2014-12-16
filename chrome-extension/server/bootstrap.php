<?php
require('settings.php');
chdir("./../..");
require('includes/config.php');
require('includes/database.class.php');
require('includes/functions.php');

header('Cache-Control: no-cache');
header('Pragma: no-cache');

$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);
date_default_timezone_set('UTC');
header('Content-Type: application/json; charset=UTF-8');

$moderator = false;
$administrator = false;
$janitor = false;

function check_permissions($uid) {
	global $moderator, $administrator, $janitor, $moderators, $administrators, $janitors;
	
	if(in_array($uid, $moderators)) {
		$moderator = true;
	} else if(in_array($uid, $administrators)) {
		$administrator = true;
	} else if(in_array($uid, $janitors)) {
		$janitor = true;
	}
}