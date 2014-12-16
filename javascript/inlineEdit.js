$(document).ready(function() {
	$(".editButton").click(function() {
		var $this = $(this);
		if($this.hasClass("editOP")) {
			alert("OP");
		}else{
			alert("Reply");
		}
		return false;
	});
});