<?php
require 'includes/header.php';
force_id();
$page_title = 'Create private message';

if (!isset($_GET['replyto']) && isset($_GET['to']) && $_GET['to'] != 'admins' && $_GET['to'] != 'mods' && !allowed('mod_pm')) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

if (isset($_GET['replyto']) && !ctype_digit($_GET['replyto'])) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

if (USER_REPLIES < 5 && !$administrator && !allowed('mod_pm') && ($_GET['to'] == 'admins' || $_GET['to'] == 'mods')) {
    add_error('Need more posts, sorry.', true);
}

if (isset($_GET['replyto'])) {
    $stmt = $link->db_exec('SELECT contents, source, destination, expiration, can_reply FROM private_messages WHERE id = %1', $_GET['replyto']);

    if ($link->num_rows($stmt) < 1) {
        $page_title = 'Non-existent message';
        add_error('The message you tried to reply to does not exist. It may have expired.', true);
    }

    list($contents, $source, $destination, $expiration, $can_reply) = $link->fetch_row($stmt);
    $contents = preg_replace('/^/m', '> ', sanitize_for_textarea($contents));

    if (!$can_reply) {
        $error = true;
    }

    if ($destination == 'mods' && !allowed('mod_pm')) {
        $error = true;
    }
    if ($destination == 'admins' && !$administrator) {
        $error = true;
    }
    if (($destination != 'mods' && $destination != 'admins') && ($destination != $_SESSION['UID'])) {
        $error = true;
    }

    if ($error) {
        add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
    }
}

// Check CSRF-token
if (isset($_POST['preview']) || isset($_POST['submit'])) {
    check_token();
}

update_activity('pm_compose');

if (isset($_POST['contents']) && isset($_POST['submit'])) {
    $message = array();
    if (strlen(super_trim($_POST['contents'])) < 1) {
        add_error('Message body can\'t be blank.', true);
    }

    if (isset($_POST['as_mod']) && allowed('mod_pm')) {
        $message['source'] = 'mods';
    } elseif (isset($_POST['as_admin']) && $administrator) {
        $message['source'] = 'admins';
    } else {
        $message['source'] = $_SESSION['UID'];
    }
    if ($_GET['replyto']) {
        $message['destination'] = $source;
    } else {
        $message['destination'] = $_GET['to'];
    }
    $message['contents'] = wrapUserFormatter($_POST['contents']);
    $message['time'] = time();
    $message['expiration'] = (isset($_POST['expiration']) && !empty($_POST['expiration']) && allowed('mod_pm')) ? time() + $_POST['expiration'] : 0;
    $message['read'] = 0;
    $message['can_reply'] = ((isset($_POST['can_reply']) && allowed('mod_pm')) || !allowed('mod_pm')) ? 1 : 0;
    if ($_SESSION['num_pms'] > 0) {
        $_SESSION['num_pms'] = $_SESSION['num_pms'] - 1;
    }
    $link->insert('private_messages', $message);

    if (($inserted_id = $link->insert_id()) != null) {
        if ($message['destination'] == 'mods') {
            $mod_recepts = array();
            $link->db_exec("SELECT uid FROM permissions WHERE permission = 'mod_pm'");
            while (list($mod_uid) = $link->fetch_row()) {
                $mod_recepts[$mod_uid] = $mod_uid;
            }
            $notify = $mod_recepts + $administrators;
        } elseif ($message['destination'] == 'admins') {
            $notify = $administrators;
        } else {
            $notify = array($message['destination']);
        }

        foreach ($notify as $notifiee) {
            add_notification('privatemessage', $notifiee, $inserted_id, array('id' => $inserted_id, 'snippet' => snippet($_POST['contents'], 90)));
        }

        redirect('Private message sent.', 'private_messages');
    } else {
        add_error('An error occurred while sending your private message. Please try later again.', true);
    }
}
?>

<h3>Creating private message <?php if (isset($_GET['replyto'])): ?>in reply to message #<?php echo intval($_GET['replyto']); ?><?php else: ?>to <?php if ($_GET['to'] == 'mods'): ?>mod<?php elseif ($_GET['to'] == 'admins'): ?>admin<?php else: ?>user #<?php echo htmlentities($_GET['to']); ?><?php endif; ?><?php endif; ?>.</h3>

<div class="body">
	<form action="<?php echo DOMAIN; ?><?php if (isset($_GET['replyto'])): ?>reply_to_message/<?php echo intval($_GET['replyto']); ?><?php else: ?>compose_message/<?php echo htmlentities($_GET['to']); ?><?php endif; ?>" method="post">
		<?php csrf_token() ?>
		<?php if (isset($_POST['preview'])): ?>
		<h3 id="preview">Preview</h3>
		<div class="body standalone">
		<?php echo parse($_POST['contents']); ?>
		</div>
		<?php endif; ?>
		<label for="body" class="noscreen">Message</label> 
		<textarea name="contents" cols="80" rows="10" tabindex="2" id="contents" class="markup_editor"><?php if (isset($_POST['contents'])): ?><?php echo htmlentities($_POST['contents']); ?><?php elseif (isset($contents)): ?><?php echo $contents."\n"; ?><?php endif; ?></textarea>
		<?php if (allowed('mod_pm')): ?>
		Expiration from now in seconds (leave empty for no expiration): <input type="text" name="expiration" size="8" class="inline" /><br />
		Recipient can reply: <input type="checkbox" name="can_reply" id="can_reply" class="inline" checked="checked" /><br />
		<?php endif; ?>
		<?php if (allowed('mod_pm')): ?>
		<?php if (isset($_GET['replyto'])): ?>Reply<?php else: ?>Send<?php endif; ?> as mod (this will allow all other mods to read this message and its response, too): <input type="checkbox" name="as_mod" id="as_mod" class="inline" /><br />
		<?php endif; ?>
		<?php if ($administrator): ?>
		<?php if (isset($_GET['replyto'])): ?>Reply<?php else: ?>Send<?php endif; ?> as admin (this will allow all other admins to read this message and it's response, too): <input type="checkbox" name="as_admin" id="as_admin" class="inline" /><br />
		<?php endif; ?>
		<label for="body" class="noscreen">Submit</label>
		<input type="submit" name="preview" value="Preview" class="inline" /> <input type="submit" name="submit" value="Send" class="inline" />
	</form>
</div>

<?php
require 'includes/footer.php';
?>
