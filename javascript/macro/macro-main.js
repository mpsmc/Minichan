$(document).ready(function () {
	if(FormData && FileReader && CanvasRenderingContext2D && CanvasRenderingContext2D.prototype.getImageData) {
		$("#nosupport").remove();
		$("#support").show();
	}
});

function step1(file) {
	if (!file || (typeof file == "object" && !file.type.match(/image.*/))) return;

	$("#step1").hide();
	$("#step2").show();

	var canvas = document.getElementById("final_image"),
		gcanvas = document.createElement('canvas');

	var ctx = canvas.getContext("2d"),
		gctx = gcanvas.getContext("2d");

	var texts = [];
		
	var reader = new FileReader();
	var mx, my, offsetx, offsety;
	var img;
	
	var stylePaddingLeft, stylePaddingTop, styleBorderLeft, styleBorderTop;
	var sel, resize;

	function Text(x, y, s, t) {
		this.x = x || 0;
		this.y = y || 0;
		this.s = s || 18;
		this.t = t || 'UNDEFINED';
		this.width = 0;
	}

	function drawTxt(ctx, text) {
		ctx.fillStyle = 'white';
		ctx.strokeStyle = 'black';
		ctx.lineWidth = 3;
		ctx.font = text.s + 'px Impact, sans-serif';
		ctx.fillText(text.t, text.x, text.y);
		ctx.strokeText(text.t, text.x, text.y);
		text.width = ctx.measureText(text.t).width;
	}
	
	function drawMoveGuide(ctx, text) {
		ctx.fillStyle = "rgba(255, 0, 0, 0.5)";
		ctx.fillRect(text.x+text.width+5, text.y, 10, 10);
		
	}

	function drawDeleteGuide(ctx, text) {
		ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
		ctx.fillRect(text.x+text.width+5, text.y-15, 10, 10);
	}

	function draw(hideGuide) {
		ctx.save();
		ctx.setTransform(1, 0, 0, 1, 0, 0);
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		ctx.drawImage(img, 0, 0);
		var text;
		for (var i = 0; i < texts.length; i++) {
			text = texts[i];
			drawTxt(ctx, text);
			if(!hideGuide) {
				drawMoveGuide(ctx, text);
				drawDeleteGuide(ctx, text);
			}
		}
		ctx.restore();
	}
	
	function loadImage() {
		canvas.width = img.width;
		canvas.height = img.height;
		gcanvas.width = img.width;
		gcanvas.height = img.height;
		
		stylePaddingLeft = parseInt(document.defaultView.getComputedStyle(canvas, null)['paddingLeft'], 10)     || 0;
		stylePaddingTop  = parseInt(document.defaultView.getComputedStyle(canvas, null)['paddingTop'], 10)      || 0;
		styleBorderLeft  = parseInt(document.defaultView.getComputedStyle(canvas, null)['borderLeftWidth'], 10) || 0;
		styleBorderTop   = parseInt(document.defaultView.getComputedStyle(canvas, null)['borderTopWidth'], 10)  || 0;
		
		draw();
	}

	canvas.onmousedown = function (e) {
		getMouse(e);

		for (var i = 0; i < texts.length; i++) {
			gctx.clearRect(0, 0, gcanvas.width, gcanvas.height);
			drawTxt(gctx, texts[i]);
			
			if(gctx.getImageData(mx, my, 1, 1).data[3] > 0) {
				sel = texts[i];
				offsetx = mx - sel.x;
				offsety = my - sel.y;
				resize = false;
				return false;
			}
			
			drawMoveGuide(gctx, texts[i]);
			if(gctx.getImageData(mx, my, 1, 1).data[3] > 0) {
				sel = texts[i];
				offsetx = mx - sel.x;
				offsety = my - sel.y;
				sel.oldSize = sel.s;
				resize = true;
				return false;
			}
			
			drawDeleteGuide(gctx, texts[i]);
			if(gctx.getImageData(mx, my, 1, 1).data[3] > 0) {
				texts.splice(i,1);
				draw();
				return false;
			}
		}
		
		sel = null;
		return false;
	}
	
	canvas.onmousemove = function(e) {
		if(!sel) return;
		getMouse(e);
		if(resize) {
			sel.s = sel.oldSize + mx - sel.x - offsetx;
			canvas.style.cursor = "se-resize";
		}else{
			sel.x = mx - offsetx;
			sel.y = my - offsety;
			canvas.style.cursor = "move";
		}
		
		draw();
		return false;
	}
	
	canvas.onmouseup = function(e) {
		sel = null;
		canvas.style.cursor = "default";
		return false;
	}
	
	canvas.onselectstart = function () { return false; }

	function getMouse(e) {
		var element = canvas, offsetX = 0, offsetY = 0;

		if (element.offsetParent) {
			do {
				offsetX += element.offsetLeft;
				offsetY += element.offsetTop;
			} while ((element = element.offsetParent));
		}

		// Add padding and border style widths to offset
		offsetX += stylePaddingLeft;
		offsetY += stylePaddingTop;

		offsetX += styleBorderLeft;
		offsetY += styleBorderTop;

		mx = e.pageX - offsetX;
		my = e.pageY - offsetY
	}
	
	if(typeof file == "object") {
		reader.onload = function(event){
			img = new Image();
			img.onload = loadImage;
			img.src = event.target.result;
		};
		
		reader.readAsDataURL(file);
	}else{
		$.getImageData({
			url: file,
			success: function(image) {
				img = image;
				loadImage();
			},
			error: function(xhr, text_status) {
				alert('Error: ' + text_status);
				window.reload();
			}
		});
	}
	
	$("#add-text").click(function() {
		texts.push(new Text(canvas.width / 2, canvas.height / 2, 45, $("#new-text").val()));
		draw();
	});
	
	$("#upload-imgur").click(function() {
		$("#upload-imgur").attr('disabled', 'disabled');
		draw(true);
		canvas.toBlob(function(blob) {
			draw();
			var fd = new FormData();
			fd.append("image", blob);
			fd.append("key", "c5c016df9f08091756ee992928b7b05d");
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "http://api.imgur.com/2/upload.json");
			xhr.onload = function() {
				$("#imgur-link").val(JSON.parse(xhr.responseText).upload.links.original).select();
				$("#upload-imgur").removeAttr('disabled');
			}

			xhr.send(fd);
		});
		
		$("#imgur-link").val("Wait...");
	});
	
	$("#download").click(function() {
		$("#download").attr('disabled', 'disabled');
		draw(true);
		canvas.toBlob(function(blob) {
			var filename;
			console.log(file);
			if(typeof file == "object") {
				filename = document.getElementById("base_image").files[0].fileName.replace(/\.[a-z]*$/g, "") + "-macro.png"
			}else{
				filename = getFileName(file) + "-macro.png";
			}
			saveAs(blob, filename);
			draw();
			$("#download").removeAttr('disabled');
		});
	});
}

function getFileName(url) {
	url = url.substring(0, (url.indexOf("#") == -1) ? url.length : url.indexOf("#"));
	url = url.substring(0, (url.indexOf("?") == -1) ? url.length : url.indexOf("?"));
	url = url.substring(url.lastIndexOf("/") + 1, url.length);
	url = url.substring(0, (url.indexOf(".") == -1) ? url.length : url.indexOf("."));
	//return
	return url;
}