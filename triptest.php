<?php

require 'includes/header.php';
$page_title = 'Tripcode tester';
echo '<form method="GET">';
if (isset($_GET['name'])) {
    $res = nameAndTripcode($_GET['name']);
    if ($res[0]) {
        $name = $res[0];
    } else {
        $name = '';
    }

    if ($res[0] && $res[1]) {
        $name .= ' ';
    }
    if ($res[1]) {
        $name .= $res[1];
    }

    echo '<b>Result</b>: '.htmlspecialchars($name).'<br/>';
}
echo '<div class="row"><label for="name">Name</label>:<input id="name" name="name" placeholder="name #tripcode" type="text" size="30" maxlength="30" value="'.htmlspecialchars($_GET['name']).'">';
echo '</form>';

require 'includes/footer.php';
