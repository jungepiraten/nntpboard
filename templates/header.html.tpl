<html>
<head>
<meta name="Content-Type" value="text/html; charset={$CHARSET}" />
<link rel="stylesheet" href="styles/default.css" type="text/css" />
</head>
<body>
<ul class="navigation global">
 <li class="start"><a href="index.php" class="start">Start</a></li>
 <li class="userpanel"><a href="userpanel.php" class="userpanel">{if !isset($auth) || $auth->isAnonymous()}Anmelden{else}{$auth->getAddress()}{/if}</a></li>
 {if isset($auth) && !$auth->isAnonymous()}<li class="logout"><a class="logout" href="userpanel.php?logout">Abmelden</a></li>{/if}
</ul>
<div class="body">
