<?php
chdir("..");
require("includes/header.php");
force_id();

if($_GET['link'] == 1 && check_token()) {
	$device_token = strtoupper($_POST['device_token']);
	$link->db_exec("SELECT 1 FROM android_tokens WHERE rand_token = %1", $device_token);
	if($link->num_rows() > 0) {
		$link->db_exec("DELETE FROM android_tokens WHERE uid = %1", $_SESSION['UID']); // Purge all old links
		$link->db_exec("UPDATE android_tokens SET uid = %1 WHERE rand_token = %2", $_SESSION['UID'], $device_token);
		header("Location: " . DOMAIN);
		$_SESSION['notice'] = "Device linked";
		add_notification('linked', $_SESSION['UID'], 'linked', array());
		die();
	}else{
		add_error("Unkown device token");
	}
}

$page_title = "Link android device";
print_errors();
?>
<form method="post" action="<?php echo DOMAIN; ?>android/link">
To link your Android device to your Minichan account, you need to first install the Minichan Android App from the Google Market.<br />
Once the application is installed it will give you a device token which you need to specify here: <br />
<input type="text" name="device_token" style="display: inline" /> <input type="submit" value="Link device" style="display: inline" /><br />
<span class="unimportant">(You can only link one device to an UID at a time, if you've linked other devices before they'll be deleted.)</span><br /><br />
<?php csrf_token() ?>
</form>
<?php
require("includes/footer.php");