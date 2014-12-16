<?php
require("includes/header.php");
if(!allowed("watch")) add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
$additional_head = "<script src='" . DOMAIN . "javascript/watcher.js'></script>";
if(isset($_GET['JSON'])){
	$link->db_exec("SELECT id, time, author, namefag, tripfag, author_ip, replies, headline, body, locked FROM topics WHERE deleted = 0 ORDER BY id DESC LIMIT 10");
	while($data = $link->fetch_assoc()){
		$data["body"] = snippet($data["body"]);
		$name = (($data["namefag"]!="") ? "<b>".$data["namefag"]."</b> " : "") . $data["tripfag"];
		if(!trim($name)) $name = "Anonymous";
		$data["name"] = $name;
		unset($data["namefag"]);
		unset($data["tripfag"]);
		$data["time"] = calculate_age($data["time"]);

        if(!allowed("open_ip")) $data["author_ip"] = "*censored*";
        if(!allowed("open_profile")) $data["author"] = "*censored*";

		$out["topics"][] = $data;
	}
	
	$link->db_exec("SELECT replies.id, replies.parent_id, replies.author, replies.namefag, replies.tripfag, replies.author_ip, replies.time, replies.body, topics.headline FROM replies INNER JOIN topics ON replies.parent_id = topics.id WHERE replies.deleted = 0 ORDER BY replies.time DESC LIMIT 10");
	while($data = $link->fetch_assoc()){
		$data["body"] = snippet($data["body"]);
		$name = (($data["namefag"]!="") ? "<b>".$data["namefag"]."</b> " : "") . $data["tripfag"];
		if(!trim($name)) $name = "Anonymous";
		$data["name"] = $name;
		unset($data["namefag"]);
		unset($data["tripfag"]);
		$data["time"] = calculate_age($data["time"]);

        if(!allowed("open_ip")) $data["author_ip"] = "*censored*";
        if(!allowed("open_profile")) $data["author"] = "*censored*";

		$out["replies"][] = $data;
	}
	
	$users = $link->db_exec("SELECT users.uid, users.first_seen, users.last_seen, users.ip_address FROM users LEFT OUTER JOIN uid_bans ON uid_bans.uid = users.uid WHERE isnull(uid_bans.who) ORDER BY users.first_seen DESC LIMIT 15");
	while($data = $link->fetch_assoc($users)){
		if($data["uid"] == $_SESSION['UID']) continue;
		
		$send = $link->db_exec("SELECT count(id) as count FROM replies WHERE deleted = 0 AND author=%1", $data['uid']);
		list($num_topics) = $link->fetch_row();
		$send = $link->db_exec("SELECT count(id) as count FROM topics WHERE deleted = 0 AND author=%1", $data['uid']);
		list($num_replies) = $link->fetch_row();
		
		$data["posts"] = $num_replies + $num_topics;
		
		$data["first_seen"] = calculate_age($data["first_seen"]);
		$data["last_seen"] = calculate_age($data["last_seen"]);

        if(!allowed("open_ip")) $data["ip_address"] = "*censored*";
        if(!allowed("open_profile")) $data["uid"] = "*censored*";

		$out["uids"][] = $data;
	}
	
	echo json_encode($out);
	die();
}
?>
<h2>Latest 10 replies:</h2>
<table>
<thead>
<tr>
<th>Topic</th>
<th>Snippet</th>
<th>Author</th>
<th>IP</th>
<th class="minimal">Time created ▼</th>
</tr>
</thead>
<tbody id="replies">

</tbody>
</table><br>

<noscript><div style='position: absolute; background: black; left:0px; top:0px; width:100%; height:100%; font-size: 36px; color:red'>ENABLE JAVASCRIPT</div></noscript>
<h2>Latest 10 topics:</h2>
<table>
<thead>
<tr>
<th>Headline</th>
<th>Snippet</th>
<th>Author</th>
<th>IP</th>
<th>Replies</th>
<th class="minimal">Time created ▼</th>
</tr>
</thead>
<tbody id="topics">

</tbody>
</table><br>

<h2>Latest 15 nonbanned UIDs:</h2>
<table>
<thead>
<tr>
<th>Profile</th>
<th>Posts</th>
<th>IP</th>
<th>Last seen</th>
<th class="minimal">First seen ▼</th>
</tr>
</thead>
<tbody id="uids">

</tbody>
</table>
<?php
require("includes/footer.php");
?>
