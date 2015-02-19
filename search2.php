<?php
require("includes/header.php");
force_id();

$additional_head = "<style>input,textarea, span { display: inline; }</style>";
$additional_head .= "<script type='text/javascript' src='".DOMAIN."/style/datepicker/js/datepicker.js'></script>";
$additional_head .= '<link rel="stylesheet" type="text/css" media="screen" href="'.DOMAIN.'/style/datepicker/css/datepicker.css" />';

$search_for = (string)$_GET['search_for'];
$topic_headline = (string)$_GET['topic_headline'];
$message_body = (string)$_GET['message_body'];
$author_name = (string)$_GET['author_name'];
$profile = ((allowed("open_profile")) ? $_GET['profile'] : null);
$IP_address = ((allowed("open_ip")) ? $_GET['IP_address'] : null);
$my_history = (bool)$_GET['my_history'];
$only_images = (bool)$_GET['only_images'];
$date_after = (string)$_GET['date_after'];
$date_before = (string)$_GET['date_before'];
$sort_by = (string)$_GET['sort_by'];
$ascending = (bool)$_GET['ascending'];
$hide_images = (bool)$_GET['hide_images'];

// Input sanitation
if(!in_array($search_for, array("topics", "replies", "both"))) $search_for = null;
if(strlen($topic_headline) > 100) $topic_headline = substr($topic_headline, 0, 100);
if(strlen($author_name) > 30) $author_name = substr($author_name, 0, 30);
if(!in_array($sort_by, array("age", "headline_relevance", "body_relevance", "combined_relevance"))) $sort_by = null;

$date_after_num = strtotime($date_after);
$date_before_num = strtotime($date_before);

$page = (int)$_GET['page'];
if(!$page) $page = 0;

$page_title = "Search";

$slmsql = "SELECT value FROM flood_control WHERE setting = 'search_disabled'";
$slmsend = send($slmsql);
$getcs = mysql_fetch_array($slmsend);
$sldm = $getcs['value'];

$uid = $_SESSION['UID'];
if($sldm){
	if(USER_REPLIES >= POSTS_TO_DEFY_SEARCH_DISABLED) {
		$goodrep = true;
	} else {
		$page_title = "Search disabled";
		echo 'Searching has been disabled for new users, please try again when you\'re a regular user.';
		exit(require('includes/footer.php'));
	}
}

$search = ($topic_headline || $message_body || $author_name || $date_after_num || $date_before_num || $profile || $IP_address) && $sort_by && $search_for;

if($search) {
	$page_title .= ", page " . ($page+1);
}

Console::log("Perform search?", $search);
?>
<form action="" method="get">
<table>
<tr><th>Label</th><th>Value</th></tr>
<tr><td class="minimal"><label for="search_for">Search for:</label></td><td><select name="search_for" id="search_for">
	<option value="both" <?php if($search_for=="both") echo "selected='selected' "; ?>>Topics & Replies</option>
	<option value="topics" <?php if($search_for=="topics") echo "selected='selected' "; ?>>Topics</option>
	<option value="replies" <?php if($search_for=="replies") echo "selected='selected' "; ?>>Replies</option>
