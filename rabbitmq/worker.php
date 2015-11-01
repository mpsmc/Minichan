<?php

if (php_sapi_name() != 'cli') {
    die('Must be ran through CLI!');
}
chdir(realpath(dirname(__FILE__)).'/..');
define('IS_ASYNC_WORKER', true);

require_once 'vendor/autoload.php';
require_once 'includes/config.php';
require_once 'includes/database.class.php';
require_once 'includes/functions.php';
require_once 'includes/async_functions.php';

$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);

if (file_exists('includes/private.php')) {
    require 'includes/private.php';
}

$async = new AsyncImplementation();

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(RABBITMQ_SERVER, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASS, RABBITMQ_VHOST);
$channel = $connection->channel();

$channel->exchange_declare('tinybbs', 'topic', false, true, false);

list($queue_name) = $channel->queue_declare('phpworker', false, true, false, false);

$binding_keys = array('async.*');

foreach ($binding_keys as $binding_key) {
    $channel->queue_bind($queue_name, 'tinybbs', $binding_key);
}

echo '[*] Waiting for commands. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    global $async;
    echo "\n[x] ",$msg->delivery_info['routing_key'], ': ', $msg->body, "\n";
    $data = json_decode($msg->body, true);
    try {
        call_user_func_array(array($async, $data['name']), $data['arguments']);
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    } catch (Exception $e) {
        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
        echo 'Caught exception: ', $e->getMessage(), "\n";
        sleep(5);
    }
};

$channel->basic_consume($queue_name, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
