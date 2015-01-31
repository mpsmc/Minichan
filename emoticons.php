<?php
require('includes/header.php');
require_once('img/emoticons/emoticons.php');

$page_title = "Emoticon gallery";
?>
<table>
<tr>
<th class='minimal'>Markup</th>
<th>Emoticon</th>
</tr>
<?php
foreach(getEmoticons() as $key=>$img) {
	echo "<tr><td>";
	echo ':'.htmlspecialchars($key).':';
	echo "</td><td>";
	echo '<img title=":'.htmlspecialchars($key, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES).':" src="'.STATIC_DOMAIN . 'img/emoticons/'.$img.'" />';
	echo "</td></tr>";
}
echo "</table>";

require('includes/footer.php');