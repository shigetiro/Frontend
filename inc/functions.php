<?php
/*
 * Ripple functions file
 * include this to include the world
*/
// Include config file and db class
$df = dirname(__FILE__);
require_once $df.'/SendMailSmtpClass.php';
require_once $df.'/config.php';
require_once $df.'/db.php';
require_once $df.'/password_compat.php';
require_once $df.'/Do.php';
require_once $df.'/Print.php';
require_once $df.'/RememberCookieHandler.php';
require_once $df.'/PlayStyleEnum.php';
require_once $df.'/resize.php';
require_once $df.'/SimpleMailgun.php';
require_once $df.'/PrivilegesEnum.php';
// Composer
require_once $df.'/../vendor/autoload.php';
// Helpers
require_once $df.'/helpers/PasswordHelper.php';
require_once $df.'/helpers/UsernameHelper.php';
require_once $df.'/helpers/URL.php';
require_once $df.'/helpers/Schiavo.php';
require_once $df.'/helpers/APITokens.php';
// controller system v2
require_once $df.'/pages/Login.php';
require_once $df.'/pages/Leaderboard.php';
require_once $df.'/pages/PasswordFinishRecovery.php';
require_once $df.'/pages/ServerStatus.php';
require_once $df.'/pages/UserLookup.php';
require_once $df.'/pages/2fa.php';
require_once $df.'/pages/2faSetup.php';
require_once $df.'/pages/RequestRankedBeatmap.php';
require_once $df.'/pages/MyAPIApplications.php';
require_once $df.'/pages/EditApplication.php';
require_once $df.'/pages/DeleteApplication.php';
require_once $df.'/pages/Support.php';
require_once $df.'/pages/Team.php';
require_once $df.'/pages/IRC.php';
require_once $df.'/pages/Beatmaps.php';
require_once $df.'/pages/Verify.php';
require_once $df.'/pages/Welcome.php';
require_once $df.'/pages/Discord.php';
$pages = [
	new UserLookup(),
    new Support(),
    new TwoFASetup(),
    new PasswordFinishRecovery(),
    new Welcome(), 
    new Verify(),
    new RequestRankedBeatmap(),
    new Leaderboard(),
	new Login(),
	new TwoFA(),
	new Beatmaps(),
];



// Set timezone to UTC
date_default_timezone_set('Europe/Rome');
// Connect to MySQL Database
$GLOBALS['db'] = new DBPDO();
// Birthday
global $isBday;
$isBday = date("dm") == "0901";
/****************************************
 **			GENERAL FUNCTIONS 		   **
 ****************************************/

function SendMail($to,$title, $text){
	global $emailPass;
	$mailSMTP = new SendMailSmtpClass('support@Verge.moe', $emailPass, 'ssl://smtp.yandex.ru', "Verge <support@gatari.pw>", 465);
	$headers= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";
$headers .= "From: Verge <support@Verge.moe>\r\n";
$result =  $mailSMTP->send($to, $title,$text, $headers); 
return $result;
}

function redisConnect() {
	return;
}

function redisPublish($method, $params){
	global $redisKey;
    $data = ["method"=>$method, "params"=>$params, "sc"=>$redisKey];
    return post_content_http('http://5.83.160.198:8080/redis.php',$data);
}
/*
 * redirect
 * Redirects to a URL.
 *
 * @param (string) ($url) Destination URL.
*/
function redirect($url) {
	header('Location: '.$url);
	session_commit();
	exit();
}
/*
 * outputVariable
 * Output $v variable to $fn file
 * Only for debugging purposes
 *
 * @param (string) ($fn) Output file name
 * @param ($v) Variable to output
*/
function outputVariable($v, $fn = "/tmp/ripple.txt") {
	file_put_contents($fn, var_export($v, true), FILE_APPEND);
}
/*
 * randomString
 * Generate a random string.
 * Used to get screenshot id in osu-screenshot.php
 *
 * @param (int) ($l) Length of the generated string
 * @return (string) Generated string
*/
function randomString($l, $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
	$res = '';
	srand((float) microtime() * 1000000);
	for ($i = 0; $i < $l; $i++) {
		$res .= $c[rand() % strlen($c)];
	}
	return $res;
}

	/*
	 * generateKey
	 * Generate a single beta key
	 *
	 * @return (string) the beta key
	 */
	function generateKey() {
		$dict = "0123456789abcdef";
		$t = 4;
		$key = "";
		while ($t != 0) {
			$i = 4;
			while ($i != 0) {
				$key .= $dict[rand(1,strlen($dict)) - 1];
				$i -= 1;
			}
			if ($t != 1)
				$key .= "-";
			$t -= 1;
		}
		return $key;
	}

function getIP() {
	return getenv('REMOTE_ADDR'); // Add getenv('HTTP_FORWARDED_FOR')?: before getenv if you are using a dumb proxy. Meaning that if you try to get the user's IP with REMOTE_ADDR, it returns 127.0.0.1 or keeps saying the same IP, always.
	// NEVER add getenv('HTTP_FORWARDED_FOR') if you're not behind a proxy.
	// It can easily be spoofed.
}
/****************************************
 **		HTML/PAGES   FUNCTIONS 		   **
 ****************************************/
