<?php
include "s-nocache.php";
include "s-error-handler.php";
// read in the status file to see if the lights are on at the moment
include "s-get-status.php";
include "s-check-lights-on.php";
// Read in the current dmx state (into $dmx)
include "x-get-dmx.php";
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
		background-image: url('https://lymingtonchurch.org/wp-content/uploads/2018/06/h1-flourish.png');
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
	.go-button.off {
		opacity: 0.5;
	}
	.go-button.on {
		opacity: 1.0;
	}
	
</style>
</head>
<body>
	<h1>Laser Control</h1>
	<div class="warning" style="display:<?php echo ($lightson?'none':'block'); ?>">
		The lights are switched off at the moment. They should be back
		<?php echo ($until==0? 'soon.': 'at '.date('H:i', $until)); ?>
	</div>
	</div>
	<div>
		<button id="R" class="go-button <?php echo ($dmx["R"]=="on"? "on": "off");?>" 
			onclick="toggleBut('R')" style="background-color:red">Red</button>
		<button id="G" class="go-button <?php echo ($dmx["G"]=="on"? "on": "off");?>" 
			onclick="toggleBut('G')" style="background-color:green">Green</button>
		<button id="B" class="go-button <?php echo ($dmx["B"]=="on"? "on": "off");?>" 
			onclick="toggleBut('B')" style="background-color:blue">Blue</button>
	</div>
	<div class="footer">
		<div class="footer-text">
			St.Thomas Church:
			the town church for Lymington, offering
			prayer and hospitality in Jesus' name.<br>
			<a href="https://lymingtonchurch.org">Click here for the church web site.</a>
		</div>
	</div>

<script>
var dmxState=<?php echo (json_encode($dmx)); ?>;

function toggleBut(id) {
	// Change internal representation
	dmxState[id] = (dmxState[id] == "on"? "off": "on");
	// Now the class for CSS styling
	e=document.getElementById(id);
	e.className="go-button " + dmxState[id];
	

    // Send the json to update dmx state
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {
		if (this.readyState != 4) return;
	};
	xhr.open("POST", "x-put-dmx.php", true);
	// can't get application/json to work so have to use form encoding
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	//xhr.setRequestHeader('Content-type', 'application/json');

	// send the collected data as JSON
	xhr.send('json='+JSON.stringify(dmxState));
	//alert('json='+JSON.stringify(dmxState));
}
</script>
		
</body>
</html>
