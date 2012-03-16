<!DOCTYPE html>
<html dir="ltr">
	<head>
		<meta name="Content-Type" content="text/html; charset={$CHARSET}" />
		<link href="/bootstrap/css/bootstrap.css" rel="stylesheet">
		<link href="/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
		<link rel="icon" type="image/png" href="images/favicon.png" />

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
				<a class="brand" href="/">
					NNTPBoard
				</a>
				<ul class="nav">
					<li class="active"><a href="/">Forenübersicht</a></li>
					<li><a href="https://ucp.junge-piraten.de/index.php?module=lists">Mailinglisten</a></li>
				</ul>

				{if $ISANONYMOUS}
					<form class="navbar-form pull-right form-inline" action="/login.php" method="POST">
						<input type="hidden" name="redirect" value="/" />
						<input type="text" name="username" class="span2" placeholder="Loginname" />
						<input type="password" name="password" class="span2" placeholder="Passwort" />
						<button type="submit" class="btn btn-primary">Anmelden</button>
					</form>
				{else}
					<a href="/logout.php" class="btn btn-danger pull-right">Abmelden</a>
				{/if}

				<ul class="nav pull-right">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">Junge Piraten <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li class="active"><a href="/">Forum</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>


	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span3">
				<div class="well sidebar-nav">
					<ul class="nav nav-list">
						<li class="nav-header">Navigation</li>
						<li><a href="/"><i class="icon-home"></i> Forenübersicht</a></li>
						{if $ISANONYMOUS}
							<li><a href="/login.php">Anmelden</a></li>
							<li><a href="https://ucp.junge-piraten.de/index.php?module=register">Registrieren</a></li>
						{else}
							<li><a href="/logout.php"><i class="icon-off"></i> Abmelden</a></li>							
						{/if}
						<li><a href="/unread.php"><i class="icon-flag"></i> Alle als gelesen markieren</a></li>
						<li><a href="https://ucp.junge-piraten.de/index.php?module=lists" class="lists"><i class="icon-envelope"></i> Mailinglisten</a></li>

						<li class="nav-header">Foren</li>
						{foreach from=$ROOTBOARD.childs item=board name=counter}
							<li><a href="#"><i class="icon-list-alt"></i> {$board.name|escape:html}</a></li>
						{/foreach}
					</ul>
				</div>
			</div>


        <div class="span9">

		<h1 style="margin-bottom: 20px;">{$title|escape:html}</h1>
		<div class="alert alert-info"><a class="close">&times;</a> Das NNTPBoard ist das Forum der Jungen Piraten. Alles, was hier gepostet wird, kommt auch automatisch auf die entsprechenden Mailinglisten und ist im entsprechenden NNTP-Thread verfügbar (Und umgedreht). Achte daher bitte darauf, deinen Text so zu formulieren, wie du es bei einer normalen E-Mail auch tun würdest.</div>

