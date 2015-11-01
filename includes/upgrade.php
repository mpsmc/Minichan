<?php

if (php_sapi_name() != 'cli') {
    die('Must be ran through CLI!');
}
chdir(realpath(dirname(__FILE__)));
$lockfile = 'locked';
if (file_exists($lockfile)) {
    die('Upgrade already in progress!');
}
register_shutdown_function(function () {
    global $lockfile;
    unlink($lockfile);
});
file_put_contents($lockfile, '1');
require_once 'config.php';
require_once 'database.class.php';
$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);

$link->db_exec('show tables');
$tableCount = 0;
while ($link->fetch_row()) {
    ++$tableCount;
}

function doDatabaseUpgrade($old, $new)
{
    global $link;
    $upgrade = true;
    require "database/upgrade-$old-to-$new.php";
}

$logState = 'base';
function console($str, $state = null)
{
    global $logState;
    if ($state == null) {
        $state = $logState;
    }
    echo "[$state] $str\n";
}

function multi_query($sql)
{
    global $link;
    $sql = preg_split('/;\s*$/m', $sql);
    foreach ($sql as $query) {
        if (trim($query) == '') {
            continue;
        }
        console('Performing query...');
        $link->db_exec($query);
    }
}

if ($tableCount === 0) {
    $logState = 'init';
    console('Loading tables');
    $sql = preg_split('/;\s*$/m', file_get_contents('../init.sql'));
    foreach ($sql as $query) {
        if (trim($query) == '') {
            continue;
        }
        console('Performing query...');
        $link->db_exec($query);
    }
    console('Finished loading tables');
} elseif ($link->getVersion() == DB_VERSION) {
    die('Nothing to upgrade!');
}

for ($i = $link->getVersion(); $i < DB_VERSION; ++$i) {
    $logState = "$i-to-".($i + 1);
    console("Starting upgrade $i-to-".($i + 1), 'base');
    doDatabaseUpgrade($i, $i + 1);
    $link->setVersion($i + 1);
    console("Finished upgrade $i-to-".($i + 1), 'base');
}
