<?php
//setlocale(LC_ALL, "ISO-8859-1");
//require('includes/profiler.php');
//if(ENABLE_PROFILER) profile_start('init');
ini_set("session.gc_maxlifetime", "21600"); //6 hours
mb_internal_encoding("UTF-8");
// No IPV6 yet :-/
if(substr($_SERVER['REMOTE_ADDR'], 0, 7) == "::ffff:") $_SERVER['REMOTE_ADDR'] = substr($_SERVER["REMOTE_ADDR"], 7);
if(substr($_SERVER['SERVER_ADDR'], 0, 7) == "::ffff:") $_SERVER['SERVER_ADDR'] = substr($_SERVER['SERVER_ADDR'], 7);

function ipCIDRCheck ($IP, $CIDR) {
	list ($net, $mask) = explode ("/", $CIDR);
	$ip_net = ip2long ($net);
	$ip_mask = ~((1 << (32 - $mask)) - 1);
	$ip_ip = ip2long ($IP);
	$ip_ip_net = $ip_ip & $ip_mask;
	return ($ip_ip_net == $ip_net);
}

if(isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
	unset($_SERVER["HTTP_X_FORWARDED_FOR"]);
	// $cloudflare_ips = array("199.27.128.0/21", "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22", "141.101.64.0/18", "108.162.192.0/18", "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22", "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/12");
	// $cloudflare_ok = false;
	// foreach($cloudflare_ips as $cloudflare_ip) {
		// if(ipCIDRCheck($_SERVER['REMOTE_ADDR'], $cloudflare_ip)) {
			// $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			// $cloudflare_ok = true;
			// break;
		// }
	// }
	
	// if($cloudflare_ok) {
		// if($_SERVER['HTTP_X_FORWARDED_FOR'] == $_SERVER["HTTP_CF_CONNECTING_IP"])
			// unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	// }else{
		// mail("r04r@minichan.org", "Minichan: Unknown cloudflare IP", $_SERVER['REMOTE_ADDR'] . " sent the HTTP_CF_CONNECTING_IP header, and I could not match this IP with any of the CIDR lists.");
	// }
}

$_start_time = microtime(); //Prepare our neat xx seconds to load thingy at the bottom of the page.
require_once('vendor/autoload.php');
require_once('includes/config.php');
require_once('includes/database.class.php');
require_once('includes/functions.php');
require_once('includes/async_functions.php');
require_once('includes/unicode.php');

/*
if(!$_COOKIE['last_topic']){
	recaptcha('Due to an ongoing attack, please enter this captcha');
	setcookie("last_topic", 0, 3600);
}
*/

if(!defined('ENABLE_CACHE'))
	session_cache_limiter('nocache');
else
	session_cache_limiter('private_no_expire');
	
session_name('SID');
session_start();

require_once("includes/ChromePhp.php");
Console::useFile(SITE_ROOT . '/tmp', '/tmp');
require("includes/useragents.php");
define("MOBILE_MODE", check_user_agent("mobile"));

function abortForMaintenance($error) {
	global $link;
	$link = null;
	date_default_timezone_set('UTC');
	header('Content-Type: text/html; charset=UTF-8');
	ob_start();
	define('DEFCON', 5);
	http_response_code(500);
	$page_title = "Maintenance";
	echo $error;
	require("includes/footer.php");
	die();
}

if(file_exists("includes/locked")) {
	abortForMaintenance("The board is currently under maintenance! Please give us a minute and refresh the page!");
}

//print_r($_COOKIE);die();
//Volgende is wat slimmer:
/*
if(!isset($_COOKIE["last_topic"])){
	if($_GET['key'] != $_SESSION['secret_key'] || !isset($_SESSION['secret_key'])){
		$_SESSION['secret_key'] = md5(rand().SALT);
		echo '<meta http-equiv="refresh" content="0;url=?key='.$_SESSION['secret_key'].'">';
		die();
	}else{
		unset($_SESSION['secret_key']);
		setcookie("last_topic", 0, 3600);
	}
}
*/
$link = new db($db_info['server'], $db_info['username'], $db_info['password'], $db_info['database']);

if(file_exists("includes/private.php")){
	require("includes/private.php");
}

if($link->getVersion() != DB_VERSION) {
	abortForMaintenance("Database version mismatch! The Board has likely been upgraded lately, and the administrator has not yet executed includes/upgrade.php");
}

