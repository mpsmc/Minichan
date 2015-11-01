<?php
require 'includes/header.php';
force_id();
$page_title = 'Your trash can';
update_activity('trash_can', 1);

echo '<p>Your deleted topics and replies are archived here.</p>';
if ($trash = show_trash($_SESSION['UID'])) {
    echo $trash;
}
require 'includes/footer.php';
?> 