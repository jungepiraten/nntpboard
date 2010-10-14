<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" dir="ltr">
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
				<a href="index.php" class="start">Start</a>
				| <a href="https://ucp.junge-piraten.de/index.php?module=lists" class="lists">Mailinglisten</a>
				{if $ISANONYMOUS}
				| <a href="login.php" class="login">Anmelden</a>
				| <a class="register" href="https://ucp.junge-piraten.de/index.php?module=register">Registrieren</a>
				{else}
				| <a href="https://ucp.junge-piraten.de/" class="editprofile">{$ADDRESS}</a>
				| <a class="logout" href="logout.php">Abmelden</a>
				{/if}
			</div>
			<a href="index.php"><img src="images/logo.png" class="logo" /></a>
		</div>
		<a href="http://piratenpad.de/jupis-nntpboard-vorschlaege">Bitte hilf mit, NNTPBoard zu verbessern!</a>
		<h1 class="mainname">{$title|escape:html}</h1>
