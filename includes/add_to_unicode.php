<?php
if(php_sapi_name() != "cli") die();
require("unicode.php");

$in = "ᵃ ᵇ ᶜ ᵈ ᵉ ᶠ ᵍ ʰ ᶦ ʲ ᵏ ᶫ ᵐ ᶰ ᵒ ᵖ ᑫ ʳ ˢ ᵗ ᵘ ᵛ ʷ ˣ ʸ ᶻ";

foreach(mb_split(' ', $in) as $k=>$v) {
    $l = chr(97+$k);
    $arr = $arrChars[$l];
    if(!in_array($v, $arr)) {
        $arrChars[$l][] = $v;
    }
}

file_put_contents("unicode.php", "<?php\n\n\$arrChars = " . var_export($arrChars, true) . ";");