
$(document).ready(function() {
	ReadInputs();
	if(document.getElementById("beatmapScores") == null || document.getElementById("beatmapScores") == undefined)
		return;
	if (typeof BeatmapID == "undefined") 
		return;
	if (typeof song_name != "undefined")
		document.title = urldecode(song_name);
	getScores();
	
});
var mods = 0;
var tId;
var tId2;
var None = 0;
var NoFail = 1;
var Easy = 2;
var NoVideo = 4;
var Hidden = 8;
var HardRock = 16;
var SuddenDeath = 32;
var DoubleTime = 64;
var Relax = 128;
var HalfTime = 256;
var Nightcore = 512;
var Flashlight = 1024;
var Autoplay = 2048;
var SpunOut = 4096;
var Relax2 = 8192;
var Perfect = 16384;
var Key4 = 32768;
var Key5 = 65536;
var Key6 = 131072;
var Key7 = 262144;
var Key8 = 524288;
var keyMod = 1015808;
var FadeIn = 1048576;
var Random = 2097152;
var LastMod = 4194304;
var Key9 = 16777216;
var Key10 = 33554432;
var Key1 = 67108864;
var Key3 = 134217728;
var Key2 = 268435456;
var SCOREV2 = 536870912;

var ezpp = {"mods": 0,"misses": 0, "accuracy" : 100.00, "combo":0};

function HideResult(){
	$(".ezpp.result").attr("style","padding: 0; height: 0;");
}

function ReadInputs(){
	clearTimeout(tId);
	HideResult();
	ezpp["combo"] = $("#ezpp-combo").val();
	ezpp["misses"] = $("#ezpp-misses").val();
	ezpp["accuracy"] = parseFloat($("#ezpp-accuracy").val());
	CalculatePp(ezpp["accuracy"], ezpp["combo"], ezpp["mods"], ezpp["misses"]);
}

function CalculatePp(Accuracy, Combo, Mods, Misses){
			$.getJSON("/api/api.php/v1/pp", {
				b:BeatmapID,
				a:Accuracy,
				x:Misses,
				c:Combo,
				m:Mods
			}, function(data) {
				if (data.status == 200) {
					$(".ezpp.result").html("That's about "+Math.round(data.pp[0])+"pp.");
					$(".ezpp.result").removeAttr("style");

				}
				if(data.status == 400){
					$(".modal-body").attr("class","modal-body error");
					$(".modal-content").prepend('<div class="ezpp error-message"><div class="message" id="error">Error: '+data.message+'</div></div>');
				}
		});	
}
$("input[id^='ezpp-']").on("keydown",function()
{ 	HideResult();
	clearTimeout(tId2);
	tId2 = setTimeout(function(){ReadInputs();}, 650);
});
$(".ezpp.btn.mode").click(function()
{
	
	HideResult();
switch(this.id){
	case 'hidden':
	if (ezpp['mods'] & Hidden){
	ezpp['mods'] = ezpp['mods'] - Hidden;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + Hidden;
	AnimateMod(0, this.id,true);
	}
	break;
	case 'double-time':
	if (ezpp['mods'] & DoubleTime){
	ezpp['mods'] = ezpp['mods'] - DoubleTime;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + DoubleTime;
	AnimateMod(0, this.id,true);
	}
	break;
	case 'hardrock':
	if (ezpp['mods'] & HardRock){
	ezpp['mods'] = ezpp['mods'] - HardRock;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + HardRock;
	AnimateMod(0, this.id,true);
	}
	break;
	case 'flashlight':
	if (ezpp['mods'] & Flashlight){
	ezpp['mods'] = ezpp['mods'] - Flashlight;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + Flashlight;
	AnimateMod(0, this.id,true);
	}
	break;
	case 'half-time':
	if (ezpp['mods'] & HalfTime){
	ezpp['mods'] = ezpp['mods'] - HalfTime;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + HalfTime;
	AnimateMod(0, this.id,true);
	}
	break;
	case 'easy':
	if (ezpp['mods'] & Easy){
	ezpp['mods'] = ezpp['mods'] - Easy;
	AnimateMod(1, this.id,true);
	}else{
	ezpp['mods'] = ezpp['mods'] + Easy;
	AnimateMod(0, this.id,true);
	}
	break;
	default:
	ezpp['mods'] = 0;
	break;
}
tId = setTimeout(function(){CalculatePp(ezpp["accuracy"], ezpp["combo"], ezpp["mods"], ezpp["misses"]);}, 350);


});

