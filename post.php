<?php
require('includes/header.php');
$additional_head = '<script type="text/javascript" src="' . DOMAIN . 'javascript/polls.js?2"></script>';
force_id();

// DM FUCKERY START
// $_hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
// $_test = '.cn';
// $chinaCIDRS = array("182.240.0.0/13", "222.219.0.0/16", "222.220.0.0/15");
// $chinaCIDR = false;
// foreach($chinaCIDRS as $cidr) {
// 	if(ipCIDRCheck($_SERVER['REMOTE_ADDR'], $cidr)) $chinaCIDR = true;
// }
// if($chinaCIDR || (strpos($_SERVER['REMOTE_ADDR'], '65.49.') === 0 || ($_hostname && $_hostname != '.' && substr_compare($_hostname, $_test, -strlen($_test), strlen($_test)) === 0))) {
// 	$fh = fopen('dm.txt', 'a');
// 	fwrite($fh, date('c') . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . ' - ' . $_SESSION['UID'] . ' - ' . json_encode($_POST) . "\n");
// 	fclose($fh);
//     header('Location: http://www.irs.gov/Businesses/Small-Businesses-&-Self-Employed/Understanding-a-Federal-Tax-Lien');
//     die();
// }
// DM FUCKERY END

if($_GET['get_markup']) {
	if($_GET['reply']) {
		$stmt = $link->db_exec('SELECT body FROM replies WHERE id = %1 AND deleted = 0', $_GET['reply']);
	}elseif($_GET['topic']) {
		$stmt = $link->db_exec('SELECT headline, body, secret_id FROM topics WHERE id = %1 AND deleted = 0', $_GET['topic']);
	}else{
		die("Error: Topic/Reply not specified.");
	}
	
	if($link->num_rows($stmt) < 1) {
		$data = array("error"=>"There is no such topic/reply. It may have been deleted.");
	}else{
		$data = $link->fetch_assoc($stmt);
	}
	
	if(!allowed('minecraft') && $data['secret_id'])
		die('Error: Not found');
	
	die(json_encode($data));
}

$ONETHREAD = -1;
if(isMatt($_SERVER['REMOTE_ADDR'])) {
	$ONETHREAD = 36481;
}
if(isHindu($_SERVER['REMOTE_ADDR'])) {
	$ONETHREAD = 37709;
}

if ($ONETHREAD != -1) {
	if(ctype_digit($_GET['edit'])) {
		add_error("I can't let you do that.", true);
	}else if($_POST['form_sent']) {
		if($_GET['reply']) {
			// if($_GET['reply'] != $ONETHREAD) {
				// $_POST['body'] = preg_replace('%\r?\n\r?\n\(Originally posted in \[url=[^]]+\]/topic/[0-9]+\[/url\]\)\s*$%m', '', $_POST['body']);
				// $_POST['body'] = preg_replace_callback('/@([0-9,]+)/m', function($matches) {
					// return "@[url=" . DOMAIN . "topic/" . $_GET['reply'] . "#reply_" . filter_var($matches[1], FILTER_SANITIZE_NUMBER_INT) . "]" . $_GET['reply'] . "/" . $matches[1] . "[/url]";
				// }, $_POST['body']) . "\n\n(Originally posted in [url=" . DOMAIN . "topic/" . $_GET['reply'] . "]/topic/" . $_GET['reply'] . "[/url])";
				// $_GET['reply'] = $ONETHREAD;
			// }
		}else{
			$_GET['reply'] = $ONETHREAD;
			$headline = super_trim($_POST['headline']);
			$body     = super_trim($_POST['body']);
			
			if(strlen($headline) > 0)
				$_POST['body'] = "==Headline: " . $headline . "==\n" . $body;
		}
	}
}

if ($_GET['reply']) {

	$reply             = true;
	$onload_javascript = 'focusId(\'body\'); init();';
	
	if (!ctype_digit($_GET['reply'])) {
		add_error('Invalid topic ID.', true);
	}
	
	$stmt = $link->db_exec('SELECT headline, author, replies, locked, time, last_post, secret_id FROM topics WHERE id = %1 AND deleted = 0', $_GET['reply']);
	if ($link->num_rows($stmt) < 1 && !$administrator) {
		$page_title = 'Non-existent topic';
		add_error('There is no such topic. It may have been deleted.', true);
	}
	
	if(DEFCON<3 && !$administrator && !allowed("mod_hyperlink")) { // DEFCON 2.
		$_SESSION["notice"] = DEFCON_2_MESSAGE;
		header("Location: ".DOMAIN);
		die();
	}
	
	if(DEFCON<4 && !GOODREP && !$administrator) { // DEFCON 3.
		$_SESSION["notice"] = DEFCON_3_MESSAGE;
		header("Location: ".DOMAIN."topic/".$_GET['reply']);
		die();
	}
	
	list($replying_to, $topic_author, $topic_replies, $locked, $time, $last_reply_time, $secret_id) = $link->fetch_row($stmt);
	
	if(!allowed('minecraft') && $secret_id)
		add_error('There is no such topic. It may have been deleted.', true);
	
	if((time()-$last_reply_time)> NECRO_BUMP_TIME && !allowed("lock_topic") && $last_reply_time){
		add_error("No bumping of topics older than a week, please. If this is truly relevant to your interests, feel free to start a new continuation topic.", true);
	}
	
	if((time()-$time)>NECRO_BUMP_TIME && !$last_reply_time && !allowed("lock_topic")){
		add_error("No bumping of topics older than a week, please. If this is truly relevant to your interests, feel free to start a new continuation topic.", true);
	}
	
	
	update_activity('replying', $_GET['reply']);
	$page_title = 'New reply in topic: <a href="'.DOMAIN.'topic/' . $_GET['reply'] . '">' . htmlspecialchars($replying_to) . '</a>';
	
	$check_watchlist = $link->db_exec('SELECT 1 FROM watchlists WHERE uid = %1 AND topic_id = %2', $_SESSION['UID'], $_GET['reply']);
	if ($link->num_rows($check_watchlist) > 0) {
		$watching_topic = true;
	}
	$link->free_result($check_watchlist);
} else { // This is a topic.
	if(DEFCON<3 && !allowed("mod_hyperlink")) { // DEFCON 2.
		$_SESSION["notice"] = DEFCON_2_MESSAGE;
		header("Location: ".DOMAIN);
		die();
	}
	if(DEFCON<4 && !GOODREP && !$administrator) { // DEFCON 3.
		$_SESSION["notice"] = DEFCON_3_MESSAGE;
		header("Location: ".DOMAIN);
		die();
	}
	$reply             = false;
	$onload_javascript = 'focusId(\'headline\'); init();';
	update_activity('new_topic');
	
	$page_title = 'New topic';
	
	if (!empty($_POST['headline'])) {
		$page_title .= ': ' . htmlspecialchars($_POST['headline']);
	}
}

