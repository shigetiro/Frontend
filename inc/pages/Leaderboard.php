<?php

class Leaderboard {
	const PageID = 13;
	const URL = 'leaderboard';
	const Title = 'Vipsu - Leaderboard';
	const LoggedIn = true;

	public function P() {
		P::GlobalAlert();
		P::MaintenanceStuff();

		global $ScoresConfig;
		echo "<h2>Leaderboard</h2>";
		// Leaderboard names (to bold the selected mode)
		$modesText = [0 => 'osu!standard', 1 => 'Taiko', 2 => 'Catch the Beat', 3 => 'osu!mania'];
		// Set $m value to 0 if not set
		if (!isset($_GET['m']) || empty($_GET['m']) || !is_numeric($_GET['m'])) {
			$m = 0;
		} else {
			$m = $_GET['m'];
		}
		// Get stats for selected mode
		$modeForDB = getPlaymodeText($m);
		$modeReadable = getPlaymodeText($m, true);
		// Make sure that $m is a valid mode integer
		$m = ($m < 0 || $m > 3 ? 0 : $m);
		// Bold the selected mode
		$modesText[$m] = '<b>'.$modesText[$m].'</b>';
		// PP or Score ranking
		if  ($ScoresConfig["enablePP"] && ($m == 1 || $m == 0 || $m == 3))
			$scoringName = "PP";
		else
			$scoringName = "Score";
		echo '<a href="?m=0">'.$modesText[0].'</a> | <a href="?m=1">'.$modesText[1].'</a> | <a href="?m=2">'.$modesText[2].'</a> | <a href="?m=3">'.$modesText[3].'</a>';

		// paginate: generate db offset
		$p = (isset($_GET["page"]) && is_numeric($_GET["page"]) ? (int)$_GET["page"] : 1);
		if ($p < 1)
			$p = 1;
		$offset = ($p-1) * 50;

		// generate table name
		$tb = 'leaderboard_'.$modeForDB;
		// Get all user data and order them by score
		$leaderboard = $GLOBALS['db']->fetchAll("
SELECT
	$tb.*,
	users.username, users_stats.country,
	users_stats.ranked_score_" . $modeForDB . ", users_stats.pp_" . $modeForDB . ",
	users_stats.avg_accuracy_" . $modeForDB . ", users_stats.playcount_" . $modeForDB . ",
	users_stats.level_" . $modeForDB . ", users_stats.id
FROM $tb
INNER JOIN users ON users.id=$tb.user
INNER JOIN users_stats ON users_stats.id=$tb.user
WHERE users.privileges & 1 > 0
ORDER BY $tb.v DESC
LIMIT $offset, 50;");

		if (count($leaderboard) == 0) {
			echo "<br><br><br><b>You have reached the end of the world.</b>";
			echo '<br><br><a href="index.php?p=13&m='.$m.'&page='.($p-1).'"><i class="fa big-arrow fa-arrow-circle-left" aria-hidden="true"></i></a>';
			return;
		}

		echo '<br><br>' . ($p > 1 ? '<a href="index.php?p=13&m='.$m.'&page='.($p-1).'"><i class="fa big-arrow fa-arrow-circle-left" aria-hidden="true"></i></a>' : '') . '<a href="index.php?p=13&m='.$m.'&page='.($p+1).'"><i class="fa big-arrow fa-arrow-circle-right" aria-hidden="true"></i></a>';

		// Leaderboard
		echo '<table class="table table-striped table-hover leaderboard">
		<thead>
		<tr>
		<th>Rank</th>
		<th>Player</th>
		<th>' . $scoringName . '</th>
		<th>Accuracy</th>
		<th>Playcount</th>
		</tr>
		</thead>';
		echo '<tbody>';
		// Print table rows
		foreach ($leaderboard as $lbUser) {
			// Increment rank
			$offset++;
			// Style for top and noob players
			if ($offset <= 3) {
				// Yellow bg and trophy for top 3 players
				$tc = 'warning';
				$rankSymbol = '<i class="fa fa-trophy"></i> ';
			} else {
				// Standard table style for everyone else
				$tc = 'default';
				$rankSymbol = '#';
			}
			// Show PP or score
			if ($ScoresConfig["enablePP"] && ($m == 1 || $m == 0 || $m == 3))
				$score = number_format($lbUser['pp_'.$modeForDB]) . ' pp';
			else
				$score = number_format($lbUser['ranked_score_'.$modeForDB]);
			// Draw table row for this user
			echo '<tr class="'.$tc.'">
			<td><b>'.$rankSymbol.$offset.'</b></td>';
			$country = $lbUser['country'];
			echo '<td><img src="./images/flags/'.$country.'.png"/  width="18px" style="margin-top: -2px;">	<a href="/u/'.$lbUser['id'].'&m='.$m.'">'.$lbUser['username'].'</a></td>
			<td>'.$score.'</td>
			<td>'.(is_numeric($lbUser['avg_accuracy_'.$modeForDB]) ? accuracy($lbUser['avg_accuracy_'.$modeForDB]) : '0.00').'%</td>
			<td>'.number_format($lbUser['playcount_'.$modeForDB]).'<i> (lvl.'.$lbUser['level_'.$modeForDB].')</i></td>
			</tr>';
		}
		// Close table
		echo '</tbody></table>';
		echo '' . ($p > 1 ? '<a href="?m='.$m.'&page='.($p-1).'"><i class="fa big-arrow fa-arrow-circle-left" aria-hidden="true"></i></a>' : '') . '<a href="?m='.$m.'&page='.($p+1).'"><i class="fa big-arrow fa-arrow-circle-right" aria-hidden="true"></i></a>';
	}

	public static function GetUserRank($u, $mode) {
		$query = $GLOBALS['db']->fetch("SELECT position FROM leaderboard_$mode WHERE user = ?;", [$u]);
		if ($query !== false) {
			$rank = (string) current($query);
		} else {
			$rank = 'Unknown';
		}

		return $rank;
	}


	// Used in potatoscores
	/*public static function BuildLeaderboard() {
		// Declare stuff that will be used later on.
		$modes = ['std', 'taiko', 'ctb', 'mania'];
		$data = ['std' => [], 'taiko' => [], 'ctb' => [], 'mania' => []];
		$allowedUsers = getAllowedUsers('id');
		// Get all user's stats
		$users = $GLOBALS['db']->fetchAll('SELECT id, ranked_score_std, ranked_score_taiko, ranked_score_ctb, ranked_score_mania FROM users_stats');
		// Put the data in the correct way into the array.
		foreach ($users as $user) {
			if (!$allowedUsers[$user['id']]) {
				continue;
			}
			foreach ($modes as $mode) {
				$data[$mode][] = ['user' => $user['id'], 'score' => $user['ranked_score_'.$mode]];
			}
		}
		// We're doing the sorting for every mode.
		foreach ($modes as $mode) {
			// Do the sorting
			usort($data[$mode], function ($a, $b) {
				if ($a['score'] == $b['score']) {
					return 0;
				}
				// We're doing ? 1 : -1 because we're doing in descending order.
				return ($a['score'] < $b['score']) ? 1 : -1;
			});
			// Remove all data from the table
			$GLOBALS['db']->execute("TRUNCATE TABLE leaderboard_$mode;");
			// And insert each user.
			foreach ($data[$mode] as $key => $val) {
				$GLOBALS['db']->execute("INSERT INTO leaderboard_$mode (position, user, v) VALUES (?, ?, ?)", [$key + 1, $val['user'], $val['score']]);
			}
		}
	}*/

	public static function Update($userID, $newScore, $mode) {
		// Who are we?
		$us = $GLOBALS['db']->fetch("SELECT * FROM leaderboard_$mode WHERE user = ?", [$userID]);
		$newplayer = false;
		if (!$us) {
			$newplayer = true;
		}
		// Find player who is right below our score
		$target = $GLOBALS['db']->fetch("SELECT * FROM leaderboard_$mode WHERE v <= ? ORDER BY position ASC LIMIT 1", [$newScore]);
		$plus = 0;
		if (!$target) {
			// Wow, this user completely sucks at this game.
			$target = $GLOBALS['db']->fetch("SELECT * FROM leaderboard_$mode ORDER BY position DESC LIMIT 1");
			$plus = 1;
		}
		// Set $newT
		if (!$target) {
			// Okay, nevermind. It's not this user to suck. It's just that no-one has ever entered the leaderboard thus far.
			// So, the player is now #1. Yay!
			$newT = 1;
		} else {
			// Otherwise, just give them the position of the target.
			$newT = $target['position'] + $plus;
		}
		// Make some place for the new "place holder".
		if ($newplayer) {
			$GLOBALS['db']->execute("UPDATE leaderboard_$mode SET position = position + 1 WHERE position >= ? ORDER BY position DESC", [$newT]);
		} else {
			$GLOBALS['db']->execute("DELETE FROM leaderboard_$mode WHERE user = ?", [$userID]);
			$GLOBALS['db']->execute("UPDATE leaderboard_$mode SET position = position + 1 WHERE position < ? AND position >= ? ORDER BY position DESC", [$us['position'], $newT]);
		}
		// Finally, insert the user back.
		$GLOBALS['db']->execute("INSERT INTO leaderboard_$mode (position, user, v) VALUES (?, ?, ?);", [$newT, $userID, $newScore]);
	}
}