$(".btn.mode").click(function() {
	if($(this).attr("class").startsWith("ezpp")) return;
	clearTimeout(tId);
switch(this.id){
	case 'hidden':
	if (mods & Hidden){
	mods = mods - Hidden;
	AnimateMod(1, this.id);
	}else{
	mods = mods + Hidden;
	AnimateMod(0, this.id);
	}
	break;
	case 'double-time':
	if (mods & DoubleTime){
	mods = mods - DoubleTime;
	AnimateMod(1, this.id);
	}else{
	mods = mods + DoubleTime;
	AnimateMod(0, this.id);
	}
	break;
	case 'hardrock':
	if (mods & HardRock){
	mods = mods - HardRock;
	AnimateMod(1, this.id);
	}else{
	mods = mods + HardRock;
	AnimateMod(0, this.id);
	}
	break;
	case 'flashlight':
	if (mods & Flashlight){
	mods = mods - Flashlight;
	AnimateMod(1, this.id);
	}else{
	mods = mods + Flashlight;
	AnimateMod(0, this.id);
	}
	break;
	case 'half-time':
	if (mods & HalfTime){
	mods = mods - HalfTime;
	AnimateMod(1, this.id);
	}else{
	mods = mods + HalfTime;
	AnimateMod(0, this.id);
	}
	break;
	case 'easy':
	if (mods & Easy){
	mods = mods - Easy;
	AnimateMod(1, this.id);
	}else{
	mods = mods + Easy;
	AnimateMod(0, this.id);
	}
	break;
	case 'none':
	if (mods == -2){
	AnimateMod(1, this.id);
		mods = 0;
	}
	else {mods = -2;
	AnimateMod(0, this.id);
	}
	break;
	default:
	mods = 0;
	break;
}
rmod = mods;
if (rmod == 0)
	rmod = -1
if (rmod == -2)
	rmod = 0;
tId = setTimeout(function(){getScores(rmod, true);}, 450);

  });

function AnimateMod(action, id, ezpp){
if(action == 1){
 $(function() {
        $({grayScale: 0}).animate({grayScale: 100}, {
            duration: 300,
            easing: 'linear',
            step: function() {
                $((ezpp == true ? '.ezpp' : '')+'#'+id).css({
                    "-webkit-filter": "grayscale("+this.grayScale+"%)",
                    "filter": "grayscale("+this.grayScale+"%)",
                    "opacity": 0.6
                });
            }
        });
    });
}else{
	 $(function() {
        $({grayScale: 100}).animate({grayScale: 0}, {
            duration: 300,
            easing: 'linear',
            step: function() {
                $((ezpp == true ? '.ezpp' : '')+'#'+id).css({
                    "-webkit-filter": "grayscale("+this.grayScale+"%)",
                    "filter": "grayscale("+this.grayScale+"%)",
                    "opacity": 1.2
                });
            }
        });
    });
}
}

var currentPage = {
	best: 1,
	recent: 1,
};
var bestIndex = 0;



function urldecode (str) {
  return decodeURIComponent((str + '').replace(/\+/g, '%20'));
}

function report(UserID, ScoreID) {
	var r = confirm("Are you sure?");
		if (r == true){
			$.getJSON("/api/api.php/v1/beatmaps/report", {
				u:UserID,
				b:BeatmapID,
				s:ScoreID,
			}, function(data) {
				if (data.code == 200) 
					alert("Thanks for your report!");
		});
	}
}

function favourite(BeatmapID, action) {
			$.getJSON("/api/api.php/v1/beatmaps/favourite/"+BeatmapID, {
				action:action,
			}, function(data) {
				if (data.code == 200) {
					alert(data.info);
					if(action == "del"){
						$("#favourite").attr("class","btn btn-blue");
						$("#favourite").html("Add to favourites");
						$("#favourite").attr("onclick","favourite('"+BeatmapID+"','add');");
					}else{
						$("#favourite").attr("class","btn btn-dblue");
						$("#favourite").html("Remove favourite");
						$("#favourite").attr("onclick","favourite('"+BeatmapID+"','del');");
					}
				}
		});
	
}

