<?php
require 'includes/header.php';
die('disabled');
update_activity('search');

$page_title = 'Search';

//if($_GET['q']) {
//	foreach(array("to4str", "toaster", "toastr", "t0sser") as $toaster) {
//		if(stripos($_GET['q'], $toaster) !== false) {
//			header("Location: http://bunker.minichan.org");die();
//		}
//	}
//}

if (!empty($_GET['q']) && ENABLE_RECAPTCHA_ON_BOT) {
    $link->db_exec('SELECT 1 FROM search_log WHERE ip_address = %1 AND time > (UNIX_TIMESTAMP()-60)', $_SERVER['REMOTE_ADDR']);
    if ($link->num_rows() > RECAPTCHA_MAX_SEARCHES_PER_MIN) {
        $link->db_exec('INSERT INTO search_log (ip_address, time, phrace) VALUES (%1, UNIX_TIMESTAMP(), %2)', $_SERVER['REMOTE_ADDR'], $_GET['q']);
        if (recaptcha()) {
            $link->db_exec('DELETE FROM search_log WHERE ip_address = %1', $_SERVER['REMOTE_ADDR']);
        }
    }
}

$onload_javascript = 'focusId(\'phrase\'); init();';

$slmsql = "SELECT value FROM flood_control WHERE setting = 'search_disabled'";
$slmsend = send($slmsql);
$getcs = mysql_fetch_array($slmsend);
$sldm = $getcs['value'];

$uid = $_SESSION['UID'];
if ($sldm) {
    if (USER_REPLIES >= POSTS_TO_DEFY_SEARCH_DISABLED) {
        $goodrep = true;
    } else {
        $page_title = 'Search disabled';
//		echo 'Searching has been disabled for new users, please try again when you\'re a regular user. You currently have ' . USER_REPLIES . "  replies and you need " . POSTS_TO_DEFY_SEARCH_DISABLED;
        echo 'Searching has been disabled for new users, please try again when you\'re a regular user.';
        exit(require('includes/footer.php'));
    }
}

if (!empty($_POST['phrase'])) {
    // CSRF checking.
    check_token();
    if ($_POST['history'] == 1) {
        $history = 'history/';
    } else {
        $history = '';
    }
    if ($_POST['deep_search']) {
        $redirect_to = DOMAIN.'deep_search/'.$history.urlencode($_POST['phrase']);
    } else {
        $redirect_to = DOMAIN.'quick_search/'.$history.urlencode($_POST['phrase']);
    }
    header('Location: '.$redirect_to);
    exit;
}
?>
<p>The "quick" option searches only topic headlines, while the "deep" option searches both headlines and bodies.</p>
<form action="" method="post">
	<?php csrf_token() ?>
	<div class="row">
		<input id="phrase" name="phrase" type="text" size="80" maxlength="255" value="<?php echo htmlspecialchars($_GET['q']) ?>" class="inline" />
		<input type="submit" value="Quick" class="inline" />
		<input type="submit" value="Deep" name="deep_search" class="inline" /><br />
		<input type="checkbox" value="1" name="history" id="search_history" class="inline" <?php if ($_GET['history'] == 1) {
    ?>checked="checked" <?php 
} ?> /> <label class="inline" for="search_history">Your history only</label><br />
	</div>
