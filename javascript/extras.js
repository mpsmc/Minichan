/* play_video: Stolen from authorizedclone.com */
function play_video ( provider, media_ID, element, record_class, record_ID ) {
        my_ID = record_class + '-' + record_ID + '-media-' + media_ID;
        if ( jQuery(element).html() == 'play' ) {
                jQuery(element).html('close');
        } else {
                jQuery(element).html('play');
                jQuery('#' + my_ID).slideUp();
                return false;
        }
        video_player_html = '';
        if ( provider == 'youtube' ) {
                video_player_html = '<div id="' + my_ID + '" style="display: none;" class="video wrapper c"><object width="500" height="405"><param name="movie" value="https://www.youtube-nocookie.com/v/' + media_ID + '&amp;hl=en_US&amp;fs=1&amp;border=1&amp;autoplay=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="https://www.youtube-nocookie.com/v/' + media_ID + '&amp;hl=en_US&amp;fs=1&amp;border=1&amp;autoplay=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="500" height="405"></embed></object><a href="https://www.youtube.com/watch?v=' + media_ID + '" class="youtube_alternate"><img src="https://img.youtube.com/vi/' + media_ID + '/0.jpg" width="480" height="360" alt="Video" /></a></div>';
        } else if ( provider == 'vimeo' ) {
                video_player_html = '<div id="' + my_ID + '" style="display: none;" class="video wrapper c"><object width="512" height="294"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="https://vimeo.com/moogaloop.swf?clip_id=' + media_ID + '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=1" /><embed src="https://vimeo.com/moogaloop.swf?clip_id=' + media_ID + '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;fullscreen=1&amp;autoplay=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="512" height="294"></embed></object></div>';
        }
        jQuery(element).parent().after(video_player_html + "\n");
        jQuery('#' + my_ID).slideDown();
}

var shortcut={'all_shortcuts':{},'add':function(shortcut_combination,callback,opt){var default_options={'type':'keydown','propagate':false,'disable_in_input':false,'target':document,'keycode':false}
if(!opt)opt=default_options;else{for(var dfo in default_options){if(typeof opt[dfo]=='undefined')opt[dfo]=default_options[dfo];}}
var ele=opt.target;if(typeof opt.target=='string')ele=document.getElementById(opt.target);var ths=this;shortcut_combination=shortcut_combination.toLowerCase();var func=function(e){e=e||window.event;if(opt['disable_in_input']){var element;if(e.target)element=e.target;else if(e.srcElement)element=e.srcElement;if(element.nodeType==3)element=element.parentNode;if(element.tagName=='INPUT'||element.tagName=='TEXTAREA')return;}
if(e.keyCode)code=e.keyCode;else if(e.which)code=e.which;var character=String.fromCharCode(code).toLowerCase();if(code==188)character=",";if(code==190)character=".";var keys=shortcut_combination.split("+");var kp=0;var shift_nums={"`":"~","1":"!","2":"@","3":"#","4":"$","5":"%","6":"^","7":"&","8":"*","9":"(","0":")","-":"_","=":"+",";":":","'":"\"",",":"<",".":">","/":"?","\\":"|"}
var special_keys={'esc':27,'escape':27,'tab':9,'space':32,'return':13,'enter':13,'backspace':8,'scrolllock':145,'scroll_lock':145,'scroll':145,'capslock':20,'caps_lock':20,'caps':20,'numlock':144,'num_lock':144,'num':144,'pause':19,'break':19,'insert':45,'home':36,'delete':46,'end':35,'pageup':33,'page_up':33,'pu':33,'pagedown':34,'page_down':34,'pd':34,'left':37,'up':38,'right':39,'down':40,'f1':112,'f2':113,'f3':114,'f4':115,'f5':116,'f6':117,'f7':118,'f8':119,'f9':120,'f10':121,'f11':122,'f12':123}
var modifiers={shift:{wanted:false,pressed:false},ctrl:{wanted:false,pressed:false},alt:{wanted:false,pressed:false},meta:{wanted:false,pressed:false}};if(e.ctrlKey)modifiers.ctrl.pressed=true;if(e.shiftKey)modifiers.shift.pressed=true;if(e.altKey)modifiers.alt.pressed=true;if(e.metaKey)modifiers.meta.pressed=true;for(var i=0;k=keys[i],i<keys.length;i++){if(k=='ctrl'||k=='control'){kp++;modifiers.ctrl.wanted=true;}else if(k=='shift'){kp++;modifiers.shift.wanted=true;}else if(k=='alt'){kp++;modifiers.alt.wanted=true;}else if(k=='meta'){kp++;modifiers.meta.wanted=true;}else if(k.length>1){if(special_keys[k]==code)kp++;}else if(opt['keycode']){if(opt['keycode']==code)kp++;}else{if(character==k)kp++;else{if(shift_nums[character]&&e.shiftKey){character=shift_nums[character];if(character==k)kp++;}}}}
if(kp==keys.length&&modifiers.ctrl.pressed==modifiers.ctrl.wanted&&modifiers.shift.pressed==modifiers.shift.wanted&&modifiers.alt.pressed==modifiers.alt.wanted&&modifiers.meta.pressed==modifiers.meta.wanted){callback(e);if(!opt['propagate']){e.cancelBubble=true;e.returnValue=false;if(e.stopPropagation){e.stopPropagation();e.preventDefault();}
return false;}}}
this.all_shortcuts[shortcut_combination]={'callback':func,'target':ele,'event':opt['type']};if(ele.addEventListener)ele.addEventListener(opt['type'],func,false);else if(ele.attachEvent)ele.attachEvent('on'+opt['type'],func);else ele['on'+opt['type']]=func;},'remove':function(shortcut_combination){shortcut_combination=shortcut_combination.toLowerCase();var binding=this.all_shortcuts[shortcut_combination];delete(this.all_shortcuts[shortcut_combination])
if(!binding)return;var type=binding['event'];var ele=binding['target'];var callback=binding['callback'];if(ele.detachEvent)ele.detachEvent('on'+type,callback);else if(ele.removeEventListener)ele.removeEventListener(type,callback,false);else ele['on'+type]=false;}}