function getScores(mods = -1,clear = false) {
	$.getJSON("/api/api.php/v1/beatmaps/scores/" + BeatmapID, {
		mode: play_mode,
		type: "b",
		m: mods,
	}, function(data) {
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :( Please report this!");
		}
		var scores = document.getElementById("beatmapScores");
		if (data.scores == null){
		scores.innerHTML = '<thead>\
		<tr><th class="text-left"> Rank</th><th>Player</th>'+((play_mode == 0 || play_mode == 3) ? '<th>PP</th>' : '')+'<th>Score</th><th>Accuracy</th><th>Max Combo</th><th>300 / 100 / 50</th><th>Misses</th><th>Mods</th><th class="text-right">Date</th>'+(userID != 0 ? '<th> </th>' : '')+'</tr>\
		</thead>';			
			return;
		}
		if (data.scores.length < 1)
			return;
		

		var data;
		var scoredata;
		$.each(data.scores, function(k, v) {
			var sel = "";
			if(userID == v.userid)
				sel = "selected";
			if(k == 0)
				scoredata = '<tr class="warning '+sel+'">';
			else scoredata += '<tr class="warning '+sel+'">';
			scoredata += '<td><p class="text-left"><a href="https://vipsu.ml/web/replays/'+v.id+'"><small>#'+(k+1)+'</small></a>		<img src="/images/ranks/'+getRank(play_mode, v.mods, v.accuracy, v.count_300, v.count_100, v.count_50, v.count_miss)+'.png"></p></td>';
			scoredata += '<td><p class="text-left"><img src="/images/flags/'+v.country+'.png" />		<a href="https://new.vipsu.ml/u/'+ v.userid + '">'+v.username+'</a></p></td>';
			if(play_mode == 0 || play_mode == 3)
				scoredata += '<td><p class="text-left">'+Math.round(v.pp)+'</b></p></td>';
			scoredata += '<td><p class="text-left">'+addCommas(v.score.toFixed())+'</b></p></td>';
			scoredata += '<td><p class="text-left">'+ v.accuracy.toFixed(2) + '%</b></p></td>';
			scoredata += '<td><p class="text-left">'+(v.max_combo == v.fc ? v.max_combo +" (FC)" : '('+ v.max_combo + '/'+v.fc+')')+'</p></td>';
			scoredata += '<td><p class="text-left">'+v.count_300+'  /  '+v.count_100+'  /  '+v.count_50+'</b></p></td>';
			scoredata += '<td><p class="text-left">'+v.count_miss+'</b></p></td>';
			scoredata += '<td><p class="text-right">' + getScoreMods(v.mods) + '</p></td>';
			scoredata += '<td><p class="text-right">' + timeSince(new Date(v.time)) + '	ago </p></td>';
			if(userID != 0)
				scoredata += '<td><p class="text-right"><a href="#" onclick="report('+v.userid+','+v.id+');">Report</a></p></td>';
			scoredata += '</tr>';
		});
		data = '<thead>\
		<tr><th class="text-left"> Rank</th><th>Player</th>'+((play_mode == 0 || play_mode == 3) ? '<th>PP</th>' : '')+'<th>Score</th><th>Accuracy</th><th>Max Combo</th><th>300 / 100 / 50</th><th>Misses</th><th>Mods</th><th class="text-right">Date</th>'+(userID != 0 ? '<th> </th>' : '')+'</tr>\
		</thead>';
		if(clear == true)
			scores.innerHTML = data+"<tbody>"+scoredata+ "</tbody></table>";
		else scores.innerHTML += "<tbody>"+scoredata+ "</tbody></table>";

	});
}


function strtolower( str ) {	
	return str.toLowerCase();
}


