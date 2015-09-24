<?php
require('includes/header.php');
force_id();
$page_title = 'Edit ignored phrases';
update_activity('ignore_list', 1);
$onload_javascript = 'focusId(\'ignore_list\'); init();';

if ($_POST['form_sent']) {
	// CSRF checking.
	check_token();
	check_length($_POST['ignore_list'], 'ignore list', 0, 4000);
    check_length($_POST['ignore_names'], 'ignorename list', 0, 4000);
	if (!$erred) {
		$update_ignore_list = $link->db_exec('INSERT INTO ignore_lists (uid, ignored_phrases, ignored_names) VALUES (%1, %2, %3) ON DUPLICATE KEY UPDATE ignored_phrases = %4, ignored_names = %5', $_SESSION['UID'], $_POST['ignore_list'], $_POST['ignore_names'], $_POST['ignore_list'], $_POST['ignore_names']);
        $_SESSION['notice'] = 'Ignore list updated.';
        if (!$user_settings['ostrich_mode']) {
            $_SESSION['notice'] .= ' You must <a href="'.DOMAIN.'dashboard">enable ostrich mode</a> for this to have any effect.';
        }
    } else {
        $ignored_phrases = $_POST['ignore_list'];
    }
}

$fetch_ignore_list = $link->db_exec('SELECT ignored_phrases, ignored_names FROM ignore_lists WHERE uid = %1', $_SESSION['UID']);
list($ignored_phrases, $ignored_names) = $link->fetch_row($fetch_ignore_list);
print_errors();
?> 
<p>When ostrich mode is <a href="<?php echo DOMAIN; ?>dashboard">enabled</a>, any topic or reply that contains a phrase on your ignore list will be hidden. Citations to hidden replies will be replaced with "@hidden". Enter one (case insensitive) phrase per line.</p>
<form action="" method="post">
	<?php csrf_token() ?>
	<div>
		<textarea id="ignore_list" name="ignore_list" cols="80" rows="10"><?php echo sanitize_for_textarea($ignored_phrases) ?></textarea>
	</div>
    <p>You can also hide messages posted by certain names. Enter one (case insensitive) username !tripcode per line.</p>
    <div>
		<textarea id="ignore_names" name="ignore_names" cols="80" rows="10"><?php echo sanitize_for_textarea($ignored_names) ?></textarea>
	</div>
	<div class="row">
		<input type="submit" name="form_sent" value="Update" />
	</div>
</form>
<?php
require('includes/footer.php');
?>