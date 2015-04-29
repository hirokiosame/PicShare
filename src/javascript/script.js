var Dropzone = require("./dropzone");


var dropzone = document.getElementById("dropBox");
var photosContainer = document.getElementById("photos");
var myDZ = new Dropzone(dropzone, {
	url: document.URL,
	clickable: true,
	previewsContainer: document.createElement("div"),
	thumbnailWidth: null,
	thumbnailHeight: null,
	previewTemplate: "<div></div>",

	success: function(file, html){

		html = html.replace(":name:", file.name);

		var contain = document.createElement("div");
		contain.innerHTML = html;

		photosContainer.appendChild(contain.children[0]);
	},
	complete: function(){

	}
});

