<?php
chdir("..");
require("includes/header.php");
update_activity('image_macro');
$additional_head .= '<script type="text/javascript" src="' . DOMAIN . 'javascript/macro/BlobBuilder.js"></script>';
$additional_head .= '<script type="text/javascript" src="' . DOMAIN . 'javascript/macro/canvas-toBlob.js"></script>';
$additional_head .= '<script type="text/javascript" src="' . DOMAIN . 'javascript/macro/FileSaver.js"></script>';
$additional_head .= '<script type="text/javascript" src="' . DOMAIN . 'javascript/macro/getimagedata.min.js"></script>';
$additional_head .= '<script type="text/javascript" src="' . DOMAIN . 'javascript/macro/macro-main.js?2"></script>';

$page_title = "Image macro generator";
?>
<div id="nosupport">
	<b>Your browser does not support this! It lacks the javascript functionality required.</b><br />
	You could probably use <a href="http://memecrunch.com/generator/custom">memecrunch.com</a> instead.
</div>
<div id="support" style="display: none">
	<div id="step1">
		<b>Base image file:</b> <input type="file" id="base_image" onchange="step1(this.files[0])" />
		<b>Or URL to use:</b><br /><input type="input" id="base_img_url" size="20" class="inline" /><input type="button" id="base_img_url_send" onclick="step1($('#base_img_url').val())" value="Go" class="inline" />
	</div>
	<div id="step2" style="display: none">
		<b>Add text and move/size it with your mouse or <a href="javascript:window.location.reload()">use another image</a></b><br />
		<input type="text" id="new-text" class="inline"><input type="button" id="add-text" value="Add" class="inline" /><br />
		<canvas id="final_image"></canvas>
		<br />
		<input type="button" id="download" value="Download" class="inline" /> <input type="button" id="upload-imgur" value="Upload to imgur" class="inline" /> <input type="text" readonly="readonly" value="Link comes here" id="imgur-link" class='inline' />
	</div>
</div>
<?php
require("includes/footer.php");