<?php

class TwoFA {
	const PageID = 29;
	const URL = '2fa';
	const Title = 'Gatari - 2FA';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = [];

	public function P() {
		P::GlobalAlert();
		check2FA($_SESSION["userid"]);
		$token = $GLOBALS["db"]->fetch("SELECT token FROM 2fa WHERE userid = ? AND ip = ? AND expire > ?", [$_SESSION["userid"], getIp(), time()]);
		echo '<div id="narrow-content">
			<div align="center">
				<h1><i class="fa fa-hand-paper-o"></i> You shall not pass!</h1>';
		if(isset($token) && !empty($token)){
			echo '
				You are logging in from a new IP address.<br>Send this 2Fa code to <a href="https://vk.com/im?sel=-139469474">VK Gatari group</a>
				<br>
				<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Show</button>
							</div>
							<div class="panel-collapse collapse">
									<div class="panel-body">!verify '.current($token).'</div>
							</div>
						</div>
					</div>
				';
		}
		else
			echo '<div class="alert alert-success" role="alert"><i class="fa fa-check-circle"></i>	<b>Verified Successful!</b></div>';
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
			// No errors, log new IP address
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