/*
 * setTitle
 * sets the title of the current $p page.
 *
 * @param (int) ($p) page ID.
*/
function setTitle($p) {
	if (isset($_COOKIE['st']) && $_COOKIE['st'] == 1) {
		// Safe title, so Peppy doesn't know we are browsing Ripple
		return '<title>Google</title>';
	} else {
		$namesRipple = [
			1 =>   'Custom osu! server',
			3 =>   'Register',
			4 =>   'User CP',
			5 =>   'Change avatar',
			6 =>   'Edit user settings',
			7 =>   'Change password',
			8 =>   'Edit userpage',
			14 =>  'Documentation files',
			16 =>  'Read documentation',
			17 =>  'Changelog',
			18 =>  'Recover your password',
			21 =>  'About',
			23 =>  'Rules',
			26 =>  'Friends',
			36 =>	'Игры разума',
			41 =>  'Elmo! Stop!',
			88 => 'Beatmap',
			89 => 'Match',
			'u' => 'Userpage',
		];
		$namesRAP = [
			99 =>  'You\'ve been tracked',
			100 => 'Dashboard',
			101 => 'System settings',
			102 => 'Users',
			103 => 'Edit user',
			104 => 'Change identity',
			106 => 'Documentation Pages',
			107 => 'Edit documentation page',
			108 => 'Badges',
			109 => 'Edit Badge',
			110 => 'Edit user badges',
			111 => 'Bancho settings',
			112 => 'View Beta keys',
			113 => 'Add Beta key',            
			116 => 'Admin Logs',
			117 => 'Rank requests',
			118 => 'Privilege Groups',
			119 => 'Edit privilege group',
			120 => 'View users in privilege group',
			121 => 'Give Donor',
			122 => 'Rollback user',
			123 => 'Wipe user',
			124 => 'Rank beatmap',
			125 => 'Rank beatmap manually',
			126 => 'Reports',
			127 => 'View report',
			128 => 'Charts',
			129 => "Add chart",
		];
		if (isset($namesRipple[$p])) {
			return __maketitle('Vipsu', $namesRipple[$p]);
		} else if (isset($namesRAP[$p])) {
			return __maketitle('VAP', $namesRAP[$p]);
		} else {
			return __maketitle('Vipsu', '404');
		}
	}
}
function __maketitle($b1, $b2) {
	return "<title>$b1 - $b2</title>";
}
/*
 * printPage
 * Prints the content of a page.
 * For protected pages (logged in only pages), call first sessionCheck() and then print the page.
 * For guest pages (logged out only pages), call first checkLoggedIn() and if false print the page.
 *
 * @param (int) ($p) page ID.
*/
function printPage($p) {
	$exceptions = ['pls goshuujin-sama do not hackerino &gt;////&lt;', 'Only administrators are allowed to see that documentation file.', "<div style='font-size: 40pt;'>ATTEMPTED USER ACCOUNT VIOLATION DETECTED</div>
			<p>We detected an attempt to violate an user account. If you didn't do this on purpose, you can ignore this message and login into your account normally. However if you changed your cookies on purpose and you were trying to access another user's account, don't do that.</p>
			<p>By the way, the attacked user is aware that you tried to get access to their account, and we removed all permanent login hashes. We wish you good luck in even finding the new 's' cookie for that user.</p>
			<p>Don't even try.</p>", 9001 => "don't even try"];
	if (!isset($_GET['u']) || empty($_GET['u'])) {
		// Standard page
		switch ($p) {
				// Error page

			case 99:
				if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
					$e = $_GET['e'];
				} elseif (isset($_GET['e']) && strlen($_GET['e']) > 12 && substr($_GET['e'], 0, 12) == 'do_missing__') {
					$s = substr($_GET['e'], 12);
					if (preg_match('/^[a-z0-9-]*$/i', $s) === 1) {
						P::ExceptionMessage('Missing parameter while trying to do action: '.$s);
						$e = -1;
					} else {
						$e = '9001';
					}
				} else {
					$e = '9001';
				}
				if ($e != -1) {
					P::ExceptionMessage($exceptions[$e]);
				}
			break;
				// Home
                
            case 38:
				P::Verify();
			break;    
            case 39:
				P::Welcome();
			break;
            case 41:
                P::StopSign();
                break;
			case 1:
				P::HomePage();
			break;
            case 16:
                P::Documentation();
            break;
					// Register page (guest)

				case 3:
					if (!checkLoggedIn()) {
						P::RegisterPage();
					} else {
						P::LoggedInAlert();
					}
				break;
            case 6:
				P::userSettingsPage();
			break;
			case 7:
				P::ChangePasswordPage();
			break;
            case 8:
				P::UserpageEditorPage();
			break;
            case 5:
				P::ChangeAvatarPage();
			break;
            case 18: 
                P::PasswordRecovery();
            break;
            case 26:
                P::FriendlistPage();
            break;
            case 36:
            	P::ChartPage();
            break;

				// Admin panel (> 100 pages are admin ones)
			case 100:
				sessionCheckAdmin();
				P::AdminDashboard();
			break;
				// Admin panel - System settings

			case 101:
				sessionCheckAdmin(Privileges::AdminManageSettings);
				P::AdminSystemSettings();
			break;
				// Admin panel - Users

			case 102:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminUsers();
			break;
				// Admin panel - Edit user

			case 103:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminEditUser();
			break;
				// Admin panel - Change identity

			case 104:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminChangeIdentity();
			break;
				// Admin panel - Documentation

			case 106:
				sessionCheckAdmin(Privileges::AdminManageDocs);
				P::AdminDocumentation();
			break;
				// Admin panel - Edit Documentation file

			case 107:
				sessionCheckAdmin(Privileges::AdminManageDocs);
				P::AdminEditDocumentation();
			break;
				// Admin panel - Badges

			case 108:
				sessionCheckAdmin(Privileges::AdminManageBadges);
				P::AdminBadges();
			break;
				// Admin panel - Edit badge

			case 109:
				sessionCheckAdmin(Privileges::AdminManageBadges);
				P::AdminEditBadge();
			break;
				// Admin panel - Edit uesr badges

			case 110:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminEditUserBadges();
			break;
				// Admin panel - System settings

			case 111:
				sessionCheckAdmin(Privileges::AdminManageSettings);
				P::AdminBanchoSettings();
			break;

			case 112:
				sessionCheckAdmin(Privileges::AdminManageBetaKeys);
				P::AdminBetaKeys();
			break;                   
                
			case 113:
				sessionCheckAdmin(Privileges::AdminManageBetaKeys);
				P::AdminAddKey();
			break;                

			// Admin panel - Admin logs
			case 116:
				sessionCheckAdmin(Privileges::AdminViewRAPLogs);
				P::AdminLog();
			break;

			// Admin panel - Beatmap rank requests
			case 117:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankRequests();
			break;

			// Admin panel - Privileges Groups
			case 118:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminPrivilegesGroupsMain();
			break;

			// Admin panel - Privileges Groups
			case 119:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminEditPrivilegesGroups();
			break;

			// Admin panel - Show users in group
			case 120:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminShowUsersInPrivilegeGroup();
			break;

			// Admin panel - Give donor to user
			case 121:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminGiveDonor();
			break;

			// Admin panel - Rollback User
			case 122:
				sessionCheckAdmin(Privileges::AdminWipeUsers);
				P::AdminRollback();
			break;

			// Admin panel - Wipe User
			case 123:
				sessionCheckAdmin(Privileges::AdminWipeUsers);
				P::AdminWipe();
			break;

			// Admin panel - Rank beatmap
			case 124:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankBeatmap();
			break;

			// Admin panel - Rank beatmap manually
			case 125:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankBeatmapManually();
			break;
			
			// Admin panel - Reports
			case 126:
				sessionCheckAdmin(Privileges::AdminManageReports);
				P::AdminViewReports();
			break;

			// Admin panel - View report
			case 127:
				sessionCheckAdmin(Privileges::AdminManageReports);
				P::AdminViewReport();
			break;
			case 87:
				P::ClanPage($_GET["clan"]);
				break;
			case 88:
			 	P::Beatmap($_GET["b"],$_GET["t"]);
			 break;
			case 89:
			 	P::Match($_GET["m"]);
			 break;
			// 404 page
			default:
				define('NotFound', '<br><h1>404</h1><p>Page not found. Meh.</p>');
				if ($p < 100)
					echo NotFound;
				else {
						echo '
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div id="content">
					' . NotFound . '
                    </div>
                </div>
            </div>
        </div>';
				}
			break;
		}
	} else {

			// Userpage
			if(isset($_GET["u"]))
				 	P::UserPage($_GET["u"], isset($_GET['m']) ? $_GET['m'] : -1);				 				
	}
}
/*
 * printNavbar
 * Prints the navbar.
 * To print tabs only for guests (not logged in), do
 *	if (!checkLoggedIn()) echo('stuff');
 *
 * To print tabs only for logged in users, do
 *	if (checkLoggedIn()) echo('stuff');
 *
 * To print tabs for both guests and logged in users, do
 *	echo('stuff');
*/
function printNavbar() {
	global $discordConfig;
	echo '<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
				<div class="container animated fadeIn">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>';
						global $isBday;
						echo $isBday ? '<a class="navbar-brand" href="/"><i class="fa fa-birthday-cake"></i><img src="/images/logos/text-1.png" style="height: 140%; display: inline; padding-left: 10px;"></a>' : '<a class="navbar-brand" href="/"><img style="height: 140%;" class="animated fadeInTop" src="/images/logos/text-1.png"></a>';
					echo '</div>
					<div class="navbar-collapse collapse">';
	// Left elements
	// Not logged left elements
	echo '<ul class="nav navbar-nav navbar-left">';
	echo '<li><a href="/leaderboard"><i class="fa fa-list-ol"></i> Leaderboard</a></li>';
	if (checkLoggedIn())
		echo '<li class="dropdown">
					<a href="/beatmaplist"><i class="fa fa-th"></i>	Beatmaps</a><ul class="dropdown-menu">      
						<li class="dropdown-submenu"><a href="/?p=31"><i class="fa fa-music"></i>	Rank Request</a></li>
					</ul> </li><li></li>';
	else
		echo '<li><a href="/beatmaplist"><i class="fa fa-th"></i> Beatmaps</a></li>';
	

	// Logged in left elements
	if (checkLoggedIn()) {
        $animate = "class='animated infinite pulse'";
        if(hasPrivilege(Privileges::UserDonor))
        {
        	$expire = $GLOBALS["db"]->fetch("SELECT donor_expire FROM users WHERE id = ?", [$_SESSION['userid']])['donor_expire'] - time();
        	 $interval = floor($expire / 86400);
        	 $animate = "";
        	 if ($interval < 4){
        	 	$animate = "class='animated infinite bounce'";
        	 	 }
        }
		echo '<li class="support-color"><a href="/support" '.$animate.' ><i class="fa fa-heart"></i>	<b>Support</b></a>';
		// Just an easter egg that you'll probably never notice, unless you do it on purpose.
		if (hasPrivilege(Privileges::AdminAccessRAP)) {
			echo '<li><a href="/p/100"><i class="fa fa-cog"></i>	<b>Admin Panel</b></a></li>';
		}
	}
	// Right elements
	echo '</ul><ul class="nav navbar-nav navbar-right ">';
	echo '<li class="search"><input type="text" style="width:190px; margin-top: 10px;" class="form-control" name="query" id="query" placeholder="Search users..."></li>';
	// Logged in right elements
	if (checkLoggedIn()) {
		global $URL;
		echo '<li class="dropdown">
					<a data-toggle="dropdown"><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="22" width="22" />	<b>'.$_SESSION['username'].'</b><span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li class="dropdown-submenu"> <a href="/u/'.getUserID($_SESSION['username']).'"><i class="fa fa-user"></i> My profile</a></li>
						<li class="dropdown-submenu"><a href="/p/7"><i class="fa fa-key"></i> Change Password</a></li>
                        <li class="dropdown-submenu"><a href="/p/6"><i class="fa fa-pencil"></i> User Settings</a></li>
                        <li class="dropdown-submenu"><a href="/p/26"><i class="fa fa-users"></i> Friend list</a></li>  
                        <li class="dropdown-submenu"><a href="/p/5"><i class="fa fa-photo"></i> Change Avatar</a></li>   
                        <li class="dropdown-submenu"><a href="/p/8"><i class="fa fa-font"></i> Edit Userpage</a></li>  
                        <li class="dropdown-submenu"><a href="/p/30"><i class="fa fa-vk"></i> Two-Factor Auth</a></li> 
						<li class="dropdown-submenu"><a href="/submit.php?action=logout"><i class="fa fa-sign-out"></i>	Logout</a></li>
					</ul>
				</li>';
	}else {
		echo '<li><a href="/p/2"><i class="fa fa-sign-in"></i>	Login</a></li>';
	}
	// Navbar end
	echo '</ul></div></div></nav>';
}

