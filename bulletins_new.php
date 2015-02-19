<?php
require 'includes/header.php';
force_id();
$page_title = "New bulletin";
update_activity('bulletins_new', 1);

// Check if user bulletins are disabled.
if(!allowed("manage_bulletins")) {
	if(!ALLOW_USER_BULLETINS) {
		$_SESSION['notice']="The posting of bulletins by users has currently been disabled"; header("Location: ".DOMAIN); exit("");
	}
}

$additional_head = "";
$required_posts = REQ_BULLETIN_POSTS; // Check required post amount.
$time_between_bulletins = TIME_BULLETINS; // Check time between bulletins posts.
$pre_moderate = PRE_MODERATE_BULLETINS; // Check if we need to pre-moderate bulletins.

function display_new_bulletin($error, $text="", $id=-1) {
	global $link, $administrator, $required_posts, $last_actions, $pre_moderate, $_start_time;
	$page_title = "New bulletin";
	$additional_head = "";
	// Check to see if user can post bulletins.
	$_SESSION['UID'] = mysql_real_escape_string($_SESSION['UID'], $link->getLink());
	$sql = "SELECT * FROM topics WHERE author='$_SESSION[UID]'";
	if(!$send = mysql_query($sql,$link->getLink())) {
	exit (mysql_error($link->getLink()));
	}
	$num_t = mysql_num_rows($send);

	$sql = "SELECT * FROM replies WHERE author='$_SESSION[UID]'";
	if(!$send = mysql_query($sql,$link->getLink())) {
	exit (mysql_error($link->getLink()));
	}
	$num_r = mysql_num_rows($send);
	
	$total = $num_t + $num_r;
	
	echo '<p> Bulletins are short bits of news or updates about the site posted by the management';
	if(ALLOW_USER_BULLETINS) {
	echo ', but regulars can post them too (The bulletins first have to be accepted by the Administrators or Moderators)';
	}
	echo '<script type="text/javascript"> printCharactersRemaining(\'numCharactersLeftForBulletin\', 512); </script>.';
	
	if($error) {
	echo '<p>'.$error.'</p>'; require 'includes/footer.php'; exit("");
	}
	
	// Print this when user bulletins are disabled and people manage to get here.
	if(!allowed("manage_bulletins")) {
		if(!ALLOW_USER_BULLETINS) {
			echo '<p>You can not post bulletins at this time.</p>'; require 'includes/footer.php'; exit("");
		}
	}
	
	if(allowed("manage_bulletins") || $total >= $required_posts) {
		if($id==-1){
			$val = "Post Bulletin";
		} else {
			$val = "Edit Bulletin";
		}
		echo '<form id="bulletin_form" name="bulletin_form" action="" method="POST">';
		csrf_token();
		echo '<div class="row"><textarea id="bulletin" name="bulletin" cols="120" rows="4" onkeydown="updateCharactersRemaining(\'bulletin\', \'numCharactersLeftForBulletin\', 512);" onkeyup="updateCharactersRemaining(\'bulletin\', \'numCharactersLeftForBulletin\', 512);">'.$text.'</textarea></div>
		<div class="row"><input name="submit" value="'.$val.'" type="submit" /> </div> </form>';
	}
	else echo '<p> You can not post bulletins at this time.</p>';
	require 'includes/footer.php';
}

