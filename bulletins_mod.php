<?php

require 'includes/header.php';

// If user is not an administrator.
if (!allowed('manage_bulletins')) {
    $_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
    header('Location: '.DOMAIN);
    exit('');
}

if (isset($_GET['no'])) {
    if (!is_numeric($_GET['no'])) {
        $_SESSION['notice'] = 'Invalid bulletin.';
        exit(header('Location: '.DOMAIN.'moderate_bulletins'));
    }
}

if (isset($_GET['mode'])) {
    if ($_GET['mode'] != 'allow') {
        if ($_GET['mode'] != 'deny') {
            $_SESSION['notice'] = 'Method must either be allow or deny.';
            exit(header('Location: '.DOMAIN.'moderate_bulletins'));
        }
    }
}

$page_title = 'Moderate bulletins';
$additional_head = '';

function view_pre_bulletins()
{
    global $link;
    echo '<table>
		<thead>
			<tr>
				<th>Message</th>
				<th class="minimal">Poster</th>
				<th class="minimal">Age ▼</th>
				<th class="minimal">Allow?</th>
				<th class="minimal">IP Address</th>
			</tr>
		</thead>
		<tbody>';

    $sql = 'SELECT * FROM pre_bulletins ORDER BY time DESC';
    if (!$send = mysql_query($sql)) {
        $_SESSION['notice'] = mysql_error();
        exit(header('Location: '.DOMAIN));
    }

    $selecter = 1;
    while ($get = mysql_fetch_array($send)) {
        $set = '1';
        if ($selecter == '1') {
            $class = '';
        } else {
            $class = 'odd';
        }
        if ($selecter == '2') {
            --$selecter;
            $set = '2';
        }

        // Get data.
        $no = $get['no'];
        $message = htmlspecialchars($get['message']);
        $poster = $get['poster'];
        $time = $get['time'];
        $time = calculate_age($time, $_SERVER['REQUEST_TIME']);
        $date = $get['date'];
        $uid = $get['uid'];
        $ip = $get['ip'];
        $uid = '<a href="'.DOMAIN.'profile/'.$uid.'">'.$poster.'</a>';

        // Parse bulletins in the queue.
        echo "<tr class=\"$class\">
		<td>$message</td>
		<td class=\"minimal\">$uid</td>
		<td class=\"minimal\"><span title='$date' class='help'>$time</span></td>
		<td class=\"minimal\"><a href='".DOMAIN."moderate_bulletins/$no/allow' onclick=\"return confirm('Allow bulletin?');\">yes</a>/<a href='".DOMAIN."moderate_bulletins/$no/deny' onclick=\"return confirm('Deny bulletin?');\">no</a></td>
		<td class=\"minimal\">$ip</td>
		</tr>";
    }
    echo '</tbody> </table>';
}

function allow_deny()
{
    global $link;
    if (!allowed('manage_bulletins')) {
        $_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
        header('Location: '.DOMAIN);
        exit('');
    }

    if (isset($_GET['no']) && isset($_GET['mode'])) {
        // If denying the bulletin.
        if ($_GET['mode'] == 'deny') {
            $sql = "DELETE FROM pre_bulletins WHERE no='$_GET[no]'";
            if (!$send = mysql_query($sql)) {
                $_SESSION['notice'] = mysql_error();
                exit(header('Location: '.DOMAIN.'moderate_bulletins'));
            } else {
                $_SESSION['notice'] = 'Bulletin denied';
                exit(header('Location: '.DOMAIN.'moderate_bulletins'));
            }
        }

        $sql = "SELECT * FROM pre_bulletins WHERE no='$_GET[no]'";
        if (!$send = mysql_query($sql)) {
            $_SESSION['notice'] = mysql_error();
            exit(header('Location: '.DOMAIN.'moderate_bulletins'));
        }
        $get = mysql_fetch_array($send);
        $message = $get['message'];
        $message = mysql_real_escape_string($message); // Safety first.
        $poster = $get['poster'];
        $uid = $get['uid'];
        $ip = $get['ip'];

        // Update data if needed.
        $new_time = $_SERVER['REQUEST_TIME'];
        $new_date = format_date($new_time);

        $sql = "INSERT INTO bulletins (message,poster,time,date,uid,ip) VALUES ('$message','$poster','$new_time','$new_date','$uid','$ip')";
        if (!$send = mysql_query($sql)) {
            $_SESSION['notice'] = mysql_error();
            exit(header('Location: '.DOMAIN.'moderate_bulletins'));
        } else {
            $sql = "DELETE FROM pre_bulletins WHERE no='$_GET[no]'";
            if (!$send = mysql_query($sql)) {
                $_SESSION['notice'] = mysql_error();
                exit(header('Location: '.DOMAIN.'moderate_bulletins'));
            }
            $_SESSION['notice'] = 'Bulletin allowed';
            exit(header('Location: '.DOMAIN.'moderate_bulletins'));
        }
    } else {
        $_SESSION['notice'] = 'Error try again.';
        exit(header('Location: '.DOMAIN.'moderate_bulletins'));
    }
}

// Time to choose a choice.
if (isset($_GET['no']) && isset($_GET['mode'])) {
    allow_deny();
} else {
    view_pre_bulletins();
}

require 'includes/footer.php';