// If we're trying to edit and it's not disabled in the configuration.
if (ALLOW_EDIT && ctype_digit($_GET['edit'])) {
	$editing = true;
	
	if ($reply) {
		$fetch_edit = $link->db_exec('SELECT author, time, body, edit_mod, post_html, admin_hyperlink FROM replies WHERE id = %1', $_GET['edit']);
	} else {
		$fetch_edit = $link->db_exec('SELECT author, time, body, edit_mod, headline, post_html, admin_hyperlink FROM topics WHERE id = %1', $_GET['edit']);
	}
	
	if ($link->num_rows($fetch_edit) < 1) {
		add_error('There is no such post. It may have been deleted.', true);
	}
	
	if ($reply) {
		list($edit_data['author'], $edit_data['time'], $edit_data['body'], $edit_data['mod'], $post_html, $post_hyperlink) = $link->fetch_row($fetch_edit);
		$page_title = 'Editing <a href="'.DOMAIN.'topic/' . $_GET['reply'] . '#reply_' . $_GET['edit'] . '">reply</a> to topic: <a href="'.DOMAIN.'topic/' . $_GET['reply'] . '">' . htmlspecialchars($replying_to) . '</a>';
	} else {
		list($edit_data['author'], $edit_data['time'], $edit_data['body'], $edit_data['mod'], $edit_data['headline'], $post_html, $post_hyperlink) = $link->fetch_row($fetch_edit);
		$page_title = 'Editing topic';
	}
	
	if(in_array($edit_data['author'], $administrators) && !$administrator && $edit_data['author'] != $_SESSION['UID']) {
		add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
	}
	
	if(allowed("edit_post", $edit_data['author']) && !($administrator || $_SESSION['UID'] == $edit_data['author'] )) {
		add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
	}
	
	if ($edit_data['author'] === $_SESSION['UID']) {
		$edit_mod = 0;
		
		if (!allowed("edit_post")) {
			if (TIME_TO_EDIT != 0 && ($_SERVER['REQUEST_TIME'] - $edit_data['time'] > (TIME_TO_EDIT * ($gold_account+1)))) {
				add_error('You can no longer edit your post.', true);
			}
			if ($edit_data['mod']) {
				add_error('You can not edit a post that has been edited by a Moderator.');
			}
		}
	} else if (!$administrator && $post_html) {
		add_error('You are not allowed to edit that HTML post.', true);
	} else if (allowed("edit_post")) {
		$edit_mod = 1;
	} else {
		add_error('You are not allowed to edit that post.', true);
	}
	
	if (!$_POST['form_sent']) {
	// CSRF checking; No checking needed for the first edit form, needs to be checked on submit, below.
		$body = $edit_data['body'];
		if (!$reply) {
			$page_title .= ': <a href="'.DOMAIN.'topic/' . $_GET['edit'] . '">' . htmlspecialchars($edit_data['headline']) . '</a>';
			$headline = $edit_data['headline'];
		}
	} else if (!empty($_POST['headline'])) {
		$page_title .= ':  <a href="'.DOMAIN.'topic/' . $_GET['edit'] . '">' . htmlspecialchars($_POST['headline']) . '</a>';
	}
}

if(allowed("post_html")) {
	$post_html = (int)$_POST['post_html'];
}else{
	$post_html = 0;
}

