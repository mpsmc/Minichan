<?php

include 'ChromePhp.php';
ChromePhp::useFile('chromelogs', 'chromelogs');

ChromePhp::log('hello world');
ChromePhp::log('_SERVER', $_SERVER);

// warnings and errors
ChromePhp::warn('this is a warning');
ChromePhp::error('this is an error');