function timeSince($date, $about = true) {
     $seconds =  floor(time() - $date) ;
     $interval = floor($seconds / 31536000);
    if ($interval > 1) {
        return $interval." years";
    }if ($interval == 1){
		return ($about == true ? "about a" : "1")." year";
	}
    $interval = floor($seconds / 2592000);
    if ($interval > 1) {
        return $interval . " months";
    }if ($interval == 1){
		return ($about == true ? "about a" : "1")." month";
	}
    $interval = floor($seconds / 86400);
    if ($interval > 1) {
        return $interval . " days";
    }if ($interval == 1){
        return ($about == true ? "about a" : "1") . " day";
	}
    $interval = floor($seconds / 3600);
    if ($interval > 1) {
        return ($about == true ? "about " : "") . $interval . " hours";
    }if ($interval == 1){
		return ($about == true ? "about a" : "1")." hour";
	}
    $interval = floor($seconds / 60);
    if ($interval > 1) {
        return  ($about == true ? "about " : "") .  $interval . " minutes";
    }if ($interval == 1){
		return ($about == true ? "about a" : "1")." minute";
	}
    return  ($about == true ? "about " : "") .  floor($seconds) ." seconds";
}

/*
 * printAdminSidebar
 * Prints the admin left sidebar
*/
function printAdminSidebar() {
	echo '<div id="sidebar-wrapper">
					<ul class="sidebar-nav">
						<li class="sidebar-brand">
							<a href="#"><i class = "fa fa-cogs"></i>	<b>V</b>ipsu <b>A</b>dmin <b>P</b>anel</a>
						</li>
						<li><a href="/p/100"><i class="fa fa-tachometer"></i>	Dashboard</a></li>';

						if (hasPrivilege(Privileges::AdminManageSettings))
							echo '<li><a href="/p/101"><i class="fa fa-cog"></i>	System settings</a></li>
							<li><a href="/p/111"><i class="fa fa-server"></i>	Bancho settings</a></li>';

						if (hasPrivilege(Privileges::AdminManageUsers))
							echo '<li><a href="/p/102"><i class="fa fa-user"></i>	Users</a></li>';
						
						if (hasPrivilege(Privileges::AdminManageReports))
							echo '<li><a href="/p/126"><i class="fa fa-flag"></i>	Reports</a></li>';

						if (hasPrivilege(Privileges::AdminManageBetaKeys))
							echo '<li><a href="/p/112"><i class="fa fa-key"></i>	Beta Keys</a></li>';    
    
						if (hasPrivilege(Privileges::AdminManagePrivileges))
							echo '<li><a href="/p/118"><i class="fa fa-group"></i>	Privilege Groups</a></li>';

						if (hasPrivilege(Privileges::AdminManageBadges))
							echo '<li><a href="/p/108"><i class="fa fa-certificate"></i>	Badges</a></li>';

						if (hasPrivilege(Privileges::AdminManageDocs))
							echo '<li><a href="/p/106"><i class="fa fa-question-circle"></i>	Documentation</a></li>';

						if (hasPrivilege(Privileges::AdminManageBeatmaps)) {
							echo '<li><a href="/p/117"><i class="fa fa-music"></i>	Rank requests</a></li>';
							echo '<li><a href="/p/125"><i class="fa fa-level-up"></i>	Rank beatmap manually</a></li>';
						}

						if (hasPrivilege(Privileges::AdminViewRAPLogs))
							echo '<li class="animated infinite pulse"><a href="/p/116"><i class="fa fa-calendar"></i>	Admin log&nbsp;&nbsp;&nbsp;<div class="label label-primary">Free botnets</div></a></li>';

						echo "</ul>
				</div>";
}
/*
 * printAdminPanel
 * Prints an admin dashboard panel, used to show
 * statistics (like total plays, beta keys left and stuff)
 *
 * @c (string) panel color, you can use standard bootstrap colors or custom ones (add them in style.css)
 * @i (string) font awesome icon of that panel. Recommended doing fa-5x (Eg: fa fa-gamepad fa-5x)
 * @bt (string) big text, usually the value
 * @st (string) small text, usually the name of that stat
*/
function printAdminPanel($c, $i, $bt, $st) {
	echo '<div class="col-lg-3 col-md-6">
			<div class="panel panel-'.$c.'">
			<div class="panel-heading">
			<div class="row">
			<div class="col-xs-3"><i class="'.$i.'"></i></div>
			<div class="col-xs-9 text-right">
				<div class="huge">'.$bt.'</div>
				<div>'.$st.'</div>
			</div></div></div></div></div>';
}
/*
 * getUserCountry
 * Does a call to ip.zxq.co to get the user's IP address.
 *
 * @returns (string) A 2-character string containing the user's country.
*/
function getUserCountry() {
	$ip = getIP();
	if (!$ip && $ip == '127.0.0.1' && $ip != "::1") {
		return 'XX'; // Return XX if $ip isn't valid.

	}
	// otherwise, retrieve the contents from ip.zxq.co's API
	$data = get_contents_http("http://ip.vanilla.rocks/$ip/country");
	// And return the country. If it's set, that is.
	return strlen($data) == 2 ? $data : 'XX';
}
// updateUserCountry updates the user's country in the database with the country they
// are currently connecting from.
function updateUserCountry($u, $field = 'username') {
	$c = getUserCountry();
	if ($c == 'XX' || empty($c))
		return;
	$GLOBALS['db']->execute("UPDATE users_stats SET country = ? WHERE $field = ?", [$c, $u]);
}
function countryCodeToReadable($cc) {
	require_once dirname(__FILE__).'/countryCodesReadable.php';

	return isset($c[$cc]) ? $c[$cc] : 'Unknown country';
}
/*
 * getAllowedUsers()
 * Get an associative array, saying whether a user is banned or not on Ripple.
 *
 * @returns (array) see above.

function getAllowedUsers($by = 'username') {
	// get all the allowed users in Ripple
	$allowedUsersRaw = $GLOBALS['db']->fetchAll('SELECT '.$by.', allowed FROM users');
	// Future array containing all the allowed users.
	$allowedUsers = [];
	// Fill up the $allowedUsers array.
	foreach ($allowedUsersRaw as $u) {
		$allowedUsers[$u[$by]] = ($u['allowed'] == '1' ? true : false);
	}
	// Free up some space in the ram by deleting the data in $allowedUsersRaw.
	unset($allowedUsersRaw);

	return $allowedUsers;
}*/
/****************************************
 **	 LOGIN/LOGOUT/SESSION FUNCTIONS	   **
 ****************************************/
