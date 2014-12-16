<?php
require('includes/header.php');
force_id();
update_activity('dashboard');
$page_title = 'Dashboard';

// Set defaults.
$defaults = array	
(
	'memorable_name' => '',
	'memorable_password' => '',
	'email' => '',
	'topics_mode' => 0,
	'spoiler_mode' => 0,
	'ostrich_mode' => 0,
	'disable_images' => 0,
	'snippet_length' => 80,
	'image_viewer' => 1,
	'style' => DEFAULT_STYLESHEET
);
$user_config = $defaults;

// These inputs have no simple valid settings.
$text_inputs = array
(
	'memorable_name',
	'memorable_password',
	'email',
	'custom_style'
);

// ...but these do.
$valid_settings = array
(
	'topics_mode' => array('0', '1'),
	'spoiler_mode' => array('0', '1'),
	'ostrich_mode' => array('0', '1'),
	'snippet_length' => array('80', '100', '120', '140', '160'),
	'image_viewer' => array('0', '1', '2'),
	'disable_images' => array('0', '1'),
	'style' => array_merge(explode(";", AVAILABLE_STYLES), array('Custom'))
);

// Get our user's settings from the database.
$stmt = $link->db_exec('SELECT memorable_name, memorable_password, email, spoiler_mode, topics_mode, ostrich_mode, snippet_length, style, custom_style, image_viewer, disable_images FROM user_settings WHERE uid = %1', $_SESSION['UID']);
list($user_config_db['memorable_name'], $user_config_db['memorable_password'], $user_config_db['email'], $user_config_db['spoiler_mode'], $user_config_db['topics_mode'], $user_config_db['ostrich_mode'], $user_config_db['snippet_length'], $user_config_db['style'], $user_config_db['custom_style'], $user_config_db['image_viewer'], $user_config_db['disable_images']) = $link->fetch_row($stmt);

// If the values were set in the database, overwrite the defaults.
foreach($user_config_db as $key => $value) {
	if( ! empty($key)) {
		$user_config[$key] = $value;
	}
}

if($_POST['form_sent']) {
	// CSRF checking.
	check_token();
	// Unticked checkboxes are not sent by the client, so we need to set them ourselves.
	foreach($defaults as $option => $setting) {
		if( ! array_key_exists($option, $_POST['form'])) {
			$_POST['form'][$option] = $setting;
		}
	}
	
	// Make some specific validations.
	if( ! empty($_POST['form']['memorable_name']) && $_POST['form']['memorable_name'] != $user_config['memorable_name']) {
		// CSRF checking.
		check_token();
		// Check if the name is already being used.
		$stmt = $link->db_exec('SELECT 1 FROM user_settings WHERE LOWER(memorable_name) = LOWER(%1)', $_POST['form']['memorable_name']);

		if($link->num_rows($stmt) > 0) {
			add_error('The memorable name "' . htmlspecialchars($_POST['form']['memorable_name']) . '" is already being used.');
		}
	}
	
	if ( ! empty($_POST['form']['memorable_password']) ){
		$_POST['form']['memorable_password'] = strtolower(md5($_POST['form']['memorable_password']));
	}

	if( ! $erred) {
		// Iterate over every sent form[] value.
		foreach($_POST['form'] as $key => $value) {
			// Check if the settings are valid.
			if($key=="memorable_password" && !$value) continue;
			if( ! in_array($key, $text_inputs) && ( ! array_key_exists($key, $defaults) || ! in_array($value, $valid_settings[$key]) ) ) {
				continue;
			}
			if(strlen($value) > 100) {
				continue;
			}
			
			// If the submitted setting differs from the current setting, update it.
			if($user_config[$key] != $value) {
				// Insert or update!
				
				if ( $key=='style' ){
					$_SESSION['user_style'] = $value;
				}
				
				$link->db_exec('INSERT INTO user_settings (uid, ' . $link->escape($key). ') VALUES (\'' . $link->escape($_SESSION['UID']). '\',  \'' . $link->escape($value) . '\') ON DUPLICATE KEY UPDATE ' . $link->escape($key). ' = \'' . $link->escape($value) . '\'');
				// echo $key . " = " . $value . "\n";
				// Reset the value so it displays correctly on this page load.
				$user_config[$key] = $value;
				
				// Text inputs never need to be set as cookies.
				if(!in_array($key, $text_inputs)) {
					setcookie($key, $value, $_SERVER['REQUEST_TIME'] + 315569260, '/', COOKIE_DOMAIN);
				}
			}
		}
	}
	$_SESSION['notice'] = 'Settings updated';
}



