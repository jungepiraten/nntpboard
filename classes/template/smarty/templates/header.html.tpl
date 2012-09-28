<!DOCTYPE html>
<html dir="ltr">
	<head>
		<meta http-equiv="content-type" content="text/xhtml; charset=UTF-8" />
		<link href="libs/bootstrap-2.1.1/css/bootstrap.min.css" rel="stylesheet" />
		<link href="libs/bootstrap-2.1.1/css/bootstrap-responsive.min.css" rel="stylesheet" />
		<script src="libs/bootstrap-2.1.1/js/bootstrap.min.js"></script>
		<link href="libs/font-awesome/css/font-awesome.css" rel="stylesheet" />
		<link href="classes/template/smarty/style.css" rel="stylesheet" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		{literal}
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
			<script src="libs/ie-html5.js"></script>
		<![endif]-->

		<title>NNTPBoard &bull; {$title|escape:html}</title>
	</head>
	<body>
		<div class="visible-desktop spacer-top">&nbsp;</div>

		<div class="navbar navbar-fixed-top navbar-inverse">
			<div class="navbar-inner">
				<div class="container-fluid">
					<a class="brand" href="index.php">
						NNTPBoard
					</a>
					<ul class="nav">
						<li class="active"><a href="index.php">Forenübersicht</a></li>
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
					<div class="well sidebar-nav">
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
					<h1>{$title|escape:html}</h1>

