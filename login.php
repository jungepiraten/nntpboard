<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

if (isset($_REQUEST["login"])) {
	$user = isset($_REQUEST["username"]) ? stripslashes($_REQUEST["username"]) : null;
	$pass = isset($_REQUEST["password"]) ? stripslashes($_REQUEST["password"]) : null;
	$permanent = isset($_REQUEST["permanent"]);

	try {
		$auth = $config->getAuth($user, $pass);
		if ($permanent) {
			$session->permanentLogin($auth);
		} else {
			$session->login($auth);
		}
		$template->viewloginsuccess($auth);
	} catch (AuthException $e) {
		$template->viewloginfailed();
	}
}

$template->viewloginform();

?>
