
function replyDivToId(div) {
	if (!div) return null;
	
	var regexExtractId = /[0-9]+/;
	var match = regexExtractId.exec($(div).attr('id'));
	if (match) {
		return match[0];
	} else {
		return null;
	}
}

function replyIdToHash(id) {
	return "#reply_" + id;
}

function hashToReplyId() {
	if (location.hash && document.getElementById(location.hash.substring(1)) && location.hash.indexOf('reply_') != -1) {
		return location.hash.substring(7);
	} else {
		return null;
	}
}

/* Browse replies. */
var replyCursor = (function() {

	var replyIds; // Use ID array for single stepping.
	var replies; // Use divs for paging.

	function highlightReplyAndReplaceHash(id) {
		// Navigation overrides any snapback operations.
		// This avoids snapbacks spamming the history.
		if (history.state && history.state.length) {
			history.go(-1 * history.state.length);
		}
		highlightReply(id);
		
		// When scrolling, the hash needs updating, but never the history.
		newHash = replyIdToHash(id);
		if (window.location.hash !== newHash) {
			window.location.replace(newHash);
		}
	}

	$(function() { // onDomReady callback.
		replyIds = $('#body_wrapper h3.c')
			.map(function(i, el) {
				return /[0-9]+/.exec(el.id);
			});

		replies = document.querySelectorAll("#body_wrapper h3");
	});

	function _replyArrayIndex() {
		var replyEl = $('#body_wrapper h3 + .body.highlighted');
		return $.inArray(replyDivToId(replyEl), replyIds);
	}

	function _nearestElement(increaseY) {
		var leeway = window.innerHeight - 80;
		var currentY = $(window).scrollTop(), targetY, candidate;
		for (var i=0; i<replies.length; i++) {
			candidate = replies[increaseY? i : replies.length - i - 1];
			targetY = $(candidate).offset().top;
			
			if ((increaseY && (targetY > currentY + leeway)) ||
				(!increaseY && (targetY < currentY - leeway))) {
				
				return candidate;
			}
		}

		if (increaseY) {
			return replies[replies.length - 1];
		} else {
			return replies[0];
		}
	}

	// Highlight the original post.
	function highlightOp() {
		$('.highlighted').each(function(i, el) {
			el.className = el.className.replace(/highlighted/, '');
		});
		$('#body_wrapper div.body')[0].className += ' highlighted';
		window.location.replace('#');
	}
	
	function _top() {
		highlightOp();
		window.scroll(0, 0);
	}

	// Highlight the last reply and goto the bottom of the page.
	function _bottom() {
		if (replyIds.length > 0) {
			highlightReplyAndReplaceHash(replyIds[replyIds.length - 1]);
		} else { // Handle corner case of no replies.
			highlightOp();
		}
		window.scroll(0, $(document).height());
	}

	function previous() {
		var current = _replyArrayIndex();
		if (current < 0 || (current === 0 || replyIds.length === 0)) {
			_top();
		} else {
			highlightReplyAndReplaceHash(replyIds[current - 1]);
		}
	}

	function next() {
		var current = _replyArrayIndex();
		if (current < 0) {
			if (replyIds.length === 0) {
				_top();
			} else {
				highlightReplyAndReplaceHash(replyIds[0]);
			}
		} else if ((current + 1) >= replyIds.length) {
			_bottom();
		} else {
			highlightReplyAndReplaceHash(replyIds[current + 1]);
		}
	}

	function previousScreen() {
		var replyId = replyDivToId(_nearestElement(false));
		if (replyId) {		  
			highlightReplyAndReplaceHash(replyId);
		} else {
			_top();
		}
	}

	function nextScreen() {
		var replyId = replyDivToId(_nearestElement(true));
		if (replyId) {			  
			highlightReplyAndReplaceHash(replyId);
		} else {
			_bottom();
		}
	}

	/*
	 * While the previous/next functions are relative to the currently active reply,
	 * the previousScreen/nextScreen functions are relative to the visible reply.
	 */
	return {
		previous: previous,
		next: next,
		previousScreen: previousScreen,
		nextScreen: nextScreen
	};
}());

/* Given a reply ID, highlight it. */
function highlightReply(id) {
	var newHash;
	var contentEl = document.getElementById('reply_' + id);
	var boxEl = document.getElementById('reply_box_' + id);

	var divs = document.getElementsByTagName('div');
	for (var i = 0; i < divs.length; i++) {
		if (divs[i].className.indexOf('body') != -1)
			divs[i].className = divs[i].className.replace(/highlighted/, '');
	}
	if (boxEl && contentEl) {
		boxEl.className += ' highlighted';
		window.scroll(0, contentEl.offsetTop);
	}
		
	if($("#reply_button_"+id).length > 0){
		$("#reply_"+id).show();
		$("#reply_box_"+id).show();
		$("#reply_button_"+id).text("[hide]");
		document.location.hash = 'reply_' + id + '_info';
		return false;
	}
	return true;
}

function visitNthCitation(n) {
	var replyEl = document.querySelector('#body_wrapper h3 + .body.highlighted');
	if (replyEl) {
		var citations = replyEl.querySelectorAll('a.cite_reply');
		if (citations.length > n) {
			citations[n].click();
		}
	}
}

function updateSnapbackLink(state) {
	var link = document.getElementById('snapback_link'), arrowhead, numeric;
	if (link == null) { // create link
		link = document.createElement("a"),
			arrowhead = document.createElement("strong"),
			numeric = document.createElement("span");

		link.id = 'snapback_link';
		link.style.display = 'none';
		link.classList.add('help_cursor');
		link.title = 'Click me to snap back!';
		
		link.addEventListener('click', function(evt) {
			window.history.back();
		});
		
		arrowhead.innerHTML = '↕';
		numeric.innerHTML = '&nbsp;';
		link.appendChild(arrowhead);
		link.appendChild(numeric);
		document.body.appendChild(link);
	} else {
		arrowhead = link.querySelector('strong');
		numeric = link.querySelector('span');
	}

	if (!history.state || !history.state.length) {
		link.style.display = 'none';
	} else if (history.state.length === 1) {
		numeric.innerHTML = '&nbsp;';
		link.style.display = 'inline';
	} else if (history.state.length > 1) {
		numeric.innerHTML = history.state.length;
		link.style.display = 'inline';		
	}

	highlightReply(hashToReplyId());
	return true;
}

function onBeforeFollowCitation(event, replyId, citationId) {
	var newState;
	if (history.state) {
		if (history.state && history.state.lastId === replyId) {
			// Avoid adding the same anchor again and again.
		} else {
			newState = {
				length: history.state.length + 1,
				lastId: replyId
			};
			history.pushState(newState, "", replyIdToHash(citationId));
		}
	} else {
		newState = {
			length: 1,
			lastId: replyId
		};
		history.pushState(newState, "", replyIdToHash(citationId));		
	}

	updateSnapbackLink(newState);
	return false;
}
