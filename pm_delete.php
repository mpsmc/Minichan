<?php

require 'includes/header.php';
force_id();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    add_error('There is no such private message or you are not wise enough.', true);
}

$delete_query = 'DELETE FROM `private_messages` WHERE `id` = %1';
if (!allowed('mod_pm')) {
    $delete_query .= ' AND `expiration` < UNIX_TIMESTAMP() AND (`source` = %2 OR `destination` = %2)';
}
if (allowed('mod_pm') && !$administrator) {
    $delete_query .= ' AND (`source` = %2 OR `source` = \'mods\' OR `destination` = %2 OR `destination` = \'mods\')';
}

$stmt = $link->db_exec($delete_query, $_GET['id'], $_SESSION['UID']);

if ($link->affected_rows() > 0) {
    redirect('Private message successfully deleted.', ''.DOMAIN.'private_messages');
} else {
    add_error('There is no such private message or you are not wise enough.', true);
}

require 'includes/footer.php';
