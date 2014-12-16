<?php
require('includes/header.php');
update_activity('stuff'); // Yes. Stuff. Sekrit page.
$page_title = 'Manage DEFCON';
if(!allowed("manage_defcon")) add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
if(isset($_POST['id'])) { 
	$defcon = $_POST['id'];
	check_token();
	if(!is_numeric($defcon) || $defcon > 5 || $defcon < 1) {
		$_SESSION['notice'] = "What are you trying to do?";
		header("Location: ".DOMAIN."defcon");
		die();
	}
	if(!$administrator && ($defcon==1||$defcon==2)){
		$_SESSION['notice'] = MESSAGE_PAGE_ACCESS_DENIED;
		header("Location: ".DOMAIN."defcon");
		die();
	}
	$link->db_exec("UPDATE flood_control SET value = %1 WHERE setting = 'defcon'", $defcon);
	log_mod("defcon", $defcon);
	header("Location: ". DOMAIN);
	die();

}
$additional_head = "<style>input[type='radio']{ display: inline !important; }</style>";
$selected = "def".DEFCON;
$$selected = " checked";
?>
<form action = "" method="post" />
<input type="radio" name="id" value="1" id="defcon_1"<?php echo $def1; if(!$administrator) echo " disabled"; ?>> <label for="defcon_1">DEFCON 1 — Block everyone from accessing the board except for Administrators.</label><br>
<input type="radio" name="id" value="2" id="defcon_2"<?php echo $def2; if(!$administrator) echo " disabled"; ?>> <label for="defcon_2">DEFCON 2 — Block all users except for Moderators and Administrators from posting.</label><br>
<input type="radio" name="id" value="3" id="defcon_3"<?php echo $def3; ?>> <label for="defcon_3">DEFCON 3 — Block posting for users with less than <?php echo POSTS_TO_DEFY_DEFCON_3; ?> posts.</label><br>
<input type="radio" name="id" value="4" id="defcon_4"<?php echo $def4; ?>> <label for="defcon_4">DEFCON 4 — Block the creation of new UIDs, existing UIDs have full privileges.</label><br>
<input type="radio" name="id" value="5" id="defcon_5"<?php echo $def5; ?>> <label for="defcon_5">DEFCON 5 — Normal board operation.</label><br>
<?php csrf_token(); ?>
<input type="submit" value="Set" />
</form>
<?php
require('includes/footer.php');
?>