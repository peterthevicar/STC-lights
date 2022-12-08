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
<link rel="stylesheet" href="styles.css" type="text/css">

</head>
<body>
	<h1>Christmas Lights Control</h1>
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

		<button class="go-button" onclick="window.location.href='x-floods-n.php?Top'" style="background-color:green">Top floods</button>

		<button class="go-button" onclick="window.location.href='x-floods-n.php?Clock'" style="background-color:blue">Clock floods</button>
		<button class="go-button" onclick="window.location.href='x-floods-n.php?Window'" style="background-color:purple">Window floods</button>
-->
	</div>
	<div class="footer">
		<div class="footer-text">
			Christmas in Bicester 9-11th December 2022
		</div>
	</div>
	
</body>
</html>
