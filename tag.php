<?php

die();
define('ENABLE_CACHE', 1);
session_cache_expire(0);
require 'includes/header.php';

if (USER_REPLIES < 1 && $_SERVER['HTTP_IF_NONE_MATCH']) {
    $info = explode(':', $_SERVER['HTTP_IF_NONE_MATCH']);
    $uid = $info[0];
    $md5_password = $info[1];

    if ($uid != $_SESSION['UID']) {
        $stmt = $link->db_exec('SELECT password FROM users WHERE uid = %1', $uid);
        list($password) = $link->fetch_row($stmt);

        if (md5($password) == $md5_password) {
            $_SESSION['UID'] = $uid;
            $_SESSION['ID_activated'] = true;
            setcookie('UID', $uid, $_SERVER['REQUEST_TIME'] + 315569260, '/', COOKIE_DOMAIN);
            setcookie('password', $password, $_SERVER['REQUEST_TIME'] + 315569260, '/', COOKIE_DOMAIN);
            $_COOKIE['password'] = $password;

            $stmt = $link->db_exec('SELECT spoiler_mode, topics_mode, ostrich_mode, snippet_length FROM user_settings WHERE uid = %1', $uid);
            list($user_config['spoiler_mode'], $user_config['topics_mode'], $user_config['ostrich_mode'], $user_config['snippet_length']) = $link->fetch_row($stmt);

            foreach ($user_config as $key => $value) {
                if ($value != 0) {
                    setcookie($key, $value, $_SERVER['REQUEST_TIME'] + 315569260, '/', COOKIE_DOMAIN);
                }
            }
            $_SESSION['last_user_style_check'] = 0;
        }
    }
}

header('Content-Type: text/javascript');
header('ETag: '.$_SESSION['UID'].':'.md5($_COOKIE['password']));
