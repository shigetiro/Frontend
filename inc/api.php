<?php

require_once '../inc/functions.php';
$url = $_SERVER['REQUEST_URI'];
$url = explode("?",$url)[0];
$method = explode("/api/api.php/v1/",$url);
$method = explode("/",$method[1]);
if($method[0] == "ip"){
	die("93.170.76.141");
}
if($method[0] == "antihax"){
    $username = $_GET["username"];
    $log = $_GET["log"];
    $userid = getUserID($username);
    $msg = "[anticheat] ".$username." (https://osu.gatari.pw/u/".$userid.")\r\n\r\n".$log;
    redisPublish("hax:warrning",json_encode(["message"=>$msg]));
}
if($method[0] == "kassacheck"){
    foreach ($_POST as $key => $value) {
        print($value."\r\n");
    }
    die();
}

if ($method[0] == "match"){
    $id = intval($method[1]);
         $info = $GLOBALS["db"]->fetch("SELECT * FROM mp_matches WHERE match_id = ?",[$id]);
         var_dump( $info);
         die();
}

if($method[0] == "searchBeatmap"){
        $name = urldecode($method[1]);
        
        $limit = 20;
        if(isset($_GET["page"])){
        	$limit = $_GET["page"]*20;
        		$limit = sprintf("%s,%s",$limit-20, 20);
        }
        echo $limit;
        $where = "WHERE ranked >= 2";
        if($_GET["gatari"] == "true")
            $where .= " AND ranked_status_freezed = 1";
        if(!isset($name) || (empty($name)) || strlen($name) < 4)
            $beatmaps = $GLOBALS["db"]->fetchAll("SELECT * FROM (SELECT ANY_VALUE(artist) artist,ANY_VALUE(title) title,ANY_VALUE(creator) creator, ANY_VALUE(ranked_status_freezed) ranked_status_freezed, ANY_VALUE(ranking_data) ranking_data, ANY_VALUE(beatmapset_id) beatmapset_id, MAX(beatmap_id) beatmap_id FROM beatmaps $where GROUP BY beatmapset_id) c ORDER BY c.ranking_data DESC,c.beatmap_id DESC LIMIT $limit");
        else
            $beatmaps = $GLOBALS["db"]->fetchAll("SELECT ANY_VALUE(artist) artist,ANY_VALUE(title) title,ANY_VALUE(creator) creator, ANY_VALUE(ranked_status_freezed) ranked_status_freezed,ANY_VALUE(ranked) ranked,ANY_VALUE(beatmapset_id) beatmapset_id, ANY_VALUE(ranking_data) ranking_data, MAX(relev) relev FROM (SELECT *, MATCH (artist,title,version,song_name) AGAINST ('$name' IN BOOLEAN MODE) as relev FROM beatmaps $where HAVING relev>11.0) c GROUP BY beatmapset_id ORDER BY ranking_data DESC, relev DESC LIMIT $limit");
                 $c = 0;
        echo json_encode(["count" => count($beatmaps)]);
        if(count($beatmaps) == 0){
            echo '<center><h3> Ничего не найдено!</h3></center>';
        }else
                        foreach ($beatmaps as $n => $beatmap) {
                         
                        if ($c == 0) {
                            echo '<div class="row"> <!-- row start -->
                            ';
                        }
                        $c++;

                      
						$img = "https://assets.ppy.sh/beatmaps/".$beatmap["beatmapset_id"]."/covers/cover.jpg";
                    //    if($beatmap["ranked_status_freezed"] == 1)
                       // $img = "http://b.ppy.sh/thumb/".$beatmap["beatmapset_id"]."l.jpg";
						echo '<a target="_blank" href="https://osu.gatari.pw/s/'.$beatmap["beatmapset_id"].'"><div class="col-sm-3">
							<audio id="audio_'.$beatmap["beatmapset_id"].'" src="https://b.ppy.sh/preview/'.$beatmap["beatmapset_id"].'.mp3"></audio>
									<div class="card hovercard">
								<div class="cardheader" id="'.$beatmap["beatmapset_id"].'" style="background: url(\''.$img.'\');" ></div>';
								if ($beatmap["ranked_status_freezed"] == 1)
									echo '<div class="corner-ribbon">Ranked on Gatari!</div>';
								echo '<div class="info">
									<!--<div class="play-download">
										<a class="btn btn-circle btn-pink btn-sm" onclick="play('.$beatmap["beatmapset_id"].')"><i id="icon_'.$beatmap["beatmapset_id"].'" class="fa fa-play"></i></a>
										<a class="btn btn-circle btn-orange btn-sm" target="_blank" href="https://osu.gatari.pw/d/'.$beatmap["beatmapset_id"].'"><i class="fa fa-download"></i></a>
									</div>-->
									<div class="desc">
										'.$beatmap["artist"].'
									</div>
									<div class="title">'.$beatmap["title"].'
									</div>
									<div class="desc">
										Mapped by '.(!empty($beatmap["creator"]) ? $beatmap["creator"] : "Someone").'
									</div>
								</div></a>
								<div class="bottom">
									
									';
											$beatmapDiffs = $GLOBALS['db']->fetchAll("SELECT mode,difficulty_std, difficulty_taiko, difficulty_ctb, difficulty_mania, version FROM beatmaps WHERE beatmapset_id = ".$beatmap["beatmapset_id"]." ORDER BY difficulty_std ASC");
                                    $icon = ["std" => "", "taiko" => "-t", "mania" => "-m", "ctb" => "-f"];
                                        foreach ($beatmapDiffs as $key => $beatmap) {
                                            $mode = getPlaymodeText($beatmap["mode"]);
                                        $diff =  floatval($beatmap["difficulty_$mode"]);
                                        if ($diff == 0)
                                            continue;
                                        if ($diff >= 5.25)
                                            $diffName = 'expert'.$icon[$mode];
                                        else if ($diff >= 3.75)
                                            $diffName = 'insane'.$icon[$mode];
                                        else if ($diff >= 2.25) 
                                            $diffName = 'hard'.$icon[$mode];
                                        else if ($diff >= 1.5)
                                            $diffName = 'normal'.$icon[$mode];
                                        else if ($diff > 0)
                                            $diffName = 'easy'.$icon[$mode];
                                        else
                                            $diffName = 'unknown';
                                echo '<a href="#" data-toggle="popover" data-placement="bottom" data-content="'.$beatmap["version"].'" data-trigger="hover" data-original-title="" title=""><div class="diffIcon '.$diffName.'"></div></a>';
                                    }  
                                    
									echo '
								</div>
							</div>
						</div>
						';


                        if ($c == 4 || $n == count($beatmaps)-1) {
                            $c = 0;
                            echo '</div> <!-- row end -->
                            ';
                        }
                    }
                    die();
}
if($method[0] == "clear_donor") { 
    $expired = $GLOBALS["db"]->fetchAll("SELECT `id` FROM `users` WHERE privileges & 4 > 0 AND donor_expire <= ?",[time()]);
    if(count($expired) > 0){
        foreach($expired as $lox) { 
            $userid = $lox['id'];
         
            $GLOBALS["db"]->execute("UPDATE users_stats SET can_custom_badge = ? WHERE id = ? LIMIT 1", [0, $userid]);
        }
    }
    $GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~4 WHERE privileges & 4 > 0 AND donor_expire <= ?",[time()]);
$response = array("code" => 200, "result" => "ok");
}
elseif($method[0] == "peppylast"){
    $username = $method[1];
   $info = $GLOBALS["db"]->fetchAll("SELECT beatmaps.song_name as sn, scores.*,
			beatmaps.beatmap_id as bid, beatmaps.difficulty_std, beatmaps.difficulty_taiko, beatmaps.difficulty_ctb, beatmaps.difficulty_mania, beatmaps.max_combo as fc FROM scores LEFT JOIN beatmaps ON beatmaps.beatmap_md5=scores.beatmap_md5 LEFT JOIN users ON users.id = scores.userid WHERE users.username = ? ORDER BY scores.time DESC LIMIT 1",[$username])[0];
 die(json_encode($info));
}

elseif($method[0] == "yandexcheck"){
    try{
$amount = $_POST["amount"];
$lel = json_encode($_POST);
$data = explode(" - ",$_POST["label"]);
$months = $data[1];
$nickname=  $data[0];
$realprice = getDonorPrice($months);
if($realprice > $amount) 
$response = array("code" => 200, "result" => "fuck you. realprice $realprice | $lel");
else{
    $userData = $GLOBALS['db']->fetchAll("SELECT * FROM `users` WHERE `username` LIKE '$nickname' ")[0]; 
    if(!hasPrivilege(Privileges::UserDonor, $userData["id"])){

    $GLOBALS["db"]->execute("UPDATE users_stats SET can_custom_badge = ? WHERE id = ? LIMIT 1", [1, $userData["id"]]);
    }  
    $start = $userData["donor_expire"];
    if ($start < time()) 
        $start = time();
    $unixPeriod = $start+((30*86400)*$months);
    $GLOBALS["db"]->execute("UPDATE users_stats SET username_changed = 0 WHERE id = ?",[$userData["id"]]);
    $GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | ".Privileges::UserDonor.", donor_expire = ? WHERE id = ?", [$unixPeriod, $userData["id"]]);

    $response = array("code" => 200, "result" => "zabok ept | $lel");
}
    }catch(Exception $e) {
        $response = array("code" => 200, "result" => "fuck you. realprice $realprice | $lel");
    }
    finally{
    $re = json_encode($response);
    $GLOBALS['db']->execute("UPDATE `xd` SET `dfaa` = '$re' WHERE `xd`.`id` = 1;");
}
}
elseif($method[0] == "verifiedStatus")
{
    $u = $_GET["u"];
    $xd = $GLOBALS['db']->fetch("SELECT * FROM users WHERE `id` = '$u'");   
    $result = -1;
    if ($xd["privileges"] & 3 > 0) 
        $result = 1;
    if(isset($_GET['callback'])) {
    return print($_GET['callback']."(".json_encode(array("status" => 200, "message" => "ok", "result" => $result)).")");
    }else
    return print(json_encode(array("status" => 200, "message" => "ok", "result" => $result)));
    
}
elseif($method[0] == "users"){
    if($method[1] == "favs"){
        $u = $_GET["u"];
        $h = $_GET["h"];
     if (!PasswordHelper::CheckPass($u, $h, true)) {
            die("error:pass");
             //   throw new Exception('error:pass');
            }
            $us = $GLOBALS['db']->fetch('
            SELECT
                users.id, users.password_md5,
                users.username FROM users
            WHERE users.username_safe = ?', [safeUsername($u)]);
         //   print_r($us);
    $favs = $GLOBALS["db"]->fetchAll("SELECT beatmapset_id FROM favourite_beatmaps WHERE userid = ?",[$us["id"]]);
    foreach ($favs as $f)
    echo $f["beatmapset_id"]."\n";
die();
    }
    if($method[1] == "favourites"){
        $u = $method[2];
        if(isset($_GET["p"])){
            $limit = $_GET["p"]*$_GET["l"];
                $limit = sprintf("%s,%s",intval($limit)-intval($_GET["l"]), $_GET["l"]);
        }
     
        $maps = [];
        $favs = $GLOBALS["db"]->fetchAll("SELECT ANY_VALUE(beatmaps.artist) artist, ANY_VALUE(beatmaps.title) title , ANY_VALUE(beatmaps.creator) creator, ANY_VALUE(favourite_beatmaps.id),beatmaps.beatmapset_id FROM favourite_beatmaps LEFT JOIN beatmaps ON (beatmaps.beatmapset_id = favourite_beatmaps.beatmapset_id)  WHERE favourite_beatmaps.userid = $u GROUP BY beatmaps.beatmapset_id LIMIT $limit");
            foreach ($favs as $key => $fav) {
                $song_name = $fav["artist"]." - ".$fav["title"];
                  array_push($maps, ["song_name"=>$song_name,"creator"=>$fav["creator"],"beatmapset_id"=>intval($fav["beatmapset_id"])]);
            }
            die(json_encode(["code"=>200, "beatmaps"=>$maps]));
    }
    if($method[1] == "report"){
    sessionCheck();
    $u = $_GET["u"];
if($_SESSION["userid"] != $u){
$isLegit = $GLOBALS['db']->fetch("SELECT id FROM `user_badges` WHERE user = ? AND badge = 4",[$u]);
$salo = $GLOBALS['db']->fetch("SELECT silence_end FROM users WHERE user = ?",[$_SESSION["userid"]])['silence_end'];
$uselessCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE from_uid = ? AND assigned = -2 AND time >= ? LIMIT 1", [$_SESSION["userid"], time() - 86400 * 14])["count"];
        	if((!$isLegit) && $uselessCount < 4){
        		if($salo < time() ){
    $GLOBALS['db']->execute("INSERT INTO reports (id, from_uid, to_uid, reason, chatlog, time) VALUES (NULL, ?, ?, ?, ?, ?)",[$_SESSION["userid"],$u,"Report - Profile","",time()]);
 $from = "[https://osu.gatari.pw/u/".$_SESSION['userid']." ".$_SESSION['username']."]";
    $to = "[https://osu.gatari.pw/u/".$u." ".getUserUsername($u)."]";
    $msg = urlencode("$from has reported $to on Profile");
    $requesturl = $URL['bancho']."/api/v1/fokabotMessage?k=FUCKTHISSHITABC123&to=%23admin"."&msg=$msg";
    getJsonCurl($requesturl,10,true);
}
}
}
    $response = array("code" => 200, "info" => "ok");
    }
    if($method[1] == "charts"){
        $m = getPlaymodeText($_GET["m"]);
        $u = $_GET["u"];
        $charts = $GLOBALS["db"]->fetchAll("SELECT pp_".$m." FROM user_charts WHERE userid = ? LIMIT 30",[$u]);
        $ch = [];
        foreach ($charts as $key => $chart) {
         array_push($ch, intval($chart["pp_".$m]));

        }
        $ppnow = $GLOBALS["db"]->fetch("SELECT pp_".$m." FROM users_stats WHERE id = ?",[$u])["pp_".$m];
        array_push($ch, intval($ppnow));
        die(json_encode(["code"=>200, "charts"=>$ch]));
    }
    if($method[1] == "stats"){
        $username = $_GET["u"];
        $xd = $GLOBALS['db']->fetchAll("SELECT * FROM `users_stats` WHERE `username`='$username' ")[0]; 
        if (!isset($xd) || !empty($xd)){
        $id = $xd["id"];
        $mode = getPlaymodeText($xd["favourite_mode"]);
        $rank = ($GLOBALS['db']->fetchAll("SELECT * FROM `leaderboard_$mode` WHERE `user`='$id' ")[0])["position"]; 
        $pp = $xd["pp_$mode"];
        $acc = $xd["avg_accuracy_$mode"];
        $pc = $xd["playcount_$mode"];
        $stats = array("id" => intval($id),"pp" => intval($pp), "playcount" => $pc, "accuracy" => floatval($acc), "rank" => intval($rank), "mode" =>$mode);
        }
        $response = array("code" => 200, "stats" => $stats);
    }
    if($method[1] == "privileges"){
        $username = $_GET["u"];
        $privileges = $GLOBALS['db']->fetch("SELECT privileges,id FROM `users` WHERE username LIKE '$username' LIMIT 1"); 
        $response = array("code" => 200, "info" => ["privileges" => intval($privileges["privileges"]), "userid"=> intval($privileges["id"])]);
    }
    elseif($method[1] == "scores")
    {
             if (!checkLoggedIn()) {
                $imAdmin = false;
            } else {
                $imAdmin = hasPrivilege(Privileges::AdminManageUsers);
                $wh = "";
            }
        $id = $_GET['id'];
        $l = $_GET['l'];
        $m = intval($_GET['mode']);
        $p = $_GET['p'];
        $page = ($l * $p) - $l;
        $page0 = $l;
        if ($m == 0 || $m == 3)
            $order = ($method[2] == "recent" ? "AND completed > 0 ORDER BY `id"  : "AND completed=3 ORDER BY `pp");
        else 
           $order = ($method[2] == "recent" ? "AND completed > 0 ORDER BY `id" : "AND completed=3 ORDER BY `score");
       if ($method[2] == "first"){
       
        $countF = 0;
        $topPlays = $GLOBALS["db"]->fetchAll("SELECT o.* FROM (SELECT * from scores WHERE userid IN (SELECT userid from users where privileges > 2 )) o LEFT JOIN (SELECT * from scores WHERE userid IN (SELECT userid from users where privileges > 2)) b on o.beatmap_md5 = b.beatmap_md5 AND o.score < b.score WHERE b.score is NULL AND o.userid = $id AND o.play_mode = '$m' AND o.completed = 3 ORDER BY o.id DESC LIMIT $page,$page0");
        $countF = intval($GLOBALS["db"]->fetch("SELECT FOUND_ROWS(); AS count")["count"]);
        $topPlays = $topPlays[0];
        $scores = [];
        for($i = 0; $i != count($topPlays); $i++)
        { 
            $data = $topPlays[$i];
            $md5 = $data["beatmap_md5"];
            $scid = $data["id"];
            $pp = floatval($data['pp']);
            $mode = getPlaymodeText($data['play_mode']);
            $xd = $GLOBALS['db']->fetchAll("SELECT *, max_combo AS FC FROM `beatmaps` WHERE `beatmap_md5`='$md5' ")[0];  
            if($xd["ranked"] < 0)
            	continue;
            $time = intval($data["time"]);
            $score = (array("id"=> $data['id'],"beatmap_md5" => $data["beatmap_md5"], "score"=>intval($data['score']),"max_combo"=>intval($data["max_combo"]),"full_combo" => ($data["full_combo"] == "1" ? "true" : "false"), "mods"=>intval($data['mods']),"count_300"=>intval($data["300_count"]),"count_100" => intval($data["100_count"]),"count_50"=>intval($data["50_count"]),"count_gekis"=>intval($data["gekis_count"]),"count_katu"=>intval($data["katus_count"]),"count_miss" => intval($data["misses_count"]),"time" => date("c",$time), "play_mode" => intval($data["play_mode"]), "accuracy"=>floatval($data['accuracy']), "pp"=>$pp,"completed"=>intval($data['completed']), "beatmap" => array("beatmap_id" => $xd["beatmap_id"], "beatmapset_id" => $xd['beatmapset_id'], "beatmap_md5" => $xd["beatmap_md5"], "song_name"=>$xd['song_name'], "ar" => $xd["ar"], "od"=>$xd["od"],"difficulty" => floatval($xd["difficulty_$mode"])), "difficulty2"=> array("std" => $xd['difficulty_std'],"taiko" => $xd['difficulty_taiko'],"ctb" => $xd['difficulty_ctb'],"mania" => $xd['difficulty_mania']), "hit_length"=>intval($xd["hit_length"]),"fc"=>intval($xd["FC"]),"ranked" =>intval($xd["ranked"]),"ranked_status_frozen" => intval($xd['ranked_status_frozen']),"latest_update" => $xd['latest_update'])) ;
            array_push($scores, $score);
            
        }


       }
       else{
        $topPlays = $GLOBALS['db']->fetchAll("SELECT * FROM scores WHERE userid=$id AND play_mode=$m $order` DESC LIMIT $page,$page0");
        $scores = [];
        for($i = 0; $i != count($topPlays); $i++)
        { 
            $data = $topPlays[$i];
            $md5 = $data["beatmap_md5"];
            $scid = $data["id"];
            $pp = floatval($data['pp']);
            //if($data["completed"] < 3)
              //  $pp = intval($data['score']);
            $mode = getPlaymodeText($data['play_mode']);
            $xd = $GLOBALS['db']->fetchAll("SELECT *, max_combo AS FC FROM `beatmaps` WHERE `beatmap_md5`='$md5' ")[0];  
            $time = intval($data["time"]);
            $score = (array("id"=> $data['id'],"beatmap_md5" => $data["beatmap_md5"], "score"=>intval($data['score']),"max_combo"=>intval($data["max_combo"]),"full_combo" => ($data["full_combo"] == "1" ? "true" : "false"), "mods"=>intval($data['mods']),"count_300"=>intval($data["300_count"]),"count_100" => intval($data["100_count"]),"count_50"=>intval($data["50_count"]),"count_gekis"=>intval($data["gekis_count"]),"count_katu"=>intval($data["katus_count"]),"count_miss" => intval($data["misses_count"]),"time" => date("c",$time), "play_mode" => intval($data["play_mode"]), "accuracy"=>floatval($data['accuracy']), "pp"=>$pp,"completed"=>intval($data['completed']), "beatmap" => array("beatmap_id" => $xd["beatmap_id"], "beatmapset_id" => $xd['beatmapset_id'], "beatmap_md5" => $xd["beatmap_md5"], "song_name"=>$xd['song_name'], "ar" => $xd["ar"], "od"=>$xd["od"],"difficulty" => floatval($xd["difficulty_$mode"])), "difficulty2"=> array("std" => $xd['difficulty_std'],"taiko" => $xd['difficulty_taiko'],"ctb" => $xd['difficulty_ctb'],"mania" => $xd['difficulty_mania']), "hit_length"=>intval($xd["hit_length"]),"fc"=>intval($xd["FC"]),"ranked" =>intval($xd["ranked"]),"ranked_status_frozen" => intval($xd['ranked_status_frozen']),"latest_update" => $xd['latest_update'])) ;
            array_push($scores, $score);
            
        }
    }
    $privileges = intval($GLOBALS['db']->fetch("SELECT privileges,id FROM `users` WHERE id = $id LIMIT 1")["privileges"]); 
    if($imAdmin == False)
        if($privileges < 3)
            $scores = null;

     if(count($scores) == 0)
            $scores = null;
        $response = array("code" => 200, "scores" => $scores,"count"=>$countF);
    }elseif($method[1] == "lookup"){
        $name = $_GET['name'];
        $users= [];
        $xd = $GLOBALS['db']->fetchAll("SELECT * FROM users WHERE username LIKE '%$name%' LIMIT 10");   
         for($i = 0; $i != count($xd); $i++)
        {
            $data = $xd[$i];
            if ($data["privileges"] & 3 > 0) {
            array_push($users,array("id"=>intval($data["id"]), "username"=>$data["username"]));
                }
        }
        if(count($users) == 0)
            $users = null;
        $response = array("code" => 200, "users" => $users);
    }
}elseif($method[0] =="beatmaps"){
    if ($method[1] == "favourite"){
      sessionCheck();
        $beatmapSetId = $method[2];
        if($_GET["action"] == "add") {
        $count = $GLOBALS["db"]->fetch("select count(*) count from favourite_beatmaps where userid = ?",[$_SESSION["userid"]])["count"];
        if($count > 99)
            die(json_encode(["code"=>200,"info"=>"The list of favourite maps is full"]));
         $count = $GLOBALS["db"]->fetch("select count(*) count from favourite_beatmaps where beatmapset_id = ? AND userid = ?",[$beatmapSetId,$_SESSION["userid"]])["count"];
         if($count > 0)
             die(json_encode(["code"=>200,"info"=>"This beatmap already favourited!"]));
        $GLOBALS['db']->execute("INSERT INTO favourite_beatmaps (userid, beatmapset_id) VALUES (?, ?)",[$_SESSION["userid"], $beatmapSetId]);
        $GLOBALS["db"]->execute("update beatmaps set favourite_count = favourite_count + 1 WHERE beatmapset_id = ?",[$beatmapSetId]);
          die(json_encode(["code"=>200,"info"=>"Beatmap added to favourites!"]));
    }else if($_GET["action"] == "del") {
         $count = $GLOBALS["db"]->fetch("select count(*) count from favourite_beatmaps where beatmapset_id = ? AND userid = ?",[$beatmapSetId,$_SESSION["userid"]])["count"];
         if($count < 1) 
            die(json_encode(["code"=>200,"info"=>"This beatmap not favourited!"]));
        $GLOBALS['db']->execute("DELETE FROM favourite_beatmaps WHERE userid = ? AND beatmapset_id = ?",[$_SESSION["userid"], $beatmapSetId]);
        $GLOBALS["db"]->execute("update beatmaps set favourite_count = favourite_count - 1 WHERE beatmapset_id = ?",[$beatmapSetId]);
          die(json_encode(["code"=>200,"info"=>"Beatmap removed from favourites!"]));
    }
}
    if($method[1] == "report"){
        sessionCheck();
        $u = $_GET["u"];
        $b = $_GET["b"];
        $s = $_GET["s"];
        if($_SESSION["userid"] != $u){
$isLegit = $GLOBALS['db']->fetch("SELECT id FROM `user_badges` WHERE user = ? AND badge = 4",[$u]);
$salo = $GLOBALS['db']->fetch("SELECT silence_end FROM users WHERE user = ?",[$_SESSION["userid"]])['silence_end'];
$uselessCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE from_uid = ? AND assigned = -2 AND time >= ? LIMIT 1", [$_SESSION["userid"], time() - 86400 * 14])["count"];
        	if((!$isLegit) && $uselessCount < 4){
        		if($salo < time()){
        $GLOBALS['db']->execute("INSERT INTO reports (id, from_uid, to_uid, reason, chatlog, time) VALUES (NULL, ?, ?, ?, ?, ?)",[$_SESSION["userid"],$u,"Report score https://osu.gatari.pw/web/replays/$s  on https://osu.gatari.pw/b/$b","",time()]);
 $from = "[https://osu.gatari.pw/u/".$_SESSION['userid']." ".$_SESSION['username']."]";
    $to = "[https://osu.gatari.pw/u/".$u." ".getUserUsername($u)."]";
    $msg = urlencode("$from has reported $to on https://osu.ppy.sh/b/$b");
    $requesturl = $URL['bancho']."/api/v1/fokabotMessage?k=FUCKTHISSHITABC123&to=%23admin"."&msg=$msg";
    getJsonCurl($requesturl,10,true);
}
}
}
        $response = array("code" => 200, "info" => "ok");
   
    }
    if($method[1] == "scores"){
        $type = $_GET["type"];
        $m = $_GET["mode"];
        $mods = ($_GET["m"] != -1 ? " AND scores.mods = ".$_GET["m"] : "");
        $where = "beatmap_id";
        if($type == "s")
            $where = "beatmapset_id";
        $beatmap = $method[2];
        $topPlays = $GLOBALS["db"]->fetchAll("SELECT scores.id, users.username, users.id as userid, scores.score,scores.pp,scores.play_mode, scores.accuracy, scores.max_combo, beatmaps.max_combo as fc, scores.100_count,scores.300_count,scores.50_count,scores.misses_count,scores.mods,scores.time FROM `beatmaps` LEFT JOIN scores ON (scores.beatmap_md5 = beatmaps.beatmap_md5) LEFT JOIN users ON (scores.userid = users.id)  WHERE beatmaps.".$where."='$beatmap' AND scores.completed = 3 AND scores.play_mode = '$m' $mods AND users.privileges & 1 > 0  ORDER BY scores.score DESC LIMIT 50");
       
        $scores = [];
        foreach ($topPlays as $key => $play) {
            $country = $GLOBALS["db"]->fetch("SELECT country FROM `users_stats` WHERE id = ? LIMIT 1",[$play["userid"]])["country"];
           $score = ["id"=>intval($play["id"]),"username"=>$play["username"],"userid"=>intval($play["userid"]),"country"=>$country,"score"=>intval($play["score"]),"accuracy"=>floatval($play["accuracy"]),"max_combo"=>intval($play["max_combo"]),"fc"=>intval($play["fc"]),"count_300"=>intval($play["300_count"]),"count_100"=>intval($play["100_count"]),"count_50"=>intval($play["50_count"]),"count_miss"=>intval($play["misses_count"]),"mods"=>intval($play["mods"]),"pp"=>floatval($play["pp"]),"play_mode"=>intval($play["play_mode"]),"time"=>date("c",intval($play["time"]))];
           array_push($scores, $score);
        }
         if(count($scores) == 0)
            $scores = null;
        $response = array("code" => 200, "scores" => $scores);
    }
           
   if($method[1] == "search")
    {
        $r = $_GET['r'];
        $q = $_GET['q'];
        $m = $_GET['m'];
        $p = $_GET['p'];
       print(get_contents_http("http://osu.ppy.sh/web/osu-search.php?u=dstemboh&h=da39103a62c7c8906968ce29b2f471a2&r=$r&q=$q&m=$m&p=$p"));
        return;
    }
    if($method[1] == "searchset")
    {  
        $p = "s";
        $q = $_GET['s'];
        if (isset($_GET['b']))
        {
            $q = $_GET['b'];
            $p = "b";
        }
       print(get_contents_http("http://osu.ppy.sh/web/osu-search-set.php?u=dstemboh&h=da39103a62c7c8906968ce29b2f471a2&$p=$q"));
        return;
    }
}
print(json_encode($response));

//MODS,ACCURACY, count_300, count_100, count_50, count_miss, beatmap[song_name], score, pp, completed
?>