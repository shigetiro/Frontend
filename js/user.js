
$(document).ready(function() {
	if($("#chart1 svg").length>0){
	shitFIX();
	}
	if (typeof UserID == "undefined" || typeof Mode == "undefined") {
		return;
	}
	getScores("best");
	getScores("recent");
	$("[data-toggle=popover]").popover();
	if(typeof Username != "undefined") {
	getScores("first");
	getFavourites();

	document.title = Username+"'s profile";
	title = document.title;
//$('.col-lg-12.text-center').attr('style','width:100%;');
	}
});

function addRemoveFriend(self){
	userid = $(self).attr("userid");
	$.getJSON("/api/api.php/v1/users/addremovefriend", {
		u: userid,
	}, function(data) {
		switch(data.friend){
			case 0:
				$(self).attr("class","btn btn-primary");
				$(self).html('<span class="glyphicon glyphicon-plus"></span>	Add as Friend</a>');
				break;
			case 1:
				$(self).attr("class","btn btn-success");
				$(self).html('<span class="glyphicon glyphicon-star"></span>	Friend</a>');
				break;
			case 2:
				$(self).attr("class","btn btn-danger");
				$(self).html('<span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a>');
				break;
			default:
				$(self).html('<span class="glyphicon glyphicon-warning-sign"></span>	Error!!!</a>');
				break;
		}
		
	});
}

function shitFIX(){
	var chart;

	nv.addGraph(function() {
		chart = nv.models.lineChart()
		.options({
			margin: {left: 80, bottom: 45},
			x: function(d) { return d[0] },
			y: function(d) { return d[1] },
			showXAxis: true,
			showYAxis: true
		})
		;

	  chart.xAxis
	  .axisLabel("Days")
	  .tickFormat(function(d) {
	  	if (d == 0) return "now";
	  	return -d + " days ago";
	  });

	  chart.yAxis
	  .axisLabel('Performance')
	  .tickFormat(function(d) {
	  	if (d == 0) return "-";
	  	return  d +"pp";
	  })
	  ;


	  chart.yScale(d3.scale.log().clamp(true));


	  chart.forceY([highLimit,lowLimit]);
	  chart.yAxis.tickValues(ValueTicks);


	  chart.xAxis.tickValues([-31, -15, 0]);
	  chart.forceX([-31,0]);

	  // No disabling / enabling elements allowed.
	  chart.legend.updateState(false);
	  chart.interpolate("basis");

	  var svg = d3.select('#chart1 svg');

	  svg.datum(data())
	  .call(chart);


	  return chart;
	});
}

	function data() {	
		return [
			{
				area: false,
				values: historyOut,
				key: "Performance",
				color: "#555",
				size: 6
			},
			// This just generates a label for us. We draw the area rectangle manually.
			/*{
				area: true,
				values: <?=json_encode(array())?>,
				key: "Algorithm updates",
				color: "#22AA22"
			},*/
		];
	}

	function shit(){
	if((typeof MyUserID != "undefined") && (Mode == 0 || Mode == 3)){
		var pps = [];
		var lbls = [];
		$.getJSON("/api/api.php/v1/users/charts", {
		u: UserID,
		m: Mode,
	}, function(data) {
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :( Please report this!");
		}
		$.each(data.charts, function(k, v) {
			if(k == 0)
				lbls.push("Right Now");
			else
				lbls.push(k+" days ago");

			pps.push(v);
		});
		var data = {
    labels: lbls.reverse(),
    datasets: [
        {
            label: "Performance",
            fill: false,
            borderWidth: 5,
            lineTension: 0,
            backgroundColor: "rgb(204, 82, 136)",
            borderColor: "rgb(204, 82, 136)",
            borderCapStyle: 'butt',
            borderDash: [],
            borderDashOffset: 0.0,
            borderJoinStyle: 'miter',
            pointBorderColor: "rgb(204, 82, 136)",
            pointBackgroundColor: "rgb(204, 82, 136)",
            pointBorderWidth: 1,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: "rgb(204, 82, 136)",
            pointHoverBorderColor: "rgba(220,220,220,1)",
            pointHoverBorderWidth: 1,
            pointRadius: 1,
            pointHitRadius: 10,
            data: pps,
            spanGaps: false
        }
    ]

};
		var ctx = document.getElementById("myChart");
var myLineChart = Chart.Line(ctx, {
    data: data,
    options: {
        scales: {
            yAxes: [{           
                display: true,
                stacked: true
            }],xAxes: [{           
                display: false,
                stacked: true
            }]
        },
         title: {
            display: false
        },
        legend: {
            display: false
        }
    }
});
	});



	}
}

function getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

var failed = 0;
var owc_country = "XX";
var currentPage = {
	best: 1,
	recent: 1,
	first: 1,
	fav: 1
};
var bestIndex = 0;


$(".upspoiler_head").click(function()
{
var spoiler=$(this).parents(".upspoiler");
spoiler.children(".upspoiler_body").slideToggle("fast");

return false;
});



function report() {
	var r = confirm("Are you sure?");
		if (r == true){
			$.getJSON("/api/api.php/v1/users/report", {
				u: UserID,
			}, function(data) {
				if (data.code == 200) 
					alert("Thanks for your report!");			
		});
	}
}

