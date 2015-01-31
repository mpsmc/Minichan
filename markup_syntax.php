<?php
require('includes/header.php');
$page_title = "Markup Syntax";

function printDemonstration($message) {
	static $odd;
	$odd = !$odd;

	echo "<tr".($odd ? ' class="odd"' : '').">";
	echo '<td class="minimal">' . parse('1:'.$message) . '</td>';
	echo '<td><kbd>' . parse('1:[code]'.$message.'[/code]') . '</kbd></td>';
	echo "</tr>";
}

?>
<p>
This board uses two styles of formatting, BBCode and Wiki. They can be mixed however you like, and are used to style your posts.
Below are examples of which formatting is available, and how it looks once applied. Emoticons are also supported, a full list can be found <a href="<?php echo DOMAIN; ?>emoticons">here</a>.
</p>
<strong>BBCode</strong>
<table>
<thead>
	<tr>
		<th class="minimal">Output</th>
		<th>Input</th>
	</tr>
</thead>
<tbody>
<?php
foreach(array(
	"[i]Emphasis[/i]",
	"[b]Strong emphasis[/b]",
	"[sp]Spoiler #1[/sp] / [spoiler]Spoiler #2[/spoiler]",
	"[u]Underline[/u]",
	"[s]Strikethrough[/s]",
	"[hl]Highlight[/hl]",
	"[h]Header[/h]",
	"[url=http://example.com/]Link text[/url]",
	"[border]Bordered Text[/border]",
	"[sup]Superscript[/sup]",
	"[sub]Subscript[/sub]",
	"[colour=red]Colours[/colour]",
	"[list]\n* Entry 1\n* Entry 2\n[/list]",
	"[code]General purpose code[/code]",
	"[code=auto]// auto for autodetect, or language name\n<?php\n  echo 'Syntax highlighted code';\n[/code]",
	"[raw]Disable formatter without [code] style[/raw]"
) as $row) printDemonstration($row);
?>
</tbody>
</table>
<strong>Wiki</strong>
<table>
<thead>
	<tr>
		<th class="minimal">Output</th>
		<th>Input</th>
	</tr>
</thead>
<tbody>
<?php
foreach(array(
	"> Quote",
	"''Emphasis''",
	"'''Strong emphasis'''",
	"**Spoiler**",
	"__Underline__",
	"--Strikethrough--",
	"%%Highlight%%",
	"==Header==",
	"[http://example.com/ Link text]"
) as $row) printDemonstration($row);
?>
</tbody>
</table>
<p>
<strong>Code</strong><br/>
The following languages are supported by the [code] tags:<br/>
<script>
	var languages = hljs.listLanguages();
	for(var i = 0; i < languages.length; i++) {
		if(i != 0) document.write(", ");
		document.write(languages[i]);
	}
</script>
</p>

<?php
require('includes/footer.php');