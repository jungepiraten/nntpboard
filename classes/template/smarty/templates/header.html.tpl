<!DOCTYPE html>
<html dir="ltr">
	<head>
		<meta name="Content-Type" content="text/html; charset={$CHARSET}" />
		<link href="bootstrap/css/bootstrap.css" rel="stylesheet">
		<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
		<link rel="icon" type="image/png" href="images/favicon.png" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<style type="text/css">
		{literal}
			body {
				padding-top: 60px;
				padding-bottom: 40px;
			}
		{/literal}
		</style>

		<!--[if lt IE 9]>
			<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->

		<title>Junge Piraten &bull; {$title|escape:html}</title>
	</head>
<body>

	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="brand" href="index.php">
					NNTPBoard
				</a>
				<ul class="nav">
					<li class="active"><a href="index.php">Forenübersicht</a></li>
					<li><a href="https://ucp.junge-piraten.de/index.php?module=lists">Mailinglisten</a></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">Junge Piraten <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="https://www.junge-piraten.de/">Homepage</a></li>
							<li><a href="https://www.junge-piraten.de/mitmachen/">Mitmachen</a></li>
							<li class="active"><a href="index.php">Forum</a></li>
							<li><a href="https://wiki.junge-piraten.de/">Wiki</a></li>
							<li><a href="http://jupis.piratenpad.de/">Piratenpad</a></li>
							<li><a href="https://ucp.junge-piraten.de/">UCP</a></li>
							<li><a href="https://www.junge-piraten.de/presse">Presse</a></li>
						</ul>
					</li>
				</ul>

				{if $ISANONYMOUS}
					<form class="navbar-form pull-right form-inline" action="login.php" method="POST">
						<input type="hidden" name="login" value="1" />
						<input type="text" name="username" class="span2" placeholder="Loginname" />
						<input type="password" name="password" class="span2" placeholder="Passwort" />
						<button type="submit" class="btn btn-primary">Anmelden</button>
					</form>
				{else}
					<a href="logout.php" class="btn btn-danger pull-right"><i class="icon-off icon-white"></i> Abmelden</a>
				{/if}
			</div>
		</div>
	</div>


	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span3 hidden-phone">
				<div class="well sidebar-nav" style="padding: 8px 0;">
					<ul class="nav nav-list">
						<li class="nav-header">Navigation</li>
						<li><a href="index.php"><i class="icon-home"></i> Forenübersicht</a></li>
						{if $ISANONYMOUS}
							<li><a href="login.php"><i class="icon-user"></i> Anmelden</a></li>
							<li><a href="https://ucp.junge-piraten.de/index.php?module=register"><i class="icon-cog"></i> Registrieren</a></li>
						{else}
							<li><a href="logout.php"><i class="icon-off"></i> Abmelden</a></li>							
						{/if}
						<li><a href="unread.php?markread="><i class="icon-flag"></i> Alle als gelesen markieren</a></li>
						<li><a href="//ucp.junge-piraten.de/index.php?module=lists" class="lists"><i class="icon-envelope"></i> Mailinglisten</a></li>
{php}
function isSubBoard($destBoard, $curBoard) {
	if (!isset($destBoard["parent"])) {
		return false;
	} else if ($curBoard["boardid"] == $destBoard["parent"]["boardid"] || $curBoard["boardid"] == $destBoard["boardid"]) {
		return true;
	} else {
		return isSubBoard($destBoard["parent"], $curBoard);
	}
}
{/php}

						<li class="nav-header">Foren</li>
						{include file="header_forennavigation.html.tpl" curboard=$ROOTBOARD}
					</ul>
				</div>
			</div>


        <div class="span9">

		<h1 style="margin-bottom: 20px;">{$title|escape:html}</h1>

