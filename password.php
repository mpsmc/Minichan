<?php
require('includes/header.php');

if (!$administrator) {
	add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

$link->db_exec("SELECT * FROM users WHERE uid = %1", $_GET['uid']);
var_dump($link->fetch_row());