</select></td></tr>
<tr><td class="minimal"><label for="topic_headline">Topic headline:</label></td><td><input type="text" name="topic_headline" id="topic_headline" id="topic_headline" size="124" value="<?php echo htmlspecialchars($topic_headline); ?>" /></td></tr>
<tr><td class="minimal"><label for="message_body">Message body:</label></td><td><input type="text" name="message_body" id="message_body" size="124" value="<?php echo htmlspecialchars($message_body); ?>" /></td></tr>
<tr><td class="minimal"><label for="author_name">Author:</label></td><td><input type="text" name="author_name" id="author_name" size="30" value="<?php echo htmlspecialchars($author_name); ?>" /> <span class="unimportant">(e.g. "username", "username !tripcode" or "!tripcode")</span></td></tr>
<?php if(allowed("open_profile")) { ?>
<tr><td class="minimal"><label for="profile">Profile:</label></td><td><input type="text" name="profile" id="profile" size="30" value="<?php echo htmlspecialchars($profile); ?>" /></td></tr>
<?php
}
if(allowed("open_ip")) { ?>
<tr><td class="minimal"><label for="IP_address">IP address:</label></td><td><input type="text" name="IP_address" id="IP_address" size="30" value="<?php echo htmlspecialchars($IP_address); ?>" /></td></tr>
<?php } ?>
<tr><td class="minimal"><label for="my_history">My history only:</label></td><td><input type="checkbox" name="my_history" id="my_history" <?php if($my_history) echo "checked='checked'"; ?> /></td></tr>
<tr><td class="minimal"><label for="only_images">With image only:</label></td><td><input type="checkbox" name="only_images" id="only_images" <?php if($only_images) echo "checked='checked'"; ?> /></td></tr>
<tr><td class="minimal"><label for="hide_images">Hide images from result:</label></td><td><input type="checkbox" name="hide_images" id="hide_images" <?php if($hide_images) echo "checked='checked'"; ?> /></td></tr>
<tr><td class="minimal"><label for="date_after">Posted after this date:</label></td><td><input type="text" size="10" maxlength="10" name="date_after" id="date_after" class="w8em format-m-d-y highlight-days-67 range-high-today" value="<?php echo htmlspecialchars($date_after); ?>" /></td></tr>
<tr><td class="minimal"><label for="date_before">Posted before this date:</label></td><td><input type="text" size="10" maxlength="10" name="date_before" id="date_before" class="w8em format-m-d-y highlight-days-67 range-high-today" value="<?php echo htmlspecialchars($date_before); ?>" /></td></tr>
<tr><td class="minimal"><label for="sort_by">Sort results by:</label></td><td><select name="sort_by" id="sort_by">
	<option value="age" <?php if($sort_by=="age") echo "selected='selected' "; ?>>Age</option>
	<option value="headline_relevance" <?php if($sort_by=="headline_relevance") echo "selected='selected' "; ?>>Headline relevance</option>
	<option value="body_relevance" <?php if($sort_by=="body_relevance") echo "selected='selected' "; ?>>Body relevance</option>
	<option value="combined_relevance" <?php if($sort_by=="combined_relevance") echo "selected='selected' "; ?>>Combined relevance</option>
</select> <input type="checkbox" name="ascending" id="ascending" <?php if($ascending) echo 'checked="checked"'; ?>> <label for="ascending">Ascending</label></td></tr>

