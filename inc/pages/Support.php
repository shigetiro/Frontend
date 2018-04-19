<?php

class Support {
	const PageID = 34;
	const URL = 'support';
	const Title = 'Vipsu - Support us';

	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = [];

	public function P() {
		$maxDonor = 24;
		startSessionIfNotStarted();
		P::GlobalAlert();
		P::MaintenanceStuff();
		$isSupporter = hasPrivilege(Privileges::UserDonor);
		if ($isSupporter) {
			$expire = $GLOBALS["db"]->fetch("SELECT donor_expire FROM users WHERE id = ?", [$_SESSION["userid"]]);
			if ($expire) {
				$expire = current($expire);
				if ($expire > time()) {
					$expireString = trim(timeDifference(time(), $expire, false));
				} else {
					$expireString = 'less than one hour';
				}
			} else {
				$expire = 0;
			}
		}
		$count = current($GLOBALS["db"]->fetch("SELECT count FROM orders_count"));
		if($count < 175)
			$percText = round($count / 1.75);
		if ($percText < 10) {
				$percBar = 10;
			} else {
				$percBar = bound($percText, 1, 100);
			}
		echo '
		<link href="https://fonts.googleapis.com/css?family=Exo+2" rel="stylesheet">
		<div id="content" style = "font-family: \'Exo 2\', sans-serif;">
			<div align="center">
				<div class="row">
					<h1 class="support-color"><i class="fa fa-heart animated infinite pulse"></i>	Support Vipsu</h1>
					<br>
			<div class="progress">
			</div>
				</div>
				<div class="row"><hr>';
					if ($isSupporter) {
						echo '
						<h2><i class="fa fa-smile-o"></i>	You are a Donator, thank you!</h2>
						<b>You are still a Donator '.$expireString.'!</b><br>
						Thanks to you the server is still alive! :3
						';
					} else {
						echo '
						<h2><i class="fa fa-frown-o"></i>	You are not a Donator of Verge :(</h2>
						<b>Perhaps, it is you who need it.</b><br>
						Follow the instructions below to become a Donator on Verge.
						';
					}
				echo '</div>
				<hr>
				<h2><i class="fa fa-gift"></i>	But what will I get?</h2>
				<div class="row grid-divider" align="left">
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-paint-brush"></i>	Unique color in chat!</center></h3>
							<p>You will immediately stand out in the chat with a yellow nickel! <center> <img src= "http://i.imgur.com/OGYGFgf.png"></center></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-certificate"></i>	Profile Badge </center></h3>
							<p>Even when entering the profile, everyone will see what your connections are<center> <img src="https://i.imgur.com/jrE6OIY.png"> </center></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-pencil"></i>	Any badge to choose from</center></h3>
							<p>During the membership of the club Donator you can get <b> Any </b>a badge besides the others with a fairly convenient editor!</p>
						</div>
					</div>
				</div>
				<hr>
				<div class="row grid-divider" align="left">
				<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-envelope"></i>	Access to the admin channel</center></h3>
							<p>You will be able to communicate with other Donators and administrators in the special IRC channel of the Donator club, which also writes admin teams. <center><img src="http://firedigger.s-ul.eu/cB9vCi6S" style="width:100%"></center></p>
						</div>
					</div>
					<div class="col-sm-4">
						
						<div class="col-padding">
							<h3><center><i class="fa fa-quote-left"></i>	Change the nickname</center></h3>
							<p>You can change your nickname one time!</b></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-flag"></i>	Change of flag</center></h3>
							<p>You can change the one-time flag to which you want.</p>
							<center> <img src="http://i.imgur.com/lwGfOo5.png"></center>
						</div>
					</div>
				</div>
				<hr>
				<div class="row grid-divider" align="left">
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><center><i class="fa fa-gamepad"></i>	View people who added you as a friend</center></h3>
							<p> <center><img src="https://i.imgur.com/uYWFBDB.png" style="width:100%"></center></p>
						</div>
					</div>
					<div class="col-sm-4">
						
						<div class="col-padding">
							<h3><center><i class="fa fa-child"></i>	The color role in the discord of Verge</center></h3>
							<p> <center><img src="https://i.imgur.com/4dPMq1y.png" style="width:100%"></center></p>
						</div>
					</div>
					<div class="col-sm-4">
						
						<div class="col-padding">
							<h3><center><i class="fa fa-child"></i>	ezpp! on the map page</center></h3>
							<p> <center><img src="https://i.imgur.com/cMjDnDs.png" style="width:100%"></center></p>
						</div>
					</div>
				</div>

						<div class="col-padding">
							<h3><center><i class="fa fa-gamepad"></i>	Complete account clean-up if desired</center></h3>
							<p> <center>You can ask the administration to clear the account and start re-typing</center></p>
						</div>
					</div>
					</div>
				<div class="row">
					<hr>
					<h2><i class="fa fa-credit-card"></i>	How can I become a Donator of Vipsu?</h2>
					
					<p class="half">
						<div class="slider" style="width: 100%;" data-slider-min="1" data-slider-max="'.$maxDonor.'" data-slider-value="1" data-slider-tooltip="hide"></div><br>
						<span id="supporter-prices"></span>
						<br>
						<div style="float: none;" class="col-sm-6">
						';
							echo '<h4><small> Vipsu username</small> </h4>';
							if(isset($_SESSION["username"]))
							echo'<input type="text" class="form-control"  name="os1" id="gatariusername" onchange="onSlide();" maxlength="200" value="'.$_SESSION["username"].'">';
						echo'</div>
					</p>
					<hr>
				</div> 
				<div class="row" align="center">
				<div class="col-sm-6">
						<div class="col-padding">
							<h3><i class="fa fa-rub"></i>   Yandex.Money</h3></h3>
						
								
								';

				

								echo '<input type="hidden" name="on1" value="Vipsu user to give donor">
								
                                <form method="POST" action="https://money.yandex.ru/quickpay/confirm.xml"> 
    <input type="hidden" name="receiver" value="410011735004776"> 
    <input type="hidden" name="formcomment" value="Vipsu Supporter"> 
    <input type="hidden" name="short-dest" value="Vipsu Supporter"> 
    <input type="hidden" name="quickpay-form" value="donate"> 
    <input type="hidden" id="formcomment" name="targets" value="Vipsu Supporter">
    <input type="hidden" id="labelya" name="label" value="My Angel Vaxei - 1">
    <input type="hidden" id="valuesum" name="sum" value="150" data-type="number"> 
    <input type="hidden" name="need-fio" value="false"> 
    <input type="hidden" name="need-email" value="false"> 
    <input type="hidden" name="need-phone" value="false"> 
    <input type="hidden" name="need-address" value="false"> 
    <input type="hidden" checked name="paymentType" value="PC">								<button type="submit" class="btn btn-warning" name="submit"><i class="fa fa-heart"></i>	Pay now</button>
							
							</form>
							</div></div> 
							
							<div class="col-sm-6">
						<div class="col-padding">
							<h3><i class="fa fa-rub"></i>   Free-Kassa(QIWI, etc.)</h3>
						<a target="_blank" id="fklink" href="" class="btn btn-info"><i class="fa fa-heart"></i> Pay now</a>
						</div> </div></div>
			</div>


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
