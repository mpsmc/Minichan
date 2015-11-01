<?php

require 'includes/header.php';
update_activity('bulletins', 1);
update_activity('bulletins_old', 1);

if (isset($_GET['page'])) {
    $page = $_GET['page'];
    if (!is_numeric($page)) {
        add_error('Invalid ID.', true);
    }
} else {
    $page = 1;
} {
    if (allowed('manage_bulletins')) {
        $delete = $_GET['delete'];
        if (!is_numeric($delete)) {
            $page = 1;
        } else {
            $link->db_exec('DELETE FROM bulletins WHERE no = %1', $delete);
            $_SESSION['notice'] = 'Bulletin deleted.';
            exit(header('Location: '.DOMAIN.'bulletins'));
        }
    } else {
        $page = 1;
    }
}

// How many bulletins to show per page.
$number = 50;
if ($page == 1) {
    $start = 0;
    $finish = $start + $number; // Getting the limit results.
} else {
    $start = $page - 1;
    $start = $start * $number;
    $finish = $number;
}

// Set page title.
$page_title = 'Latest bulletins';
$additional_head = '';

// Display the bulletins menu and any messages.
echo '<ul class="menu">';
if (ALLOW_USER_BULLETINS || allowed('manage_bulletins')) {
    echo '<li><a href="'.DOMAIN.'new_bulletin">New bulletin</a></li>';
}
echo '<li><a href="'.DOMAIN.'about_bulletins">About</a></li>';
if (allowed('manage_bulletins')) {
    if (PRE_MODERATE_BULLETINS && ALLOW_USER_BULLETINS) {
        echo '<li><a href="'.DOMAIN.'moderate_bulletins">Moderate bulletins</li></a>';
    }
}
echo'</ul>';

// Get all the bulletins.
$send = $link->db_exec('SELECT * FROM bulletins ORDER BY time DESC LIMIT %1, %2', $start, $finish);
if (!$link->num_rows($send)) {
    echo '<h2>No bulletins found.</h2>';
    require 'includes/footer.php';
    die();
}

echo '<table>
	<thead>
		<tr>
			<th>Message</th>
			<th class="minimal">Poster</th>
			<th class="minimal">Age ▼</th>';
            if (allowed('manage_bulletins')) {
                echo '<th class="minimal">Modify</th><th class="minimal">From</th>';
            }
        echo'</tr>
	</thead>
	<tbody>';

    // Stuff to set the alternating classes.
    $selecter = '1';
    while ($get = mysql_fetch_assoc($send)) {
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
        $no = $get['no'];
        $message = parse($get['message']);
        $poster = $get['poster'];
        $uid = $get['uid'];
        $age = $get['time'];
        $date = $get['date'];
        $bump = calculate_age($age, $_SERVER['REQUEST_TIME']);

        // Then display all the bulletins.
        echo '<tr class="'.$class.'">
		<td>'.$message.'</td>
		<td class="minimal">'.$poster.'</td>
		<td class="minimal"><span class="help" title="'.$date.'">'.$bump.'</span></td>';
        $editLink = '<a href="'.DOMAIN.'edit_bulletin/'.$no.'">Edit</a>';
        $deleteLink = '<a href="'.DOMAIN.'delete_bulletin/'.$no."\" onclick=\"return confirm('Really delete?');\">Delete</a>";

        $EditOrDelete = $editLink.' / '.$deleteLink;

        if (allowed('manage_bulletins')) {
            echo '<td class="minimal">'.$EditOrDelete.'</td><td class="minimal"><a href="'.DOMAIN.'profile.php?uid='.$uid.'">UID</a></td>';
        }
        echo '</tr>';
        if ($selecter == '1' && $set == '1') {
            ++$selecter;
        }
    }

    // Close table after parsing the bulletins.
    echo '</tbody> </table>';

    // Now, lets have have a cookie.
    setcookie('last_bulletin', $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'] + 315569260, '/');

    // Insert newer and older links
    if ($page == 1 && $num > $number) {
        echo '<ul class="menu"> <li> <a href="'.DOMAIN.'bulletins/'.$page + 1 .'">Older</a></li></ul>';
    }
    if ($page > 1 && $num > $number) {
        echo '<ul class="menu"> <li> <a href="'.DOMAIN.'bulletins/1">Newest</a></li> <li> <a href="'.DOMAIN.'bulletins/'.$page - 1 .'">Newer</a></li> <li> <a href="'.DOMAIN.'bulletins/'.$page + 1 .'">Older</a></li></ul>';
    }

require 'includes/footer.php';
