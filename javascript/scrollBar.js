$(function() {
	var cloned;
	var $o=$("#main_menu_wrapper");
	var $o2=$("#main_menu");
	var h=$o.position().top;
	var $body = $('body');
	var bodyPadding = $body.outerWidth(true) - $body.width();
	
	$(window).scroll(function(e){
		var s=$(window).scrollTop();
		if(s>h){
			if(!cloned) { 
				cloned = $($o).clone().css('visibility', 'hidden').prependTo("body");
				$o.css('position', 'fixed');
				$o.css('width', '100%');
				$o.css('top', '0');
				$o.css('left', bodyPadding/2);
				$o2.css('margin-right', bodyPadding);
			}
		}else{
			if(cloned) {
				cloned.remove();
				cloned = null;
				$o.css('position', '');
				$o.css('width', '');
				$o.css('top', '');
				$o.css('left', '');
				$o2.css('margin-right', '');
			}
		}
	});
});