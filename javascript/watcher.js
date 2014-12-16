function grabData(){
	var domain = "http://" + document.domain;
	
	$.getJSON("watch.php?JSON", function(data){
		$(".displayed_topics").remove();
		var flip = false;
		$.each(data.topics, function(i, elem){
			var row = create_row('displayed_topics' + ((flip) ? ' odd' : ''), [
				"<a href='"+domain+"/topic/"+elem.id+"'>"+elem.headline+"</a>" + ((elem.locked==1)?'<a style="float: right"><small>[L]</small></a>':''),
				elem.body,
				"<a href='"+domain+"/profile/"+elem.author+"'>"+elem.name+"</a>",
				"<a href='"+domain+"/IP_address/"+elem.author_ip+"'>"+elem.author_ip+"</a>",
				elem.replies,
				elem.time
			]);
			flip = !flip;
			$("#topics").append(row);
		});
		flip = false;
		$(".displayed_replies").remove();
		$.each(data.replies, function(i, elem){
			var row = create_row('displayed_replies' + ((flip) ? ' odd' : ''), [
				"<a href='"+domain+"/topic/"+elem.parent_id+"#reply_"+elem.id+"'>"+elem.headline+"</a>",
				elem.body,
				"<a href='"+domain+"/profile/"+elem.author+"'>"+elem.name+"</a>",
				"<a href='"+domain+"/IP_address/"+elem.author_ip+"'>"+elem.author_ip+"</a>",
				elem.time
			]);
			flip = !flip;
			$("#replies").append(row);
		});
		
		flip = false;
		$(".displayed_uids").remove();
		$.each(data.uids, function(i, elem){
			var row = create_row('displayed_uids' + ((flip) ? ' odd' : ''), [
				"<a href='"+domain+"/profile/"+elem.uid+"'>"+elem.uid+"</a>",
				elem.posts,
				"<a href='"+domain+"/IP_address/"+elem.ip_address+"'>"+elem.ip_address+"</a>",
				elem.last_seen,
				elem.first_seen
			]);
			flip = !flip;
			$("#uids").append(row);
		});
		
		
	});
	
	setTimeout("grabData();", 5000);
	
}



function create_row(setClass,data){
	var output = "<tr class='" + setClass + "'>";
	for(i=0; i<data.length; i++){
		output = output + "<td>" + data[i] + "</td>";
	}
	output = output + "</tr>";
	return output;
}

$(function(){
	grabData();	
});