$(function() {
	shortcut.add("Ctrl+B",function() {
		window.location = "/bumps";
	}, {
		'type':'keydown',
		'propagate':false,
		'target':document
	});
});

$(function() {
	$(".markup_editor").each(createMarkupEditor);
});

function createMarkupEditor() {
	var $this = $(this);
	
	var $bar = $("<span />");
	$this.before($bar);
	
	function createButton(html, cb) {
		var $elem = $("<h3 />");
		$elem.css('display', 'inline-block');
		$elem.css('cursor', 'pointer');
		$elem.css('margin-right', '3px');
		$elem.html(html);
		$elem.click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			cb();
			return false;
		});
		$bar.append($elem);
	}
	
	function createBB(html, tag) {
		createButton('['+html+']', function() {
			wrapText($this, '['+tag+']', '[/'+tag+']');
		});
	}
	
	createBB('<strong>b</strong>', 'b');
	createBB('<em>i</em>', 'i');
	createBB('<u>u</u>', 'u');
	createBB('<s>s</s>', 's');
	createBB('spoiler', 'sp');
	createButton('[code]', function() {
		wrapText($this, '[code]\n', '\n[/code]');
	});
	createButton('[url]', function() {
		var url = prompt("What URL do you want to use?");
		var start = '', end = '';
		if(url) {
			start = '[url='+url+']';
			end = '[/url]';
		}
		wrapText($this, start, end);
	});
}

function wrapText(elem, openTag, closeTag) {
	var textArea = $(elem);

	var len = textArea.val().length;
	var start = textArea[0].selectionStart;
	var end = textArea[0].selectionEnd;
	var selectedText = textArea.val().substring(start, end);
	var replacement = openTag + selectedText + closeTag;

	var event = document.createEvent('TextEvent');
	event.initTextEvent('textInput', true, true, null, replacement);
	textArea.get(0).dispatchEvent(event);

	setSelectionRange(textArea[0], end+openTag.length, end+openTag.length);
}

function setSelectionRange(input, selectionStart, selectionEnd) {
	if (input.setSelectionRange) {
		input.focus();
		input.setSelectionRange(selectionStart, selectionEnd);
	} else if (input.createTextRange) {
		var range = input.createTextRange();
		range.collapse(true);
		range.moveEnd('character', selectionEnd);
		range.moveStart('character', selectionStart);
		range.select();
	}
}