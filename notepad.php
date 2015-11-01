<?php
require 'includes/header.php';
force_id();
$page_title = 'Notepad';
update_activity('notepad', 1);
$onload_javascript = 'focusId(\'notepad_list\'); init();';
if ($_POST['form_sent']) {
    // CSRF checking.
    check_token();
    check_length($_POST['notepad_list'], 'notepad list', 0, 4000);
    if (!$erred) {
        $update_notepad_list = $link->db_exec('INSERT INTO notepad (uid, notepad_content) VALUES (%1, %2) ON DUPLICATE KEY UPDATE notepad_content = %3', $_SESSION['UID'], $_POST['notepad_list'], $_POST['notepad_list']);
        $_SESSION['notice'] = 'Notepad updated';
    } else {
        $notepad_content = $_POST['notepad_list'];
    }
}
$fetch_notepad_list = $link->db_exec('SELECT notepad_content FROM notepad WHERE uid = %1', $_SESSION['UID']);
list($notepad_content) = $link->fetch_row($fetch_notepad_list);
print_errors();
?> 
<p>This is your notepad, use it to keep notes, save drafts, to-do lists, etc, etc.</p>
<form action="" method="post">
	<?php csrf_token() ?>
	<div>
		<textarea id="notepad_list" name="notepad_list" cols="80" rows="10"><?php echo sanitize_for_textarea($notepad_content) ?></textarea>
	</div>
	<div class="row">
		<input type="submit" name="form_sent" value="Update" />
	</div>
</form>
<?php
require 'includes/footer.php';
?>