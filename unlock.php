<?php

session_cache_limiter('nocache');
session_name('SID');
session_start();
$_SESSION['notice'] = 'Enjoy your April Fools';
$_SESSION['unlocked'] = true;
header('Location: /');
die();