if ($_POST['form_sent']) {
	// CSRF checking.
	check_token();
	if(function_exists("private_botDetect")) private_botDetect();
	$token = $_GET['token'];
	if(!is_array($_SESSION['post_salts']) || !in_array($token, $_SESSION['post_salts'])){
		add_error("Token error, please try again");
	}else{
		unset($_SESSION['post_salts'][$token]);
	}
	// Trimming.
	$headline = super_trim($_POST['headline']);
	$body     = super_trim($_POST['body']);
	$namefag  = ENABLE_NAMES ? ((isset($_POST['name'])) ? $_POST['name'] : '') : '';
	
	//$namefag = str_replace(array(chr(226), chr(128), chr(174)), '', $namefag);
	$namefag = str_replace(array(chr(226) . chr(128)), '', $namefag);
	
	$body = wrapUserFormatter($body);
	
	// Parse for mass quote tag ([quote]). I'm not sure about create_function, it seems kind of slow.
	$body = preg_replace_callback('/\[quote\](.+?)\[\/quote\]/s', create_function('$matches', 'return preg_replace(\'/.*[^\s]$/m\', \'> $0\', $matches[1]);'), $body);
	
	if(detect_spam($body)) add_error("Spam.");
	if(detect_spam($headline)) add_error("Spam.");
	if((USER_REPLIES < RECAPTCHA_MIN_POSTS) && (recaptcha_valid() !== true)) add_error("Please fill out the captcha.");
	
	if ($_POST['post']) {
		// Check for poorly made bots.
		if (!$editing && $_SERVER['REQUEST_TIME'] - $_POST['start_time'] < 3) {
			add_error('Wait a few seconds between starting to compose a post and actually submitting it.');
		}
		if (!empty($_POST['e-mail'])) {
			add_error('Bot detected.');
		}
		
		if(!((ALLOW_IMAGES || ALLOW_IMGUR) && (!empty($_FILES['image']['name']) || !empty($_POST['imageurl'])))) check_length($body, 'body', MIN_LENGTH_BODY, MAX_LENGTH_BODY);
		check_length($namefag, 'name', 0, 30);
		if (count(explode("\n", $body)) > MAX_LINES) {
			add_error('Your post has too many lines.');
		}

		if (ALLOW_IMAGES && !empty($_FILES['image']['name']) && !$editing) {
			$image_data = array();
			
			switch ($_FILES['image']['error']) {
				case UPLOAD_ERR_OK:
					$uploading = true;
					break;
				
				case UPLOAD_ERR_PARTIAL:
					add_error('The image was only partially uploaded.');
					break;
				
				case UPLOAD_ERR_INI_SIZE:
					add_error('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
					break;
				
				case UPLOAD_ERR_NO_FILE:
					add_error('No file was uploaded.');
					break;
				
				case UPLOAD_ERR_NO_TMP_DIR:
					add_error('Missing a temporary directory.');
					break;
				
				case UPLOAD_ERR_CANT_WRITE:
					add_error('Failed to write image to disk.');
					break;
				
				default:
					add_error('Unable to upload image.');
			}
			
			if ($uploading) {
				$uploading   = false; // Until we make our next checks.
				$valid_types = array
				(
					'jpg',
					'gif',
					'png'
				);
				
				$valid_name         = preg_match('/(.+)\.([a-z0-9]+)$/i', $_FILES['image']['name'], $match);
				$image_data['type'] = strtolower($match[2]);
				$image_data['md5']  = md5_file($_FILES['image']['tmp_name']);
				$image_data['name'] = str_replace(array
				(
					'.',
					'/',
					'<',
					'>',
					'"',
					"'",
					'%'
				), '', $match[1]);
				$image_data['name'] = substr(trim($image_data['name']), 0, 35);
				
				if ($image_data['type'] == 'jpeg') {
					$image_data['type'] = 'jpg';
				}
				
				// Uncomment the 3 commented lines to only change filename if a file doesn't exsist.
				// if(file_exists('img/' . $image_data['name'] . '.' . $image_data['type']))
				// {
				$image_data['name'] = $_SERVER['REQUEST_TIME'] . mt_rand(99, 999999);
				// }
				
				if ($valid_name === 0 || empty($image_data['name'])) {
					add_error('The image has an invalid file name.');
				} else if (!in_array($image_data['type'], $valid_types)) {
					add_error('Only <strong>GIF</strong>, <strong>JPEG</strong> and <strong>PNG</strong> files are allowed.');
				} else if ($_FILES['image']['size'] > MAX_IMAGE_SIZE) {
					add_error('Uploaded images can be no greater than ' . round(MAX_IMAGE_SIZE / 1048576, 2) . ' MB. You can instead upload to imgur and fill in the url (or use the [upload] button to upload directly from the site).');
				} else {
					$uploading          = true;
					$image_data['name'] = $image_data['name'] . '.' . $image_data['type'];
				}
			}
		}else if(ALLOW_IMGUR && !$editing && $_POST['imageurl']) {
			$external = img_url_data($_POST['imageurl']);
			if($external == null) {
				add_error("This is not a valid/allowed image URL.");
			}
		}

		// DM FUCKERY START
		// $_hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		// $_test = '.cn';
		// if(ALLOW_IMAGES && !$editing && (strpos($_SERVER['REMOTE_ADDR'], '65.49.') === 0 || ($_hostname && $_hostname != '.' && substr_compare($_hostname, $_test, -strlen($_test), strlen($_test)) === 0))) {
			// $uploading = false;
			// $image_data = null;
			// $external = img_url_data("http://i.imgur.com/3OjEQGv.jpg");
		// }
		// DM FUCKERY END
		
		// Set the author (internal use only).
		$author = $_SESSION['UID'];
		$flag = getFlagForIP($_SERVER["REMOTE_ADDR"]);
		
		
		
		if(allowed("mod_hyperlink")) {
			$admin_hyperlink = (int)$_POST['post_hyperlink'];
		}else{
			$admin_hyperlink = 0;
		}
		
		// If this is a reply.
		if ($reply) {
			if (!$editing) {

			// Check if topic is locked, if so deny posting, except for admins and mods.
			if($locked != 0 && !allowed("lock_topic")) {
				$_SESSION['notice']="You can not reply in a locked topic.";
				header("Location: ".DOMAIN.""); exit("");
			}
				// Lurk more.
				if ($_SERVER['REQUEST_TIME'] - $_SESSION['first_seen'] < REQUIRED_LURK_TIME_REPLY) {
					add_error('Lurk for at least ' . REQUIRED_LURK_TIME_REPLY . ' seconds before posting your first reply.');
				}
				
				// Flood control.
				$too_early = $_SERVER['REQUEST_TIME'] - FLOOD_CONTROL_REPLY;
				$stmt = $link->db_exec('SELECT count(id) FROM replies WHERE author_ip = %1 AND time > %2', $_SERVER['REMOTE_ADDR'], $too_early);
				list($replies_too_early) = $link->fetch_row($stmt);
				if(!allowed("mod_hyperlink")) {
//				if(!$administrator) {
					if ($replies_too_early > 0) {
						add_error('Wait at least ' . FLOOD_CONTROL_REPLY . ' seconds between each reply. ');
					}
				}
				
				// Get letter, if applicable.
				/* CHANGE HERE FOR MODS TOO */
				if(false && (allowed("mod_hyperlink")) && $_POST['number'] != "-" && $_POST['number'] && $_POST['number'] != "+1"){
					$fake_num = letter_to_number($_POST['number']);
					if($fake_num == -1){
						add_error("You entered an invalid Anonymous <b>#</b>");
					}
					$poster_number = $fake_num;
				}elseif ($_SESSION['UID'] == $topic_author && $_POST['number'] != "+1") {
					$poster_number = 0;
				} else { // We are not the topic author.
					$stmt = $link->db_exec('SELECT poster_number FROM replies WHERE parent_id = %1 AND author = %2 LIMIT 1', $_GET['reply'], $author);
					list($poster_number) = $link->fetch_row($stmt);
					
					// If the user has not already replied to this thread, get a new letter.
					if (empty($poster_number)) {
						// We need to lock the table to prevent others from selecting the same letter.
						//$unlock_table = true;
						//$link->db_exec('LOCK TABLE replies WRITE');
						
						$stmt = $link->db_exec('SELECT poster_number FROM replies WHERE parent_id = %1 ORDER BY poster_number DESC LIMIT 1', $_GET['reply']);
						list($last_number) = $link->fetch_row($stmt);
						
						
						if (empty($last_number)) {
							$poster_number = 1;
						} else {
							$poster_number = $last_number + 1;
						}
					}
				}

				if ($namefag != '') {
				// Uncomment next line for letter assignments.
				// Pro's: Less letter, as first anon in namefag threads becomes anon b
				// Con's: Harder to spot when a namefag becomes anon, and not used anywhere else.
				// I prefer it the TC/4chan/normal board way ~~~sim
				//	$poster_number = 0;
					$namefag       = nameAndTripcode($namefag);
				}
				
				$insert["author"] = $author;
				$insert["author_ip"] = $_SERVER['REMOTE_ADDR'];
				$insert["poster_number"] = $poster_number;
				$insert["parent_id"] = $_GET['reply'];
				$insert["body"] = $body;
				$insert["time"] = "UNIX_TIMESTAMP()";
				$insert["namefag"] = $namefag[0];
				$insert["tripfag"] = $namefag[1];
				$insert["stealth_ban"] = $stealth_banned;
				$insert["admin_hyperlink"] = $admin_hyperlink;
				$insert["post_html"] = $post_html;
				$insert["flag"] = $flag;
				if(!$erred){
					$stmt = $link->insert("replies", $insert);
					$inserted_id = $link->insert_id();
					
					if(!$namefag[0] && !$namefag[1]) {
						$ircname = "Anonymous";
					}else{
						$ircname = chr(2) . trim($namefag[0]) . chr(2) . $namefag[1];
					}
					
					if(!$stealth_banned)
						log_irc("Reply in \"" . $replying_to . "\" by " . $ircname . " - " . create_link(DOMAIN . "topic/" . $_GET['reply'] . "#reply_" . $inserted_id) . " - " . trim(snippet($body, 150, false, false)));
				}
				unset($insert);
				
				if(!$stealth_banned) {
					// Notify cited posters.
					preg_match_all('/@([0-9,]+)/m', $body, $matches);
					// Needs to filter before array_unique in case of @11, @1,1 etc.
					$citations = filter_var_array($matches[0], FILTER_SANITIZE_NUMBER_INT);
					$citations = array_unique($citations);
					$citations = array_slice($citations, 0, 10);
					foreach ($citations as $citation) {
						// Note that nothing is inserted unless the SELECT returns a row.
						$link->db_exec('INSERT INTO citations (reply, topic, uid) SELECT %1, %2, `author` FROM replies WHERE replies.id = %3 AND replies.deleted = 0 AND replies.parent_id = %4', $inserted_id, $_GET['reply'], (int) $citation, $_GET['reply']);
					}
					
					if($inserted_id && count($citations) > 0) {
						// Push notifications
						$link->db_exec('SELECT id, author FROM replies WHERE deleted = 0 AND parent_id = %1 AND id IN(' . implode(",", $citations) . ')', $_GET['reply']);
						$citations_sent = array();
						while($row = $link->fetch_row()) {
							if(in_array($row[1], $citations_sent)) continue;
							add_notification('citation', $row[1], $inserted_id, array('topic'=>$_GET['reply'], 'reply'=>$inserted_id, 'snippet'=>snippet($body, 90), 'headline'=>$replying_to), $_GET['reply']);
							$citations_sent[] = $row[1];
						}
						
						
					}
					
					if($inserted_id) {
						$link->db_exec('SELECT uid FROM watchlists WHERE topic_id = %1', $_GET['reply']);
						$watchlists_sent = array();
						while($row = $link->fetch_row()) {
							if(is_array($citations_sent) && in_array($row[0], $citations_sent)) continue;
							
							if(in_array($row[0], $watchlists_sent)) continue;
							add_notification('watchlist', $row[0], $inserted_id, array('topic'=>$_GET['reply'], 'reply'=>$inserted_id, 'snippet'=>snippet($body, 90), 'headline'=>$replying_to), $_GET['reply']);
							$watchlists_sent[] = $row[0];
						}
						
						unset($watchlists_sent);
						unset($citations_sent);
					}
				}
				
				
				$congratulation = 'Reply posted.';
			} else { // Editing.
				$update["body"]	= $body;
				$update["edit_mod"] = $edit_mod;
				$update["edit_time"] = "UNIX_TIMESTAMP()";
				$update["id"] = $_GET['edit'];
				$update["admin_hyperlink"] = $admin_hyperlink;
				$update["post_html"] = $post_html;
				if(!$erred)
					$stmt = $link->update("replies", $update, "id=".$link->escape((int)$_GET['edit']));
				$congratulation = 'Reply edited.';
				if($edit_mod){
					$link->db_exec('SELECT topics.id FROM replies, topics WHERE replies.parent_id = topics.id AND replies.id = %1', $_GET['edit']);
					list($replying_to) = $link->fetch_row();
					log_mod("edit_reply", $replying_to . "#reply_" . $_GET['edit']);
				}
			}
		} else { // Or a topic.
			check_length($headline, 'headline', MIN_LENGTH_HEADLINE, MAX_LENGTH_HEADLINE);

			// Check headline for non-ascii and other crap characters, for both new topics and topic editing.
//			if (preg_match('/[^\\x20-\\x7E]/', $headline)) {
//			if (preg_match('/[\x{488}\x{489}\x{2B0}-\x{3BF}]/u', $headline )) {
			if (preg_match('/[^\x20-\x7E\xA0-\xFF\x{100}-\x{2AF}\x{0400}-\x{0482}\x{48A}-\x{4FF}}\x{0E00}-\x{0E7F}\x{0F00}-\x{0FFF}\x{1E00}-\x{1EFF}\x{1F00}-\x{1FFF}\x{2010}-\x{2027}\x{2030}-\x{205E}\x{2100}-\x{23FF}\x{2500}-\x{25FF}\x{2600}-\x{27FF}\x{2900}-\x{2BFF}\x{2E80}-\x{9FFF}]/u', $headline )) {
			add_error('Please do not use any special characters in the headline.');
			}
	
			if (!$editing) {
			// Do we need to lurk some more?
				if ($_SERVER['REQUEST_TIME'] - $_SESSION['first_seen'] < REQUIRED_LURK_TIME_TOPIC) {
					add_error('Lurk for at least ' . REQUIRED_LURK_TIME_TOPIC . ' seconds before posting your first topic.');
				}
				
				// Flood control.
				
				if(!allowed("mod_hyperlink")){
					$too_early = $_SERVER['REQUEST_TIME'] - FLOOD_CONTROL_TOPIC;
					$stmt      = $link->db_exec('SELECT 1 FROM topics WHERE author_ip = %1 AND time > %2', $_SERVER['REMOTE_ADDR'], $too_early);
					
					if ($link->num_rows($stmt) > 0) {
						add_error('Wait at least ' . FLOOD_CONTROL_TOPIC . ' seconds before creating another topic. ');
					}
				}
				
				if ($namefag != '') {
					$namefag = nameAndTripcode($namefag);
				}
				
				if(isset($_POST['sticky']) && allowed("stick_topic")) {
					$sticky = 1;
				} else {
					$sticky = 0;
				}

				if(isset($_POST['locked']) && allowed("lock_topic")) {
					$locked = 1;
				} else {
					$locked = 0;
				}
				
				$polls_enabled = false;
				if($_POST['enable_poll'] && ENABLE_POLLS) {
					$poll_options = (array)$_POST['polloptions'];
					$poll_options = array_slice($poll_options, 0, 25);
					if(trim($poll_options[0]) && trim($poll_options[1])) {
						$polls_enabled = true;
					}
				}
				
				// Prepare our query.
				if(!$erred) {
					$link->db_exec('
						INSERT INTO topics (author, author_ip, headline, body, last_post, time, namefag, tripfag, sticky, locked, poll, admin_hyperlink, post_html, flag, stealth_ban)
						VALUES (%1, %2, %3, %4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), %5, %6, %7, %8, %9, %10, %11, %12, %13)',
						$author, $_SERVER['REMOTE_ADDR'], $headline, $body, $namefag[0], $namefag[1], $sticky, $locked, (int)$polls_enabled, $admin_hyperlink, $post_html, $flag, $stealth_banned
					);
					
					$inserted_id = $link->insert_id();
					
					if($polls_enabled) {
						$sql = "INSERT INTO poll_options (topic_id, content) VALUES ";
					
						foreach($poll_options as $num=>$poll) {
							$poll = trim($poll);
							if(!$poll) continue;
							$poll = substr(trim($poll), 0, 255);
							
							$sql_options[] = "(" . $inserted_id . ", \"" . $link->escape($poll) . "\")";
						}
						$sql .= implode(", ", $sql_options);
						
						$link->db_exec($sql);
					}
					
					if(!$namefag[0] && !$namefag[1]) {
						$ircname = "Anonymous";
					}else{
						$ircname = chr(2) . trim($namefag[0]) . chr(2) . $namefag[1];
					}
					
					if(!$stealth_banned) {
						topic_notification($headline, $ircname, snippet($body), DOMAIN . 'topic/' . $inserted_id);
					
						log_irc("Topic \"" . $headline . "\" by " . $ircname . " - " . create_link(DOMAIN . "topic/" . $inserted_id) . " - " . trim(snippet($body, 150, false, false)));
					}
					
					//$link->db_exec("INSERT INTO poll_options
					
					$congratulation = 'Topic created.';
				}
			} else { // Editing.
				if(!$erred)
					$stmt = $link->db_exec('UPDATE topics SET headline = %1, body = %2, edit_mod = %3, edit_time = UNIX_TIMESTAMP(), admin_hyperlink = %4, post_html = %5 WHERE id = %6', $headline, $body, $edit_mod, $admin_hyperlink, $post_html, $_GET['edit']);
					
				if($edit_mod){
					log_mod("edit_topic", $_GET['edit']);	
				}
				$congratulation = 'Topic edited.';
			}
		}
		
		// If all is well, execute!
		if (!$erred) {
			
			if ($unlock_table) {
				$link->db_exec('UNLOCK TABLE');
			}
			
			
			
			$rawnamefag = (isset($_POST['name'])) ? super_trim($_POST['name']) : '';
			if(!$editing) {
			$link->db_exec("UPDATE users SET namefag = %1 WHERE uid = %2", $rawnamefag, $_SESSION['UID']);
			}
			// Sort out what topic we're affecting and where to go next. Needs to be trimmed.
			if (!$editing) {
				if ($reply) {
					$target_topic = $_GET['edit'];
					$redir_loc    = $_GET['reply'] . '#reply_' . $inserted_id;
				} else { // If topic.
					$target_topic = $inserted_id;
					$redir_loc    = $inserted_id;
				}
			} else { // If editing.
				if ($reply) {
					$target_topic = $_GET['reply'];
					$redir_loc    = $_GET['reply'] . '#reply_' . $_GET['edit'];
				} else // If topic.
					{
					$target_topic = $_GET['edit'];
					$redir_loc    = $_GET['edit'];
				}
			}
			
			// We did it!
			if (!$editing) {
				setcookie('last_bump', time(), $_SERVER['REQUEST_TIME'] + 315569260, '/');
				if ($reply) {
					// Update last bump.
					$link->db_exec("UPDATE last_actions SET time = UNIX_TIMESTAMP() WHERE feature = 'last_bump'");
					if(!$stealth_banned) {
						$link->db_exec('UPDATE topics SET replies = replies + 1, last_post = UNIX_TIMESTAMP() WHERE id = %1', $_GET['reply']);
					}
				} else { // If topic.
					// Do not change the time() below to REQUEST_TIME. The script execution may have taken a second.
					setcookie('last_topic', time(), $_SERVER['REQUEST_TIME'] + 315569260, '/');
					// Update last topic and last bump, for people using the "date created" order option in the dashboard.
					$link->db_exec("UPDATE last_actions SET time = UNIX_TIMESTAMP() WHERE feature = 'last_topic' OR feature = 'last_bump'");
				}
			}
			
			// Take care of the upload.
			if ($uploading) {
				// Check if this image is already on the server.
				$duplicate_check = $link->db_exec('SELECT file_name, thumb_width, thumb_height FROM images WHERE md5 = %1', $image_data['md5']);
				list($previous_image, $thumb_width, $thumb_height) = $link->fetch_row($duplicate_check);				
				
				// If the file has been uploaded before this, just link the old version.
				if ($previous_image && file_exists("img/" . $previous_image) && file_exists("thumbs/" . $previous_image)) {
					$image_data['name'] = $previous_image;
				} else { // Otherwise, keep the new image and make a thumbnail.
					
					if($previous_image)
						$image_data['name'] = $previous_image;
					
					if(file_exists("img/" . $previous_image) && $previous_image) @unlink("img/" . $previous_image);
					if(file_exists("thumbs/" . $previous_image) && $previous_image) @unlink("thumbs/" . $previous_image);
					
					list($thumb_width, $thumb_height)= thumbnail($_FILES['image']['tmp_name'], $image_data['name'], $image_data['type']);
					move_uploaded_file($_FILES['image']['tmp_name'], 'img/' . $image_data['name']);
				}
				
				if ($reply) {
					$insert_image = $link->db_exec('INSERT INTO images (file_name, md5, reply_id, thumb_width, thumb_height) VALUES (%1, %2, %3, %4, %5)', $image_data['name'], $image_data['md5'], $inserted_id, $thumb_width, $thumb_height);
				} else {
					$insert_image = $link->db_exec('INSERT INTO images (file_name, md5, topic_id, thumb_width, thumb_height) VALUES (%1, %2, %3, %4, %5)', $image_data['name'], $image_data['md5'], $inserted_id, $thumb_width, $thumb_height);
				}
			}elseif($external) {
				$insert_image = $link->db_exec('INSERT INTO images (file_name, md5, '.(($reply) ? 'reply_id' : 'topic_id') . ', thumb_width, thumb_height, img_external, thumb_external) VALUES (%1, %2, %3, %4, %5, %6, %7)', "", "", $inserted_id, $external["thumb_width"], $external["thumb_height"], $external["imgurl"], $external["thumburl"]);
			}
			
			// Add topic to watchlist if desired.
			if ($_POST['watch_topic'] && !$watching_topic) {
				$add_watchlist = $link->db_exec('INSERT INTO watchlists (uid, topic_id) VALUES (%1, %2)', $_SESSION['UID'], $target_topic);
			}
			
			// Set the congratulation notice and redirect to affected topic or reply.
			redirect($congratulation, 'topic/' . $redir_loc);
			
			$stmt->close();
		} else { // If we got an error, insert this into failed postings.
			if ($unlock_table) {
				$link->db_exec('UNLOCK TABLE');
			}
			
			if ($reply) {
				$add_fail = $link->db_exec('INSERT INTO failed_postings (time, uid, reason, body) VALUES (UNIX_TIMESTAMP(), %1, %2, %3)', $_SESSION['UID'], serialize($errors), substr($body, 0, MAX_LENGTH_BODY));
			} else {
				$add_fail = $link->db_exec('INSERT INTO failed_postings (time, uid, reason, body, headline) VALUES (UNIX_TIMESTAMP(), %1, %2, %3, %4)', $_SESSION['UID'], serialize($errors), substr($body, 0, MAX_LENGTH_BODY), substr($headline, 0, MAX_LENGTH_HEADLINE));
			}
		}
	}
}
print_errors();
// For the bot check.
$start_time = $_SERVER['REQUEST_TIME'];
if (ctype_digit($_POST['start_time'])) {
	$start_time = $_POST['start_time'];
}

