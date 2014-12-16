<?php
chdir("..");
require("includes/header.php");
$to = $_POST['url'];

if($to && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $to))
	add_error("Invalid URL");

if($to && check_token() && !$erred) {
	unset($_SESSION['csrf'][$csrf]);

	function gen_uuid($len=8) {
		// Seed + uniqueness
		$hex = md5("ahoasdha8s9dysaoda" . uniqid("", true));
		$pack = pack('H*', $hex);
		$uid = base64_encode($pack);        // max 22 chars
		$uid = preg_replace("#[^A-Z0-9]#", "", strtoupper($uid));    // uppercase only

		if ($len<4)
			$len=4;
		if ($len>128)
			$len=128;                       // prevent silliness, can remove

		while (strlen($uid)<$len)
			$uid = $uid . gen_uuid(22);     // append until length achieved

		return substr($uid, 0, $len);
	}

	function uid_exists($uid) {
		global $link;
		$result = $link->db_exec("SELECT 1 FROM shorturls WHERE id = %1 LIMIT 1", $uid);
		return ($link->num_rows() > 0); 
	}

	do {
		$uid = gen_uuid();
	}while(uid_exists($uid) != false);

	$link->db_exec("INSERT INTO shorturls (id, url, ip) VALUES (%1, %2, %3)", $uid, $to, $_SERVER['REMOTE_ADDR']);
}

$page_title = "MiniURL" . (($uid) ? " - Generated" : "");
print_errors();
if($uid) {
?>
<p>
The following URL:<br />
<b style="padding-left: 10px;"><?php echo htmlspecialchars($to); ?></b><br />
has been turned into:<br />
<b style="padding-left: 10px;"><?php echo DOMAIN; ?>link/<?php echo $uid; ?></b><br />
</p>
<hr />
<?php
}
?>
<p>
<form method="post">
<b>Enter a long url to minify:</b><br />
<input type="text" name="url" size="30" style="display: inline">
<input type="submit" name="submit" value="Make MiniURL!"  style="display: inline">
<?php csrf_token() ?>
</form>
</p>
<?php
require("includes/footer.php");