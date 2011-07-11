$(document).ready(function() {
	var showButton = $('#show_key'),
		keyUrl = showButton.attr('href');
	
	showButton.click(function(event) {
		var key = $('#key').val(),
			url = keyUrl + '/' + key;
		
		showButton.attr('href', url);
	});
});
