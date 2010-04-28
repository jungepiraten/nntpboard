<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/auth.class.php");
require_once(dirname(__FILE__)."/classes/smarty.class.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$smarty = new UserPanelSmarty($config, $session->getAuth());

if (isset($_REQUEST["login"])) {
	$user = isset($_REQUEST["username"]) ? stripslashes($_REQUEST["username"]) : null;
	$pass = isset($_REQUEST["password"]) ? stripslashes($_REQUEST["password"]) : null;

	try {
		$auth = $config->getAuth($user, $pass);
		$session->login($auth);
		$smarty->viewloginsuccess($auth);
	} catch (AuthException $e) {
		$smarty->viewloginfailed();
		exit;
	}
}

if (isset($_REQUEST["logout"])) {
	$session->logout();
	$smarty->viewlogoutsuccess();
}

if ($session->getAuth()->isAnonymous()) {
	$smarty->viewloginform();
	exit;
}

$smarty->viewuserpanel();

?>