echo '<div>';

// Check if OP.
if ($reply && !$editing) {
	echo '<p>You <strong>are';
	if ($_SESSION['UID'] !== $topic_author) {
		echo ' not';
	}
	echo '</strong> recognized as the original poster of this topic.</p>';
}

// Print deadline for edit submission.
if ($editing && TIME_TO_EDIT != 0 && !allowed("edit_post")) {
	echo '<p>You have <strong>' . calculate_age($_SERVER['REQUEST_TIME'], $edit_data['time'] + (TIME_TO_EDIT*($gold_account+1))) . '</strong> left to finish editing this post.</p>';
}

// Print preview.
if ($_POST['preview'] && !empty($body)) {
	if($post_html) {
		$preview_body = $body;
	}else{
		$preview_body = parse($body);
	}
	$preview_body = preg_replace('/^@([0-9,]+|OP)/m', '<span class="unimportant"><a href="#">$0</a></span>', $preview_body);
	echo '<h3 id="preview">Preview</h3><div class="body standalone">' . $preview_body . '</div>';
}

// Check if any new replies have been posted since we last viewed the topic.
if ($reply && isset($visited_topics[$_GET['reply']]) && $visited_topics[$_GET['reply']] < $topic_replies) {
	$new_replies = $topic_replies - $visited_topics[$_GET['reply']];
	echo '<p><a href="'.DOMAIN.'topic/' . $_GET['reply'] . '#new"><strong>' . $new_replies . '</strong> new repl' . ($new_replies == 1 ? 'y</a> has' : 'ies</a> have') . ' been posted in this topic since you last checked!</p>';
}

