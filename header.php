<?xml version="1.0"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="content-language" content="en" />
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" media="screen,projection" type="text/css" href="css/reset.css" /> <!-- RESET -->
	<link rel="stylesheet" media="screen,projection" type="text/css" href="css/main.css" /> <!-- MAIN STYLE SHEET -->
	<link rel="stylesheet" media="screen,projection" type="text/css" href="css/2col.css" title="2col" /> <!-- DEFAULT: 2 COLUMNS -->
	<link rel="alternate stylesheet" media="screen,projection" type="text/css" href="css/1col.css" title="1col" /> <!-- ALTERNATE: 1 COLUMN -->
	<!--[if lte IE 6]><link rel="stylesheet" media="screen,projection" type="text/css" href="css/main-ie6.css" /><![endif]--> <!-- MSIE6 -->
	<link rel="stylesheet" media="screen,projection" type="text/css" href="css/style.css" /> <!-- GRAPHIC THEME -->
	<link rel="stylesheet" media="screen,projection" type="text/css" href="css/mystyle.css" /> <!-- WRITE YOUR CSS CODE HERE -->
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
	<script type="text/javascript" src="js/jquery.switcher.js"></script>
	<script type="text/javascript" src="js/toggle.js"></script>
	<script type="text/javascript" src="js/ui.core.js"></script>
	<script type="text/javascript" src="js/ui.tabs.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		$(".tabs > ul").tabs();
		$.tablesorter.defaults.sortList = [[1,0]];
		$("table").tablesorter({
			headers: {
				0: { sorter: false },
				7: { sorter: false }
			}
		});
	});
	</script>
	<title>Reception Desk Scheduler</title>
</head>

<body>

<?php
//permissions management
$management=1;

?>

<div id="main">

	<!-- Tray -->
	<div id="tray" class="box">

		<p class="f-left box">

			<!-- Switcher -->
			<span class="f-left" id="switcher">
				<a href="#" rel="1col" class="styleswitch ico-col1" title="Display one column"><img src="design/switcher-1col.gif" alt="1 Column" /></a>
				<a href="#" rel="2col" class="styleswitch ico-col2" title="Display two columns"><img src="design/switcher-2col.gif" alt="2 Columns" /></a>
			</span>

			Toggle sidebar

		</p>

		<p class="f-right">User: <strong><a href="#">Administrator</a></strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong><a href="#" id="logout">Log out</a></strong></p>

	</div> <!--  /tray -->

	<hr class="noscreen" />

	<!-- Menu -->
	<!-- /header -->

	<hr class="noscreen" />

	<!-- Columns -->
	<div id="cols" class="box">

		<!-- Aside (Left Column) -->
		<div id="aside" class="box">

			<div class="padding box">

				<!-- Logo (Max. width = 200px) -->
				<p><a href="#"><img src="img/logo.png" alt="Our logo" title="Visit Site" /></a></p>

			</div> <!-- /padding -->

			<ul class="box">
				<?php
				$curr_page = basename($_SERVER["SCRIPT_NAME"]);

				echo "<li "; if ($curr_page=="index.php") echo "id=\"submenu-active\""; echo "><a href=\"index.php\">Schedule</a></li>";
				echo "<li "; if ($curr_page=="status.php") echo "id=\"submenu-active\""; echo "><a href=\"status.php\">Status</a></li>";
				if ($management) {
					echo "<li "; if ($curr_page=="manage.php") echo "id=\"submenu-active\""; echo "><a href=\"manage.php\">Manage</a></li>";
					echo "	<ul>";
					echo "		<li><a href=\"manage.php?option=ListUsers\">List Users</a></li>";
					echo "		<li><a href=\"manage.php?option=GenerateAvail\">Master Availability Sheet</a></li>";
					echo "		<li><a href=\"manage.php?option=place\">Place Deskies at Desks</a></li>";
					echo "		<li><a href=\"manage.php?option=schedule\">Schedule Deskies</a></li>";
					echo "		<li><a href=\"manage.php?option=ClearDB\">Clear Database</a></li>";
					echo "	</ul>";
				}
				?>
			</ul>

		</div> <!-- /aside -->

		<hr class="noscreen" />

		<!-- Content (Right Column) -->
		<div id="content" class="box">
