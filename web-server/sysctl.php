<?php
include "s-nocache.php";
// Set up error handler and err function for logging errors
include "s-error-handler.php";

// Access code - change for each installation
$acc="hc";

$req=(($_GET != [] and array_key_exists('mode',$_GET))? $_GET['mode']: '');
if ($req == '' 
and !(array_key_exists('QUERY_STRING',$_SERVER) and $_SERVER['QUERY_STRING'] == $acc)
and (array_key_exists('SERVER_ADDR',$_SERVER))) {
	echo '<html><body><p>SYSCTL (System Compromise Threat Logger), your IP ' . $_SERVER['SERVER_ADDR'] . ' has been recorded.</body></html>';
	exit(1);
}
//~ err('DEBUG:sysctl:12 req='.$req);
//~ $req='cou'; // DEBUG: when running stand-alone, comment out above and use this
// read in current status
include "s-get-status.php";
//~ err('DEBUG:sysctl:17 status='.json_encode($status));
function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%ad,%h:%i:%s');
}
// see if we're being called to change things or just to update
if ($req == '') {
	// Just an update
}
else {
	// Have a request
	// Overwrite values if provided
	if (array_key_exists('st',$_GET)) $status['st'] = $_GET['st'];
	if (array_key_exists('et',$_GET)) $status['et'] = $_GET['et'];
	if (array_key_exists('brled',$_GET)) $status['brled'] = $_GET['brled'];
	if (array_key_exists('brdmx',$_GET)) $status['brdmx'] = $_GET['brdmx'];
	if (array_key_exists('brmet',$_GET)) $status['brmet'] = $_GET['brmet'];
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
		file_put_contents('j-q.json', '{"cur_id":"XXX","next_t":0,"q":["COU",25]}'); # countdown will start on next de-q
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
				width: 120px;
				height: 100px;
				font-size: 15pt;
				border-radius: 10px;
				vertical-align: top;
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
			p.warn {
				background-color:red;
				color:white;
			}
			p.ok {
				background-color:white;
				color:green;
			}
		</style>
	</head>
	<body>
		<h2>System status: <?php echo date('D H:i:s', time())?></h2>
		<p class="<?php echo ($lightson?'on': 'off');?>"><?php echo '<br>lightson='.($lightson?'true':'false').', until='.($until==0? '0': date('H:i', $until)).'<br>s-status='.file_get_contents($status_file);?>
        <?php
        // Look up all the time stamps
        $files=glob("ts-pulse*");
        foreach ($files as $tsf) {
            // Build a <p class="..."> tag with class as warn or ok depending on how long since previous timestamp
            echo '<p class="';
            $t = filemtime($tsf);
            $d = time()-$t;
            echo ($d>45?'warn': 'ok');
            echo '">';
            echo $tsf.': <b>'.strval($d).'</b> seconds ago';
        }
        ?>
        <p>Queue: <?php $q = json_decode(file_get_contents('j-q.json'), true); echo ('cur_id: <b>'.$q['cur_id'].'</b>; next_t: <b>'.date('H:i:s', $q['next_t']).' (in '.strval($q['next_t']-time()).'s)</b>; queue: '.json_encode($q['q'])); ?>
		<p class="<?php $t = filemtime('error-log.txt'); $d = time()-$t; echo ($t>filemtime('ts-error-check')?'warn': 'ok'); ?>">Error log: <?php echo 'last error: <b>'.date('D H:i:s', $t).' ('.secondsToTime($d).'</b> ago)'; ?>
		<div>
			<button type=button style="background-color:white" onclick="location.href='<?php echo "sysctl.php?{$acc}"?>'">REFRESH</button>
			<button type=button style="background-color:cream;color:black" onclick="location.href='s-error-check.php'">Check error log</button>
			<button type=button style="background-color:grey;color:white" onclick="location.href='s-jd-check.php'">Check and back up j-displays</button>
		</div>
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
			<p class=head>LED Brightness</p>
			<input type=number id=brled autocomplete=off value="<?php echo $status['brled'] ?>" max=255 min=0>
			<p class=head>DMX Brightness</p>
			<input type=number id=brdmx autocomplete=off value="<?php echo $status['brdmx'] ?>" max=255 min=0>
			<p class=head>Meteors</p>
			<input type=checkbox id=brmet autocomplete=off <?php echo ($status['brmet']=='true'?'checked=checked':'') ?>>
		</div>

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
					'&brled='+document.getElementById("brled").value+
					'&brdmx='+document.getElementById("brdmx").value+
					'&brmet='+(document.getElementById("brmet").checked?'true':'false');
			}
		</script>
	</body>
</html>
