<?php
require('includes/header.php');
if($_GET['secret'] != IRC_PING_SECRET) {
	http_response_code(403);
	die("Invalid secret");
}

$payload = json_decode($_REQUEST['payload']);

if($payload->ref != "refs/heads/minichan") {
	die("Invalid branch");
}

$body = array();

foreach($payload->commits as $commit) {
	$body[] = '- ' . explode("\n", $commit->message)[0];
}

$body = implode($body, "\n") . "\n\n" . $payload->compare;

$headline = explode("\n", $payload->head_commit->message)[0];

$link->insert("topics", array(
	"time" => "UNIX_TIMESTAMP()",
	"last_post" => "UNIX_TIMESTAMP()",
	"author" => $administrators['r04r'],
	"namefag" => "System",
	"tripfag" => "",
	"author_ip" => $_SERVER['REMOTE_ADDR'],
	"headline" => "Update: " . $headline,
	"body" => $body,
	"admin_hyperlink" => true
));