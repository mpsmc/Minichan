if((navigator.userAgent.toLowerCase().indexOf('chrome') > -1) && (localStorage[toolbarConfig.hide] == undefined)) {
	document.write('<div id="ext_toolbar"></div>');
	showToolbar();
}

function showToolbar() {
	$("body").css({margin: 0, padding: 0});
	$('#ext_toolbar').css({backgroundImage: "url('http://assets.idiomag.com/ext/toolbar/gradient.png')", fontFamily: 'Tahoma, sans-serif', fontSize: '14px', color: '#333', borderBottom: '1px solid #CCC', height: "37px", clear:"none", textAlign:"left"});	
	$('#ext_toolbar').append("<div id=\"ext_toolbar_close\"><span style=\"display:none;\">Close</span></div>");
	$('#ext_toolbar').append("<div id=\"ext_toolbar_install\"><span style=\"display:none;\">Install</span></div>");
	$('#ext_toolbar').append("<div id=\"ext_toolbar_text\">"+toolbarConfig.title+" now has a <span id=\"ext_toolbar_link\">Google Chrome Extension</span>. "+toolbarConfig.slogan+".</div>");
	$('#ext_toolbar_link').css({textDecoration:"underline", color: "#4B689C", cursor:"pointer"});
	$('#ext_toolbar_text').css({padding: "10px 10px 10px 38px", background: "url('http://assets.idiomag.com/ext/toolbar/icon.png') no-repeat 5px 5px", overflow:"hidden", whiteSpace:"nowrap", "-webkit-user-select":"none", cursor: "default", clear:"none"});
	$('#ext_toolbar_close').css({float:"right", background: "url('http://assets.idiomag.com/ext/toolbar/close.png') no-repeat", width:"10px", height:"10px", margin: "14px 10px 0 0", cursor:"pointer", clear:"none"});
	$('#ext_toolbar_install').css({float:"right", background: "url('http://assets.idiomag.com/ext/toolbar/install.png') no-repeat", width:"87px", height:"26px", margin: "6px 10px 0 0", cursor:"pointer", clear:"none"});
	
	if(toolbarConfig.slide) {
		$('#ext_toolbar').hide().slideDown('fast');
	}
	$('#ext_toolbar_close').click(closeToolbar);
	$('#ext_toolbar_install').click(openExtension); // currently set to open directory not direct download
	$('#ext_toolbar_link').click(openExtension); // currently set to open directory not direct download
	
	$("embed").attr("wmode", "opaque");
	$(window).resize(flow); 
	$(window).scroll(move);
	move();
}

function closeToolbar() {
	$('#ext_toolbar').slideUp('fast'); 
	localStorage[toolbarConfig.hide] = true;
}

function openExtension() {
	closeToolbar();
	window.open(toolbarConfig.link);
}

function flow() {
	$('#ext_toolbar_text').width($(window).width() - 170);
	$("#ext_toolbar").css({width:$(window).width()});
}

function move() {
	if($(window).scrollTop() <= 1) {
		$("#ext_toolbar").css({position:"relative", top: 0});
	}
	else {
		$("#ext_toolbar").css({position:"absolute", top: $(window).scrollTop()+"px", width:$(window).width(), zIndex:"100000"});
	}
	flow();
}