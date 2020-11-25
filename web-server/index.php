<?php
include "s-nocache.php";
include "s-error-handler.php";
// read in the status file to see if the lights are on at the moment
include "s-get-status.php";
include "s-check-lights-on.php";
//
// ----------------------------- END PHP -----------------------------//
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- -->
<!-- ---------------------- CSS ------------------- -->
<!-- -->
<style type="text/css">
	@import url('https://fonts.googleapis.com/css?family=Paprika');
	@import url('https://fonts.googleapis.com/css?family=Open+Sans');
	html {
		background-color:white;
		color:rgb(102, 102, 102);
		font-family:'Open Sans';
	}
	body {
		margin: 0 5px 0 5px;
		font-size: 15px;
	}
	h1 {
		font-family: 'Paprika', serif;
		background-image: url('h1-flourish.png');
		background-repeat: no-repeat;
		background-position: bottom left;
		color: rgb(118, 50, 63);
		font-weight: 600;
		text-transform: uppercase;
		min-height: 70px;
		font-size: 34px;
		padding-bottom: 10px !important;
	}
	.warning { /* will scroll to top of page then stop */
		position: sticky; top: 0; 
		background-color:rgb(118, 50, 63);
		padding:5px;
		color:white;
	}
	.go-button {
		height:100px; width:120px;
		border-radius:8px;
		padding: 5px;
		font-size:20px; color:white;
		display:inline-block;
		vertical-align: top;
	}
	.footer {
		width: 100%;
		margin-top: 10px;
	}
	.footer-text {
		background-color:rgb(86, 86, 86);
		color: rgb(150, 150, 150);
		padding: 5px;
		margin: 0 0 0 0;
	}
	.footer a {
		color:white; font-weight: bold;
		text-decoration: none;
	}
	.go-button {
		height:100px; width:120px;
		border-radius:8px;
		padding: 5px;
		font-size:20px; color:white;
		display:inline-block;
		vertical-align: top;
	}
</style>
</head>
<body>
	<h1>St.Thomas Christmas Lights</h1>
	<div class="warning" style="display:<?php echo ($lightson?'none':'block'); ?>">
		The lights are switched off at the moment. They should be back
		<?php echo ($until==0? 'soon.': 'at '.date('H:i', $until)); ?>
	</div>
	</div>
	<div>
		<h2>What do you want to change?</h2>
		<button class="go-button" onclick="window.location.href='d-choose.php'" style="background-color:red">Animated display</button>
<!--
		<button class="go-button" onclick="window.location.href='x-laser.php'" style="background-color:green">Laser</button>
-->
		<button class="go-button" onclick="window.location.href='x-floods-n.php?Top'" style="background-color:green">Top floods</button>
		<button class="go-button" onclick="window.location.href='x-floods-n.php?Clock'" style="background-color:blue">Clock floods</button>
		<button class="go-button" onclick="window.location.href='x-floods-n.php?Window'" style="background-color:purple">Window floods</button>
	</div>
	<div class="footer">
		<div class="footer-text">
			St.Thomas Church:
			the town church for Lymington, offering
			prayer and hospitality in Jesus' name.<br>
			<a href="https://lymingtonchurch.org">Click here for the church web site.</a>
		</div>
	</div>
	
</body>
</html>
