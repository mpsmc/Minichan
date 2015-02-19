<?php
require('includes/header.php');
force_id();
require_once("includes/recaptchalib.php");

$page_title = "Report to Moderator";

$link->db_exec("SELECT report_allowed FROM users WHERE uid = %1", $_SESSION['UID']);
list($allow_user_report) = $link->fetch_row();

if(!$allow_user_report || !GOODREP){
	echo "Unfortunately you are not allowed to file reports.";
	require('includes/footer.php');
	die();
}

//$link->db_exec("SELECT COUNT(*) FROM reports where uid = ? AND time > NOW() - whatever")

$needscaptcha = USER_REPLIES < POSTS_TO_DEFY_DEFCON_3*10;

$topic_id = $_GET['topic'];
$reply_id = $_GET['reply'];

$store_report = false;

if ($_POST["recaptcha_response_field"]) {
	$resp = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY,
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);

	if ($resp->is_valid) {
			unset($_POST['recaptcha_challenge_field']);
			unset($_POST['recaptcha_response_field']);
			
			$store_report = true;
	} else {
			# set the error code so that we can display it
			$error = $resp->error;
	}
}else if(!$needscaptcha && isset($_POST['comment'])) {
	$store_report = true;
}

$link->db_exec("SELECT headline FROM topics WHERE id = %1", $topic_id);
list($topic_headline) = $link->fetch_row();

if(!$topic_headline) {
	add_error("This appears to be an invalid topic", true);
}

if(!$store_report){
	echo "Use this function to inform the Moderators and Administrators of illegal content.<br />";
	echo "<i>Please note that abuse of this feature may have your posting rights revoked.</i><br /><br />";
	echo "<form action='' method='post'>";
	echo "Enter comment: <input type='text' value='" . htmlspecialchars($_POST['comment']) . "' name='comment' size='50' maxlength='512'><br />";
	if($needscaptcha) {
		echo "Enter captcha:";
		echo recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, $error);
		echo "<br />";
	}
	echo "<input name='report' type='submit' value='Submit report' />";
	echo "</form>";
}else{
	$insert["ip_address"] = $_SERVER['REMOTE_ADDR'];
	$insert["uid"] = $_SESSION['UID'];
	$insert["topic"] = $topic_id;
	$insert["reply"] = $reply_id;
	$insert["reason"] = substr($_POST['comment'], 0, 512);
	$link->insert("reports", $insert);
	
	
	$topiclink = DOMAIN . "topic/" . $insert['topic'];
	if($insert["reply"]) $topiclink .= "#reply_"  . $insert['reply'];
	$handlelink = DOMAIN . "reports/handle/" . $insert['topic'];
	if($insert['reply']) $handlelink .= '/' . $insert['reply'];
	
	log_irc(chr(2) . "New report:" . chr(2) . " " . snippet($insert["reason"], 200, false, false) . chr(2) . " Handle: "  . chr(2) . create_link($handlelink) . chr(2) . " Link: " . chr(2) . create_link($topiclink), true);
	
	$_SESSION['notice'] = "Your report has been stored and will be looked at shortly";
	header("Location: " . DOMAIN);die();
}
require('includes/footer.php');
