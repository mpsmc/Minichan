<?php

require 'includes/header.php';
update_activity('hot_topics', 1);
$page_title = 'Hot topics';

echo '<div style="float: left; width: 50%;">';

$stmt = $link->db_exec('SELECT headline, id, time, replies FROM topics WHERE deleted = 0 AND locked = 0 AND ((UNIX_TIMESTAMP()-last_post)<3600) ORDER BY replies DESC LIMIT 0, 10');
echo '<h2>Last hour:</h2><ol>';
while (list($hot_headline, $hot_id, $hot_time, $hot_replies) = $link->fetch_row($stmt)) {
    echo "\n\t<li><a href=\"".DOMAIN.'topic/'.$hot_id.'">'.htmlspecialchars($hot_headline).'</a></li>';
}
echo "\n</ol>";

$stmt = $link->db_exec('SELECT headline, id, time, replies FROM topics WHERE deleted = 0 AND locked = 0 AND ((UNIX_TIMESTAMP()-last_post)<43200) ORDER BY replies DESC LIMIT 0, 10');
echo '<h2>Last 12 hours:</h2><ol>';
while (list($hot_headline, $hot_id, $hot_time, $hot_replies) = $link->fetch_row($stmt)) {
    echo "\n\t<li><a href=\"".DOMAIN.'topic/'.$hot_id.'">'.htmlspecialchars($hot_headline).'</a></li>';
}
echo "\n</ol>";

$stmt = $link->db_exec('SELECT headline, id, time, replies FROM topics WHERE deleted = 0 AND locked = 0 AND ((UNIX_TIMESTAMP()-last_post)<86400) ORDER BY replies DESC LIMIT 0, 10');
echo '<h2>Last 24 hours:</h2><ol>';
while (list($hot_headline, $hot_id, $hot_time, $hot_replies) = $link->fetch_row($stmt)) {
    echo "\n\t<li><a href=\"".DOMAIN.'topic/'.$hot_id.'">'.htmlspecialchars($hot_headline).'</a></li>';
}
echo "\n</ol>";

$stmt = $link->db_exec('SELECT headline, id, time, replies FROM topics WHERE deleted = 0 AND locked = 0 AND ((UNIX_TIMESTAMP()-last_post)<604800) ORDER BY replies DESC LIMIT 0, 10');
echo '<h2>This week:</h2><ol>';
while (list($hot_headline, $hot_id, $hot_time, $hot_replies) = $link->fetch_row($stmt)) {
    echo "\n\t<li><a href=\"".DOMAIN.'topic/'.$hot_id.'">'.htmlspecialchars($hot_headline).'</a></li>';
}
echo "\n</ol>";
echo '</div><div style="float: right; width: 50%;">';

$stmt = $link->db_exec('SELECT headline, id, time, replies FROM topics WHERE deleted = 0 ORDER BY replies DESC LIMIT 0, 50');
echo '<h2>All time:</h2><ol>';
while (list($hot_headline, $hot_id, $hot_time, $hot_replies) = $link->fetch_row($stmt)) {
    echo "\n\t<li><a href=\"".DOMAIN.'topic/'.$hot_id.'">'.htmlspecialchars($hot_headline).'</a></li>';
}
echo "\n</ol></div>";

require 'includes/footer.php';
