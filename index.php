<?php
require('includes/header.php');

if($_GET['bumps']) {
	$topics_mode = false;
}else if($_GET['topics']){
	$topics_mode = true;
}else{
	$link->db_exec("SELECT topics_mode FROM user_settings WHERE uid = %1", $_SESSION["UID"]);
	list($topics_mode) = $link->fetch_row();
	$topics_mode = (bool)$topics_mode;
}
if(! empty($_GET['error'])) {
	redirect($_GET['error']);
	add_error('error');
}

if($_GET['p'] > 1 && ctype_digit($_GET['p'])) {
	$current_page = (int)$_GET['p'];
}else{
	$current_page = 1;
}

if($topics_mode) {
	$current_page_activity = 'latest_topics';
	$current_page_title = 'Topics';
} else {
	$current_page_activity = 'latest_bumps';
	$current_page_title = 'Bumps';
}

update_activity($current_page_activity, $current_page == 1 ? null : $current_page);
$last_seen = $_COOKIE['last_bump'];
if($current_page == 1) {
	$page_title = 'Latest ' . strtolower($current_page_title);
}else{
	$page_title = $current_page_title . ', page #' . number_format($current_page);
}

// Update the last_bump and last_topic cookies. These control both the last seen marker and the exclamation mark in main menu.
if($_COOKIE['last_bump'] <= $last_actions['last_bump']) {
	setcookie('last_bump', $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'] + 315569260, '/');
}
if($_COOKIE['last_topic'] <= $last_actions['last_topic']) {
	setcookie('last_topic', $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'] + 315569260, '/');
}

// If ostrich mode is enabled, fetch a list of blacklisted phrases.
$ignored_phrases = fetch_ignore_list();

// Fetch the topics appropriate to this page.
$items_per_page = ITEMS_PER_PAGE;
$start_listing_at = $items_per_page * ($current_page - 1);
// Print the topics we just fetched in a table.
$table = new table();
$order_name = ($topics_mode) ? 'Age' : ((MOBILE_MODE) ? 'Bump' : 'Last bump');
$columns = array
(
	'Headline',
	'Snippet',
	'Replies',
	'Visits',
	$order_name . ' ▼'
);
			
if(!$user_settings['spoiler_mode'] || MOBILE_MODE) {
	// If spoiler mode is disabled, remove the snippet column.	
	array_splice($columns, 1, 1);
}

if(MOBILE_MODE){
	array_splice($columns, 2, 1);
}

$table->define_columns($columns, 'Headline');
$table->add_td_class('Headline', 'topic_headline');
$table->add_td_class('Snippet', 'snippet');

function renderTopics($table, $topics) {
	global $topics_mode, $link, $visited_topics, $last_seen, $user_settings, $ignored_phrases;
	
	while($topic = $link->fetch_assoc($topics)) {
		if($topic['secret_id'] && !allowed('minecraft')) {
			$table->num_rows_fetched++;
			continue;
		}
		
		if(!$topic['sticky']) {
			if($topic['stealth_ban'] && !allowed('undelete') && !canSeeStealthBannedPost($author, $author_ip)) {
				$table->num_rows_fetched++;
				continue;
			}
			
			// Should we even bother?
			if($user_settings['ostrich_mode']) {
				if($ignored_phrases) {
					foreach($ignored_phrases as $ignored_phrase) {
						if(stripos($topic['headline'], $ignored_phrase) !== false || stripos($topic['body'], $ignored_phrase) !== false) {
							// We've encountered an ignored phrase, so skip the rest of this while() iteration.
							$table->num_rows_fetched++;
							continue 2;
						}
					}
				}
			}
		}
		
		$order_time = $topics_mode ? $topic['time'] : $topic['last_post'];
		
		if((time()-$topic['last_post'])>NECRO_BUMP_TIME) $topic['locked'] = true;

		$lockTxt = array();
		
		if($topic['locked']) {
			$lockTxt[] = "[LOCKED]";
		}
		
		if($topic['sticky']) {
			$lockTxt[] = "[STICKY]";
		}
		
		if($topic['stealth_ban'] && allowed('undelete')) $lockTxt[] = "[STEALTH]";
			
		if($topic['poll']) {
			$pollTxt = " (Poll)";
		}else{
			$pollTxt = "";
		}

		$visited = ((!$visited_topics[$topic['id']] && isset($visited_topics[$topic['id']])) || (($topic['replies'] - $visited_topics[$topic['id']] != $topic['replies'])) ? ' class="visited"' : '');

		$lockTxt = "<small class='topic_info'>". implode(" ", $lockTxt) . "</a>";

		$values = array (
							'<a'.$visited.' href="'.DOMAIN.'topic/' . $topic['id'] . '">' . htmlspecialchars($topic['headline'], ENT_COMPAT | ENT_HTML401, "") . '</a>' . $pollTxt . $lockTxt,
							snippet($topic['body']),
							replies($topic['id'], $topic['replies']),
							format_number($topic['visits']),
							'<span class="help" title="' . format_date($order_time) . '">' . calculate_age($order_time) . '</span>'
						);
		if(!$user_settings['spoiler_mode'] || MOBILE_MODE) {	
			array_splice($values, 1, 1);
		}
		if(MOBILE_MODE){
			array_splice($values, 2, 1);
		}
		if($topic['sticky']) {
			$table->last_seen_marker(false, false);
		}else{
			$table->last_seen_marker($last_seen, $order_time);
		}
		$table->row($values);
	}
}


$stickies = $link->db_exec('SELECT *
							FROM topics
							WHERE sticky = 1
							AND deleted = 0
							ORDER BY ' . ($topics_mode ? 'id' : 'last_post') . ' DESC');
$stickyRows = $link->num_rows($stickies);
renderTopics($table, $stickies);
$link->free_result($stickies);

$topics = $link->db_exec('SELECT *
						FROM topics
						WHERE sticky = 0
						AND deleted = 0
						ORDER BY ' . ($topics_mode ? 'id' : 'last_post') . ' DESC LIMIT %1, %2', $start_listing_at, $items_per_page - $stickyRows);
renderTopics($table, $topics);
$link->free_result($topics);
$num_rows_fetched = $table->num_rows_fetched;
echo $table->output('topics');

// Navigate backward or forward.
$navigation_path = 'bumps';
if($_GET['topics']) {
	$navigation_path = 'topics';
}

page_navigation($navigation_path, $current_page, $num_rows_fetched);

require('includes/footer.php');
?>