if(RABBITMQ_SERVER) {
    $async = new AsyncToRabbitMQ();
}else{
    $async = new AsyncImplementation();
}

date_default_timezone_set('UTC');
header('Content-Type: text/html; charset=UTF-8');

// Assume that we have no privileges.
$moderator = false;
$administrator = false;
$janitor = false;

$link->db_exec("SELECT value FROM flood_control WHERE setting =  'defcon'");
list($DEFCON) = $link->fetch_row();
define("DEFCON", $DEFCON);
unset($DEFCON);

// If necessary, assign the client a new ID.
if(empty($_COOKIE['UID']) || empty($_COOKIE['password'])) {
	if (check_proxy()){
		$proxy = true;
	}
	
	if($proxy){
		if(!$_SESSION['proxy_notice']) $_SESSION['notice'] = "Proxies are blocked from creating accounts. Please visit the site without using a proxy first or <a href='".DOMAIN."restore_ID'>restore your ID</a>.";
		//var_dump($_SERVER);die();
		$_SESSION['proxy_notice'] = true;
	}else{
		if(!$_SESSION['welcomed']){
			$_SESSION['notice'] = 'Welcome to <strong>' . SITE_TITLE . '</strong>, an account has automatically been created and assigned to you, you don\'t have to register or log in to use the board, but don\'t clear your cookies unless you have <a href="'.DOMAIN.'dashboard">set a memorable name and password</a>. Alternatively, you can <a href="'.DOMAIN.'restore_ID">restore your ID</a>.';
		}
		
		force_id($proxy, false, true);
		$_SESSION['welcomed'] = true;
	}
} else if( ! empty($_COOKIE['password'])) {
	// Log in those who have just began their session.
	if( ! isset($_SESSION['ID_activated'])) {
		activate_id();
	}
	// ...and check for mod/admin privileges from the cache.
	if(in_array($_SESSION['UID'], $moderators)) {
		$moderator = true;
	} else if(in_array($_SESSION['UID'], $administrators)) {
		$administrator = true;
	} else if(in_array($_SESSION['UID'], $janitors)) {
		$janitor = true;
	}

	/*
	if($moderator||$administrator){
		if((time() - $_SESSION['last_seen']) > 21600 && $_SESSION['authenticated']){
			$_SESSION['notice'] = "You have been logged out due to inactivity.";
			$_SESSION['authenticated'] = false;
		}
		if($_SESSION['authenticated']!==true) {
			$show_mod_alert = true;
			$moderator = false;
			$administrator = false;
		}
	}
	*/
	
	$_SESSION['last_seen'] = time();
}

/* $hostaddr = gethostbyaddr($_SERVER['REMOTE_ADDR']);
if(
	(    stripos($hostaddr, ".ipredate.net")!==false
	  || stripos($hostaddr, "tor-exit")!==false
	  || IsTorExitPoint()
	 )
   && !$moderator && !$administrator && !$show_mod_alert) {
	die("Unforuntately certain VPN services have been blocked. Please try reconnecting without using one.");
}
*/

if(DEFCON<2&&!$administrator) { // DEFCON 1.
	header("Location: ".DOMAIN."lockdown.html");
	die();
}

function performBanCheck($table, $column, $value, $template) {
    global $link, $stealth_banned;
    $check = $link->db_exec("SELECT * FROM $table WHERE $column = %1", $value);
    
    if ($link->num_rows($check) == 0 && !defined("TEST_BAN")) return;
    $ban = $link->fetch_assoc($check);
    $link->free_result($check);
    
    if(!$stealth_banned) $stealth_banned = $ban['stealth'];
    if(defined("TEST_BAN")) {
        $ban = array(
            $column => $value,
            'expiry' => $_SERVER['REQUEST_TIME'] + 1,
            'reason' => 'Visiting the test page.',
            'stealth' => false
        );
    }
    
    if($ban['expiry'] != 0 && $ban['expiry'] <= $_SERVER['REQUEST_TIME'] && !defined('TEST_BAN')) {
        $link->db_exec("DELETE FROM $table where $column = %1", $value);
        return;
    }
    
    if($stealth_banned) return;
    
    require template($template);
    die();
}

performBanCheck('uid_bans', 'uid', $_SESSION['UID'], 'uid_ban');
performBanCheck('ip_bans', 'ip_address', $_SERVER['REMOTE_ADDR'], 'ip_ban');