/*
 * startSessionIfNotStarted
 * Starts a session only if not started yet.
*/
function startSessionIfNotStarted() {
	if (session_status() == PHP_SESSION_NONE)
		session_start();
	if (isset($_SESSION['username']) && !isset($_SESSION['userid']))
		$_SESSION['userid'] = getUserID($_SESSION['username']);
}
/*
 * sessionCheck
 * Check if we are logged in, otherwise go to login page.
 * Used for logged-in only pages
*/
function sessionCheck($redirect = True) {
	try {
		// Start session
		startSessionIfNotStarted();
		// Check if we are logged in
		if (!isset($_SESSION["username"])) {
			if($redirect){
			unset($_SESSION['redirpage']);
			$_SESSION['redirpage'] = $_SERVER['REQUEST_URI'];
			throw new Exception('You are not logged in.');
		}else return;
	}
		// Check if we've changed our password
		if ($_SESSION['passwordChanged']) {
			// Update our session password so we don't get kicked
			$_SESSION['password'] = current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username']));
			// Reset passwordChanged
			$_SESSION['passwordChanged'] = false;
		}
		// Check if our password is still valid
		if (current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username'])) != $_SESSION['password']) {
			throw new Exception('Session expired. Please login again.');
		}
		/* Check if we aren't banned (OLD)
		if (current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username'])) == 0) {
			throw new Exception('You are banned.');
		} */
		// Ban check (NEW)
		if (!hasPrivilege(Privileges::UserNormal)) {
			if($redirect)
				throw new Exception('You are banned.');
		}
		// Set Y cookie
		setYCookie($_SESSION["userid"]);
		// Everything is ok, go on

	}
	catch(Exception $e) {
		addError($e->getMessage());
		// Destroy session if it still exists
		D::Logout();
		// Return to login page
		redirect('/p/2');
	}
}
/*
 * sessionCheckAdmin
 * Check if we are logged in, and we are admin.
 * Used for admin pages (like admin cp)
 * Call this function instead of sessionCheck();
*/
function sessionCheckAdmin($privilege = -1, $e = 0) {
	sessionCheck();
	try {
		// Make sure the user can access RAP and is not banned/restricted
		if (!hasPrivilege(Privileges::AdminAccessRAP) || !hasPrivilege(Privileges::UserPublic) || !hasPrivilege(Privileges::UserNormal)) {
			throw new Exception;
		}

		if ($privilege > -1 && !hasPrivilege($privilege)) {
			throw new Exception;
		}
		return true;
	} catch (Exception $meme) {
		redirect('/p/99&e='.$e);
		return false;
	}
}
/*
 * updateLatestActivity
 * Updates the latest_activity column for $u user
 *
 * @param ($u) (string) User ID
*/

function getPlayMode($beatmap){
	if(floatval($beatmap["difficulty_std"]) > 0)
		return 0;
	else if(floatval($beatmap["difficulty_taiko"]) > 0)
		return 1;
	else if(floatval($beatmap["difficulty_mania"]) > 0)
		return 3;
	else if(floatval($beatmap["difficulty_ctb"]) > 0)
		return 2;
}

function updateLatestActivity($u) {
	$GLOBALS['db']->execute('UPDATE users SET latest_activity = ? WHERE id = ?', [time(), $u]);
}
/*
 * updateSafeTitle
 * Updates the st cookie, if 1 title is "Google" instead
 * of Ripple - pagename, so Peppy doesn't know that
 * we are browsing Ripple
*/
function updateSafeTitle() {
	$safeTitle = $GLOBALS['db']->fetch('SELECT safe_title FROM users_stats WHERE username = ?', $_SESSION['username']);
	setcookie('st', current($safeTitle));
}
/*
 * timeDifference
 * Returns a string with difference from $t1 and $t2
 *
 * @param (int) ($t1) Current time. Usually time()
 * @param (int) ($t2) Event time.
 * @param (bool) ($ago) Output "ago" after time difference
 * @return (string) A string in "x minutes/hours/days (ago)" format
*/

function NumberEnd($number, $titles) {
    $cases = array (2, 0, 1, 1, 1, 2);
    return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
}

function timeDifference($t1, $t2, $ago = true, $leastText = "Right Now", $mmon = False) {
	// Calculate difference in seconds
	// abs and +1 should fix memes
	$d = abs($t1 - $t2 + 1);
	switch ($d) {
		// Right now
		default:
			return $leastText;
		break;

		// 1 year or more
		case $d >= 31556926 && $mmon == False:
			$n = round($d / 31556926);
			$i = $n.NumberEnd($n, array(' year',' years',' years'));
		break;

		// 1 month or more
		case $d >= 2629743 && ($d < 31556926 || $mmon == True):
			$n = round($d / 2629743);
			$i = $n.NumberEnd($n, array(' Month',' Months',' Months'));
		break;

		// 1 day or more
		case $d >= 86400 && $d < 2629743:
			$n = round($d / 86400);
			$i = $n.NumberEnd($n, array(' ay','Days',' Days'));
		break;

		// 1 hour or more
		case $d >= 3600 && $d < 86400:
			$n = round($d / 3600);
			$i = $n.NumberEnd($n, array(' hour',' hours',' hours'));
		break;

		// 1 minute or more
		case $d >= 60 && $d < 3600:
			$n = round($d / 60);
			$i = $n.NumberEnd($n, array(' minute',' minutes','minutes', ''));
		break;
	}

	// Plural, ago and more
	
	$a = $ago ? 'ago' : '';

	return $i.' '.$a;
}
$checkLoggedInCache = -100;
/*
 * checkLoggedIn
 * Similar to sessionCheck(), but let the user choose what to do if logged in or not
 *
 * @return (bool) true: logged in / false: not logged in
*/
function checkLoggedIn() {
	global $checkLoggedInCache;
	// Start session
	startSessionIfNotStarted();
	if ($checkLoggedInCache !== -100) {
		return $checkLoggedInCache;
	}
	// Check if we are logged in
	if (!isset($_SESSION['userid'])) {
		$checkLoggedInCache = false;
		return false;
	}
	// Check if our password is still valid
	if ($GLOBALS['db']->fetch('SELECT password FROM users WHERE username = ?', $_SESSION['username']) == $_SESSION['password']) {
		$checkLoggedInCache = false;

		return false;
	}
	// Check if we aren't banned
	//if ($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username']) == 0) {
	if (!hasPrivilege(Privileges::UserNormal)) {
		$checkLoggedInCache = false;

		return false;
	}
	// Everything is ok, go on
	$checkLoggedInCache = true;

	return true;
}
/*
 * getUserRank
 * Gets the rank of the $u user
 *
 * @return (int) rank

function getUserRank($u) {
	return current($GLOBALS['db']->fetch('SELECT rank FROM users WHERE username = ?', $u));
}*/
function checkWebsiteMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkGameMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkBanchoMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkRegistrationsEnabled() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'")) == 0) {
		return false;
	} else {
		return true;
	}
}
/****************************************
 **	  DOCUMENTATION FUNCTIONS	   **
 ****************************************/
/*
 * listDocumentationFiles
 * Retrieves all teh files in the folder ../docs/,
 * parses their filenames and then returns them in alphabetical order.
*/
function listDocumentationFiles() {
	// Maintenance alerts
	P::MaintenanceStuff();
	// Global alert
	P::GlobalAlert();
	echo '<div id="narrow-content"><h1><i class="fa fa-question-circle"></i> Documentation</h1>';
	$e = "<ul class='text-left'>\n";
	$data = $GLOBALS['db']->fetchAll("SELECT id, doc_name FROM docs WHERE public = '1'");
	if (count($data) != 0) {
		foreach ($data as $value) {
			$e .= "<li><a href='index.php?p=16&id=".$value['id']."'>".$value['doc_name']."</a></li>\n";
		}
	} else {
		$e .= 'It looks like there are no documentation files! Perhaps try again later?';
	}
	$e .= '</ul>';
	echo $e;
	echo '</div>';
}
/*
 * redirectToNewDocs
 * Redirects the user to the new documentation file's place.
 *
 * @param (string) ($docname) The documentation file name.
*/
function redirectToNewDocs($docname) {
	$new = $GLOBALS['db']->fetch('SELECT id FROM docs WHERE old_name = ?;', [$docname]);
	if ($new == false) {
		redirect('index.php?p=16&id=9001');
	} else {
		redirect('index.php?p=16&id='.$new['id']);
	}
}
/*
 * getDocPageAndParse
 * Gets a page on the documentation.
 *
 * @param (string) ($docid) The document ID.
*/
function getDocPageAndParse($docid) {
	// Maintenance check
	P::MaintenanceStuff();
	// Global alert
	P::GlobalAlert();
	try {
		if ($docid === null) {
			throw new Exception();
		}
		$doc = $GLOBALS['db']->fetch('SELECT doc_contents, public FROM docs WHERE id = ?;', $docid);
		if ($doc['public'] == '0' && !sessionCheckAdmin(1)) {
			return;
		}
		if ($doc == false) {
			throw new Exception();
		}
		require_once 'parsedown.php';
		$p = new Parsedown();
		echo "<div class='text-left'>".$p->text($doc['doc_contents']).'</div>';
	}
	catch(Exception $e) {
		echo '<br>That documentation file could not be found!';
	}
}
// ******** GET USER ID/USERNAME FUNCTIONS *********
$cachedID = false;
/*
 * getUserID
 * Get the user ID of the $u user
 *
 * @param (string) ($u) Username
 * @return (string) user ID of $u
*/
function getUserID($u) {
	global $cachedID;
	if (isset($cachedID[$u])) {
		return $cachedID[$u];
	}
	$ID = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $u);
	if ($ID) {
		$cachedID[$u] = current($ID);
	} else {
		// ID not set, maybe invalid player. Return 0.
		$cachedID[$u] = 0;
	}

	return $cachedID[$u];
}
/*
 * getUserUsername
 * Get the username for $uid user
 *
 * @param (int) ($uid) user ID
 * @return (string) username
*/

