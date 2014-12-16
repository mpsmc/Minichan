<?php
chdir("..");
require("includes/header.php");
$page_title = "MinichanNotifier (".htmlspecialchars($_GET['version']).") has been updated!";
?>
<h2>0.1.16</h2>
<ul><li>Added popup notifications</li></ul>
<h2>0.1.15</h2>
<ul><li>Typo</li></ul>
<h2>0.1.14</h2>
<ul>
<li>Made script honour middle click in the popup window</li>
</ul>
<h2>0.1.13</h2>
<ul>
<li>Added popup method for existing notifications. It'll be set as default for you, but you can find the old methods in the code.
	<ul>
    	<li>It uses the user's specified style on MC</li>
    </ul>
</li>
</ul>
<h2>0.1.12</h2>
<ul>
<li>Bugfix</li>
</ul>
<h2>0.1.11</h2>
<ul>
<li>Added options page with a bunch of new features
	<ul>
    	<li>Right click icon, hit options, play with it</li>
        <li>The "existing tab" option will open the leftmost minichan tab in the current window.</li>
    </ul>
</li>
</ul>
<h2>0.1.10</h2>
<ul>
<li>Added in the "tabs" permission, you will get a warning about that. It is required to open stuff in the same tab instead of a new one (e.g. omnibar and pressing the icon). Check source if you're concerned about your security.</li>
<li>Added OmniBar.
    <ul>
        <li>Type "mc" in the URL bar to get started</li>
        <li>Should show the last 5 bumps by default</li>
        <li>Typing anything then pressing enter should search for that term</li>
        <li>Typing "mc bumps" should open the bumps page</li>
        <li>Typing "mc topics" should open the topics page</li>
        <li>Typing "mc new" should open the New Topic page</li>
        <li>Typing "mc (anything else)" should perform a deep search for that term</li>
        <li>Selecting one of the latest 5 suggested topics should open that topic</li>
    </ul>
</li>
</ul>
<?php
require("includes/footer.php");