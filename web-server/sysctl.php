<?php
// Set up error handler and err function for logging errors

include "s-error-handler.php";

// System control interface

$req=(($_GET != [] and array_key_exists('mode',$_GET))? $_GET['mode']: '');
if ($req == '' 
and !(array_key_exists('QUERY_STRING',$_SERVER) and $_SERVER['QUERY_STRING'] == 'st') 
and (array_key_exists('SERVER_ADDR',$_SERVER))) {
	echo '<html><body><p>Welcome to the St.Thomas IP logger, your IP ' . $_SERVER['SERVER_ADDR'] . ' has been recorded.</body></html>';
	exit(1);
}
//~ err('DEBUG:sysctl:12 req='.$req);
//~ $req='cou'; // DEBUG: when running stand-alone, comment out above and use this
// read in current status
include "s-get-status.php";
//~ err('DEBUG:sysctl:11 status='.json_encode($status));

// see if we're being called to change things or just to update
if ($req == '') {
	// Just an update
}
else {
	// Have a request
	// Overwrite st and et if provided
	if (array_key_exists('st',$_GET)) $status['st'] = $_GET['st'];
	if (array_key_exists('et',$_GET)) $status['et'] = $_GET['et'];
	if (array_key_exists('br',$_GET)) $status['br'] = $_GET['br'];
	// Process the request itself
	if ($req == 'off') {
		$status['on']='OFF';
	}
	else if ($req == 'sta') {
		$status['on']='STA';
	}
	else if ($req == 'tim') {
		$status['on']='TIM';
	}
	else if ($req == 'cou') {
		// Put a countdown sequence into the queue
		$next_t = strval(time()+20);
		file_put_contents('j-q.json', '{"cur_id":"id1","next_t":'.strval(time()).',"q":["COU",25]}');
		//~ err('DEBUG:sysctl:44 next_t='.$next_t.' time='.strval(time()).' q='.file_get_contents('j-q.json'));
		$status['on']='ON';
	}
	else if ($req == 'fon') {
		$status['on']='ON';
	}
	else if ($req == 'reb') {
		$status['on']='REB'; // Picked up by the next call to de-q
	}
	// Write back the modified status file
	file_put_contents($status_file, json_encode($status));
}
// calculate $lightson from the status settings
include "s-check-lights-on.php";
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
			button {
				width: 200px;
				height: 100px;
				font-size: 15pt;
				border-radius: 10px;
			}
			button.current {
				border-color:magenta;
				border-width:5px;
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
			p.on {
				background-color:yellow;
			}
			p.off {
				background-color:silver;
			}
		</style>
	</head>
	<body>
		<p class="<?php echo ($lightson?'on': 'off');?>">System status at <?php echo date('H:i', time()).'<br>lightson='.($lightson?'true':'false').', until='.($until==0? '0': date('H:i', $until)).'<br>s-status='.file_get_contents($status_file);?>
		<p>Queue: <?php $q = json_decode(file_get_contents('j-q.json'), true); echo 'last de-q pulse: <b>'.strval(time()-filemtime('s-pulse')).'</b> seconds ago; cur_id: <b>'.$q['cur_id'].'</b>; next_t: <b>'.date('H:i:s', $q['next_t']).'/'.strval($q['next_t']-time()).'</b>; queue: '.json_encode($q['q']); ?>
		<div>
			<button type=button <?php echo ($status['on']=='OFF'?'class="current" ': ''); ?>style="background-color:red" onclick="do_button('off')">OFF</button>
			<button type=button <?php echo ($status['on']=='STA'?'class="current" ': ''); ?>style="background-color:orange" onclick="do_button('sta')">STANDBY</button>
			<button type=button style="background-color:cyan" onclick="do_button('cou')">Countdown</button>
			<button type=button <?php echo ($status['on']=='ON'?'class="current" ': ''); ?>style="background-color:yellow" onclick="do_button('fon')">FORCE ON</button>
		</div>
		<div style="border:5px solid lightgreen; width:200px; padding-bottom:10px;">
			<button type=button <?php echo ($status['on']=='TIM'?'class="current" ': ''); ?>style="background-color:lightgreen" onclick="do_button('tim')">Timer controlled</button>
			<p class=head>On time</p>
			<input type=time id=st autocomplete=off value="<?php echo $status['st'] ?>">
			<p class=head>Off time</p>
			<input type=time id=et autocomplete=off value="<?php echo $status['et'] ?>">
		</div>
		<div style="border:5px solid gray; width:200px; padding-bottom:10px;">
			<p class=head>Brightness</p>
			<input type=number id=br autocomplete=off value="<?php echo $status['br'] ?>" max=255 min=0>
		</div>
		<button type=button style="background-color:grey;color:white" onclick="location.href='s-jd-check.php'">Check and back up j-displays</button>

		<p style="display:block; padding-top:100px">HERE BE DRAGONS!!<br><br>
		Check the de-q pulse at the top of this page to see if the reboot call has been picked up. <br>
		Make sure to select another button once it has been picked up or you will get a<br>
		BOOT LOOP</p>
		<button type=button <?php echo ($status['on']=='REB'?'class="current" ': ''); ?>style="background-color:black; color:white;" onclick="do_button('reb')">REBOOT RPi</button>
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
