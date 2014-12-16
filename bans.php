<?php
require 'includes/header.php';

// If user is not an administrator.
if(!allowed("ban_ip") && !allowed("ban_uid")) {
	$_SESSION['notice']=MESSAGE_PAGE_ACCESS_DENIED; header("Location: ".DOMAIN); exit("");
}

$page_title = "Bans";
$additional_head = "";

global $link;
echo '<table>
	<thead>
		<tr>
			<th class="minimal">IP/UID</th>
			<th>Reason</th>
			<th class="minimal">Mod UID</th>
			<th class="minimal">Ban filed ▼</th>
			<th class="minimal">Expires in</th>
		</tr>
	</thead>
	<tbody>';
	
$sql = "(SELECT ip_address,  '' AS uid, filed, who, expiry, reason FROM ip_bans ) UNION ALL (SELECT  '' AS ip_address, uid, filed, who, expiry, reason FROM uid_bans) ORDER BY filed DESC";
$send = mysql_query($sql);
		
$selecter = true;
while($get = mysql_fetch_array($send)) {
	if($selecter) {
		$class = "";
	} else { 
		$class = "odd";
	}
	$selecter = !$selecter;
	
	// Get data.
	$filed = $get['filed'];
	$expiry = $get['expiry'];
	if($expiry==0) {
		$expiry = 'Never';
	} else {
		if($expiry < $_SERVER['REQUEST_TIME']){
			continue;
		}
		$expiry = calculate_age($expiry,$_SERVER['REQUEST_TIME']);
	}
	$filed = calculate_age($filed, $_SERVER['REQUEST_TIME']);
	$who = $get['who'];
	
	if($get['ip_address']!='') {
		$display = $get['ip_address'];
		$display = "<a href='".DOMAIN."IP_address/$display'>$display</a>";
	} else {
		$display = $get['uid'];
		$display = "<a href='".DOMAIN."profile/$display'>$display</a>";
	}
				
	if($who==$last_who) {
		$who_show = '<span class="unimportant">(See above)</span>';
	} else {
		$who_show = modname($who);
	}
	$last_who = $who;
		
	if($display==$last_display) {
		$display_show = '<span class="unimportant">(See above)</span>';
	} else {
		$display_show = $display;
	}
	$last_display = $display;
		
	// Parse bans.
	echo "<tr class=\"$class\">
	<td class=\"minimal\">$display_show</td>
	<td>" . htmlspecialchars($get['reason']) . "</td>
	<td class=\"minimal\"><a href='".DOMAIN."profile/$who'>$who_show</a></td>
	<td class=\"minimal\">$filed ago</td>
	<td class=\"minimal\">$expiry</td>
	</tr>";
}
	echo '</tbody> </table>';
	
require 'includes/footer.php';
?>
