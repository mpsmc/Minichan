<?php

require 'bootstrap.php';

$return = array();
$link->db_exec('SELECT uid FROM users WHERE uid = %1 AND password = %2', $_GET['user'], $_GET['pass']);
if ($link->num_rows() > 0) {
    $row = $link->fetch_row();
    $return['valid'] = ($row[0] == $_GET['user']);
} else {
    $return['valid'] = false;
}

if ($return['valid']) {
    check_permissions($_GET['user']);
    if ($administrator) {
        $return['permission'] = 'administrator';
    } elseif ($moderator) {
        $return['permission'] = 'moderator';
    } elseif ($janitor) {
        $return['permission'] = 'janitor';
    } else {
        $return['permission'] = 'none';
    }
}

header('Content-type: text/plain');
echo json_encode($return);
