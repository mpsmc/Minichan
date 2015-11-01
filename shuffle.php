<?php
require 'includes/header.php';
force_id();
update_activity('shuffle', 1);
$stmt = $link->db_exec('SELECT id FROM topics WHERE deleted = 0 AND locked = 0 AND ((UNIX_TIMESTAMP()-last_post)<%1) ORDER BY RAND() LIMIT 1', NECRO_BUMP_TIME);
list($shuffle_topic_id) = $link->fetch_row($stmt);
redirect('There you go — a semi-random topic!', 'topic/'.$shuffle_topic_id);
require 'includes/footer.php';
?> 
