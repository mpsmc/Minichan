<?php
require('includes/header.php');
force_id();

// Take the action.
switch($_GET['action']) {
	
	case 'disable_reporting':
		if(!allowed('manage_reporting')){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if(isset($_POST['id'])){
			check_token();
			$link->update("users", array("report_allowed"=>0), "uid=\"" . $link->escape($_POST['id']) . "\"");
			log_mod("disable_reporting", $_POST['id']);
			redirect("User may no longer file reports", 'profile/'.$_POST['id']);
		}
	break;
	
	case 'mass_delete':
		if(!allowed('delete')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if((int)$_GET['id'] && is_array($_GET['reply_id'])) {
			check_token();
			
			$replies_to_delete = array();
			
			foreach($_GET['reply_id'] as $reply_id) {
				if((int)$reply_id < 1) continue; // Ints only!
				$replies_to_delete[] = "id=".$reply_id;
				deleteImage("reply", $reply_id);
				log_mod("delete_reply", $reply_id);
			}
			$replies_to_delete = implode(" OR ", $replies_to_delete);
			
			delete_citation($_GET['reply_id']);
			
			$link->db_exec("UPDATE replies SET deleted=1 WHERE parent_id = %1 AND (".$replies_to_delete.")", $_GET['id']);
			$deletes = $link->affected_rows();
			$link->db_exec('UPDATE topics SET replies = replies - %1 WHERE id = %2', $deletes, $_GET['id']);
			redirect("Mass delete complete!", "topic/".$_GET['id']);
		}
	break;
	
	case 'enable_reporting':
		if(!allowed('manage_reporting')){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if(isset($_POST['id'])){
			check_token();
			$link->update("users", array("report_allowed"=>1), "uid=\"" . $link->escape($_POST['id']) . "\"");
			log_mod("enable_reporting", $_POST['id']);
			redirect("User may file reports once again", 'profile/'.$_POST['id']);
		}
	break;
	
	case 'cast_vote':
			if( ! ctype_digit($_GET['id'])) {
				add_error('Invalid topic ID.', true);
			}
			
			if(!GOODREP) {
				add_error('You are not allowed to vote yet.', true);
			}
		   
			$id = $_GET['id'];
			$page_title = 'Cast vote';
		   
			if(ctype_digit($_POST['option_id'])) {
					check_token();
				   
					$check_votes = $link->db_exec('SELECT count(uid) FROM poll_votes WHERE (ip = %1 OR uid = %2) AND parent_id = %3', $_SERVER['REMOTE_ADDR'], $_SESSION['UID'], $id);
					list($num_rows) = $link->fetch_row();
					if($num_rows == 0) {
							$record = $link->db_exec('INSERT INTO poll_votes (uid, ip, parent_id, option_id) VALUES (%1, %2, %3, %4)', $_SESSION['UID'], $_SERVER['REMOTE_ADDR'], $id, $_POST['option_id']);
							$increment_option = $link->db_exec('UPDATE poll_options SET votes = votes + 1 WHERE id = %1', $_POST['option_id']);
					}
					else {
							add_error('You\'ve already voted in this poll.', true);
					}
					redirect('Thanks for voting.', 'topic/' . $id);
			}
			else {
					redirect('You need to select an option.', 'topic/' . $id);
			}
               
        break;
	
	// Normal actions.
	case 'watch_topic':
	
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Watch topic';
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$check_watchlist = $link->db_exec('SELECT count(uid) FROM watchlists WHERE uid = %1 AND topic_id = %2', $_SESSION['UID'], $id);
			list($num_watchlists) = $link->fetch_row($check_watchlist);
			if($num_watchlists == 0) {
				$link->db_exec('INSERT INTO watchlists (uid, topic_id) VALUES (%1, %2)', $_SESSION['UID'], $_POST['id']);
			}
			redirect('Topic added to your watchlist.');
		}
		
	break;
	
	case 'set_image':
		if(!allowed('set_image')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		$type = $_GET['type'];
		$id = $_GET['id'];
		$topic = $_GET['topic'];
		
		if(isset($_POST['url'])) {
			$url = $_POST['url'];
			check_token();
			
			$external = img_url_data($url);
			if($external == null) {
				add_error("Could not upload image");
			}else{
				log_mod("set_image", $type."_".$id);
				deleteImage($type, $id);
				
				$insert_image = $link->db_exec('INSERT INTO images (file_name, md5, '.(($type=="reply") ? 'reply_id' : 'topic_id') . ', thumb_width, thumb_height, img_external, thumb_external) VALUES (%1, %2, %3, %4, %5, %6, %7)', "", "", $id, $external["thumb_width"], $external["thumb_height"], $external["imgurl"], $external["thumburl"]);
			}
			if($type=='reply'){
				$extra = "#reply_".$id;
			}else{ $extra=""; }
			redirect('Image set.', 'topic/'.$topic.$extra);
		}
	case 'delete_image':
		if(!allowed('delete_image')){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		$type = $_GET['type'];
		$id = $_GET['id'];
		$topic = $_GET['topic'];
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			log_mod("delete_image", $type."_".$id);
			deleteImage($type, $id);
			if($type=='reply'){
				$extra = "#reply_".$id;
			}else{ $extra=""; }
			redirect('Image deleted.', 'topic/'.$topic.$extra);
		}
	
	break;
	
		// Priveleged actions.
	case 'delete_page':
	
		if(!allowed('manage_cms')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Delete page';
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			log_mod("delete_page", $id);
			$file_uid_ban = $link->db_exec('DELETE FROM pages WHERE id = %1', $id);
			redirect('Page deleted.', 'CMS');
		}
		
	break;
	case 'unban_uid':
	
		if(!allowed('ban_uid')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! id_exists($_GET['id'])) {
			add_error('There is no such user.', true);
		}
		
		if(allowed("ban_uid", $_GET['id']) && !$administrator){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Unban poster ' . $id;
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			remove_id_ban($id);
			log_mod("unban_uid", $id);
			redirect('User ID unbanned.');
		}
		
	break;
		
	case 'unban_ip':
	
		if(!allowed('ban_ip')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! filter_var($_GET['id'], FILTER_VALIDATE_IP)) {
			add_error('That is not a valid IP address.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Unban IP address ' . $id;
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			remove_ip_ban($id);
			log_mod("unban_ip", $id);
			if(isset($_GET['uids'])) {
				$stmt = $link->db_exec('SELECT uid FROM users WHERE ip_address = %1', $id);
				while(list($uid) = $link->fetch_row($stmt)) {
					remove_id_ban($uid);
					log_mod("unban_uid", $uid);
				}
				$_SESSION['notice'] = 'IP address & UIDs unbanned.';
			}else{
				$_SESSION['notice'] = 'IP address unbanned.';
			}
			exit(header("Location: ".DOMAIN."IP_address/".$id.""));
		}
		
	break;
	
	case 'toggle_search_mode':
	
		if(!allowed('manage_search')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		$id = $_GET['id'];
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			log_mod("toggle_search_mode", $id);
			$link->db_exec("UPDATE flood_control SET value = %1 WHERE setting = 'search_disabled' LIMIT 1;", $id);
			$page_title = 'Toggle search mode';
			$_SESSION['notice'] = 'Search mode set to '.($id ? "disabled" : "enabled").'.';
			exit(header("Location: ".DOMAIN."stuff"));
		}

	break;
	
	case 'set_time':
		if(!allowed('set_time')) add_error(MESSAGE_PAGE_ACCESS_DENIED, true);

		$id = $_GET['id'];
		if(isset($_POST['time'])) {
			check_token();
			log_mod("set_time", $id);
			$time = $_POST['time'];
			if(!is_numeric($time)) $time = strtotime($time);
			$link->db_exec("UPDATE topics set last_post = %1 WHERE id = %2", $time, $id);
			$_SESSION['notice'] = 'Time updated.';
			exit(header("Location: ".DOMAIN."topic/".$id));
		}
	break;

	case 'stick_topic':
	
		if(!allowed('stick_topic')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$sql = "UPDATE `topics` SET `sticky` = '1' WHERE `topics`.`id` =".$id." LIMIT 1 ;";
			send($sql);
			log_mod("stick_topic", $id);
			$_SESSION['notice'] = 'Topic is now sticky.';
			exit(header("Location: ".DOMAIN."topic/" . $id));
		}
		
	break;
	
		case 'unstick_topic':
	
		if(!allowed('stick_topic')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$sql = "UPDATE `topics` SET `sticky` = '0' WHERE `topics`.`id` =".$id." LIMIT 1 ;";
			send($sql);
			log_mod("unstick_topic", $id);
			$_SESSION['notice'] = 'Topic is no longer sticky.';
			exit(header("Location: ".DOMAIN."topic/" . $id));
		}
		
	break;
	
	case 'lock_topic':
	
		if(!allowed('lock_topic')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$sql = "UPDATE `topics` SET `locked` = '1' WHERE `topics`.`id` =".$id." LIMIT 1 ;";
			send($sql);
			log_mod("lock_topic", $id);
			$_SESSION['notice'] = 'Topic has been locked.';
			exit(header("Location: ".DOMAIN."topic/" . $id));
		}
		
	break;
	
		case 'unlock_topic':
	
		if(!allowed('lock_topic')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$sql = "UPDATE `topics` SET `locked` = '0' WHERE `topics`.`id` =".$id." LIMIT 1 ;";
			send($sql);
			log_mod("unlock_topic", $id);
			$_SESSION['notice'] = 'Topic has been unlocked.';
			exit(header("Location: ".DOMAIN."topic/" . $id));
		}
		
	break;
	
	case 'undelete_topic':
		if(!allowed('undelete')){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Undelete topic';
		
		$link->db_exec("SELECT author, deleted, stealth_ban FROM topics WHERE id = %1", $id);
		list($author_id, $deleted, $stealth_ban) = $link->fetch_row();
		if(!$deleted && !$stealth_ban){
			add_error("Topic isn't deleted.", true);
		}
		
		$link->update("topics", array("deleted"=>0, "stealth_ban"=>0), "id=".$link->escape($id));
		log_mod("undelete_topic", $id);
		redirect('Topic undeleted and pulled out of the archive.', '');
		
	break;
	
	case 'stealth_delete_topic':

		if(!allowed('delete') || !allowed('undelete')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Stealth Delete topic';
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			
			$link->update("topics", array("stealth_ban"=>1), "id=".$link->escape($id));
			log_mod("stealth_delete_topic", $id);
			redirect('Topic stealthbanned.');
		}
		
	break;
	
	case 'delete_topic':
	
		if(!allowed('delete')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid topic ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Delete topic';
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			
			$link->db_exec("SELECT author, deleted FROM topics WHERE id = %1", $id);
			list($author_id, $deleted) = $link->fetch_row();
			if($deleted){
				add_error("Topic is already deleted.", true);
			}
			if(in_array($author_id, $administrators) && !$administrator) {
				add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
			}
			if(!$administrator && allowed("delete", $author_id) && $author_id != $_SESSION['UID']) {
				add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
			}
			
			// Dump the image.
			deleteImage("topic", $id);
			
			// Get replies.
			$fetchImg = $link->db_exec("SELECT author, id FROM replies WHERE parent_id = %1", $id);
			while($row = $link->fetch_row($fetchImg)){
				deleteImage("reply", $row[1]);
			}
			// Move record to user's trash.
			//$archive_topic = $link->db_exec('INSERT INTO trash (uid, headline, body, time) SELECT topics.author, topics.headline, topics.body, UNIX_TIMESTAMP() FROM topics WHERE topics.id = %1;', $id);
		
			// And delete it from the main table.
			//$delete_topic = $link->db_exec('DELETE FROM topics WHERE id = %1', $id);
			$link->update("topics", array("deleted"=>1), "id=".$link->escape($id));
			log_mod("delete_topic", $id);
			redirect('Topic archived and deleted.', '');
		}
		
	break;
		
	case 'undelete_reply':
		if(!allowed('undelete')){
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid reply ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Undelete reply';
		
		$link->db_exec("SELECT author, deleted, stealth_ban FROM replies WHERE id = %1", $id);
		list($author_id, $deleted, $stealth_ban) = $link->fetch_row();
		if(!$deleted && !$stealth_ban){
			add_error("Reply isn't deleted.", true);
		}
		
		$link->update("replies", array("deleted"=>0, "stealth_ban"=>0), "id=".$link->escape($id));
		log_mod("undelete_reply", $id);
		
		$fetch_parent = $link->db_exec('SELECT parent_id FROM replies WHERE id = %1', $id);
		list($parent_id) = $link->fetch_row();
		
		$link->db_exec('UPDATE topics SET replies = replies + 1 WHERE id = %1', $parent_id);
		
		redirect('Reply undeleted and pulled out of the archive.', 'topic/' . $parent_id . "#reply_" . $id);
		
	break;
	
	case 'stealth_delete_reply':

		if(!allowed('delete') || !allowed('undelete')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid reply ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Stealth Delete reply';
		
		$fetch_parent = $link->db_exec('SELECT parent_id FROM replies WHERE id = %1', $id);
		list($parent_id) = $link->fetch_row();
		
		if(!$parent_id) {
			add_error('No such reply.', true);
		}
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			
			$link->update("replies", array("stealth_ban"=>1), "id=".$link->escape($id));
			$link->db_exec('UPDATE topics SET replies = replies - 1 WHERE id = %1', $parent_id);
			log_mod("stealth_delete_reply", $id);
			redirect('Reply stealthbanned.');
		}
		
	break;
		
	case 'delete_reply':
	
		if(!allowed('delete')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		if( ! ctype_digit($_GET['id'])) {
			add_error('Invalid reply ID.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Delete reply';
	
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			
			$link->db_exec("SELECT author FROM replies WHERE id = %1", $id);
			list($author_id) = $link->fetch_row();
			
			if(in_array($author_id, $administrators) && !$administrator) {
				add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
			}
			
			if(!$administrator && allowed("delete", $author_id) && $author_id != $_SESSION['UID']) {
				add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
			}
			
			$fetch_parent = $link->db_exec('SELECT parent_id FROM replies WHERE id = %1', $id);
			list($parent_id) = $link->fetch_row();

			if( ! $parent_id) {
				add_error('No such reply.', true);
			} else {
				deleteImage("reply", $id);
			}
			
			// Move record to user's trash.
			//$archive_reply = $link->db_exec('INSERT INTO trash (uid, body, time) SELECT replies.author, replies.body, UNIX_TIMESTAMP() FROM replies WHERE replies.id = %1;', $id);
					
			// And delete it from the main table.
			//$delete_reply = $link->db_exec('DELETE FROM replies WHERE id = %1', $id);
			$link->update("replies", array("deleted"=>1), "id=".$link->escape($id));		
			// Reduce the parent's reply count.
			$link->db_exec('UPDATE topics SET replies = replies - 1 WHERE id = %1', $parent_id);
			log_mod("delete_reply", $id);
			redirect('Reply archived and deleted.');
		}
		
	break;
	
	case 'delete_ip_ids':
	
		if(!allowed('nuke_uids')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		
		if( ! filter_var($_GET['id'], FILTER_VALIDATE_IP)) {
			add_error('That is not a valid IP address.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Delete IDs assigned to <a href="'.DOMAIN.'IP_address/' . $id . '">' . $id . '</a>';
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			$link->db_exec('SELECT uid FROM users WHERE ip_address = %1', $id);
			
			while(list($uid) = $link->fetch_row()){
				if(in_array($uid, $administrators)) {
					add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
				}
			}
			$delete_ids = $link->db_exec('DELETE users, user_settings FROM users LEFT OUTER JOIN user_settings ON users.uid=user_settings.uid WHERE users.ip_address = %1', $id);
			$_SESSION['notice'] = 'IDs deleted.';
			log_mod("delete_ip_ids", $id);
			exit(header("Location: ".DOMAIN."IP_address/".$id.""));
		}
		
	break;
	
	case 'nuke_id':
		add_error("No longer implemented", true);
		if( !allowed('nuke_posts')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if(in_array($_GET['id'], $administrators)) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! id_exists($_GET['id'])) {
			add_error('There is no such user.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Nuke all posts by <a href="'.DOMAIN.'profile/' . $id . '">' . $id . '</a>';
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			// Delete replies.
			$fetch_parents = $link->db_exec('SELECT parent_id, id FROM replies WHERE author = %1', $id);
			$victim_parents = array();
			while(list($parent_id, $reply_id) = $link->fetch_row($fetch_parents)) {
				$victim_parents[] = $parent_id;
				deleteImage("reply", $reply_id);
			}
			
			// Dump images which belong to topics.
			$fetch_topics = $link->db_exec('SELECT id FROM topics WHERE author = %1', $id);
			
			while(list($topic_id) = $link->fetch_row($fetch_topics)) {
				deleteImage("topic", $topic_id);
				$fetch_replies = $link->db_exec('SELECT author, id FROM replies WHERE parent_id = %1', $topic_id);
				while(list($author_id, $reply_id) = $link->fetch_row($fetch_replies)) {
					deleteImage("reply", $reply_id);
				}
				$link->db_exec("DELETE FROM replies WHERE parent_id = %1", $topic_id);
			}
			
			$delete_replies = $link->db_exec('DELETE FROM replies WHERE author = %1', $id);
			
			foreach($victim_parents as $parent_id) {
				$decrement = $link->db_exec('UPDATE topics SET replies = replies - 1 WHERE id = %1', $parent_id);
			}
			
			// Delete topics.
			$delete_topics = $link->db_exec('DELETE FROM topics WHERE author = %1', $id);
			
			log_mod("nuke_id", $id);
			$_SESSION['notice'] = 'All topics and replies by ' . $id . ' have been deleted.';
			exit(header("Location: ".DOMAIN."profile/".$id.""));
		}
		
	break;
	
	case 'nuke_ip':
		add_error("No longer implemented", true);
		if( !allowed('nuke_posts')) {
			add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
		}
		
		if( ! filter_var($_GET['id'], FILTER_VALIDATE_IP)) {
			add_error('That is not a valid IP address.', true);
		}
		
		$id = $_GET['id'];
		$page_title = 'Nuke all posts by <a href="'.DOMAIN.'IP_address/' . $id . '">' . $id . '</a>';
		
		if(isset($_POST['id'])) {
			// CSRF checking.
			check_token();
			
			$link->db_exec("SELECT uid FROM users WHERE ip_address = %1", $id);
			while(list($uid)=$link->fetch_row()){
				if(in_array($uid, $administrators)) {
					add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
				}
			}
			
			
			// Delete replies.
			$fetch_parents = $link->db_exec('SELECT parent_id, id FROM replies WHERE author_ip = %1', $id);
			$victim_parents = array();
			while(list($parent_id, $reply_id) = $link->fetch_row($fetch_parents)) {
				$victim_parents[] = $parent_id;
				deleteImage("reply", $reply_id);
			}
			
			// Nuke the images and delete replies.
			$fetch_topics = $link->db_exec('SELECT id FROM topics WHERE author_ip = %1', $id);
			while(list($topic_id) = $link->fetch_row($fetch_topics)) {
				deleteImage("topic", $topic_id);
				$fetch_replies = $link->db_exec('SELECT author, id FROM replies WHERE parent_id = %1', $topic_id);
				while(list($author_id, $reply_id) = $link->fetch_row($fetch_replies)) {
					deleteImage("reply", $reply_id);
				}
				$link->db_exec("DELETE FROM replies WHERE parent_id = %1", $topic_id);
			}

			$delete_replies = $link->db_exec('DELETE FROM replies WHERE author_ip = %1', $id);
			foreach($victim_parents as $parent_id) {
				$decrement = $link->db_exec('UPDATE topics SET replies = replies - 1 WHERE id = %1 AND replies > 0', $parent_id);
			}
			
			// Delete topics.
			$delete_topics = $link->db_exec('DELETE FROM topics WHERE author_ip = %1', $id);
			$_SESSION['notice'] = 'All topics and replies by ' . $id . ' have been deleted.';
			log_mod("nuke_ip", $id);
			exit(header("Location: ".DOMAIN."IP_address/".$id.""));
		}
	break;
	
	default:
		add_error('No valid action specified.', true);	
}

echo '<p>This is a fallback system. If you\'re seeing this it means you have javascript disabled, or using a browser without proper javascript support.</p> <form action="" method="post">';
csrf_token();
echo '<div> <input type="hidden" name="id" value="' . $_GET['id'] . '" /> <input type="submit" value="Do it" /> </div>';

require('includes/footer.php');
?>
