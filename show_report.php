<?php

require 'includes/header.php';

// If user is not an administrator.
if (!allowed('manage_reports')) {
    $_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
    header('Location: '.DOMAIN);
    exit('');
}

$topic_id = (int) $_GET['topic'];
$reply_id = (int) $_GET['reply'];

if (!$reply_id) {
    $link->db_exec('SELECT headline, body FROM topics WHERE id = %1', $topic_id);
    list($headline, $body) = $link->fetch_row();

    $sqlreports = $link->db_exec('SELECT reports.id, reports.reason, reports.ip_address, reports.uid, reports.handled FROM reports WHERE reports.topic = %1 GROUP BY reports.id', $topic_id);
} else {
    $link->db_exec('SELECT topics.headline, replies.body FROM topics, replies WHERE topics.id = replies.parent_id AND replies.id = %1', $reply_id);
    list($headline, $body) = $link->fetch_row();

    $sqlreports = $link->db_exec('SELECT reports.id, reports.reason, reports.ip_address, reports.uid, reports.handled FROM reports WHERE reports.topic = %1 AND reports.reply = %2 GROUP BY reports.id', $topic_id, $reply_id);
}

$page_title = 'Report Details';
$additional_head = '';
if (!$headline) {
    echo 'Target does no longer exist.';
} else {
    echo "<b>Headline:</b> <a href='".DOMAIN.'topic/'.$topic_id.($reply_id ? '#reply_'.$reply_id : '')."'>".htmlspecialchars($headline).'</a><br />';

    echo '<b>Snippet:</b> '.snippet($body).'<br /><br />';
}
echo '<table>
	<thead>
		<tr>
			<th>Reason</th>
			<th class="minimal">IP</th>
			<th class="minimal">UID</th>
			<th class="minimal">Delete</th>
		</tr>
	</thead>
	<tbody>';

while (list($report_id, $report_reason, $reporter_ip, $reporter_uid, $report_handled) = $link->fetch_row($sqlreports)) {
    $report_reason = htmlspecialchars($report_reason);
    if ($report_handled) {
        $report_reason = "<s>$report_reason</s>";
    }

    echo '<tr>';
    echo '<td>'.$report_reason.'</td>';
    echo "<td class='minimal'><a href='".DOMAIN.'IP_address/'.$reporter_ip."'>IP</a></td>";
    echo "<td class='minimal'><a href='".DOMAIN.'profile/'.$reporter_uid."'>".modname($reporter_uid).'</a></td>';
    echo "<td class='minimal'><a href='".DOMAIN.'report/handle/'.$report_id."'>Delete</a></td>";
    echo '</tr>';
}

echo '</tbody></table>';
require 'includes/footer.php';
