<?php
require 'includes/header.php';

if (!allowed('manage_cms')) {
    add_error(MESSAGE_PAGE_ACCESS_DENIED, true);
}

$page_title = 'Content management';
?>
<p>This feature can be used to edit and create non-dynamic pages.</p>
<?php
$table = new table();
$columns = array(
    'Path',
    'Title',
    'Content snippet',
    'Edit',
    'Delete',
);
$table->define_columns($columns, 'Content snippet');
$table->add_td_class('Content snippet', 'snippet');
$result = $link->db_exec('SELECT id, url, page_title, content FROM pages');

while ($row = $link->fetch_assoc($result)) {
    $values = array(
        '<a href="'.DOMAIN.$row['url'].'">'.$row['url'].'</a>',
        $row['page_title'],
        snippet($row['content']),
        '<a href="'.DOMAIN.'edit_page/'.$row['id'].'">&#9998;</a>',
        '<a href="'.DOMAIN.'delete_page/'.$row['id'].'">&#10008;</a>',
    );
    $table->row($values);
}
echo $table->output('pages');
?>
<ul class="menu">
	<li><a href="<?php echo DOMAIN; ?>new_page">New page</a></li>
</ul>
<?php
require 'includes/footer.php';
?>