if($_SESSION['last_posts_check'] < time() - 120 || !ENABLE_CACHING){
	// Panic mode check, this can prolly be done more efficient.
	$send = $link->db_exec("SELECT count(*) as count FROM replies WHERE deleted = 0 AND author=%1", $_SESSION['UID']);
	list($num_topics) = $link->fetch_row();
	$send = $link->db_exec("SELECT count(*) as count FROM topics WHERE deleted = 0 AND author=%1", $_SESSION['UID']);
	list($num_replies) = $link->fetch_row();
	
	define("USER_REPLIES", $num_replies+$num_topics);
	$_SESSION['user_replies'] = USER_REPLIES;
	if(USER_REPLIES >= POSTS_TO_DEFY_DEFCON_3 || $administrator || $moderator) {
		define("GOODREP", true);
	} else {
		define("GOODREP", false);
	}
	$_SESSION['user_rep'] = GOODREP;
	$_SESSION['last_posts_check'] = time();
}else{
	define("GOODREP", $_SESSION['user_rep']);
	define("USER_REPLIES", $_SESSION['user_replies']);
}

function error_handler($errno, $errstr, $errfile, $errline) {
	global $disable_errors;
	if($disable_errors) return;
	if($errno == E_NOTICE || $errno == E_USER_NOTICE) {
		return; // Just a notice. Feh.	
	}
	switch ($errno) {
	case E_WARNING:
	case E_USER_WARNING:
		$errors = "Warning";
		break;
	case E_ERROR:
	case E_USER_ERROR:
		$errors = "Fatal Error";
		break;
	case 2048: // Some non-important error.
		return;
		break;
	default:
		$errors = "Unknown Error";
		break;
	}
	$errfile = htmlentities($errfile);
	$errstr = htmlentities($errstr);
	$out = "<b>{$errors} ({$errno}):</b> {$errstr} in <b>{$errfile}</b> on line <b>{$errline}</b>";
	
	if(SITE_ROOT){
		$out = str_replace(SITE_ROOT, "", $out);
	}
	echo $out;
	ob_flush();
	die();
}

set_error_handler ("error_handler"); // Handle errors :D
error_reporting(E_ALL | E_STRICT);

if(function_exists("register_shutdown_function")) {
	ini_set("display_errors", 0);
	
	function shutdown_function() {
		$isError = false;
		if ($error = error_get_last()) {
			switch($error['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					error_handler($error['type'], $error['message'], $error['file'], $error['line']);
					break;
			}
		}
	}
	register_shutdown_function('shutdown_function');
}

// Start buffering stuff for the template.
ob_start(); 

/*
// Get visited topics from cookie.
$visited_cookie = explode('t', $_COOKIE['topic_visits']);
$visited_topics = array();
foreach($visited_cookie as $topic_info) {
	if(empty($topic_info)) {
		continue;
	}
	list($cur_topic_id, $num_replies) = explode('n', $topic_info);
	$visited_topics[$cur_topic_id] = $num_replies;
}
*/

$link->db_exec('SELECT topic, replies FROM read_topics WHERE uid = %1 AND time > UNIX_TIMESTAMP() - 2592000', $_SESSION['UID']);
while($row = $link->fetch_row()) {
	$visited_topics[$row[0]] = (int)$row[1];
}

// Get most recent actions to see if there's anything new.
$link->db_exec('SELECT feature, time FROM last_actions');
$last_actions = array();
while($row = $link->fetch_assoc()) {
	$last_actions[$row['feature']] = $row['time'];
}
// We hate magic quotes.
function stripslashes_from_array(&$array) {
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			stripslashes_from_array($array[$key]);
		} else {
		$array[$key] = stripslashes($value);
		}
	}
}

if ( get_magic_quotes_gpc ( ) ) {
	stripslashes_from_array($_GET);
	stripslashes_from_array($_POST);
}
if ( get_magic_quotes_runtime ( ) ) {
	set_magic_quotes_runtime ( 0 ); 
	}

