<!DOCTYPE html>
<html dir="ltr">
	<head>
		<meta http-equiv="content-type" content="text/xhtml; charset={$CHARSET}" />
		<link href="https://static.junge-piraten.de/bootstrap-2.1.1/css/bootstrap.min.css" rel="stylesheet" />
		<link href="https://static.junge-piraten.de/bootstrap-2.1.1/css/bootstrap-responsive.min.css" rel="stylesheet" />
		<link href="https://static.junge-piraten.de/bootstrap-jupis.css" rel="stylesheet" />
		<script src="https://static.junge-piraten.de/jquery.min.js"></script>
		<script src="https://static.junge-piraten.de/bootstrap-2.1.1/js/bootstrap.min.js"></script>
		<link rel="icon" type="image/png" href="https://static.junge-piraten.de/favicon.png" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		{literal}
		<style type="text/css">
			body {
				padding-top: 60px;
				padding-bottom: 40px;
			}

			.no-padding {
				padding:0px;
			}

			.no-top-bottom-margin {
				margin-top:0px;
				margin-bottom:0px;
			}

			input, textarea {
				background-color:white;
			}
		</style>
		<script type="text/javascript">
		$(function() {
			$("a.btn").click(function (event) {
				if ($(this).hasClass("disabled")) {
					return false;
				}
				$(this).addClass("disabled");
			});
			$("form").submit(function (event) {
				if ($(this).hasClass("disabled")) {
					return false;
				}
				$(this).addClass("disabled").find(".btn").addClass("disabled");
			});
		});
		</script>
		{/literal}

		<!--[if lt IE 9]>
			<script src="https://static.junge-piraten.de/ie-html5.js"></script>
		<![endif]-->

		<title>Junge Piraten &bull; {$title|escape:html}</title>
	</head>
	<body>
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container-fluid">
					<a class="brand" href="index.php">
						NNTPBoard
					</a>
					<ul class="nav">
						<li class="active"><a href="index.php">Forenübersicht</a></li>
						<li class="dropdown hidden-phone">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">Junge Piraten <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="https://www.junge-piraten.de/">Homepage</a></li>
								<li><a href="https://www.junge-piraten.de/community/">Mitmachen</a></li>
								<li class="active"><a href="index.php">Forum</a></li>
								<li><a href="https://wiki.junge-piraten.de/">Wiki</a></li>
								<li><a href="https://ucp.junge-piraten.de/">UCP</a></li>
								<li><a href="https://pad.junge-piraten.de/">Pads</a></li>
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
							<li><a href="https://ucp.junge-piraten.de/index.php?module=lists" class="lists"><i class="icon-envelope"></i> Mailinglisten</a></li>
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

