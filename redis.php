<?php
$df = dirname(__FILE__);
require_once $df.'/vendor/autoload.php';


function redisConnect() {
	if (!isset($_GLOBALS["redis"])) {
		$GLOBALS["redis"] = new Predis\Client();
	}
}

if(isset($_POST["method"]) && isset($_POST["params"])){
    redisConnect();
    $GLOBALS["redis"]->publish($_POST["method"], $_POST["params"]);
    echo("ok");
}else
    echo("hui");

?>
