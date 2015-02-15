function massDelete(domain, topic_id) {
	
	var output = [];
	
	output[0] = "id=" + topic_id;
	output[1] = "CSRF_token=" + $("#CSRF_token").val();
	
	$(".mass_delete:checked").each(function(i){
		output[i+2] = "reply_id[]=" + $(this).val();
	});
	
	if(!confirm("Are you sure you want to delete " + (output.length-2) + " replies?")) return;
	
	output = output.join("&");
	
	window.location = domain + "action.php?action=mass_delete&" + output;
}

function uploadImage(file) {
	/* Is the file an image? */
	if (!file || !file.type.match(/image.*/)) return;

	/* It is! */
	document.getElementById("uploader").innerHTML = "Uploading...";
	document.getElementById("uploader").href = "";
	var reader = new FileReader(); 
	reader.onloadend = function () {
		console.log('Doin');
		$.ajax({
			url: 'https://api.imgur.com/3/image',
			type: 'post',
			headers: {
				Authorization: 'Client-ID ' + IMGUR_CLIENT_ID
			},
			data: {
				image: reader.result.split(',')[1]
			},
			dataType: 'json',
			success: function(response) {
				if(!response.success) {
					alert(JSON.stringify(response));
					return;
				}
				
				document.getElementById("imageurl").value = response.data.link;
				$("#uploader").remove();
			}
		});
	};
	reader.readAsDataURL(file);
}

$(document).ready(function(){
	if(typeof(FormData) == "undefined") {
		$("#uploader").remove();
	}
	
	$(".mass_delete").change(function(){
		if($(".mass_delete:checked").length > 0){
			$(".do_massDelete").css("display", "block");
		}else{
			$(".do_massDelete").css("display", "none");
		}
		
		$("#massDeleteCount").text($(".mass_delete:checked").length);
	});
	
	var $div = null;
	var hovering = false;
	var divHovering = false;
	
	function scheduleTimeout() {
		setTimeout(function() {
			if($div != null && !hovering && !divHovering) {
				$div.remove();
				$div = null;
			}
		}, 500);
	}
	
	$(".topic_profile_link").hover(function hoverIn(e) {
		hovering = true;
		
		var $this = $(this);
		var uid = $this.attr('href').match(/\/([a-z0-9.]+$)/i)[1];
		var domain = $this.attr('href').match(/(^.+)\/profile/i)[1];
		
		$.getJSON(domain + '/profile.php?uid=' + uid + '&json=1', function(data) {
			if($div != null) {
				$div.remove();
			}
			
			if(!hovering) return;
			
			if(data.mod_notes == "") data.mod_notes = "There's nothing here!";
		
			var pos = $this.position();
			$div = $("<div class='body' style='position:absolute;white-space:pre'></div>");
			$div.css('top', pos.top + $this.height() + 5);
			$div.css('max-width', 400);
			$div.text(data.mod_notes);
			
			$div.hover(function() {
				divHovering = true;
			}, function() {
				divHovering = false;
				scheduleTimeout();
			});
			
			$(document.body).append($div);
			$div.css('left', pos.left - $div.width() + $this.width());
		});
		
		
		
	}, function hoverOut(e) {
		hovering = false;
		scheduleTimeout();
	});
});	

function showDeleted(id, elem){
	if($(elem).text() == "[show]"){
		$("#reply_"+id).show();
		$("#reply_box_"+id).show();
		$(elem).text("[hide]");
	}else{
		$("#reply_"+id).hide();
		$("#reply_box_"+id).hide();
		$(elem).text("[show]");
	}
}

function focusId(id) {
	document.getElementById(id).focus();
	init();
}

