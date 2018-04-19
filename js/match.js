
$(document).ready(function() {
	if(document.getElementById("matchinfo") == null || document.getElementById("matchinfo") == undefined)
		return;
	if (typeof matchID == "undefined") 
		return;
	
setInterval(searchSubmit, 400);
});

function searchSubmit(){
$.ajax({
url: "https://osu.gatari.pw/api/v1/match/"+matchID,
success: function(data){
$("#matchinfo").html(data)
}
});
}