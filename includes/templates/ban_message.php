<?php
if ($ban['expiry'] > 0) {
    echo 'This ban will expire in '.calculate_age($ban['expiry']).'.';
} else {
    echo 'This ban is not set to expire.';
}
if ($ban['reason']): ?>
<br />
You have been banned for the following reason: <?php echo $ban['reason']; ?>
<?php
endif;
echo getRandomYoutube();
if (file_exists(template('extra_ban_message'))) {
    require template('extra_ban_message');
}
?>