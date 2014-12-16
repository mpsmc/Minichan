<?php
require('includes/header.php');
?>
<script>
$(document).ready(function() {
	var w = $(window).width();
	var h = $(window).height();
	
	function getRandomInt (min, max) {
		return Math.floor(Math.random() * (max - min + 1)) + min;
	}
	
	setInterval(function() {
		var elem = $("<div style='width:1px;height:1px;background-color:black;position:fixed;cursor:default'></div>");
		elem.css('left', getRandomInt(0, w) + 'px');
		elem.css('top', getRandomInt(0, h) + 'px');
		$('body').append(elem);
	}, 100);
});
</script>
<?php
require('includes/footer.php');