function getRank($gameMode, $mods, $acc, $c300, $c100, $c50, $cmiss) {
	 $total = $c300+$c100+$c50+$cmiss;

	 $hdfl = ($mods & (8 | 1024 | 1048576)) > 0;

	 $ss = $hdfl ? "XH" : "X";
	 $s = $hdfl ? "SH" : "S";

	switch($gameMode) {
		case 0:
		case 1:
			 $ratio300 = $c300 / $total;
			 $ratio50 = $c50 / $total;

			if ($ratio300 == 1)
				return $ss;

			if ($ratio300 > 0.9 && $ratio50 <= 0.01 && $cmiss == 0)
				return $s;

			if (($ratio300 > 0.8 && $cmiss == 0) || ($ratio300 > 0.9))
				return "A";

			if (($ratio300 > 0.7 && $cmiss == 0) || ($ratio300 > 0.8))
				return "B";

			if ($ratio300 > 0.6)
				return "C";

			return "D";

		case 2:
			if ($acc == 100)
				return $ss;

			if ($acc > 98)
				return $s;

			if ($acc > 94)
				return "A";

			if ($acc > 90)
				return "B";

			if ($acc > 85)
				return "C";

			return "D";

		case 3:
			if ($acc == 100)
				return $ss;

			if ($acc > 95)
				return $s;

			if ($acc > 90)
				return "A";

			if ($acc > 80)
				return "B";

			if ($acc > 70)
				return "C";

			return "D";
	}
}

function getUserUsername($uid) {
	$username = $GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ? LIMIT 1', $uid);
	if ($username) {
		return current($username);
	} else {
		return 'unknown';
	}
}
/*
 * getPlaymodeText
 * Returns a text representation of a playmode integer.
 *
 * @param (int) ($playModeInt) an integer from 0 to 3 (inclusive) stating the play mode.
 * @param (bool) ($readable) set to false for returning values to be inserted into the db. set to true for having something human readable (osu!standard / Taiko...)
*/
function getPlaymodeText($playModeInt, $readable = false) {
	switch ($playModeInt) {
		case 1:
			return $readable ? 'Taiko' : 'taiko';
		break;
		case 2:
			return $readable ? 'Catch the Beat' : 'ctb';
		break;
		case 3:
			return $readable ? 'osu!mania' : 'mania';
		break;
			// Protection against memes from the users

		default:
			return $readable ? 'osu!standard' : 'std';
		break;
	}
}
/*
 * getScoreMods
 * Gets the mods for the $m mod flag
 *
 * @param (int) ($m) Mod flag
 * @returns (string) Eg: "+ HD, HR"
*/
function getScoreMods($m) {
	require_once dirname(__FILE__).'/ModsEnum.php';
	$r = '';
	if ($m & ModsEnum::NoFail) {
		$r .= 'NF, ';
	}
	if ($m & ModsEnum::Easy) {
		$r .= 'EZ, ';
	}
	if ($m & ModsEnum::NoVideo) {
		$r .= 'NV, ';
	}
	if ($m & ModsEnum::Hidden) {
		$r .= 'HD, ';
	}
	if ($m & ModsEnum::HardRock) {
		$r .= 'HR, ';
	}
	if ($m & ModsEnum::SuddenDeath) {
		$r .= 'SD, ';
	}
	if ($m & ModsEnum::DoubleTime) {
		$r .= 'DT, ';
	}
	if ($m & ModsEnum::Relax) {
		$r .= 'RX, ';
	}
	if ($m & ModsEnum::HalfTime) {
		$r .= 'HT, ';
	}
	if ($m & ModsEnum::Nightcore) {
		$r .= 'NC, ';
		// Remove DT and display only NC
		$r = str_replace('DT, ', '', $r);
	}
	if ($m & ModsEnum::Flashlight) {
		$r .= 'FL, ';
	}
	if ($m & ModsEnum::Autoplay) {
		$r .= 'AP, ';
	}
	if ($m & ModsEnum::SpunOut) {
		$r .= 'SO, ';
	}
	if ($m & ModsEnum::Relax2) {
		$r .= 'AP, ';
	}
	if ($m & ModsEnum::Perfect) {
		$r .= 'PF, ';
        $r = str_replace('SD, ', '', $r);
	}
	if ($m & ModsEnum::Key4) {
		$r .= '4K, ';
	}
	if ($m & ModsEnum::Key5) {
		$r .= '5K, ';
	}
	if ($m & ModsEnum::Key6) {
		$r .= '6K, ';
	}
	if ($m & ModsEnum::Key7) {
		$r .= '7K, ';
	}
	if ($m & ModsEnum::Key8) {
		$r .= '8K, ';
	}
	if ($m & ModsEnum::keyMod) {
		$r .= '';
	}
	if ($m & ModsEnum::FadeIn) {
		$r .= 'FD, ';
	}
	if ($m & ModsEnum::Random) {
		$r .= 'RD, ';
	}
	if ($m & ModsEnum::LastMod) {
		$r .= 'CN, ';
	}
	if ($m & ModsEnum::Key9) {
		$r .= '9K, ';
	}
	if ($m & ModsEnum::Key10) {
		$r .= '10K, ';
	}
	if ($m & ModsEnum::Key1) {
		$r .= '1K, ';
	}
	if ($m & ModsEnum::Key3) {
		$r .= '3K, ';
	}
	if ($m & ModsEnum::Key2) {
		$r .= '2K, ';
	}
	// Add "+" and remove last ", "
	if (strlen($r) > 0) {
		return '+ '.substr($r, 0, -2);
	} else {
		return '';
	}
}
/*
 * calculateAccuracy
 * Calculates the accuracy of a score in a given gamemode.
 *
 * @param int $n300 The number of 300 hits in a song.
 * @param int $n100 The number of 100 hits in a song.
 * @param int $n50 The number of 50 hits in a song.
 * @param int $ngeki The number of geki hits in a song.
 * @param int $nkatu The number of katu hits in a song.
 * @param int $nmiss The number of missed hits in a song.
 * @param int $mode The game mode.
*/
function calculateAccuracy($n300, $n100, $n50, $ngeki, $nkatu, $nmiss, $mode) {
	// For reference, see: http://osu.ppy.sh/wiki/Accuracy
	switch ($mode) {
		case 0:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $n300 * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
		case 1:
			// Please note this is not what is written on the wiki.
			// However, what was written on the wiki didn't make any sense at all.
			$totalPoints = ($n100 * 50 + $n300 * 100);
			$maxHits = ($nmiss + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 100);
		break;
		case 2:
			$fruits = $n300 + $n100 + $n50;
			$totalFruits = $fruits + $nmiss + $nkatu;
			$accuracy = $fruits / $totalFruits;
		break;
		case 3:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $nkatu * 200 + $n300 * 300 + $ngeki * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300 + $ngeki + $nkatu);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
	}

	return $accuracy * 100; // we're doing * 100 because $accuracy is like 0.9823[...]

}
/*
 * getRequiredScoreForLevel
 * Gets the required score for $l level
 *
 * @param (int) ($l) level
 * @return (int) required score
*/
function getRequiredScoreForLevel($l) {
	// Calcolate required score    
	if ($l <= 100) {
		if ($l >= 2) {
		$xd = 5000 / 3 * (4 * bcpow($l, 3, 0) - 3 * bcpow($l, 2, 0) - $l) + 1.25 * bcpow(1.8, $l - 60, 0);
            return $xd; 
        } elseif ($l <= 0 || $l = 1) {
			return 1;
		} // Should be 0, but we get division by 0 below so set to 1

	} elseif ($l >= 101) {
		return 26931190829 + 100000000000 * ($l - 100);
	}
}
/*
 * getLevel
 * Gets the level for $s score
 *
 * @param (int) ($s) ranked score number
*/
function getLevel($s) {
	$level = 1;
	while (true) {
		// if the level is > 8000, it's probably an endless loop. terminate it.
		if ($level > 5000) {
			return $level;
			break;
		}
		// Calculate required score
		$reqScore = getRequiredScoreForLevel($level);
		// Check if this is our level
		if ($s <= $reqScore) {
			// Our level, return it and break
			return $level;
			break;
		} else {
			// Not our level, calculate score for next level
			$level++;
		}
	}
}
/**************************
 ** CHANGELOG FUNCTIONS  **
 **************************/
