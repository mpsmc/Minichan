<?php
require 'includes/header.php';

if (!allowed('manage_cms')) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

$page_data = array();

if ($_POST['form_sent']) {
    $page_data['url'] = ltrim($_POST['url'], '/');
    $page_data['title'] = $_POST['title'];
    $page_data['content'] = $_POST['content'];
}

if ($_GET['edit']) {
    $erred = false;
    if (!ctype_digit($_GET['edit'])) {
        add_error('Invalid page ID.', true);
    }

    $stmt = $link->db_exec('SELECT url, page_title, content FROM pages WHERE id = %1', $_GET['edit']);
    if ($link->num_rows($stmt) < 1) {
        $page_title = 'Non-existent page';
        add_error('There is no page with that ID.', true);
    }
    if (!$_POST['form_sent']) {
        list($page_data['url'], $page_data['title'], $page_data['content']) = $link->fetch_row($stmt);
    }
    $editing = true;
    $page_title = 'Editing page: <a href="'.DOMAIN.$page_data['url'].'">'.htmlspecialchars($page_data['title']).'</a>';
    $page_data['id'] = $_GET['edit'];
} else { // New page.
    $page_title = 'New page';
    if (!empty($page_data['title'])) {
        $page_title .= ': '.htmlspecialchars($page_data['title']);
    }
}

if ($_POST['post']) {
    // CSRF checking.
    check_token();
    $erred = false;
    if (empty($page_data['url'])) {
        add_error('A path is required.');
    }

    if (!$erred) {
        // Undo the effects of sanitize_for_textarea.
        $page_data['content'] = str_replace('&#47;textarea', '/textarea', $page_data['content']);
        if ($editing) {
            $edit_page = $link->db_exec('UPDATE pages SET url = %1, page_title = %2, content = %3 WHERE id = %4', $page_data['url'], $page_data['title'], $page_data['content'], $page_data['id']);
            $notice = 'Page successfully edited.';
        } else { // New page.
            $add_page = $link->db_exec('INSERT INTO pages (url, page_title, content) VALUES (%1, %2, %3)', $page_data['url'], $page_data['title'], $page_data['content']);
            $notice = 'Page successfully created.';
        }
        redirect($notice, $page_data['url']);
    }
}

print_errors();

if ($_POST['preview'] && !empty($page_data['content']) && check_token()) {
    echo '<h3 id="preview">Preview</h3><div class="body standalone"> <h2>'.$page_data['title'].'</h2>'.$page_data['content'].'</div>';
}
?>
<form action="" method="post">
	<?php csrf_token() ?>
	<div class="noscreen">
		<input type="hidden" name="form_sent" value="1" />
	</div>
	<div class="row">	
		<label for="url">Path</label>
		<input id="url" name="url" value="<?php echo htmlspecialchars($page_data['url']) ?>" />
	</div>
	<div class="row">	
		<label for="title">Page title</label>
		<input id="title" name="title" value="<?php echo htmlspecialchars($page_data['title']) ?>" />
	</div>
	<div class="row">	
		 <textarea id="content" name="content" cols="120" rows="18"><?php echo sanitize_for_textarea($page_data['content']) ?></textarea>
		 <p>Use pure HTML.</p>
	</div>
	<div class="row">
			<input type="submit" name="preview" value="Preview" class="inline" /> 
			<input type="submit" name="post" value="Submit" class="inline">
	</div>
</form>
<?php
require 'includes/footer.php';
?>