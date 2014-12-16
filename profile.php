<?php
require('includes/header.php');

// If you're not an admin or a Moderator, you're out of luck.
if(!allowed("open_profile")) {
	add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

// Demand UID.
if( ! isset($_GET['uid'])) {
	add_error('No UID specified.', true);
}

// Demand a _valid_ UID, fetch first_seen, IP address, and hostname.
$uid_exists = $link->db_exec('SELECT first_seen, last_seen, ip_address, last_ip, mod_notes FROM users WHERE uid = %1', $_GET['uid']);
if($link->num_rows($uid_exists) < 1) {
	add_error('There is no such user.', true);
}

if($_GET['json']) {
	header('Content-Type: application/json');
	echo json_encode($link->fetch_assoc($uid_exists));
	die();
}

list($id_first_seen, $id_last_seen, $id_ip_address, $last_ip, $mod_notes) = $link->fetch_row($uid_exists);

$link->db_exec("SELECT activity.action_name, activity.action_id, activity.time, topics.headline FROM activity LEFT OUTER JOIN topics ON activity.action_id = topics.id WHERE activity.uid = %1", $_GET['uid']);
list($activity_name, $activity_id, $activity_time, $activity_headline) = $link->fetch_row();

$activity_time = calculate_age($activity_time);
$topic_headline = htmlspecialchars($topic_headline);
$activity_name = $actions[$activity_name];
if($activity_name) {
	$activity_name = str_replace("{headline}", $activity_headline, $activity_name);
	$activity_name = str_replace("{action_id}", $activity_id, $activity_name);
	$activity = "User was \"" . $activity_name . "\", " . $activity_time . " ago";
}else{
	$activity = "";
}

$link->db_exec("SELECT report_allowed FROM users WHERE uid = %1", $_GET['uid']);
list($allow_user_report) = $link->fetch_row();

$id_hostname = @gethostbyaddr($id_ip_address);
if($id_hostname === $id_ip_address) {
	$id_hostname = false;
}

if($last_ip=="0.0.0.0") $last_ip = $id_ip_address;

$last_ip_hostname = @gethostbyaddr($last_ip);
if($last_ip_hostname === $last_ip) {
	$last_ip_hostname = false;
}


if(!empty($_POST['do_permissions']) && $administrator) {
	check_token();
		
	$new_perms = $_POST['permissions'];
	foreach(get_all_permissions() as $permission) {
		if($new_perms[$permission]) { // User has it
			$sqldata = array();
			$sqldata["uid"] = $_GET['uid'];
			$sqldata["permission"] = $permission;
			$link->insertorupdate("permissions", $sqldata);
		}elseif(allowed($permission, $_GET['uid'])){
			$link->db_exec("DELETE FROM permissions WHERE uid = %1 AND permission = %2", $_GET['uid'], $permission);
		}
	}
	
	$_SESSION['notice'] = "Permissions saved.";
	$permissions[$_GET['uid']] = null;
	
}

if(!empty($_POST['do_notes'])) {
	check_token();
	$sqldata = array();
	$uid = $_GET['uid'];
	$sqldata["uid"] = $uid;
	$sqldata["mod_notes"] = $_POST['mod_notes'];
	$ban_ip = $link->insertorupdate("users", $sqldata);
	log_mod("uid_note", $uid);
	$_SESSION['notice'] = 'Notes saved.';
	
	$mod_notes = $_POST['mod_notes'];
}

// If a ban request has been submitted.
if (!empty($_POST['ban_length']) && !empty($_POST['do_ban']) && allowed("ban_uid")) {
	// CSRF checking.
	check_token();
	if ($_POST['ban_length'] == 'indefinite' || $_POST['ban_length'] == 'infinite') {
		$new_ban_expiry = 0;
	} else if (strtotime($_POST['ban_length']) > $_SERVER['REQUEST_TIME']) {
		$new_ban_expiry = strtotime($_POST['ban_length']);
	} else {
		add_error('Invalid ban length.');
	}
	
	if(strlen($_POST['ban_reason']) > 255) {
		add_error('Ban reason too long.');
	}
	
	$banreason = $_POST['ban_reason'];
	
	$uid = $_GET['uid'];
	
	if($uid == $_SESSION['UID']){
		add_error("Errr... What are you trying to do?", true);
	}
	
	if(in_array($uid, $administrators)) {
		add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
	}
	
	if(allowed("ban_uid", $uid) && !$administrator) {
		add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
	}	
	
	if (!$erred) {
		$sqldata = array();
		$sqldata["uid"] = $uid;
		$sqldata["expiry"] = $new_ban_expiry;
		$sqldata["filed"] = "UNIX_TIMESTAMP()";
		$sqldata["who"] = $_SESSION['UID'];
		$sqldata["reason"] = $banreason;
		$ban_ip = $link->insertorupdate("uid_bans", $sqldata);
		log_mod("ban_uid", $uid);
		$_SESSION['notice'] = 'UID banned.';
	}
}else{
	log_mod("open_profile", $_GET['uid']);
}



// Check if banned.
$check_uid_ban = $link->db_exec('SELECT filed, expiry, reason FROM uid_bans WHERE uid = %1', $_GET['uid']);
list($ban_filed, $ban_expiry, $banreason) = $link->fetch_row($check_uid_ban);
$banned = false;
if (!empty($ban_filed)) {
	if ($ban_expiry == 0 || $ban_expiry > $_SERVER['REQUEST_TIME']) {
		$banned = true;
	} else // The ban has already expired.
		{
		remove_id_ban($_GET['uid']);
	}
}

// Fetch number of topics and replies.
unset($query);
$query[] = "SELECT count(*) FROM topics WHERE deleted = 0 AND author = '" . $link->escape($_GET['uid']) . "';";
$query[] = "SELECT count(*) FROM replies WHERE deleted = 0 AND author = '" . $link->escape($_GET['uid']) . "';";
foreach($query as $q){
	$result = $link->db_exec($q);
	while ($row = $link->fetch_row()) {
		$statistics[] = $row[0];
	}
}

$id_num_topics = $statistics[0];
$id_num_replies = $statistics[1];

// Now print everything.
$page_title = 'Profile of poster ' . $_GET['uid'];
dummy_form();

echo '<p>First seen <strong class="help" title="' . format_date($id_first_seen) . '">' . calculate_age($id_first_seen) . ' ago</strong> using the IP address <strong><a href="'.DOMAIN.'IP_address/' . $id_ip_address . '">' . $id_ip_address . '</a></strong> (';

// If there's a valid host name.
if($id_hostname) {
	echo '<strong>' . $id_hostname . '</strong>';
} else {
	echo 'no valid host name';
}

echo ') and last seen <strong class="help" title="' . format_date($id_last_seen) . '">' . calculate_age($id_last_seen) . ' ago</strong> using the';

if($last_ip!=$id_ip_address){
	
	echo ' IP address <strong><a href="'.DOMAIN.'IP_address/' . $last_ip . '">' . $last_ip . '</a></strong> (';
	
	// If there's a valid host name.
	if($last_ip_hostname) {
		echo '<strong>' . $last_ip_hostname . '</strong>';
	} else {
		echo 'no valid host name';
	}
	echo ")";
}else{
	echo " same IP address";
}
echo ', has started <strong>' . $id_num_topics . '</strong> existing topic' . ($id_num_topics == 1 ? '' : 's') . ' and posted <strong>' . $id_num_replies . '</strong> existing repl' . ($id_num_replies == 1 ? 'y' : 'ies') . '.</p>';
echo '<p>'.$activity."</p>";

if ($banned) {
	echo '<p>This UID is currently <strong>banned</strong>. The ban was filed <span class="help" title="' . format_date($ban_filed) . '">' . calculate_age($ban_filed) . ' ago</span> and will ';
	if ($ban_expiry == 0) {
		echo 'last indefinitely';
	} else {
		echo 'expire in ' . calculate_age($ban_expiry);
	}
	echo '.</p>';
}

if(allowed("ban_uid")) {
?>
<form action="" method="post">
	<?php csrf_token() ?>
	<div class="row">
		<label for="ban_length" class="inline">Ban length&nbsp;&nbsp;</label>
		<input type="text" name="ban_length" id="ban_length" value="<?php if( ! $banned) echo '1 week' ?>" class="inline" />

<br />
		<label for="ban_length" class="inline">Ban reason</label>
		<input type="text" maxlength="255" name="ban_reason" id="ban_reason" value="<?php if( $banreason) echo htmlspecialchars($banreason); ?>" class="inline" />
		<input type="submit" name="do_ban" value="<?php echo ($banned) ? 'Update ban length' : 'Ban' ?>" class="inline" />
		<span class="unimportant">(A ban length of "indefinite" will never expire.)</span><br />
	</div>
</form>
<?php } ?>
<ul class="menu">
<?php
if(!allowed("manage_reporting", $_GET['uid']) && allowed("manage_reporting") && $allow_user_report) echo '<li><a onclick="return submitDummyForm(\''.DOMAIN.'disable_reporting/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really disallow this user from reporting?\');" href="'.DOMAIN.'disable_reporting/' . $_GET['uid'] . '">Disallow reporting</a></li>';

if(!allowed("manage_reporting", $_GET['uid']) && allowed("manage_reporting") && !$allow_user_report) echo '<li><a onclick="return submitDummyForm(\''.DOMAIN.'enable_reporting/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really allow this user to report?\');" href="'.DOMAIN.'enable_reporting/' . $_GET['uid'] . '">Allow reporting</a></li>';

if($banned && allowed("ban_uid")) echo '<li><a onclick="return submitDummyForm(\''.DOMAIN.'unban_poster/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really unban this poster?\');" href="'.DOMAIN.'unban_poster/' . $_GET['uid'] . '">Unban</a></li>';
echo '<li><a href="'.DOMAIN.'compose_message/' . $_GET['uid'] . '">Send PM</a>';
/*
if( ! $banned) {
	echo '<li><a href="'.DOMAIN.'ban_poster/' . $_GET['uid'] . '" onclick="return submitDummyForm(\''.DOMAIN.'ban_poster/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really ban this poster?\');">Ban ID</a></li>';
	if($administrator) echo '<li><a href="'.DOMAIN.'perma_ban_poster/' . $_GET['uid'] . '" onclick="return submitDummyForm(\''.DOMAIN.'perma_ban_poster/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really perma ban this poster?\');">Perma Ban ID</a></li>';
} else {
	if(!($permanent && !$administrator)){
		echo '<li><a href="'.DOMAIN.'unban_poster/' . $_GET['uid'] . '" onclick="return submitDummyForm(\''.DOMAIN.'unban_poster/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really unban this poster?\');">Unban ID</a></li>';
	}
}
*/
if($administrator) echo '<li><a href="'.DOMAIN.'nuke_ID/' . $_GET['uid'] . '" onclick="return submitDummyForm(\''.DOMAIN.'nuke_ID/' . $_GET['uid'] . '\', \'id\', \'' . $_GET['uid'] . '\', \'Really delete all topics and replies by this poster?\');">Delete all posts</a></li>';
echo '<li><a href="'.DOMAIN.'stalk/uid/' . $_GET['uid'] . '">Stalk</a>';
echo '</ul>';

if($administrator) {
	echo '<form action="" method="post"><h4 class="section">Permissions</h4>';
	$permissions[$_GET['uid']] = null; // Clear cached permissions for user, just in case.
	$counter = 0;
	echo '<table><tr>';
	foreach(get_all_permissions() as $permission) {
		$name = htmlspecialchars($permission);
		echo "<td class='minimal'><input name='permissions[$name]' class='inline' id='$name' type='checkbox' value='1' " . (allowed($permission, $_GET['uid']) ? "checked='checked'" : "") . "/> <label for='$name'>$name</label></td>";
		$counter++;
		if($counter>5) {
			$counter = 0;
			echo "</td><tr>";
		}
		
	}
	
	echo "</tr></table><input type='submit' name='do_permissions' value='Save' /></form>";
}

echo '<h4 class="section">Notes</h4>';
?>
<form action="" method="post">
		<div style='position: relative; display:inline'>
		<textarea name="mod_notes" id="mod_notes" cols="40" rows="5" style='width:100%' class="inline"><?php echo htmlspecialchars($mod_notes); ?></textarea>
		<input type="submit" name="do_notes" value="Save" style='position: absolute; right: 0' />
		</div>
</form>
<?php

echo '<h4 class="section">Statistics</h4>';
print_statistics($_GET['uid'], false);

if($id_num_topics > 0) {
	echo '<h4 class="section">Topics</h4>';

	$stmt = $link->db_exec('SELECT id, time, replies, visits, headline, author_ip, namefag, tripfag FROM topics WHERE deleted = 0 AND author = %1 ORDER BY id DESC', $_GET['uid']);

	$topics = new table();
	$columns = array
	(
		'Headline',
		'Name',
		'IP address',
		'Replies',
		'Visits',
		'Age ▼'
	);
	$topics->define_columns($columns, 'Headline');
	$topics->add_td_class('Headline', 'topic_headline');
	
	while(list($topic_id, $topic_time, $topic_replies, $topic_visits, $topic_headline, $topic_ip_address, $namefag, $tripfag) = $link->fetch_row($stmt)) 
	{
		$name = "Anonymous";
		if($namefag){
			$name = "<b>$namefag</b>";
			if($tripfag) $name .= " ";
		}
		if($tripfag){
			$name .= $tripfag;
		}
		
		$values = array 
		(
			'<a href="'.DOMAIN.'topic/' . $topic_id . '">' . htmlspecialchars($topic_headline) . '</a>',
			$name,
			'<a href="'.DOMAIN.'IP_address/' . $topic_ip_address . '">' . $topic_ip_address . '</a>',
			replies($topic_id, $topic_replies),
			format_number($topic_visits),
			'<span class="help" title="' . format_date($topic_time) . '">' . calculate_age($topic_time) . '</span>'
		);
								
		$topics->row($values);
	}
	echo $topics->output();
}

if($id_num_replies > 0) {
	echo '<h4 class="section">Replies</h4>';

	$stmt = $link->db_exec('SELECT replies.id, replies.parent_id, replies.time, replies.body, replies.author_ip, topics.headline, topics.time, replies.namefag, replies.tripfag FROM replies INNER JOIN topics ON replies.parent_id = topics.id WHERE replies.author = %1 AND replies.deleted = 0 AND topics.deleted = 0 ORDER BY id DESC', $_GET['uid']);
	
	$replies = new table();
	$columns = array
	(
		'Reply snippet',
		'Topic',
		'Name',
		'IP address',
		'Age ▼'
	);
	$replies->define_columns($columns, 'Topic');
	$replies->add_td_class('Topic', 'topic_headline');
	$replies->add_td_class('Reply snippet', 'reply_body_snippet');

	while(list($reply_id, $parent_id, $reply_time, $reply_body, $reply_ip_address, $topic_headline, $topic_time, $namefag, $tripfag) = $link->fetch_row($stmt)) 
	{
		$name = "Anonymous";
		if($namefag){
			$name = "<b>$namefag</b>";
			if($tripfag) $name .= " ";
		}
		if($tripfag){
			$name .= $tripfag;
		}
		$values = array
		(
//			'<div class="profile_fix"><a href="'.DOMAIN.'topic/' . $parent_id . '#reply_' . $reply_id . '">' . snippet($reply_body) . '</a></div>',
			'<a href="'.DOMAIN.'topic/' . $parent_id . '#reply_' . $reply_id . '">' . snippet($reply_body) . '</a>',
			'<a href="'.DOMAIN.'topic/' . $parent_id . '">' . htmlspecialchars($topic_headline) . '</a> <span class="help unimportant" title="' . format_date($topic_time) . '">(' . calculate_age($topic_time) . ' old)</span>',
			$name,
			'<a href="'.DOMAIN.'IP_address/' . $reply_ip_address . '">' . $reply_ip_address . '</a>',
			'<span class="help" title="' . format_date($reply_time) . '">' . calculate_age($reply_time) . '</span>'
		);
		$replies->row($values);
	}
	echo $replies->output();
}

if($trash = show_trash($_GET['uid'])) {
	echo '<h4 class="section">Trash</h4>' . $trash;
}
require('includes/footer.php');
?>