function getChangelog() {
	sessionCheck();
	echo '<p align="center"><h1><i class="fa fa-code"></i>	Changelog</h1>';
	echo 'Welcome to the changelog page.<br>As soon as a change is made, it will be posted here.<br>Hover a change to know when it was done.<br><br>';
	if (!file_exists(dirname(__FILE__).'/../../ci-system/ci-system/changelog.txt')) {
		echo '<b>Unfortunately, no changelog for this Ripple instance is available. Slap the sysadmin and tell him to configure it.</b>';
	} else {
		$_GET['page'] = (isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1);
		$data = getChangelogPage($_GET['page']);
		if ($data == false || count($data) == 0) {
			echo "<b>You've reached the end of the universe.</b>";
			echo "<br><br><a href='/p/17&page=".($_GET['page'] - 1)."'>&lt; Previous page</a>";

			return;
		}
		echo "<table class='table table-striped table-hover'><thead><th style='width:10%'></th><th style='width:5%'></th><th style='width:75%'></th></thead><tbody>";
		foreach ($data as $commit) {
			echo sprintf("<tr class='%s'><td>%s</td><td><b>%s:</b></td><td><div title='%s'>%s</div></td></tr>", $commit['row'], $commit['labels'], $commit['username'], $commit['time'], $commit['content']);
		}
		echo '</tbody></table><br><br>';
		if ($_GET['page'] != 1) {
			echo "<a href='/p/17&page=".($_GET['page'] - 1)."'>&lt; Previous page</a>";
			echo ' | ';
		}
		echo "<a href='/p/17&page=".($_GET['page'] + 1)."'>Next page &gt;</a>";
	}
}
/*
 * getChangelogPage()
 * Gets a page from the changelog.json with some commits.
 *
 * @param (int) ($p) Page. Optional. Default is 1.
*/
function getChangelogPage($p = 1) {
	global $ChangelogConfig;
	// Retrieve data from changelog.json
	$data = explode("\n", file_get_contents(dirname(__FILE__).'/../../ci-system/ci-system/changelog.txt'));
	$ret = [];
	// Check there are enough commits for the current page.
	$initoffset = ($p - 1) * 50;
	if (count($data) < ($initoffset)) {
		return false;
	}
	// Get only the commits we're interested in.
	$data = array_slice($data, $initoffset, 50);
	// check whether user is admin
	$useradmin = hasPrivilege(Privileges::AdminAccessRAP);
	// Get each commit
	foreach ($data as $commit) {
		// Separate the various components of the commit
		$commit = explode('|', $commit);
		// Silently ignore commits that don't have enough data
		if (count($commit) < 4) {
			continue;
		}
		$valid = true;
		$labels = '';
		// Fix author name
		$commit[2] = trim($commit[2]);
		// Check forbidden commits
		if (isset($ChangelogConfig['forbidden_commits'])) {
			foreach ($ChangelogConfig['forbidden_commits'] as $hash) {
				if (strpos($commit[0], strtolower($hash)) !== false) {
					$valid = false;
					break;
				}
			}
		}
		// Only get first line of commit
		$message = implode('|', array_slice($commit, 3));
		// Check forbidden words
		if (isset($ChangelogConfig['forbidden_keywords']) && !empty($ChangelogConfig['forbidden_keywords'])) {
			foreach ($ChangelogConfig['forbidden_keywords'] as $word) {
				if (strpos(strtolower($message), strtolower($word)) !== false) {
					$valid = false;
					break;
				}
			}
		}
		// Add labels
		if (isset($ChangelogConfig['labels'])) {
			// Hidden label if user is an admin and commit is hidden
			if ($useradmin && !$valid) {
				$row = 'warning';
				$labels .= "<span class='label label-default'>Hidden</span>	";
			} else {
				$row = 'default';
			}
			// Other labels
			foreach ($ChangelogConfig['labels'] as $label) {
				// Add label if needed
				$label = explode(',', $label);
				$keyword = $label[0];
				$text = $label[1];
				$color = $label[2];
				if (strpos(strtolower($message), strtolower($keyword)) !== false) {
					$labels .= "<span class='label label-".$color."'>".$text.'</span>	';
				}
				// Remove label keyword from commit
				$message = str_ireplace($keyword, ' ', $message);
			}
		} else {
			$row = 'default';
		}
		// If we should not output this commit, let's skip it.
		if (!$valid && !$useradmin) {
			continue;
		}
		// Change names if needed
		if (isset($ChangelogConfig['change_name'][$commit[2]])) {
			$commit[2] = $ChangelogConfig['change_name'][2];
		}
		// Build return array
		$ret[] = ['username' => htmlspecialchars($commit[2]), 'content' => htmlspecialchars($message), 'time' => gmdate("Y-m-d\TH:i:s\Z", intval($commit[1])), 'labels' => $labels, 'row' => $row];
	}

	return $ret;
}
/**************************
 **   OTHER   FUNCTIONS  **
 **************************/
