<?php

$buffered_content = ob_get_contents();
ob_end_clean();
require 'template.php';
