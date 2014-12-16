<?php
chdir("..");
require("includes/config.php");
$link = mysql_pconnect($db_info['server'], $db_info['username'], $db_info['password']);
mysql_select_db($db_info['database']);

if(isset($_GET['internal']))
	$internal = (bool)$_GET['internal'];
else
	$internal = false;

if($internal) {
	$result = mysql_query("SELECT url FROM internal_shorturls WHERE id = \"" . mysql_real_escape_string(base_convert($_GET['id'], 36, 10)) . "\" LIMIT 1");
}else{
	$result = mysql_query("SELECT url FROM shorturls WHERE id = \"" . mysql_real_escape_string($_GET['id']) . "\" LIMIT 1");
}
if(mysql_num_rows($result) > 0) {
	$resArr = mysql_fetch_array($result, MYSQL_NUM);
	header("Location: " . $resArr[0]);
	if(!$internal)
		mysql_query("UPDATE shorturls SET hits = hits + 1 WHERE id = \"" . mysql_real_escape_string($_GET['id']) . "\" LIMIT 1");
		
	die();
}else{
	require("includes/header.php");
	$_SESSION['notice'] = "404 - Link not found";
	header("Location: " . DOMAIN . "link");
	die();
}