function get_contents_http($url) {
	// If curl is not installed, attempt to use file_get_contents
	if (!function_exists('curl_init')) {
		$w = stream_get_wrappers();
		if (in_array('http', $w)) {
			return file_get_contents($url);
		}

		return;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Timeout in seconds
 //   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/27.0.1453.94 Safari/537.36"); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //    curl_setopt($ch, CURLOPT_PROXY, '217.29.53.104:13077'); 
       // curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'pf16cx:3DpNPn');
	// Download the given URL, and return output
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
function post_content_http($url, $content, $timeout=10) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// POST data
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// Download the given URL, and return output
	$output = curl_exec($ch);
	// Close the cURL resource, and free system resources
	curl_close($ch);

	return $output;
}
/*
 * printBadgeSelect()
 * Prints a select with every badge available as options
 *
 * @param (string) ($sn) Name of the select, for php form stuff
 * @param (string) ($sid) Name of the selected item (badge ID)
 * @param (array) ($bd) Badge data array (SELECT * FROM badges)
*/
function printBadgeSelect($sn, $sid, $bd) {
	echo '<select name="'.$sn.'" class="selectpicker" data-width="100%">';
	foreach ($bd as $b) {
		if ($sid == $b['id']) {
			$sel = 'selected';
		} else {
			$sel = '';
		}
		echo '<option value="'.$b['id'].'" '.$sel.'>'.$b['name'].'</option>';
	}
	echo '</select>';
}
/**
 * BwToString()
 * Bitwise enum number to string.
 *
 * @param (int) ($num) Number to convert to string
 * @param (array) ($bwenum) Bitwise enum in the form of array, $EnumName => $int
 * @param (string) ($sep) Separator
 */
function BwToString($num, $bwenum, $sep = '<br>') {
	$ret = [];
	foreach ($bwenum as $key => $value) {
		if ($num & $value) {
			$ret[] = $key;
		}
	}

	return implode($sep, $ret);
}
/*
 * checkUserExists
 * Check if given user exists
 *
 * @param (string) ($i) username/id
 * @param (bool) ($id) if true, search by id. Default: false
*/
function checkUserExists($u, $id = false) {
	if ($id) {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE id = ?', [$u]);
	} else {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', [$u]);
	}
}
/*
 * getFriendship
 * Check friendship between u0 and u1
 *
 * @param (int/string) ($u0) u0 id/username
 * @param (int/string) ($u1) u1 id/username
 * @param (bool) ($id) If true, u0 and u1 are ids, if false they are usernames
 * @return (int) 0: no friendship, 1: u0 friend with u1, 2: mutual
*/
function getFriendship($u0, $u1, $id = false) {
	// Get id if needed
	if (!$id) {
		$u0 = getUserID($u0);
		$u1 = getUserID($u1);
	}
	// Make sure u0 and u1 exist
	if (!checkUserExists($u0, true) || !checkUserExists($u1, true)) {
		return 0;
	}
	// If user1 is friend of user2, check for mutual.
	if ($GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?', [$u0, $u1]) !== false) {
		if ($u1 == 999 || $GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user2 = ? AND user1 = ?', [$u0, $u1]) !== false) {
			return 2;
		}

		return 1;
	}
	// Otherwise, it's just no friendship.
	return 0;
}
/*
 * addFriend
 * Add $newFriend to $dude's friendlist
 *
 * @param (int/string) ($dude) user who sent the request
 * @param (int/string) ($newFriend) dude's new friend
 * @param (bool) ($id) If true, $dude and $newFriend are ids, if false they are usernames
 * @return (bool) true if added, false if not (already in friendlist, invalid user...)
*/
function addFriend($dude, $newFriend, $id = false) {
	try {
		// Get id if needed
		if (!$id) {
			$dude = getUserID($dude);
			$newFriend = getUserID($newFriend);
		}
		// Make sure we aren't adding us to our friends
		if ($dude == $newFriend) {
			throw new Exception();
		}
		// Make sure users exist
		if (!checkUserExists($dude, true) || !checkUserExists($newFriend, true)) {
			throw new Exception();
		}
		// Check whether frienship already exists
		if ($GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?') !== false) {
			throw new Exception();
		}
		// Add newFriend to friends
		$GLOBALS['db']->execute('UPDATE users_stats SET followers_count = followers_count + 1 WHERE id = ?',[$newFriend]);
		$GLOBALS['db']->execute('INSERT INTO users_relationships (user1, user2) VALUES (?, ?)', [$dude, $newFriend]);

		return true;
	}
	catch(Exception $e) {
		return false;
	}
}
/*
 * removeFriend
 * Remove $oldFriend from $dude's friendlist
 *
 * @param (int/string) ($dude) user who sent the request
 * @param (int/string) ($oldFriend) dude's old friend
 * @param (bool) ($id) If true, $dude and $oldFriend are ids, if false they are usernames
 * @return (bool) true if removed, false if not (not in friendlist, invalid user...)
*/
function removeFriend($dude, $oldFriend, $id = false) {
	try {
		// Get id if needed
		if (!$id) {
			$dude = getUserID($dude);
			$oldFriend = getUserID($oldFriend);
		}
		// Make sure users exist
		if (!checkUserExists($dude, true) || !checkUserExists($oldFriend, true)) {
			throw new Exception();
		}
		// Delete user relationship. We don't need to check if the relationship was there, because who gives a shit,
		// if they were not friends and they don't want to be anymore, be it. ¯\_(ツ)_/¯
		$GLOBALS['db']->execute('DELETE FROM users_relationships WHERE user1 = ? AND user2 = ?', [$dude, $oldFriend]);
		$GLOBALS['db']->execute('UPDATE users_stats SET followers_count = followers_count - 1 WHERE id = ?',[$oldFriend]);
		return true;
	}
	catch(Exception $e) {
		return false;
	}
}
// I don't know what this function is for anymore
function clir($must = false, $redirTo = '/p/2&e=3') {
	if (checkLoggedIn() === $must) {
		if ($redirTo == "/p/2&e=3") {
			addError('You\'re not logged in.');
			$redirTo == "/p/2";
		}
		redirect($redirTo);
	}
}
/*
 * checkMustHave
 * Makes sure a request has the "Must Have"s of a page.
 * (Must Haves = $mh_GET, $mh_POST)
*/
function checkMustHave($page) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($page->mh_POST) && count($page->mh_POST) > 0) {
			foreach ($page->mh_POST as $el) {
				if (empty($_POST[$el])) {
					redirect('/p/99&e=do_missing__'.$el);
				}
			}
		}
	} else {
		if (isset($page->mh_GET) && count($page->mh_GET) > 0) {
			foreach ($page->mh_GET as $el) {
				if (empty($_GET[$el])) {
					redirect('/p/99&e=do_missing__'.$el);
				}
			}
		}
	}
}
/*
 * accuracy
 * Convert accuracy to string, having 2 decimal digits.
 *
 * @param (float) accuracy
 * @return (string) accuracy, formatted into a string
*/
function accuracy($acc) {
	return number_format(round($acc, 2), 2);
}
function checkServiceStatus($url) {
	// allow very little timeout for each service
	//ini_set('default_socket_timeout', 5);
	// 0: offline, 1: online, -1: restarting
	try {
		// Bancho status
		//$result = @json_decode(@file_get_contents($url), true);
		$result = getJsonCurl($url);
		if (!isset($result)) {
			throw new Exception();
		}
		if (!array_key_exists('status', $result)) {
			throw new Exception();
		}

		if (array_key_exists('result', $result)) {
			return $result['result'];
		} else {
			return $result['status'];
		}
	}
	catch(Exception $e) {
		return 0;
	}
}
function serverStatusBadge($status) {
	switch ($status) {
		case 1:
		case 200:
			return '<span class="label label-success"><i class="fa fa-check"></i>	Online</span>';
		break;
		case -1:
			return '<span class="label label-warning"><i class="fa fa-exclamation"></i>	Restarting</span>';
		break;
		case 0:
			return '<span class="label label-danger"><i class="fa fa-close"></i>	Offline</span>';
		break;
		default:
			return '<span class="label label-default"><i class="fa fa-question"></i>	Unknown</span>';
		break;
	}
}
function addError($e) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['errors']) || !is_array($_SESSION['errors']))
		$_SESSION['errors'] = array();
	$_SESSION['errors'][] = $e;
}
function addSuccess($s) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['successes']) || !is_array($_SESSION['successes']))
		$_SESSION['successes'] = array();
	$_SESSION['successes'][] = $s;
}
// logIP adds the user to ip_user if they're not in it, and increases occurencies if they are
function logIP($uid) {
	// botnet-track IP
    $ip = getIP();
    if($ip != "127.0.0.1" && $ip != "::1")
	$GLOBALS['db']->execute("INSERT INTO ip_user (userid, ip, occurencies) VALUES (?, ?, '1')
							ON DUPLICATE KEY UPDATE occurencies = occurencies + 1",
							[$uid, $ip]);
}

function getJsonCurl($url, $timeout = 10, $nojson = false) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$result=curl_exec($ch);
		curl_close($ch);
        if($nojson)
            return $result;
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

function postJsonCurl($url, $data, $timeout = 1) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result=curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

/*
 * bloodcatDirectString()
 * Return a osu!direct-like string for a specific song
 * from a bloodcat song array
 *
 * @param (array) ($arr) Bloodcat data array
 * @param (bool) ($np) If true, output chat np beatmap, otherwise output osu direct search beatmap
 * @return (string) osu!direct-like string
*/
function bloodcatDirectString($arr, $np = false) {
	$s = '';
	if ($np) {
		$s = $arr['id'].'.osz|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['id'].'|0|0|0|';
	} else {
		$s = $arr['id'].'|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['beatmaps'][0]['id'].'|0|0|0||';
		foreach ($arr['beatmaps'] as $diff) {
			$s .= $diff['name'].'@'.$diff['mode'].',';
		}
		$s = rtrim($s, ',');
		$s .= '|';
	}
	return $s;
}

function printBubble($userID, $username, $message, $time, $through) {
	echo '
	<img class="circle" src="' . URL::Avatar() . '/' . $userID . '">
	<div class="bubble">
		<b>' . $username . '</b> ' . $message . '<br>
		<span style="font-size: 80%">' . timeDifference($time, time()) .' through <i>' . $through . '</i></span>
	</div>';
}

function rapLog($message, $userID = -1, $through = "RAP") {
	if ($userID == -1)
		$userID = $_SESSION["userid"];
	$GLOBALS["db"]->execute("INSERT INTO rap_logs (id, userid, text, datetime, through) VALUES (NULL, ?, ?, ?, ?);", [$userID, $message, time(), $through]);
}

function readableRank($rank) {
	switch ($rank) {
		case 1: return "normal"; break;
		case 2: return "supporter"; break;
		case 3: return "developer"; break;
		case 4: return "community manager"; break;
		default: return "akerino"; break;
	}
}

