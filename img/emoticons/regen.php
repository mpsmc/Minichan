<?php if(php_sapi_name() != 'cli') die("Must be ran through CLI!");
chdir(realpath(dirname(__FILE__)));
require('emoticons.php');

$emoticons = getEmoticons();
$values = array_values($emoticons);

$dir = new DirectoryIterator(".");
foreach ($dir as $fileinfo) {
	if ($fileinfo->isDot()) continue;
	$name = $fileinfo->getFilename();
	$found = false;
	foreach(array(".png", ".gif", ".jpg") as $ext) {
		if(strpos($name, $ext, strlen($name) - strlen($ext)) !== false) $found = substr($name, 0, strlen($name) - strlen($ext));
	}
	if($found===false) continue;
	if(in_array($name, $values)) continue;
	$emoticons[$found] = $name;
}

ksort($emoticons);

foreach($emoticons as $key=>$img) {
	if(!file_exists($img)) {
		echo "Warning: $img ($key) does not exist!\n";
	}
}

$output = <<<EOF
<?php
function getEmoticons() {
  return %ARRAY%
}
EOF;
$output = str_replace("%ARRAY%", preg_replace('/\n/', "\n  ", var_export($emoticons, true).';'), $output);
file_put_contents("emoticons.php", $output);