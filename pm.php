<?php
require 'includes/header.php';
force_id();
$page_title = 'Private message';

// Validate message ID, then fetch it.
if (!ctype_digit($_GET['id'])) {
    add_error('Invalid ID.', true);
}

$stmt = $link->db_exec('SELECT `source`, `destination`, `contents`, `time`, `expiration`, `read`, `can_reply` FROM `private_messages` WHERE `id` = %1', $_GET['id']);

// Check if we have a message.
if ($link->num_rows($stmt) == 0) {
    $page_title = 'Non-existent message';
    add_error('There is no such private message. It may have expired.', true);
}

// Fetch it.
list($source, $destination, $contents, $time, $expiration, $read, $can_reply) = $link->fetch_row($stmt);
$link->free_result($stmt);

// Check if the message has expired yet.
if ($expiration < time() && $expiration != 0) {
    $page_title = 'Non-existent message';
    add_error('There is no such private message. It may have expired.', true);
}

if ($source != $_SESSION['UID'] && $destination != $_SESSION['UID']) {
    $error = true;
}
if (($source == 'mods' || $destination == 'mods') && (allowed('mod_pm'))) {
    $error = false;
}
if (($source == 'admins' || $destination == 'admins') && $administrator) {
    $error = false;
}

if ($error) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

/* set the message as read if it was previously unread */
if (!$read) {
    if ($_SESSION['num_pms'] > 0) {
        $_SESSION['last_pm_check'] = 0;
    }
    $link->db_exec('UPDATE `private_messages` SET `read` = 1 WHERE `id` = %1', $_GET['id']);

    remove_notification('privatemessage', $_SESSION['UID'], $_GET['id'], null);
}
?>
<h3>Sent by 
<strong>
<?php
if ($source == $_SESSION['UID']) {
    echo 'you';
} elseif ($source == 'mods') {
    echo 'a mod';
} elseif ($source == 'admins') {
    echo 'an admin';
} else {
    if (allowed('open_profile')) {
        echo '<a href="'.DOMAIN.'profile/'.$source.'">';
    }
    echo modname($source);
    if (allowed('open_profile')) {
        echo '</a>';
    }
}

?>
</strong>

 to 
<strong>
<?php
if ($destination == $_SESSION['UID']) {
    echo 'you';
} elseif ($destination == 'mods') {
    echo 'all mods';
} elseif ($destination == 'admins') {
    echo 'all admins';
} else {
    if (allowed('open_profile')) {
        echo '<a href="'.DOMAIN.'profile/'.$destination.'">';
    }
    echo modname($destination);
    if (allowed('open_profile')) {
        echo '</a>';
    }
}
?>
</strong>

 <?php echo calculate_age($time, time()) ?> ago.
</h3>

<div class="body">
	<?php echo parse($contents); ?>
</div>

<?php if ($can_reply || $expiration): ?>
<p>
	<?php if ($can_reply): ?>
	<strong><a href="<?php echo DOMAIN; ?>reply_to_message/<?php echo intval($_GET['id']); ?>" title="Reply to message">Reply to message</a></strong>
	<?php endif; ?>
	<?php if ($can_reply && $expiration): ?>
	<br />
	<?php endif; ?>
	<?php if ($expiration): ?>
	<em>[ This message will expire <?php echo calculate_age(time(), $expiration); ?> from now. ]</em>
	<?php endif; ?>
</p>
<?php endif; ?>

<?php require './includes/footer.php'; ?>