// Print the main form.
$randVar = md5(mt_rand());
$_SESSION['post_salts'][$randVar] = true;
$url = $_SERVER['REQUEST_URI'];
$url = preg_replace('/[a-f0-9]{32}/', '', $url);
$url = trim($url, "\\/");
$url = '/' . $url . "/" . $randVar;
$url = htmlspecialchars($url);
?>
<form action="<?php echo $url; ?>" method="post"<?php if(ALLOW_IMAGES) echo ' enctype="multipart/form-data"' ?>>
	<?php
    	csrf_token();
	?>
	<div class="noscreen">
		<input name="form_sent" type="hidden" value="1" />
		<input name="e-mail" type="hidden" />
		<input name="start_time" type="hidden" value="<?php echo $start_time ?>" />
		</div>
		<?php if( ! $reply): ?>
		<div class="row">
			<label for="headline">Headline</label> <script type="text/javascript"> printCharactersRemaining('headline_remaining_characters', <?php echo MAX_LENGTH_HEADLINE; ?>); </script>.
			<input id="headline" name="headline" tabindex="1" type="text" size="<?php echo (MOBILE_MODE) ? 30 : 124 ?>" maxlength="<?php echo MAX_LENGTH_HEADLINE; ?>" onkeydown="updateCharactersRemaining('headline', 'headline_remaining_characters', <?php echo MAX_LENGTH_HEADLINE; ?>);" onkeyup="updateCharactersRemaining('headline', 'headline_remaining_characters', <?php echo MAX_LENGTH_HEADLINE; ?>);" value="<?php if($_POST['form_sent'] || $editing) echo htmlspecialchars($headline) ?>">
		</div>
		<?php endif; ?>
		<?php
			if(!$editing && ENABLE_NAMES) {
				if($_POST['form_sent']) {
					$setName = $_POST["name"];
				} else {
					// Grab our stored namefag.
					$link->db_exec("SELECT namefag FROM users WHERE uid = %1", $_SESSION['UID']);
					$result = $link->fetch_assoc();
					$setName = $result["namefag"];
				}
				echo '<div class="row"><label for="name">Name</label>:<input id="name" name="name" placeholder="name #tripcode" type="text" size="30" maxlength="30" tabindex="2" value="' . htmlspecialchars($setName) . '">';
				echo '</div>';
			}
		/* CHANGE HERE FOR MODS TOO */
		if(false && $reply && !$editing && allowed("mod_hyperlink")){
		?>
<!--		<div class="row"><label for="name">Anonymous <b>#</b></label>:
			<input id="number" name="number" type="text" size="5" maxlength="3" tabindex="2" value="-">
		</div>
-->
	<?php } ?>

		<div class="row">
			<label for="body" class="noscreen">Post body</label> 
