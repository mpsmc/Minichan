<?php
require('includes/header.php');
update_activity('notifications');
force_id();

$cite_topics = array();

if($new_citations) {
	echo '<h2>Citations</h2>';
		// Delete notifications of replies-to-replies that no longer exist.
	$link->db_exec('DELETE FROM citations WHERE uid = %1 AND (NOT EXISTS (SELECT 1 FROM replies WHERE citations.reply = replies.id AND replies.deleted = 0) OR NOT EXISTS (SELECT 1 FROM topics WHERE citations.topic = topics.id AND topics.deleted = 0))', $_SESSION['UID']);

	// List replies to user's replies.
	$stmt = $link->db_exec('SELECT DISTINCT citations.reply, replies.parent_id, replies.time, replies.body, topics.headline, topics.time FROM citations INNER JOIN replies ON citations.reply = replies.id INNER JOIN topics ON replies.parent_id = topics.id WHERE citations.uid = %1 ORDER BY citations.reply DESC', $_SESSION['UID']);

	$citations = new table();
	$columns = array(
		'Reply to your reply',
		'Topic',
		'Age ▼'
	);
	$citations->define_columns($columns, 'Topic');
	$citations->add_td_class('Topic', 'topic_headline');
	$citations->add_td_class('Reply to your reply', 'reply_body_snippet');

	while (list($reply_id, $parent_id, $reply_time, $reply_body, $topic_headline, $topic_time) = $link->fetch_row($stmt)) {
		$cite_topics[] = $parent_id;
		$values = array(
			'<a href="'.DOMAIN.'topic/' . $parent_id . '#reply_' . $reply_id . '">' . snippet($reply_body) . '</a>',
			'<a href="'.DOMAIN.'topic/' . $parent_id . '">' . htmlspecialchars($topic_headline) . '</a> <span class="help unimportant" title="' . format_date($topic_time) . '">(' . calculate_age($topic_time) . ' old)</span>',
			'<span class="help" title="' . format_date($reply_time) . '">' . calculate_age($reply_time) . '</span>'
		);
		
		$citations->row($values);
	}
	if($citations->num_rows_fetched > 0) {
		echo $citations->output();
	} else {
		echo '<p>Nothing to see here...</p>';
	}
}

if($new_watchlists) {
	echo '<h2>Watchlists</h2>';
		
	$stmt = $link->db_exec('
SELECT topics.id, topics.headline, topics.replies, topics.visits, topics.time FROM watchlists, read_topics, topics WHERE
watchlists.uid = %1 AND
read_topics.uid = %1 AND
watchlists.topic_id = read_topics.topic AND
watchlists.topic_id = topics.id AND
topics.replies > read_topics.replies
	', $_SESSION['UID']);

	$topics       = new table();
	$topic_column = 'Topic';
	$columns      = array(
		$topic_column,
		'Replies',
		'Visits',
		'Age ▼'
	);
	$topics->define_columns($columns, $topic_column);
	$topics->add_td_class($topic_column, 'topic_headline');
	
	while (list($topic_id, $topic_headline, $topic_replies, $topic_visits, $topic_time) = $link->fetch_row($stmt)) {
		//if(in_array($topic_id, $cite_topics)) continue;
		
		$values = array(
			'<input type="checkbox" name="rejects[]" value="' . $topic_id . '" class="inline" /> <a href="'.DOMAIN.'topic/' . $topic_id . '">' . htmlspecialchars($topic_headline) . '</a>',
			replies($topic_id, $topic_replies),
			format_number($topic_visits),
			'<span class="help" title="' . format_date($topic_time) . '">' . calculate_age($topic_time) . '</span>'
		);
		
		$topics->row($values);
	}
	$num_topics_fetched = $topics->num_rows_fetched;
	
	echo '<form name="watch_list" action="'.DOMAIN.'watchlist" method="post">';
	
	echo $topics->output();
	
	if ($num_topics_fetched !== 0) {
		echo '<div class="row"><input type="submit" value="Unwatch selected" onclick="return confirm(\'Really remove selected topic(s) from your watchlist?\');" class="inline" /></div>';
	}
	echo '</form>';
}

if(!($new_watchlists && $new_citations)) {
	echo "<p>Nothing to see here...</p>";
}

require("includes/footer.php");