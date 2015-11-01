<?php
require 'includes/header.php';
update_activity('stuff');
$page_title = 'Stuff';
?>
<div style="float: left; width: 50%;">
	<ul class="stuff">
		<li><strong><a href="<?php echo DOMAIN; ?>dashboard">Dashboard</a></strong> — <span class="unimportant">Your personal settings, including username and password.</span></li>
		<li><a href="<?php echo DOMAIN; ?>private_messages">Inbox</a> — <span class="unimportant">Your private messages (<?php echo $num_pms; ?>).</span></li>
		<li><a href="<?php echo DOMAIN; ?>notepad">Notepad</a> — <span class="unimportant">Your personal notepad.</span></li>
		<li><a href="<?php echo DOMAIN; ?>edit_ignore_list">Edit ignore list</a> — <span class="unimportant">Self-censorship.</span></li>
		<li><a href="<?php echo DOMAIN; ?>trash_can">Trash can</a> — <span class="unimportant">Your deleted posts.</span></li>
	</ul>
	<ul class="stuff">
		<li><strong><a href="<?php echo DOMAIN; ?>restore_ID">Restore ID</a></strong> — <span class="unimportant">Log in.</span></li>
		<li><a href="<?php echo DOMAIN; ?>back_up_ID">Back up ID</a></li>
		<li><a href="<?php echo DOMAIN; ?>recover_ID_by_email">Recover ID by e-mail</a></li>
		<li><a href="<?php echo DOMAIN; ?>generate_ID_card">Download ID card</a></li>
		<li><a href="<?php echo DOMAIN; ?>drop_ID">Drop ID</a> — <span class="unimportant">Log out.</span></li>
	</ul>
</div>
<div style="float: right; width: 50%;">
	<ul class="stuff">
		<li><strong><a href="<?php echo DOMAIN; ?>rules">Rules</a></strong> — <span class="unimportant">The unwritten rules, written.</span></li>
		<li><a href="<?php echo DOMAIN; ?>FAQ">FAQ</a> — <span class="unimportant">Frequently asked questions.</span></li>
		<li><a href="<?php echo DOMAIN; ?>statistics">Statistics</a></li>
		<li><a href="<?php echo DOMAIN; ?>failed_postings">Failed postings</a></li>
		<li><a href="<?php echo DOMAIN; ?>date_and_time">Date and time</a></li>
	</ul>
	<ul class="stuff">
		<li><strong><a href="<?php echo DOMAIN; ?>chat">Chat (IRC)</a></strong> — <span class="unimportant">Have some biscuits and tea.</span></li>
<!--		<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=7P4QPNBZJ4R7N&amp;lc=GB&amp;item_name=TinyBBS&amp;item_number=TinyBBS%20Donation&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" alt="Donate" target="_new">Donate</a> — <span class="unimportant">Contribute towards the site costs.</span></li> -->
		<li><a href="<?php echo DOMAIN; ?>compose_message/mods">Contact</a> — <span class="unimportant">Send a personal message to the mod(s).</span></li>
		<li><strong><a href="<?php echo DOMAIN; ?>addons">Add-ons</a></strong> — <span class="unimportant">Add-ons, ready to take over the world.</span></li>
		<li><strong><a href="<?php echo DOMAIN; ?>push">HTML5 Push Notifications</a></strong> — <span class="unimportant">Be there. Always.</span></li>
		<li><strong><a href="<?php echo DOMAIN; ?>link">MiniURL</a></strong> — <span class="unimportant">Minify links</span></li>
		<li><strong><a href="<?php echo DOMAIN; ?>triptest">Tripcode Tester</a></strong> — <span class="unimportant">Test your tripcodes</span></li>
		<?php
        if (MOBILE_MODE) {
            $main_menu = array(
            'Hot' => 'hot_topics',
            'Replies' => 'replies',
            'History' => 'history',
            'Watchlist' => 'watchlist',
            'Bulletins' => 'bulletins',
            'Events' => 'events',
            'Folks' => 'folks',
            'Search' => 'search',
            );
            foreach ($main_menu as $linked_text => $path) {
                // Output the link if we're not already on that page.
                    echo indent().'<li><a href="'.DOMAIN.$path.'">'.$linked_text;

                // If we need to check for new stuff...
                if (isset($last_action_check[ $linked_text ])) {
                    $last_action_name = $last_action_check[ $linked_text ];
                    // If there's new stuff, print an exclamation mark.
                    if (isset($_COOKIE[$last_action_name]) && $_COOKIE[ $last_action_name ] < $last_actions[ $last_action_name ]) {
                        echo '<i>!</i>';
                    }
                }
                echo '</a></li>';
            }
        }
        ?>
	</ul>
