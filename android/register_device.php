<?php

chdir('..');
//require("includes/header.php");
require 'includes/config.php';
require 'includes/database.class.php';
require 'includes/functions.php';

//$_POST = $_GET;

function gen_uuid($len = 8)
{
    // Seed + uniqueness
    $hex = md5('ahoasdha8s9dysaoda'.uniqid('', true));
    $pack = pack('H*', $hex);
    $uid = base64_encode($pack);        // max 22 chars
    $uid = preg_replace('#[^A-Z1-9]#', '', strtoupper($uid));    // uppercase only

    if ($len < 4) {
        $len = 4;
    }
    if ($len > 128) {
        $len = 128;
    }                       // prevent silliness, can remove

    while (strlen($uid) < $len) {
        $uid = $uid.gen_uuid(22);
    }     // append until length achieved

    return substr($uid, 0, $len);
}

function token_exists($rand_token)
{
    global $link;
    $result = $link->db_exec('SELECT 1 FROM android_tokens WHERE rand_token = %1 LIMIT 1', $rand_token);

    return ($link->num_rows() > 0);
}

$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);
date_default_timezone_set('UTC');

$action = $_POST['action'];

if ($action == 'register') {
    $token = $_POST['token'];

    do {
        $rand_token = gen_uuid(10);
    } while (token_exists($rand_token));

    $link->show_errors = false;
    $finished = $link->db_exec('INSERT INTO android_tokens (token, rand_token, request_time) VALUES (%1, %2, UNIX_TIMESTAMP())', $token, $rand_token);
    $link->show_errors = true;

    if ($finished) {
        echo $rand_token;
    } else {
        echo 'error';
    }
} elseif ($action == 'unregister') {
    $link->db_exec('DELETE FROM android_tokens WHERE token = %1', $_POST['token']);
    var_dump($_POST['token']);
}
