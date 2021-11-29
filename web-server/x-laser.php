<?php
include "s-nocache.php";
include "s-error-handler.php";
// read in the status file to see if the lights are on at the moment
include "s-get-status.php";
include "s-check-lights-on.php";
// Read in the current laser state (into $j_arr)
$j_file="j-dmx-las.json";
include "s-get-json-nolock.php";
# If it's gone wrong, set to off
if ($j_arr == array()) $j_arr = json_decode('{"l_mode":"off"}',true);
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
    .home {
        color: rgb(118, 50, 63);
        text-decoration: none;
        border: 2px;
        border-style:solid;
    }
	.warning { /* will scroll to top of page then stop */
		position: sticky; top: 0; 
		background-color:rgb(118, 50, 63);
		padding:5px;
		color:white;
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
	/*----------------- Slider ---*/
	.colour-slider {
	  -webkit-appearance: none;
	  width: 100%;
	  height: 25px;
	  background: #d3d3d3;
	  background-image: linear-gradient(to right, red, yellow, green, cyan, blue, magenta, red);
	  outline: none;
	  opacity: 0.7;
	  -webkit-transition: .2s;
	  transition: opacity .2s;
	}
	/*----------------- COLLAPSIBLE ---*/
	/* Style the button that is used to open and close the collapsible content */
	.collapsible {
		background-color:rgb(192, 159, 128);
		font-size:18px; 
		color: white;
		cursor: pointer;
		padding: 15px 15px 15px 5px;
		width: 100%;
		border: none;
		text-align: left;
		outline: none;
	}
	.collapsible:before {
		content: '\025B6'; /* Unicode character for "plus" sign (+) */
		color: white;
		float: left;
		margin: 0px 15px 0px 5px;
	}

	.active:before {
		content: "\025BC";
	}
	
</style>
</head>
<body>
	<h1><a href="index.php" class="home">&nbsp;&lt;&nbsp</a> Laser Control</h1>
	<div class="warning" style="display:<?php echo ($lightson?'none':'block'); ?>">
		The lights are switched off at the moment. They should be back
		<?php echo ($until==0? 'soon.': 'at '.date('H:i', $until)); ?>
	</div>
	<button id="off" class="collapsible">OFF</button>
	<div style="display:none;">
		<p>You have now switched the lasers OFF</p>
	</div>
	<button id="tur" class="collapsible">Turning</button>
	<div style="display:none;">
		<p>Laser brightness</p>
		<p>Red</p>
		<input id="l_R" onchange="sendChange('l_R')" type="range" min="0" max="255" class="colour-slider" style="	  background-image: linear-gradient(to right, black, red);" value="<?php echo $j_arr['l_R']?>">
		<p>Green</p>
		<input id="l_G" onchange="sendChange('l_G')" type="range" min="0" max="255" class="colour-slider" style="	  background-image: linear-gradient(to right, black, green);" value="<?php echo $j_arr['l_G']?>">
		<p>Blue</p>
		<input id="l_B" onchange="sendChange('l_B')" type="range" min="0" max="255" class="colour-slider" style="	  background-image: linear-gradient(to right, black, blue);" value="<?php echo $j_arr['l_B']?>">
		<p>Turning speed and direction. Stop is in the middle</p>
		<input id="l_spd" onchange="sendChange('l_spd')" type="range" min="1" max="10" value="<?php echo $j_arr['l_spd']?>">
		<p>Flash speed, stop is on the left</p>
		<input id="l_strobe" onchange="sendChange('l_strobe')" type="range" min="0" max="4" value="<?php echo $j_arr['l_strobe']?>">
	</div>
	<button id="seq" class="collapsible">Random</button>
	<div style="display:none;">
		<p>Choose how intense you want the sequence to be</p>
		<p>Speed and density</p>
		<input id="l_seq" onchange="sendChange('l_seq')" type="range" min="1" max="5" value="<?php echo $j_arr['l_seq']?>">
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
var dmxState=<?php echo (json_encode($j_arr)); ?>;

function sendChange(id) {
    // Send the json to update dmx state
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {
		if (this.readyState != 4) return;
	};
	xhr.open("POST", "s-put-json-nolock.php", true);
	// can't get application/json to work so have to use form encoding
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	//xhr.setRequestHeader('Content-type', 'application/json');

	// send the collected data as JSON
	if (id != '') dmxState[id] = document.getElementById(id).value;
	dmxState['fn'] = 'j-dmx-las.json';
	dmxState['l_ts'] = Math.floor(Date.now()/1000).toString();
	xhr.send('json='+JSON.stringify(dmxState));
	//alert('json='+JSON.stringify(dmxState));
}
</script>
<script>
	//
	//-------------- onClick for Collapsible sections ----------------//
	//
	var coll = document.getElementsByClassName("collapsible");
	var i;

	for (i = 0; i < coll.length; i++) {
		coll[i].addEventListener("click", function() {
			for (i = 0; i < coll.length; i++) {
				var content = coll[i].nextElementSibling;
			    if (coll[i] == this) {
					this.classList.add("active");
					content.style.display = "block";
				}
				else {
					coll[i].classList.remove("active");
					content.style.display = "none";
				}
			}
			dmxState['l_mode'] = this.id;
			sendChange('');
		});
	}
</script>	
		
</body>
</html>