function check2FA($userID) {
	// Check if 2fa is enabled
	if (!is2FAEnabled($userID))
		return false;

	// New ip?
	$ip = getIp();
	if ($GLOBALS["db"]->fetch("SELECT * FROM ip_user WHERE userid = ? AND ip = ?", [$userID, $ip]))
		return false;

	// Check if we already have a pending 2FA token from that IP
	if ($GLOBALS["db"]->fetch("SELECT * FROM 2fa WHERE userid = ? AND ip = ? AND expire > ?", [$userID, $ip, time()]))
		return false;

	// No 2FA tokens from that IP, add a new one
	$token = strtoupper(randomString(8));
	$GLOBALS["db"]->execute("INSERT INTO 2fa (id, userid, token, ip, expire, sent) VALUES (NULL, ?, ?, ?, ?, 0)", [$userID, $token, $ip, time()+3600]);

	// Send 2FA telegram message
	if (!isset($_GLOBALS["redis"])) 
        $GLOBALS["redis"] = new Predis\Client();
    $vkid = current($GLOBALS["db"]->fetch("SELECT vk FROM users WHERE id = ?",[$userID]));
    $GLOBALS["redis"]->set('2fa_confirm:'.$vkid, json_encode(["token"=>$token,"ip"=>$ip]));
	#getJsonCurl("http://127.0.0.1:8888/update");
	return true;
}

function redirect2FA() {
	// Check 2FA only if we are logged in
	if (!checkLoggedIn())
		return;

	// Generate 2FA token if needed
	check2FA($_SESSION["userid"]);
	// Don't redirect to 2FA page if we are on submit.php with resend2FA, 2fa or logout action
	if (($_SERVER['PHP_SELF'] == "/submit.php") &&
		(@$_GET["action"] == "resend2FACode" || @$_POST["action"] == "2fa" || @$_GET["action"] == "logout"))
		return;

if(isset($_GET["p"]) && $_GET["p"] == 29)
	return;

	// Redirect to 2FA page
	if ($GLOBALS['db']->fetch("SELECT * FROM 2fa WHERE userid = ? AND ip = ?", [$_SESSION["userid"], getIp()])) {
		redirect("/p/29");
	}
}




/* 
   RIP Documentation and comments from now on.
   Those functions are the last ones that we've added to old-frontend
   Because new frontend is coming soonTM, so I don't want to waste time writing comments and docs.
   You'll also find 20% more memes in these functions.

   ...and fuck php
   -- Nyo 
   
   I'd just like to interject for a moment. You do not just 'fuck' PHP, you 'fuck' PHP with a CACTUS!
   -- Howl
*/




function cleanExpiredConfirmationToken() {
	$GLOBALS["db"]->execute("DELETE FROM 2fa_confirmation WHERE expire < ?", [time()]);
}

function bound($x, $min, $max) 
{ 
     return min(max($x, $min), $max); 
} 

function getConfirmationToken($userID) {
	// Get current token
	$token = $GLOBALS["db"]->fetch("SELECT token FROM 2fa_confirmation WHERE userid = ? LIMIT 1", [$userID]);
	// Generate a new token if not found
	$vkid = current($GLOBALS["db"]->fetch("SELECT vk FROM users WHERE id = ?",[$userID]));
	if (!$token && $vkid > 1) {
		$token = randomString(32);
		$GLOBALS["db"]->execute("INSERT INTO 2fa_confirmation (id, userid, token, expire) VALUES (NULL, ?, ?, ?)", [$userID, $token, time()+600]);
		if (!isset($_GLOBALS["redis"])) 
        	$GLOBALS["redis"] = new Predis\Client();
    	
    $GLOBALS["redis"]->set('2fa:'.$vkid, $token);
		return getConfirmationToken($userID);
	} else {
		return $token["token"];
	}
}

function is2FAEnabled($userID, $force = false) {
	
	if (session_status() != PHP_SESSION_NONE && !$force && isset($_SESSION["2fa"]))
		return $_SESSION["2fa"];
	$result = $GLOBALS["db"]->fetch("SELECT * FROM 2fa_telegram WHERE userid = ? LIMIT 1", [$userID]);
	return $result ? true : false;
}

function getUserPrivileges($userID) {
	global $cachedPrivileges;
	if (isset($cachedPrivileges[$userID])) {
		return $cachedPrivileges[$userID];
	}

	$result = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$userID]);
	if ($result) {
		$cachedPrivileges[$userID] = current($result);
	} else {
		$cachedPrivileges[$userID] = 0;
	}
	return getUserPrivileges($userID);
}

function hasPrivilege($privilege, $userID = -1) {
	if ($userID == -1)
		if (!array_key_exists("userid", $_SESSION))
			return false;
		else
			$userID = $_SESSION["userid"];
	$result = getUserPrivileges($userID) & $privilege;
	return $result > 0 ? true : false;
}

function isRestricted($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && hasPrivilege(Privileges::UserNormal, $userID));
}

function isBanned($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && !hasPrivilege(Privileges::UserNormal, $userID));
}

function multiaccCheckIP($ip) {
	$multiUserID = $GLOBALS['db']->fetch("SELECT userid, users.username FROM ip_user LEFT JOIN users ON users.id = ip_user.userid WHERE ip = ?", [$ip]);
	if (!$multiUserID)
		return false;
	return $multiUserID;
	/*$multiUsername = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$multiUserID]);

	if ($multiUsername) {
		Schiavo::CM("User **" . current($multiUsername) . "** (https://ripple.moe/?u=$multiUserID) tried to create a multiaccount (**" . $_POST['u'] . "**) from IP **" . $ip . "**");
	}
	$GLOBALS["db"]->execute("UPDATE users SET notes=CONCAT(COALESCE(notes, ''),'\n-- Multiacc attempt (".$_POST["u"].") from IP ".$ip."') WHERE id = ?", [$multiUserID]); */
}

function getUserFromMultiaccToken($token) {
	$multiToken = $GLOBALS["db"]->fetch("SELECT userid, users.username FROM identity_tokens LEFT JOIN users ON users.id = identity_tokens.userid WHERE token = ? LIMIT 1", [$token]);
	if (!$multiToken)
		return false;
	return $multiToken;
}

function multiaccCheckToken() {
	if (!isset($_COOKIE["y"]))
		return false;

	// y cookie is set, we expect to found a token in db
	$multiToken = getUserFromMultiaccToken($_COOKIE["y"]);
	if ($multiToken === FALSE) {
		// Token not found in db, user has edited cookie manually.
		// Akerino, keep showing multiacc warning
		$multiToken = false;
	}
	return $multiToken;
}

function getIdentityToken($userID, $generate = True) {
	$identityToken = $GLOBALS["db"]->fetch("SELECT token FROM identity_tokens WHERE userid = ? LIMIT 1", [$userID]);
	if (!$identityToken && $generate) {
		// If not, generate a new one
		do {
			$identityToken = hash("sha256", randomString(32));
			$collision = $GLOBALS["db"]->fetch("SELECT id FROM identity_tokens WHERE token = ? LIMIT 1", $identityToken);
		} while ($collision);

		// And save it in db
		$GLOBALS["db"]->execute("INSERT INTO identity_tokens (id, userid, token) VALUES (NULL, ?, ?)", [$userID, $identityToken]);
	} else if ($identityToken) {
		$identityToken = current($identityToken);
	} else {
		$identityToken = false;
	}
	return $identityToken;
}

function appendNotes($userID, $notes, $addNl=true) {
    if ($addNl) {
        $notes = "\n" . $notes;
    }
	$GLOBALS["db"]->execute("UPDATE users SET notes=CONCAT(COALESCE(notes, ''),?) WHERE id = ? LIMIT 1", [$notes, $userID]);
}

function setYCookie($userID) {
	$identityToken = getIdentityToken($userID);
	setcookie("y", $identityToken, time()+60*60*24*30*6, "/");	// y of yee
}

function UNIXTimestampToOsuDate($unix) {
	return date("ymdHis", $unix);
}

function isOnline($uid) {
	global $URL;
	try {
		$data = getJsonCurl($URL["bancho"]."/api/v1/isOnline?id=".urlencode($uid));
		return $data["result"];
	} catch (Exception $e) {
		return false;
	}
}


function escapeIdent($value)
{
	return "`".str_replace("`","``",$value)."`";
}

function getDonorPrice($months) {
	return number_format(pow($months * 1827 * 0.1, 0.97), 2, ".", "");
}

function unsetCookie($name) {
	unset($_COOKIE[$name]);
	setcookie($name, "", time()-3600);
}

function safeUsername($name) {
	return str_replace(" ", "_", strtolower($name));
}

function updateBanBancho($userID) {
	redisPublish("peppy:ban", $userID);
}

function updateSilenceBancho($userID) {
	redisPublish("peppy:silence", $userID);
}

function stripSuccessError($url) {
	$parts = parse_url($url);
	parse_str($parts['query'], $query);
	unset($query["e"]);
	unset($query["s"]);
	return $parts["path"] . "?" .  http_build_query($query);
}