function timeSince(date) {
	var seconds =  Math.floor((new Date() - date ) / 1000) ;


    var interval = Math.floor(seconds / 31536000);

    if (interval > 1) {
        return interval + " years";
    }if (interval == 1){
		return "about a year";
	}
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) {
        return interval + " months";
    }if (interval == 1){
		return "about a month";
	}
    interval = Math.floor(seconds / 86400);
    if (interval > 1) {
        return interval + " days";
    }if (interval == 1){
		 interval = Math.floor(seconds / 3600);
        return "about " + interval + " hours";
	}
    interval = Math.floor(seconds / 3600);
    if (interval > 1) {
        return "about " + interval + " hours";
    }if (interval == 1){
		return "about a hour";
	}
    interval = Math.floor(seconds / 60);
    if (interval > 1) {
        return "about " + interval + " minutes";
    }if (interval == 1){
		return "about a minute";
	}
    return "about " + Math.floor(seconds) + " seconds";
}


function getScoreMods(m) {
	var r = '';
	var hasNightcore = false, hasPF = false;
	if (m & NoFail) {
		r += 'NF';
	}
	if (m & Easy) {
		r += 'EZ';
	}
	if (m & NoVideo) {
		r += 'NV';
	}
	if (m & Hidden) {
		r += 'HD';
	}
	if (m & HardRock) {
		r += 'HR';
	}
	if (m & Nightcore) {
		r += 'NC';
		hasNightcore = true;
	}
	if (!hasNightcore && (m & DoubleTime)) {
		r += 'DT';
	}
    if (m & Perfect) {
		r += 'PF';
        hasPF = true;
	}
	if (m & Relax) {
		r += 'RX';
	}
	if (m & HalfTime) {
		r += 'HT';
	}
	if (m & Flashlight) {
		r += 'FL';
	}
	if (m & Autoplay) {
		r += 'AP';
	}
	if (m & SpunOut) {
		r += 'SO';
	}
	if (m & Relax2) {
		r += 'AP';
	}
	if (!hasPF && (m & SuddenDeath)) {
		r += 'SD';
	}
	if (m & Key4) {
		r += '4K';
	}
	if (m & Key5) {
		r += '5K';
	}
	if (m & Key6) {
		r += '6K';
	}
	if (m & Key7) {
		r += '7K';
	}
	if (m & Key8) {
		r += '8K';
	}
	if (m & keyMod) {
		r += '';
	}
	if (m & FadeIn) {
		r += 'FD';
	}
	if (m & Random) {
		r += 'RD';
	}
	if (m & LastMod) {
		r += 'CN';
	}
	if (m & Key9) {
		r += '9K';
	}
	if (m & Key10) {
		r += '10K';
	}
	if (m & Key1) {
		r += '1K';
	}
	if (m & Key3) {
		r += '3K';
	}
	if (m & Key2) {
		r += '2K';
	}
    if (m & SCOREV2) {
		r += 'V2';
	}
	if (r.length > 0) {
		return "<b>"+r+"</b>";
	} else {
		return 'None';
	}
}



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

function getRank(gameMode, mods, acc, c300, c100, c50, cmiss) {
	var total = c300+c100+c50+cmiss;

	var hdfl = (mods & (Hidden | Flashlight | FadeIn)) > 0;

	var ss = hdfl ? "sshd" : "ss";
	var s = hdfl ? "shd" : "s";

	switch(gameMode) {
		case 0:
		case 1:
			var ratio300 = c300 / total;
			var ratio50 = c50 / total;

			if (ratio300 == 1)
				return ss;

			if (ratio300 > 0.9 && ratio50 <= 0.01 && cmiss == 0)
				return s;

			if ((ratio300 > 0.8 && cmiss == 0) || (ratio300 > 0.9))
				return "a";

			if ((ratio300 > 0.7 && cmiss == 0) || (ratio300 > 0.8))
				return "b";

			if (ratio300 > 0.6)
				return "c";

			return "d";

		case 2:
			if (acc == 100)
				return ss;

			if (acc > 98)
				return s;

			if (acc > 94)
				return "a";

			if (acc > 90)
				return "b";

			if (acc > 85)
				return "c";

			return "d";

		case 3:
			if (acc == 100)
				return ss;

			if (acc > 95)
				return s;

			if (acc > 90)
				return "a";

			if (acc > 80)
				return "b";

			if (acc > 70)
				return "c";

			return "d";
	}
}