function getFavourites(){
	var btn = $(".load-more-user-scores[data-rel='favourites']");
	btn.attr("disabled", "true");
	$.getJSON("/api/api.php/v1/users/favourites/" + UserID, {
		l: 8,
		p: currentPage["fav"],
	}, function(data) {
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :( Please report this!");
		}
			var tb = $("#favourite-beatmaps");
		$.each(data.beatmaps, function(k, v) {
			var u = "<tr>";
			u +=  '<td class="warning">\
						<img class="pc-img" src="https://b.ppy.sh/thumb/'+ v.beatmapset_id+'l.jpg"><div class="song-title">\
						<b><a href="/s/'+v.beatmapset_id+'">'+ v.song_name+ '</a></b>\
							</div><div class="mapped" style=""> mapped by '+(v.creator == "" ? "Someone" : v.creator)+'</div></td>';
			u += '<td class="warning"><p class="text-right"></p></td>';
			u += "</tr>";
			tb.append(u);
	});
				if (data.beatmaps.length >= 8)
			btn.removeAttr("disabled");
		currentPage["fav"]++;
});
}

function getScores(type) {
	var btn = $(".load-more-user-scores[data-rel='" + type + "']");
	btn.attr("disabled", "true");

	$.getJSON("/api/api.php/v1/users/scores/" + type, {
		id: UserID,
		l: 20,
		f: failed,
		p: currentPage[type],
		mode: Mode,
	}, function(data) {
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :( Please report this!");
		}

		if(type == "first" && currentPage[type] == 1)
		{
			$('a[href="#best"]').append("	<i class='counter animated bounceInRight'>"+data.count+"</i>");
		}
		
		var tb = $("#" + type + "-plays-table");
		$.each(data.scores, function(k, v) {
			var sw = "warning";
			if(v.completed == 0)
				sw = "danger";
			var u = "<tr>";
			u +=  '<td class="' + sw + '">\
						<p class="text-left">\
							<div class="score-song_title"><img src="/images/ranks/' + (v.completed == 0 ? "f" : getRank(Mode, v.mods, v.accuracy, v.count_300, v.count_100, v.count_50, v.count_miss)) + '.png"></img> \
							<a href="https://new.vipsu.ml/b/'+ v.beatmap.beatmap_id+(Mode != 0 ? "&m="+Mode : "")+'">'+ v.beatmap.song_name+ '</a>\
							<b>' + getScoreMods(v.mods) + '</b> (' + v.accuracy.toFixed(2) + '%) </p>\
							<div class="play-info">\
							<small><b>'+addCommas(v.score.toFixed())+' / '+v.max_combo+'x'+(v.fc == 0 ? '' : '<span style="color: #455;">('+v.fc+'x)</span>')+'</b>\
							 { '+v.count_300+' / '+v.count_100+' / '+v.count_50+' / '+v.count_miss+' }</small></div>\
							<div title="'+new Date(v.time).toUTCString()+'"><small>' + timeSince(new Date(v.time)) + ' ago</small></div>\
						</td>';
			u += '<td class="' + sw + '"><p class="text-right"><b>';
			var small = "";
				u += "<span title='" + addCommas(v.pp)+ "'>" + addCommas(Math.round(v.pp)) + "pp</span>";
				if (type == "best") {
					var perc = Math.pow(0.95, bestIndex);
					var wpp  = v.pp * perc;
					if(wpp < 2)
						data.scores.length--;
					small = "<small>weighted " + Math.round(perc * 100) + "% (" + addCommas(Math.round(wpp)) + " pp)</small><br>";
					bestIndex++;
				}
				if(type == "best" || type == "first")
					small += "<small><span class='fa fa-eye'></span>	"+addCommas(v.views)+"</small>";
			
			if (v.completed == 3) {
				u += ' <a href="/web/replays/' + v.id + '"><i class="fa fa-star"></i></a>';
			}
			u += '</b><br>' + small + '</div></p></td>';
			u += "</tr>";
			tb.append(u);
		});
		if (data.scores.length >= 20)
			btn.removeAttr("disabled");
		currentPage[type]++;
	});
}

function ShowFailed() {
	var scoringName = "PP";
	currentPage["recent"]--;
	failed = (failed == 0 ? 1 : 0);
	$("#recent-plays-table").html('<tbody><tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th class="text-right">' +scoringName + '</th></tr> <tr><th class="text-left">	<input type="checkbox" '+(failed == 1 ? "checked" : "")+' onclick="ShowFailed();" id="checkbox"><label for="checkbox">	Â Show failed plays</label></th><th class="text-right"></th></tr></tbody>');
	getScores("recent");
}

$(".owc.unknown>a.btn").click(function(){
owc_country = $("[name='country']").val();
var parent = $(this).parent().parent();
$.getJSON("/api/api.php/v1/users/owc/" + owc_country, {}, function(data) {
		if(data.info == "ok"){
			parent.html("<div class='owc "+owc_country.toLowerCase()+"'></div>");
		}else{
			Location.reload();
		}
	});
});

$("[name='country']").change(function(){
owc_country = this.value;
});

$(".load-more-user-scores").click(function() {
	if ($(this).attr("disabled"))
		return;
	if ($(this).data("rel") == "favourites")
		getFavourites();
	else
	getScores($(this).data("rel"));
});

function timeSince(date) {
var d = new Date;
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
		r += 'TC';
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
		return "+ "+r;
	} else {
		return '';
	}
}

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
