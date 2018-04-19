<?php

class TwoFASetup {
	const PageID = 30;
	const URL = '2faSetup';
	const Title = 'Verge - 2FA Setup';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = [];

	public function P() {
		P::GlobalAlert();
		P::MaintenanceStuff();
		startSessionIfNotStarted();
		$_SESSION["2fa"] = is2FAEnabled($_SESSION["userid"], true);
		if (!$_SESSION["2fa"]) {
			cleanExpiredConfirmationToken();
			$token = getConfirmationToken($_SESSION["userid"]);
		}
		echo '
		<div id="narrow-content">
			<div align="center">
				<h1><i class="fa fa-ticket"></i> Two-Factor Auth Setup</h1>
				<br>
				
				We highly recommend setting up 2FA to increase your account security.<br><br>';

				if ($_SESSION["2fa"]) {
					echo '<div class="alert alert-success" role="alert"><i class="fa fa-check-circle"></i>	<b>VK 2FA is enabled on your account</b></div><br>
					<a onclick="sure(\'/submit.php?action=disable2FA\')" type="button" class="btn btn-primary"><span class="fa-stack"><i class="fa fa-paper-plane fa-stack-1x"></i><i class="fa fa-ban fa-stack-2x text-danger"></i></span>	Disable VK 2FA</a><br>';
				} else {
					$vk = current($GLOBALS["db"]->fetch("SELECT vk FROM users WHERE id = ?",[$_SESSION["userid"]]));
					
					echo '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>VK 2FA is not enabled on your account</b></div>
					<br>
					';
					if(!$vk)echo'
					<b>To enable 2FA, firstly u need to link your VK account to Verge.</b><br>
					<a href="https://verge.moe/index.php?p=6" type="button" class="btn btn-primary">	Link VK Account</a>
					<hr>';

					echo ($vk ?'
					<b>To enable 2FA, firstly you need to ' : '<b>Then you need to').' start chatting with <a href="https:">VK Verge Group</a> on VK and send this message</b><br><br>
					<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Show</button>
							</div>
							<div class="panel-collapse collapse">
									<div class="panel-body">!2fa '.$token.'</div>
							</div>
						</div>
					</div>';
				}
			echo '</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d['error']);
			redirect("index.php?p=29");
		} else {
			// No errors, run log new IP address
			logIP($_SESSION["userid"]);
			redirect("index.php?p=1");
		}
	}

	public function DoGetData() {
		try {
			// Get tokenID
			$token = $GLOBALS["db"]->fetch("SELECT * FROM 2fa WHERE userid = ? AND ip = ? AND token = ?", [$_SESSION["userid"], getIp(), $_POST["token"]]);
			// Make sure the token exists
			if (!$token) {
				throw new Exception("Invalid 2FA code.");
			}
			// Make sure the token is not expired
			if ($token["expire"] < time()) {
				throw new Exception("Your 2FA token is expired. Please enter the new code you've just received.");
			}
			// Everything seems fine, delete 2FA token to allow this session
			$GLOBALS["db"]->execute("DELETE FROM 2fa WHERE id = ?", [$token["id"]]);
		} catch (Exception $e) {
			$ret["error"] = $e->getMessage();
		}

		return $ret;
	}
}