</form>
<?php
if (!empty($_GET['q'])) {
    $search_query = trim($_GET['q']);

    if (strlen($search_query) < 3) {
        add_error('Your query must be at least 3 characters.');
    } elseif (strlen($search_query) > 255) {
        add_error('Your query must be shorter than 256 characters.');
    }
    if ($_SERVER['REQUEST_TIME'] - $_SESSION['last_search'] < 1) {
        add_error('Wait at least 1 seconds between searches.');
    }

    if (!$erred) {
        $_SESSION['last_search'] = $_SERVER['REQUEST_TIME'];
        //$search_query = '%' . $search_query . '%';
        $link->db_exec('INSERT INTO search_log (ip_address, time, phrace) VALUES (%1, UNIX_TIMESTAMP(), %2)', $_SERVER['REMOTE_ADDR'], $search_query);

        if ($_GET['history'] == 1) {
            $filter_author = $_SESSION['UID'];
        } else {
            $filter_author = null;
        }

        if ($_GET['deep_search']) {
            //$search_topics = $link->db_exec('SELECT id, time, replies, visits, headline FROM topics WHERE deleted = 0 AND (headline LIKE %1 OR body LIKE %1) ORDER BY id DESC LIMIT 50', $search_query);
            $search_topics = $link->db_exec('SELECT id, time, replies, visits, headline, MATCH(headline) AGAINST (%1) as relevance FROM topics WHERE deleted = 0 AND MATCH(headline, body) AGAINST (%1)'.($filter_author ? ' AND author = %2' : '').' ORDER BY id DESC LIMIT 50', $search_query, $filter_author);
        } else {
            //$search_topics = $link->db_exec('SELECT id, time, replies, visits, headline FROM topics WHERE deleted = 0 AND headline LIKE %1 ORDER BY id DESC LIMIT 50', $search_query);
            $search_topics = $link->db_exec('SELECT id, time, replies, visits, headline, MATCH(headline) AGAINST (%1) as relevance FROM topics WHERE deleted = 0 AND MATCH(headline) AGAINST (%1)'.($filter_author ? ' AND author = %2' : '').' ORDER BY id DESC LIMIT 50', $search_query, $filter_author);
        }

        if ($link->num_rows($search_topics) > 0) {
            echo '<h4 class="section">Topics</h3>';
            $topics = new table();
            $columns = array(
                'Headline',
                'Replies',
                'Visits',
                'Age ▼',
            );
            $topics->define_columns($columns, 'Headline');
            $topics->add_td_class('Headline', 'topic_headline');

            while (list($topic_id, $topic_time, $topic_replies, $topic_visits, $topic_headline) = $link->fetch_row($search_topics)) {
                $values = array(
                    '<a href="'.DOMAIN.'topic/'.$topic_id.'">'.str_ireplace($_GET['q'], '<em class="marked">'.htmlspecialchars($_GET['q']).'</em>', htmlspecialchars($topic_headline)).'</a>',
                    replies($topic_id, $topic_replies),
                    format_number($topic_visits),
                    '<span class="help" title="'.format_date($topic_time).'">'.calculate_age($topic_time).'</span>',
                );

                $topics->row($values);
            }
            $num_topics_fetched = $topics->num_rows_fetched;
            echo $topics->output('', true);

            if ($num_topics_fetched == 50) {
                echo '<p class="unimportant">(Too many results, truncating.)</p>';
            }
        } else {
            echo '<p>(No matching topic headlines';
            if ($_GET['deep_search']) {
                echo ' or bodies';
            }
            echo '.)</p>';
        }

        if ($_GET['deep_search']) {
            //$search_replies = $link->db_exec('SELECT replies.id, replies.parent_id, replies.time, replies.body, topics.headline, topics.time FROM replies INNER JOIN topics ON replies.parent_id = topics.id WHERE replies.deleted = 0 AND topics.deleted = 0 AND replies.body LIKE %1 ORDER BY id DESC LIMIT 50', $search_query);
            $search_replies = $link->db_exec('SELECT replies.id, replies.parent_id, replies.time, replies.body, topics.headline, topics.time, MATCH(replies.body) AGAINST (%1) as relevance FROM replies INNER JOIN topics ON replies.parent_id = topics.id WHERE replies.deleted = 0 AND topics.deleted = 0 AND MATCH(replies.body) AGAINST (%1)'.($filter_author ? ' AND replies.author = %2' : '').' ORDER BY relevance DESC LIMIT 50', $search_query, $filter_author);
//var_dump($search_replies);die();
            if ($link->num_rows($search_replies) > 0) {
                $replies = new table();
                $columns = array(
                    'Reply snippet',
                    'Topic',
                    'Age ▼',
                );
                $replies->define_columns($columns, 'Topic');
                $replies->add_td_class('Topic', 'topic_headline');
                $replies->add_td_class('Reply snippet', 'reply_body_snippet');

                while (list($reply_id, $parent_id, $reply_time, $reply_body, $topic_headline, $topic_time) = $link->fetch_row($search_replies)) {
                    $values = array(
                        '<a href="'.DOMAIN.'topic/'.$parent_id.'#reply_'.$reply_id.'">'.str_ireplace($_GET['q'], '<em class="marked">'.htmlspecialchars($_GET['q']).'</em>', snippet($reply_body)).'</a>',
                        '<a href="'.DOMAIN.'topic/'.$parent_id.'">'.htmlspecialchars($topic_headline).'</a> <span class="help unimportant" title="'.format_date($topic_time).'">('.calculate_age($topic_time).' old)</span>',
                        '<span class="help" title="'.format_date($reply_time).'">'.calculate_age($reply_time).'</span>',
                    );

                    $replies->row($values);
                }
                $num_replies_fetched = $replies->num_rows_fetched;
                echo $replies->output('', true);

                if ($num_replies_fetched == 50) {
                    echo '<p class="unimportant">(Too many results, truncating.)</p>';
                }
            } else {
                echo '<p>(No matching replies.)</p>';
            }
        }
    }

    if (!$erred) {
        $analytics_track_url = '/search?q='.$_GET['q'].'&category='.(($_GET['deep_search']) ? 'deep' : 'quick');
    }
}
print_errors();
require 'includes/footer.php';
?>