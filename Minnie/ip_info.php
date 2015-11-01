<?php

chdir('..');
require 'includes/config.php';
if ($_GET['secret'] != IP_INFO_SECRET) {
    die('Unauthorized.');
}
require 'includes/database.class.php';
$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);

$queries[] = 'SELECT COUNT(*) as Occurances, concat(trim(namefag), tripfag) as Name FROM replies WHERE author_ip = %1 GROUP BY Name';
$queries[] = 'SELECT COUNT(*) as Occurances, concat(trim(r.namefag), r.tripfag) as Name FROM replies AS r, users AS u WHERE r.author = u.uid AND (u.last_ip = %1 OR u.ip_address = %1) GROUP BY Name';
$queries[] = 'SELECT COUNT(*) as Occurances, concat(trim(namefag), tripfag) as Name FROM topics WHERE author_ip = %1 GROUP BY Name';
$queries[] = 'SELECT COUNT(*) as Occurances, concat(trim(t.namefag), t.tripfag) as Name FROM topics AS t, users AS u WHERE t.author = u.uid AND (u.last_ip = %1 OR u.ip_address = %1) GROUP BY Name';
$names = array();
$result = array();

foreach ($queries as $query) {
    $link->db_exec($query, $_GET['ip']);
    while (list($count, $name) = $link->fetch_row()) {
        if (!trim($name)) {
            $name = 'Anonymous';
        }
        $names[$name] += $count;
    }
}

asort($names);
$names = array_reverse($names);
$names = array_slice($names, 0, 3);

$result['names'] = $names;

echo json_encode($result);