<?php			
// Give mobile users a slightly smaller text editing field.
if(!MOBILE_MODE){ ?>
<textarea name="body" cols="120" rows="18" tabindex="2" id="body" class="markup_editor">
<?php } else { ?>
<textarea name="body" cols="120" rows="8" tabindex="2" id="body" class="markup_editor">
<?php }

		// If we've had an error or are previewing, print the submitted text.
		if ($_POST['form_sent'] || $editing) {
			echo sanitize_for_textarea($body);
		}  else if (isset($_GET['quote_topic']) || ctype_digit($_GET['quote_reply'])) { // Otherwise, fetch any text we may be quoting.
			// Fetch the topic.
			if (isset($_GET['quote_topic'])) {
				$stmt = $link->db_exec('SELECT body FROM topics WHERE id = %1', $_GET['reply']);
			} else { // ...or a reply.
			echo '@' . number_format($_GET['quote_reply']) . "\n\n";
			$stmt = $link->db_exec('SELECT body FROM replies WHERE id = %1', $_GET['quote_reply']);
			}

			// Execute it.
			list($quoted_text) = $link->fetch_row($stmt);

			// Snip citations from quote.
			$quoted_text = trim(preg_replace('/^@([0-9,]+|OP)/m', '', $quoted_text));

			// Prefix newlines with >.
			$quoted_text = preg_replace('/^/m', '> ', $quoted_text);
			echo sanitize_for_textarea($quoted_text) . "\n\n";
		}

		// If we're just citing, print the citation.
		else if (ctype_digit($_GET['cite'])) {
			echo '@' . number_format($_GET['cite']) . "\n\n";
		}
		echo '</textarea>';

		if (ALLOW_IMAGES && !$editing) {
			echo '<label for="image" class="noscreen">Image</label> <input type="file" name="image" id="image" />';
		}
		if (ALLOW_IMGUR && !$editing) {
			echo '<label for="imageurl">Imgur URL:</label> <input class="inline" type="text" name="imageurl" id="imageurl" size="21" placeholder="http://i.imgur.com/rcrlO.jpg" /> <a href="javascript:document.getElementById(\'imgurupload\').click()" id="uploader">[upload]</a><br />';
		}
		if(!$editing && !$reply) {
		?>
        <input type='hidden' id='enable_poll' name='enable_poll' value='1' />
        <p><a id='poll_toggle' onclick="showPoll(this);">Poll options</a></p>
        <table id="topic_poll">
        <?php
			if(isset($_POST['polloptions'])) {
				$polls = $_POST['polloptions'];
			}else{ 
				$polls[0] = "";
				$polls[1] = "";
			}
			
			$odd = true;
			foreach($polls as $num=>$poll) {
				if($num>9) break;
				echo "<tr class='odd'><td class='minimal'><label for='poll_option_" . ($num+1) . "'>Poll option #" . ($num+1) . "</label></td><td><input id='poll_option_1' type='text' class='pollInput' name='polloptions[]' value='" . htmlspecialchars($poll) . "' /></td></tr>";
			}
		?>
        </table>
        <?php
		}
		
		if(USER_REPLIES < RECAPTCHA_MIN_POSTS) {
			echo "<br /><b>You are required to fill in a captcha for your first " . RECAPTCHA_MIN_POSTS . " posts. That's only " . (RECAPTCHA_MIN_POSTS - USER_REPLIES) . " more! We apologize, but this helps stop spam.</b>";
			recaptcha_inline();
		}
		?>
			<p>Please familiarise yourself with the <a href="<?php echo DOMAIN; ?>rules" target="_blank">rules</a> and <a href="<?php echo DOMAIN; ?>markup_syntax" target="_blank">markup syntax</a> before posting, also keep in mind you can minify URLs using <a target="_blank" href="<?php echo DOMAIN; ?>link">MiniURL</a> and generate image macros using <a target="_blank" href="<?php echo DOMAIN; ?>macro">MiniMacro</a>.</p>
		</div>
		<?php
		if (!$watching_topic) {
			echo '<input type="checkbox" name="watch_topic" id="watch_topic" class="inline"';
			if ($_POST['watch_topic']) {
			echo ' checked="checked"';
			}
			echo ' /><label for="watch_topic" class="inline"> Watch</label><br />';
		}
		if(!$reply && !$editing) {
			if(allowed("stick_topic")) {
				echo '<input type="checkbox" name="sticky" value="1" class="inline"/><label for="sticky" class="inline"> Stick</label><br />';
			}
			if(allowed("lock_topic")) {
				echo '<input type="checkbox" name="locked" value="1" class="inline"/><label for="locked" class="inline"> Lock</label><br />';
			}
        }
		if(allowed("post_html")) {
			echo '<input class="inline" type="checkbox" name="post_html" id="post_html"' . ($post_html ? ' checked="checked"' : '') . ' value="1" /> <label for="post_html">HTML</label><br />';
		}
		if(allowed("mod_hyperlink")) {
 			echo '<input class="inline" type="checkbox" name="post_hyperlink" id="post_hyperlink"' . (((!isset($post_hyperlink) && $administrator) || ((int)$post_hyperlink)===1) ? ' checked="checked"' : '') . ' value="1" /> <label for="post_hyperlink">Hyperlink</label><br />';
		}
		?>
		<div class="row">
		<input type="submit" name="preview" tabindex="3" value="Preview" class="inline"<?php if(ALLOW_IMAGES) echo ' onclick="document.getElementById(\'image\').value=\'\'"' ?> /> 
			<input type="submit" name="post" tabindex="4" value="<?php echo ($editing) ? 'Update' : 'Post' ?>" class="inline">
		</div>
	</form>
    
    <?php
    	if((ALLOW_IMAGES || ALLOW_IMGUR) && !$editing)
			echo '<input style="visibility: hidden; width: 0px; height:0px" type="file" id="imgurupload" onchange="uploadImage(this.files[0])">';
	?>
