import { shortcut } from './shortcut.js';
window.shortcut = shortcut;

$(function() {
	var youtubeEmbedHtml = '<div style="display: none; width:560px" class="video wrapper c"><iframe width="560" height="315" src="https://www.youtube.com/embed/{vid}?autoplay=1&start={start}" frameborder="0" allowfullscreen></iframe></div>';
	var vimeoEmbedHtml = '<div style="display: none;" class="video wrapper c"><object width="512" height="294"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="https://vimeo.com/moogaloop.swf?clip_id={vid}&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=1" /><embed src="https://vimeo.com/moogaloop.swf?clip_id={vid}&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;fullscreen=1&amp;autoplay=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="512" height="294"></embed></object></div>';
	var vidmeEmbedHtml = '<div style="display: none; width: 640px" class="video wrapper c"><iframe src="https://vid.me/e/{vid}?autoplay=1" width="640" height="360" frameborder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen scrolling="no"></iframe></div>';

	function transformVideoLink(vid, start, html) {
		var $this = $(this);
		var $play = $("<a href='#'>[play]</a>");

		var $video = $(html.replace(/\{vid\}/g, vid).replace(/\{start\}/g, start));
		var active = false;

		$play.click(function(e) {
			e.preventDefault();
			if(active) return;
			active = true;

			if($video.is(':visible')) {
				$play.text('[play]');
				$video.slideUp(function() {
					$video.remove();
					active = false;
				});
			}else{
				$play.text('[close]');
				$this.parent().after($video);
				$video.slideDown(function() {
					active = false;
				});
			}
		});

		$this.after($play);
		$this.after(" ");
	}

	// This can probably do a lot more effecient...
	function getTimeFromYTUrl(elem) {
		var parts = elem.search.substring(1).split('&');
		var time = null;
		for(var i = 0; i < parts.length; i++) {
			var pair = parts[i].split('=');
			if(pair[0] == "t") {
				time = decodeURIComponent(pair[1]);
				break;
			}
		}

		if(time == null) {
			var match = /^#t=([0-9ms]+)/.exec(elem.hash);
			if(match) time = match[1];
		}

		if(time == null) return 0;
		if(time.match(/^\d*$/)) return time;
		var regexp = /([0-9]+|[a-z]+)/g;
		var match = regexp.exec(time);
		var actual = 0;
		var carry = null;

		var weights = {
			'd': 86400,
			'h': 3600,
			'm': 60,
			's': 1
		};

		while(match != null) {
			if(!isNaN(match[0])) {
				carry = match[0];
			}else if(carry !== null) {
				var weight = weights[match[0]];
				if(weight) {
					actual += carry * weight;
					carry = null;
				}
			}

			match = regexp.exec(time);
		}

		return actual;
	}

	$("div.body a").each(function() {
		var $this = $(this);
		if(this.hostname.match(/(www\.)?youtube(-nocookie)?.com/)) {
			if(this.pathname.match(/^\/watch/)) {
				var parts = this.search.substring(1).split('&');
				var vid = null;
				for(var i = 0; i < parts.length; i++) {
					var pair = parts[i].split('=');
					if(pair[0] == "v") {
						transformVideoLink.call(this, decodeURIComponent(pair[1]), getTimeFromYTUrl(this), youtubeEmbedHtml);
						break;
					}
				}
			}
		}else if(this.hostname.match(/(www\.)?youtu.be/)) {
			var match = /^\/([^\/?#]+)/.exec(this.pathname);
			if(match) {
				transformVideoLink.call(this, match[1], getTimeFromYTUrl(this), youtubeEmbedHtml);
			}
		}else if(this.hostname.match(/(www\.)?vimeo.com/)) {
			var match = /^\/([0-9]+)/.exec(this.pathname);
			if(match) {
				transformVideoLink.call(this, match[1], 0, vimeoEmbedHtml);
			}
		}else if(this.hostname.match(/(www\.)?vid.me/)) {
			var match = /^\/([a-zA-Z0-9]+)/.exec(this.pathname);
			if(match) {
				transformVideoLink.call(this, match[1], 0, vidmeEmbedHtml);
			}
		}
	});
});

$(function() {
	shortcut.add("Ctrl+B", function() {
		window.location = "/bumps";
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

	function createShortcut(shortcutSpec, tag) {
		shortcut.add(shortcutSpec, function() {
			wrapText($this, '['+tag+']', '[/'+tag+']');
		}, {
			target: $this[0]
		});
	}

	createBB('<strong>b</strong>', 'b');
	createBB('<em>i</em>', 'i');
	createBB('<u>u</u>', 'u');
	createBB('<s>s</s>', 's');
	createBB('spoiler', 'sp');
	createBB('border', 'border');
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

	createShortcut("Ctrl+B", "b");
	createShortcut("Ctrl+I", "i");
	createShortcut("Ctrl+U", "u");
    createShortcut("Ctrl+S", "s");
}

function wrapText(elem, openTag, closeTag) {
	var textArea = $(elem);

	var len = textArea.val().length;
	var start = textArea[0].selectionStart;
	var end = textArea[0].selectionEnd;
	var selectedText = textArea.val().substring(start, end);
	var replacement = openTag + selectedText + closeTag;

	textArea.val(textArea.val().substring(0, start) + replacement + textArea.val().substring(end, len));

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
