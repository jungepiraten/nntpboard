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

				<form class="navbar-form pull-right">
					<input type="text" class="span2" placeholder="Loginname" />
					<input type="password" class="span2" placeholder="Passwort" />
					<button type="submit" class="btn btn-primary">Anmelden</button>
				</form>


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
						<li class="active"><a href="/">Forenübersicht</a></li>
						{if $ISANONYMOUS}
							<li><a href="/login.php">Anmelden</a></li>
							<li><a href="https://ucp.junge-piraten.de/index.php?module=register">Registrieren</a></li>
						{else}
							<li><a href="/logout.php">Abmelden</a></li>							
						{/if}
						<li><a href="/unread.php">Alle als gelesen markieren</a></li>
						<li><a href="https://ucp.junge-piraten.de/index.php?module=lists" class="lists">Mailinglisten</a></li>
					</ul>
				</div>
			</div>


        <div class="span9">


		<h1>{$title|escape:html}</h1>
