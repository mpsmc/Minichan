<?php
require 'includes/header.php';
$requested_page = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if (substr($requested_page, 0, strlen($basePath)) == $basePath) {
    $requested_page = substr($requested_page, strlen($basePath));
}
$requested_page = ltrim($requested_page, '/');
if ($requested_page == 'favicon.ico' || $requested_page == 'favicon.png') {
    die();
}
$stmt = $link->db_exec('SELECT page_title, content FROM pages WHERE url = %1', $requested_page);
if ($link->num_rows($stmt) < 1) {
    redirect(MESSAGE_PAGE_NOT_FOUND.' (Page: '.$requested_page.')', DOMAIN);
}
list($page_title, $content) = $link->fetch_row($stmt);
echo $content;
require 'includes/footer.php';
?> 