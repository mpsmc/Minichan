<?php
require('includes/header.php');
if( !allowed("undelete")) {
	add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}
// Should we sort by topic creation date or last bump?
// if($_COOKIE['topics_mode'] == 1 && ! $_GET['bumps']) {
//	$topics_mode = true;
// }

if(! empty($_GET['error'])) {
	redirect($_GET['error']);
	add_error('error');
}

// Are we on the first page?
if ($_GET['p'] < 2 || ! ctype_digit($_GET['p'])) {
	$current_page = 1;
	$page_title = 'Latest deletes';
// The page number is greater than one.
} else {
	$current_page = $_GET['p'];
	$page_title = 'Deletes, page #' . number_format($current_page);
}

// Fetch the topics appropriate to this page.
$items_per_page = ITEMS_PER_PAGE;
$start_listing_at = $items_per_page * ($current_page - 1);
// Print the topics we just fetched in a table.
$table = new table();
$columns = array
(
	'Headline',
	'Snippet',
	'Replies',
	'Visits',
	'Time since deletion ▼',
	'Deleted by',
	'Undelete'
);

$table->define_columns($columns, 'Headline');
$table->add_td_class('Headline', 'topic_headline');
$table->add_td_class('Snippet', 'snippet');

$newPerPage = $items_per_page;

$stmt = $link->db_exec('SELECT topics.id, topics.time, topics.replies, topics.visits, topics.headline, topics.body, topics.last_post, topics.locked, mod_actions.mod_UID, mod_actions.time FROM topics, mod_actions WHERE topics.deleted = 1 AND topics.id = mod_actions.target AND mod_actions.action = \'delete_topic\' GROUP BY topics.id ORDER BY mod_actions.time DESC LIMIT %1, %2', $start_listing_at, $newPerPage);

while(list($topic_id, $topic_time, $topic_replies, $topic_visits, $topic_headline, $topic_body, $topic_last_post, $topic_locked, $mod_uid, $trash_time) = $link->fetch_row($stmt)) {
	
	// Decide what to use for the last seen marker and the age/last bump column.
	if($topics_mode) {
		$order_time = $topic_time;
	} else {
		$order_time = $topic_last_post;
	}
	
	if($topic_locked){
		$lockTxt = ' <small class="topic_info">[LOCKED]</small>';
	}else{
		$lockTxt = '';
	}
	
        //$link->db_exec("SELECT mod_UID, time FROM mod_actions WHERE action = %1 AND target = %2 ORDER BY time DESC LIMIT 1", 'delete_topic', $topic_id);
        //list($mod_uid, $trash_time) = $link->fetch_row();

	// Process the values for this row of our table. 
	$values = array (
						'<a href="'.DOMAIN.'topic/' . $topic_id . '">' . htmlspecialchars($topic_headline) . '</a>'.$lockTxt,
						snippet($topic_body),
						replies($topic_id, $topic_replies),
						format_number($topic_visits),
						'<span class="help" title="' . format_date($trash_time) . '">' . calculate_age($trash_time) . '</span>',
						'<a href="' . DOMAIN . 'profile/' . $mod_uid . '">' . modname($mod_uid) . '</a>',
						'<a href="'.DOMAIN.'undelete_topic/' . $topic_id . '">Undelete</a>'
					);
	$table->row($values);
}
$link->free_result($stmt);
$num_rows_fetched = $table->num_rows_fetched;
echo $table->output('topics');

// Navigate backward or forward.
$navigation_path = 'deleted_topics';
page_navigation($navigation_path, $current_page, $num_rows_fetched);
require('includes/footer.php');
?>
