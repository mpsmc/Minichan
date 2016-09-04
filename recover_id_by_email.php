<?php
require 'includes/header.php';
$page_title = 'Recover ID by e-mail';
$onload_javascript = 'focusId(\'e-mail\');';

if (!empty($_POST['e-mail'])) {
    // Validate e-mail address.
    if (!filter_var($_POST['e-mail'], FILTER_VALIDATE_EMAIL)) {
        add_error('That doesn\'t look like a valid e-mail address.');
    }
    // Deny flooders (this should be done from the database for added security).
    if ($_SESSION['recovery_email_count'] > 3) {
        add_error('How many times do you need to recover your password in one day?');
    }

    $stmt = $link->db_exec('SELECT user_settings.uid, users.password, user_settings.email FROM user_settings INNER JOIN users ON user_settings.uid = users.uid WHERE user_settings.email = %1 LIMIT 50', $_POST['e-mail']);

    $ids_for_email = array();
    while (list($uid, $password, $email) = $link->fetch_row($stmt)) {
        if ($email == $_POST['e-mail']) {
            $ids_for_email[$uid] = $password;
        }
    }

    if (!$erred) {
        if (!empty($ids_for_email)) {
            $num_ids = count($ids_for_email);
            if ($num_ids == 1) {
                $email_body = 'Your ID is '.key($ids_for_email).' and your password is '.current($ids_for_email).'. To restore your ID, follow this link: '.DOMAIN.'restore_ID/'.key($ids_for_email).'/'.current($ids_for_email);
            } else {
                $email_body = 'The following IDs are associated with your e-mail address:'."\n\n";
                foreach ($ids_for_email as $id => $password) {
                    $email_body .= 'ID: '.$id."\n".'Password: '.$password."\n".'Link to restore: '.DOMAIN.'restore_ID/'.$id.'/'.$password."\n\n";
                }
            }
            mail($_POST['e-mail'], SITE_TITLE.' ID recovery', $email_body, 'From: '.SITE_TITLE.'<'.MAILER_ADDRESS.'>');
        }
        ++$_SESSION['recovery_email_count'];
        redirect('ID recovery e-mail sent.', '');
    }
}
print_errors();
?>
<p>If your ID has an e-mail address associated with it (as set in the <a href="<?php echo DOMAIN; ?>dashboard">dashboard</a>), this tool can be used to recover its password. You will be sent a recovery link for every ID associated with your e-mail address.</p>
<form action="" method="post">
	<div class="row">
		<label for="e-mail">Your e-mail address</label>
		<input type="text" id="e-mail" name="e-mail" size="30" maxlength="100" />
	</div>
	
	<div class="row">
		<input type="submit" value="Send recovery e-mail" />
	</div>
</form>
<?php
require 'includes/footer.php';
?>