function post_bulletin($id=-1) {
	global $link, $administrator, $required_posts, $pre_moderate, $time_between_bulletins;
	
// Check if user bulletins are disabled.
if(!allowed("manage_bulletins")) {
	if(!ALLOW_USER_BULLETINS) {
		$_SESSION['notice']="The posting of bulletins by users has currently been disabled."; header("Location: ".DOMAIN."");
	}
}
	
// Check if user has enough posts.
if(!allowed("manage_bulletins")) {	
	$_SESSION['UID'] = mysql_real_escape_string($_SESSION['UID']);
	$sql = "SELECT * FROM topics WHERE author='$_SESSION[UID]'";
		if(!$send = mysql_query($sql,$link->getLink())) {
			exit (mysql_error($link->getLink()));
		}
	$num_t = mysql_num_rows($send);
	$sql = "SELECT * FROM replies WHERE author='$_SESSION[UID]'";
		if(!$send = mysql_query($sql,$link->getLink())) {
			exit (mysql_error($link->getLink()));
		}
	$num_r = mysql_num_rows($send);
	$total = $num_t + $num_r;

	// Now we check and return them back if they are not allowed.
	if($total < $required_posts || $num) {
	exit (display_new_bulletin("You do not have permission to post bulletins."));
	}
}
	
	// More checking.
	if(!allowed("manage_bulletins") && $pre_moderate) {
		$sql = "SELECT * FROM pre_bulletins WHERE uid='$_SESSION[UID]'";
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
		if($num = mysql_num_rows($send)) {
		exit (display_new_bulletin("You already have a pending bulletin in the bulletins queue."));
		}
	}
	
	// Check time between bulletin posts.
	if(!allowed("manage_bulletins")) {
		$sql = "SELECT * FROM bulletins WHERE uid='uid'";
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
		while($get = mysql_fetch_array($send)) {
		$time = $get['time']; $diff = time() - $time; if($diff<$time_between_bulletins) {
			exit (display_new_bulletin("You must wait at least 5 minutes before posting a new bulletin."));
			}
		}
		
		$sql = "SELECT * FROM bulletins WHERE ip='$_SERVER[REMOTE_ADDR]'";
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
		while($get = mysql_fetch_array($send)) {
		$time = $get['time']; $diff = time() - $time; if($diff<$time_between_bulletins) {
			exit (display_new_bulletin("You must wait at least 5 minutes before posting a new bulletin."));
			}
		}
	}

	if(strlen($_POST['bulletin'])>512){
	$_POST['bulletin'] = substr($_POST['bulletin'],0,512) . "...";
	}
	// CSRF checking.
	check_token();
//	$_POST['bulletin'] = $_POST['bulletin'];
	$_POST['bulletin'] = mysql_real_escape_string($_POST['bulletin'], $link->getLink());	// Safe sex, really.
	
	// Set the name of the poster and uid.
	if($administrator){
	$poster = "<a href=\"".DOMAIN."admin\"><strong>".ADMIN_NAME."</strong></a>";
	} elseif(allowed("mod_hyperlink")) {
	$poster = "<a href=\"".DOMAIN."mod\">Mod</a>";
//	$poster = "<strong>?</strong>";
	} else {
	$poster = "?";
	}
	
	$uid = $_SESSION['UID'];
	$time = $_SERVER['REQUEST_TIME'];
	$date = format_date($time);
	$ip = $_SERVER['REMOTE_ADDR'];
	
	if(allowed("manage_bulletins") || !$pre_moderate) {
		if($id==-1){
			$sql = "INSERT INTO bulletins (message,poster,time,date,uid,ip) VALUES ('$_POST[bulletin]','$poster','$time','$date','$uid','$ip')";
		} else {
			$sql = "UPDATE bulletins SET ".
			"message = '$_POST[bulletin]' ".
			"WHERE no = " . $id;
		}
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
	} else {
		$sql = "INSERT INTO pre_bulletins (message,poster,time,date,uid,ip) VALUES ('$_POST[bulletin]','$poster','$time','$date','$uid','$ip')";
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
	}
	// Update last action.
	if(!$pre_moderate || allowed("manage_bulletins")){
	$sql = "UPDATE last_actions SET time='$time' WHERE feature='last_bulletin'";
		if(!$send = mysql_query($sql,$link->getLink())) {
		exit (mysql_error($link->getLink()));
		}
	}
	if($pre_moderate && !allowed("manage_bulletins")) {
	$_SESSION['notice'] = "Bulletin posted.";
	} else {
	$_SESSION['notice'] = "Bulletin posted.";
	}
	header("Location: ".DOMAIN."bulletins");
}

// Decide wether to post bulletin or display the form.
if(isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0 && allowed("manage_bulletins")) { // Probably not the best place to put this. Bite me.
	$id = $_GET['edit'];
	$sql = "SELECT * FROM bulletins WHERE no = " . $id . ' LIMIT 1';
	$bulletin = mysql_query($sql, $link->getLink());
	$line = mysql_fetch_assoc($bulletin);
	if(!$administrator) {
		$posterID = $line['uid'];
		if(allowed("manage_bulletins", $posterID))  {
			$_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
			exit(header("Location: ".DOMAIN."bulletins"));
		}
	}
	$text = $line["message"];
	if(!$text){
		$id=-1;
		$text="";
	}
} else {
	$text = "";
	$id = -1;
}
if(!isset($_POST['submit'])) {
	display_new_bulletin("", $text, $id);
	} else {
	post_bulletin($id);
}
?>
