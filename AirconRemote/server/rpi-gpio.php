<?php
// Air Conditioner Remote Script [Server]
// for WebIOPi (Raspberry Pi GPIO)
// (c) 2013 Akkiesoft.

require_once 'HTTP/Request2.php';
session_start();

$redirectTo	= 'rpi-gpio.php';
$webiopi_host   = 'localhost:8000';
$webiopi_user   = 'webiopi';
$webiopi_passwd = 'SET YOUR PASSPHRASE';
$webiopi_gpio   = '17';

function runGPIO() {
	global $webiopi_host, $webiopi_user, $webiopi_passwd, $webiopi_gpio;

	// TEKITOU
	$req = new HTTP_Request2('http://'.$webiopi_host.'/GPIO/'.$webiopi_gpio.'/value/1', HTTP_Request2::METHOD_POST);
	$req->setAuth($webiopi_user, $webiopi_passwd, HTTP_Request2::AUTH_BASIC);
	$response1 = $req->send()->getStatus();
	usleep(200000);
	$req = new HTTP_Request2('http://'.$webiopi_host.'/GPIO/'.$webiopi_gpio.'/value/0', HTTP_Request2::METHOD_POST);
	$req->setAuth($webiopi_user, $webiopi_passwd, HTTP_Request2::AUTH_BASIC);
	$response2 = $req->send()->getStatus();
	if ($response1 == $response2 && $response1 == '200') {
		return 'OK.';
	}
	return 'Failed.<br>Status: [LED on]' . $response1 . ' / [LED off]' . $response2;
}

function getTEMP() {
	global $webiopi_host, $webiopi_user, $webiopi_passwd, $webiopi_gpio;
	$req = new HTTP_Request2('http://'.$webiopi_host.'/room/temperature', HTTP_Request2::METHOD_GET);
	$req->setAuth($webiopi_user, $webiopi_passwd, HTTP_Request2::AUTH_BASIC);
	return $req->send()->getBody();	
}

/* for AT command. */
if (isset($argv[1]) && $argv[1] == 'at') {
	runGPIO();
	exit;
}


if (isset($_GET['tray']) || isset($_POST['open'])) {
	$result = runGPIO();
	if (isset($_GET['api']) && $_GET['api'] == 1) {
		print $result;
		exit();
	}
	$_SESSION['result'] = '<ul id="result"><li>Send ' . $result . '</li></ul>';
	header('Location:' . $redirectTo);
	exit();
}

/* Tray timer. */
if (isset($_GET['timer']) || isset($_POST['timerset'])) {
	if (isset($_GET['timer'])) {
		$time = intval($_GET['timer']);
	}
	else if (isset($_POST['timerset'])) {
		$time = intval($_POST['time']);
	}
	if ($time) {
		exec("echo '/usr/bin/php /var/www/index.php at' | /usr/bin/at now +" . $time . "minute");
		$result = exec('/usr/bin/atq', $ret2);
		$message = $time . '分後にボタンを押します。<br><small>詳細: ' . htmlspecialchars($result) . '</small>';
	} else {
		$result = 'Invalid parameter.';
		$message = '時間の指定が不正です。。';
	}
	if (isset($_GET['api']) && $_GET['api'] == 1) {
		print $result;
		exit();
	}
	$_SESSION['resultTimer'] = '<ul id="result"><li>' . $message . '</li></ul>';
	header('Location:' . $redirectTo);
	exit();
}

/* Temperature */
$temp = getTEMP();
$temp = $temp[0];
if (isset($_GET['temp'])) {
	if (isset($_GET['api']) && $_GET['api'] == 1) {
		print $temp;
		exit();
	}
}
$tempicon = "accept.png";
if ($temp < 18) { $tempicon = "delete.png"; }
if (17 < $temp) { $tempicon = "error.png";  }
if (21 < $temp) { $tempicon = "accept.png"; }
if (28 < $temp) { $tempicon = "error.png";  }
if (30 < $temp) { $tempicon = "delete.png"; }

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta name="viewport" content="width=320px,user-scalable=no">
	<title>エアコンのリモコン</title>
	<style type="text/css">
*	{ font-family:'メイリオ',sans-serif;padding:0;margin:0; }
html{ background:#ccc; }
body{ margin:0 auto;width:320px;background:#fff; }
h1	{
	padding		:5px 0;
	background	:#ddeeff;
	text-align	:center;
}
h2		{ margin	:0 0 10px; }
section { padding	:10px; }
input	{
	font-size	:16pt;
	height		:40px;
}
#result	{
	margin			:10px 0;
	border			:1px solid #22aa22;
	background		:#eeffef;
	list-style-image:url(accept.png);
}
#result li {
	margin-left:2em;
	line-height:200%;
}
#temp	{
	margin-left	:0.5em;
	font-size	:20pt;
}
#aircon	{ text-align:center; }
#aircon input { width:100%; }
	</style>
</head>
<body>
  <h1>エアコンのリモコン</h1>

  <section>
	<h2>室温</h2>
	<p id="temp"><img src="<?php print $tempicon; ?>"> <?php print $temp; ?> &#08451;</p>
  </section>

  <section>
	<h2>エアコン操作</h2>
<?php
if (isset($_SESSION['result'])) {
	print $_SESSION['result'];
	$_SESSION['result'] = '';
}
?>
	<form action="<?php print $redirectTo; ?>" method="post" id="aircon">
		<input type="submit" name="open" value="電源ボタンを押す">
	</form>
  </section>

  <section>
	<h2>タイマー操作</h2>
<?php
if (isset($_SESSION['resultTimer'])) {
	print $_SESSION['resultTimer'];
	$_SESSION['resultTimer'] = '';
}
?>
	<form action="<?php print $redirectTo; ?>" method="post" id="aircontimer">
		<input type="number" name="time" style="width:3em;">分後にボタンを押す 
		<input type="submit" name="timerset" value="決定">
	</form>
  </section>
</body>
</html>

