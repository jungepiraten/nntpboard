<html>
<head>
<meta name="Content-Type" value="text/html; charset={$CHARSET}" />
<link rel="stylesheet" href="styles/default.css" type="text/css" />
<link rel="icon" type="image/png" href="images/favicon.png" />
<title>Junge Piraten &bull; {$board.name|escape:html}</title>
</head>
<body>
<div class="seite">
<div class="headerdiv">
<img src="images/logo.png">
<div class="buttondiv">
<a href="index.php" class="start">Start</a> | 
{if $ISANONYMOUS}<a href="userpanel.php" class="userpanel">Anmelden{else}{$ADDRESS}</a>{/if}
{if !$ISANONYMOUS} | <li class="logout"><a class="logout" href="userpanel.php?logout">Abmelden</a>{/if}
</div></div><h1 class="mainname">{$board.name|escape:html}</h1>