function addCommas(nStr){
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

function quickQuote(id, content){
	document.getElementById('quick_reply').style.display = 'block';
	document.getElementById('qr_text').scrollIntoView(true);
	document.getElementById('qr_text').focus();
	document.getElementById('qr_text').value += '@' + addCommas(id) + '\r\n\r\n';
	document.getElementById('qr_text').value += decodeURIComponent(content.replace(/\+/g, '%20')) + "\r\n\r\n" ;
	document.getElementById('qr_text').scrollTop = document.getElementById('qr_text').scrollHeight;
	return false;
}

function quickCite(id){
	document.getElementById('quick_reply').style.display = 'block';
	document.getElementById('qr_text').scrollIntoView(true);
	document.getElementById('qr_text').focus();
	document.getElementById('qr_text').value += '@' + addCommas(id) + '\r\n';
	document.getElementById('qr_text').scrollTop = document.getElementById('qr_text').scrollHeight;
	return false;
}

function checkOrUncheckAllCheckboxes() {
	tmp = document.tinybbs_tmp;
	for (i = 0; i < tmp.elements.length; i++) {
		if (tmp.elements[i].type == 'checkbox') {
			if (tmp.master_checkbox.checked == true)
				tmp.elements[i].checked = true;
			else
				tmp.elements[i].checked = false;
		}
	}
}

function submitDummyForm(theAction, theVariableName, theVariableValue, confirmMessage) {
	if (confirmMessage === false)
		var tmp = true;
	else if (confirmMessage === undefined)
		var tmp = confirm('Really?');
	else
		var tmp = confirm(confirmMessage);
	if (tmp) {
		var form = document.getElementById('dummy_form');
		form.action = theAction;
		form.some_var.name = theVariableName;
		form.some_var.value = theVariableValue;
		form.submit();
	}
	return false;
}

function updateCharactersRemaining(theInputOrTextarea, theElementToUpdate, maxCharacters) {
	tmp = document.getElementById(theElementToUpdate);
	tmp.firstChild.data = maxCharacters - document.getElementById(theInputOrTextarea).value.length;
}

function printCharactersRemaining(idOfTrackerElement, numDefaultCharacters) {
	document.write(' (<STRONG ID="' + idOfTrackerElement + '">' + numDefaultCharacters + '</STRONG> characters left)');
}

function getCookie(c_name) {
	var i, x, y, ARRcookies = document.cookie.split(";");
	for (i = 0; i < ARRcookies.length; i++) {
		x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
		y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
		x = x.replace(/^\s+|\s+$/g, "");
		if (x == c_name) {
			return unescape(y);
		}
	}
}

function setCookie(cookieName,cookieValue,nDays) {
	var today = new Date();
	var expire = new Date();
	if (nDays==null || nDays==0) nDays=1;
	expire.setTime(today.getTime() + 3600000*24*nDays);
	document.cookie = cookieName+"="+escape(cookieValue)
				 + ";expires="+expire.toGMTString();
}

function chooseImage(elem) {
	var url = prompt("Imgur url to attach?");
	if(url == "" || url == null)
		return false;
		
	submitDummyForm(elem.href, 'url', url, false);
	return false;
}

function highlightReplyFromHash() {
	var replyId = hashToReplyId();
	
	if (replyId)
		highlightReply(replyId);
	else if (window.location.hash.indexOf('new') != -1)
		highlightReply(document.getElementById('new_id').value);
	else
		$("div.highlighted").removeClass("highlighted");
}

function init() {
	highlightReplyFromHash();
	window['UID'] = getCookie("UID");
	if(!getCookie('fp')) setCookie('fp', new Fingerprint({canvas: true}).get(), 7);

	$("form").submit(function() {
		var $inputs = $("input, button, textarea", this);
		setTimeout(function() {
			// This must run in the next tick or the input elem is not included in request
			$inputs.prop("disabled", true);
		}, 1);
		setTimeout(function() {
			$inputs.prop("disabled", false);
		}, 3000);
	});
}

$(init);

function submitSetTime(el) {
        var time = prompt("New last bump time?");
        var form = document.getElementById('dummy_form');
        form.action = el.href;
        form.some_var.name = "time";
        form.some_var.value = time;
        form.submit();
        return false;
}

(function(e,t,n){if(typeof module!=="undefined"&&module.exports){module.exports=n()}else if(typeof define==="function"&&define.amd){define(n)}else{t[e]=n()}})("Fingerprint",this,function(){"use strict";var e=function(e){var t,n;t=Array.prototype.forEach;n=Array.prototype.map;this.each=function(e,n,r){if(e===null){return}if(t&&e.forEach===t){e.forEach(n,r)}else if(e.length===+e.length){for(var i=0,s=e.length;i<s;i++){if(n.call(r,e[i],i,e)==={})return}}else{for(var o in e){if(e.hasOwnProperty(o)){if(n.call(r,e[o],o,e)==={})return}}}};this.map=function(e,t,r){var i=[];if(e==null)return i;if(n&&e.map===n)return e.map(t,r);this.each(e,function(e,n,s){i[i.length]=t.call(r,e,n,s)});return i};if(typeof e=="object"){this.hasher=e.hasher;this.screen_resolution=e.screen_resolution;this.canvas=e.canvas;this.ie_activex=e.ie_activex}else if(typeof e=="function"){this.hasher=e}};e.prototype={get:function(){var e=[];e.push(navigator.userAgent);e.push(navigator.language);e.push(screen.colorDepth);if(this.screen_resolution){var t=this.getScreenResolution();if(typeof t!=="undefined"){e.push(this.getScreenResolution().join("x"))}}e.push((new Date).getTimezoneOffset());e.push(this.hasSessionStorage());e.push(this.hasLocalStorage());e.push(!!window.indexedDB);if(document.body){e.push(typeof document.body.addBehavior)}else{e.push(typeof undefined)}e.push(typeof window.openDatabase);e.push(navigator.cpuClass);e.push(navigator.platform);e.push(navigator.doNotTrack);e.push(this.getPluginsString());if(this.canvas&&this.isCanvasSupported()){e.push(this.getCanvasFingerprint())}if(this.hasher){return this.hasher(e.join("###"),31)}else{return this.murmurhash3_32_gc(e.join("###"),31)}},murmurhash3_32_gc:function(e,t){var n,r,i,s,o,u,a,f;n=e.length&3;r=e.length-n;i=t;o=3432918353;u=461845907;f=0;while(f<r){a=e.charCodeAt(f)&255|(e.charCodeAt(++f)&255)<<8|(e.charCodeAt(++f)&255)<<16|(e.charCodeAt(++f)&255)<<24;++f;a=(a&65535)*o+(((a>>>16)*o&65535)<<16)&4294967295;a=a<<15|a>>>17;a=(a&65535)*u+(((a>>>16)*u&65535)<<16)&4294967295;i^=a;i=i<<13|i>>>19;s=(i&65535)*5+(((i>>>16)*5&65535)<<16)&4294967295;i=(s&65535)+27492+(((s>>>16)+58964&65535)<<16)}a=0;switch(n){case 3:a^=(e.charCodeAt(f+2)&255)<<16;case 2:a^=(e.charCodeAt(f+1)&255)<<8;case 1:a^=e.charCodeAt(f)&255;a=(a&65535)*o+(((a>>>16)*o&65535)<<16)&4294967295;a=a<<15|a>>>17;a=(a&65535)*u+(((a>>>16)*u&65535)<<16)&4294967295;i^=a}i^=e.length;i^=i>>>16;i=(i&65535)*2246822507+(((i>>>16)*2246822507&65535)<<16)&4294967295;i^=i>>>13;i=(i&65535)*3266489909+(((i>>>16)*3266489909&65535)<<16)&4294967295;i^=i>>>16;return i>>>0},hasLocalStorage:function(){try{return!!window.localStorage}catch(e){return true}},hasSessionStorage:function(){try{return!!window.sessionStorage}catch(e){return true}},isCanvasSupported:function(){var e=document.createElement("canvas");return!!(e.getContext&&e.getContext("2d"))},isIE:function(){if(navigator.appName==="Microsoft Internet Explorer"){return true}else if(navigator.appName==="Netscape"&&/Trident/.test(navigator.userAgent)){return true}return false},getPluginsString:function(){if(this.isIE()&&this.ie_activex){return this.getIEPluginsString()}else{return this.getRegularPluginsString()}},getRegularPluginsString:function(){return this.map(navigator.plugins,function(e){var t=this.map(e,function(e){return[e.type,e.suffixes].join("~")}).join(",");return[e.name,e.description,t].join("::")},this).join(";")},getIEPluginsString:function(){if(window.ActiveXObject){var e=["ShockwaveFlash.ShockwaveFlash","AcroPDF.PDF","PDF.PdfCtrl","QuickTime.QuickTime","rmocx.RealPlayer G2 Control","rmocx.RealPlayer G2 Control.1","RealPlayer.RealPlayer(tm) ActiveX Control (32-bit)","RealVideo.RealVideo(tm) ActiveX Control (32-bit)","RealPlayer","SWCtl.SWCtl","WMPlayer.OCX","AgControl.AgControl","Skype.Detection"];return this.map(e,function(e){try{new ActiveXObject(e);return e}catch(t){return null}}).join(";")}else{return""}},getScreenResolution:function(){return[screen.height,screen.width]},getCanvasFingerprint:function(){var e=document.createElement("canvas");var t=e.getContext("2d");var n="http://valve.github.io";t.textBaseline="top";t.font="14px 'Arial'";t.textBaseline="alphabetic";t.fillStyle="#f60";t.fillRect(125,1,62,20);t.fillStyle="#069";t.fillText(n,2,15);t.fillStyle="rgba(102, 204, 0, 0.7)";t.fillText(n,4,17);return e.toDataURL()}};return e})
