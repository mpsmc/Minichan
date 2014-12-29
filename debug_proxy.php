<?php
require("includes/header.php");
echo "Output: ";
$hostaddr = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$_test = '.cn';

if(!$hostaddr) echo 1;
if($hostaddr == ".") echo 2;
if($_SERVER['HTTP_X_FORWARDED_FOR']) echo 3;
if($_SERVER['HTTP_X_FORWARDED']) echo 4;
if($_SERVER['HTTP_FORWARDED_FOR']) echo 5;
if($_SERVER['HTTP_VIA']) echo 6;
if(in_array($_SERVER['REMOTE_PORT'], array(8080,80,6588,8000,3128,553,554))) echo 7;
if(!$_SERVER['HTTP_CONNECTION']) echo 8;
if(stripos($hostaddr, "tor-exit")!==false) echo 9;
if(stripos($hostaddr, "torserversnet")!==false) echo 0;
if(stripos($hostaddr, "anonymizer")!==false) echo "a";
if(stripos($hostaddr, "mycingular.net")!==false) echo "b";
if(stripos($hostaddr, "ipredate.net")!==false) echo "c";
if(stripos($hostaddr, "proxy")!==false) echo "d";
if(stripos($hostaddr, ".info")!==false) echo "e";
if(stripos($hostaddr, "ioflood.com")!==false) echo "f";
if(IsTorExitPoint()) echo "g";
if(strpos($hostaddr, ".") !== false && substr_count($hostaddr, ".") < 3) echo "h";
if(strpos($_SERVER['REMOTE_ADDR'], '65.49.') === 0) echo "i";
if($hostaddr && substr_compare($hostaddr, $_test, -strlen($_test), strlen($_test)) === 0) echo "j";


require("includes/footer.php");
