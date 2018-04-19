$(document).ready(function() {

$(".wrapper>.content").after('<div class="footer"><span class="footer-text"><a href="/support" class="landing-footer-social__icon landing-footer-social__icon support">\
<span class="fa fa-heart"></span>\
</a><a href="https://new.vipsu.ml/doc/2"> Rules </a> \
<a href="https://discord.gg/2DJXQga"> Discord </a><a href="https://new.vipsu.ml/doc/1"> How to connect </a> \
</span>\
<span class="footer-powered">Vipsu Copyright</span></div>');

});

var search = function(query, syncResults, asyncResults) {
	$.getJSON("/api/api.php/v1/users/lookup", {
		name: query,
	}, function(result) {
		if (result.code == 200) {
			asyncResults(result.users);
		}
	});
};
var displayer = function(a) {
	return a.username
}
var suggest = function(a) {
	return "<div><span class='avileft'><img src='https://a.vipsu.ml/" + a.id + "' class='tinyavatar'></span> " + a.username + "</div>";
}

var fired = false;
$("#query").typeahead(
{
	highlight: true,
},
{
	source: search,
	limit: 10,
	display: displayer,
	templates: {
		suggestion: suggest,
	}
}).bind('typeahead:select', function(ev, suggestion) {
  fired = true;
  window.location.href = "/u/" + suggestion.id;
}).keyup(function(event){
    if(event.keyCode == 13){
		setTimeout(function(){
			if (fired)
				return;
	        window.location.href = "/u/" + encodeURIComponent($("#query").val());
		}, 200);
    }
});;
