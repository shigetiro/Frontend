$("#checkbox").change(function(){
gatari = this.checked;
searchSubmit();
});

var mode = -1;
var busy = false;  
$(window).scroll(function() {
   if($(window).scrollTop() + $(window).height() > $(document).height()-300 && !busy) {
         busy = true;
         setTimeout(function() {
                    PageID++; 
           searchSubmit(true);
                     
         }, 0);
                     
      }  
   });
$(".beatmapsets-search-filter__item").click(function() {
	$(".beatmapsets-search-filter__item--active").attr("class","beatmapsets-search-filter__item undefined");
	$(this).attr("class","beatmapsets-search-filter__item beatmapsets-search-filter__item--active");
	mode = $(this).attr("value")
	searchSubmit();
});


$(document).ready(function() {
	if(document.getElementById("beatmaps") == null || document.getElementById("beatmaps") == undefined)
		return;
	if (typeof PageID == "undefined") 
		return;	
	
	searchSubmit();
});
var gatari = false;
var tId;
function searchBM(){
clearTimeout(tId);
PageID = 1;
tId = setTimeout(searchSubmit, 400);
}
function searchSubmit(infinity = false){
	if(infinity == false)
$("#beatmaps").html('<div style="font-size:-webkit-xxx-large"><i class="fa fa-refresh fa-spin"></i><br><small> Loading beatmaps\
</small></div>');
var song_name = $("#serachquery").val(); 
$.ajax({
url: "/api/api.php/v1/searchBeatmap/"+encodeURI(song_name),
data: "page="+PageID+"&mode="+mode+"&gatari="+gatari,
success: function(data){
var houeta = explode('{"count":', data)[1];
var conunt = explode('}',houeta)[0];
data = explode('{"count":'+conunt+'}', data)[1];
if(infinity == false){
	$("#beatmaps").html(data);//(PageID > 1 ? '<a style="cursor:pointer;" onclick="prevPage()" ;="">Prev Page</a> | ' : '')+(conunt >= 20 ? '<a style="cursor:pointer;" onclick="nextPage()">Next Page</a>' : '')+data+(PageID > 1 ? '<a style="cursor:pointer;" onclick="prevPage()" ;="">Prev Page</a>  | ' : '')+(conunt >= 20 ? '<a style="cursor:pointer;" onclick="nextPage()">Next Page</a>' : ''));
}
 else{
 	$("#beatmaps").html($("#beatmaps").html()+data);
 }
 if (conunt >= 20) busy = false;
}
});

}

function explode( delimiter, string ) {	// Split a string by string
	// 
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: kenneth
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)

	var emptyArray = { 0: '' };

	if ( arguments.length != 2
		|| typeof arguments[0] == 'undefined'
		|| typeof arguments[1] == 'undefined' )
	{
		return null;
	}

	if ( delimiter === ''
		|| delimiter === false
		|| delimiter === null )
	{
		return false;
	}

	if ( typeof delimiter == 'function'
		|| typeof delimiter == 'object'
		|| typeof string == 'function'
		|| typeof string == 'object' )
	{
		return emptyArray;
	}

	if ( delimiter === true ) {
		delimiter = '1';
	}

	return string.toString().split ( delimiter.toString() );
}


function prevPage() {
	PageID--;
	searchSubmit();
}

function nextPage(){
		PageID++;
	searchSubmit();
}