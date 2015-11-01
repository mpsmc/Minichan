<?php
require 'includes/header.php';

if (!allowed('exterminate')) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

$page_title = 'Exterminate trolls by phrase';

if ($_POST['exterminate']) {
    // CSRF checking.
    check_token();
    $_POST['phrase'] = str_replace("\r", '', $_POST['phrase']);

    // Prevent CSRF.
    if (empty($_POST['start_time']) || $_POST['start_time'] != $_SESSION['exterminate_start_time']) {
        add_error('Session error.', true);
    }

    if (strlen($_POST['phrase']) < 4) {
        add_error('That phrase is too short.', true);
    }

    $phrase = '%'.$_POST['phrase'].'%';

    if (ctype_digit($_POST['range'])) {
        $affect_posts_after = $_SERVER['REQUEST_TIME'] - $_POST['range'];

        // Delete replies.
        $fetch_parents = $link->db_exec('SELECT author, id, parent_id FROM replies WHERE body LIKE %1 AND time > %2', $phrase, $affect_posts_after);

        $victim_parents = array();
        while (list($author_id, $reply_id, $parent_id) = $link->fetch_row($fetch_parents)) {
            $link->db_exec('UPDATE topics SET replies = replies - 1 WHERE id = %1', $parent_id);
            deleteImage('reply', $reply_id);
        }
        $link->free($fetch_parents);

        $link->db_exec('DELETE FROM replies WHERE body LIKE %1 AND time > %2', $phrase, $affect_posts_after);

        $fetch_topics = $link->db_exec('SELECT author, id FROM topics WHERE body LIKE %1 OR headline LIKE %1 AND time > %2', $phrase, $affect_posts_after);
        while (list($author_id, $topic_id) = $link->fetch_row($fetch_topics)) {
            deleteImage('topic', $topic_id);
            $fetch_replies = $link->db_exec('SELECT author, id FROM replies WHERE parent_id = %1', $topic_id);
            while (list($author_id, $reply_id) = $link->fetch_row($fetch_replies)) {
                deleteImage('reply', $reply_id);
            }
            $link->free($fetch_replies);
            $link->db_exec('DELETE FROM replies WHERE parent_id = %1', $topic_id);
        }

        // Delete topics.
        $link->db_exec('DELETE FROM topics WHERE body LIKE %1 OR headline LIKE %1 AND time > %2', $phrase, $affect_posts_after);
        $_SESSION['notice'] = 'Finished';
    }
}

$start_time = $_SERVER['REQUEST_TIME'];
$_SESSION['exterminate_start_time'] = $start_time;
?>
<p>This features removes all posts that contain anywhere in the body or headline the exact phrase that you specify.</p>
<form action="" method="post" onsubmit="if(!confirm('Are you sure you want to do this?')){return false;}">
	<?php csrf_token() ?>
	<div class="noscreen">
		<input type="hidden" name="start_time" value="<?php echo $start_time ?>" />
	</div>
	<div class="row">
		<label for="phrase">Phrase</label>
		<textarea id="phrase" name="phrase"></textarea>
	</div>
	<div class="row">
		<label for="range" class="inline">Affect posts made within:</label>
		<select id="range" name="range" class="inline">
			<option value="28800">Last 8 hours</option>
			<option value="86400">Last 24 hours</option>
			<option value="259200">Last 72 hours</option>
			<option value="604800">Last week</option>
			<option value="2629743">Last month</option>
		</select>
	</div>
	<div class="row">
			<input type="submit" name="exterminate" value="Do it" />
		</div>
</form>
<?php
require 'includes/footer.php';
?>