<html>
<head>
<meta name="Content-Type" value="text/html; charset={$CHARSET}" />
<link rel="stylesheet" href="styles/default.css" type="text/css" />
</head>
<body>
<a href="index.php">Start</a>
| <a href="userpanel.php">{if !isset($auth) || $auth->isAnonymous()}Anmelden{else}{$auth->getAddress()}{/if}</a>
{if isset($auth) && !$auth->isAnonymous()} | <a href="userpanel.php?logout">Abmelden</a>{/if}
