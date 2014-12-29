<?php
require('includes/header.php');

// Validate and fetch topic info.
if (!ctype_digit($_GET['id'])) {
	add_error('Invalid ID.', true);
}

if($new_citations) {
        $link->db_exec('DELETE FROM citations WHERE uid = %1 AND topic = %2', $_SESSION['UID'], $_GET['id']);
        $citations_deleted = $link->affected_rows();
		$new_citations -= $citations_deleted;
		
		if($citations_deleted > 0) {
			remove_notification('citation', $_SESSION['UID'], null, $_GET['id']);
		}
}

function create_no_link($var) {
	return $var;
}

/*
if($_SESSION['UID'] == "4c2d4fcde75992.06627601" && $_GET['id'] != 19735) {
	$_SESSION['notice'] = "Please reply to this topic first.";
	header("Location: http://minichan.org/topic/19735");
	die();
}
*/

$link->db_exec("SELECT t1.report_allowed, t2.image_viewer, t2.disable_images FROM users AS t1 LEFT JOIN user_settings AS t2 ON t1.uid = t2.uid WHERE t1.uid = %1", $_SESSION['UID']);
list($allow_user_report, $user_image_viewer, $user_disable_images) = $link->fetch_row();

if (ALLOW_IMAGES || ALLOW_IMGUR) {
	$stmt = $link->db_exec('SELECT topics.flag, topics.time, topics.author, topics.visits, topics.replies, topics.headline, topics.body, topics.edit_time, topics.edit_mod, images.file_name, topics.namefag, topics.tripfag, topics.sticky, topics.locked, topics.deleted, topics.last_post, images.thumb_width, images.thumb_height, images.img_external, images.thumb_external, topics.poll, topics.post_html, topics.admin_hyperlink, topics.secret_id FROM topics LEFT OUTER JOIN images ON topics.id = images.topic_id WHERE topics.id = %1', $_GET['id']);
} else {
	$stmt = $link->db_exec('SELECT flag, time, author, visits, replies, headline, body, edit_time, edit_mod, namefag, tripfag, sticky, locked, deleted, last_post, poll, post_html, admin_hyperlink, secret_id FROM topics WHERE id = %1', $_GET['id']);
}
if ($link->num_rows($stmt) < 1) {
	$page_title = 'Non-existent topic';
	update_activity('nonexistent_topic');
	add_error('There is no such topic. It may have been deleted.', true);
}

if (ALLOW_IMAGES || ALLOW_IMGUR) {
	list($topic_flag, $topic_time, $topic_author, $topic_visits, $topic_replies, $topic_headline, $topic_body, $topic_edit_time, $topic_edit_mod, $topic_image_name, $opnamefag, $optripfag, $sticky, $locked, $deleted, $last_reply_time, $thumb_width, $thumb_height, $topic_img_external, $topic_thumb_external, $show_poll, $topic_html, $topic_hyperlink, $secret_id) = $link->fetch_row($stmt);
} else {
	list($topic_flag, $topic_time, $topic_author, $topic_visits, $topic_replies, $topic_headline, $topic_body, $topic_edit_time, $topic_edit_mod, $opnamefag, $optripfag, $sticky, $locked, $deleted, $last_reply_time, $show_poll, $topic_html, $topic_hyperlink, $secret_id) = $link->fetch_row($stmt);
}
$link->free_result($stmt);

if(!ENABLE_POLLS) $show_poll = false;

if($deleted && !allowed("undelete")){
	add_error('There is no such topic. It may have been deleted.', true);
}

if($secret_id && !allowed('minecraft'))
	add_error('There is no such topic. It may have been deleted.', true);

if(!$locked){
	if((time()-$last_reply_time)>NECRO_BUMP_TIME && $last_reply_time){
		$locked = true;
	}
		
	if((time()-$topic_time)>NECRO_BUMP_TIME && !$last_reply_time){
		$locked = true;
	}
}

if($deleted){
//	echo "<div style='position:fixed; top:0px; right:1px; background-color: lightYellow; border: 1px solid black'><center><b>Deleted topic</b></center></div>";
	?><div id="notice" style="position:fixed; top:0px; right:1px;">
	Browsing a deleted topic, woohoo!
	</div><?php
}

update_activity('topic', $_GET['id']);

$page_title = 'Topic: ' . htmlspecialchars($topic_headline, ENT_COMPAT | ENT_HTML401, "");

// Increment visit count.
if (!isset($_SESSION['visited_topics'][$_GET['id']]) && isset($_COOKIE['SID'])) {
	$_SESSION['visited_topics'][$_GET['id']] = 1;
	
	$increment_visits = $link->db_exec('UPDATE topics SET visits = visits + 1 WHERE id = "%1"', $_GET['id']);
}

$last_read_post = $visited_topics[$_GET['id']];

// Set visited cookie.
/*
if ($last_read_post !== $topic_replies) {
	// Build cookie.
	// Add the current topic:
	$visited_topics = array(
		$_GET['id'] => $topic_replies
	) + $visited_topics;
	// Readd old topics.
	foreach ($visited_topics as $cur_topic_id => $num_replies) {
		// If the cookie is getting too long (4kb), stop.
		if (strlen($cookie_string) > 3900) {
			break;
		}
		$cookie_string .= 't' . $cur_topic_id . 'n' . $num_replies;
	}
	setcookie('topic_visits', $cookie_string, $_SERVER['REQUEST_TIME'] + 604800, '/', COOKIE_DOMAIN);
}
*/

