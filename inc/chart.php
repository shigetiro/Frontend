<?php
require_once '../inc/functions.php';
$users = $GLOBALS['db']->fetchAll("SELECT pp_std,pp_mania,id FROM `users_stats` ORDER BY id ASC");
foreach ($users as $key => $user) {
	$u = $user['id'];
	if ($user["pp_std"] > 0 || $user["pp_mania"] > 0)
		$GLOBALS["db"]->execute("INSERT INTO `user_charts` (`userid`, `pp_std`,`pp_mania`, `time`) VALUES ( ?, ?, ?, ? )",[$u, $user["pp_std"], $user["pp_mania"], time()]);
	
}
?>