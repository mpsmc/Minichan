var num_poll_options;

function showPoll(elem) {
	if($("#topic_poll").css("display") == "none") {
		$(elem).text("[-] Poll");
		$("#topic_poll").show();
		$("#poll_option_"+(num_poll_options-1)).focus();
		$("#enable_poll").val(1);
	}else{
		$(elem).text("[+] Poll");
		$("#topic_poll").hide();
		$("#enable_poll").val(0);
	}
}

$(document).ready(function(){
	$(".pollInput").keypress(pollFocus);
	$(".pollInput").focus(pollFocus);
	$(".pollInput").blur(pollFocus);
	num_poll_options = $(".pollInput").length;
	if(!$(".pollInput").eq(0).val()) {
		$("#topic_poll").hide();
		$("#enable_poll").val(0);
		$("#poll_toggle").text("[+] Poll").attr("href", "javascript:void(0)");
	}else{
		$("#poll_toggle").text("[-] Poll").attr("href", "javascript:void(0)");
		$("#enable_poll").val(1);
	}
	pollFocus();
});

function pollFocus() {
	if(num_poll_options>=25) return true;
	var cont = true;
	$(".pollInput").each(function(){
		if(!$(this).val()) cont = false;
	});
	
	if(!cont) return true;
	num_poll_options++;
	var tr = document.createElement("tr");
	var td1 = document.createElement("td");
	var td2 = document.createElement("td");
	var input = document.createElement("input");
	
	if(num_poll_options%2) tr.class='odd';
	td1.innerHTML = "<label for='poll_option_" + num_poll_options + "'>Poll option #"  + num_poll_options + "</label>";
	td1.class = 'minimal';
	
	input.type = 'text';
	input.id = "poll_option_" + num_poll_options;
	input.name = 'polloptions[' + (num_poll_options - 1) + ']';
	input.setAttribute("class", 'pollInput');
	$(input).focus(pollFocus);
	$(input).keypress(pollFocus);
	$(input).blur(pollFocus);
	
	td2.appendChild(input);
	
	tr.appendChild(td1);
	tr.appendChild(td2);
	
	$("#topic_poll").append(tr);
}
