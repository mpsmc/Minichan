<?php
require('includes/header.php');

if(!$_SESSION['UID']) die();

$sql = array();
foreach($_POST['plugins'] as $plugin) {
	$sql[] = '("'.$_SESSION['UID'].'", "' . $_SERVER['REMOTE_ADDR'] . '", UNIX_TIMESTAMP(), "' . $link->escape($plugin["name"]) . '", "' . $link->escape($plugin["desc"]) . '", "' . $link->escape($plugin["version"]) . '")' ;
}

$sql = "INSERT INTO vuln_plugins (`uid`, `ip_address`, `time`, `name`, `desc`, `version`) VALUES " . implode(", ", $sql);

//echo $sql;
$link->show_errors(false);
$link->db_exec($sql);