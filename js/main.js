
$(document).ready(function() {
	getInfo("online");
	getInfo("banned");
	getInfo("users");
	getInfo("scores");
});




function getInfo(type) {
			$.getJSON("/api/api.php/v1/system/"+type, {}, function(data) {
				if (data.code == 200) 
					$("#"+type).html(data.result);			
		});
}



