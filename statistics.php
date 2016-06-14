<?php

require 'includes/header.php';
force_id();
update_activity('statistics');
$page_title = 'Statistics';
?>
<p>
Deleted posts are not included in amount.
</p>
<?php
print_statistics($_SESSION['UID']);
require 'includes/footer.php';
