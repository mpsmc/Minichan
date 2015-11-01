<?php

chdir('..');
require 'includes/header.php';
$data = array();

switch ($_GET['type']) {
    case 'topics':
        $stmt = $link->db_exec('SELECT id, headline FROM topics WHERE sticky = 0 AND deleted = 0 ORDER BY id DESC LIMIT 5');
    break;
    case 'bumps':
        $stmt = $link->db_exec('SELECT id, headline FROM topics WHERE sticky = 0 AND deleted = 0 ORDER BY last_post DESC LIMIT 5');
    break;
    default:
        die(json_encode(array('error' => 'Unknown type')));
}

while (list($id, $headline) = $link->fetch_row()) {
    $data[] = array(
        'content' => 'open '.$id,
        'description' => $headline,
    );
}

echo json_encode($data);
