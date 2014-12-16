<?php
require('includes/header.php');
force_id();

if (!ctype_digit($_GET['p']) || $_GET['p'] < 2) {
	$current_page = 1;
	$page_title = 'Private messages';
} else {
	$current_page = $_GET['p'];
	$page_title = 'Private messages, page #' . number_format($current_page);
}

$pm_query = 'SELECT `id`, `source`, `destination`, `contents`, `time`, `expiration` FROM `private_messages` WHERE `destination` = %1';
if(allowed("mod_pm")) {
	$pm_query .= ' OR `destination` = \'mods\'';
}
if($administrator) {
	$pm_query .= ' OR `destination` = \'admins\'';
}
$pm_query .= ' AND (`expiration` > UNIX_TIMESTAMP() OR `expiration` = 0) ORDER BY `time` DESC LIMIT %2, %3';
$stmt = $link->db_exec($pm_query, $_SESSION['UID'], ($current_page - 1) * 50, 50);

$pms = new table();
$columns = array(	'Message',
					'From',
					'Age ?',
					'Expiration',
					'Delete' );
$pms->define_columns($columns, 'Headline');
$pms->add_td_class('Headline', 'topic_headline');

while(list($id, $source, $destination, $contents, $time, $expiration) = $link->fetch_row($stmt)) {
	$values = array();
	$values[] = '<a href="' . DOMAIN . 'private_message/' . $id . '">' . snippet($contents) . '</a>';
	if($source == 'admins') {
		$values[] = 'admin';
	} elseif($source == 'mods') {
		$values[] = 'mod';
	} else {
		if(allowed("open_profile")) {
			$values[] = '<a href="'.DOMAIN.'profile/'.$source.'">'.modname($source).'</a>';
		} else {
			$values[] = modname($source);
		}
	}
	$values[] = calculate_age($time, time()) . ' ago';
	if($expiration == 0) {
		$values[] = 'Never';
	} else {
		$values[] = calculate_age(time(), $expiration) . ' left';
	}
	if(($expiration > time() && $expiration != 0) || $administrator || (allowed("mod_pm") && ($source == 'mods' || $destination == 'mods'))) {
		$values[] = '<a href="' . DOMAIN . 'delete_message/' . $id . '">Delete</a>';
	} else {
		$values[] = '';
	}
	$pms->row($values);
}

?>
<ul class="menu">
	<li><a href="<?php echo DOMAIN; ?>compose_message/mods">Mod PM</a></li>
	<!--<li><a href="<?php echo DOMAIN; ?>compose_message/admins">Admin PM</a></li>-->
</ul>
<?php
$pm_num_rows = $pms->num_rows_fetched;
echo $pms->output('private messages');
page_navigation('private_messages', $current_page, $pm_num_rows);
require('includes/footer.php');
?>
