<?php

require 'includes/header.php';
force_id();
update_activity('statistics');
$page_title = 'Statistics';

print_statistics($_SESSION['UID']);
require 'includes/footer.php';
