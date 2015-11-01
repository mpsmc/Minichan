<?php

require 'includes/header.php';
if (!$administrator) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

$page_title = 'Permissions overview';

$display_perms = array();

$link->db_exec('SELECT uid, permission FROM permissions');
while (list($uid, $permission) = $link->fetch_row()) {
    $display_perms[$uid][] = htmlspecialchars($permission);
}

echo '<table>';
echo '<tr><th class="minimal">UID</th><th>Permissions</th></tr>';
foreach ($display_perms as $user => $user_perms) {
    $user_perms = implode(', ', $user_perms);
    $plink = DOMAIN.'profile/'.$user;
    echo '<tr><td class="minimal"><a href="'.$plink.'">'.modname($user).'</a></td><td>'.$user_perms.'</td></tr>';
}

echo '</table>';

require 'includes/footer.php';
