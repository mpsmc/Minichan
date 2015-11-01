<?php

chdir('..');
echo `php -q /home/minichan/cron/googleAuthenticate.php`;
die();
require 'includes/header.php';
$link->db_exec('SELECT token FROM android_tokens WHERE uid = %1', $_SESSION['UID']);
if ($link->num_rows() > 0) {
    $rows = $link->fetch_assoc();
    var_dump($rows['token']);
} else {
    echo "You don't have an android device linked";
}
require 'includes/footer.php';
