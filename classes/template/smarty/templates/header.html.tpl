<html>
<head>
<meta name="Content-Type" value="text/html; charset={$CHARSET}" />
<link rel="stylesheet" href="styles/default.css" type="text/css" />
<link rel="icon" type="image/png" href="images/favicon.png" />
<title>Junge Piraten &bull; {$title|escape:html}</title>
</head>
<body>
<div class="seite">
<div class="headerdiv">
<div class="buttondiv">
<a href="index.php" class="start">Start</a> | 
<a href="userpanel.php" class="userpanel">{if $ISANONYMOUS}Anmelden{else}{$ADDRESS}{/if}</a>
{if $ISANONYMOUS} | <a class="register" href="http://wiki.junge-piraten.de/w/index.php?title=Spezial:Anmelden&amp;type=signup">Registrieren</a>
{else} | <a class="logout" href="userpanel.php?logout">Abmelden</a>{/if}
</div>
<a href="index.php"><img src="images/logo.png" class="logo" /></a>
</div>
<h1 class="mainname">{$title|escape:html}</h1>