</table>
<input type="submit" value="Search" /> 
<span class="unimportant">(You need to provide at least a topic headline, message body, author, or one of the dates. The topic headline and message body fields are in <a href="http://dev.mysql.com/doc/refman/5.1/en/fulltext-boolean.html">boolean search</a> mode. Dates are in MM/DD/YYYY.)</span>
</form>
<?php
if($search) {
	$link->db_exec("SELECT t2.image_viewer FROM users AS t1 LEFT JOIN user_settings AS t2 ON t1.uid = t2.uid WHERE t1.uid = %1", $_SESSION['UID']);
	list($user_image_viewer) = $link->fetch_row();
	
	if($search_for == "replies") {
		$search_replies = true;
		$search_topics = false;
	}else if($search_for == "topics") {
		$search_replies = false;
		$search_topics = true;
	}else{
		$search_replies = true;
		$search_topics = true;
	}
	
	echo "<hr />";
	
	$conditions = array();
	$conditions['deleted'] = "t1.deleted = 0";
	if($my_history)
		$conditions['my_history'] = "t1.author = '" . $link->escape($_SESSION['UID']) . "'";
	
	if((int)$date_after_num>0)
		$conditions['date_after'] = "t1.time > " . (int)$date_after_num;
	
	if((int)$date_before_num>0)
		$conditions['date_before'] = "t1.time < " . (int)$date_before_num;
		
	if($only_images)
		$conditions['only_images'] = "images.md5 IS NOT null";
		
	if($author_name && mb_ereg("^(.*?)(!!?[^!]+)?$", $author_name, $regs)) {
		$nick = trim($regs[1]);
		$trip = trim($regs[2]);
		
		if($nick)
			$conditions['author_nick'] = "t1.namefag = '" . $link->escape($nick) . "'";
			
		if($trip)
			$conditions['author_trip'] = "t1.tripfag = ' " . $link->escape($trip) . "'"; // Whitespace is intentional (old bug persistant in db)
	}
	
	if($profile)
		$conditions['profile'] = "t1.author = '" . $link->escape($profile) . "'";
		
	if($IP_address)
		$conditions['profile'] = "t1.author_ip = '" . $link->escape($IP_address) . "'";
	
	if($topic_headline) {
		$escaped_headline = $link->escape($topic_headline);
		$conditions['topic_headline'] = "MATCH(headline) AGAINST ('" . $escaped_headline . "' IN BOOLEAN MODE)";
	}else{
		$escaped_headline = "";
	}
		
	if($message_body)
		$conditions['message_body'] = "MATCH(t1.body) AGAINST ('" . $link->escape($message_body) . "' IN BOOLEAN MODE)";
	
	$conditions['secret_thread'] = "t1.secret_id IS NULL";
	
	$query_topics = "SELECT t1.id, NULL, thumb_width, thumb_height, file_name, img_external, thumb_external, t1.author, t1.namefag, t1.tripfag, 0, t1.time, t1.replies, t1.visits, t1.headline, t1.body FROM topics AS t1 LEFT OUTER JOIN images ON t1.id = images.topic_id WHERE " . implode(" AND ", $conditions);
	Console::log("query_topics", $query_topics);
		
	$conditions['deleted_topic'] = "t2.deleted = 0";
	
	$conditions['secret_thread'] = "t2.secret_id IS NULL";
	
	$query_replies = "SELECT t1.id, t1.parent_id, thumb_width, thumb_height, file_name, img_external, thumb_external, t1.author, t1.namefag, t1.tripfag, t1.poster_number, t1.time, t2.replies, t2.visits, t2.headline, t1.body FROM replies AS t1 LEFT OUTER JOIN images ON t1.id = images.reply_id, topics AS t2 WHERE t1.parent_id = t2.id AND " . implode(" AND ", $conditions);
	Console::log("query_replies", $query_replies);
	
	$query = "";
	
	if($search_topics && $search_replies) {
		$query = "(" . $query_topics . ") UNION ALL (" . $query_replies . ") ";
	}else if($search_topics && !$search_replies) {
		$query = $query_topics . " ";
	}else if($search_replies && !$search_topics) {
		$query = $query_replies . " ";
	}else {
		add_error("Unexplained behavior. Please report what you were doing, and include the following: " . base64_encode($query_topics . " ||| " . $query_replies), true);
	}
	
	if($ascending) {
		$order = " ASC";
	}else{
		$order = " DESC";
	}
	
	if($sort_by == "age") {
		$query .= "ORDER BY time" . $order;
	}else if($sort_by == "headline_relevance" && $conditions['topic_headline']) {
		$query .= "ORDER BY " . $conditions['topic_headline'] . $order;
	}else if($sort_by == "body_relevance" && $conditions['message_body']) {
		$query .= "ORDER BY " . $conditions['message_body'] . $order;
	}else if($sort_by == "combined_relevance") {
		$sorts = array();
		if($conditions['topic_headline'])
			$sorts[] = $conditions['topic_headline'] . $order;
			
		if($conditions['message_body'])
			$sorts[] = $conditions['message_body'] . $order;
		
		if(count($sorts)>0)
			$query .= "ORDER BY " . implode(", ", $sorts);
	}
	
	$query .= " LIMIT " . (100*$page) . ", 100";
	
	Console::log("query", $query);
	
	$query = $link->db_exec($query);
	$num_rows = $link->num_rows($query);
	
	$query_string = $_GET;
	unset($query_string["page"]);
	$query_string = DOMAIN . "search?" . http_build_query($query_string) . "&page=";
	
	$navigation_menu = "";
	if($page != 0) {
		$navigation_menu .= "<li><a href='" . $query_string . 0 . "'>First</a></li>";
	}
	
	if($page > 0) {
		$navigation_menu .= "<li><a href='" . $query_string . ($page-1) . "'>Previous</a></li>";
	}
	
	if($num_rows >= 100) {
		$navigation_menu .= "<li><a href='" . $query_string . ($page+1) . "'>Next</a></li>";
	}
	
	if($navigation_menu)
		$navigation_menu = "<ul class='menu'>" . $navigation_menu . "</ul>";
	
	if($navigation_menu)
		echo $navigation_menu;
	
	if ($num_rows > 0) {
		while(list($id, $parent_id, $thumb_width, $thumb_height, $image_name, $img_external, $thumb_external, $author, $name, $trip, $poster_number, $time, $replies, $visits, $headline, $body) = $link->fetch_row($query)) {
			echo "<h3>";
			if(!$name && !$trip) {
				echo "Anonymous <strong>" . number_to_letter($poster_number) . "</strong>";
			}else{
				if($name) {
					echo "<strong>" . htmlspecialchars(trim($name)) . "</strong>";
				}
				
				if($trip) {
					echo htmlspecialchars($trip);
				}
			}
			
			$additions = array();
			
			if($poster_number == 0) {
				$additions[] = "OP";
			}
			
			if($author == $_SESSION['UID']) {
				$additions[] = "you";
			}
			
			if(count($additions) > 0) 
				echo " (" . implode(", ", $additions) . ")";
			
			if($parent_id === null) {
				echo " posted the topic ";
			}else{
				echo " posted in ";
			}
			
			echo "<a href='" . DOMAIN . "topic/";
			if($parent_id !== null)
				echo $parent_id;
			else
				echo $id;
			
			if($parent_id !== null)
				echo "#reply_" . $id;
				
			echo "'>" . htmlspecialchars($headline) . "</a>";
			echo ' <strong><span class="help" title="' . format_date($time) . '">' . calculate_age($time) . ' ago</span></strong>';
			echo "</h3>";
			echo "<div class='body'>";
			
			if(!$hide_images) {
				if($img_external && $thumb_external) {
					if(MOBILE_MODE){
						$new_thumb_width = min($thumb_width, 100);
						$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
						$thumb_width = $new_thumb_width;
					}
					
					echo '<a href="'.htmlspecialchars($img_external) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'. htmlspecialchars($thumb_external) . '" alt="Externally hosted image" title="Externally hosted image" /></a>';
				}elseif ($image_name) {
					if(!$thumb_width&&!$thumb_height){
						$thumb_dimensions = getimagesize("thumbs/".$image_name);
						$thumb_width = $thumb_dimensions[0];
						$thumb_height = $thumb_dimensions[1];
						$link->update("images", array("thumb_width"=>$thumb_width, "thumb_height"=>$thumb_height), "file_name='".$link->escape($image_name)."'");
					}
					if(MOBILE_MODE){
						$new_thumb_width = min($thumb_width, 100);
						$thumb_height = round($thumb_height * ($new_thumb_width / $thumb_width));
						$thumb_width = $new_thumb_width;
					}
					if(file_exists("img/".$image_name)){
						if(!file_exists("thumbs/".$image_name)){
							thumbnail("img/" . $image_name, $image_name, end(explode(".", $image_name)));
						}
						echo '<a href="'.STATIC_DOMAIN.'img/' . htmlspecialchars($image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($image_name) . '" alt="" /></a>';
					}else{
						if(file_exists("thumbs/".$image_name)){
							echo '<a href="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($image_name) . '"' . (($user_image_viewer==1) ? 'class="thickbox"' : '') . (($user_image_viewer==2) ? 'target=_blank"' : '') . '><img width="'.$thumb_width.'" height="'.$thumb_height.'" src="'.STATIC_DOMAIN.'thumbs/' . htmlspecialchars($image_name) . '" alt="" /></a>';
						}else{
							echo '<a missing='.$image_name.' href="http://minichan.org/topic/6346"><img width="147" height="180" src="'.DOMAIN.'style/deleted.png" alt="Image went missing" /></a>';
						}
					}
				}
			}
			
			$snip = strlen($body) > 250;
			echo parse(substr($body, 0, 250));
			if($snip)
				echo "<br /><strong>...</strong>";
				
			echo "</div>";
		}
		
		if($navigation_menu)
			echo $navigation_menu;
	}else{
		echo "<strong>Nothing found.</strong>";
	}
}

require("includes/footer.php");