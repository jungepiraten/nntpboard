<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$session->logout();
$template->viewlogoutsuccess();

?>
