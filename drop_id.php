<?php
require 'includes/header.php';
$page_title = 'Drop ID';

if ($_POST['drop_ID']) {
    // CSRF checking.
    check_token();
    unset($_SESSION['UID']);
    unset($_SESSION['ID_activated']);
    setcookie('UID', '', $_SERVER['REQUEST_TIME'] - 3600, '/', COOKIE_DOMAIN);
    setcookie('password', '', $_SERVER['REQUEST_TIME'] - 3600, '/', COOKIE_DOMAIN);
    setcookie('topics_mode', '', $_SERVER['REQUEST_TIME'] - 3600, '/', COOKIE_DOMAIN);
    setcookie('spoiler_mode', '', $_SERVER['REQUEST_TIME'] - 3600, '/', COOKIE_DOMAIN);
    setcookie('snippet_length', '', $_SERVER['REQUEST_TIME'] - 3600, '/', COOKIE_DOMAIN);
    session_destroy();
    session_name('SID');
    session_start();
    $_SESSION['notice'] = 'Your ID has been dropped.';
    header('Location: /');
    die();
}
?>
<p><em>Dropping</em> your ID will simply remove the UID, password, and mode cookies from your browser, effectively logging you out. If you want to keep your post history, settings, etc., <a href="<?php echo DOMAIN; ?>back_up_ID">back up your ID</a> and/or <a href="<?php echo DOMAIN; ?>dashboard">set a memorable password</a> before doing this.</p>
<form action="" method="post">
	<?php csrf_token() ?>
	<input type="submit" name="drop_ID" value="Drop my ID" />
</form>
<?php
require 'includes/footer.php';
?>
