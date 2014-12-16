<?php
require 'includes/header.php';
update_activity('events', 1);

if(isset($_GET['page'])) {
	$page = $_GET['page'];
	if(!is_numeric($page)) {
		add_error('Invalid ID.', true);
	}
} else {
	$page = 1;
}

if(allowed("manage_events")) {
	$delete = $_GET['delete'];
	if(!is_numeric($delete)){
		$page = 1;
	} else {
		$sql = "DELETE FROM `events` WHERE `no`='$delete'";
		mysql_query($sql,$link->getLink());
		$_SESSION['notice'] = "Event deleted.";
		exit(header("Location: ".DOMAIN."events"));
	}
}


// How many events to show per page.
$number = 50;
if($page == 1){$start = 0; $finish = $start + $number;} // Getting the limit results.
else{$start = $page - 1; $start = $start * $number; $finish = $number;}

// Set page title.
$page_title = "Upcoming events";
$additional_head = "";

// Delete events.
$expire_time = $_SERVER['REQUEST_TIME'] + 86400;
$sql = "DELETE FROM `events` WHERE `expires` < $expire_time";
$send = send($sql);

// Display the events menu and events.
echo '<ul class="menu">';if(ALLOW_USER_EVENTS || allowed("manage_events")){echo '<li><a href="'.DOMAIN.'new_event">New event</a></li><li>';}
echo'<a href="'.DOMAIN.'about_events">About</a></li>'; if(allowed("manage_events")){if(PRE_MODERATE_BULLETINS && ALLOW_USER_EVENTS){
echo '<li><a href="'.DOMAIN.'moderate_events">Moderate events</li></a>';}} echo'</ul>';

// Get all the events.
$sql = "SELECT * FROM events ORDER BY expires ASC LIMIT $start, $finish";
if(!$send = mysql_query($sql,$link->getLink())){exit (mysql_error($link->getLink()));} // {exit ("$sql");}
if(!$num = mysql_num_rows($send)){echo '<h2>No events on this page</h2>'; require 'includes/footer.php'; die();}

// Print current date, disabled for now. To enable uncomment next line and comment the empty echo out.
// echo '<span class="unimportant">Today is <b>'. date("Y-m-d") . '</b></span><br /><br />';
echo '';

echo '<table>
	<thead>
		<tr>
			<th>Description</th>
			<th class="minimal">Web address</th>
			<th class="minimal">Date ?</th>
			<th class="minimal">Time left</th>';
			if(allowed("manage_events")){echo '<th class="minimal">Modify</th><th class="minimal">From</th>';}
		echo'</tr>
	</thead>
	<tbody>';
	
	// Stuff to set the alternating classes.
	$selecter = "1";
	while($get = mysql_fetch_array($send)) {
		$set = "1";
		if($selecter == "1"){$class = "";}
		else{$class = "odd";}
		if($selecter == "2") {$selecter --; $set = "2";}
		$no = $get['no'];
		$des = $get['description'];
		$des = htmlspecialchars($des);
		$addr = $get['address'];
		$addr = htmlspecialchars($addr);
		$addr = "<a href=\"$addr\">$addr</a>";
		$date = $get['date'];
		$expires = $get['expires'];
		$uid = $get['uid'];
		$if_now = $_SERVER['REQUEST_TIME'] + 86400;
		if($expires <= $_SERVER['REQUEST_TIME']){$now = "Happening now";}
		elseif($expires <= $if_now){$now = "<b>&lt;24 Hours</b>";}
		else {
		$expires = calculate_age($expires,"");
		$now = $expires;
		}
		
		// Then display all the events.
		echo '<tr class="'.$class.'">
		<td><span class="extra">'.$des.'</span></td>
		<td class="minimal unimportant"><span class="extra">'.$addr.'</span></td>
		<td class="minimal"><span class="extra">'.$date.'</span></td>
		<td class="minimal"><span class="extra">'.$now.'</span></td>';
		$editLink = '<a href="'.DOMAIN.'edit_event/'.$no.'">Edit</a>';
		$deleteLink = "<a href=\"".DOMAIN."delete_event/".$no."\" onclick=\"return confirm('Really delete?');\">Delete</a>";
		
		$EditOrDelete = $editLink . ' / ' . $deleteLink;
		
		if(allowed("manage_events")){echo '<td class="minimal">'.$EditOrDelete.'</td><td class="minimal"><a href="'.DOMAIN.'profile.php?uid='.$uid.'">UID</a></td>';}
		echo '</tr>';
		if($selecter == "1" && $set == "1") {$selecter ++;}
	}
		
	// After the events have been displayed close the table.
	echo '</tbody></table>';

	// Insert newer and older links.
	if($page == 1 && $num > $number){echo '<ul class="menu"> <li> <a href="'.DOMAIN.'events/'.$page+1 .'">Older</a></li></ul>';}
	if($page > 1 && $num > $number){echo '<ul class="menu"> <li> <a href="'.DOMAIN.'events/1">Newest</a></li> <li> <a href='.DOMAIN.'events/'.$page - 1 .'">Newer</a></li> <li> <a href="'.DOMAIN.'events/'.$page + 1 .'">Older</a></li></ul>';}

require 'includes/footer.php';
?>