// Check for unread PM's
// Don't bother if we're already reading a PM...
// $_SERVER['REQUEST_URI'] = /private_message/2464
if(strpos($_SERVER['REQUEST_URI'], 'private_message')===false && $_SESSION['UID']) {
	$pm_query = 'SELECT id FROM private_messages WHERE `read` = 0 AND (destination = %1';
	if(allowed("mod_pm")) {
		$pm_query .= ' OR destination = \'mods\')';
	} elseif($administrator) {
		$pm_query .= ' OR destination = \'mods\' OR destination = \'admins\')';
	} else {
		$pm_query .= ')';
	}

	$pm_link = $link->db_exec($pm_query, $_SESSION['UID']);
	$pm = $link->fetch_row($pm_link);
	$num_pms = $link->num_rows($pm_link);
	$_SESSION['pm_id'] = $pm[0];
	$_SESSION['num_pms'] = $num_pms;
}else{
	$num_pms = 0;
}

if($num_pms > 0) {
	$message_notice = 'You have <strong><a href="' . DOMAIN . 'private_message/' . intval($_SESSION['pm_id']) . '">1 new</a></strong> private message in your inbox';
	if($num_pms > 1) {
		$message_notice .= ', plus <strong>' . $num_pms - 1 . '</strong> more waiting!';
	} else {
		$message_notice .= '.';
	}
	$_SESSION['notice'] = $message_notice;
}

$gold_account = false;
$link->db_exec("SELECT expires FROM gold_accounts WHERE UID = %1", $_SESSION['UID']);
if($link->num_rows()>0){
	list($gold_account_expires) = $link->fetch_row();
	if($_SERVER['REQUEST_TIME'] > $gold_account_expires){
		$_SESSION['notice'] = "Your gold account has expired.";
		$link->db_exec("DELETE FROM gold_accounts WHERE UID = %1", $_SESSION['UID']);
	}else{
		$gold_account = true;
	}
}

if(allowed("manage_reports")){
	$link->db_exec("SELECT count(id) FROM reports WHERE handled = 0 AND uid != %1", $_SESSION['UID']);
	list($NUM_REPORTS) = $link->fetch_row();
	if($NUM_REPORTS > 0 && strpos($_SERVER['REQUEST_URI'], 'report')===false)
		$_SESSION['notice'] = "There " . ($NUM_REPORTS == 1 ? "is" : "are") ." <a href='" . DOMAIN . "reports'>" . $NUM_REPORTS . " unhandled report" . ($NUM_REPORTS == 1 ? "" : "s") . "</a>.";
}

$stmt = $link->db_exec('SELECT * FROM user_settings WHERE uid = %1', $_SESSION['UID']);
$user_settings = $link->fetch_assoc($stmt);
if(!$user_settings) $user_settings = dashboardDefaults();

$citation_check = $link->db_exec('SELECT COUNT(*) FROM citations WHERE uid = %1', $_SESSION['UID']);
list($new_citations) = $link->fetch_row($citation_check);

$link->db_exec('
SELECT count(*) FROM watchlists, read_topics, topics WHERE
watchlists.uid = %1 AND
read_topics.uid = %1 AND
watchlists.topic_id = read_topics.topic AND
watchlists.topic_id = topics.id AND
topics.replies > read_topics.replies
', $_SESSION['UID']);
list($new_watchlists) = $link->fetch_row();
if(!$new_watchlists) $new_watchlists=0;

// Update last_seen.
$link->db_exec('UPDATE users SET last_seen = UNIX_TIMESTAMP(), last_ip = %2 WHERE uid = %1 LIMIT 1', $_SESSION['UID'], $_SERVER['REMOTE_ADDR']);

if($_COOKIE['fp'] && $_COOKIE['fp'] != $_SESSION['fingerprint']) {
	$link->db_exec('INSERT INTO fingerprints (uid, ip, fingerprint, time) VALUES (%1, %2, %3, UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE time=UNIX_TIMESTAMP()', $_SESSION['UID'], $_SERVER['REMOTE_ADDR'], $_COOKIE['fp']);
	$_SESSION['fingerprint'] = $_COOKIE['fp'];
	// SELECT * FROM fingerprints WHERE fingerprint IN (SELECT fingerprint FROM fingerprints GROUP BY fingerprint HAVING COUNT(*) > 1) ORDER BY fingerprint, ip, uid
}

if(in_array($_SESSION['fingerprint'], array("1499995809", "2810661459", "3082178391", "1669373772", "1870961004", "968661064", "1969777502"))
    /*strpos($_SERVER["REMOTE_ADDR"], "174.79.161.") === 0*/) {
    die("<html><head></head><body>Your IP address is banned. This ban is not set to expire.</body></html>");
}
?>
