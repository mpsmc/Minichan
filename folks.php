<?php

require 'includes/header.php';
update_activity('folks', 1);

$stmt = $link->db_exec('SELECT activity.action_name, activity.action_id, activity.uid, activity.time, topics.headline FROM activity LEFT OUTER JOIN topics ON activity.action_id = topics.id WHERE activity.time > %1 - 960 ORDER BY time DESC', $_SERVER['REQUEST_TIME']);
$count = $link->num_rows($stmt);
$page_title = 'Folks on-line ('.$count.')';

$table = new table();
$columns = array(
    'Doing',
    'Poster',
    'Last sign of life ▼',
);

$table->define_columns($columns, 'Doing');
$table->add_td_class('Poster', 'minimal');
$table->add_td_class('Last sign of life ▼', 'minimal');

$i = 0;

while (list($action, $action_id, $uid, $age, $headline) = $link->fetch_row($stmt)) {
    // Maximum amount of actions to be shown.
    if (++$i == 100) {
        break;
    }

    if ($uid == $_SESSION['UID']) {
        $uid = 'You!';
    } else {
        if (allowed('open_profile')) {
            $uid = '<a href="'.DOMAIN.'profile/'.$uid.'">'.$uid.'</a>';
        } else {
            $uid = '?';
        }
    }

    $bump = calculate_age($age, $_SERVER['REQUEST_TIME']);
    $headline = htmlspecialchars($headline);

    $action = $actions[$action];
    $action = str_replace('{headline}', $headline, $action);
    $action = str_replace('{action_id}', $action_id, $action);

    // Unknown or unrecorded actions are bypassed.
    if ($action == null) {
        continue;
    }

    // Repeated actions are listed as (See above).
    if ($action == $old_action) {
        $temp = '<span class="unimportant">(See above)</span>';
    } else {
        $old_action = $action;
        $temp = $action;
    }

    $values = array(
        $temp,
        $uid,
        '<span class="help" title="'.format_date($age).'">'.calculate_age($age).'</span>',
    );
    $table->row($values);
}
echo $table->output();
if ($count > 100) {
    echo '<p class="unimportant">(There are "a lot" of people active right now. Not all are shown here.)</p>';
}
require 'includes/footer.php';
