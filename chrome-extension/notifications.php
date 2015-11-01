<?php

chdir('..');
//require("includes/header.php");
require 'includes/config.php';
require 'includes/database.class.php';
require 'includes/functions.php';

session_cache_limiter('nocache');
session_name('SID');
session_start();

$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);
date_default_timezone_set('UTC');
header('Content-Type: application/json; charset=UTF-8');

if (!empty($_COOKIE['password'])) {
    if (!isset($_SESSION['ID_activated'])) {
        activate_id();
    }
}

if (!$_SESSION['UID']) {
    $data['notifications'] = 0;
    $data['citations'] = 0;
    $data['num_pms'] = 0;
    $data['watchlists'] = 0;
    $data['error'] = 'No UID';
    die(json_encode($data));
}

$moderator = false;
$administrator = false;
$janitor = false;

if (in_array($_SESSION['UID'], $moderators)) {
    $moderator = true;
} elseif (in_array($_SESSION['UID'], $administrators)) {
    $administrator = true;
} elseif (in_array($_SESSION['UID'], $janitors)) {
    $janitor = true;
}

// Delete citations that no longer exist
$link->db_exec('DELETE FROM citations WHERE uid = %1 AND (NOT EXISTS (SELECT 1 FROM replies WHERE citations.reply = replies.id AND replies.deleted = 0) OR NOT EXISTS (SELECT 1 FROM topics WHERE citations.topic = topics.id AND topics.deleted = 0))', $_SESSION['UID']);

if ($_GET['mode'] != 'popup') {
    $pm_query = 'SELECT id FROM private_messages WHERE `read` = 0 AND (destination = %1';
    if ($moderator) {
        $pm_query .= ' OR destination = \'mods\')';
    } elseif ($administrator) {
        $pm_query .= ' OR destination = \'mods\' OR destination = \'admins\')';
    } else {
        $pm_query .= ')';
    }

    $pm_link = $link->db_exec($pm_query, $_SESSION['UID']);
    $num_pms = $link->num_rows($pm_link);

    $citation_check = $link->db_exec('SELECT COUNT(*) FROM citations WHERE uid = %1', $_SESSION['UID']);
    list($new_citations) = $link->fetch_row($citation_check);

    $link->db_exec('
	SELECT count(*) FROM watchlists, read_topics, topics WHERE
	watchlists.uid = %1 AND
	read_topics.uid = %1 AND
	watchlists.topic_id = read_topics.topic AND
	watchlists.topic_id = topics.id AND
	topics.replies > read_topics.replies
	', $_SESSION['UID']);
    list($new_watchlists) = $link->fetch_row();

    if (!$new_citations) {
        $new_citations = 0;
    }
    if (!$num_pms) {
        $num_pms = 0;
    }
    if (!$new_watchlists) {
        $new_watchlists = 0;
    }

    $data = array();

    $data['notifications'] = $new_citations + $num_pms + $new_watchlists;
    $data['citations'] = $new_citations;
    $data['num_pms'] = $num_pms;
    $data['watchlists'] = $new_watchlists;
} elseif ($_GET['mode'] == 'popup') {
    $link->db_exec('SELECT style, custom_style FROM user_settings WHERE uid = %1', $_SESSION['UID']);
    list($stylesheet, $custom_stylesheet) = $link->fetch_row();
    if ($stylesheet != 'Custom') {
        unset($custom_stylesheet);
        if (!$stylesheet || !file_exists(SITE_ROOT.'/style/'.$stylesheet.'.css')) {
            $stylesheet = DEFAULT_STYLESHEET;
        }

        $stylesheet = DOMAIN.'style/'.$stylesheet.'.css';
    } else {
        $stylesheet = $custom_stylesheet;
        unset($custom_stylesheet);
    }
    $data['user_style'] = $stylesheet;

    $link->db_exec('SELECT DISTINCT citations.reply, replies.parent_id, replies.body, topics.headline FROM citations INNER JOIN replies ON citations.reply = replies.id INNER JOIN topics ON replies.parent_id = topics.id WHERE citations.uid = %1 ORDER BY citations.reply DESC', $_SESSION['UID']);

    $data['citations'] = array();

    while (list($reply_id, $topic_id, $reply_body, $topic_headline) = $link->fetch_row()) {
        $data['citations'][] = array(
            'reply_id' => $reply_id,
            'topic_id' => $topic_id,
            'snippet' => snippet($reply_body),
            'headline' => htmlspecialchars($topic_headline),
        );
    }

    $data['watchlists'] = array();

    $link->db_exec('
SELECT topics.id, topics.headline, topics.replies, (topics.replies-read_topics.replies) FROM watchlists, read_topics, topics WHERE
watchlists.uid = %1 AND
read_topics.uid = %1 AND
watchlists.topic_id = read_topics.topic AND
watchlists.topic_id = topics.id AND
topics.replies > read_topics.replies
	', $_SESSION['UID']);

    while (list($topic_id, $topic_headline, $topic_replies, $unread_replies) = $link->fetch_row()) {
        $data['watchlists'][] = array(
            'topic_id' => $topic_id,
            'headline' => htmlspecialchars($topic_headline),
            'replies' => $topic_replies,
            'unread' => $unread_replies,
        );
    }

    $data['pms'] = array();
    $pm_query = 'SELECT id, source, contents FROM private_messages WHERE `read` = 0 AND (destination = %1';
    if ($moderator) {
        $pm_query .= ' OR destination = \'mods\')';
    } elseif ($administrator) {
        $pm_query .= ' OR destination = \'mods\' OR destination = \'admins\')';
    } else {
        $pm_query .= ')';
    }

    $link->db_exec($pm_query, $_SESSION['UID']);

    while (list($id, $source, $contents) = $link->fetch_row()) {
        $data['pms'][] = array(
            'id' => $id,
            'source' => modname($source),
            'snippet' => snippet($contents),
        );
    }
} else {
    $data['error'] = 'Unknown mode';
}

echo json_encode($data);
