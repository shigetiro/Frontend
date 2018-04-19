
$(document).ready(function() {
	
	getFriendList(0, true);
});

var currentPage = 1;

var followerList = 0;

function addRemoveFriend(self){
userid = $(self).attr("userid");
$.getJSON("/api/api.php/v1/users/addremovefriend", {
	u: userid,
}, function(data) {
	switch(data.friend){
		case 0:
			$(self).attr("class","btn btn-primary");
			$(self).html('<span class="glyphicon glyphicon-plus"></span>	Add as Friend</a>');
			$("#"+userid).attr("class", "ui follow column");
			break;
		case 1:
			$(self).attr("class","btn btn-success");
			$(self).html('<span class="glyphicon glyphicon-star"></span>	Friend</a>');
			$("#"+userid).attr("class", "ui column");
			break;
		case 2:
			$(self).attr("class","btn btn-danger");
			$(self).html('<span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a>');
			$("#"+userid).attr("class", "ui mutual column");
			break;
	}
	
});
}

function getFriendList(followers, init) {
var btn = $(".load-more-user-scores");
btn.attr("disabled", "true");

$.getJSON("/api/api.php/v1/users/friends/", {
	l: 50,
	f: followers,
	p: currentPage,
}, function(data) {
	var tb = $("#friend-list");
	if(data.friends.length < 0){
		if(init)
			tb.append('<b>You don\'t have any friends.</b> You can add someone to your friends list<br>by clicking the <b>"Add as friend"</b> button on someones\'s profile.<br>You can add friends from the game client too.');
		return;
	}
	if(init) tb.html('');
	
	$.each(data.friends, function(k, v) {
		var friendButton;
		var type = "";
		switch(v.mutual){
			case 2:
				friendButton = '<a onclick="addRemoveFriend(this);" userid="'+v.userid+'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a>';
				type = "mutual";
				break;
			case 1:
				friendButton = '<a onclick="addRemoveFriend(this);" userid="'+v.userid+'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a>';
				type = "follow";
				break;
			default:
				friendButton = '<a onclick="addRemoveFriend(this);" userid="'+v.userid+'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a>';
				break;
		}
		var u = '<div id="'+v.userid+'" class="ui '+type+' column">';
				u += '		<h4 class="ui image header">';
				u += '			<img src="https://a.vipsu.ml/'+v.userid+'" class="ui mini rounded image">';
				u += '			<div class="content">';
				u += '			<a href="/u/'+v.userid+'">'+v.username+'</a>';
				u += '			<div class="sub header">';
				u += '				'+friendButton+'';
				u += '			</div>';
				u += '		</div>';
				u += '	</h4>';
				u += '	</div>';
		
		tb.append(u);
		
	});
	if (data.friends.length >= 50 && followerList == 0)
		btn.removeAttr("disabled");
	currentPage++;
});
}



$(".load-more-user-scores").click(function() {
getFriendList(followerList,false);
});


$("#checkbox").click(function() {
followerList = (followerList == 0 ? 1 : 0);
currentPage = 1;
getFriendList(followerList, true);
}); 

function addCommas(nStr) {
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