// New way of storing visited topics
if($_SESSION['UID']) {
	$link->insertorupdate('read_topics', array(
		"uid" => $_SESSION['UID'],
		"topic" => $_GET['id'],
		"replies" => ($topic_replies) ? $topic_replies : '0',
		"time" => time()
	));
}

// If ostrich mode is enabled, fetch a list of blacklisted phrases.
$ignored_phrases = fetch_ignore_list();

// Output dummy form. This is for JavaScript submissions to action.php.
dummy_form();

if($deleted){
	echo "<h3>Delete info</h3>";
	$link->db_exec("SELECT mod_UID, mod_ip, time FROM mod_actions WHERE target = %1 AND action = 'delete_topic' ORDER BY time DESC LIMIT 1", $_GET['id']);
	list($mod_uid, $mod_ip, $time) = $link->fetch_row();
	echo "<div class='body'>";
	echo "This topic was deleted <a class='help' title='" . format_date($time) . "'>" . calculate_age($time) . "</a> ago by <a href='".DOMAIN."profile/".$mod_uid."'>".modname($mod_uid)."</a> with the ip address <a href='".DOMAIN."IP_address/".$mod_ip."'>".$mod_ip."</a>";
	echo "</div>";
}

// Output OP.
echo '<h3>';
if ($topic_author == 'admin') {
	echo '<strong><a href="'.DOMAIN.'admin">' . ADMIN_NAME . '</a></strong> ';
} else {
	$tripprefix = "";
	
	if (!HIDDEN_MODS) {
		if ($opnamefag != '' && $topic_hyperlink && allowed("mod_hyperlink", $topic_author) && !in_array($topic_author, $administrators)) {
			echo '<a href="'.DOMAIN.'mod">';
			$tripprefix = "</a>";
		}
	}
	if (!HIDDEN_ADMINS) {
		if ($opnamefag != '' && $topic_hyperlink && in_array($topic_author, $administrators)) {
			echo '<a href="'.DOMAIN.'admin">';
			$tripprefix = "</a>";
		}
	}
	if (!$opnamefag && !$optripfag) {
		echo 'Anonymous <strong>A</strong>';
	} else {
		if ($tripprefix) {
			$optripfag = $tripprefix . $optripfag;
		}
		echo '<strong>' . htmlspecialchars(trim($opnamefag)) . '</strong>' . $optripfag;
		if(check_gold($topic_author)){
			if(!MOBILE_MODE){
				echo ' <a style="vertical-align: bottom;text-decoration: none;color:#edff2a" title="Jew" href="#">&#9733;</a>';			
			}else{
				echo ' <a style="vertical-align: bottom;text-decoration: none;color:#edff2a" title="Jew" href="#">&#9733;</a>';			
//				echo ' <a style="vertical-align: bottom;text-decoration: none;" title="Gold member" href="'.DOMAIN.'gold_account"><img style="border: none" src="'.DOMAIN.'style/star.png" /></a>';	
			}
		}
	}

	if($topic_flag != null) echo ' <img style="border: none; vertical-align:middle" src="'.DOMAIN.'flags/'.$topic_flag.'.png" />';
	
	echo ' ';
}
if ($topic_author == $_SESSION['UID']) {
	echo '(you) ';
}
if(!MOBILE_MODE){
echo 'started this discussion <strong><span class="help" title="' . format_date($topic_time) . '">' . calculate_age($topic_time) . ' ago</span> <span class="reply_id unimportant"><a href="'.DOMAIN.'topic/' . $_GET['id'] . '">#' . number_format($_GET['id']) . '</a></span></strong></h3> <div class="body">';
}else{
	echo 'posted <strong>' . calculate_age($topic_time) . ' ago <span class="reply_id unimportant"><a href="'.DOMAIN.'topic/' . $_GET['id'] . '">#' . number_format($_GET['id']) . '</a></span></strong></h3> <div class="body">';
}
if($user_disable_images != 1) { 
	if($topic_img_external && $topic_thumb_external) {
		if(MOBILE_MODE){
			$new_thumb_width = min($thumb_width, 100);
			$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
			$thumb_width = $new_thumb_width;
		}
		
		echo '<a href="'.htmlspecialchars($topic_img_external) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'. htmlspecialchars($topic_thumb_external) . '" alt="Externally hosted image" title="Externally hosted image" /></a>';
		
	}elseif ($topic_image_name) {
		if(!$thumb_width&&!$thumb_height){
			$thumb_dimensions = getimagesize("thumbs/".$topic_image_name);
			$thumb_width = $thumb_dimensions[0];
			$thumb_height = $thumb_dimensions[1];
			$link->update("images", array("thumb_width"=>$thumb_width, "thumb_height"=>$thumb_height), "file_name='".$link->escape($topic_image_name)."'");
		}
		if(MOBILE_MODE){
			$new_thumb_width = min($thumb_width, 100);
			$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
			$thumb_width = $new_thumb_width;
		}
		if(file_exists("img/".$topic_image_name)){
			if(!file_exists("thumbs/".$topic_image_name)){
				thumbnail("img/" . $topic_image_name, $topic_image_name, end(explode(".", $topic_image_name)));
			}
			echo '<a href="'.STATIC_DOMAIN.'img/' . htmlspecialchars($topic_image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($topic_image_name) . '" alt="" /></a>';
		}else{
			if(file_exists("thumbs/".$topic_image_name)){
				echo '<a href="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($topic_image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($topic_image_name) . '" alt="" /></a>';
			}else{
				echo '<a missing='.$topic_image_name.' href="http://minichan.org/topic/6346"><img width="147" height="180" src="'.DOMAIN.'style/deleted.png" alt="Image went missing" /></a>';
			}
		}
		
	}
}

if($topic_html) {
	echo $topic_body;
}else{
	echo parse($topic_body);
}

edited_message($topic_time, $topic_edit_time, $topic_edit_mod);

echo '<ul class="menu">';

$reply_body_quickquote = trim(preg_replace('/^@([0-9,]+|OP)/m', '', $topic_body));
$reply_body_quickquote = preg_replace('/^/m', '> ', $reply_body_quickquote);
$reply_body_quickquote = urlencode($reply_body_quickquote);

	
	if ($topic_author == $_SESSION['UID'] && TIME_TO_EDIT == 0 || $topic_author == $_SESSION['UID'] && ($_SERVER['REQUEST_TIME'] - $topic_time < (TIME_TO_EDIT * ($gold_account+1))) || allowed("edit_post")) {
	echo '<li><a class="editButton editOP" href="'.DOMAIN.'edit_topic/' . $_GET['id'] . '">Edit</a></li>';
}

if(allowed("open_profile")) echo '<li><a class="topic_profile_link" href="'. DOMAIN.'profile/' . $topic_author . '">Profile</a></li>';
if(allowed("mod_pm")) echo '<li><a href="'.DOMAIN.'compose_message/' . $topic_author . '">PM</a></li>';
if(!$sticky && allowed("stick_topic")) echo '<li><a href="'.DOMAIN.'stick_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'stick_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Stick this topic?\');">Stick</a></li>';
if($sticky && allowed("stick_topic"))  echo '<li><a href="'.DOMAIN.'unstick_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'unstick_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Unstick this topic?\');">Unstick</a></li>';
if(!$locked && allowed("lock_topic")) echo '<li><a href="'.DOMAIN.'lock_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'lock_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Really lock this topic?\');">Lock</a></li>';
if($locked && allowed("lock_topic"))  echo '<li><a href="'.DOMAIN.'unlock_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'unlock_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Unlock this topic?\');">Unlock</a></li>';
if(!$deleted) { 
	if(allowed("delete")) echo '<li><a href="'.DOMAIN.'delete_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'delete_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Really delete this topic?\');">Delete</a></li>';
}else{
	if(allowed("undelete")) echo '<li><a href="'.DOMAIN.'undelete_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'undelete_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Really undelete this topic?\');">Undelete</a></li>';
}
if(allowed("delete_image") && ($topic_image_name || ($topic_img_external && $topic_thumb_external))) echo '<li><a href="'.DOMAIN.'delete_image/topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'delete_image/topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Really delete this image?\');">Delete Image</a></li>';
if(allowed("set_image") && !($topic_image_name || ($topic_img_external && $topic_thumb_external))) echo '<li><a href="'.DOMAIN.'set_image/topic/' . $_GET['id'] . '" onclick="return chooseImage(this);">Set Image</a></li>';
if(allowed("set_time")) {
	echo '<li><a href="'.DOMAIN.'action.php?action=set_time&id=' . $_GET['id'] . '" onclick="return submitSetTime(this);">Bump</a></li>';
}
if(allowed("delete") || allowed("undelete") || allowed("delete_image") || allowed("lock_topic") || allowed("set_time") || allowed("stick_topic") || allowed("open_profile") || allowed("mod_pm")) echo "<li><strong>|</strong></li>";
	
//echo '<li><a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&pubid=ra-4db55d8a51bfcd4c"><span>Share</span></a></li>';

	if($allow_user_report && GOODREP)
		echo '<li><a href="'.DOMAIN.'report_topic/' . $_GET['id'] . '">Report</a></li>';
		
	$check_watchlist = $link->db_exec('SELECT count(uid) FROM watchlists WHERE uid = %1 AND topic_id = %2', $_SESSION['UID'], $_GET['id']);
	list($num_rows) = $link->fetch_row($check_watchlist);
	if ($num_rows == 0) {
		echo '<li><a href="'.DOMAIN.'watch_topic/' . $_GET['id'] . '" onclick="return submitDummyForm(\''.DOMAIN.'watch_topic/' . $_GET['id'] . '\', \'id\', ' . $_GET['id'] . ', \'Add this topic to the watchlist?\');">Watch</a></li> ';
	} else {
		echo '<li><a href="'.DOMAIN.'watchlist">Unwatch</a></li> ';
		remove_notification('watchlist', $_SESSION['UID'], null, $_GET['id']);
	}
	
	if(!$locked || allowed("lock_topic")) echo '<li><a href="'.DOMAIN.'new_reply/' . $_GET['id'] . '/quote_topic" onclick="quickQuote(\'OP\', \''.$reply_body_quickquote.'\');return false;">Quote</a></li>';
	echo '<li><a href="'.DOMAIN.'trivia_for_topic/' . $_GET['id'] . '" class="help" title="' . $topic_replies . ' repl' . ($topic_replies == 1 ? 'y' : 'ies') . '">' . $topic_visits . ' visit' . ($topic_visits == 1 ? '' : 's') . '</a></li>';
	
	
echo "</ul></div>";
	
if($show_poll) {
	$check_votes = $link->db_exec('SELECT option_id FROM poll_votes WHERE (ip = %1 OR uid = %2) AND parent_id = %3', $_SERVER['REMOTE_ADDR'], $_SESSION['UID'], $_GET['id']);
	list($voted) = $link->fetch_row($check_votes);
	if(!$voted && !$locked && GOODREP) {
		echo '<form action="' . DOMAIN . 'cast_vote/' . $_GET['id'] . '" method="POST">';
		csrf_token();
	}
	
	$table = new table();
	
	$columns = array
	(
		'Poll option',
		'Votes',
		'Percentage',
		'Graph'
	);
	$table->define_columns($columns, 'Poll option');
	
	$link->db_exec("SELECT sum(votes) FROM poll_options WHERE topic_id = %1", $_GET['id']);
	list($poll_votes) = $link->fetch_row();
	
	$options = $link->db_exec('SELECT id, content, votes FROM poll_options WHERE topic_id = %1 GROUP BY id', $_GET['id']);
	while(list($option_id, $option_text, $option_votes) = $link->fetch_row($options)) {
		if($poll_votes == 0) {
			$percent = 0;
		}else{
			$percent = round(100 * ($option_votes / $poll_votes));
		}
	
		$values = array
		(
			htmlspecialchars($option_text),
			format_number($option_votes),
			$percent . '%',
			'<div class="bar_container help" style="width: 130px; padding:1px; border:1px solid #555" title=" ' . $option_votes . ' of ' . $poll_votes . ' "><div class="bar" style="width: ' . $percent . '%; height:.9em; background-color:#990000;"></div></div>'
		);
		
		if(!$voted && !$locked && GOODREP) {
			$values[0] = '<input name="option_id" class="inline" value="' . $option_id . '" id="option_' . $option_id . '" type="radio" /><label for="option_' . $option_id . '" class="inline">' . $values[0] . '</label>';
		}else if($voted == $option_id) {
			$values[0] = '<strong title="You voted for this." class="help">' . $values[0] . '</strong>';
		}
		
		$table->row($values);
	}
	
	echo $table->output('options');
	if(!$voted && !$locked) {
		if(GOODREP) {
			echo '<div class="row"><input type="submit" name="cast_vote" value="Vote" /></div></form>';
		}else{
			echo '<div class="row"><strong>You need more posts to be allowed to vote.</strong></div>';
		}
	}
}

	
// Output replies.
if (ALLOW_IMAGES || ALLOW_IMGUR) {
	$stmtTopic = $link->db_exec('SELECT replies.flag, replies.id, replies.time, replies.author, replies.poster_number, replies.body, replies.edit_time, replies.edit_mod, replies.stealth_ban, images.file_name, replies.namefag, replies.tripfag, replies.author_ip, replies.deleted, replies.admin_hyperlink, replies.post_html, images.thumb_width, images.thumb_height, images.img_external, images.thumb_external FROM replies LEFT OUTER JOIN images ON replies.id = images.reply_id WHERE replies.parent_id = %1 ORDER BY id', $_GET['id']);
} else {
	$stmtTopic = $link->db_exec('SELECT flag, id, time, author, poster_number, body, edit_time, edit_mod, namefag, tripfag, author_ip, deleted, stealth_ban, admin_hyperlink, post_html FROM replies WHERE parent_id = %1 ORDER BY id', $_GET['id']);
}

$reply_ids          = array();
$posters            = array();
$hidden_replies     = array(); // Ostrich mode.
$previous_poster    = $topic_author;
$previous_post_time = $topic_time;
$posts_in_row       = 0; // Number of posts in a row by one UID.
$tuple              = array(
	1 => 'double',
	2 => 'triple',
	3 => 'quadruple',
	4 => 'quintuple',
	5 => 'sextuple',
	6 => 'septuple',
	7 => 'octuple',
	8 => 'nonuple',
	9 => 'decuple',
	10 => 'undecuple',
	11 => 'duodecuple',
	12 => 'tridecuple',
	13 => 'quattuodecuple',
	14 => 'sexadecuple',
	15 => 'sedecuple',
	16 => 'septuadecuple',
	17 => 'octadecuple'
);

function fetchReplyList(){
	global $link, $stmtTopic;
	if (ALLOW_IMAGES || ALLOW_IMGUR) {
		global $reply_flag, $reply_id, $reply_time, $reply_author, $reply_poster_number, $reply_body, $reply_edit_time, $reply_edit_mod, $reply_image_name, $namefag, $tripfag, $reply_deleted, $thumb_width, $thumb_height, $img_external, $thumb_external, $reply_stealth, $reply_ip, $reply_html, $reply_hyperlink;
		return list($reply_flag, $reply_id, $reply_time, $reply_author, $reply_poster_number, $reply_body, $reply_edit_time, $reply_edit_mod, $reply_stealth, $reply_image_name, $namefag, $tripfag, $reply_ip, $reply_deleted, $reply_hyperlink, $reply_html, $thumb_width, $thumb_height, $img_external, $thumb_external) = $link->fetch_row($stmtTopic);
	} else {
		global $reply_flag, $reply_id, $reply_time, $reply_author, $reply_poster_number, $reply_body, $reply_edit_time, $reply_edit_mod, $namefag, $tripfag, $reply_deleted, $reply_stealth, $reply_ip, $reply_hyperlink, $reply_html;
		return list($reply_flag, $reply_id, $reply_time, $reply_author, $reply_poster_number, $reply_body, $reply_edit_time, $reply_edit_mod, $namefag, $tripfag, $reply_ip, $reply_deleted, $reply_stealth, $reply_hyperlink, $reply_html) = $link->fetch_row($stmtTopic);
	}
}


function preg_replace_anchors($data){
	global $reply_ids, $hidden_replies, $previous_id, $reply_id, $anchor_cache;
	$formatted_id = $data[0];
	
	$pure_id = str_replace(array(
		'@',
		','
	), '', $formatted_id);
	
	if($anchor_cache[$formatted_id]) {
	
		if($anchor_cache[$formatted_id]){
			if ($pure_id == $previous_id) {
				$link_text = '@previous';
			} else {
				$link_text = $formatted_id;
			}
		}
		
		return str_replace(array("%link_text%", "%reply_id%", "%pure_id%"), array($link_text, $reply_id, $pure_id), $anchor_cache[$formatted_id]);
	}
	
	if (!array_key_exists($pure_id, $reply_ids)) {
		//$reply_body_parsed = str_replace($formatted_id, '<span class="unimportant">(Citing a deleted or non-existent reply.)</span>', $reply_body_parsed);
		$retval = '<span class="unimportant">(Citing a deleted or non-existent reply.)</span>';
		$anchor_cache[$formatted_id] = $retval;
		return $retval;
	} else if (in_array($pure_id, $hidden_replies)) {
		//$reply_body_parsed = str_replace($formatted_id, '<span class="unimportant help" title="' . snippet($reply_ids[$pure_id]['body']) . '">@hidden</span>', $reply_body_parsed);
		$retval = '<span class="unimportant help" title="' . snippet($reply_ids[$pure_id]['body']) . '">@hidden</span>';
		$anchor_cache[$formatted_id] = $retval;
		return $retval;
	} else {
		if ($pure_id == $previous_id) {
			$link_text = '@previous';
		} else {
			$link_text = $formatted_id;
		}
		
		
		if ($reply_ids[$pure_id]['author'] == $_SESSION['UID']) {
			$you = '<span class="unimportant"> (you)</span>';
		}
	
		if ($reply_ids[$pure_id]['deleted']) {
			$you .= '<span class="unimportant"> (deleted)</span>';
		}
		
		$retval = '<span class="unimportant"><a href="#reply_%pure_id%" onclick="createSnapbackLink(\'%reply_id%\'); return highlightReply(\'%pure_id%\');" class="help cite_reply" title="' . snippet($reply_ids[$pure_id]['body']) . '">%link_text%</a></span>' . $you;
		$anchor_cache[$formatted_id] = $retval;
		return str_replace(array("%link_text%", "%reply_id%", "%pure_id%"), array($link_text, $reply_id, $pure_id), $retval);
	}
}
$undeleted_replies = 0;
while (fetchReplyList()) {
	// Should we even bother?
	if($reply_deleted && !allowed("undelete")) continue;
	if($reply_stealth && !$administrator && $reply_author != $_SESSION['UID'] && $reply_ip != $_SERVER['REMOTE_ADDR']) continue;
	
	if($reply_stealth && $administrator) {
		$reply_deleted = true;
	}
	
	if(!$reply_deleted){ // If it's deleted, yeah... No hidey.
		if ($_COOKIE['ostrich_mode'] == 1) {
			foreach ($ignored_phrases as $ignored_phrase) {
				if (stripos($reply_body, $ignored_phrase) !== false) {
					$hidden_replies[]     = $reply_id;
					$reply_ids[$reply_id] = array(
						'body' => $reply_body,
						'author' => $reply_author
					);
					// We've encountered an ignored phrase, so skip the rest of this while() iteration.
					continue 2;
				}
			}
		}
	}
	
	if($administrator && !$reply_deleted && $last_was_delete){
		echo "<br />";
	}
	
	// We should!
	unset($out);
	$out = array(); // Output variables.
	
	if ($reply_author == 'admin') {
		$out['author'] = '<strong><a href="'.DOMAIN.'admin">' . ADMIN_NAME . '</a></strong>';
	} else {
		if (!HIDDEN_ADMINS) {
			if ($namefag != "" && $reply_hyperlink && in_array($reply_author, $administrators)) {
				$out['author'] = '<a href="'.DOMAIN.'admin">';
			}
		}
		if (!HIDDEN_MODS) {
			if ($namefag != "" && $reply_hyperlink && allowed("mod_hyperlink", $reply_author) && !in_array($reply_author, $administrators)) {
				$out['author'] = '<a href="'.DOMAIN.'mod">';
			}
		}
		if ($namefag == "" && $tripfag == "") {
			$out['author'] .= 'Anonymous <strong>';
			if ($reply_poster_number == 0) {
				$out['author'] .= 'A';
			} else {
				$out['author'] .= number_to_letter($reply_poster_number);
			}
			$out['author'] .= "</strong>";
		} else {
			if ($reply_hyperlink && $namefag != "" && (in_array($reply_author, $administrators) || allowed("mod_hyperlink", $reply_author))) {
				$tripfag = "</a>" . $tripfag;
			}
			$out['author'] .= '<strong>' . htmlspecialchars(trim($namefag)) . '</strong>' . $tripfag;
			if(check_gold($reply_author)){
				if(!MOBILE_MODE){
					$out['author'] .= ' <a style="vertical-align: bottom;text-decoration: none;color:#edff2a" title="Jew" href="#">&#9733;</a>';
				}else{
					$out['author'] .= ' <a style="vertical-align: bottom;text-decoration: none;color:#edff2a" title="Jew" href="#">&#9733;</a>';
	//				$out['author'] .= ' <a style="vertical-align: bottom;text-decoration: none;" title="Gold member" href="'.DOMAIN.'gold_account"><img style="border: none" src="'.DOMAIN.'style/star.png" /></a>';
				}
			}
		}

		if($reply_flag != null) $out['author'] .= ' <img style="border: none; vertical-align:middle" src="'.DOMAIN.'flags/'.$reply_flag.'.png" />';
	}
	
	
	if ($reply_author == $topic_author) {
		$out['author_desc'] = ' (OP';
		if ($reply_author == $_SESSION['UID']) {
			$out['author_desc'] .= ', you';
		}
		$out['author_desc'] .= ')';
	} else {
		if ($reply_author == $_SESSION['UID']) {
			$out['author_desc'] .= ' (you)';
		}
		if (!in_array($reply_author, $posters)) {
			if(!MOBILE_MODE) {
				$out['action'] = 'joined in and ';
			}else{
				$out['action'] = 'joined and ';
			}
		}
	}
	if($reply_author == $previous_poster && $posts_in_row < 17) {
		$posts_in_row++;
		$out['action'] .= $tuple[$posts_in_row] . '-posted';
	} else if($reply_author == $previous_poster && $posts_in_row >= 17) {
		$posts_in_row++;
		if(!MOBILE_MODE) $out['action'] .=  "just kept on posting";
	} else {
		$posts_in_row = 0;
		if(!MOBILE_MODE) $out['action'] .= 'replied with';
	}	
	
	$styleHidden = '';
	if($reply_deleted){
		if(!$reply_stealth) {
			$link->db_exec("SELECT mod_UID, time FROM mod_actions WHERE action = 'delete_reply' AND target = %1 ORDER BY time DESC LIMIT 1", $reply_id);
			list($deleter, $delete_time) = $link->fetch_row();
			$deleter_name = modname($deleter);
		}else{
			$deleter = $reply_author;
			$deleter_name = "Stealthban";
			$delete_time = $reply_time;	
		}
		$styleHidden = 'style="display:none" ';
		echo '<h3 id="reply_'.$reply_id.'_info" class="highlighted"><center>Reply #' . $reply_id . ' from ' . $out['author'] . ' deleted by <b><a href="' . DOMAIN . 'profile/' . $deleter . '">' . $deleter_name . '</a></b>, <strong><span class="help" title="' . format_date($delete_time) . '">' . calculate_age($delete_time) . ' ago</span></strong> <a href="#reply_' . $reply_id . '" onClick="showDeleted('.$reply_id.', this);return false;" id="reply_button_'.$reply_id.'">[show]</a></center></h3>';
	}
	
	// Now, output the reply.
	echo '<h3 class="c" '. $styleHidden .'name="reply_' . $reply_id . '" id="reply_' . $reply_id . '">';
	
	// If this is the newest unread post, let the #new anchor highlight it.
	if ($undeleted_replies == $last_read_post) {
		echo '<span id="new"></span><input type="hidden" id="new_id" class="noscreen" value="' . $reply_id . '" />';
	}
	
	// The content of the header:
	if(!MOBILE_MODE){
		echo $out['author'] . $out['author_desc'] . ' ' . $out['action'] . $t . ' this <strong><span class="help" title="' . format_date($reply_time) . '">' . calculate_age($reply_time) . ' ago</span></strong>, ' . calculate_age($reply_time, $previous_post_time) . ' later';
		if (!empty($posters)) // If not first reply.
			{
			echo ', ' . calculate_age($reply_time, $topic_time) . ' after the original post';
		}
	}else{
		echo $out['author'] . $out['author_desc'] . ', <strong><span class="help">' . calculate_age($reply_time) . ' ago</span></strong>';
	}
	
	// Finish the header and begin outputting the body.
	echo '<span class="reply_id unimportant"><a href="#top">[^]</a> <a href="#bottom">[v]</a> <a href="#reply_' . $reply_id . '" onclick="return highlightReply(\'' . $reply_id . '\');">#' . number_format($reply_id) . '</a>' . ((allowed("delete")) ? '<input style="display:inline" type="checkbox" class="mass_delete" value="'.$reply_id.'" />' : '') . '</span></h3> <div ' . $styleHidden . 'class="body" id="reply_box_' . $reply_id . '">';
	
	if($user_disable_images != 1) {
		if($img_external && $thumb_external) {
			if(MOBILE_MODE){
				$new_thumb_width = min($thumb_width, 100);
				$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
				$thumb_width = $new_thumb_width;
			}
			
			echo '<a href="'.htmlspecialchars($img_external) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'. htmlspecialchars($thumb_external) . '" alt="Externally hosted image" title="Externally hosted image" /></a>';
			
		}elseif ($reply_image_name) {
			if(!$thumb_width&&!$thumb_height&&file_exists("thumbs/".$reply_image_name)){
				$thumb_dimensions = getimagesize("thumbs/".$reply_image_name);
				$thumb_width = $thumb_dimensions[0];
				$thumb_height = $thumb_dimensions[1];
				$link->update("images", array("thumb_width"=>$thumb_width, "thumb_height"=>$thumb_height), "file_name='".$link->escape($reply_image_name)."'");
			}
			
			if(MOBILE_MODE){
				$new_thumb_width = min($thumb_width, 100);
				$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
				$thumb_width = $new_thumb_width;
			}
			
			if(file_exists("img/".$reply_image_name)){
				if(!file_exists("thumbs/".$reply_image_name)){
					thumbnail("img/" . $reply_image_name, $reply_image_name, end(explode(".", $reply_image_name)));
				}
				echo '<a href="'.STATIC_DOMAIN.'img/' . htmlspecialchars($reply_image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($reply_image_name) . '" alt="" /></a>';
			}else{
				if(file_exists("thumbs/".$reply_image_name)){
					echo '<a href="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($reply_image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($reply_image_name) . '" alt="" /></a>';
				}else{
					echo '<a href="http://minichan.org/topic/6346"><img width="147" height="180" src="'.STATIC_DOMAIN.'style/deleted.png" alt="Image went missing" /></a>';
				}
			}
		}
	}
	
	if($reply_html) {
		$reply_body_parsed = $reply_body;
	}else{
		$reply_body_parsed = parse($reply_body);
	}
	
	$reply_body_parsed = preg_replace_callback('/@([0-9,]+)/m', "preg_replace_anchors", $reply_body_parsed);
	
	$reply_body_quickquote = trim(preg_replace('/^@([0-9,]+|OP)/m', '', $reply_body));
	$reply_body_quickquote = preg_replace('/^/m', '> ', $reply_body_quickquote);
	$reply_body_quickquote = urlencode($reply_body_quickquote);
	
	$reply_body_parsed = preg_replace('/@OP/', '<span class="unimportant"><a class="cite_op" href="#">$0</a></span>', $reply_body_parsed);
	echo $reply_body_parsed;
	edited_message($reply_time, $reply_edit_time, $reply_edit_mod);
	
	echo '<ul class="menu">';

	if ($reply_author == $_SESSION['UID'] && TIME_TO_EDIT == 0 || $reply_author == $_SESSION['UID'] && ($_SERVER['REQUEST_TIME'] - $reply_time < (TIME_TO_EDIT * ($gold_account+1))) || allowed("edit_post")) {
		echo '<li><a class="editButton" href="'.DOMAIN.'edit_reply/' . $_GET['id'] . '/' . $reply_id . '">Edit</a></li>';
	}

	if(allowed("open_profile")) echo '<li><a class="topic_profile_link" href="'. DOMAIN.'profile/' . $reply_author . '">Profile</a></li>';
	if(allowed("mod_pm")) echo '<li><a href="'.DOMAIN.'compose_message/' . $reply_author . '">PM</a></li>';
	
	if(!$reply_deleted){
		if(allowed("delete")) echo '<li><a href="'.DOMAIN.'delete_reply/' . $reply_id . '" onclick="return submitDummyForm(\''.DOMAIN.'delete_reply/' . $reply_id . '\', \'id\', ' . $reply_id . ', \'Really delete this reply?\');">Delete</a></li>';
	}else{
		if(allowed("undelete")) echo '<li><a href="'.DOMAIN.'undelete_reply/' . $reply_id . '" onclick="return submitDummyForm(\''.DOMAIN.'undelete_reply/' . $reply_id . '\', \'id\', ' . $reply_id . ', \'Really undelete this reply?\');">Undelete</a></li>';
	}
	
	if((allowed("delete_image")) && ($reply_image_name || $img_external)) echo '<li><a href="'.DOMAIN.'delete_image/topic/' . $_GET['id'] . '/reply/' . $reply_id . '" onclick="return submitDummyForm(\''.DOMAIN.'delete_image/topic/' . $_GET['id'] . '/reply/' . $reply_id . '\', \'id\', ' . $reply_id . ', \'Really delete this image?\');">Delete Image</a></li>';
	if((allowed("set_image")) && !($reply_image_name || $img_external)) echo '<li><a href="'.DOMAIN.'set_image/topic/' . $_GET['id'] . '/reply/' . $reply_id . '" onclick="return chooseImage(this);">Set Image</a></li>';

	if(allowed("delete") || allowed("undelete") || allowed("delete_image") || allowed("open_profile") || allowed("mod_pm")) echo "<li><strong>|</strong></li>";
	
	if($allow_user_report && GOODREP)
		echo '<li><a href="'.DOMAIN.'report_reply/' . $_GET['id'] . '/' . $reply_id . '">Report</a></li>';
	
	if(!$locked || allowed("lock_topic")) echo '<li><a href="'.DOMAIN.'new_reply/' . $_GET['id'] . '/quote_reply/' . $reply_id . '" onclick="quickQuote('. $reply_id . ', \'' . $reply_body_quickquote . '\');return false;">Quote</a></li><li><a href="'.DOMAIN.'new_reply/' . $_GET['id'] . '/cite_reply/' . $reply_id . '" onclick="quickCite('.$reply_id.');return false;">Cite</a></li>';
			
	echo "</ul></div>";
	// Store information for the next round.
	$reply_ids[$reply_id] = array(
		'body' => $reply_body,
		'author' => $reply_author,
		'deleted' => $reply_deleted
	);
	
	if(!$reply_deleted)
		$undeleted_replies++;
	
	$last_was_delete = $reply_deleted;
	
	$posters[]            = $reply_author;
	$previous_poster      = $reply_author;
	$previous_id          = $reply_id;
	$previous_post_time   = $reply_time;
}
$link->free_result($stmt);

// random code...
if(count($_SESSION['post_salts']) > 3) {
	$randVar = array_rand($_SESSION['post_salts']);
	$_SESSION['post_salts'][$randVar] = true;
}else{
	$randVar = md5(mt_rand());
	$_SESSION['post_salts'][$randVar] = true;
}

// If topic is locked, display locked message, except for admins and mods.
if($locked == 0 || allowed("lock_topic")) {
	echo '<ul class="menu"><li><a href="'.DOMAIN.'new_reply/' . $_GET['id'] . '" onclick="$(\'#quick_reply\').toggle();$(\'#qr_text\').get(0).scrollIntoView(true);$(\'#qr_text\').focus(); return false;">Reply</a> ' . (($locked) ? "<small>(locked)</small>" : "") .  '<span class="reply_id unimportant"><a href="#top">[Top]</a></span></li></ul>';
} else {
	echo '<ul class="menu"><li>Topic locked.<span class="reply_id unimportant"><a href="#">[Top]</a></span></li></ul>';
}

// Quick reply stuff starts here.
$link->db_exec("SELECT namefag FROM users WHERE uid = %1", $_SESSION['UID']);
list($setName) = $link->fetch_row();

?>
<div id="quick_reply" class="noscreen">
	<form enctype="multipart/form-data" action="<?php echo DOMAIN; ?>new_reply/<?php echo intval($_GET['id']); ?>/<?php echo $randVar; ?>" method="post">
		<?php csrf_token(); ?>
		<input name="form_sent" type="hidden" value="1" />
		<input name="e-mail" type="hidden" />
		<input name="start_time" type="hidden" value="<?php echo time(); ?>" />
		<input name="image" type="hidden" value="" />
		<?php if(ENABLE_NAMES) { ?>
        <div class="row"><label for="name">Name</label>:
			<input class="inline" id="name" name="name" type="text" size="30" placeholder="name #tripcode" maxlength="30" tabindex="2" value="<?php echo htmlspecialchars($setName); ?>">
			<?php if(allowed("post_html")) { ?>
			<label for="post_html">HTML:</label> <input class="inline" type="checkbox" name="post_html" id="post_html" value="1" /> 
			<?php } ?>
			<?php if(allowed("mod_hyperlink")) { ?>
			<label for="post_hyperlink">Hyperlink:</label> <input class="inline" type="checkbox" name="post_hyperlink" id="post_hyperlink" <?php if($administrator) { echo 'checked="checked" '; }?>value="1" />
			<?php } ?>
		</div>
        <?php
		}
		if($administrator){ /* CHANGE HERE FOR MODS TOO */ ?>
<!--		<div class="row"><label for="name">Anonymous <b>#</b></label>:
			<input id="number" name="number" type="text" size="5" maxlength="3" tabindex="2" value="-">
		</div>
-->
	    <?php } ?>
		<textarea class="inline" name="body" id="qr_text" rows="5" cols="90" tabindex="3"></textarea>
		<?php if (ALLOW_IMAGES) { ?>
		<input type="file" name="image" id="image" tabindex="5" />
		<?php } if (ALLOW_IMGUR) { ?>
       	<label for="imageurl">Imgur URL:</label> <input class="inline" type="text" name="imageurl" id="imageurl" size="21" placeholder="http://i.imgur.com/rcrlO.jpg" /> <a href="javascript:document.getElementById('imgurupload').click()" id="uploader">[upload]</a><br />
		<?php
		}
		if(USER_REPLIES < RECAPTCHA_MIN_POSTS) {
			echo "<br /><b>You are required to fill in a captcha for your first " . RECAPTCHA_MIN_POSTS . " posts. That's only " . (RECAPTCHA_MIN_POSTS - USER_REPLIES) . " more! We apologize, but this helps stop spam.</b>";
			recaptcha_inline();
		}
		?>
		<input type="submit" name="preview" tabindex="6" value="Preview" class="inline" /> 
		<input type="submit" name="post" tabindex="4" value="Post" class="inline">
		<p>Please familiarise yourself with the <a href="<?php echo DOMAIN; ?>rules" target="_blank">rules</a> and <a href="<?php echo DOMAIN; ?>markup_syntax" target="_blank">markup syntax</a> before posting, also keep in mind you can minify URLs using <a href="<?php echo DOMAIN; ?>link" target="_blank">MiniURL</a> and generate image macros using <a href="<?php echo DOMAIN; ?>macro" target="_blank">MiniMacro</a>.</p>
	</form>
    <input style="visibility: hidden; width: 0px; height: 0px;" type="file" id="imgurupload" onchange="uploadImage(this.files[0])"> 
</div>
<div id="bottom"></div>
<?php
if(allowed("delete")) { ?>
<div id="notice" class='do_massDelete' style="position:fixed; bottom:0px; right:1px; display:none">
	<a href="#" onClick="massDelete('<?php echo DOMAIN ?>', '<?php echo $_GET['id'] ?>');">Mass delete (<span id='massDeleteCount'>0</span>)!</a>
</div>
<?php
}
?>
<a id='snapback_link' style='display: none' class='help_cursor' onclick='popSnapbackLink(); return false' title='Click me to snap back!' href='#'>
<strong>↕</strong>
<span>&nbsp;</span>			
</a>

<script>
var defaults = {
	'type':'keydown',
	'propagate':true, // Don't swallow keypresses.
	'target':document
};

/* Do nothing when in insert mode. */
function ignore() {
    return $("input, textarea").is(":focus");
}
                        
shortcut.add("j", function() { ignore() || replyCursor.next(); }, defaults);
shortcut.add("k", function() { ignore() || replyCursor.previous(); }, defaults);
shortcut.add("f", function() { ignore() || replyCursor.nextScreen(); }, defaults);
shortcut.add("b", function() { ignore() || replyCursor.previousScreen(); }, defaults);
</script>

<?php
require('includes/footer.php');
?>
