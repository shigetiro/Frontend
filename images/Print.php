<?php
class P {
	/*
	 * AdminDashboard
	 * Prints the admin panel dashborad page
	*/

    
    
	public static function AdminDashboard() {
		// Get admin dashboard data
		$totalScores = number_format(current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM scores WHERE completed = 3 LIMIT 1')));
		$betaKeysLeft = number_format(current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM beta_keys LIMIT 1')));
		$totalPPQuery = $GLOBALS['db']->fetch("SELECT SUM(pp) FROM scores WHERE completed = 3 LIMIT 1");
		$totalPP = 0;
		foreach ($totalPPQuery as $pp) {
			$totalPP += $pp;
		}
		$totalPP = number_format($totalPP);
       
	$recentPlays = $GLOBALS['db']->fetchAll('
		SELECT
			beatmaps.song_name,beatmaps.beatmap_id, scores.beatmap_md5, users.username,
			scores.userid, scores.time, scores.score, scores.pp,
			scores.play_mode, scores.mods
		FROM scores
		LEFT JOIN beatmaps ON beatmaps.beatmap_md5 = scores.beatmap_md5
		LEFT JOIN users ON users.id = scores.userid
		WHERE scores.completed > 2 
		ORDER BY scores.id DESC 
		LIMIT 10');
		 
        $topPlays = [];
		$topPlays = $GLOBALS['db']->fetchAll('SELECT beatmaps.song_name, scores.beatmap_md5, users.username, scores.userid, scores.time,beatmaps.beatmap_id, scores.score, scores.pp, scores.play_mode, scores.mods FROM (SELECT * FROM scores WHERE scores.play_mode = 0 AND scores.completed = 3 ORDER BY scores.pp DESC LIMIT 100) as scores LEFT JOIN beatmaps ON beatmaps.beatmap_md5 = scores.beatmap_md5 INNER JOIN (SELECT * FROM users WHERE users.privileges & 2 > 0 and users.privileges & 1 > 0) as users ON users.id = scores.userid ORDER BY scores.pp DESC LIMIT 10');
       
		$onlineUsers =  getJsonCurl(URL::Bancho()."/api/v1/onlineUsers");
        if ($onlineUsers == false) {
			$onlineUsers = 0;
		} else {
			$onlineUsers = $onlineUsers["result"];
		}
         //die("XD FUCK KILL ME XD XD");
		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"> <div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Stats panels
		echo '<div class="row"><br>';
		printAdminPanel('primary', 'fa fa-gamepad fa-5x', $totalScores, 'Total scores');
		printAdminPanel('green', 'fa fa-user fa-5x', $onlineUsers, 'Online users');
		printAdminPanel('red', 'fa fa-gift fa-5x', $betaKeysLeft, 'Beta keys left');
		printAdminPanel('yellow', 'fa fa-dot-circle-o fa-5x', $totalPP, 'Total PP');
		echo '</div>';
		// Recent plays table
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th>Score</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
      //  echo '<tr class="danger"><td colspan=6>Disabled</td></tr>';
      
		foreach ($recentPlays as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="success">';
			echo '<td><p class="text-left"><b><a href="/u/'.$play["username"].'">'.$play['username'].'</a></b></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.gatari.pw/b/'.$play["beatmap_id"].'">'.$bn.'</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">'.$pm.'</p></td>';
			echo '<td><p class="text-left">'.timeDifference(time(), $play['time']).'</p></td>';
			echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>'.number_format($play['pp']).'pp</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		// Top plays table
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		 foreach ($topPlays as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="warning">';
			echo '<td><p class="text-left"><a href="/u/'.$play["username"].'"><b>'.$play['username'].'</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.gatari.pw/b/'.$play["beatmap_id"].'">'.$bn.'</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">'.$pm.'</p></td>';
			echo '<td><p class="text-left">'.timeDifference(time(), $play['time']).'</p></td>';
			echo '<td><p class="text-right"><b>'.number_format($play['pp']).'</b></p></td>';
			echo '</tr>';
		} 
		echo '</tbody>';
		echo '</div></div></div>';
	}

	/*
	 * AdminUsers
	 * Prints the admin panel users page
	*/
	public static function AdminUsers() {
		// Get admin dashboard data
		$totalUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users'));
		$supporters = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges = 1048576'));
		$bannedUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & 1 = 0 AND privileges!=1048576'));
		$modUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & '.Privileges::AdminAccessRAP.'> 0'));
		// Multiple pages
		$pageInterval = 100;
		$from = (isset($_GET["from"])) ? $_GET["from"] : 999;
		$to = $from+$pageInterval;
		$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE id >= ? AND id < ?', [$from, $to]);
		$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Stats panels
		echo '<div class="row"><br><hr>';
		printAdminPanel('primary', 'fa fa-user fa-5x', $totalUsers, 'Total users');
		printAdminPanel('red', 'fa fa-thumbs-down fa-5x', $bannedUsers, 'Banned users');
		printAdminPanel('info', 'fa fa-clock-o fa-5x', $supporters, ' Waiting');
		printAdminPanel('green', 'fa fa-star fa-5x', $modUsers, 'Admins');
		echo '</div>';
		// Quick edit/silence/kick user button
		echo '<br><p align="center"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickEditUserModal">Quick edit user (username)</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-info" data-toggle="modal" data-target="#quickEditEmailModal">Quick edit user (email)</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal">Silence user</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#kickUserModal">Kick user from Bancho</button>';
		echo '</p>';
		// Users plays table
		echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-user"></i>	ID</th><th class="text-center">Username</th><th class="text-center">Privileges Group</th><th class="text-center">Allowed</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';
		foreach ($users as $user) {

			// Get group color/text
			$groupColor = "default";
			$groupText = "None";
			foreach ($groups as $group) {
				if ($user["privileges"] == $group["privileges"] || $user["privileges"] == ($group["privileges"] | Privileges::UserDonor)) {
					$groupColor = $group["color"];
					$groupText = $group["name"];
				}
			}

			// Get allowed color/text
			$allowedColor = "success";
			$allowedText = "Ok";
			if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Not visible and not active, banned
				$allowedColor = "danger";
				$allowedText = "Banned";
			} else if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) > 0) {
				// Not visible but active, restricted 
				$allowedColor = "warning";
				$allowedText = "Restricted";
			} else if (($user["privileges"] & Privileges::UserPublic) > 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Visible but not active, disabled (not supported yet)
				$allowedColor = "default";
				$allowedText = "Locked";
			}

			// Print row
			echo '<tr>';
			echo '<td><p class="text-center">'.$user['id'].'</p></td>';
			echo '<td><p class="text-center"><b><a href="/u/'.$user['id'].'">'.$user['username'].'</a></b></p></td>';
			echo '<td><p class="text-center"><span class="label label-'.$groupColor.'">'.$groupText.'</span></p></td>';
			echo '<td><p class="text-center"><span class="label label-'.$allowedColor.'">'.$allowedText.'</span></p></td>';
			echo '<td><p class="text-center">
			<div class="btn-group">
			<a title="Edit user" class="btn btn-xs btn-primary" href="/index.php?p=103&id='.$user['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>';
			if (hasPrivilege(Privileges::AdminBanUsers)) {
				if (isBanned($user["id"])) {
					echo '<a title="Unban user" class="btn btn-xs btn-success" onclick="sure(\'/submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
				} else {
					echo '<a title="Ban user" class="btn btn-xs btn-warning" onclick="sure(\'/submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
				}
				if (isRestricted($user["id"])) {
					echo '<a title="Remove restrictions" class="btn btn-xs btn-success" onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-ok-circle"></span></a>';
				} else {
					echo '<a title="Restrict user" class="btn btn-xs btn-warning" onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-remove-circle"></span></a>';
				}
			}
			echo '	<a title="Change user identity" class="btn btn-xs btn-danger" href="/index.php?p=104&id='.$user['id'].'"><span class="glyphicon glyphicon-refresh"></span></a>
			</div>
			</p></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p align="center"><a href="/index.php?p=102&from='.($from-($pageInterval+1)).'">< Previous page</a> | <a href="/index.php?p=102&from='.($to).'">Next page ></a></p>';
		echo '</div></div></div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control-black" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Search user by email modal
		echo '<div class="modal fade" id="quickEditEmailModal" tabindex="-1" role="dialog" aria-labelledby="quickEditEmailModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditEmailModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-email-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUserEmail" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control-black" placeholder="Email" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-email-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Silence user modal
		echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="silenceUserModal">Silence user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="silence-user-form" action="submit.php" method="POST">
		<input name="action" value="silenceUser" hidden>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control-black" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
		<input type="number" name="c" class="form-control-black" placeholder="How long" aria-describedby="basic-addon1" required>
		<select name="un" class="selectpicker" data-width="30%">
			<option value="1">Seconds</option>
			<option value="60">Minutes</option>
			<option value="3600">Hours</option>
			<option value="86400">Days</option>
		</select>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control-black" placeholder="Reason" aria-describedby="basic-addon1">
		</div>

		<p style="line-height: 15px"></p>

		During the silence period, user\'s client will be locked. <b>Max silence time is 7 days.</b> Set length to 0 to remove the silence.

		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
		</div>
		</div>
		</div>
		</div>';
		// Kick user modal
		echo '<div class="modal fade" id="kickUserModal" tabindex="-1" role="dialog" aria-labelledby="kickUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="kickUserModalLabel">Kick user from Bancho</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="kick-user-form" action="submit.php" method="POST">
		<input name="action" value="kickUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control-black" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</p>
		<p>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control-black" placeholder="Reason" aria-describedby="basic-addon1" value="You have been kicked from the server. Please login again." required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="kick-user-form" class="btn btn-primary">Kick user</button>
		</div>
		</div>
		</div>
		</div>';
	}

	/*
	 * AdminEditUser
	 * Prints the admin panel edit user page
	*/
	public static function AdminEditUser() {
		try {
			// Check if id is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid user ID!');
			}
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			$userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ? LIMIT 1', $_GET['id']);
			$ips = $GLOBALS['db']->fetchAll('SELECT ip FROM ip_user WHERE userid = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData || !$userStatsData) {
				throw new Exception("That user doesn't exist");
			}
			// Hax check
			if ($userData["aqn"] == 1) {
				$haxText = "Yes";
				$haxCol = "danger";
			} else {
				$haxText = "No";
				$haxCol = "success";
			}
			// Cb check
			if ($userStatsData["can_custom_badge"] == 1) {
				$cbText = "Yes";
				$cbCol = "success";
			} else {
				$cbText = "No";
				$cbCol = "danger";
			}
			// Set readonly stuff
			$readonly[0] = ''; // User data stuff
			$readonly[1] = ''; // Username color/style stuff
			$selectDisabled = '';
			// Check if we are editing our account
			if ($userData['username'] == $_SESSION['username']) {
				// Allow to edit only user stats
				$readonly[0] = 'readonly';
				$selectDisabled = 'disabled';
			} elseif ((strtolower($_SESSION['username']) != "xxdstem") && ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				// We are trying to edit a user with same/higher rank than us :akerino:
				redirect("/index.php?p=102&e=You don't have enough permissions to edit this user chree");
				die();
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			// Selected values stuff 1
			//$selected[0] = [1 => '', 2 => '', 3 => '', 4 => ''];
			// Selected values stuff 2
			//$selected[1] = [0 => '', 1 => '', 2 => ''];

			// Get selected stuff
			//$selected[0][current($GLOBALS['db']->fetch('SELECT rank FROM users WHERE id = ?', $_GET['id']))] = 'selected';
			//$selected[1][($userData["privileges"] & Privileges::UserBasic) > 0 ? 1 : 0] = 'selected';

			echo '<p align="center"><font size=5><i class="fa fa-user"></i>	Edit user</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveEditUser" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control-black" value="'.$userData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control-black" value="'.$userData['username'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Email</td>
			<td><p class="text-center"><input type="text" name="e" class="form-control-black" value="'.$userData['email'].'" '.$readonly[0].'></td>
			</tr>';
			echo '<tr>
			<td>Country</td>
			<td>
			<select name="country" class="selectpicker" data-width="100%">
			';
			require_once dirname(__FILE__) . "/countryCodesReadable.php";
			asort($c);
			// Push XX to top
			$c = array('XX' => $c['XX']) + $c;
			reset($c);
			foreach ($c as $k => $v) {
				$sd = "";
				if ($userStatsData['country'] == $k)
					$sd = "selected";
				$ks = $k;
				if (!file_exists(dirname(__FILE__) . "/../images/flags/$ks.png"))
					$ks = "xx";
				echo "<option value='$k' $sd data-content=\""
					. "<img src='images/flags/$ks.png' style='width: 20px;' alt='$k'>"
					. " $v\"></option>\n";
			}
			echo '
			</select>
			</td>
			</tr>';
			echo '<tr>
			<td>Allowed</td>
			<td>';

			if (isBanned($userData["id"])) {
				echo "Banned";
			} else if (isRestricted($userData["id"])) {
				echo "Restricted";
			} else if (!hasPrivilege(Privileges::UserNormal, $userData["id"])) {
				echo "Locked";
			} else {
				echo "Ok";
			}

			echo '</td>
			</tr>';
			if (isBanned($userData["id"]) || isRestricted($userData["id"])) {
				$canAppeal = time()-$userData["ban_datetime"] >= 86400*30;
				echo '<tr class="'; echo $canAppeal ? 'success' : 'warning'; echo '">
				<td>Ban/Restricted Date<br><i>(dd/mm/yyyy)</i></td>
				<td>' . date('d/m/Y', $userData["ban_datetime"]) . "<br>";
				echo $canAppeal ? '<i> (can appeal)</i>' : '<i> (can\'t appeal yet)<i>';
				echo '</td>
				</tr>';
			}
			if (hasPrivilege(Privileges::UserDonor,$userData["id"])) {
				$donorExpire = timeDifference($userData["donor_expire"], time(), false);
				echo '<tr>
				<td>Donor expires in</td>
				<td>'.$donorExpire.'</td>
				</tr>';
			}
			echo '<tr>
			<td>Username color<br><i>(HTML or HEX color)</i></td>
			<td><p class="text-center"><input type="text" name="c" class="form-control-black" value="'.$userStatsData['user_color'].'" '.$readonly[1].'></td>
			</tr>';
			echo '<tr>
			<td>Username CSS<br><i>(like fancy gifs as background)</i></td>
			<td><p class="text-center"><input type="text" name="bg" class="form-control-black" value="'.$userStatsData['user_style'].'" '.$readonly[1].'></td>
			</tr>';
			echo '<tr>
			<td> A.K.A</td>
			<td><p class="text-center"><input type="text" name="aka" class="form-control-black" value="'.htmlspecialchars($userStatsData['username_aka']).'"></td>
			</tr>';
			echo '<tr>
			<td>Userpage<br><a onclick="censorUserpage();">(reset userpage)</a></td>
			<td><p class="text-center"><textarea name="up" class="form-control-black" style="overflow:auto;resize:vertical;height:200px">'.$userStatsData['userpage_content'].'</textarea></td>
			</tr>';
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				echo '<tr>
				<td>Silence end time<br><a onclick="removeSilence();">(remove silence)</a></td>
				<td><p class="text-center"><input type="text" name="se" class="form-control-black" value="'.$userData['silence_end'].'"></td>
				</tr>';
				echo '<tr>
				<td>Silence reason</td>
				<td><p class="text-center"><input type="text" name="sr" class="form-control-black" value="'.$userData['silence_reason'].'"></td>
				</tr>';
			}
			if (hasPrivilege(Privileges::AdminManagePrivileges)) {
				$gd = $userData["id"] == $_SESSION["userid"] ? "disabled" : "";
				echo '<tr>
				<td>Privileges<br><i>(Don\'t touch<br>UserPublic or UserNormal.<br>Use ban/restricted buttons<br>instead to avoid messing up)</i></td>
				<td>';
				$refl = new ReflectionClass("Privileges");
				$privilegesList = $refl->getConstants();
				foreach ($privilegesList as $i => $v) {
					if ($v <= 0)
						continue;
					$c = (($userData["privileges"] & $v) > 0) ? "checked" : "";
					$d = ($v <= 2 && $gd != "disabled") ? "disabled" : "";
					echo '<label><input name="privilege" value="'.$v.'" type="checkbox" onclick="updatePrivileges();" '.$c.' '.$gd.' '.$d.'>	'.$i.' ('.$v.')</label><br>';
				}
				echo '</tr>';
				$ro = $userData["id"] == $_SESSION["userid"] ? "readonly" : "";
				echo '<tr>
				<td>Privilege number</td>
				<td><input class="form-control-black" id="privileges-value" name="priv" value="'.$userData["privileges"].'" '.$ro.'></td>
				</tr>';
				echo '<tr>
				<td>Privilege group<br><i>(This is basically a preset<br>and will replace every<br>existing privilege)</i></td>
				<td>
					<select id="privileges-group" name="privgroup" class="selectpicker" data-width="100%" onchange="groupUpdated();" '.$gd.'>';
					$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
					echo "<option value='-1'>None</option>";
					foreach ($groups as $group) {
						$s = (($userData["privileges"] == $group["privileges"]) || ($userData["privileges"] == ($group["privileges"] | Privileges::UserDonor)))? "selected": "";
						echo "<option value='$group[privileges]' $s>$group[name]</option>";
					}
					echo '</select>
				</td>
				</tr>';
			}
			echo '<tr>
			<td>Avatar<br><a onclick="sure(\'/submit.php?action=resetAvatar&id='.$_GET['id'].'\')">(reset avatar)</a></td>
			<td>
				<p align="center">
					<img src="'.URL::Avatar().'/'.$_GET['id'].'" height="50" width="50" style="width: 50px;" ></img>
				</p>
			</td>
			</tr>';
			if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
				echo '<tr>
				<td>Custom badge</td>
				<td>
					<p align="center">
						<i class="fa '.htmlspecialchars($userStatsData["custom_badge_icon"]).' fa-2x"></i>
						<br>
						<b>'.htmlspecialchars($userStatsData["custom_badge_name"]).'</b>
					</p>
				</td>
				</tr>';
			}
			echo '<tr>
			<td>Can edit custom badge</td>
			<td><span class="label label-'.$cbCol.'">'.$cbText.'</span></td>
			</tr>';
			echo '<tr>
			<td>Detected AQN folder
				<br>
				<i>(If \'yes\', AQN (hax) folder has been<br>detected on this user, so he is<br>probably cheating).</i></td>
			</td>
			<td><span class="label label-'.$haxCol.'">'.$haxText.'</span></td>
			</tr>';
			echo '<tr>
			<td>Notes for CMs
			<br>
			<i>(visible only from RAP)</i></td>
			<td><textarea name="ncm" class="form-control-black" style="overflow:auto;resize:vertical;height:100px">' . $userData["notes"] . '</textarea></td>
			</tr>';
			echo '<tr><td>IPs</td><td><ul>';
			foreach ($ips as $ip) {
				echo "<li>$ip[ip] <a class='getcountry' data-ip='$ip[ip]' title='Click to retrieve IP country'>(?)</a></li>";
			}
			echo '</ul></td></tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center" style="width:50%; margin-left:25%;">
					<button type="submit" form="system-settings-form" class="btn btn-primary">Save changes</button><br><br>

					<br><br>
					<b>If you have made any changes to this user through this page, make sure to save them before using one of the following functions, otherwise unsubmitted changes will be lost.</b>
					<ul class="list-group">
						<li class="list-group-item list-group-item-info">Actions</li>
						<li class="list-group-item">';
							if (hasPrivilege(Privileges::AdminManageBadges)) {
								echo '<a href="/index.php?p=110&id='.$_GET['id'].'" class="btn btn-success">Edit badges</a>';
							}
							echo '	<a href="/index.php?p=104&id='.$_GET['id'].'" class="btn btn-info">Change identity</a>';
							if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
								echo '	<a onclick="sure(\'/submit.php?action=removeDonor&id='.$_GET['id'].'\');" class="btn btn-danger">Remove donor</a>';
							}
							echo '	<a href="/index.php?p=121&id='.$_GET['id'].'" class="btn btn-warning">Give donor</a>';
							echo '	<a href="/u/'.$_GET['id'].'" class="btn btn-primary">View profile</a>
						</li>
					</ul>';

					echo '<ul class="list-group">
					<li class="list-group-item list-group-item-danger">Dangerous Zone</li>
					<li class="list-group-item">';
					if (hasPrivilege(Privileges::AdminWipeUsers)) {
						echo '	<a href="/index.php?p=123&id='.$_GET["id"].'" class="btn btn-danger">Wipe account</a>';
						echo '	<a href="/index.php?p=122&id='.$_GET["id"].'" class="btn btn-danger">Rollback account</a>';
					}
					if (hasPrivilege(Privileges::AdminBanUsers)) {
						echo '	<a onclick="sure(\'/submit.php?action=banUnbanUser&id='.$_GET['id'].'\')" class="btn btn-danger">(Un)ban user</a>';
						echo '	<a onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id='.$_GET['id'].'\')" class="btn btn-danger">(Un)restrict user</a>';
						echo '	<a onclick="sure(\'/submit.php?action=lockUnlockUser&id='.$_GET['id'].'\', \'Restrictions and bans will be removed from this account if you lock it. Make sure to lock only accounts that are not banned or restricted.\')" class="btn btn-danger">(Un)lock user</a>';
						echo '	<a onclick="sure(\'/submit.php?action=clearHWID&id='.$_GET['id'].'\');" class="btn btn-danger">Clear HWID matches</a>';
					}
					
					
					echo '<br><br><a onclick="sure(\'/submit.php?action=toggleCustomBadge&id='.$_GET['id'].'\');" class="btn btn-danger">'.(($userStatsData["can_custom_badge"] == 1) ? "Revoke" : "Grant").' custom badge</a>';
					echo '<br>
						</li>
					</ul>';

				echo '</div>
				</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * AdminChangeIdentity
	 * Prints the admin panel change identity page
	*/
	public static function AdminChangeIdentity() {
		try {
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
			$userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData || !$userStatsData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we are trying to edit our account or a higher rank account
			if ((strtolower($_SESSION['username']) != "xxdstem") && $userData['username'] != $_SESSION['username'] && (($userData['privileges'] & Privileges::AdminManageUsers) > 0)) {
				throw new Exception("You don't have enough permission to edit this user.");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change identity</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="changeIdentity" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control-black" value="'.$userData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Old Username</td>
			<td><p class="text-center"><input type="text" name="oldu" class="form-control-black" value="'.$userData['username'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>New Username</td>
			<td><p class="text-center"><input type="text" name="newu" class="form-control-black"></td>
			</tr>';
            echo '<tr>
			<td>New Password (leave blank if you don`t want to change it)</td>
			<td><p class="text-center"><input type="text" name="newpd" class="form-control-black"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change identity</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * AdminSystemSettings
	 * Prints the admin panel system settings page
	*/
	public static function AdminSystemSettings() {
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$wm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'"));
		$gm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'"));
		$r = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'"));
		$ga = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		$ha = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($wm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($gm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($r == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<p align="center"><font size=5><i class="fa fa-cog"></i>	System settings</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveSystemSettings" hidden>';
		echo '<tr>
		<td>Maintenance mode (website)</td>
		<td>
		<select name="wm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Maintenance mode (in-game)</td>
		<td>
		<select name="gm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Registration</td>
		<td>
		<select name="r" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Global alert<br>(visible on every page of the website)</td>
		<td><textarea type="text" name="ga" class="form-control-black" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ga.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Homepage alert<br>(visible only on the home page)</td>
		<td><textarea type="text" name="ha" class="form-control-black" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ha.'</textarea></td>
		</tr>';
		echo '<tr class="success"><td colspan=2><p align="center">Click <a href="/index.php?p=111">here</a> for bancho settings</p></td></tr>';
		echo '</tbody></form>';
		echo '</table>';
		echo '<div class="text-center"><div class="btn-group" role="group">
		<button type="submit" form="system-settings-form" class="btn btn-primary">Save settings</button>
		</div><br></div></div></div>';
		echo '</div>';
	}

	/*
	 * AdminDocumentation
	 * Prints the admin panel documentation files page
	*/
	public static function AdminDocumentation() {
		// Get data
		$docsData = $GLOBALS['db']->fetchAll('SELECT id, doc_name, public, is_rule FROM docs');
		// Print docs stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Documentation</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>
		<tr><th class="text-center"><i class="fa fa-book"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Public</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($docsData as $doc) {
			// Public label
			if ($doc['public'] == 1) {
				$publicColor = 'success';
				$publicText = 'Yes';
			} else {
				$publicColor = 'danger';
				$publicText = 'No';
			}
			$ruletxt = "";
			if ($doc['is_rule'])
				$ruletxt = " <b>(rules)</b>";
			// Print row for this doc page
			echo '<tr>
			<td><p class="text-center">'.$doc['id'].'</p></td>
			<td><p class="text-center">'.$doc['doc_name'].$ruletxt.'</p></td>
			<td><p class="text-center"><span class="label label-'.$publicColor.'">'.$publicText.'</span></p></td>
			<td><p class="text-center">
			<a title="Edit page" class="btn btn-xs btn-primary" href="/index.php?p=107&id='.$doc['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="View page" class="btn btn-xs btn-success" href="/index.php?p=16&id='.$doc['id'].'"><span class="glyphicon glyphicon-eye-open"></span></a>
			<a title="Make rules page" class="btn btn-xs btn-warning" href="submit.php?action=setRulesPage&id='.$doc['id'].'"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></a>
			<a title="Delete page" class="btn btn-xs btn-danger" onclick="sure(\'/submit.php?action=removeDoc&id='.$doc['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div class="text-center"><div class="btn-group" role="group">
		<a href="/index.php?p=107&id=0" type="button" class="btn btn-primary">Add documentation page</a><br>
		</div></div>';
		echo '</div></div></div>';
	}

	/*
	 * AdminBadges
	 * Prints the admin panel badges page
	*/
	public static function AdminBadges() {
		// Get data
		$badgesData = $GLOBALS['db']->fetchAll('SELECT * FROM badges');
		// Print docs stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Badges</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>
		<tr><th class="text-center"><i class="fa fa-certificate"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Icon</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($badgesData as $badge) {
			// Print row for this badge
			echo '<tr>
			<td><p class="text-center">'.$badge['id'].'</p></td>
			<td><p class="text-center">'.$badge['name'].'</p></td>
			<td><p class="text-center"><i class="fa '.$badge['icon'].' fa-2x"></i></p></td>
			<td><p class="text-center">
			<a title="Edit badge" class="btn btn-xs btn-primary" href="/index.php?p=109&id='.$badge['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="Delete badge" class="btn btn-xs btn-danger" onclick="sure(\'/submit.php?action=removeBadge&id='.$badge['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div class="text-center">
			<a href="/index.php?p=109&id=0" type="button" class="btn btn-primary">Add a new badge</a>
			<a type="button" class="btn btn-success" data-toggle="modal" data-target="#quickEditUserBadgesModal">Edit user badges</a>
		</div>';
		echo '</div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserBadgesModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserBadgesModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserBadgesModalLabel">Edit user badges</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUserBadges" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control-black" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user badges</button>
		</div>
		</div>
		</div>
		</div>';
	}

	/*
	 * AdminEditDocumentation
	 * Prints the admin panel edit documentation file page
	*/
	public static function AdminEditDocumentation() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid documentation page id');
			}
			// Check if we are editing or creating a new docs page
			if ($_GET['id'] > 0) {
				$docData = $GLOBALS['db']->fetch('SELECT * FROM docs WHERE id = ?', $_GET['id']);
			} else {
				$docData = ['id' => 0, 'doc_name' => 'New Documentation Page', 'doc_contents' => '', 'public' => 1];
			}
			// Check if this doc page exists
			if (!$docData) {
				throw new Exception("That documentation page doesn't exist");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			// Selected values stuff
			$selected[0] = [0 => '', 1 => ''];
			// Get selected stuff
			$selected[0][$docData['public']] = 'selected';
			echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Edit documentation page</font></p>';
			echo '<table class="table table-striped table-hover table-75-center">';
			echo '<tbody><form id="edit-doc-form" action="submit.php" method="POST"><input name="action" value="saveDocFile" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control-black" value="'.$docData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Page Name</td>
			<td><p class="text-center"><input type="text" name="t" class="form-control-black" value="'.$docData['doc_name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Page content</td>
			<td><textarea type="text" name="c" class="form-control-black" style="height: 200px;max-width:100%" spellcheck="false">'.$docData['doc_contents'].'</textarea></td>
			</tr>';
			echo '<tr class="success"><td></td><td>Tip: You can use markdown syntax instead of HTML syntax</td></tr>';
			echo '<tr>
			<td>Public</td>
			<td>
			<select name="p" class="selectpicker" data-width="100%">
			<option value="1" '.$selected[0][1].'>Yes</option>
			<option value="0" '.$selected[0][0].'>No</option>
			</select>
			</td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-doc-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=106&e='.$e->getMessage());
		}
	}

	/*
	 * AdminEditBadge
	 * Prints the admin panel edit badge page
	*/
	public static function AdminEditBadge() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid badge id');
			}
			// Check if we are editing or creating a new badge
			if ($_GET['id'] > 0) {
				$badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			} else {
				$badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
			}
			// Check if this doc page exists
			if (!$badgeData) {
				throw new Exception("That badge doesn't exist");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit badge</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="saveBadge" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control-black" value="'.$badgeData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><p class="text-center"><input type="text" name="n" class="form-control-black" value="'.$badgeData['name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Icon</td>
			<td><p class="text-center"><input type="text" name="i" class="form-control-black icp icp-auto" value="'.$badgeData['icon'].'" ></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * AdminEditUserBadges
	 * Prints the admin panel edit user badges page
	*/
    
	public static function AdminAddKey() {
		try {
			// Check if id is set
		/*	if (!isset($_GET['id'])) {
				throw new Exception('Invalid badge id');
			}
			*/
            // Check if we are editing or creating a new badge
			/*if ($_GET['id'] > 0) {
				$badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			} else {
				$badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
			}
			// Check if this doc page exists
			if (!$badgeData) {
				throw new Exception("That badge doesn't exist");
			}*/
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-key"></i>	Add Beta keys</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="addKey" hidden>';
			echo '<tr>
			<td>Keys count</td>
			<td><p class="text-center"><input type="number" name="count" class="form-control-black" value="1"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Add Key(s)</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}    
    
    
	public static function AdminEditUserBadges() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			// get all badges
			$allBadges = $GLOBALS['db']->fetchAll("SELECT id, name FROM badges");
			// Get user badges
			$userBadges = $GLOBALS['db']->fetchAll('SELECT badge FROM user_badges ub WHERE ub.user = ?', $_GET['id']);
			// Get username
			$username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $_GET['id']));
			// Print edit user badges stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit user badges</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-user-badges" action="submit.php" method="POST"><input name="action" value="saveUserBadges" hidden>';
			echo '<tr>
			<td>User</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control-black" value="'.$username.'" readonly></td>
			</tr>';
			for ($i = 1; $i <= 5; $i++) {
				echo '<tr>
				<td>Badge ' . $i . '</td>
				<td>';
				echo "<select name='b0$i' class='selectpicker' data-width='100%'>";
				foreach ($allBadges as $badge) {
					$selected = "";
					if ($badge["id"] == @$userBadges[$i-1]["badge"])
						$selected = " selected";
					echo "<option value='$badge[id]'$selected>$badge[name]</option>";
				}
				echo '</select></td>
				</tr>';
			}
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Save changes</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * AdminBanchoSettings
	 * Prints the admin panel bancho settings page
	*/
	public static function AdminBanchoSettings() {
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$bm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'"));
		$od = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'free_direct'"));
		$rm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'"));
		$mi = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
		$lm = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'"));
		$ln = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
		$cv = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_versions'"));
		$cmd5 = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_md5s'"));
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($bm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($rm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($od == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<p align="center"><font size=5><i class="fa fa-server"></i>	Bancho settings</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveBanchoSettings" hidden>';
		echo '<tr>
		<td>Maintenance mode (bancho)</td>
		<td>
		<select name="bm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Restricted mode joke</td>
		<td>
		<select name="rm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Free osu!direct</td>
		<td>
		<select name="od" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Menu bottom icon<br>(imageurl|clickurl)</td>
		<td><p class="text-center"><input type="text" value="'.$mi.'" name="mi" class="form-control-black"></td>
		</tr>';
		echo '<tr>
		<td>Login #osu messages<br>One per line<br>(user|message)</td>
		<td><textarea type="text" name="lm" class="form-control-black" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$lm.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Login notification</td>
		<td><textarea type="text" name="ln" class="form-control-black" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ln.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Supported osu! versions<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cv.'" name="cv" class="form-control-black"></td>
		</tr>';
		echo '<tr>
		<td>Supported osu!.exe md5s<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cmd5.'" name="cmd5" class="form-control-black"></td>
		</tr>';
		echo '<tr class="success">
		<td colspan=2><p align="center"><b>Settings are automatically reloaded on Bancho when you press "Save settings".</b> There\'s no need to do <i>!system reload</i> manually anymore.</p></td>
		</tr>';
		echo '</tbody><table>
		<div class="text-center"><button type="submit" class="btn btn-primary">Save settings</button><br></div></div></div></form>';
		echo '</div>';
	}

	/*
	 * AdminLog
	 * Prints the admin log page
	*/
	public static function AdminLog() {
		// TODO: Ask stampa piede COME SI DICHIARANO LE COSTANTY IN PIACCAPPI??
		$pageInterval = 50;

		// Get data
		$first = false;
		if (isset($_GET["from"])) {
			$from = $_GET["from"];
			$first = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1")) == $from;
		} else {
			$from = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1"));
			$first = true;
		}
		$to = $from-$pageInterval;
		$logs = $GLOBALS['db']->fetchAll('SELECT rap_logs.*, users.username FROM rap_logs LEFT JOIN users ON rap_logs.userid = users.id WHERE rap_logs.id <= ? AND rap_logs.id > ? ORDER BY rap_logs.datetime DESC', [$from, $to]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper" style="text-align: left;"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span align="center"><h2><i class="fa fa-calendar"></i>	Admin Log</h2></span>';
		// Main page content here
		echo '<div class="bubbles-container">';
		if (!$logs) {
			printBubble(999, "You", "have reached the end of the life the universe and everything. Now go fuck a donkey.", time()-(43*60), "The Hitchhiker's Guide to the Galaxy");
		} else {
			$lastDay = -1;
			foreach ($logs as $entry) {
				$currentDay = date("z", $entry["datetime"]);
				if ($lastDay != $currentDay)
					echo'<div class="line"><div class="line-text"><span class="label label-primary">' . date("d/m/Y", $entry["datetime"]) . '</span></div></div>';
				printBubble($entry["userid"], $entry["username"], $entry["text"], $entry["datetime"], $entry["through"]);
				$lastDay = $currentDay;
			}
		}
		echo '</div>';
		echo '<br><br><p align="center">';
		if (!$first)
			echo '<a href="/index.php?p=116&from=' .($from+$pageInterval) . '">< Prev page</a>';
		if (!$first && $logs)
			echo ' | ';
		if ($logs)
			echo '<a href="/index.php?p=116&from=' . $to . '">Next page</a> ></p>';
		// Template end
		echo '</div></div></div>';
	}

	/*
	 * HomePage
	 * Prints the homepage
	*/
	public static function HomePage() {
		P::GlobalAlert();
		// Home success message
		$success = ['forgetDone' => 'Done! Your "Stay logged in" tokens have been deleted from the database.'];
		$error = [1 => 'You are already logged in.'];
		if (!empty($_GET['s']) && isset($success[$_GET['s']])) {
			self::SuccessMessage($success[$_GET['s']]);
		}
		if (!empty($_GET['e']) && isset($error[$_GET['e']])) {
			self::ExceptionMessage($error[$_GET['e']]);
		}
		$color = "pink";
		if (mt_rand(0,9) == 0) {
			switch(mt_rand(0,3)) {
				case 0: $color = "red"; break;
				case 1: $color = "blue"; break;
				case 2: $color = "green"; break;
				case 3: $color = "orange"; break;
			}
		}

        echo '<div class="col-lg-12 text-center">
        <p align="center"><img src="/images/logos/logo.png" style="width:50%"></p>
<div class="col-lg-3 text-center"><div class="panel-heading"><h5><b>Most played beatmaps on this week
    
    </b></h5></div><div class="alert alert-up" role="alert"><table class="table">
			<tbody>
	';

		$mostPlays =  $GLOBALS["db"]->fetchAll("SELECT ANY_VALUE(beatmaps.creator)creator,beatmaps.beatmapset_id,ANY_VALUE(beatmaps.artist)artist,ANY_VALUE(beatmaps.title)title, ANY_VALUE(beatmaps.beatmap_md5)beatmap_md5, COUNT(*) count from (SELECT beatmap_md5 FROM scores WHERE scores.time > UNIX_TIMESTAMP() - 86400 * 7 AND scores.play_mode = 0) AS scores LEFT JOIN beatmaps ON (beatmaps.beatmap_md5 = scores.beatmap_md5) WHERE  beatmaps.ranked > 0 GROUP BY beatmapset_id ORDER BY count DESC LIMIT 5");

	

		foreach($mostPlays as $play)
{
    echo'
<tr>
<td class = "warning">
<div class = "user-home__beatmapsets">
<a class = "user-home-beatmapset" href = "https://osu.gatari.pw/s/'.$play["beatmapset_id"].'">
<img class = "user-home-beatmapset__cover" src = "https://b.ppy.sh/thumb/'.$play["beatmapset_id"].'l.jpg">
<div class = "user-home-beatmapset__meta">
<div class = "user-home-beatmapset__title u-ellipsis-overflow">'.$play["title"].'</div>
<div class = "user-home-beatmapset__artist u-ellipsis-overflow">'.$play["artist"].'</div>
<div class = "user-home-beatmapset__creator u-ellipsis-overflow">
by '.$play["creator"].', <span class = "user-home-beatmapset__playcount">'.$play["count"].' plays</span>
</div>
</div>

</a></div></td>

</tr>';

}

 echo '</tbody></table></div></div>
			
  <div class="col-lg-6 text-center">
				 <script type="text/javascript" src="//vk.com/js/api/openapi.js?146"></script>

<!-- VK Widget -->
<div id="vk_groups"></div>
<script type="text/javascript">
VK.Widgets.Group("vk_groups", {mode: 4, height: "480", width: "550"}, 139469474);
</script>
		</div>
		<div class="col-lg-3 text-center"><div class="panel-heading"><h5><b>Server info
    
    </b></h5></div><div class="alert alert-up mp" role="alert">
			<div class="panel panel-green">
			<div class="panel-heading main">
			<div class="row">
			<div class="col-xs-3"><i class="fa fa-user fa-4x"></i></div>
			<div class="col-xs-9 text-right">
				<div id="online" class="huge" ><img src="/images/loading.gif"></div>
				<div>Online users</div>
			</div></div></div></div>

<div class="panel panel-warning">
			<div class="panel-heading main">
			<div class="row">
			<div class="col-xs-3"><i class="fa fa-gamepad fa-4x"></i></div>
			<div class="col-xs-9 text-right">
				<div id="scores" class="huge"><img src="/images/loading.gif"></div>
				<div>Total scores</div>
			</div></div></div></div>
			<div class="panel panel-primary">
			<div class="panel-heading main">
			<div class="row">
			<div class="col-xs-3"><i class="fa fa-user fa-4x"></i></div>
			<div class="col-xs-9 text-right">
				<div id="users" class="huge"><img src="/images/loading.gif"></div>
				<div>Total users</div>
			</div></div></div></div>
			<div class="panel panel-red">
			<div class="panel-heading main">
			<div class="row">
			<div class="col-xs-3"><i class="fa fa-thumbs-down fa-4x"></i></div>
			<div class="col-xs-9 text-right">
				<div id="banned" class="huge"><img src="/images/loading.gif"></div>
				<div>Banned users</div>
			</div></div></div></div>

			</div></div>
';



echo '
</div>
		</div>';

		// Home alert
		self::HomeAlert();
	}


		public static function Match($matchID)
{
		global $ScoresConfig;
		global $PlayStyleEnum;
		APITokens::PrintScript(sprintf('var matchID = %s;', $matchID));
		echo '<br><hr><div id="matchinfo"> </div><br><hr>';
}
	public static function Beatmap($beatmap, $type)
	{
		global $ScoresConfig;
		global $PlayStyleEnum;

				// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();	
		$where = "beatmap_id";
		if ($type == "s")
			$where = "beatmapset_id";
		$beatmapData = $GLOBALS['db']->fetch("SELECT * FROM beatmaps WHERE $where = '$beatmap' ORDER BY playcount DESC LIMIT 1");
		if(empty($beatmapData)){
			echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>Beatmap not found!</b></div>';
			exit;
		}
		echo("<h2><b>");
		$re = '/(.* - .*) \[(.*)]/';
		$beatapSet = $beatmapData["beatmapset_id"];
        $str = $beatmapData["song_name"];
        preg_match_all($re, $str, $matches);
        $song_name = $matches[1][0];
		echo($song_name);
		echo("<hr></b></h2>");
		$beatmapDiffs = $GLOBALS['db']->fetchAll("SELECT mode,song_name,difficulty_std,difficulty_taiko, difficulty_ctb, difficulty_mania,beatmap_id FROM beatmaps WHERE beatmapset_id = '$beatapSet' ORDER BY difficulty_std ASC");
		if(count($beatmapDiffs) > 1) {
		echo'<div id="tablist"><ul>';

                                    $icon = ["std" => "", "taiko" => "-t", "mania" => "-m", "ctb" => "-f"];
                                        foreach ($beatmapDiffs as $key => $beatmap) { 
                                        $mode = getPlaymodeText($beatmap["mode"]);
                                        $diff =  floatval($beatmap["difficulty_$mode"]);
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
                                            $diffName = 'unknown'.$diff;
		$str = $beatmap["song_name"];
        preg_match_all($re, $str, $matches);
        $diff = $matches[2][0];
		echo '<li><a class="beatmapTab '.($beatmap["beatmap_id"] == $beatmapData["beatmap_id"] ? "selected":"").'" href="/b/'.$beatmap["beatmap_id"].'"><div class="diffIcon '.$diffName.'"></div><span>'.$diff.'</span></a></li>';
		
		
	}
		echo("</u1></div><hr>"); 
	}
		echo '<audio id="audio_'.$beatmapData["beatmapset_id"].'" src="https://b.ppy.sh/preview/'.$beatmapData["beatmapset_id"].'.mp3"></audio>';
		 $img = "https://b.ppy.sh/thumb/".$beatmapData["beatmapset_id"]."l.jpg";
		echo("<a onclick='play(".$beatmapData["beatmapset_id"].",false)'><img class='beatmap-preview' src='$img'></a>");

		echo'
		<div class="beatmap-info"><table>
			<tbody>
              <tr>
			<td id="stats-name">Song Length:</td>
			<td id="stats-value"><b>'.date("i:s", mktime(0, 0, intval($beatmapData["hit_length"]))).'</b></td>
              </tr>
              <tr>
			<td id="stats-name">Approach rate:</td>
			<td id="stats-value"><b>'.$beatmapData["ar"].'</b></td>
              </tr>
              <tr>
			<td id="stats-name">Overall Difficulty:</td>
			<td id="stats-value"><b>'.$beatmapData["od"].'</b></td>
              </tr>
              <tr>
			<td id="stats-name">BPM:</td>
			<td id="stats-value"><b>'.$beatmapData["bpm"].'</b></td>
              </tr>
              <tr>
			<td id="stats-name">Difficulty:</td>
			<td id="stats-value"><b>'.round($beatmapData["difficulty_".getPlaymodeText($beatmapData["mode"])],2).'</b></td>
              </tr>
             	 
  </tbody></table></div>';

$favs = $GLOBALS["db"]->fetchAll("SELECT userid, users.username FROM `favourite_beatmaps` LEFT JOIN users ON (users.id = userid) WHERE beatmapset_id = ?",[$beatmapData["beatmapset_id"]]);
$favsCount = count($favs);
echo'<div class="beatmap-favs">
<div><center><b>Favourited '.$favsCount.' times</b> in total</center>
</div>';
if($favsCount > 0) {
echo 'Users that love this map:
<br>';
foreach ($favs as $key => $user) {
	echo '<a href="/u/'.$user["userid"].'">'.$user["username"]."</a>";
if($key+1 > 15) {
echo ' and <b> many </b> more!';
break;
}else if($key+1 == $favsCount) break; else echo ', ';
}
}else
	echo'No one is in love with this beatmap :(';


echo'</div>';

 echo '<div class="list-group-item" style="text-align: center;display: table;margin-bottom: 10px !important;padding: 10px 19px;">'; 
 if($_SESSION["userid"] != 0) {
	$isFavourite = $GLOBALS["db"]->fetch("SELECT COUNT(*) count FROM `favourite_beatmaps` WHERE userid = ? AND beatmapset_id = ?",[$_SESSION["userid"],$beatmapData["beatmapset_id"]])["count"] > 0;
	if($isFavourite){
	echo '<div class="beatmap-downlaod"><a class="btn btn-dblue" id ="favourite" onclick="favourite(\''.$beatmapData["beatmapset_id"].'\', \'del\');"> Remove favourite</a></div>';
	}else
		echo '<div class="beatmap-downlaod"><a class="btn btn-blue" id ="favourite" onclick="favourite(\''.$beatmapData["beatmapset_id"].'\', \'add\');"> Add to favourites</a></div>';
}else echo '<div class="beatmap-downlaod"><a class="btn btn-blue" id ="favourite" href="/index.php?p=2"> Add to favourites</a></div>';
  echo '<div class="beatmap-downlaod"><a href="https://osu.gatari.pw/d/'.$beatmapData["beatmapset_id"].'"><button class="btn btn-default" style="padding: 6px 22px;">Download Beatmap</button></a></div><div class="beatmap-downlaod"><a href="osu://dl/'.$beatmapData["beatmapset_id"].'"><button class="btn btn-direct">Open in osu!Direct</button></a></div>'; 
echo'</div>';
if($beatmapData["ranked"] >=2){
echo'<div class="list-group-item" style="text-align: center;display: table;padding: 10px 17px;">
<a class="btn mode" id="none">
</a><a class="btn mode" id="hidden">
</a><a class="btn mode" id="hardrock"></a>
<a class="btn mode" id="double-time"></a>
<a class="btn mode" id="flashlight"></a>
<a class="btn mode" id="half-time"></a>
<a class="btn mode" id="easy"></a></div>';


		echo '<hr><div ><table id="beatmapScores" class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"> Rank</th><th>Player</th>'.(($beatmapData["mode"] == 0 || $beatmapData["mode"] == 3) ? '<th>PP</th>' : '').'<th>Score</th><th>Accuracy</th><th>Max Combo</th><th>300 / 100 / 50</th><th>Misses</th><th>Mods</th><th class="text-right">Date</th>'.($_SESSION["userid"] != 0 ? '<th> </th>' : '').'</tr>
		</thead>';
			// Print row


		echo '</table></div>';
	}
echo '<hr>';
	APITokens::PrintScript(sprintf('var BeatmapID = %s; var BeatmapSetID = %s; var userID = %s; var song_name = "%s"; var play_mode = %s;', $beatmapData["beatmap_id"], $beatmapData["beatmapset_id"],isset($_SESSION["userid"]) ? $_SESSION["userid"] : 0, urlencode($beatmapData["artist"]." - ".$beatmapData["title"]),  getPlayMode($beatmapData)));
	}

	/*
	 * UserPage
	 * Print user page for $u user
	 *
	 * @param (int) ($u) ID of user.
	 * @param (int) ($m) Playmode.

	*/

	public static function ChartPage(){
		global $ScoresConfig;
		global $PlayStyleEnum;

		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		try {
			$counts = [];
			$expireString = trim(timeDifference(time(), 1500843600, false));	
			if(1500843600 - time() < 0){
				echo '<h1><i class="fa fa-clock-o animated infinite pulse"></i>	Ожидайте новый чарт!</h1>';	
				return;
			}

		echo '<h1><i class="fa fa-clock-o animated infinite pulse"></i>	До конца осталось: '.$expireString.'</h1>';	
		echo'<br>
		<table class="table table-striped table-hover">
		<thead>
		<tr>
		<th>Rank</th>
		<th>Player</th>
		<th>' . "Points" . '</th>		
		</tr>
		</thead>';
		echo '<tbody>';

					$data = $GLOBALS["db"]->fetchAll("SELECT scores.*,beatmaps.difficulty_std,scores.pp, users.privileges from scores LEFT JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 LEFT JOIN users ON scores.userid = users.id WHERE privileges > 2 AND completed = 3 AND play_mode = 0 AND scores.beatmap_md5 IN (SELECT beatmap_md5 FROM chart_beatmaps) ");
			foreach($data as $key => $e){
				if($counts[$e["beatmap_md5"]] == null)
					$counts[$e["beatmap_md5"]] = [];
				array_push($counts[$e["beatmap_md5"]],["userid"=>$e["userid"], "score"=>($e["pp"] > 0 ? floatval($e["pp"]) : intval($e["score"])),"difficulty"=>floatval($e["difficulty_std"])]);
			}
			$price = array();
			foreach ($counts as $key => $row)
			{	
				usort($row, function($a, $b) {
					return $b['score'] - $a['score'];
				});
				$counts[$key] = $row;
			}
			$userCounts = [];
			foreach ($counts as $key => $value) {
				foreach ($value as $i => $e) {
					if($userCounts[$e["userid"]] == null)	$userCounts[$e["userid"]] = 0;
					$userCounts[$e["userid"]] += pow((50.0 / ($i +1)) * $e["difficulty"],0.8);
				}
			}
			$mas = [];
			foreach($userCounts as $u => $v){
				array_push($mas, ["userid"=>$u, "score"=>round($v)]);
			}
			usort($mas, function($a, $b) {
					return $b["score"] - $a["score"];
			});	
		// Print table rows
		foreach($mas as $key => $row){
			if($key >= 50) break;
			$pc = $GLOBALS["db"]->fetch("SELECT username, country  from users_stats WHERE id = ? LIMIT 1",[$row["userid"]]);
			// Increment rank
			$offset++;
			// Style for top and noob players
			if ($offset <= 1) {
				// Yellow bg and trophy for top 3 players
				$tc = 'warning';
				$rankSymbol = '<i class="fa fa-trophy"></i> ';
			} else {
				// Standard table style for everyone else
				$tc = 'default';
				$rankSymbol = '#';
			}
			echo '<tr class="'.$tc.'">
			<td><b>'.$rankSymbol.$offset.'</b></td>';
			$country = $pc['country'];
			echo '<td><img src="./images/flags/'.$country.'.png"/  width="18px" style="margin-top: -2px;">	<a href="/u/'.$row['userid'].'&m=0">'.$pc['username'].'</a></td>
			<td>'.$row["score"].'</td>
			</tr>';
		}
		// Close table
		echo '</tbody></table>';
					
			
			
		}	catch(Exception $e) {
			echo '<br><div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>'.$e->getMessage().'</b></div>';
		}
	}

	public static function UserPage($u, $m = -1){
				global $ScoresConfig;
		global $PlayStyleEnum;

		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		try {
			$wh = "AND privileges & 3 > 0";
			if (!checkLoggedIn()) {
				$imAdmin = false;
			} else {
				$imAdmin = hasPrivilege(Privileges::AdminManageUsers);
				$wh = "";
			}		
			$kind = $GLOBALS['db']->fetch('SELECT 1 FROM users WHERE id = ? '.$wh, [$u]) ? "id = ?" : "username LIKE '$u' ";

			// Check banned status
			$userData = $GLOBALS['db']->fetch("
SELECT
	users_stats.*, users.privileges,users.username, users.id as usersuid, users.latest_activity,
	users.silence_end, users.silence_reason, users.register_datetime
FROM users_stats
LEFT JOIN users ON users.id=users_stats.id
WHERE users.$kind LIMIT 1", [$u]);
			if (!$userData) {
				// LISCIAMI LE MELE SUDICIO
				throw new Fava('User not found');
			}

			// Get admin/pending/banned/restricted/visible statuses

			$isPending = (($userData["privileges"] & Privileges::UserPendingVerification) > 0);
			$isBanned = (($userData["privileges"] & Privileges::UserNormal) == 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$isRestricted = (($userData["privileges"] & Privileges::UserNormal) > 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$myUserID = (checkLoggedIn()) ? $_SESSION["userid"] : -1;
			$isVisible = (!$isBanned && !$isRestricted && !$isPending) || $userData["id"] == $myUserID;
			if (!$isVisible) {
				// The user is not visible
				if ($imAdmin) {
                     
					// We are admin, show admin message and print profile
					if ($isPending) {
						echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>	<b>This user has never logged in to Bancho and is pending verification.</b> Only admins can see this profile.</div>';
					} else if ($isBanned) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User banned.</b></div>';
					} else if ($isRestricted) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User restricted.</b></div>';
					}
				} else {
                   
					// We are a normal user, print 404 and die
					throw new Exception('User not found');
				}
			}
			// Get all user stats for all modes and username
			$username = $userData["username"];
			echo '<title> '.$Username."</title>";
			$userID = $userData["usersuid"];
			// Set default modes texts, selected is bolded below
			$modesText = [0 => 'osu!standard', 1 => 'Taiko', 2 => 'Catch the Beat', 3 => 'osu!mania'];
			// Get stats for selected mode
			$m = ($m < 0 || $m > 3 ? $userData['favourite_mode'] : $m);
			$modeForDB = getPlaymodeText($m);
			$modeReadable = getPlaymodeText($m, true);
			// Standard stats
			$rankedScore = $userData['ranked_score_'.$modeForDB];
			$totalScore = $userData['total_score_'.$modeForDB];
			$playCount = $userData['playcount_'.$modeForDB];
			$totalHits = $userData['total_hits_'.$modeForDB];
			$accuracy = $userData['avg_accuracy_'.$modeForDB];
			$replaysWatchedByOthers = $userData['replays_watched_'.$modeForDB];
			$pp = $userData['pp_'.$modeForDB];
			$country = $userData['country'];
			$usernameAka = $userData['username_aka'];
			$level = $userData['level_'.$modeForDB];
			$latestActivity = $userData['latest_activity'];
			$silenceEndTime = $userData['silence_end'];
			$silenceReason = $userData['silence_reason'];
			$followers = $userData["followers_count"];
						// Get badges id and icon (max 6 badges)
			$badgeID = [];
			$badgeIcon = [];
			$badgeName = [];
			$logArray = $GLOBALS["db"]->fetchAll("SELECT beatmaps.song_name, users_logs.log, users_logs.time,beatmaps.beatmap_id  FROM `users_logs` LEFT JOIN beatmaps ON (beatmaps.beatmap_md5 = users_logs.beatmap_md5) WHERE  `user` = ? AND users_logs.game_mode = ? AND users_logs.time > ? ORDER BY users_logs.time  DESC LIMIT 5",[$userID,$m,time()-2592000]);
			$isLegit = false;
			$badges = $GLOBALS["db"]->fetchAll("SELECT b.id, b.icon, b.name
			FROM user_badges ub
			INNER JOIN badges b ON b.id = ub.badge
			WHERE ub.user = ?", [$userID]);
			foreach ($badges as $key => $badge) {
				$badgeID[$key] = $badge["id"];
				$badgeIcon[$key] = htmlspecialchars($badge['icon']);
                if($badgeID[$key] == 4){
                	$isLegit = True;
               $badgeName[$key] = htmlspecialchars($badge['name']);
                   //$badgeName[$key] = "<font color='green'>".htmlspecialchars($badge['name'])."</font>";
                }
                else
				$badgeName[$key] = htmlspecialchars($badge['name']);
				if (empty($badgeIcon[$key])) {
					$badgeIcon[$key] = 0;
				}
				if (empty($badgeName[$key])) {
					$badgeIcon[$key] = '';
				}
			}

			// Set custom badge
			$showCustomBadge = hasPrivilege(Privileges::UserDonor, $userData['id']) && $userData["show_custom_badge"] == 1 && $userData["can_custom_badge"] == 1;
			if ($showCustomBadge) {
				for ($i=0; $i < 5; $i++) { 
					if (@$badgeID[$i] == 0) {
						$badgeID[$i] = -1;
						$badgeIcon[$i] = htmlspecialchars($userData["custom_badge_icon"]);
						$badgeName[$i] = "<i>".htmlspecialchars($userData["custom_badge_name"])."</i>";
						break;
					}
				}
			}


            
			// Make sure that we have at least one score to calculate maximum combo, otherwise maximum combo is 0
			$maximumCombo = $GLOBALS['db']->fetch('SELECT max_combo FROM scores WHERE userid = ? AND play_mode = ? ORDER BY max_combo DESC LIMIT 1', [$userData['id'], $m]);
			if ($maximumCombo) {
				$maximumCombo = current($maximumCombo);
			} else {
				$maximumCombo = 0;
			}
			// Get username style (for random funny stuff lmao)
			if ($silenceEndTime - time() > 0) {
				$userStyle = 'text-decoration: line-through;';
			} else {
				$userStyle = $userData["user_style"];
			}

			// Print API token data for scores retrieval
			APITokens::PrintScript(sprintf('var UserID = %s; var Mode = %s; var Username = \'%s\';', $userData["id"], $m, $username));

			// Get top/recent plays for this mode
			$beatmapsTable =  "beatmaps";
			$beatmapsField =  "song_name";
			$orderBy = ($ScoresConfig["enablePP"] ? "pp" : "score" );
			// Bold selected mode text.
			$modesText[$m] = '<b>'.$modesText[$m].'</b>';
			// Get userpage
			$userpageContent = $userData['userpage_content'];

	$userData['user_color'] = str_replace("#000000","white",$userData['user_color']);

			// Userpage header
			$countryReadble = countryCodeToReadable($country);
			echo '<div class="col-sm-2" id="prof-left"><div class="alert alert-up" role="alert"><div class="avatar"><img src="https://a.gatari.pw/'.$userID.'"></div>';
			echo'<div style="font-size: 105%; '.$userStyle.'"><b style="'. (!empty($userData["user_color"]) ? "color: ".str_replace("white","#333",$userData[user_color])." !important;" : "").' " title="'.htmlspecialchars($userData["username_aka"]).'">	'.$username.'</b></div>';
			if ($country != 'XX' && isset($country) && !empty($country)) 
				echo '<img src="/images/flags/'.$country.'.png" width=16px title="'.$countryReadble.'">	';
			if(hasPrivilege(Privileges::UserDonor, $userData['id']))
				echo '<div class="profileSupporter"></div>';
			if (isOnline($userData["id"])) 
				echo '<i class="fa fa-circle online-circle"></i>';
			else echo '<i class="fa fa-circle offline-circle"></i>';

						$u = $userData["id"];
			if($u == 1206)
			echo '<br><div class="gst-winner first"></div>';
			elseif ($u == 1088)
			echo '<br><div class="gst-winner second"></div>';
			elseif ($u == 1531)
			echo '<br><div class="gst-winner third"></div>';		
			// Friend button
			if (!checkLoggedIn() || $username == $_SESSION['username']) {
				$friendButton = '';
                        
			} else {
				$friendship = getFriendship($_SESSION['username'], $username);
			
                switch ($friendship) {
					case 1:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a></div>';
					break;
					case 2:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a></div>';
					break;
					default:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a></div>';
					break;
				}
			}
echo $friendButton;

	if ($friendButton == '') $margin = 8; else $margin = -8;
			echo '<br><div class="list-group-item" style="margin-top: '.$margin.'px !important;">	
													<font size="2">
													<div title="'.date("d/m/Y H:i",$userData["register_datetime"]).'" name="date_reg"><i class="fa fa-sign-in"></i> '.timeSince($userData["register_datetime"],false).' ago</div>';
													if($latestActivity > 0 && !isOnline($userData["id"]) )
														echo '<div title="'.date("d/m/Y H:i",$latestActivity).'" name="date_auth"><i class="fa fa-sign-out"></i> '.timeSince($latestActivity,false).' ago</div>';
													echo '<div name="followers"><i class="fa fa-users"></i>	'.$followers.' followers</div>';
													echo '</font>
													';



													echo '</div>'; 
													$buttons = "";
			if (checkLoggedIn() && $username != $_SESSION['username']){
				if(hasPrivilege(Privileges::UserPublic) && !$isLegit && !(hasPrivilege(Privileges::AdminManageUsers)))
					$buttons = $buttons.'<a href="#" class="report" onclick="report();">Report</a>';
			}
			if (hasPrivilege(Privileges::AdminManageUsers)) {
				$buttons =  $buttons.(strlen($buttons) > 0 ? "<br>" : "").'<a href="/index.php?p=103&id='.$u.'">Edit User</a>';
			}
			if (hasPrivilege(Privileges::AdminBanUsers) && $username != $_SESSION['username']) {
				$buttons =  $buttons.(strlen($buttons) > 0 ? "<br>" : "").'<a onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id='.$u.'\')";>Restrict User</a>';
			}
			echo $buttons;
													echo'</div>
													
													<a href="#tab-about" role="tab" data-toggle="tab" class="list-group-item" data-original-title="" title=""><i class="fa fa-user"></i> General</a>
												 
													<a href="#performance" role="tab" data-toggle="tab" class="list-group-item" data-original-title="" title=""><i class="fa fa-trophy"></i> Top Ranks</a>
													<a href="#recent" role="tab" data-toggle="tab" class="list-group-item" data-original-title="" title=""><i class="fa fa-clock-o"></i> Recent Plays</a>
													<a href="#best" role="tab" data-toggle="tab" class="list-group-item" data-original-title="" title=""><i class="fa fa-trophy"></i> First Places</a>	
													<a href="#favourites" role="tab" data-toggle="tab" class="list-group-item" data-original-title="" title=""><i class="fa fa-music"></i> 	Beatmaps</a>														
<br>';

			if ((@$badgeID[0] > 0 || @$badgeID[0] == -1) && !empty($badgeIcon[0])) {
				echo '<div class="alert alert-up badge"><i class="fa '.$badgeIcon[0].' fa"></i>	'.$badgeName[0].'</div>';
			}
			if ((@$badgeID[1] > 0 || @$badgeID[1] == -1) && !empty($badgeIcon[1])) {
				echo '<div class="alert alert-up badge"><i class="fa '.$badgeIcon[1].' fa"></i>	'.$badgeName[1].'</div>';
			}
			if ((@$badgeID[2] > 0 || @$badgeID[2] == -1) && !empty($badgeIcon[2])) {
				echo '<div class="alert alert-up badge"><i class="fa '.$badgeIcon[2].' fa"></i>	'.$badgeName[2].'</div>';
			}										
			if ((@$badgeID[3] > 0 || @$badgeID[3] == -1) && !empty($badgeIcon[3])) {
				echo '<div class="alert alert-up badge"><i class="fa '.$badgeIcon[3].' fa"></i>	'.$badgeName[3].'</div>';
			}
			if ((@$badgeID[4] > 0 || @$badgeID[4] == -1) && !empty($badgeIcon[4])) {
				echo '<div class="alert alert-up badge"><i class="fa '.$badgeIcon[4].' fa"></i>	'.$badgeName[4].'</div>';
			}

echo '</div>';

echo '</div> <div class="col-sm-10 tab-content">';
			// Userpage custom stuff
			if (strlen($userpageContent) > 0) {
				// BB Code parser
				require_once 'bbcode.php';
				// Collapse type (if < 500 chars, userpage will be shown)
				if (strlen($userpageContent) <= 600) {
					$ct = 'in';
				} else {
					$ct = 'out';
				}
				// Print userpage content
				echo '<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Expand userpage</button>';
				if (checkLoggedIn() && $username == $_SESSION['username']) {
					echo '	<a href="/index.php?p=8" type="button" class="btn btn-default btn-xs">Edit</a>';
				}
				echo '</div>
							<div class="panel-collapse collapse '.$ct.'">
								<div class="panel-body" >'.bbcode::toHtml($userpageContent, true).'</div>
							</div>
						</div>
					</div>';
			} 
echo'<div class="tab-pane active" id="tab-about">';

										
//echo '<div class="alert alert-warning"  id="container" style=" height: 320px; margin: 0 auto"></div>';

			$reqScore = getRequiredScoreForLevel($level);
     
			$reqScoreNext = getRequiredScoreForLevel($level + 1);
           
			$scoreDiff = $reqScoreNext - $reqScore;
			$ourScore = $reqScoreNext - $totalScore;
			$percText = 100 - floor((100 * $ourScore) / ($scoreDiff + 1)); // Text percentage, real one
			if ($percText < 10) {
				$percBar = 10;
			} else {
				$percBar = $percText;
			} // Progressbar percentage, minimum 10 or it's glitched

            $rank = intval(Leaderboard::GetUserRank($u, $modeForDB));
			//$rank = sprintf('%2d', $rank);
			htmlspecialchars($usernameAka);


			echo'<div class="profileGameModes list-group-item">
<div><a class="profileGameModeButton'.($m == 0 ? " active" : "").'" href="?m=0">osu!</a><a class="profileGameModeButton'.($m == 1 ? " active" : "").'" href="?m=1">Taiko</a><a class="profileGameModeButton'.($m == 2 ? " active" : "").'" href="?m=2">CatchTheBeat</a><a class="profileGameModeButton'.($m == 3 ? " active" : "").'" href="?m=3">osu!mania</a></div>
</div><br>';
	if($_SESSION["olddesign"] != 1){

				if(($m == 0 || $m == 3 )&& $pp > 0 ){ 
					$rowOffset = 0;

					$history = $GLOBALS["db"]->fetchAll("SELECT * FROM user_charts WHERE userid = $u ORDER BY id DESC LIMIT 29");
					$history = array_reverse($history);
					$now = array_push ($history,["pp_".$modeForDB => $pp]);
					$higherPP = $pp;
					$lowerPP = $pp;
					foreach ($history as $key => $value) {
						if($value["pp_".$modeForDB] < $lowerPP)
							$lowerPP == $value["pp_".$modeForDB];
						if($value["pp_".$modeForDB] > $higherPP)
							$higherPP == $value["pp_".$modeForDB];
					}
	$historyOut = array();
	$offset = count($history);

	//account for the case where offset isn't yet updated but this user's ranks have been updated (happens during daily update).
	$check1 = $history[($offset + 1) % 30 + $rowOffset]["pp_".$modeForDB] - $history[$offset % 30 + $rowOffset]["pp_".$modeForDB];
	$check2 = $history[($offset + 29) % 30 + $rowOffset]["pp_".$modeForDB] - $history[$offset % 30 + $rowOffset]["pp_".$modeForDB];
	if (abs($check2) < abs($check1))
		$offset++;

	$i = 0;

	$rankHigh = round($higherPP / 1.2);
	$rankLow = round($lowerPP * 1.2);



	for ($i = 0; $i < 30; $i++)
	{
		$r = $history[($offset + $i) % 30 + $rowOffset]["pp_".$modeForDB];
		if ($r == 0)
			$r = null;
		else
		{
			if ($r > $rankHigh) $rankHigh = $r;
			if ($r < $rankLow || $rankLow < 0) $rankLow = $r;
		}

		array_push($historyOut, array($i - 29, $r != null ? $r : null));
	}

	$multipliers = array(2.5, 2, 2);
	$i = 2;

	$highLimit = round($higherPP / 1.2)+150;
	$lowLimit = round($lowerPP * 1.2)+150;

	/*
	while ($rankLow >= 1 && $lowLimit / $multipliers[$i] > $rankLow)
	{
		$lowLimit /= $multipliers[$i];
		$i = ($i-1) < 0 ? count($multipliers)-1 : $i-1;
	}

	$i = 0;

	// Prevent #2.5 from becoming a limit
	if($highLimit * 5 < $rankHigh)
	{
		$i = 2;
		$highLimit *= 5;

		while ($highLimit * $multipliers[$i] < $rankHigh)
		{
			$highLimit *= $multipliers[$i];
			
		}
	}

*/
	$i = ($i+1) % count($multipliers);
	$currentTick = $lowLimit;
	$ticks = array(-$currentTick);
	while ($currentTick * $multipliers[$i] <= $highLimit)
	{
		$currentTick *= $multipliers[$i];
		$i = ($i+1) % count($multipliers);

		array_push($ticks, -floor($currentTick));
	}

	echo'<div class="alert alert-warning" role="alert"id="chart1" >
  <svg style="height: 250px; width: 925px;"></svg>
</div>';
echo '
 <script src="/js/d3.v3.js"></script>

 <script src="/js/nv.d3.js"></script>
 <link href="/css/nv.d3.css" rel="stylesheet">
<script>
	  var lowLimit ='.$lowLimit.';
	  var highLimit = '.$highLimit.';
	  var ValueTicks = '.json_encode($ticks).';
	  var historyOut = '.json_encode($historyOut).';
	   </script>';

					//echo '<div class="alert alert-warning" role="alert"><canvas id="myChart" width="750" height="250"></canvas></div>';
				}
			}
			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3)) 
				echo '<div class="panel-heading text-left"><h4>Performance: <b>' . number_format($pp) .'pp</b> (<b>#'.$rank.'</b>)</h4></div>';
			else echo '<div class="panel-heading text-left"><h4>Rank: <b>#'.number_format($rank)."</b></h4></div>";
			
			echo '<div class="alert alert-warning" role="alert">';
			echo '
			<!-- Stats -->
			<table>
			<tbody>
			<tr>
			<td id="stats-name">Ranked Score</td>
			<td id="stats-value"><b>'.number_format($rankedScore).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total score</td>
			<td id="stats-value"><b>'.number_format($totalScore).'</b></td>
			<tr>
			<td id="stats-name">Play Count</td>
			<td id="stats-value"><b>'.number_format($playCount).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Hit Accuracy</td>
			<td id="stats-value"><b>'.(is_numeric($accuracy) ? accuracy($accuracy) : '0.00').'%</b></td>
			</tr>
			<tr>
			<td id="stats-name">Maximum Combo</td>
			<td id="stats-value"><b>'.number_format($maximumCombo).'</b></td>
			</tr>
			<tr>
				<td id="stats-name">Replays watched by others</td>
				<td id="stats-value"><b>'.number_format($replaysWatchedByOthers).'</b></td>
			</tr>';
			echo '<tr><td id="stats-name">From</td><td id="stats-value"><b>'.$countryReadble.'</b></td></tr>';
			// Show latest activity only if it's valid

			// Playstyle
			if ($userData['play_style'] > 0) {
				echo '<tr><td id="stats-name">Play style</td><td id="stats-value"><b>'.BwToString($userData['play_style'], $PlayStyleEnum).'</b></td></tr>';
			}

			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3))
				$scoringName = "PP";
			else
				$scoringName = "Score";

			echo '</tbody></table>
			';
	$x = $GLOBALS["db"]->fetch("SELECT COUNT(*) count FROM scores WHERE (rank='X' OR rank='XH') AND userid = ? AND play_mode = ? AND completed = 3",[$u,$m])["count"];
	$s = $GLOBALS["db"]->fetch("SELECT COUNT(*) count FROM scores WHERE (rank='S' OR rank='SH') AND userid = ? AND play_mode = ? AND completed = 3",[$u,$m])["count"];
	$a = $GLOBALS["db"]->fetch("SELECT COUNT(*) count FROM scores WHERE rank='A'  AND userid = ? AND play_mode = ? AND completed = 3",[$u,$m])["count"];
						echo '			<b>Level '.$level.'</b>
			<div class="progress">
			<div class="progress-bar" role="progressbar" aria-valuenow="'.$percBar.'" aria-valuemin="10" aria-valuemax="100" style="width:'.$percBar.'%">'.$percText.'%</div>
			</div>

<div class="list-group-item" style="display: inline-flex;">
<table align="center" width="90%" cellspacing="0" cellpadding="0" border="0" style="
">
<tbody>
<tr>
<td width="60"><img height="36" src="/images/ranks/ss_big.png"></td><td width="60">'.$x.'</td>
<td width="60"><img height="36" src="/images/ranks/s_big.png"></td><td width="60">'.$s.'</td>
<td width="60"><img height="36" src="/images/ranks/a_big.png"></td><td width="60">'.$a.'</td>
</tr>
</tbody>
</table>

			</div>

			</div>';


			

			echo '<table class="table" id="recent-activity-table">
			<tbody><tr><th class="text-left">	Recent Activity</th><th></th></tr>';
			if(count($logArray) == 0) {
				echo '<tr><td class="warning">This user hasn\'t done anything notable recently!</td></tr>';
			}else{
			foreach($logArray as $key => $log){
				echo '<tr><td class="warning">
				<p class="text-left"><a  href="https://osu.gatari.pw/u/'.$userID.'">'.$username.'</a> 
				'.$log["log"]. (isset($log["song_name"]) ? "<a href='https://osu.gatari.pw/b/".$log["beatmap_id"]."'>".$log["song_name"].'</a>' : "").'	</p></td><td class="warning"><p class="text-right"><small>'.timeSince($log['time']).' ago</small></p></td></tr>';
			}
		}
			echo '</tbody></table>';
			
	echo '</div>';
			// print table skeleton
//perf
echo '<div class="tab-pane" id="performance">';
echo '<table class="table" id="best-plays-table">
			<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="best" disabled>Show me more!</button>';
echo '</div>';
//perf

//recent
echo '<div class="tab-pane" id="recent">';
			echo '<table class="table" id="recent-plays-table">
			<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th class="text-right">' . $scoringName . '</th></tr> <tr><th class="text-left">	<input type="checkbox" onclick="ShowFailed();">	Show failed plays</th><th class="text-right"></th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="recent" disabled>Show me more!</button></div>';

//recent

//topranks

echo '<div class="tab-pane" id="best">';
			echo '<table class="table" id="first-plays-table">
			<tr><th class="text-left"><i class="fa fa-trophy"></i>	First places plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="first" disabled>Show me more!</button></div>';


//top

//favourites
echo '<div class="tab-pane" id="favourites">';

			echo '<table class="table" id="history-plays-table">
			<tr><th class="text-left">Most Played Beatmaps</th><th class="text-right"></th></tr>';
		
			$history = $GLOBALS["db"]->fetchAll("SELECT beatmaps.creator,beatmaps.beatmapset_id, beatmaps.difficulty_std, beatmaps.difficulty_taiko, beatmaps.difficulty_ctb, beatmaps.difficulty_mania,beatmaps.song_name, beatmaps.beatmap_id,scores.beatmap_md5, COUNT(*) count from scores LEFT JOIN beatmaps ON (beatmaps.beatmap_md5 = scores.beatmap_md5) WHERE userid = ? AND scores.play_mode = ? AND beatmaps.ranked>0 GROUP BY beatmap_md5 ORDER BY count DESC LIMIT 6",[$u, $m]);
	foreach ($history as $key => $map) {
			echo'<tr>
			<td class="warning">
				<img class="pc-img" src="https://b.ppy.sh/thumb/'.$map["beatmapset_id"].'l.jpg"><div class="song-title"><b><a href="/b/'.$map["beatmap_id"].'">'.$map['song_name'].'</a></b></div><div class="mapped" style="">mapped by '.(empty($map["creator"]) ? "Someone" : $map["creator"]).'</div></td>
			<td class="warning"><p class="text-right"><b>
		'.$map["count"].' plays</b><br><small>'.round($map["difficulty_".getPlaymodeText($m)],2).'★</small></p></td>
			</tr>';
	}

			echo '</table>';
			echo '<table class="table" id="favourite-beatmaps">
			<tr><th class="text-left"><i class="fa fa-star"></i>	Favourites beatmaps</th><th></th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="favourites" disabled>Show me more!</button></div>';

echo '</div>';
//favourites

echo '</div>';



		}
		catch(Exception $e) {
			echo '<br><div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>'.$e->getMessage().'</b></div>';
		}


		//end here	
	}

	public static function UserPageOld($u, $m = -1) {
		global $ScoresConfig;
		global $PlayStyleEnum;

		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		try {
			$wh = "AND privileges & 3 > 0";
			if (!checkLoggedIn()) {
				$imAdmin = false;
			} else {
				$imAdmin = hasPrivilege(Privileges::AdminManageUsers);
				$wh = "";
			}		
			$kind = $GLOBALS['db']->fetch('SELECT 1 FROM users WHERE id = ? '.$wh, [$u]) ? "id = ?" : "username LIKE '$u' ";

			// Check banned status
			$userData = $GLOBALS['db']->fetch("
SELECT
	users_stats.*, users.privileges,users.username, users.id as usersuid, users.latest_activity,
	users.silence_end, users.silence_reason, users.register_datetime
FROM users_stats
LEFT JOIN users ON users.id=users_stats.id
WHERE users.$kind LIMIT 1", [$u]);
			if (!$userData) {
				// LISCIAMI LE MELE SUDICIO
				throw new Fava('User not found');
			}

			// Get admin/pending/banned/restricted/visible statuses

			$isPending = (($userData["privileges"] & Privileges::UserPendingVerification) > 0);
			$isBanned = (($userData["privileges"] & Privileges::UserNormal) == 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$isRestricted = (($userData["privileges"] & Privileges::UserNormal) > 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$myUserID = (checkLoggedIn()) ? $_SESSION["userid"] : -1;
			$isVisible = (!$isBanned && !$isRestricted && !$isPending) || $userData["id"] == $myUserID;
			if (!$isVisible) {
				// The user is not visible
				if ($imAdmin) {
                     
					// We are admin, show admin message and print profile
					if ($isPending) {
						echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>	<b>This user has never logged in to Bancho and is pending verification.</b> Only admins can see this profile.</div>';
					} else if ($isBanned) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User banned.</b></div>';
					} else if ($isRestricted) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User restricted.</b></div>';
					}
				} else {
                   
					// We are a normal user, print 404 and die
					throw new Exception('User not found');
				}
			}
			// Get all user stats for all modes and username
			$username = $userData["username"];
			$userID = $userData["usersuid"];
			// Set default modes texts, selected is bolded below
			$modesText = [0 => 'osu!standard', 1 => 'Taiko', 2 => 'Catch the Beat', 3 => 'osu!mania'];
			// Get stats for selected mode
			$m = ($m < 0 || $m > 3 ? $userData['favourite_mode'] : $m);
			$modeForDB = getPlaymodeText($m);
			$modeReadable = getPlaymodeText($m, true);
			// Standard stats
			$rankedScore = $userData['ranked_score_'.$modeForDB];
			$totalScore = $userData['total_score_'.$modeForDB];
			$playCount = $userData['playcount_'.$modeForDB];
			$totalHits = $userData['total_hits_'.$modeForDB];
			$accuracy = $userData['avg_accuracy_'.$modeForDB];
			$replaysWatchedByOthers = $userData['replays_watched_'.$modeForDB];
			$pp = $userData['pp_'.$modeForDB];
			$country = $userData['country'];
			$usernameAka = $userData['username_aka'];
			$level = $userData['level_'.$modeForDB];
			$latestActivity = $userData['latest_activity'];
			$silenceEndTime = $userData['silence_end'];
			$silenceReason = $userData['silence_reason'];

			// Get badges id and icon (max 6 badges)
			$badgeID = [];
			$badgeIcon = [];
			$badgeName = [];
			$logArray = $GLOBALS["db"]->fetchAll("SELECT beatmaps.song_name, users_logs.log, users_logs.time,beatmaps.beatmap_id  FROM `users_logs` LEFT JOIN beatmaps ON (beatmaps.beatmap_md5 = users_logs.beatmap_md5) WHERE  `user` = ? AND users_logs.game_mode = ? AND users_logs.time > ? ORDER BY users_logs.time  DESC LIMIT 5",[$userID,$m,time()-2592000]);
			$isLegit = false;
			$badges = $GLOBALS["db"]->fetchAll("SELECT b.id, b.icon, b.name
			FROM user_badges ub
			INNER JOIN badges b ON b.id = ub.badge
			WHERE ub.user = ?", [$userID]);
			foreach ($badges as $key => $badge) {
				$badgeID[$key] = $badge["id"];
				$badgeIcon[$key] = htmlspecialchars($badge['icon']);
                if($badgeID[$key] == 4){
                	$isLegit = True;
                    $badgeName[$key] = "<font color='green'>".htmlspecialchars($badge['name'])."</font>";
                }
                else
				$badgeName[$key] = htmlspecialchars($badge['name']);
				if (empty($badgeIcon[$key])) {
					$badgeIcon[$key] = 0;
				}
				if (empty($badgeName[$key])) {
					$badgeIcon[$key] = '';
				}
			}

			// Set custom badge
			$showCustomBadge = hasPrivilege(Privileges::UserDonor, $userData['id']) && $userData["show_custom_badge"] == 1 && $userData["can_custom_badge"] == 1;
			if ($showCustomBadge) {
				for ($i=0; $i < 6; $i++) { 
					if (@$badgeID[$i] == 0) {
						$badgeID[$i] = -1;
						$badgeIcon[$i] = htmlspecialchars($userData["custom_badge_icon"]);
						$badgeName[$i] = "<i>".htmlspecialchars($userData["custom_badge_name"])."</i>";
						break;
					}
				}
			}


            
			// Make sure that we have at least one score to calculate maximum combo, otherwise maximum combo is 0
			$maximumCombo = $GLOBALS['db']->fetch('SELECT max_combo FROM scores WHERE userid = ? AND play_mode = ? ORDER BY max_combo DESC LIMIT 1', [$userData['id'], $m]);
			if ($maximumCombo) {
				$maximumCombo = current($maximumCombo);
			} else {
				$maximumCombo = 0;
			}
			// Get username style (for random funny stuff lmao)
			if ($silenceEndTime - time() > 0) {
				$userStyle = 'text-decoration: line-through;';
			} else {
				$userStyle = $userData["user_style"];
			}

			// Print API token data for scores retrieval
			APITokens::PrintScript(sprintf('var UserID = %s; var Mode = %s;', $userData["id"], $m));

			// Get top/recent plays for this mode
			$beatmapsTable =  "beatmaps";
			$beatmapsField =  "song_name";
			$orderBy = ($ScoresConfig["enablePP"] ? "pp" : "score" );
			// Bold selected mode text.
			$modesText[$m] = '<b>'.$modesText[$m].'</b>';
			// Get userpage
			$userpageContent = $userData['userpage_content'];

			// seriosuly fuck this shit who the fuck thought it was sane to write this fucking piece
			// of fucking shit like holy titties fuck tits cock the whole code of oldfrontend is absolutely
			// fucked but i still can't believe how FUCKED the code of the user profiles are why are they
			// even called userpages in this fucking code they're supposed to be profiles not pages
			// userpages are the ones with custom data written in bbcode
			// why are userpages in bbcode
			// like
			// markdown is much superior
			// anyway
			// you might wonder why the fuck i am doing the next thing
			// and that is $u used to always be an userid
			// and then changes happened and the validation to check $_GET["u"] was an username or
			// an userid was moved into the userpage() function
			// problem is though
			// i forgot there was another check of more or less the same thing in functions.php
			// (fuck functions.php by the way)
			// and so yeah
			// $u then became either an username or an userid
			// except I didn't know it was used in other places apart from the initial lookup of the user.
			// fuck
			// this
			// gay
			// earth
			// https://www.youtube.com/watch?v=HnrjygAG18o
			// TOOONIGHT IM GONNA HAVE MYSELF A REAL GOOD TIME
			// I FEEL ALIIIVE AH AH AAAH
			// AND THE WORLD
			// IS TURNING INSIDE OUT YEAH
			// I'M FLOATING AROUND IN ECSTASY
			// SO DON'T STOP ME NOW
			// SO DON'T STOP ME NOW
			// CAUSE IM HAVING A GOOD TIME
			// HAVING A GOOD TIME
			// I'M A SUPERSTARE LEAKING THROUGH THE SKYES LIKE A TIGER
			// DEFYING THE LAWS OF GRAVITY'
			// I'M A RACING CAR PASSING BY LIKE LADY GODDIVA
			// I GOTTA GO
			// GO
			// GO
			// THERE'S NO STOPPING ME
			// Now that I filled my whole screen with this comment I can finally procede writing
			// some more shitty code
			// I hope my nonsense has made your day
			// And don't you dare post this on reddit.
			$u = $userData["id"];
			// Friend button
			if (!checkLoggedIn() || $username == $_SESSION['username']) {
				$friendButton = '';
                        
			} else {
				$friendship = getFriendship($_SESSION['username'], $username);
			
                switch ($friendship) {
					case 1:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a></div>';
					break;
					case 2:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a></div>';
					break;
					default:
						$friendButton = '<div id="friend-button"><a href="/submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a></div>';
					break;
				}
			}
			// Get rank
			$rank = intval(Leaderboard::GetUserRank($u, $modeForDB));
			// Set rank char (trophy for top 3, # for everyone else)
			if ($rank <= 3) {
				$rankSymbol = '<i class="fa fa-trophy"></i> ';
			} else {
				$rank = sprintf('%02d', $rank);
				$rankSymbol = '#';
			}
			// Silence thing
			if ($silenceEndTime - time() > 0) {
				echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>'.$username.'</b> can\'t speak in the chat for the next <b>'.timeDifference($silenceEndTime, time(), false).'</b> for the following reason: "<b>'.$silenceReason.'</b>"</div>';
			}
			// Userpage custom stuff
			if (strlen($userpageContent) > 0) {
				// BB Code parser
				require_once 'bbcode.php';
				// Collapse type (if < 500 chars, userpage will be shown)
				if (strlen($userpageContent) <= 500) {
					$ct = 'in';
				} else {
					$ct = 'out';
				}
				// Print userpage content
				echo '<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Expand userpage</button>';
				if (checkLoggedIn() && $username == $_SESSION['username']) {
					echo '	<a href="/index.php?p=8" type="button" class="btn btn-default btn-xs"><i>Edit</i></a>';
				}
				echo '</div>
							<div class="panel-collapse collapse '.$ct.'">
								<div class="panel-body" style="font-family: sans-serif;">'.bbcode::toHtml($userpageContent, true).'</div>
							</div>
						</div>
					</div>';
			}    
			
			$userData['user_color'] = str_replace("#000000","white",$userData['user_color']);

			// Userpage header
			echo '<div id="userpage-header">
			<!-- Avatar, username and rank -->
			<p><img id="user-avatar" src="'.URL::Avatar().'/'.$userData["id"].'" height="100" width="100" /></p>
			<p id="username"><div style="display: inline; font-size: 140%; '.$userStyle.'"><b style="'. (!empty($userData["user_color"]) ? "color: ".str_replace("black","white",$userData[user_color])." !important;" : "") .' ">';
			if ($country != 'XX' && isset($country) && !empty($country)) {
				echo '<img src="/images/flags/'.strtolower($country).'.png">	';
			}
			if (isOnline($userData["id"])) 
				echo '<i class="fa fa-circle online-circle"></i>';
			

			echo $username.'</b></div></p>';
			if ($usernameAka != '') {
				echo '<small style="font-family: sans-serif;"> <i> A.K.A '.htmlspecialchars($usernameAka).'</i></small>';
			}
			echo '<br><a href="/u/'.$u.'&m=0">'.$modesText[0].'</a> | <a href="/u/'.$u.'&m=1">'.$modesText[1].'</a> | <a href="/u/'.$u.'&m=2">'.$modesText[2].'</a> | <a href="/u/'.$u.'&m=3">'.$modesText[3].'</a>';

			echo "<br>";
			$buttons = "";
			if (checkLoggedIn() && $username != $_SESSION['username']){
				if(hasPrivilege(Privileges::UserPublic) && !$isLegit && !$ImAdmin)
					$buttons = $buttons.'<a href="#" class="report" onclick="report();">Report</a>';
			}
			if (hasPrivilege(Privileges::AdminManageUsers)) {
				$buttons =  $buttons.(strlen($buttons) > 0 ? " | " : "").'<a href="/index.php?p=103&id='.$u.'">Edit user</a> | <a href="/index.php?p=110&id='.$u.'">Edit badges</a>';
			}
			if (hasPrivilege(Privileges::AdminBanUsers) && $username != $_SESSION['username']) {
				$buttons =  $buttons.(strlen($buttons) > 0 ? " | " : "").'<a onclick="sure(\'/submit.php?action=banUnbanUser&id='.$u.'\')";>Ban user</a> | <a onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id='.$u.'\')";>Restrict user</a>';
			}
			echo $buttons;
			
			echo "</p>";
            
			echo '<div id="rank"><font size=5><b> '.$rankSymbol.$rank.'</b></font><br>';
			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3)) echo '<b>' . number_format($pp) . ' pp</b>';
			echo $friendButton;
			echo '</div>';
			echo '</div>';

			echo '<div id="userpage-content" style="font-family: \'Exo 2\', sans-serif;">
			<div class="col-md-3">';
			// Badges Left colum
			if (@$badgeID[0] > 0 || @$badgeID[0] == -1) {
				echo '<i class="fa '.$badgeIcon[0].' fa-2x"></i><br><b>'.$badgeName[0].'</b><br><br>';
			}
			if (@$badgeID[2] > 0 || @$badgeID[2] == -1) {
				echo '<i class="fa '.$badgeIcon[2].' fa-2x"></i><br><b>'.$badgeName[2].'</b><br><br>';
			}
			if (@$badgeID[4] > 0 || @$badgeID[4] == -1) {
				echo '<i class="fa '.$badgeIcon[4].' fa-2x"></i><br><b>'.$badgeName[4].'</b><br><br>';
			}
			echo '</div>
			<div class="col-md-3">';
			// Badges Right column
			if (@$badgeID[1] > 0 || @$badgeID[1] == -1) {
				echo '<i class="fa '.$badgeIcon[1].' fa-2x"></i><br><b>'.$badgeName[1].'</b><br><br>';
			}
			if (@$badgeID[3] > 0 || @$badgeID[3] == -1) {
				echo '<i class="fa '.$badgeIcon[3].' fa-2x"></i><br><b>'.$badgeName[3].'</b><br><br>';
			}
			if (@$badgeID[5] > 0 || @$badgeID[5] == -1) {
				echo '<i class="fa '.$badgeIcon[5].' fa-2x"></i><br><b>'.$badgeName[5].'</b><br><br>';
			}

// Calculate required score for our level
                                               

			$reqScore = getRequiredScoreForLevel($level);
             
			$reqScoreNext = getRequiredScoreForLevel($level + 1);
           
			$scoreDiff = $reqScoreNext - $reqScore;
			$ourScore = $reqScoreNext - $totalScore;
			$percText = 100 - floor((100 * $ourScore) / ($scoreDiff + 1)); // Text percentage, real one
			if ($percText < 10) {
				$percBar = 10;
			} else {
				$percBar = $percText;
			} // Progressbar percentage, minimum 10 or it's glitched

			echo '</div><div class="col-md-6 nopadding">
			<!-- Stats -->
			<b>Level '.$level.'</b>
			<div class="progress">
			<div class="progress-bar" role="progressbar" aria-valuenow="'.$percBar.'" aria-valuemin="10" aria-valuemax="100" style="width:'.$percBar.'%">'.$percText.'%</div>
			</div>
			<table>
			<tr>
			<td id="stats-name">Ranked Score</td>
			<td id="stats-value"><b>'.number_format($rankedScore).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total score</td>
			<td id="stats-value">'.number_format($totalScore).'</td>
			<tr>
			<td id="stats-name">Play Count</td>
			<td id="stats-value"><b>'.number_format($playCount).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Hit Accuracy</td>
			<td id="stats-value"><b>'.(is_numeric($accuracy) ? accuracy($accuracy) : '0.00').'%</b></td>
			</tr>
			<tr>
			<td id="stats-name">Maximum Combo</td>
			<td id="stats-value"><b>'.number_format($maximumCombo).'</b></td>
			</tr>
			<tr>
				<td id="stats-name">Replays watched by others</td>
				<td id="stats-value"><b>'.number_format($replaysWatchedByOthers).'</b></td>
			</tr>';
			echo '<tr><td id="stats-name">From</td><td id="stats-value"><b>'.countryCodeToReadable($country).'</b></td></tr>';
			// Show latest activity only if it's valid
			if ($latestActivity != 0) {
				echo '<tr>
				<td id="stats-name">Latest activity</td>
				<td id="stats-value"><b>'.timeDifference(time(), $latestActivity).'</b></td>
			</tr>';
			}
			echo '<tr>
				<td id="stats-name">Registered</td>
				<td id="stats-value"><b>'.timeDifference(time(), $userData["register_datetime"]).'</b></td>
			</tr>';
			// Playstyle
			if ($userData['play_style'] > 0) {
				echo '<tr><td id="stats-name">Play style</td><td id="stats-value"><b>'.BwToString($userData['play_style'], $PlayStyleEnum).'</b></td></tr>';
			}

			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3))
				$scoringName = "PP";
			else
				$scoringName = "Score";

			echo '</table>
			</div>
			</div>
			<div id ="userpage-plays">';

			echo '<table class="table" id="recent-activity-table">
			<tbody><tr><th class="text-left">	Recent Activity</th><th></th></tr>';
			if(count($logArray) == 0) {
				echo '<tr><td class="warning">This user hasn\'t done anything notable recently!</td></tr>';
			}else{
			foreach($logArray as $key => $log){
				echo '<tr><td class="warning">
				<p class="text-left"><b><a style="color: #b3b3b3 !important; " href="https://osu.gatari.pw/u/'.$userID.'">'.$username.'</a> 
				</b>'.$log["log"]. (isset($log["song_name"]) ? "<a href='https://osu.gatari.pw/b/".$log["beatmap_id"]."'>".$log["song_name"].'</a>' : "").'	</p></td><td class="warning"><p class="text-right"><small>'.timeSince($log['time']).' ago</small></p></td></tr>';
			}
		}
			echo '</tbody></table>';
			echo '<table class="table" id="best-plays-table">
			<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="best" disabled>Show me more!</button>';

			// brbr it's so cold
			echo '<br><br><br>';

			// print table skeleton
			echo '<table class="table" id="recent-plays-table">
			<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="recent" disabled>Show me more!</button></div>';
		}
		catch(Exception $e) {
			echo '<br><div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>'.$e->getMessage().'</b></div>';
		}
	}

	/*
	 * AboutPage
	 * Prints the about page.
	*/
	public static function AboutPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo file_get_contents('./html_static/about.html');
	}

	/*
	 * StopSign
	 * For preventing future multiaccounters.
	*/
	public static function StopSign() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		if (!isset($_GET["user"])) {
			self::ExceptionMessage("lol");
			return;
		}
		echo str_replace("{}", htmlspecialchars($_GET["user"]), file_get_contents('./html_static/elmo_stop.html'));
	}

	/*
	 * RulesPage
	 * Prints the rules page.
	*/
	public static function RulesPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		$doc = $GLOBALS['db']->fetch('SELECT doc_contents FROM docs WHERE is_rule = "1" LIMIT 1');
		if (!$doc) {
			self::ExceptionMessage('Looks like the admins forgot to set a rules page in their documentation file listing. Which means, anarchy reigns here!');
			return;
		}
		require_once 'parsedown.php';
		$p = new Parsedown();
		echo "<div class='text-left'>".$p->text($doc['doc_contents']).'</div>';
	}
    

    public static function Documentation(){
        $id = $_GET['id'];
        // Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		$doc = $GLOBALS['db']->fetch("SELECT `doc_contents` FROM docs WHERE `public` = '1' AND `id` = '$id' LIMIT 1");
		if (!$doc) {
			self::ExceptionMessage('Looks like the admins forgot to set a rules page in their documentation file listing. Which means, anarchy reigns here!');
			return;
		}
		require_once 'parsedown.php';
		$p = new Parsedown();
		echo "<div class='text-left'>".$p->text($doc['doc_contents']).'</div>';
    }

	/*
	 * ChangelogPage
	 * Prints the Changelog page.
	*/
	public static function Changelogpage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Changelog
		getChangelog();
	}

	/*
	 * ExceptionMessage
	 * Display an error alert with a custom message.
	 *
	 * @param (string) ($e) The custom message (exception) to display.
	*/
	public static function ExceptionMessage($e, $ret = false) {
		$p = '<div class="container alert alert-danger" role="alert" style="width: 100%;"><p align="center"><b>An error occurred:<br></b>'.$e.'</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function ExceptionMessageStaccah($s, $ret = false) {
		return P::ExceptionMessage(htmlspecialchars($s), $ret);
	}

	/*
	 * SuccessMessage
	 * Display a success alert with a custom message.
	 *
	 * @param (string) ($s) The custom message to display.
	*/
	public static function SuccessMessage($s, $ret = false) {
		$p = '<div class="container alert alert-success" role="alert" style="width:100%;"><p align="center">'.$s.'</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function SuccessMessageStaccah($s, $ret = false) {
		return P::SuccessMessage(htmlspecialchars($s), $ret);
	}

	/*
	 * Messages
	 * Displays success/error messages from $_SESSION[errors] or $_SESSION[successes]
	 * (aka success/error messages set with addError and addSuccess).
	 *
	 * @return bool Whether something was printed.
	 */
	public static function Messages() {
		$p = false;
		if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
			foreach ($_SESSION['errors'] as $err) {
				self::ExceptionMessage($err);
				$p = true;
			}
			$_SESSION['errors'] = array();
		}
		if (isset($_SESSION['successes']) && is_array($_SESSION['successes'])) {
			foreach ($_SESSION['successes'] as $s) {
				self::SuccessMessage($s);
				$p = true;
			}
			$_SESSION['successes'] = array();
		}
		return $p;
	}

	/*
	 * LoggedInAlert
	 * Display a message to the user that he's already logged in.
	 * Printed when a logged in user tries to view a guest only page.
	*/
	public static function LoggedInAlert() {
		echo '<div class="alert alert-warning" role="alert">You are already logged in.</i></div>';
	}

	/*
	 * RegisterPage
	 * Prints the register page.
	*/
	public static function RegisterPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Registration enabled check
		if (!checkRegistrationsEnabled()) {
			// Registrations are disabled
			self::ExceptionMessage('<b>Registrations are currently disabled.</b>');
			die();
		}
		echo '<br><div id="narrow-content"><h1><i class="fa fa-plus-circle"></i>	Sign up</h1>';

		$ip = getIp();

		// Multiacc warning checks
		// Exact IP
		$multiIP = multiaccCheckIP($ip);
		// "y" cookie
		$multiToken = multiaccCheckToken();
		$multiThing = $multiIP === FALSE ? $multiToken : $multiIP;

		// Show multiacc warning if ip or token match
		$errors = self::Messages();
		if (($multiIP !== FALSE || $multiToken !== FALSE)) {
			if (@$_GET["iseethestopsign"] == "1") {
				echo '<div class="container alert alert-warning" role="alert" style="width: 100%;"><p align="center">Since I love delivering completely random quotes:<br><i>if you keep going the way you are now... you\'re gonna have a bad time.</i></p></div>';
			} else {
				$multiName = $multiThing["username"];
				redirect("/index.php?p=41&user=" . $multiName);
			}
		} else if (!$errors) {
			// Print default warning message if we have no exception/success/multiacc warn
			echo '<p>Please fill every field in order to sign up.<br></p>';
		}
		//echo '<div class="alert alert-danger animated shake" role="alert"><b><i class="fa fa-gavel"></i>	Please read the <a href="/index.php?p=23" target="_blank">rules</a> before creating an account.</b></div>
		//<a href="/index.php?p=16&id=1" target="_blank">Need some help?</a></p>';
		// Print register form
		echo '	<form action="submit.php" method="POST">
		<input name="action" value="register" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" max-width="25%"></span></span><input type="text" name="u" required class="form-control-black" placeholder="Username" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control-black" placeholder="Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control-black" placeholder="Repeat Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" max-width="25%"></span></span><input type="text" name="e" required class="form-control-black" placeholder="Email" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="fa fa-key" max-width="25%"></span></span><input type="text" name="k" required class="form-control-black" placeholder="Beta Key" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p><a href="https://github.com/nyawk/Gatari-Switcher/releases"> Download server switcher </a>
		<hr>
		<button type="submit" class="btn btn-primary">Sign up!</button>
		</form>
		';
	}

	/*
	 * ChangePasswordPage
	 * Prints the change password page.
	*/
	public static function ChangePasswordPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo '<div id="narrow-content"><h1><i class="fa fa-lock"></i>	Change password</h1>';
		// Print messages
		self::Messages();
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Fill the form with your existing and new desired password.</p>';
		}
		// Print change password form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="changePassword" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="pold" required class="form-control-black" placeholder="Current password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control-black" placeholder="New password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control-black" placeholder="Repeat new password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change password</button>
		</form>
		<hr></div>';
	}

	/*
	 * userSettingsPage
	 * Prints the user settings page.
	*/
	public static function userSettingsPage() {
		global $PlayStyleEnum;
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user settings data
		$data = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ? LIMIT 1', $_SESSION['userid']);
		// Title
		echo '<div id="narrow-content" style="font-family: \'Exo 2\', sans-serif;"><h1><i class="fa fa-cog"></i>	User settings</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', 'You can\'t edit your settings while you\'re restricted.'];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('User settings saved!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>You can edit your account settings here.</p>';
		}

		// Default select stuff
		$selected[1] = [0 => '', 1 => ''];
		$selected[2] = [0 => '', 1 => ''];
		
		$selected[1][isset($_COOKIE['st']) && $_COOKIE['st'] == 1] = 'selected';
		$selected[2][$data['show_custom_badge']] = 'selected';

		// Howl is cool so he does it in his own way
		$mode = $data['favourite_mode'];
		$cj = function ($index) use ($mode) {
			$r = "value='$index'";
			if ($index == $mode) {
				return $r.' selected';
			}

			return $r.'';
		};

		// Print form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserSettings" hidden>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Safe page title</span>
			<select name="st" class="selectpicker" data-width="100%">
				<option value="1" '.$selected[1][1].'>Yes</option>
				<option value="0" '.$selected[1][0].'>No</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon4" style="width:40%">Favourite gamemode</span>
			<select name="mode" class="selectpicker" data-width="100%">
				<option '.$cj(0).'>osu! Standard</option>
				<option '.$cj(1).'>Taiko</option>
				<option '.$cj(2).'>Catch the Beat</option>
				<option '.$cj(3).'>osu!mania</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon2" style="width:40%">Username colour</span>
			<input type="text" name="c" class="form-control-black colorpicker" value="'.$data['user_color'].'" placeholder="HEX/Html color" aria-describedby="basic-addon2" spellcheck="false">
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon3" style="width:40%">A.K.A</span>
			<input type="text" name="aka" class="form-control-black" value="'.htmlspecialchars($data['username_aka']).'" placeholder="Alternative username (not for login)" aria-describedby="basic-addon3" spellcheck="false">
		</div>';

		if (hasPrivilege(Privileges::UserDonor)) {
			echo '<p style="line-height: 15px"></p>
			<div class="input-group" style="width:100%">
				<span class="input-group-addon" id="basic-addon0" style="width:40%">Show custom badge</span>
				<select name="showCustomBadge" class="selectpicker" data-width="100%">
					<option value="1" '.$selected[2][1].'>Yes</option>
					<option value="0" '.$selected[2][0].'>No</option>
				</select>
			</div>';
			
					if($data['flag_changed'] == 0) {
						echo '<p style="line-height: 15px"></p>
			<div class="input-group" style="width:100%">
				<span class="input-group-addon" id="basic-addon0" style="width:40%">Country</span>
							<select name="country" class="selectpicker" data-width="100%">
			';
			require_once dirname(__FILE__) . "/countryCodesReadable.php";
			asort($c);
			// Push XX to top
			$c = array('XX' => $c['XX']) + $c;
			reset($c);
			foreach ($c as $k => $v) {
				$sd = "";
				if ($data['country'] == $k)
					$sd = "selected";
				$ks = $k;
				if (!file_exists(dirname(__FILE__) . "/../images/flags/$ks.png"))
					$ks = "xx";
				echo "<option value='$k' $sd data-content=\""
					. "<img src='images/flags/$ks.png' style='width: 20px;' alt='$k'>"
					. " $v\"></option>\n";
			}
			echo '
			</select>
			</div>';
		}
		
			if($data['username_changed'] == 0) {
			echo '<p style="line-height: 15px"></p><hr>';
		echo '
				<div class="alert alert-warning">
					<i class="fa fa-exclamation-triangle"></i>
					<b>Смена никнейма одноразовая. Будьте внимательны при выборе</b>
				</div>
				<div class="input-group" style="width:100%">
				<span class="input-group-addon" id="basic-addon3" style="width:40%">New Username</span>
						<input type="text" name="newUsername" class="form-control-black" value="'.htmlspecialchars($data['username']).'" placeholder="New Username" aria-describedby="basic-addon3" spellcheck="false" maxlength="14">
						<p style="line-height: 15px"></p>
					</div><div class="row">
				</div>';
			}
			}
		echo '<p style="line-height: 15px"></p><hr>';
		if (hasPrivilege(Privileges::UserDonor)) {
			echo '<h3>Custom Badge</h3>';
			if ($data["can_custom_badge"] == 0) {
				echo '<div class="alert alert-danger">
					<i class="fa fa-exclamation-triangle"></i>
					Due to an incorrect use of custom badges, we\'ve <b>revoked your ability to create custom badges.</b>
				</div>';
			} else {
				echo '
				<div class="alert alert-warning">
					<i class="fa fa-exclamation-triangle"></i>
					<b>Do not use offensive badges and do not pretend to be someone else with your badge.</b> If you abuse the badges system, you\'ll be <b>silenced</b> and you won\'t be able to <b>edit your custom badge</b> anymore.
				</div>
				<div class="row">
					<div class="col-md-6">
						<i id="badge-icon" class="fa '.htmlspecialchars($data["custom_badge_icon"]).' fa-2x"></i>
						<br>
						<b><span id="badge-name">'.htmlspecialchars($data["custom_badge_name"]).'</span></b>
					</div>
					<div class="col-md-6" style="text-align: left;">
						<input id="badge-icon-input" type="text" placeholder="Icon" name="badgeIcon" data-placement="bottomLeft" class="form-control-black icp icp-auto" value="'.htmlspecialchars($data["custom_badge_icon"]).'" maxlength="32">
						<p style="line-height: 15px"></p>
						<input id="badge-name-input" type="text" placeholder="Name" name="badgeName" class="form-control-black" value="'.htmlspecialchars($data["custom_badge_name"]).'" maxlength="24">
						<p style="line-height: 15px"></p>
					</div>
				</div>';
			}
			echo '<p style="line-height: 15px"></p>
				<hr>';
		}

		echo '<h3>Playstyle</h3>
		<div>
		';
		// Display playstyle checkboxes
		$playstyle = $data['play_style'];
		foreach ($PlayStyleEnum as $k => $v) {
			echo "
			<label style='font-weight: normal;'><input type='checkbox' name='ps_$k' value='1' ".($playstyle & $v ? 'checked' : '')."> $k</label><br>";
		}
		echo '
		</div>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save settings</button>
		</form>
		</div>';
		echo '<p style="line-height: 15px"></p><hr>';

	}

	/*
	 * ChangeAvatarPage
	 * Prints the change avatar page.
	*/
	public static function ChangeAvatarPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Title
		echo '<div id="narrow-content"><h1><i class="fa fa-picture-o"></i>	Change avatar</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', 'That file is not a valid image.', 'Invalid file format. Supported extensions are .png, .jpg and .jpeg', 'The file is too large. Maximum file size is 1MB.', 'Error while uploading avatar.', "You can't change your avatar while you're restricted."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('Avatar changed!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Give a nice touch to your profile with a custom avatar!<br></p>';
		}
		// Print form
		echo '
		<b>Current avatar:</b><br><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="100" width="100"/>
		<p style="line-height: 15px"></p>
		<form action="submit.php" method="POST" enctype="multipart/form-data">
		<input name="action" value="changeAvatar" hidden>
		<p align="center"><input type="file" name="file"></p>
		<i>Max size: 1MB<br>
		.jpg, .jpeg or <b>.png (recommended)</b><br>
		Recommended size: 100x100</i>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change avatar</button>
		</form>
		</div>';
	}

	/*
	 * UserpageEditorPage
	 * Prints the userpage editor page.
	*/
	public static function UserpageEditorPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get userpage content from db
		$content = $GLOBALS['db']->fetch('SELECT userpage_content FROM users_stats WHERE username = ?', $_SESSION['username']);
		$userpageContent = htmlspecialchars(current(($content === false ? ['t' => ''] : $content)));
		// Title
		echo '<h1><i class="fa fa-pencil"></i>	Userpage</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', "Your userpage <b>can't be longer than 1500 characters</b> (bb code syntax included)", "You can't edit your userpage while you're restricted."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('Userpage saved!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Introduce yourself here! <i>(max 1500 chars)</i></p>';
		}
		// Print form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserpage" hidden>
		<p align="center"><textarea name="c" class="sceditor" style="width:700px; height:400px;">'.$userpageContent.'</textarea></p>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save userpage</button>
		<button type="submit" class="btn btn-success" name="view" value="1">Save and view userpage</a>
		</form>
		';
	}

	/*
	 * PasswordRecovery - print the page to recover your password if you lost it.
	*/
	public static function PasswordRecovery() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo '<div id="narrow-content" style="width:500px"><h1><i class="fa fa-exclamation-circle"></i> Recover your password</h1>';
		// Print Exception if set and in array.
		$exceptions = ['Nice troll.', "That user doesn't exist.", "You are banned from Gatari. We won't let you come back in."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		if (isset($_GET['s'])) {
			self::SuccessMessage('You should have received an email containing instructions on how to recover your Gatari account');
		}
		if (checkLoggedIn()) {
			echo 'What are you doing here? You\'re already logged in, you moron!<br>';
			echo 'If you really want to fake that you\'ve lost your password, you should at the very least log out of Gatari, you know.';
		} else {
			echo '<p>Let\'s get some things straight. We can only help you if you DID put your actual email address when you signed up. If you didn\'t, you\'re screwed. Hope to know the admins well enough to tell them to change the password for you, otherwise your account is now dead.</p><br>
			<form action="submit.php" method="POST">
			<input name="action" value="recoverPassword" hidden>
			<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="fa fa-user" max-width="25%"></span></span><input type="text" name="username" required class="form-control-black" placeholder="Type your username." aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
			<button type="submit" class="btn btn-primary">Recover my password!</button>
			</form></div>';
		}
	}

	/*
	 * MaintenanceAlert
	 * Prints the maintenance alert and die if we are normal users
	 * Prints the maintenance alert and keep printing the page if we are mod/admin
	*/
	public static function MaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-warning margin" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Gatari\'s website is in <b>maintenance mode</b>. Only moderators and administrators have access to the full website.</p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-warning margin" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Gatari\'s website is in <b>maintenance mode</b>. We are working for you, <b>please come back later.</b></p></div>';
			die();
		}
	}

	/*
	 * GameMaintenanceAlert
	 * Prints the game maintenance alert
	*/
	public static function GameMaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Gatari\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u><br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Gatari\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u></b></p></div>';
		}
	}

	/*
	 * BanchoMaintenance
	 * Prints the game maintenance alert
	*/
	public static function BanchoMaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Gatari\'s Bancho server is in maintenance mode. You can\'t play on Gatari right now. Try again later.<br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Gatari\'s Bancho server is in maintenance mode. You can\'t play on Gatari right now. Try again later.</p></div>';
		}
	}

	/*
	 * MaintenanceStuff
	 * Prints website/game maintenance alerts
	*/
	public static function MaintenanceStuff() {
		// Check Bancho maintenance
		if (checkBanchoMaintenance()) {
			self::BanchoMaintenanceAlert();
		}
		// Game maintenance check
		if (checkGameMaintenance()) {
			self::GameMaintenanceAlert();
		}
		// Check website maintenance
		if (checkWebsiteMaintenance()) {
			self::MaintenanceAlert();
		}
	}

	/*
	 * GlobalAlert
	 * Prints the global alert (only if not empty)
	*/
	public static function GlobalAlert() {
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning margin" role="alert"><p align="center">'.$m.'</p></div>';
		}
		self::RestrictedAlert();
	}

	/*
	 * HomeAlert
	 * Prints the home alert (only if not empty)
	*/
	public static function HomeAlert() {
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning margin" role="alert"><p align="center">'.$m.'</p></div>';
		}
	}

	/*
	 * FriendlistPage
	 * Prints the friendlist page.
	*/

	public static function FriendlistPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user friends
		// Title and header message
		echo '<h1><i class="fa fa-star"></i>	Friends</h1>';
		echo '<div class="ui segments" > <div class="ui segment">';
		if(hasPrivilege(Privileges::UserDonor))
			echo'<p align=center><h5><input type="checkbox" class="checkbox" id="checkbox">
				<label for="checkbox">Show followers</label></h5></p>';
	echo'<div class="ui four column stackable grid" id="friend-list">';
	echo '</div><br><button type="button" class="btn btn-default load-more-user-scores"  disabled>Show me more!</button></div></div></div>';
		
	}

	public static function FriendlistPage2() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user friends
		$ourID = getUserID($_SESSION['username']);
		$friends = $GLOBALS['db']->fetchAll('
		SELECT user2, users.username
		FROM users_relationships
		LEFT JOIN users ON users_relationships.user2 = users.id
		WHERE user1 = ? AND users.privileges & 1 > 0', [$ourID]);
		// Title and header message
		echo '<h1><i class="fa fa-star"></i>	Friends</h1>';
		if (count($friends) == 0) {
			echo '<b>You don\'t have any friends.</b> You can add someone to your friends list<br>by clicking the <b>"Add as friend"</b> button on someones\'s profile.<br>You can add friends from the game client too.';
		} else {
			// Friendlist
			echo '<div class="ui segments">	
				<div class="ui segment">
				<div class="ui four column stackable grid">';
			// Loop through every friend and output its username and mutual status
			foreach ($friends as $friend) {
				$uname = $friend['username'];

				$mutual = ($friend['user2'] == 999 || getFriendship($friend['user2'], $ourID, true) == 2) ? True : False;
				switch ($mutual) {
					case False:
						$friendButton = '<a href="/submit.php?action=addRemoveFriend&u='.$friend['user2'].'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a>';
					break;
					case True:
						$friendButton = '<a href="/submit.php?action=addRemoveFriend&u='.$friend['user2'].'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a>';
					break;
					default:
						$friendButton = '<a href="/submit.php?action=addRemoveFriend&u='.$friend['user2'].'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a>';
					break;
				}
				echo '
				<div class="ui '.($mutual ? 'mutual' : '').' column">
							<h4 class="ui image header">
								<img src="https://a.gatari.pw/'.$friend['user2'].'" class="ui mini rounded image">
								<div class="content">
									<a href="/u/'.$friend['user2'].'">'.$uname.'</a>
									<div class="sub header">
										'.$friendButton.'
									</div>
								</div>
							</h4>
						</div>
				';
			}
			echo '</div>
			
		</div>
		
		
		
	</div>';
		}
	}
	public static function FriendlistPage1() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user friends
		$ourID = getUserID($_SESSION['username']);
		$friends = $GLOBALS['db']->fetchAll('
		SELECT user2, users.username
		FROM users_relationships
		LEFT JOIN users ON users_relationships.user2 = users.id
		WHERE user1 = ? AND users.privileges & 1 > 0', [$ourID]);
		// Title and header message
		echo '<h1><i class="fa fa-star"></i>	Friends</h1>';
		if (count($friends) == 0) {
			echo '<b>You don\'t have any friends.</b> You can add someone to your friends list<br>by clicking the <b>"Add as friend"</b> button on someones\'s profile.<br>You can add friends from the game client too.';
		} else {
			// Friendlist
			echo '<table class="table table-striped table-hover table-50-center">
			<thead>
			<tr><th class="text-center">Username</th><th class="text-center">Mutual</th></tr>
			</thead>
			<tbody>';
			// Loop through every friend and output its username and mutual status
			foreach ($friends as $friend) {
				$uname = $friend['username'];
				$mutualIcon = ($friend['user2'] == 999 || getFriendship($friend['user2'], $ourID, true) == 2) ? '<i class="fa fa-heart"></i>' : '';
				echo '<tr><td><div align="center"><a href="/u/'.$friend['user2'].'">'.$uname.'</a></div></td><td><div align="center">'.$mutualIcon.'</div></td></tr>';
			}
			echo '</tbody></table>';
		}
	}

	/*
	 * AdminRankRequests
	 * Prints the admin rank requests
	*/
	public static function AdminRankRequests() {
		global $ScoresConfig;
		// Get data
		$rankRequestsToday = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
		$rankRequests = $GLOBALS["db"]->fetchAll("SELECT rank_requests.*, users.username FROM rank_requests LEFT JOIN users ON rank_requests.userid = users.id WHERE time > ? ORDER BY id DESC LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		
		// Main page content here
		echo '<div class="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		//echo '<div style="width: 50%; margin-left: 25%;" class="alert alert-info" role="alert"><i class="fa fa-info-circle"></i>	Only the requests made in the past 24 hours are shown. <b>Make sure to load every difficulty in-game before ranking a map.</b><br><i>(We\'ll add a system that does it automatically soonTM)</i></div>';
		echo '<span align="center"><h2><i class="fa fa-music"></i>	Beatmap rank requests</h2></span>';
		echo '<hr>
		<h2 style="display: inline;">'.$rankRequestsToday["count"].'</h2><h3 style="display: inline;">/'.$ScoresConfig["rankRequestsQueueSize"].'</h3><br><h4>requests submitted today</h4>
		<hr>';
		echo '<table class="table table-striped table-hover" style="width: 94%; margin-left: 3%;">
		<thead>
		<tr><th><i class="fa fa-music"></i>	ID</th><th>Artist & song</th><th>Difficulties</th><th>Mode</th><th>From</th><th>When</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($rankRequests as $req) {
			$criteria = $req["type"] == "s" ? "beatmapset_id" : "beatmap_id";
			$b = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name, ranked FROM beatmaps WHERE ".$criteria." = ? LIMIT 1", [$req["bid"]]);

			if ($b) {
				$matches = [];
				if (preg_match("/(.+)(\[.+\])/i", $b["song_name"], $matches)) {
					$song = $matches[1];
				} else {
					$song = "Wat";
				}
			} else {
				$song = "Unknown";
			}

			if ($req["type"] == "s")
				$bsid = $req["bid"];
			else
				$bsid = $b ? $b["beatmapset_id"] : 0;

			$today = !($req["time"] < time()-86400);
			$beatmaps = $GLOBALS["db"]->fetchAll("SELECT song_name, beatmap_id, ranked, difficulty_std, difficulty_taiko, difficulty_ctb, difficulty_mania FROM beatmaps WHERE beatmapset_id = ? LIMIT 15", [$bsid]);
			$diffs = "";
			$allUnranked = true;
			$forceParam = "1";
			$modes = [];
			foreach ($beatmaps as $beatmap) {
				$icon = ($beatmap["ranked"] >= 2) ? "check" : "times";
				if($beatmap["ranked"] == 5) 
					$icon = "heart";
				$name = htmlspecialchars("$beatmap[song_name] ($beatmap[beatmap_id])");
				$diffs .= "<a href='#' data-toggle='popover' data-placement='bottom' data-content=\"$name\" data-trigger='hover'>";
				$diffs .= "<i class='fa fa-$icon'></i>";
				$diffs .= "</a>";
				if ($beatmap["difficulty_std"] > 0 && !in_array("std", $modes)) {
					$modes[] = "std";
				} else if ($beatmap["difficulty_std"] == 0) {
					if ($beatmap["difficulty_taiko"] > 0 && !in_array("taiko", $modes)) {
						$modes[] = "taiko";
					} else if ($beatmap["difficulty_ctb"] > 0 && !in_array("ctb", $modes)) {
						$modes[] = "ctb";
					} else if ($beatmap["difficulty_mania"] > 0 && !in_array("mania", $modes)) {
						$modes[] = "mania";
					}
				}

				if ($beatmap["ranked"] >= 2) {
					$allUnranked = false;
					$forceParam = "0";
				}
			}

			$modes = implode(", ", $modes);

			if (count($beatmaps) >= 15) {
				$diffs .= "...";
				$modes .= "...";
			}

			if ($req["blacklisted"] == 1) {
				$rowClass = "danger";
			} else if ($allUnranked) {
				$rowClass = $today ? "success" : "default";
			} else {
				$rowClass = "default";
			}

			/*if (($bsid & 1073741824) > 0) {
				$host = "osu!mp";
			} else if (($bsid & 536870912) > 0) {
				$host = "ripple";
			} else {
				$host = "osu!";
			}*/

			echo "<tr class='$rowClass'>
				<td><a href='https://osu.gatari.pw/s/$bsid' target='_blank'>$req[type]/$req[bid]</a></td>
				<td>$song</td>
				<td>
					$diffs
				</td>
				<td>$modes</td>
				<td>$req[username]</td>
				<td>".timeDifference(time(), $req["time"])."</td>
				<td>
					<p class='text-center'>
						<a title='Edit ranked status' class='btn btn-xs btn-primary' href='index.php?p=124&bsid=$bsid&force=".$forceParam."'><span class='glyphicon glyphicon-pencil'></span></a>
						<a title='Toggle blacklist' class='btn btn-xs btn-danger' href='submit.php?action=blacklistRankRequest&id=$req[id]'><span class='glyphicon glyphicon-flag'></span></a>
					</p>
				</td>
			</tr>";
		}
		echo '</tbody>';
		echo '</table>';
		// Template end
		echo '</div></div></div>';
	}

	public static function AdminPrivilegesGroupsMain() {
		// Get data
		$groups = $GLOBALS['db']->fetchAll('SELECT * FROM privileges_groups ORDER BY id ASC');
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span align="center"><h2><i class="fa fa-group"></i>	Privilege Groups</h2></span>';
		// Main page content here
		echo '<div align="center">';
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-left"><i class="fa fa-group"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Privileges</th><th class="text-center">Action</th></tr>
		</thead>
		<tbody>';
		foreach ($groups as $group) {
			echo "<tr>
					<td style='text-align: center;'>$group[id]</td>
					<td style='text-align: center;'>$group[name]</td>
					<td style='text-align: center;'>$group[privileges]</td>
					<td style='text-align: center;'>
						<div class='btn-group'>
							<a href='index.php?p=119&id=$group[id]' title='Edit' class='btn btn-xs btn-primary'><span class='glyphicon glyphicon-pencil'></span></a>
							<a href='index.php?p=119&h=$group[id]' title='Inherit' class='btn btn-xs btn-warning'><span class='glyphicon glyphicon-copy'></span></a>
							<a href='index.php?p=120&id=$group[id]' title='View users in this group' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-search'></span></a>
						</div>
					</td>
				</tr>";
		}
		echo '</tbody>
		</table>';

		echo '<a href="/index.php?p=119" type="button" class="btn btn-primary">New group</a>';

		echo '</div>';
		// Template end
		echo '</div></div></div>';
	}


	public static function AdminEditPrivilegesGroups() {
		try {
			// Check if id is set, otherwise set it to 0 (new badge)
			if (!isset($_GET['id']) && !isset($_GET["h"])) {
				$_GET['id'] = 0;
			}
			// Check if we are editing, creating or inheriting a new group
			if (isset($_GET["h"])) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', [$_GET['h']]);
				$privilegeGroupData["id"] = 0;
				$privilegeGroupData["name"] .= " (child)";
			} else if ($_GET["id"] > 0) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', $_GET['id']);
			} else {
				$privilegeGroupData = ['id' => 0, 'name' => 'New Privilege Group', 'privileges' => 0, 'color' => 'default'];
			}
			// Check if this group exists
			if (!$privilegeGroupData) {
				throw new Exception("That privilege group doesn't exists");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-group"></i>	Privilege Group</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="savePrivilegeGroup" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><input type="number" name="id" class="form-control-black" value="'.$privilegeGroupData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><input type="text" name="n" class="form-control-black" value="'.$privilegeGroupData['name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Privileges</td>
			<td>';

			$refl = new ReflectionClass("Privileges");
			$privilegesList = $refl->getConstants();
			foreach ($privilegesList as $i => $v) {
				if ($v <= 0)
					continue;
				$c = (($privilegeGroupData["privileges"] & $v) > 0) ? "checked" : "";
				echo '<label class="colucci"><input name="privileges" value="'.$v.'" type="checkbox" onclick="updatePrivileges();" '.$c.'>	'.$i.' ('.$v.')</label><br>';
			}
			echo '</td></tr>';

			echo '<tr>
			<td>Privileges number</td>
			<td><input class="form-control-black" id="privileges-value" name="priv" value="'.$privilegeGroupData["privileges"].'"></td>
			</tr>';

			// Selected stuff
			$sel = ["","","","","",""];
			switch($privilegeGroupData["color"]) {
				case "default": $sel[0] = "selected"; break;
				case "success": $sel[1] = "selected"; break;
				case "warning": $sel[2] = "selected"; break;
				case "danger": $sel[3] = "selected"; break;
				case "primary": $sel[4] = "selected"; break;
				case "info": $sel[5] = "selected"; break;
			}

			echo '<tr>
			<td>Color<br><i>(used in RAP users listing page)</i></td>
			<td>
			<select name="c" class="selectpicker" data-width="100%">
				<option value="default" '.$sel[0].'>Gray</option>
				<option value="success" '.$sel[1].'>Green</option>
				<option value="warning" '.$sel[2].'>Yellow</option>
				<option value="danger" '.$sel[3].'>Red</option>
				<option value="primary" '.$sel[4].'>Blue</option>
				<option value="info" '.$sel[5].'>Light Blue</option>
			</select>
			</td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=119&e='.$e->getMessage());
		}
	}



	public static function AdminShowUsersInPrivilegeGroup() {
		// Exist check
		try {
			if (!isset($_GET["id"])) {
				throw new Exception("That group doesn't exist");
			}

			// Get data
			$groupData = $GLOBALS["db"]->fetch("SELECT * FROM privileges_groups WHERE id = ?", [$_GET["id"]]);
			if (!$groupData) {
				throw new Exception("That group doesn't exist");
			}
			$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE privileges = ? OR privileges = ? | '.Privileges::UserDonor, [$groupData["privileges"], $groupData["privileges"]]);
			// Print sidebar and template stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			// Header
			echo '<span align="center"><h2><i class="fa fa-search"></i>	Users in '.$groupData["name"].' group</h2></span>';
			// Main page content here
			echo '<div align="center">';
			echo '<table class="table table-striped table-hover table-75-center">
			<thead>
			<tr><th class="text-left"><i class="fa fa-group"></i>	ID</th><th class="text-center">Username</th></tr>
			</thead>
			<tbody>';
			foreach ($users as $user) {
				echo "<tr>
						<td style='text-align: center;'>$user[id]</td>
						<td style='text-align: center;'><a href='/u/$user[id]'>$user[username]</a></td>
					</tr>";
			}
			echo '</tbody>
			</table>';

			echo '</div>';
			// Template end
			echo '</div></div></div>';
		} catch(Exception $e) {
			redirect("/index.php?p=118?e=".$e->getMessage());
		}
	}


	public static function RestrictedAlert() {
		if (!checkLoggedIn()) {
			return;
		}

		if (!hasPrivilege(Privileges::UserPublic)) {
			echo '<div class="alert alert-danger" role="alert">
					<p align="center"><i class="fa fa-exclamation-triangle"></i>	<b>Ваш аккаунт в ограниченом режиме!</b> Из-за нарушения правил сервера.<br>Вы не можете взаимодействовать с остальными игроками и вашь профиль видень лишь вам.<br><b> Получить доп. инфорамация по поводу бана и написать аппил можно тут: <a href="https://vk.com/im?sel=-139469474">https://vk.com/im?sel=-139469474</a>.</p>
				  </div>';
		}
	}

	/*
	 * AdminGiveDonor
	 * Prints the admin give donor page
	*/
	public static function AdminGiveDonor() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-money"></i>	Give donor</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]])["username"];
			if (!$username) {
				throw new Exception("Invalid user");
			}
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="edit-user-badges" action="submit.php" method="POST"><input name="action" value="giveDonor" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control-black" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control-black" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input name="m" type="number" class="form-control-black" placeholder="Months" required></input>
			</td>
			</tr>';
			echo '<tr>
			<td>Operation type</td>
			<td>
			<select name="type" class="selectpicker" data-width="100%">
				<option value=0 selected>Add months</option>
				<option value=1>Replace months</option>
			</select></td>
			</tr>';

						
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Give donor</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}


	/*
	 * AdminRollback
	 * Prints the admin rollback page
	*/
	public static function AdminRollback() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-fast-backward"></i>	Rollback account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-rollback" action="submit.php" method="POST"><input name="action" value="rollback" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control-black" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control-black" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input type="number" name="length" class="form-control-black" style="width: 40%; display: inline;">
			<div style="width: 5%; display: inline-block;"></div>
			<select name="period" class="selectpicker" data-width="53%">
				<option value="d">Days</option>
				<option value="w">Weeks</option>
				<option value="m">Months</option>
				<option value="y">Years</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-rollback" class="btn btn-primary">Rollback account</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}



	/*
	 * AdminWipe
	 * Prints the admin wipe page
	*/
	public static function AdminWipe() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-eraser"></i>	Wipe account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-wipe" action="submit.php" method="POST"><input name="action" value="wipeAccount" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control-black" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control-black" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Gamemode</td>
			<td>
			<select name="gm" class="selectpicker" data-width="100%">
				<option value="-1">All</option>
				<option value="0">Standard</option>
				<option value="1">Taiko</option>
				<option value="2">Catch the beat</option>
				<option value="3">Mania</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-wipe" class="btn btn-primary">Wipe account</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}



	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	*/
	public static function AdminRankBeatmap() {
		try {
			// Check if id is set
			if (!isset($_GET['bsid']) || empty($_GET['bsid'])) {
				throw new Exception('Invalid beatmap set id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><h2><i class="fa fa-music"></i>	Rank beatmap</h2></p>';

			echo '<br><br>';

			echo '<div id="main-content">
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Loading beatmap data from osu!api...</h3>
				<h5>This might take a while</h5>
			</div>';
			echo '</div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	*/
	public static function AdminRankBeatmapManually() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-level-up"></i>	Rank beatmap manually</h2></p>';

		echo '<br>';

		echo '
		<div id="narrow-content">
			<form action="submit.php" method="POST">
				<input name="action" value="redirectRankBeatmap" hidden>
				<input name="id" type="text" class="form-control-black" placeholder="Beatmap(set) id" style="width: 40%; display: inline;">
				<div style="width: 1%; display: inline-block;"></div>
				<select name="type" class="selectpicker bs-select-hidden" data-width="25%">
					<option value="bid" selected="">Beatmap ID</option>
					<option value="bsid">Beatmap Set ID</option>
				</select>
				<hr>
				<button type="submit" class="btn btn-primary">Edit ranked status</button>
			</form><br>

		</div>';

		echo '</div>';
		echo '</div></div></div>';
	}
	
	

	public static function AdminCharts() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		self::MaintenanceStuff();
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-key"></i>	Chart Beatmaps</h2></p>';

		echo '<br>';
		
		$reports = $GLOBALS["db"]->fetchAll("SELECT beatmaps.song_name, beatmap_md5, id FROM chart_beatmaps LEFT JOIN beatmaps ON chart_beatmaps.beatmap_md5 = beatmaps.beatmap_md5 ORDER BY id DESC LIMIT 50;");
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-key"></i>	ID</th><th class="text-center">Song name</th><th>md5</th><th>Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($reports as $report) {
			echo '<tr class="' . $rowClass . '">
			<td><p class="text-center">'.$report['id'].'</p></td>
			<td><p class="text-center">' . $report["song_name"] .'</td> <td><p class="text-center">' . $report["beatmap_md5"] ."</td>"  .'  
			<td><p class="text-center"> 
			<a title="Remove Chart" class="btn btn-xs btn-danger" '.'onclick="'."sure('submit.php?action=removeChart&id=".$report['id']."')".'"<span class="glyphicon glyphicon-trash"><span class="glyphicon glyphicon-trash"></span></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
        echo '<div class="text-center"><a href="/index.php?p=129"><button form="edit-badge-form" class="btn btn-primary">Add new beatmap</button></a><br></div>';
		echo '</div>';
		echo '</div></div></div>';
	}

		public static function AdminAddChart() {
		try {
			// Check if id is set
		/*	if (!isset($_GET['id'])) {
				throw new Exception('Invalid badge id');
			}
			*/
            // Check if we are editing or creating a new badge
			/*if ($_GET['id'] > 0) {
				$badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			} else {
				$badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
			}
			// Check if this doc page exists
			if (!$badgeData) {
				throw new Exception("That badge doesn't exist");
			}*/
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-key"></i>	Add Beta keys</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="addKey" hidden>';
			echo '<tr>
			<td>Keys count</td>
			<td><p class="text-center"><input type="number" name="count" class="form-control-black" value="1"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Add Key(s)</button></div>';
			echo '</div></div></div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}   

	public static function AdminBetaKeys() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		self::MaintenanceStuff();
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-key"></i>	Beta Keys</h2></p>';

		echo '<br>';
		
		$reports = $GLOBALS["db"]->fetchAll("SELECT * FROM beta_keys ORDER BY id DESC LIMIT 50;");
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-key"></i>	ID</th><th class="text-center">Key</th><th>Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($reports as $report) {
			echo '<tr class="' . $rowClass . '">
			<td><p class="text-center">'.$report['id'].'</p></td>
			<td><p class="text-center">' . $report["name"] ."</td>".'  
			<td><p class="text-center">
			<a title="View/Edit report" class="btn btn-xs btn-danger" '.'onclick="'."sure('submit.php?action=removeKey&id=".$report['id']."')".'"<span class="glyphicon glyphicon-trash"><span class="glyphicon glyphicon-trash"></span></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
        echo '<div class="text-center"><a href="/index.php?p=113"><button form="edit-badge-form" class="btn btn-primary">Add new keys</button></a><br></div>';
		echo '</div>';
		echo '</div></div></div>';
	}
        
        
	public static function AdminViewReports() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
		self::MaintenanceStuff();
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-flag"></i>	Reports</h2></p>';

		echo '<br>';
		
		$reports = $GLOBALS["db"]->fetchAll("SELECT * FROM reports ORDER BY id DESC LIMIT 50;");
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-flag"></i>	ID</th><th class="text-center">From</th><th class="text-center">Target</th><th class="text-l">Reason</th><th class="text-center">When</th><th class="text-center">Assignee</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($reports as $report) {
			if ($report['assigned'] == 0) {
				$rowClass = "danger";
				$assignee = "No one";
			} else if ($report['assigned'] == -1) {
				$rowClass = "success";
				$assignee = "Solved";
			} else if ($report["assigned"] == -2) {
				$rowClass = "warning";
				$assignee = "Useless";
			} else {
				$rowClass = "";
				$assignee = '<img class="circle" style="width: 30px; height: 30px; margin-top: 0px;" src="'.$URL["avatar"]."/" . $report['assigned'] . '"> ' . getUserUsername($report['assigned']);
			}
			echo '<tr class="' . $rowClass . '">
			<td><p class="text-center">'.$report['id'].'</p></td>
			<td><p class="text-center"><a href="/u/' . $report["from_uid"] . '" target="_blank">'.getUserUsername($report['from_uid']).'</a></p></td>
			<td><p class="text-center"><b><a href="/u/' . $report["to_uid"] . '" target="_blank">'.getUserUsername($report['to_uid']).'</a></b></p></td>
			<td><p>'.substr($report['reason'], 0, 40).'</p></td>
			<td><p>'.timeDifference(time(), $report['time']).'</p></td>
			<td><p class="text-center">' . $assignee . '</p></td>
			<td><p class="text-center">
			<a title="View/Edit report" class="btn btn-xs btn-primary" href="/index.php?p=127&id='.$report['id'].'"><span class="glyphicon glyphicon-zoom-in"></span></a>
			<!-- <a title="Set as solved" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-ok"></span></a>-->
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '</div>';
		echo '</div></div></div>';
	}

	public static function AdminViewReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$report = $GLOBALS["db"]->fetch("SELECT * FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$report) {
				throw new Exception("Invalid report id");
			}
			$statusRowClass = "";
			if ($report["assigned"] == 0) {
				$status = "Unassigned";
			} else if ($report["assigned"] == -1) {
				$status = "Solved";
				$statusRowClass = "info";
			} else if ($report["assigned"] == -2) {
				$status = "Useless";
				$statusRowClass = "warning";
			} else {
				$status = "Assigned to " . getUserUsername($report["assigned"]);
				if ($report["assigned"] == $_SESSION["userid"]) {
					$statusRowClass = "success";
				}
			}
			$reportedCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE to_uid = ? AND time >= ? LIMIT 1", [$report["to_uid"], time() - 86400 * 30])["count"];
			$uselessCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE from_uid = ? AND assigned = -2 AND time >= ? LIMIT 1", [$report["from_uid"], time() - 86400 * 30])["count"];

			$takeButtonText = $report["assigned"] == 0 || $report["assigned"] != $_SESSION["userid"] ? "Take" : "Leave";
			$takeButtonDisabled = $report["assigned"] < 0  ? "disabled" : "";

			$solvedButtonText = $report["assigned"] != -1 ? "Mark as solved" : "Mark as unsolved";
			$solvedButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -1 ? "disabled" : "";

			$uselessButtonText = $report["assigned"] != -2 ? "Mark as useless" : "Mark as useful";
			$uselessButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -2 ? "disabled" : "";

			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper"><div class="col-lg-12 text-center"><div id="content"><br>';
			self::MaintenanceStuff();
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			echo '<p align="center">
				<h2><i class="fa fa-flag"></i>	View report</h2>
				<h4><a href="/index.php?p=126"><i class="fa fa-chevron-left"></i>&nbsp;&nbsp;Back</a></h4>
			</p>';

			echo '<br>';

			echo '
			<div id="narrow-content">
				<table class="table table-striped table-hover table-100-center"><tbody>
					<tr>
						<td><b>From</b></td>
						<td>' . getUserUsername($report["from_uid"]) . '</td>
					</tr>
					<tr>
						<td><b>Reported user</b></td>
						<td><b>' . getUserUsername($report["to_uid"]) . '</b></td>
					</tr>
					<tr>
						<td><b>Reason</b></td>
						<td><b>' . $report["reason"] . '</b></td>
					</tr>
					<tr>
						<td><b>When</b></td>
						<td>' . timeDifference(time(), $report["time"]) . '</td>
					</tr>
					<tr>
						<td><b>Chatlog*</b></td>
						<td>' . str_replace("\n", "<br>", $report["chatlog"]) .  '</td>
					</tr>
					<tr class="' . $statusRowClass . '">
						<td><b>Status</b></td>
						<td>' . $status . '</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["to_uid"]) . '</b> has been reported <b>' . $reportedCount . '</b> times in the last month</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["from_uid"]) . '</b> has sent <b>' . $uselessCount . '</b> useless reports in the last month</td>
					</tr>
				</table>

				<ul class="list-group">
					<li class="list-group-item list-group-item-warning">Ticket actions</li>
					<li class="list-group-item">
						<a class="btn btn-warning ' . $takeButtonDisabled . '" href="submit.php?action=takeReport&id=' . $report["id"] . '"><i class="fa fa-bolt"></i> ' . $takeButtonText .' ticket</a>
						<a class="btn btn-success ' . $solvedButtonDisabled . '" href="submit.php?action=solveUnsolveReport&id=' . $report["id"] . '"><i class="fa fa-check"></i> ' . $solvedButtonText . '</a>
						<a class="btn btn-danger ' . $uselessButtonDisabled . '" href="submit.php?action=uselessUsefulReport&id=' . $report["id"] . '"><i class="fa fa-trash"></i> ' . $uselessButtonText . '</a>
					</li>
				</ul>

				<ul class="list-group">
					<li class="list-group-item list-group-item-danger">Quick actions</li>
					<li class="list-group-item">
						<a class="btn btn-primary" href="/index.php?p=103&id=' . $report["to_uid"] . '"><i class="fa fa-expand"></i> View reported user in RAP</a>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["to_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence reported user</div>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["from_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence source user</div>
						';
						$restrictedDisabled = isRestricted($report["to_uid"]) ? "disabled" : "";
						echo '<a class="btn btn-danger ' . $restrictedDisabled . '" onclick="sure(\'/submit.php?action=restrictUnrestrictUser&id=' . $report["to_uid"] . '&resend=1\')"><i class="fa fa-times"></i> Restrict reported user</a>';
					echo '</li>
				</ul>

				<i><b>*</b> Latest 10 public messages sent from reported user before getting reported, trimmed to 50 characters.</i>

			</div>';

			echo '</div>';
			echo '</div></div></div>';
			// Silence user modal
			echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
			<div class="modal-dialog">
			<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Silence user</h4>
			</div>
			<div class="modal-body">
			<p>
			<form id="silence-user-form" action="submit.php" method="POST">
			<input name="action" value="silenceUser" hidden>
			<input name="resend" value="1" hidden>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
			<input type="text" name="u" class="form-control-black" placeholder="Username" aria-describedby="basic-addon1" required>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
			<input type="number" name="c" class="form-control-black" placeholder="How long" aria-describedby="basic-addon1" required>
			<select name="un" class="selectpicker" data-width="30%">
				<option value="1">Seconds</option>
				<option value="60">Minutes</option>
				<option value="3600">Hours</option>
				<option value="86400">Days</option>
			</select>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
			<input type="text" name="r" class="form-control-black" placeholder="Reason" aria-describedby="basic-addon1">
			</div>

			<p style="line-height: 15px"></p>

			During the silence period, user\'s client will be locked. <b>Max silence time is 7 days.</b> Set length to 0 to remove the silence.

			</form>
			</p>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
			</div>
			</div>
			</div>
			</div>';
		} catch (Exception $e) {
			redirect("/index.php?p=126&e=" . $e->getMessage());
		}
		
	}
}

// LISCIAMI LE MELE SUDICIO
class Fava extends Exception {
	 public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
