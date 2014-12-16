<?php
require('includes/header.php');
$additional_head .= '<script type="text/javascript" src="'.DOMAIN.'/javascript/graph/raphael-min.js"></script>';
$additional_head .= '<script type="text/javascript" src="'.DOMAIN.'/javascript/graph/dracula_graph.js?'.mt_rand().'"></script>';
$additional_head .= '<script type="text/javascript" src="'.DOMAIN.'/javascript/graph/dracula_graffle.js?'.mt_rand().'"></script>';
$additional_head .= '<script type="text/javascript" src="'.DOMAIN.'/javascript/graph/stalk.js?'.mt_rand().'"></script>';

ini_set("memory_limit","100M");
set_time_limit(60);

// If you're not an admin or a Moderator, you're out of luck.
if( !allowed("open_profile") || !allowed("open_ip")) {
	add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

function search_uid($uid) {
	global $link, $data, $found_ips, $found_uids;
	
	if(isset($found_uids[$uid])) return;
	$found_uids[$uid] = true;

	$search = $link->db_exec('SELECT ip_address, last_ip FROM users WHERE uid = %1', $uid);
	while(list($first_ip, $last_ip) = $link->fetch_row($search)) {
		$data['connections'][] = array($uid, $first_ip);
		$data['connections'][] = array($uid, $last_ip);

		search_ip($first_ip);
		if($last_ip != $first_ip)
			search_ip($last_ip);
	}
	$link->free($search);
	
	$search = $link->db_exec('SELECT DISTINCT author_ip FROM replies WHERE author = %1 UNION SELECT DISTINCT author_ip FROM topics WHERE author = %1', $uid);
	while(list($ip) = $link->fetch_row($search)) {
		if(in_array(array($uid, $ip), $data['connections']) || in_array(array($ip, $uid), $data['connections'])) continue;
		$data['connections'][] = array($uid, $ip);
		search_ip($ip);
	}
	$link->free($search);
}

function search_ip($ip) {
	global $link, $data, $found_ips, $found_uids, $postcounts;
	
	if(isset($found_ips[$ip])) return;
	$found_ips[$ip] = true;
	
	$search = $link->db_exec('SELECT uid FROM users WHERE ip_address = %1 OR last_ip = %1 UNION SELECT DISTINCT author FROM replies WHERE author_ip = %1 UNION SELECT DISTINCT author FROM topics WHERE author_ip = %1', $ip);
	while(list($uid) = $link->fetch_row($search)) {
		if(in_array(array($ip, $uid), $data['connections']) || in_array(array($uid, $ip), $data['connections'])) continue;
	
		if(!isset($postcounts[$uid])) {
			$link->db_exec("SELECT count(*) as count FROM replies WHERE author=%1", $uid);
			list($num_topics) = $link->fetch_row();
			$link->free();
			$link->db_exec("SELECT count(*) as count FROM topics WHERE author=%1", $uid);
			list($num_replies) = $link->fetch_row();
			$link->free();
			$postcount = $num_topics + $num_replies;
			$postcounts[$uid] = $postcount;
		}
		
		if($postcounts[$uid] > 0) {
			$data['connections'][] = array($ip, $uid);
			search_uid($uid);
		}
	}
	$link->free($search);
}

$data = array();
$data['connections'] = array();
$found_ips = array();
$found_uids = array();
$postcounts = array();

if($_GET['uid']) {
	log_mod("stalk_uid", $_GET['uid']);
	search_uid($_GET['uid']);
}else if($_GET['ip']) {
	log_mod("stalk_ip", $_GET['ip']);
	search_ip($_GET['ip']);
}else{
	add_error('What are you doing?', true);
}

foreach($found_ips as $ip=>$placeholder) {
	$link->db_exec("SELECT namefag, tripfag, count(*) FROM topics WHERE author_ip = %1 GROUP BY namefag, tripfag UNION ALL SELECT namefag, tripfag, count(*) FROM replies WHERE author_ip = %1 GROUP BY namefag, tripfag", $ip);
	while(list($name, $trip) = $link->fetch_row()) {
		$names[$name.$trip]++;
	}
}

//$data["namelinks"] = array();
/*
foreach($found_uids as $uid=>$placeholder) {
	$link->db_exec("SELECT namefag, tripfag, count(*) FROM topics WHERE author = %1 GROUP BY namefag, tripfag UNION ALL SELECT namefag, tripfag, count(*) FROM replies WHERE author = %1 GROUP BY namefag, tripfag", $uid);
	while(list($name, $trip, $count) = $link->fetch_row()) {
		$names[$name.$trip]+=$count;
		//if($names[$data["namelinks"][$uid]] > $names[$name.$trip])
		//	$data["namelinks"][$uid] = $name.$trip;
	}
}

asort($names, SORT_NUMERIC);
$names = array_reverse($names, true);
$data["names"] = $names;
unset($names);
*/
?>
<div id="canvas"></div>
<script>draw_graph(<?php echo json_encode($data); ?>);</script>
<?php

require('includes/footer.php');