print_errors();
?>
<form action="" method="post">
	<?php csrf_token() ?>
	<div>
		<label class="common" for="memorable_name">Memorable name:</label>
		<input type="text" id="memorable_name" name="form[memorable_name]" class="inline" value="<?php echo htmlspecialchars($user_config['memorable_name']) ?>" maxlength="100" />
	</div>
	<div>
		<label class="common" for="memorable_password">Memorable password:</label>
		<input type="password" class="inline" id="memorable_password" name="form[memorable_password]" maxlength="100" /> <?php if($user_config['memorable_password']!="") ?>
		
		<p class="caption">This information can be used to more easily <a href="<?php echo DOMAIN; ?>restore_ID">restore your ID</a>. Password is optional, but recommended.</p>
	</div>
	<div class="row">
		<label class="common" for="e-mail">E-mail address:</label>
		<input type="text" id="e-mail" name="form[email]" class="inline" value="<?php echo htmlspecialchars($user_config['email']) ?>"  size="35" maxlength="100" />
		
		<p class="caption">Used to recover your internal ID <a href="<?php echo DOMAIN; ?>recover_ID_by_email">via e-mail</a>.</p>
	</div>
	<div class="row">
		<label class="common" for="topics_mode" class="inline">Sort topics by:</label>
		<select id="topics_mode" name="form[topics_mode]" class="inline">
			<option value="0"<?php if($user_config['topics_mode'] == 0) echo ' selected' ?>>Last post (default)</option>
			<option value="1"<?php if($user_config['topics_mode'] == 1) echo ' selected' ?>>Date created</option>
		</select>
	</div>
	<div class="row">
		<label class="common" for="style" class="inline">Stylesheet:</label>
		<select id="style" name="form[style]" class="inline">
        <?php
		$_AVAILABLE_STYLES = explode(";", AVAILABLE_STYLES);
		$_AVAILABLE_STYLES[] = 'Custom';
		if(!$user_config['style']) $user_config['style'] = DEFAULT_STYLESHEET;
		foreach($_AVAILABLE_STYLES as $style){
			if(!file_exists(SITE_ROOT . "/style/" . $style . ".css") && $style != "Custom") continue;
			echo "<option value='".$style."'". (($user_config['style']==$style) ? ' selected' : '') .  '>' . htmlentities(ucfirst(strtolower($style))) . '</option>';
		}
		?>
		</select>
	</div>
	<div class="row">
		<label class="common" for="custom_style" calss="inline">Custom Stylesheet:</label>
		<input type="text" name="form[custom_style]" value="<?php echo htmlspecialchars($user_config['custom_style']) ?>" maxlength="255" class="inline" />
		<p class="caption">When the Stylesheet above is set to Custom, the CSS from this URL is loaded.</p>
	</div>
    <div class="row">
    	<label class="common" for="image_viewer" class="inline">Image viewer:</label>
        <select id="image_viewer" name="form[image_viewer]" class="inline">
        <option value="1"<?php if($user_config['image_viewer'] == 1) echo ' selected' ?>>On</option>
        <option value="0"<?php if($user_config['image_viewer'] == 0) echo ' selected' ?>>Off (Current tab)</option>
		<option value="2"<?php if($user_config['image_viewer'] == 2) echo ' selected' ?>>Off (New tab)</option>
        </select>
    </div>
	<div class="row">
		<label class="common" for="snippet_length" class="inline">Snippet length in characters:</label>
		<select id="snippet_length" name="form[snippet_length]" class="inline">
			<option value="80"<?php if($user_config['snippet_length'] == 0) echo ' selected' ?>>80 (default)</option>
			<option value="100"<?php if($user_config['snippet_length'] == 100) echo ' selected' ?>>100</option>
			<option value="120"<?php if($user_config['snippet_length'] == 120) echo ' selected' ?>>120</option>
			<option value="140"<?php if($user_config['snippet_length'] == 140) echo ' selected' ?>>140</option>
			<option value="160"<?php if($user_config['snippet_length'] == 160) echo ' selected' ?>>160</option>
		</select>
		<p class="caption"></p>
	</div>
	<div class="row">
		<label class="common" for="spoiler_mode">Spoiler mode</label>
		<input type="checkbox" id="spoiler_mode" name="form[spoiler_mode]" value="1" class="inline"<?php if($user_config['spoiler_mode'] == 1) echo ' checked="checked"' ?> />
		<p class="caption">When enabled, snippets of the bodies will show in the topic list. Not recommended unless you have a very high-resolution screen.</p>
	</div>
	<div class="row">
		<label class="common" for="ostrich_mode">Ostrich mode</label>
		<input type="checkbox" id="ostrich_mode" name="form[ostrich_mode]" value="1" class="inline"<?php if($user_config['ostrich_mode'] == 1) echo ' checked="checked"' ?> />
		
		<p class="caption">When enabled, any topic or reply that contains a phrase from your <a href="<?php echo DOMAIN; ?>edit_ignore_list">ignore list</a> will be hidden.</p>
	</div>
	<div class="row">
		<label class="common" for="disable_images">Disable images</label>
		<input type="checkbox" id="disable_images" name="form[disable_images]" value="1" class="inline"<?php if($user_config['disable_images'] == 1) echo ' checked="checked"' ?> />
		
		<p class="caption">When enabled, images will not be shown.</p>
	</div>
	<div class="row">
		<input type="submit" name="form_sent" value="Save settings" />
	</div>
</form>
<?php
require('includes/footer.php');
?>