<?php
if(php_sapi_name() != 'cli') die("Must be ran through CLI!");
chdir(realpath(dirname(__FILE__)));
$lockfile = "locked";
if(file_exists($lockfile)) {
	die("Upgrade already in progress!");
}
register_shutdown_function(function() {
	global $lockfile;
	unlink($lockfile);
});
file_put_contents($lockfile, "1");
require_once('config.php');
require_once('database.class.php');
$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);

function doDatabaseUpgrade($old, $new) {
	global $link;
	$upgrade = true;
	require("database/upgrade-$old-to-$new.php");
}

for($i = $link->getVersion(); $i < DB_VERSION; $i++) {
	echo "Performing upgrade $i-to-".($i+1)."\n";
	doDatabaseUpgrade($i, $i+1);
	$link->setVersion($i+1);
	echo "Finished upgrade $i-to-".($i+1)."\n";
}