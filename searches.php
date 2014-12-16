<?php
require("includes/header.php");
// If user is not an administrator.
if( !allowed("manage_search")) {
	$_SESSION['notice']=MESSAGE_PAGE_ACCESS_DENIED; header("Location: ".DOMAIN); exit("");
}
$link->db_exec("SELECT ip_address, time, phrace FROM search_log ORDER BY search_id DESC LIMIT 100");
while(list($ip, $time, $phrace) = $link->fetch_row()) {
	echo "$ip - " . htmlspecialchars($phrace) . "<br />";
}

require("includes/footer.php");
