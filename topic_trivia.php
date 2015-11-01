<?php
require 'includes/header.php';

if (!ctype_digit($_GET['id'])) {
    add_error('Invalid ID.', true);
}

$stmt = $link->db_exec('SELECT headline, visits, replies, author FROM topics WHERE id = %1', $_GET['id']);

if ($link->num_rows($stmt) < 1) {
    $page_title = 'Non-existent topic';
    add_error('There is no such topic. It may have been deleted.', true);
}

list($topic_headline, $topic_visits, $topic_replies, $topic_author) = $link->fetch_row($stmt);

update_activity('topic_trivia', $_GET['id']);

$page_title = 'Trivia for topic: <a href="'.DOMAIN.'topic/'.$_GET['id'].'">'.htmlspecialchars($topic_headline).'</a>';

$statistics = array();

unset($query);
$query[] = "SELECT count(*) FROM watchlists WHERE topic_id = '".$link->escape($_GET['id'])."';";
$query[] = "SELECT count(*) FROM activity WHERE action_name = 'topic' AND action_id = '".$link->escape($_GET['id'])."';";
$query[] = "SELECT count(*) FROM activity WHERE action_name = 'replying' AND action_id = '".$link->escape($_GET['id'])."';";
$query[] = "SELECT count(DISTINCT author) FROM replies WHERE parent_id = '".$link->escape($_GET['id'])."' AND author != '".$link->escape($topic_author)."';"; // Alternatively, we could select the most recent poster_number. I'm not sure which method would be fastest.

foreach ($query as $q) {
    $result = $link->db_exec($q);
    while ($row = $link->fetch_row()) {
        $statistics[] = $row[0];
    }
}

$topic_watchers = $statistics[0];
$topic_readers = $statistics[1];
$topic_writers = $statistics[2];
$topic_participants = $statistics[3] + 1; // Include topic author.
?>
<table>
	<tr>
		<th class="minimal">Total visits</th>
		<td><?php echo format_number($topic_visits) ?></td>
	</tr>
	<tr class="odd">
		<th class="minimal">Watchers</th>
		<td><?php echo format_number($topic_watchers) ?></td>
	</tr>
	<tr>
		<th class="minimal">Participants</th>
		<td><?php echo ($topic_participants === 1) ? '(Just the creator.)' : format_number($topic_participants) ?></td>
	</tr>
	<tr class="odd">
		<th class="minimal">Replies</th>
		<td><?php echo format_number($topic_replies) ?></td>
	</tr>
	<tr>
		<th class="minimal">Current readers</th>
		<td><?php echo format_number($topic_readers) ?></td>
	</tr>
	<tr class="odd">
		<th class="minimal">Current reply writers</th>
		<td><?php echo format_number($topic_writers) ?></td>
	</tr>
</table>
<?php
require 'includes/footer.php';
?>