</div>

<?php

$mod_section = allowed('open_modlog') || allowed('undelete_topic') || allowed('manage_defcon') || allowed('exterminate') || allowed('manage_search') || allowed('ban_ip') || allowed('ban_uid') || allowed('manage_cms');

if ($mod_section) {
    echo '<div><h4 class="section" style="clear: both">Moderation</h4> <ul class="stuff">';
}

if (allowed('manage_cms')) {
    echo '<li><a href="'.DOMAIN.'CMS">Content management</a>  — <span class="unimportant">Manage non-dynamic (static) pages.</span></li>';
}

if (allowed('ban_ip') || allowed('ban_uid')) {
    echo '<li><a href="'.DOMAIN.'bans">Bans</a>  — <span class="unimportant">View a list of current bans and manage them.</span></li>';
}

if (allowed('manage_search')) {
    $search_mode_sql = "SELECT value FROM flood_control WHERE setting = 'search_disabled'";
    $search_mode_send = send($search_mode_sql);
    $search_mode_get = mysql_fetch_array($search_mode_send);
    $search_mode_status = $search_mode_get['value'];
    if ($search_mode_status == 0) {
        $search_mode = '<strong><em>enabled</em></strong> <small>(<a href="'.DOMAIN.'toggle_search_mode/1" onclick="return submitDummyForm(\''.DOMAIN.'toggle_search_mode/1\', \'id\', 1, \'Disable search mode for newbies?\');">disable</a>)</small>';
    } else {
        $search_mode = '<strong><em>disabled</em></strong> <small>(<a href="'.DOMAIN.'toggle_search_mode/0" onclick="return submitDummyForm(\''.DOMAIN.'toggle_search_mode/0\', \'id\', 0, \'Enable search mode for newbies?\');">enable</a>)</small>';
    }
    echo '<li>Searching for newbies is currently '.$search_mode.' — <span class="unimportant">Toggles search mode for newbies.</span></li>';
}

if (allowed('exterminate')) {
    echo '<li><a href="'.DOMAIN.'exterminate">Exterminate trolls by phrase</a>  — <span class="unimportant">A last measure.</span></li>';
}

if (allowed('watch')) {
    echo '<li><a href="'.DOMAIN.'watch">Watch</a>  — <span class="unimportant">Watch stuff happen.</span></li>';
}

if (allowed('manage_defcon')) {
    echo '<li><a href="'.DOMAIN.'defcon">Manage DEFCON</a>  — <span class="unimportant">Do not treat this lightly.</span></li>';
}

if (allowed('undelete')) {
    echo '<li><a href="'.DOMAIN.'deleted_topics">Deleted topics</a>  — <span class="unimportant">Browse deleted topics.</span></li>';
}

if (allowed('open_modlog')) {
    echo '<li><a href="'.DOMAIN.'modlog">Modlog</a>  — <span class="unimportant">Browse the modlog.</span></li>';
}

if ($administrator) {
    echo '<li><a href="'.DOMAIN.'permissions">Permissions</a>  — <span class="unimportant">Permissions overview.</span></li>';
}

if ($mod_section) {
    echo '</ul></div>';
}
dummy_form();
require 'includes/footer.php';
?>
