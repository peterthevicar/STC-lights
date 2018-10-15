<?php
// Set up error handler and err function for logging errors
include "error-handler.php";

// System control interface

$req=($_GET == []? '': $_GET['mode']);

// Get the current status
$status_file = 'json-status.json';
if (file_exists($status_file)) $status = json_decode(file_get_contents($status_file), true);
else $status = '';
if ($status == '' or 
!array_key_exists('st',$status) or 
!array_key_exists('et',$status) or 
!array_key_exists('br',$status)) {
	$status = json_decode('{"on":"ON", "tim":false, "st":"16:00", "et":"01:00", "br":"128"}', true);
}

err('DEBUG:sysctl:13 status='.json_encode($status));

if ($req == '') {
}
else {
	// Have a request
	// Overwrite st and et if provided
	if (array_key_exists('st',$_GET)) $status['st'] = $_GET['st'];
	if (array_key_exists('et',$_GET)) $status['et'] = $_GET['et'];
	if (array_key_exists('br',$_GET)) $status['br'] = $_GET['br'];
	$status['tim'] = false; // Only set true if we get a tim request
	// Process the request itself
	if ($req == 'off') {
		$status['on']='OFF';
	}
	else if ($req == 'sta') {
		// TODO Put standby sequence into the queue
		$status['on']='OFF';
	}
	else if ($req == 'tim') {
		// Calculate whether it's on or off according to the timer
		$st = strtotime($status['st']);
		$et = strtotime($status['et']);
		if ($st < $et) //Normal case: start time before off time
			$lightson = time() >= $st and time() < $et;
		else //off time before start time means off time is after midnight so logic is reversed
			$lightson = !(time() >= $et and time() < $st);
		$status['on'] = ($lightson? 'ON': 'OFF');
		$status['tim'] = true;
	}
	else if ($req == 'cou') {
		// TODO Put a countdown sequence into the queue
		$status['on']='ON';
	}
	else if ($req == 'fon') {
		$status['on']='ON';
	}
	// Write back the modified status file
	file_put_contents($status_file, json_encode($status));
}
?>
<html>
	<head>
		<style>
			button {
				width: 200px;
				height: 100px;
				font-size: 15pt;
				border-radius: 10px;
			}
			input {
				display:block;
				margin: 0 auto;
			}
			p.head {
				font-size: 10pt;
				margin: 10px auto 0 auto;
				text-align: center;
			}
		</style>
	</head>
	<body>
		<p>System status: <?php echo file_get_contents($status_file);?>
		<div>
			<button type=button style="background-color:red" onclick="do_button('off')">OFF</button>
			<button type=button style="background-color:orange" onclick="do_button('sta')">STANDBY</button>
			<button type=button style="background-color:cyan" onclick="do_button('cou')">Countdown</button>
			<button type=button style="background-color:yellow" onclick="do_button('fon')">FORCE ON</button>
		</div>
		<div style="border:5px solid lightgreen; width:200px; padding-bottom:10px;">
			<button type=button style="background-color:lightgreen" onclick="do_button('tim')">Timer controlled</button>
			<p class=head>On time</p>
			<input type=time id=st autocomplete=off value="<?php echo $status['st'] ?>">
			<p class=head>Off time</p>
			<input type=time id=et autocomplete=off value="<?php echo $status['et'] ?>">
		</div>
		<div style="border:5px solid gray; width:200px; padding-bottom:10px;">
			<p class=head>Brightness</p>
			<input type=number id=br autocomplete=off value="<?php echo $status['br'] ?>" max=255 min=0>
		</div>
		<script>
			function do_button(id) {
				// Call this file again with the button id
				location.href = 'sysctl.php?mode=' + id +
					'&st='+document.getElementById("st").value +
					'&et='+document.getElementById("et").value +
					'&br='+document.getElementById("br").value;
			}
		</script>
	</body>
</html>
<!--
TODO
	Max brightness
	Time on / off
-->
