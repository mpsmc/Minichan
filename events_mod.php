<?php
require 'includes/header.php';

// If user is not an administrator.
if(!allowed("manage_events")) {
	$_SESSION['notice']=MESSAGE_PAGE_ACCESS_DENIED; header("Location: ".DOMAIN.""); exit("");
}

if(isset($_GET['no'])) {
	if(!is_numeric($_GET['no'])) {
	$_SESSION['notice'] = "Invalid event."; exit(header("Location: ".DOMAIN."moderate_events"));
	}
}

if(isset($_GET['mode'])) {
	if($_GET['mode'] != "allow") {
		if($_GET['mode'] != "deny") {
			$_SESSION['notice'] = "Method must either be allow or deny."; exit(header("Location: ".DOMAIN."moderate_events"));
		}
	}
}

$page_title = "Moderate events";
$additional_head = "";

function view_pre_events() {
	global $link;
	echo '<table>
		<thead>
			<tr>
				<th>Event</th>
				<th class="minimal">Web address</th>
				<th class="minimal">Event date</th>
				<th class="minimal">Allow?</th>
				<th class="minimal">From</th>
			</tr>
		</thead>
		<tbody>';
	
	$sql = "SELECT * FROM pre_events ORDER BY time DESC";
	if(!$send = mysql_query($sql)) {
		$_SESSION['notice'] = mysql_error(); exit(header("Location: ".DOMAIN.""));
	}
	
	$selecter = 1;
	while($get = mysql_fetch_array($send))
	{
		$set = "1";
		if($selecter == "1"){
		$class = "";
		} else {
		$class = "odd";
		}
		if($selecter == "2") {
		$selecter --; $set = "2";
		}
		
		// Get data.
		$no = $get['no'];
		$des = htmlspecialchars($get['description']);
		$addr = htmlspecialchars($get['address']);
		$time = htmlspecialchars($get['time']);
		$time = calculate_age($time,$_SERVER['REQUEST_TIME']);
		$date = htmlspecialchars($get['date']);
		$expires = htmlspecialchars($get['expires']);
		$uid = $get['uid'];
		$uid = '<a href="'.DOMAIN.'profile/'.$uid.'">UID</a>';
				
		// Parse all events in the queue.
		echo '<tr class='.$class.'>
		<td><span class="extra">'.$des.'</span></td>
		<td class="minimal unimportant"><span class="extra">'.$addr.'</span></td>
		<td class="minimal"><span class="extra">'.$date.'</span></td>
		<td class="minimal"><span class="extra"><a href="'.DOMAIN.'moderate_events/'.$no.'/allow" onclick="return confirm(\'Allow event?\');">yes</a>/<a href="'.DOMAIN.'moderate_events/'.$no.'/deny" onclick="return confirm(\'Deny event?\');">no</a></span></td>
		<td class="minimal"><span class="extra">';
		echo "$uid</span></td></tr>";
		
		if($selecter == "1" && $set == "1") {
		$selecter ++;
		}
	}
	echo '</tbody> </table>';
}

function allow_deny() {
	global $link, $administrator;
	if(!allowed("manage_events")) {
	$_SESSION['notice']=MESSAGE_PAGE_ACCESS_DENIED; header("Location: ".DOMAIN.""); exit("");
	}

	if(isset($_GET['no']) && isset($_GET['mode'])) {
		// If denying the event.
		if($_GET['mode'] == "deny") {
			$sql = "DELETE FROM pre_events WHERE no='$_GET[no]'";
			if(!$send = mysql_query($sql)) {
			$_SESSION['notice'] = mysql_error(); exit(header("Location: ".DOMAIN."moderate_events"));
			} else {
			$_SESSION['notice']="Event denied."; exit(header("Location: ".DOMAIN."moderate_events"));
			}
		}
		$sql = "SELECT * FROM pre_events WHERE no='$_GET[no]'";
		if(!$send = mysql_query($sql)) {
		$_SESSION['notice'] = mysql_error(); exit(header("Location: ".DOMAIN."moderate_events"));
		}
		$get = mysql_fetch_array($send);
		$des = mysql_real_escape_string($get['description']);
		$adr = mysql_real_escape_string($get['address']);
		$adr = mysql_real_escape_string($adr); // Safety first.
		$date = $get['date'];
		$expires = $get['expires'];
		$uid = $get['uid'];
		$ip = $get['ip'];

		$sql = "INSERT INTO events (description,address,date,expires,uid,ip) VALUES ('$des','$adr','$date','$expires','$uid','$ip')";
		if(!$send = mysql_query($sql)){$_SESSION['notice'] = mysql_error(); exit(header("Location: ".DOMAIN."moderate_events"));}
		else {
			$sql = "DELETE FROM pre_events WHERE no='$_GET[no]'";
			if(!$send = mysql_query($sql)) {
			$_SESSION['notice'] = mysql_error(); exit(header("Location: ".DOMAIN."moderate_events"));
			}
			$_SESSION['notice'] = "Event allowed."; 
			exit(header ("Location: ".DOMAIN."moderate_events"));
		}
	} else {
	$_SESSION['notice'] = "Error try again."; exit(header("Location: ".DOMAIN."moderate_events"));
	}
}

// Choose a choice and decide.
if(isset($_GET['no']) && isset($_GET['mode'])) {
	allow_deny();
}
else{view_pre_events();}

require 'includes/footer.php';
?>