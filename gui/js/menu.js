$(document).ready(function() {
	
$("ul#topnav li").hover(function() { //Hover over event on list item
	$(this).css({ 'background' : '#1376c9 url(/gui/css/topnav_active.gif) repeat-x'}); //Add background color + image on hovered list item
	$(this).find("span").show(); //Show the subnav
} , function() { //on hover out...
	$(this).css({ 'background' : 'none'}); //Ditch the background
	$(this).find("span").hide(); //Hide the subnav
});
	
});
