var redraw;

function is_uid(data) {
	return data.match(/[a-z]/);
}

function draw_graph(data) {
    
    var width = $(document).width() - 40;
    var height = $(document).height() - 70;
    
    g = new Graph();
	
	var data1, data;
	for(var i = 0; i < data.connections.length; i++) {
		var from = data.connections[i][0],
			to = data.connections[i][1];
	
		if(is_uid(from)) {
			data1 = {is: 'uid'};
			//from += '\n('+data.namelinks[from]+')';
		} else {
			data1 = null;
		}
			
		if(is_uid(to)) {
			data2 = {is: 'uid'};
			//to += '\n('+data.namelinks[to]+')';
		} else {
			data2 = null;
		}
		
		g.addEdge(from, to, null, data1, data2);
	}
	
    /* layout the graph using the Spring layout implementation */
    var layouter = new Graph.Layout.Spring(g);
    
    /* draw the graph using the RaphaelJS draw implementation */
    renderer = new Graph.Renderer.Raphael('canvas', g, width, height);

	
    redraw = function() {
        layouter.layout();
        renderer.draw();
    };
	
	/*var i = 1;
	for(var name in data['names']) {
		var count = data['names'][name];
		if(!name) continue;
		
		renderer.r.text(0, 10*i, name + ' (' + count + ')').attr({"text-anchor":"start"});
		
		i++;
	}*/
};

function middleClickHandler(elem) {
	var link = elem.attrs.text;
	
	link = link.split("\n")[0];
	
	if(is_uid(link)) {
		window.open("/profile/"+link);
	}else{
		window.open("/IP_address/"+link);
	}
}
