<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AsyncToRabbitMQ {
    private $channel;
    
    function __call($name, $arguments) {
        $msg = json_encode(array(
            'name' => $name,
            'arguments' => $arguments
        ));
        $this->getChannel()->basic_publish(new AMQPMessage($msg), 'tinybbs', 'async.'.$name);
    }
    
    function getChannel() {
        if($this->channel) return $this->channel;
        $connection = new AMQPStreamConnection(RABBITMQ_SERVER, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASS, RABBITMQ_VHOST);
        $this->channel = $connection->channel();
        $this->channel->exchange_declare('tinybbs', 'topic', false, true, false);
        return $this->channel;
    }
}

class AsyncImplementation {
    function __construct() {
        global $link;
        $this->link = $link;
    }
    
    function log($msg) {
        if(!defined("IS_ASYNC_WORKER")) return;
        echo $msg."\n";
    }
    
    function checkUID($uid, $ip) {
        $result = json_decode(file_get_contents("http://api.stopforumspam.org/api?ip=" . urlencode($ip) . "&f=json"));
        if($result == null)
            throw new Exception("Could not retrieve data from stopforumspam.com");
        
        if($result->success && $result->ip->appears) {
            $reason = "The IP address ($ip) used to create your UID has been listed in stopforumspam.com, so your account has been banned.";
            
            $this->link->insertorupdate("ip_bans", array(
                "ip_address" => $ip,
                "expiry" => 0,
                "filed" => "UNIX_TIMESTAMP()",
                "who" => "",
                "stealth" => 0,
                "reason" => $reason
            ));
            
            log_mod("ban_ip", $ip);
            
            $this->link->insertorupdate("uid_bans", array(
                "uid" => $uid,
                "expiry" => 0,
                "filed" => "UNIX_TIMESTAMP()",
                "who" => "",
                "stealth" => 0,
                "reason" => $reason
            ));
            
            log_mod("ban_uid", $uid);
            
            $this->log("Banned $uid / $ip");
        }
    }
}