</div>
<?php
// If citing, fetch and display the reply in question.
if (ctype_digit($_GET['cite'])) {
	$stmt = $link->db_exec('SELECT body, poster_number, namefag, tripfag FROM replies WHERE id = %1', $_GET['cite']);
	list($cited_text, $poster_number, $r_namefag, $r_tripfag) = $link->fetch_row();
	if (!empty($cited_text)) {
		$cited_text = parse($cited_text);
		// Linkify citations within the text.
		preg_match_all('/^@([0-9,]+)/m', $cited_text, $matches);
		foreach ($matches[0] as $formatted_id) {
			$pure_id = str_replace(array(
				'@',
				','
			), '', $formatted_id);
			$cited_text = str_replace($formatted_id, '<a href="'.DOMAIN.'topic/' . $_GET['reply'] . '#reply_' . $pure_id . '" class="unimportant">' . $formatted_id . '</a>', $cited_text);
		}
		// And now, let us parse it!
		if($r_namefag!='' || $r_tripfag!=''){
			if($r_namefag!=''){ 
				$replyTo = htmlspecialchars($r_namefag);
				if($_tripfag!='') $replyTo .= " ";
			}else{
				$replyTo = '';
			}
			if($r_tripfag!=''){
				$replyTo .= '<a style="font-weight: 400">'.htmlspecialchars($r_tripfag).'</a>';
			}
		}else{
			$replyTo = '<a style="font-weight: 400">Anonymous</a> ' . number_to_letter($poster_number);
		}
		echo '<h3 id="replying_to">Replying to ' . $replyTo . '&hellip;</h3> <div class="body standalone">' . $cited_text . '</div>';
	}
} else if ($reply && !isset($_GET['quote_topic']) && !isset($_GET['quote_reply']) && !$editing) { // If we're not citing or quoting, display the original post.
	$stmt = $link->db_exec('SELECT body FROM topics WHERE id = %1', $_GET['reply']);
	$results = $link->fetch_assoc($stmt);
	$cited_text = $results["body"];
	echo '<h3 id="replying_to">Original post</h3> <div class="body standalone">' . parse($cited_text) . '</div>';
}
require('includes/footer.php');
?>
