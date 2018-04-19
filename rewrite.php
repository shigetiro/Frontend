<?php

// Require functions file
require_once './inc/functions.php';
// URI and explode
$uri = explode("?",$_SERVER['REQUEST_URI'])[0];
$uri = explode('/', $uri);
// Redirect to the right url with right parameter
switch ($uri[2]) {

    case 'ucp.php':
        redirect('https://osu.gatari.pw/index.php?p=5');
    break;

	case 'u':
		redirect('https://osu.gatari.pw/u/'.$uri[3]);
	break;
	case 'b':
		redirect('https://osu.gatari.pw/b/'.$uri[3]);
	break;
	case 's':
		redirect('https://osu.gatari.pw/s/'.$uri[3]);
	break;
	case 'doc':
		redirect('https://osu.gatari.pw/doc/'.$uri[3]);
	break;	
    
		// Redirect to bloodcat map download

	case 'd':{
    $bm = $uri[3];
	$username = "dstemboh"; // Логин
	$password = "XXDSTEM123"; // Пароль
    $cookie_file_path = "cookie.txt";
    $postfields['redirect'] ="/p/beatmap?s=$bm&m=0";
    $postfields['sid'] = "";
    $postfields['username'] = $username;
    $postfields['password'] = $password;
    $postfields['autologin'] = "on";
    $postfields['login'] = "login";
    $URL = GetBeatmapURL("https://osu.ppy.sh/forum/ucp.php?mode=login",$postfields,$bm);
    redirect($URL);
	break;
}
		// No matches, redirect to index

	default:
		redirect('https://osu.gatari.pw?');
	break;
}

	function GetBeatmapURL($url, $post = false,$bm = false) {
	$ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_PROXY, '217.29.53.104:13077'); 
       // curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'pf16cx:3DpNPn');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
        curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, "https://osu.ppy.sh/d/$bm");
        curl_exec($ch);
        $response = curl_getinfo($ch,CURLINFO_REDIRECT_URL);
        return $response;
	}
