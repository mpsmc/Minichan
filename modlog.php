<?php

require 'includes/header.php';

// If user is not an administrator.
if (!allowed('open_modlog')) {
    //if( ! $administrator ) {
    $_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
    header('Location: '.DOMAIN);
    exit('');
}

$page_title = 'Modlog';
$additional_head = '';

global $link;
echo '<table>
	<thead>
		<tr>
			<th class="minimal">Action</th>
			<th class="minimal">Target</th>
			<th class="minimal">Mod UID</th>
			<th class="minimal">Mod IP</th>
			<th class="minimal">Filed ▼</th>
		</tr>
	</thead>
	<tbody>';

$sql = 'SELECT action, target, mod_UID, mod_ip, time FROM mod_actions ORDER BY time DESC LIMIT 300';
$send = mysql_query($sql);

$selecter = true;
while ($get = mysql_fetch_array($send)) {
    if ($selecter) {
        $class = '';
    } else {
        $class = 'odd';
    }
    $selecter = !$selecter;

    // Get data.
    $filed = $get['time'];
    $filed = calculate_age($filed, $_SERVER['REQUEST_TIME']);

    $who = $get['mod_UID'];
    $ip = $get['mod_ip'];

    $display_ip = "<a href='".DOMAIN.'IP_address/'.$ip."'>".$ip.'</a>';

    $display_uid = "<a href='".DOMAIN.'profile/'.$who."'>".modname($who).'</a>';

    if ($who == $last_who) {
        $who_show = '<span class="unimportant">(See above)</span>';
    } else {
        $who_show = $who;
    }
    $last_who = $who;

    if ($display == $last_display) {
        $display_show = '<span class="unimportant">(See above)</span>';
    } else {
        $display_show = $display;
    }
    $last_display = $display;

    $action = $get['action'];
    $target = $get['target'];

    $doReplyStuff = false;
    if ($_SESSION['UID'] != '4d4839bb5803c1.29024241' && (in_array($action, array('open_profile', 'open_ip')))) {
        continue;
    }
    switch ($action) {
        case 'ban_uid':
        case 'unban_uid':
        case 'enable_reporting':
        case 'disable_reporting':
        case 'stalk_uid':
        case 'open_profile':
        case 'uid_note':
            $url = 'profile/##';
            break;
        case 'ban_ip':
        case 'unban_ip':
        case 'stalk_ip':
        case 'open_ip':
            $url = 'IP_address/##';
            break;
        case 'delete_reply':
        case 'undelete_reply':
            $doReplyStuff = true;
            $url = 'topic/##';
            break;
        case 'stick_topic':
        case 'unstick_topic':
        case 'lock_topic':
        case 'unlock_topic':
        case 'delete_topic':
        case 'undelete_topic':
        case 'edit_reply':
        case 'edit_topic':
        case 'handle_reports':
            $url = 'topic/##';
            break;
        case 'delete_image':
        case 'set_image':
            if (substr($target, 0, 5) == 'reply') {
                $target = substr($target, 6);
                $doReplyStuff = true;
                $url = 'topic/##';
            } else {
                $target = substr($target, 6);
                $url = 'topic/##';
            }
            break;
        default:
            $url = '';
            break;

    }

    if ($url) {
        if ($doReplyStuff) {
            $link->db_exec('SELECT parent_id FROM replies WHERE id = %1', $target);
            list($parent_id) = $link->fetch_row();
            $url = str_replace('##', $parent_id, $url).'#reply_'.$target;
        } else {
            $url = str_replace('##', $target, $url);
        }
        $target = "<a href='".DOMAIN.$url."'>".$url.'</a>';
    }

    // Parse bans.
    echo "<tr class=\"$class\">
	<td class=\"minimal\">".$action.'</td>
	<td class="minimal">'.$target."</td>
	<td class=\"minimal\">$display_uid</td>
	<td class=\"minimal\">$display_ip</td>
	<td class=\"minimal\">$filed ago</td>
	</tr>";
}
    echo '</tbody> </table>';

require 'includes/footer.php';
