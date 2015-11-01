<?php

require 'includes/header.php';
force_id();
update_activity('events_new', 1);
$page_title = 'New event';
$additional_head = '';

$required_posts = REQ_EVENTS_POSTS; // Check required post amount.
$time_between_bulletins = TIME_EVENTS; // Check time between event posts.
$pre_moderate = PRE_MODERATE_EVENTS; // Check if we need to pre-moderate events.

// Check if user events are disabled.
if (!allowed('manage_events')) {
    if (!ALLOW_USER_EVENTS) {
        $_SESSION['notice'] = 'The posting of events by users has currently been disabled.';
        header('Location: '.DOMAIN.'');
        exit('');
    }
}

// First check to see if you can post events
if (!allowed('manage_events')) {
    $_SESSION['UID'] = mysql_real_escape_string($_SESSION['UID']);
    $sql = "SELECT * FROM topics WHERE author='$_SESSION[UID]'";
    if (!$send = mysql_query($sql, $link->getLink())) {
        exit(mysql_error());
    }
    $num_t = mysql_num_rows($send);
    $sql = "SELECT * FROM replies WHERE author='$_SESSION[UID]'";
    if (!$send = mysql_query($sql, $link->getLink())) {
        exit(mysql_error($link->getLink()));
    }
    $num_r = mysql_num_rows($send);
    $total = $num_t + $num_r;
    if ($total < REQ_EVENT_POSTS) {
        echo '<p>You can not post events at this time.</p>';
        require 'includes/footer.php';
        exit('');
    }
}

function new_event($id = -1)
{
    global $link;
    $desc = '';
    $web = '';
    $date = '';

    if ($id != -1 && $id != null) {
        $sql = 'SELECT * FROM events WHERE no = '.$id.' LIMIT 1';
        $bulletin = mysql_query($sql, $link->getLink());
        $line = mysql_fetch_assoc($bulletin); // Taking out <br />'s like this is safe, because stuff inputted by the user is escaped.
        $desc = str_replace('<br />', '', $line['description']);
        $desc = str_replace('<br>', '', $desc);
        $desc = str_replace('<br/>', '', $desc);
        if (!$desc) {
            $desc = '';
        } else {
            $web = htmlentities($line['address']);
            $date = htmlentities($line['date']);
            $desc = htmlentities($desc);
        }
    }
    echo '<p>You may add any upcoming event here, preferably (but not necessarily) of general interest.';
    echo '<form action="" method="POST">';
    csrf_token();
    echo '<div class="row"><label for="description"><strong>Description</strong>:</label><input value="'.$desc.'" type="text" name="description" maxlength="256" size="128" /></div>';
    echo '<div class="row"><label for="web_address"><strong>Related web address</strong> (optional):</label><input value="'.$web.'" type="text" name="web_address" maxlength="256" size="128" /></div>';
    echo '<div class="row"><label for="date"><strong>Date</strong> (YYYY-MM-DD):</label><input value="'.$date.'" type="text" name="date" maxlength="10" size="10" value="'.date('Y-').'"/></div>';
    echo '<input type="submit" name="event_sub" value="Submit event" /> </form>';
}

function post_event($id = -1)
{
    global $administrator, $link, $total;

    $des = $_POST['description'];
    if (!$des) {
        $_SESSION['notice'] = 'No description';
        exit(header('Location: '.DOMAIN.'new_event'));
    }
    if (strlen($des) > 256) {
        $des = substr(0, 256, $des);
    }
    $des = mysql_real_escape_string($des, $link->getLink());

    $addr = $_POST['web_address'];
    if (strlen($addr) > 256) {
        $addr = substr(0, 256, $addr);
    }
    if (!preg_match('#^http://#', $addr) && $addr) {
        $addr = 'http://'.$addr;
    }
    $addr = mysql_real_escape_string($addr, $link->getLink());

    $date = $_POST['date'];
    $uid = $_SESSION['UID'];

    if (empty($date)) {
        $_SESSION['notice'] = 'Date format incorrect.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }
    if (preg_match('/^([0-9]....)+-([0-9]..)+-([0-9]..)+/', $date)) {
        $_SESSION['notice'] = 'Date format incorrect.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }
    $date = explode('-', $date);
    if ($date[1] > 12) {
        $_SESSION['notice'] = 'Invalid month.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }
    if ($date[0] < date('Y')) {
        $_SESSION['notice'] = 'Date can not be set in the past.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }
    if ($date[2] > 31) {
        $_SESSION['notice'] = 'Invalid day.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }

    // Check the date.
    $years = array(
        '2012',
        '2016',
        '2020',
        '2024',
        '2028',
        '2032',
    );

    $mon['01'] = 31;
    $mon['02'] = 29;
    $mon['03'] = 31;
    $mon['04'] = 30;
    $mon['05'] = 31;
    $mon['06'] = 30;
    $mon['07'] = 31;
    $mon['08'] = 31;
    $mon['09'] = 30;
    $mon['10'] = 31;
    $mon['11'] = 30;
    $mon['12'] = 31;

    $mon1['01'] = 'January';
    $mon1['02'] = 'February';
    $mon1['03'] = 'March';
    $mon1['04'] = 'April';
    $mon1['05'] = 'May';
    $mon1['06'] = 'June';
    $mon1['07'] = 'July';
    $mon1['08'] = 'August';
    $mon1['09'] = 'September';
    $mon1['10'] = 'October';
    $mon1['11'] = 'November';
    $mon1['12'] = 'December';

    // Invalid date.
    if ($date[2] > $mon[$date[1]]) {
        $_SESSION['notice'] = 'Invalid date.';
        exit(header('Location: '.DOMAIN.'new_event'));
    }

    // Checking for leap years.
    if ($date[1] == '02') {
        if ($date[2] == '29' && !in_array($date[0], $years)) {
            $_SESSION['notice'] = "It's not a leap year.";
            exit(header('Location: '.DOMAIN.'new_event'));
        }
    }

    $con_date = $date[2].' '.$mon1[$date[1]].' '.$date[0];
    $expires = strtotime("$con_date");
    $date = mysql_real_escape_string(implode('-', $date));
    $ip = $_SERVER['REMOTE_ADDR'];

    if ($id != -1 && $id != null) { // Update an event. Only got here if we're an admin :D (Look down)
        $sql = 'UPDATE events SET '.
            "description = '$des', ".
            "address = '$addr', ".
            "date = '$date', ".
            "expires = '$expires' ".
            'WHERE no = '.$id;
        send($sql);
        $_SESSION['notice'] = 'Event updated.';
        exit(header('Location: '.DOMAIN.'events'));
    } else {
        if (!allowed('manage_events') && PRE_MODERATE_EVENTS) {
            $sql = "INSERT INTO pre_events (description,address,date,expires,uid,time,ip) VALUES ('$des','$addr','$date','$expires','$uid', UNIX_TIMESTAMP(), '$ip')";
        } else {
            $sql = "INSERT INTO events (description,address,date,expires,uid,ip) VALUES ('$des','$addr','$date','$expires','$uid', '$ip')";
        }
        $send = send($sql);
        $_SESSION['notice'] = 'Event submitted.';
        exit(header('Location: '.DOMAIN.'events'));
    }
}
$id = ((isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0 && allowed('manage_events')) ? $_GET['edit'] : -1);
if (isset($_POST['event_sub'])) {
    // CSRF checking.
    check_token();
    post_event($id);
} else {
    new_event($id);
}
require 'includes/footer.php';
