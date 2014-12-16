<?php
include("includes/header.php");
if(!$administrator) die("Get fucked.");

$counter = 0;
foreach(glob("img/*.delete") as $image) {
	unlink($image);
	echo "$image <br />";	
	$counter++;
}
echo $counter;

$link->db_exec("SELECT file_name, topic_id, reply_id, md5 FROM images");

$images = 0;
/*
while(list($filename, $topicid, $replyid) = $link->fetch_row()) {
	if(($replyid < 407000 && $replyid != NULL) || ($topicid < 27000 && $topicid != NULL)) {
		if(file_exists("img/$filename") && $filename) {
			echo "$filename $topicid $replyid<br />";
			rename("img/".$filename, "img/".$filename.".delete");
			$images++;
		}
	}
}

*/
echo $images;
